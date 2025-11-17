<?php

namespace App\Http\Controllers;

use App\Models\BacSignatory;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\ResolutionSignatory;
use App\Models\RfqSignatory;
use App\Models\Supplier;
use App\Services\BacResolutionService;
use App\Services\BacRfqService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BacQuotationController extends Controller
{
    public function index(Request $request): View
    {
        $requests = PurchaseRequest::withCount('items')
            ->with(['documents' => function($query) {
                $query->where('document_type', 'bac_resolution')->latest();
            }])
            ->where('status', 'bac_evaluation')
            ->latest()
            ->paginate(15);

        return view('bac.quotations.index', compact('requests'));
    }

    public function manage(PurchaseRequest $purchaseRequest): View|RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403);
        
        // Redirect to procurement method setting if not set yet
        if (empty($purchaseRequest->procurement_method)) {
            return redirect()->route('bac.procurement-method.edit', $purchaseRequest)
                ->with('error', 'Please set the procurement method first before managing quotations.');
        }
        
        $purchaseRequest->load(['items', 'documents', 'resolutionSignatories', 'rfqSignatories']);
        
        // Get the BAC resolution document if it exists
        $resolution = $purchaseRequest->documents()
            ->where('document_type', 'bac_resolution')
            ->latest()
            ->first();
        
        // Get the RFQ document if it exists
        $rfq = $purchaseRequest->documents()
            ->where('document_type', 'bac_rfq')
            ->latest()
            ->first();
        
        $suppliers = Supplier::where('status', 'active')->orderBy('business_name')->get();
        $quotations = Quotation::where('purchase_request_id', $purchaseRequest->id)
            ->with(['supplier', 'quotationItems.purchaseRequestItem'])
            ->get();
        
        // Get BAC signatories for regeneration form
        $bacSignatories = BacSignatory::with('user')->active()->get()->groupBy('position');
        
        return view('bac.quotations.manage', compact('purchaseRequest', 'suppliers', 'quotations', 'resolution', 'rfq', 'bacSignatories'));
    }

    public function store(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_if(empty($purchaseRequest->procurement_method), 403, 'Procurement method must be set first.');
        
        // Load PR items for validation
        $purchaseRequest->load('items');
        
        // Get RFQ document to check submission deadline
        $rfq = $purchaseRequest->documents()
            ->where('document_type', 'bac_rfq')
            ->latest()
            ->first();
        
        // Validate the request
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'supplier_location' => ['nullable', 'string', 'max:500'],
            'quotation_date' => ['required', 'date'],
            'quotation_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
            'items' => ['required', 'array'],
            'items.*.pr_item_id' => ['required', 'exists:purchase_request_items,id'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'], // Now optional - supplier may not quote all items
        ]);

        // Validate that at least one item has a unit price (supplier must quote at least one item)
        $hasAtLeastOnePrice = false;
        foreach ($validated['items'] as $item) {
            if (isset($item['unit_price']) && $item['unit_price'] !== null && $item['unit_price'] !== '') {
                $hasAtLeastOnePrice = true;
                break;
            }
        }

        if (!$hasAtLeastOnePrice) {
            return back()->withErrors([
                'items' => 'Supplier must provide pricing for at least one item.'
            ])->withInput();
        }

        // Validate quotation date is within 4 days of RFQ creation
        if ($rfq) {
            $rfqDate = $rfq->created_at;
            $deadline = $rfqDate->copy()->addDays(4);
            
            if ($validated['quotation_date'] > $deadline->toDateString()) {
                return back()->withErrors([
                    'quotation_date' => 'Quotation date must be within 4 days of RFQ creation date (' . $rfqDate->format('M d, Y') . '). Deadline was ' . $deadline->format('M d, Y') . '.'
                ])->withInput();
            }
        }

        // Calculate validity date (quotation_date + 10 days)
        $quotationDate = \Carbon\Carbon::parse($validated['quotation_date']);
        $validityDate = $quotationDate->copy()->addDays(10);

        // Check for duplicate supplier quotation
        $existingQuotation = Quotation::where('purchase_request_id', $purchaseRequest->id)
            ->where('supplier_id', $validated['supplier_id'])
            ->first();
        
        if ($existingQuotation) {
            return back()->withErrors([
                'supplier_id' => 'A quotation from this supplier already exists for this PR.'
            ])->withInput();
        }

        try {
            \DB::beginTransaction();

            // Handle file upload if present
            $quotationFilePath = null;
            if ($request->hasFile('quotation_file')) {
                $file = $request->file('quotation_file');
                $filename = 'quotation_' . time() . '_' . $validated['supplier_id'] . '.' . $file->getClientOriginalExtension();
                $quotationFilePath = $file->storeAs('quotations', $filename, 'public');
            }

            // Generate quotation number
            $quotationNumber = self::generateQuotationNumber();

            // Calculate grand total and check ABC compliance
            $grandTotal = 0;
            $exceedsAbc = false;
            $itemsData = [];

            foreach ($validated['items'] as $itemData) {
                $prItem = $purchaseRequest->items->firstWhere('id', $itemData['pr_item_id']);
                
                if (!$prItem) {
                    continue;
                }

                // Check if supplier quoted this item (unit_price is provided and not empty)
                $unitPrice = isset($itemData['unit_price']) && $itemData['unit_price'] !== '' && $itemData['unit_price'] !== null 
                    ? (float) $itemData['unit_price'] 
                    : null;

                // Skip items that weren't quoted by the supplier
                if ($unitPrice === null) {
                    $itemsData[] = [
                        'pr_item_id' => $prItem->id,
                        'unit_price' => null,
                        'total_price' => 0,
                        'is_within_abc' => true, // Not quoted items don't affect ABC compliance
                    ];
                    continue;
                }

                $quantity = $prItem->quantity_requested;
                $totalPrice = $unitPrice * $quantity;
                $abc = (float) $prItem->estimated_unit_cost;
                
                // Check if unit price exceeds ABC (only for quoted items)
                $isWithinAbc = $unitPrice <= $abc;
                
                if (!$isWithinAbc) {
                    $exceedsAbc = true;
                }

                $grandTotal += $totalPrice;

                $itemsData[] = [
                    'pr_item_id' => $prItem->id,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'is_within_abc' => $isWithinAbc,
                ];
            }

            // Create the quotation
            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_id' => $validated['supplier_id'],
                'supplier_location' => $validated['supplier_location'] ?? null,
                'quotation_date' => $validated['quotation_date'],
                'validity_date' => $validityDate->toDateString(),
                'total_amount' => $grandTotal,
                'exceeds_abc' => $exceedsAbc,
                'quotation_file_path' => $quotationFilePath,
                'bac_status' => $exceedsAbc ? 'non_compliant' : 'pending_evaluation',
            ]);

            // Create quotation items
            foreach ($itemsData as $itemData) {
                \App\Models\QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'purchase_request_item_id' => $itemData['pr_item_id'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price'],
                    'is_within_abc' => $itemData['is_within_abc'],
                ]);
            }

            // Automatically identify lowest bidder
            $this->autoIdentifyLowestBidder($purchaseRequest);

            \DB::commit();

            $message = 'Quotation recorded successfully.';
            if ($exceedsAbc) {
                $message .= ' Note: Some items exceed the ABC and this quotation is marked as non-compliant.';
            }

            return back()->with('status', $message);

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Failed to store quotation: ' . $e->getMessage());
            return back()->with('error', 'Failed to save quotation. Please try again.')->withInput();
        }
    }

    /**
     * Automatically identify and mark the lowest bidder
     * Only considers quotations that are eligible (within ABC and submission deadline)
     */
    private function autoIdentifyLowestBidder(PurchaseRequest $purchaseRequest): void
    {
        // Get all quotations for this PR
        $quotations = Quotation::where('purchase_request_id', $purchaseRequest->id)
            ->with('quotationItems')
            ->get();

        // Reset all lowest_bidder statuses first
        Quotation::where('purchase_request_id', $purchaseRequest->id)
            ->where('bac_status', 'lowest_bidder')
            ->update(['bac_status' => 'pending_evaluation']);

        // Filter only eligible quotations (not exceeding ABC and within deadline)
        $eligibleQuotations = $quotations->filter(function ($quotation) {
            return !$quotation->exceeds_abc && $quotation->isWithinSubmissionDeadline();
        });

        if ($eligibleQuotations->isEmpty()) {
            // No eligible quotations yet
            return;
        }

        // Find the quotation with the lowest total amount
        $lowestQuotation = $eligibleQuotations->sortBy('total_amount')->first();

        if ($lowestQuotation) {
            $lowestQuotation->update([
                'bac_status' => 'lowest_bidder',
            ]);

            Log::info('Lowest bidder identified', [
                'pr_number' => $purchaseRequest->pr_number,
                'quotation_id' => $lowestQuotation->id,
                'supplier' => $lowestQuotation->supplier->business_name ?? 'N/A',
                'total_amount' => $lowestQuotation->total_amount,
            ]);
        }
    }

    public function evaluate(Request $request, Quotation $quotation): RedirectResponse
    {
        $validated = $request->validate([
            'technical_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'financial_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bac_status' => ['required', 'in:compliant,non_compliant,lowest_bidder'],
            'bac_remarks' => ['nullable', 'string'],
        ]);

        $total = null;
        if (isset($validated['technical_score']) && isset($validated['financial_score'])) {
            $total = round(($validated['technical_score'] * 0.6) + ($validated['financial_score'] * 0.4), 2);
        }

        $quotation->update([
            'technical_score' => $validated['technical_score'] ?? null,
            'financial_score' => $validated['financial_score'] ?? null,
            'total_score' => $total,
            'bac_status' => $validated['bac_status'],
            'bac_remarks' => $validated['bac_remarks'] ?? null,
            'evaluated_at' => now(),
        ]);

        return back()->with('status', 'Quotation evaluated.');
    }

    public function finalize(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403);

        // Mark winning quotation
        $winnerId = $request->integer('winning_quotation_id');
        if ($winnerId) {
            Quotation::where('purchase_request_id', $purchaseRequest->id)->update(['is_winning_bid' => false]);
            Quotation::where('id', $winnerId)->update(['is_winning_bid' => true, 'bac_status' => 'awarded', 'awarded_at' => now()]);
        }

        // Move PR status forward to BAC approved (abstract ready)
        $purchaseRequest->status = 'bac_approved';
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        return redirect()->route('bac.quotations.index')->with('status', 'Abstract finalized.');
    }

    protected static function generateQuotationNumber(): string
    {
        $year = now()->year;
        $prefix = 'QUO-' . $year . '-';
        $last = Quotation::where('quotation_number', 'like', $prefix . '%')->orderByDesc('quotation_number')->value('quotation_number');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Download BAC Resolution document
     */
    public function downloadResolution(PurchaseRequest $purchaseRequest): StreamedResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        // Get the resolution document
        $resolution = $purchaseRequest->documents()
            ->where('document_type', 'bac_resolution')
            ->latest()
            ->first();

        if (!$resolution) {
            abort(404, 'Resolution document not found.');
        }

        if (!Storage::exists($resolution->file_path)) {
            abort(404, 'Resolution file not found in storage.');
        }

        return Storage::download($resolution->file_path, $resolution->file_name);
    }

    /**
     * Regenerate BAC Resolution document
     */
    public function regenerateResolution(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);
        abort_if(empty($purchaseRequest->procurement_method), 403, 'Procurement method must be set before generating resolution.');

        // Validate signatory data if provided
        $validated = $request->validate([
            'signatories' => ['nullable', 'array'],
            'signatories.bac_chairman' => ['nullable', 'array'],
            'signatories.bac_chairman.input_mode' => ['required_with:signatories.bac_chairman', 'in:select,manual'],
            'signatories.bac_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman' => ['nullable', 'array'],
            'signatories.bac_vice_chairman.input_mode' => ['required_with:signatories.bac_vice_chairman', 'in:select,manual'],
            'signatories.bac_vice_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_vice_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1' => ['nullable', 'array'],
            'signatories.bac_member_1.input_mode' => ['required_with:signatories.bac_member_1', 'in:select,manual'],
            'signatories.bac_member_1.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_1.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2' => ['nullable', 'array'],
            'signatories.bac_member_2.input_mode' => ['required_with:signatories.bac_member_2', 'in:select,manual'],
            'signatories.bac_member_2.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_2.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3' => ['nullable', 'array'],
            'signatories.bac_member_3.input_mode' => ['required_with:signatories.bac_member_3', 'in:select,manual'],
            'signatories.bac_member_3.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_3.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat' => ['nullable', 'array'],
            'signatories.head_bac_secretariat.input_mode' => ['required_with:signatories.head_bac_secretariat', 'in:select,manual'],
            'signatories.head_bac_secretariat.user_id' => ['nullable', 'exists:users,id'],
            'signatories.head_bac_secretariat.name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo' => ['nullable', 'array'],
            'signatories.ceo.input_mode' => ['required_with:signatories.ceo', 'in:select,manual'],
            'signatories.ceo.user_id' => ['nullable', 'exists:users,id'],
            'signatories.ceo.name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo.suffix' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $signatoryData = null;
            
            // If signatories are provided, save them and prepare data
            if (!empty($validated['signatories'])) {
                Log::info('Regenerating resolution with signatories', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'signatory_count' => count($validated['signatories'])
                ]);
                
                $this->saveSignatories($purchaseRequest, $validated['signatories']);
                $signatoryData = $this->prepareSignatoryData($validated['signatories']);
                
                // IMPORTANT: Refresh the relationship so the service loads fresh signatory data
                $purchaseRequest->refresh();
                $purchaseRequest->load('resolutionSignatories');
                
                Log::info('Signatories saved and loaded', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'loaded_count' => $purchaseRequest->resolutionSignatories->count()
                ]);
            } else {
                Log::info('No signatories provided, will use existing or defaults', [
                    'pr_number' => $purchaseRequest->pr_number
                ]);
            }

            $resolutionService = new BacResolutionService();
            $resolutionService->generateResolution($purchaseRequest, $signatoryData);

            return back()->with('status', 'Resolution has been regenerated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to regenerate BAC resolution for PR ' . $purchaseRequest->pr_number . ': ' . $e->getMessage());
            return back()->with('error', 'Failed to regenerate resolution. Please try again: ' . $e->getMessage());
        }
    }

    /**
     * Save signatories to database
     */
    private function saveSignatories(PurchaseRequest $purchaseRequest, array $signatories): void
    {
        // Delete existing signatories
        $purchaseRequest->resolutionSignatories()->delete();

        Log::info('Saving signatories', [
            'pr_number' => $purchaseRequest->pr_number,
            'positions' => array_keys($signatories)
        ]);

        // Save new signatories
        $savedCount = 0;
        foreach ($signatories as $position => $data) {
            Log::debug("Processing signatory", [
                'position' => $position,
                'input_mode' => $data['input_mode'] ?? 'not set',
                'has_user_id' => !empty($data['user_id']),
                'has_selected_name' => !empty($data['selected_name']),
                'has_name' => !empty($data['name'])
            ]);
            
            if ($data['input_mode'] === 'select' && !empty($data['user_id'])) {
                // User selected from registered user accounts
                ResolutionSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => $data['user_id'],
                    'name' => null,
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                Log::debug("Saved user-based signatory for position: {$position}");
            } elseif ($data['input_mode'] === 'select' && !empty($data['selected_name'])) {
                // Pre-configured signatory with manual name (no user account)
                ResolutionSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['selected_name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                Log::debug("Saved pre-configured manual signatory for position: {$position}");
            } elseif ($data['input_mode'] === 'manual' && !empty($data['name'])) {
                // Manually entered name
                ResolutionSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                Log::debug("Saved manual signatory for position: {$position}");
            } else {
                Log::warning("Skipped signatory for position: {$position}", $data);
            }
        }
        
        Log::info("Total signatories saved: {$savedCount}");
    }

    /**
     * Prepare signatory data for resolution service
     */
    private function prepareSignatoryData(array $signatories): array
    {
        $result = [];
        
        foreach ($signatories as $position => $data) {
            if ($data['input_mode'] === 'select' && !empty($data['user_id'])) {
                // Registered user from dropdown
                $user = \App\Models\User::find($data['user_id']);
                $result[$position] = [
                    'name' => $user->name ?? 'N/A',
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            } elseif ($data['input_mode'] === 'select' && !empty($data['selected_name'])) {
                // Pre-configured signatory with manual name
                $result[$position] = [
                    'name' => $data['selected_name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            } elseif ($data['input_mode'] === 'manual' && !empty($data['name'])) {
                // Manually entered name
                $result[$position] = [
                    'name' => $data['name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            }
        }
        
        return $result;
    }

    /**
     * Generate RFQ document
     */
    public function generateRfq(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);
        abort_if(empty($purchaseRequest->resolution_number), 403, 'Resolution must be generated before creating RFQ.');

        try {
            // Generate RFQ number if not already set
            if (empty($purchaseRequest->rfq_number)) {
                $purchaseRequest->rfq_number = PurchaseRequest::generateNextRfqNumber();
                $purchaseRequest->save();
            }

            $rfqService = new BacRfqService();
            $rfqService->generateRfq($purchaseRequest);

            return back()->with('status', 'RFQ has been generated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to generate RFQ for PR ' . $purchaseRequest->pr_number . ': ' . $e->getMessage());
            return back()->with('error', 'Failed to generate RFQ. Please try again: ' . $e->getMessage());
        }
    }

    /**
     * Download RFQ document
     */
    public function downloadRfq(PurchaseRequest $purchaseRequest): StreamedResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        // Get the RFQ document
        $rfq = $purchaseRequest->documents()
            ->where('document_type', 'bac_rfq')
            ->latest()
            ->first();

        if (!$rfq) {
            abort(404, 'RFQ document not found.');
        }

        if (!Storage::exists($rfq->file_path)) {
            abort(404, 'RFQ file not found in storage.');
        }

        return Storage::download($rfq->file_path, $rfq->file_name);
    }

    /**
     * Regenerate RFQ document
     */
    public function regenerateRfq(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);
        abort_if(empty($purchaseRequest->resolution_number), 403, 'Resolution must be generated before creating RFQ.');

        // Validate signatory data if provided
        $validated = $request->validate([
            'signatories' => ['nullable', 'array'],
            'signatories.bac_chairperson' => ['nullable', 'array'],
            'signatories.bac_chairperson.input_mode' => ['required_with:signatories.bac_chairperson', 'in:select,manual'],
            'signatories.bac_chairperson.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_chairperson.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairperson.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairperson.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_chairperson.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.canvassing_officer' => ['nullable', 'array'],
            'signatories.canvassing_officer.input_mode' => ['required_with:signatories.canvassing_officer', 'in:select,manual'],
            'signatories.canvassing_officer.user_id' => ['nullable', 'exists:users,id'],
            'signatories.canvassing_officer.name' => ['nullable', 'string', 'max:255'],
            'signatories.canvassing_officer.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.canvassing_officer.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.canvassing_officer.suffix' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $signatoryData = null;
            
            // If signatories are provided, save them and prepare data
            if (!empty($validated['signatories'])) {
                Log::info('Regenerating RFQ with signatories', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'signatory_count' => count($validated['signatories'])
                ]);
                
                $this->saveRfqSignatories($purchaseRequest, $validated['signatories']);
                $signatoryData = $this->prepareSignatoryData($validated['signatories']);
                
                // IMPORTANT: Refresh the relationship so the service loads fresh signatory data
                $purchaseRequest->refresh();
                $purchaseRequest->load('rfqSignatories');
                
                Log::info('RFQ Signatories saved and loaded', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'loaded_count' => $purchaseRequest->rfqSignatories->count()
                ]);
            } else {
                Log::info('No signatories provided, will use existing or defaults', [
                    'pr_number' => $purchaseRequest->pr_number
                ]);
            }

            $rfqService = new BacRfqService();
            $rfqService->generateRfq($purchaseRequest, $signatoryData);

            return back()->with('status', 'RFQ has been regenerated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to regenerate RFQ for PR ' . $purchaseRequest->pr_number . ': ' . $e->getMessage());
            return back()->with('error', 'Failed to regenerate RFQ. Please try again: ' . $e->getMessage());
        }
    }

    /**
     * Save RFQ signatories to database
     */
    private function saveRfqSignatories(PurchaseRequest $purchaseRequest, array $signatories): void
    {
        // Delete existing signatories
        $purchaseRequest->rfqSignatories()->delete();

        Log::info('Saving RFQ signatories', [
            'pr_number' => $purchaseRequest->pr_number,
            'positions' => array_keys($signatories)
        ]);

        // Save new signatories
        $savedCount = 0;
        foreach ($signatories as $position => $data) {
            Log::debug("Processing RFQ signatory", [
                'position' => $position,
                'input_mode' => $data['input_mode'] ?? 'not set',
                'has_user_id' => !empty($data['user_id']),
                'has_selected_name' => !empty($data['selected_name']),
                'has_name' => !empty($data['name'])
            ]);
            
            if ($data['input_mode'] === 'select' && !empty($data['user_id'])) {
                // User selected from registered user accounts
                RfqSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => $data['user_id'],
                    'name' => null,
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                Log::debug("Saved user-based RFQ signatory for position: {$position}");
            } elseif ($data['input_mode'] === 'select' && !empty($data['selected_name'])) {
                // Pre-configured signatory with manual name (no user account)
                RfqSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['selected_name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                Log::debug("Saved pre-configured manual RFQ signatory for position: {$position}");
            } elseif ($data['input_mode'] === 'manual' && !empty($data['name'])) {
                // Manually entered name
                RfqSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                Log::debug("Saved manual RFQ signatory for position: {$position}");
            } else {
                Log::warning("Skipped RFQ signatory for position: {$position}", $data);
            }
        }
        
        Log::info("Total RFQ signatories saved: {$savedCount}");
    }
}


