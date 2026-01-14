<?php

namespace App\Http\Controllers;

use App\Models\BacSignatory;
use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\ResolutionSignatory;
use App\Models\RfqSignatory;
use App\Models\Supplier;
use App\Services\AoqService;
use App\Services\BacResolutionService;
use App\Services\BacRfqService;
use App\Services\SupplierWithdrawalService;
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
            ->with(['documents' => function ($query) {
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

        $purchaseRequest->load([
            'items',
            'documents',
            'resolutionSignatories',
            'rfqSignatories',
            'itemGroups.rfqGeneration',
            'itemGroups.items',
        ]);

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
            ->with(['supplier', 'quotationItems.purchaseRequestItem', 'prItemGroup'])
            ->get();

        // Group quotations by pr_item_group_id for tabbed interface
        $quotationsByGroup = $quotations->groupBy('pr_item_group_id');

        // Get BAC signatories for regeneration form
        $bacSignatories = BacSignatory::with('user')->active()->get()->groupBy('position');

        return view('bac.quotations.manage', compact('purchaseRequest', 'suppliers', 'quotations', 'quotationsByGroup', 'resolution', 'rfq', 'bacSignatories'));
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

        if (! $hasAtLeastOnePrice) {
            return back()->withErrors([
                'items' => 'Supplier must provide pricing for at least one item.',
            ])->withInput();
        }

        // Validate quotation date is within 4 days of RFQ creation
        if ($rfq) {
            $rfqDate = $rfq->created_at;
            $deadline = $rfqDate->copy()->addDays(4);

            if ($validated['quotation_date'] > $deadline->toDateString()) {
                return back()->withErrors([
                    'quotation_date' => 'Quotation date must be within 4 days of RFQ creation date ('.$rfqDate->format('M d, Y').'). Deadline was '.$deadline->format('M d, Y').'.',
                ])->withInput();
            }
        }

        // Calculate validity date (quotation_date + 10 days)
        $quotationDate = \Carbon\Carbon::parse($validated['quotation_date']);
        $validityDate = $quotationDate->copy()->addDays(10);

        // Determine item group if items are grouped (for duplicate check)
        $tempGroupId = null;
        if ($purchaseRequest->itemGroups()->exists()) {
            $firstItemId = $validated['items'][0]['pr_item_id'] ?? null;
            if ($firstItemId) {
                $firstItem = $purchaseRequest->items->firstWhere('id', $firstItemId);
                $tempGroupId = $firstItem->pr_item_group_id ?? null;
            }
        }

        // Check for duplicate supplier quotation (per group if grouped)
        $existingQuery = Quotation::where('purchase_request_id', $purchaseRequest->id)
            ->where('supplier_id', $validated['supplier_id']);

        if ($tempGroupId) {
            $existingQuery->where('pr_item_group_id', $tempGroupId);
        }

        $existingQuotation = $existingQuery->first();

        if ($existingQuotation) {
            $errorMessage = $tempGroupId
                ? 'A quotation from this supplier already exists for this item group.'
                : 'A quotation from this supplier already exists for this PR.';

            return back()->withErrors([
                'supplier_id' => $errorMessage,
            ])->withInput();
        }

        try {
            \DB::beginTransaction();

            // Handle file upload if present
            $quotationFilePath = null;
            if ($request->hasFile('quotation_file')) {
                $file = $request->file('quotation_file');
                $filename = 'quotation_'.time().'_'.$validated['supplier_id'].'.'.$file->getClientOriginalExtension();
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

                if (! $prItem) {
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

                if (! $isWithinAbc) {
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

            // Determine item group if items are grouped
            $prItemGroupId = null;
            if ($purchaseRequest->itemGroups()->exists()) {
                // Get the group from the first item in the quotation
                $firstItemId = $validated['items'][0]['pr_item_id'] ?? null;
                if ($firstItemId) {
                    $firstItem = $purchaseRequest->items->firstWhere('id', $firstItemId);
                    $prItemGroupId = $firstItem->pr_item_group_id ?? null;
                }
            }

            // Create the quotation
            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'purchase_request_id' => $purchaseRequest->id,
                'pr_item_group_id' => $prItemGroupId,
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
            Log::error('Failed to store quotation: '.$e->getMessage());

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
            return ! $quotation->exceeds_abc && $quotation->isWithinSubmissionDeadline();
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
        $prefix = 'QUO-'.$year.'-';
        $last = Quotation::where('quotation_number', 'like', $prefix.'%')->orderByDesc('quotation_number')->value('quotation_number');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
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

        if (! $resolution) {
            abort(404, 'Resolution document not found.');
        }

        if (! Storage::exists($resolution->file_path)) {
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
            if (! empty($validated['signatories'])) {
                Log::info('Regenerating resolution with signatories', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'signatory_count' => count($validated['signatories']),
                ]);

                $this->saveSignatories($purchaseRequest, $validated['signatories']);
                $signatoryData = $this->prepareSignatoryData($validated['signatories']);

                // IMPORTANT: Refresh the relationship so the service loads fresh signatory data
                $purchaseRequest->refresh();
                $purchaseRequest->load('resolutionSignatories');

                Log::info('Signatories saved and loaded', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'loaded_count' => $purchaseRequest->resolutionSignatories->count(),
                ]);
            } else {
                Log::info('No signatories provided, will use existing or defaults', [
                    'pr_number' => $purchaseRequest->pr_number,
                ]);
            }

            $resolutionService = new BacResolutionService;
            $resolutionService->generateResolution($purchaseRequest, $signatoryData);

            return back()->with('status', 'Resolution has been regenerated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to regenerate BAC resolution for PR '.$purchaseRequest->pr_number.': '.$e->getMessage());

            return back()->with('error', 'Failed to regenerate resolution. Please try again: '.$e->getMessage());
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
            'positions' => array_keys($signatories),
        ]);

        // Save new signatories
        $savedCount = 0;
        foreach ($signatories as $position => $data) {
            Log::debug('Processing signatory', [
                'position' => $position,
                'input_mode' => $data['input_mode'] ?? 'not set',
                'has_user_id' => ! empty($data['user_id']),
                'has_selected_name' => ! empty($data['selected_name']),
                'has_name' => ! empty($data['name']),
            ]);

            if ($data['input_mode'] === 'select' && ! empty($data['user_id'])) {
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
            } elseif ($data['input_mode'] === 'select' && ! empty($data['selected_name'])) {
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
            } elseif ($data['input_mode'] === 'manual' && ! empty($data['name'])) {
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
            if ($data['input_mode'] === 'select' && ! empty($data['user_id'])) {
                // Registered user from dropdown
                $user = \App\Models\User::find($data['user_id']);
                $result[$position] = [
                    'name' => $user->name ?? 'N/A',
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            } elseif ($data['input_mode'] === 'select' && ! empty($data['selected_name'])) {
                // Pre-configured signatory with manual name
                $result[$position] = [
                    'name' => $data['selected_name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            } elseif ($data['input_mode'] === 'manual' && ! empty($data['name'])) {
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
            // Validate BAC signatories are configured
            $signatoryLoader = new \App\Services\SignatoryLoaderService;
            $missingPositions = $signatoryLoader->getMissingPositions(['bac_chairperson', 'canvassing_officer']);

            if (! empty($missingPositions)) {
                return back()->with('error', 'Please configure the following BAC signatories first: '.implode(', ', $missingPositions).'. <a href="'.route('bac.signatories.index').'" class="underline">Configure Signatories</a>');
            }

            // Generate RFQ number if not already set
            if (empty($purchaseRequest->rfq_number)) {
                $purchaseRequest->rfq_number = PurchaseRequest::generateNextRfqNumber();
                $purchaseRequest->save();
            }

            $rfqService = new BacRfqService;
            $rfqService->generateRfq($purchaseRequest);

            return back()->with('status', 'RFQ has been generated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to generate RFQ for PR '.$purchaseRequest->pr_number.': '.$e->getMessage());

            return back()->with('error', 'Failed to generate RFQ. Please try again: '.$e->getMessage());
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

        if (! $rfq) {
            abort(404, 'RFQ document not found.');
        }

        if (! Storage::exists($rfq->file_path)) {
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
            if (! empty($validated['signatories'])) {
                Log::info('Regenerating RFQ with signatories', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'signatory_count' => count($validated['signatories']),
                ]);

                $this->saveRfqSignatories($purchaseRequest, $validated['signatories']);
                $signatoryData = $this->prepareSignatoryData($validated['signatories']);

                // IMPORTANT: Refresh the relationship so the service loads fresh signatory data
                $purchaseRequest->refresh();
                $purchaseRequest->load('rfqSignatories');

                Log::info('RFQ Signatories saved and loaded', [
                    'pr_number' => $purchaseRequest->pr_number,
                    'loaded_count' => $purchaseRequest->rfqSignatories->count(),
                ]);
            } else {
                Log::info('No signatories provided, will use existing or defaults', [
                    'pr_number' => $purchaseRequest->pr_number,
                ]);
            }

            $rfqService = new BacRfqService;
            $rfqService->generateRfq($purchaseRequest, $signatoryData);

            return back()->with('status', 'RFQ has been regenerated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to regenerate RFQ for PR '.$purchaseRequest->pr_number.': '.$e->getMessage());

            return back()->with('error', 'Failed to regenerate RFQ. Please try again: '.$e->getMessage());
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
            'positions' => array_keys($signatories),
        ]);

        // Save new signatories
        $savedCount = 0;
        foreach ($signatories as $position => $data) {
            Log::debug('Processing RFQ signatory', [
                'position' => $position,
                'input_mode' => $data['input_mode'] ?? 'not set',
                'has_user_id' => ! empty($data['user_id']),
                'has_selected_name' => ! empty($data['selected_name']),
                'has_name' => ! empty($data['name']),
            ]);

            if ($data['input_mode'] === 'select' && ! empty($data['user_id'])) {
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
            } elseif ($data['input_mode'] === 'select' && ! empty($data['selected_name'])) {
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
            } elseif ($data['input_mode'] === 'manual' && ! empty($data['name'])) {
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

    /**
     * Generate RFQ for a specific item group
     */
    public function generateRfqForGroup(\App\Models\PrItemGroup $itemGroup): RedirectResponse
    {
        $purchaseRequest = $itemGroup->purchaseRequest;
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);
        abort_if(empty($purchaseRequest->resolution_number), 403, 'Resolution must be generated before creating RFQ.');

        try {
            // Validate BAC signatories are configured
            $signatoryLoader = new \App\Services\SignatoryLoaderService;
            $missingPositions = $signatoryLoader->getMissingPositions(['bac_chairperson', 'canvassing_officer']);

            if (! empty($missingPositions)) {
                return back()->with('error', 'Please configure the following BAC signatories first: '.implode(', ', $missingPositions).'. <a href="'.route('bac.signatories.index').'" class="underline">Configure Signatories</a>');
            }

            $rfqService = new BacRfqService;
            $rfqService->generateRfqForGroup($itemGroup);

            return back()->with('status', 'RFQ has been generated successfully for '.$itemGroup->group_name.'.');
        } catch (\Exception $e) {
            Log::error('Failed to generate RFQ for group '.$itemGroup->id.': '.$e->getMessage());

            return back()->with('error', 'Failed to generate RFQ. Please try again: '.$e->getMessage());
        }
    }

    /**
     * Download RFQ document for a specific group
     */
    public function downloadRfqForGroup(\App\Models\PrItemGroup $itemGroup): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $purchaseRequest = $itemGroup->purchaseRequest;
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        $rfqGeneration = $itemGroup->rfqGeneration;

        if (! $rfqGeneration) {
            abort(404, 'RFQ document not found for this group.');
        }

        if (! Storage::exists($rfqGeneration->file_path)) {
            abort(404, 'RFQ file not found in storage.');
        }

        $filename = 'RFQ_'.$rfqGeneration->rfq_number.'_'.$itemGroup->group_code.'.docx';

        return Storage::download($rfqGeneration->file_path, $filename);
    }

    /**
     * Regenerate RFQ for a specific group
     */
    public function regenerateRfqForGroup(Request $request, \App\Models\PrItemGroup $itemGroup): RedirectResponse
    {
        $purchaseRequest = $itemGroup->purchaseRequest;
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

            // If signatories are provided, prepare data
            if (! empty($validated['signatories'])) {
                $signatoryData = $this->prepareSignatoryData($validated['signatories']);
            }

            $rfqService = new BacRfqService;
            $rfqService->generateRfqForGroup($itemGroup, $signatoryData);

            return back()->with('status', 'RFQ has been regenerated successfully for '.$itemGroup->group_name.'.');
        } catch (\Exception $e) {
            Log::error('Failed to regenerate RFQ for group '.$itemGroup->id.': '.$e->getMessage());

            return back()->with('error', 'Failed to regenerate RFQ. Please try again: '.$e->getMessage());
        }
    }

    /**
     * View AOQ data (preview before generation)
     */
    public function viewAoq(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        $aoqService = new AoqService;

        // Calculate winners and ties
        $aoqData = $aoqService->calculateWinnersAndTies($purchaseRequest);

        // Check if can generate
        $validation = $aoqService->canGenerateAoq($purchaseRequest);

        // Get all quotations
        $quotations = $purchaseRequest->quotations()
            ->with(['supplier', 'quotationItems.purchaseRequestItem'])
            ->get();

        // Get existing AOQ generations
        $aoqGenerations = $purchaseRequest->aoqGenerations()
            ->with('generatedBy')
            ->latest()
            ->get();

        // Prepare signatory data sources
        $activeBacSignatories = BacSignatory::with('user')->active()->get();
        $signatoryDefaults = $this->buildAoqSignatoryDefaults($activeBacSignatories);
        $bacSignatoryOptions = $this->groupAoqSignatoryOptions($activeBacSignatories);
        $eligibleSignatoryUsers = $this->getEligibleSignatoryUsers();

        return view('bac.quotations.aoq', compact(
            'purchaseRequest',
            'aoqData',
            'validation',
            'quotations',
            'aoqGenerations',
            'eligibleSignatoryUsers',
            'signatoryDefaults',
            'bacSignatoryOptions'
        ));
    }

    /**
     * Build AOQ signatory default data from BAC signatories table
     */
    protected function buildAoqSignatoryDefaults($bacSignatories): array
    {
        $memberSignatories = $bacSignatories->where('position', 'bac_member')->values();

        $positionsMap = [
            'bac_chairman' => ['position' => 'bac_chairman'],
            'bac_vice_chairman' => ['position' => 'bac_vice_chairman'],
            'bac_member_1' => ['position' => 'bac_member', 'index' => 0],
            'bac_member_2' => ['position' => 'bac_member', 'index' => 1],
            'bac_member_3' => ['position' => 'bac_member', 'index' => 2],
            'head_bac_secretariat' => ['position' => 'head_bac_secretariat'],
            'ceo' => ['position' => 'ceo'],
        ];

        $defaults = [];
        foreach ($positionsMap as $key => $config) {
            if ($config['position'] === 'bac_member') {
                $record = $memberSignatories->get($config['index'] ?? 0);
            } else {
                $record = $bacSignatories->firstWhere('position', $config['position']);
            }

            $defaults[$key] = $this->formatSignatoryDefault($record);
        }

        return $defaults;
    }

    /**
     * Format a BAC signatory into AOQ modal default data
     */
    protected function formatSignatoryDefault(?BacSignatory $signatory): array
    {
        if (! $signatory) {
            return [
                'input_mode' => 'select',
                'user_id' => null,
                'manual_name' => null,
                'display_name' => null,
                'prefix' => null,
                'suffix' => null,
                'bac_signatory_id' => null,
            ];
        }

        return [
            'input_mode' => 'select',
            'user_id' => $signatory->user_id,
            'manual_name' => $signatory->manual_name,
            'display_name' => $signatory->display_name,
            'prefix' => $signatory->prefix,
            'suffix' => $signatory->suffix,
            'bac_signatory_id' => $signatory->id,
        ];
    }

    /**
     * Group BAC signatories for dropdown usage
     */
    protected function groupAoqSignatoryOptions($bacSignatories): array
    {
        return [
            'bac_chairman' => $bacSignatories->where('position', 'bac_chairman')->values(),
            'bac_vice_chairman' => $bacSignatories->where('position', 'bac_vice_chairman')->values(),
            'bac_member' => $bacSignatories->where('position', 'bac_member')->values(),
            'head_bac_secretariat' => $bacSignatories->where('position', 'head_bac_secretariat')->values(),
            'ceo' => $bacSignatories->where('position', 'ceo')->values(),
        ];
    }

    /**
     * Get eligible users for signatory dropdowns
     */
    protected function getEligibleSignatoryUsers()
    {
        $roles = ['BAC Chair', 'BAC Members', 'BAC Secretariat', 'Executive Officer', 'System Admin'];

        return \App\Models\User::with('roles')
            ->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve a tie by manually selecting winner
     */
    public function resolveTie(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        $validated = $request->validate([
            'purchase_request_item_id' => ['required', 'exists:purchase_request_items,id'],
            'winning_quotation_item_id' => ['required', 'exists:quotation_items,id'],
            'justification' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $aoqService = new AoqService;
            $decision = $aoqService->resolveTie(
                $purchaseRequest,
                $validated['purchase_request_item_id'],
                $validated['winning_quotation_item_id'],
                $validated['justification'],
                auth()->user()
            );

            Log::info('Tie resolved', [
                'pr_number' => $purchaseRequest->pr_number,
                'item_id' => $validated['purchase_request_item_id'],
                'winner_id' => $validated['winning_quotation_item_id'],
                'resolved_by' => auth()->user()->name,
            ]);

            return back()->with('status', 'Tie resolved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to resolve tie: '.$e->getMessage());

            return back()->with('error', 'Failed to resolve tie. Please try again.');
        }
    }

    /**
     * Apply BAC override to change winner
     */
    public function applyBacOverride(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        $validated = $request->validate([
            'purchase_request_item_id' => ['required', 'exists:purchase_request_items,id'],
            'winning_quotation_item_id' => ['required', 'exists:quotation_items,id'],
            'justification' => ['required', 'string', 'min:20', 'max:1000'],
        ]);

        try {
            $aoqService = new AoqService;
            $decision = $aoqService->applyBacOverride(
                $purchaseRequest,
                $validated['purchase_request_item_id'],
                $validated['winning_quotation_item_id'],
                $validated['justification'],
                auth()->user()
            );

            Log::info('BAC override applied', [
                'pr_number' => $purchaseRequest->pr_number,
                'item_id' => $validated['purchase_request_item_id'],
                'winner_id' => $validated['winning_quotation_item_id'],
                'overridden_by' => auth()->user()->name,
            ]);

            return back()->with('status', 'BAC override applied successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to apply BAC override: '.$e->getMessage());

            return back()->with('error', 'Failed to apply override. Please try again.');
        }
    }

    /**
     * Generate AOQ document
     */
    public function generateAoq(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        // Check if BAC signatories are configured (allow override during generation)
        $signatoryLoader = new \App\Services\SignatoryLoaderService;
        $missingPositions = $signatoryLoader->getMissingPositions(['bac_chairman', 'bac_vice_chairman', 'bac_member_1', 'bac_member_2', 'bac_member_3']);

        // If signatories are missing and not provided in request, show error
        if (! empty($missingPositions) && ! $request->has('signatories')) {
            return back()->with('error', 'Please configure the following BAC signatories first: '.implode(', ', $missingPositions).'. <a href="'.route('bac.signatories.index').'" class="underline">Configure Signatories</a>');
        }

        // Validate signatory data
        $validated = $request->validate([
            'signatories' => ['nullable', 'array'],
            'signatories.bac_chairman' => ['required', 'array'],
            'signatories.bac_chairman.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman' => ['required', 'array'],
            'signatories.bac_vice_chairman.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_vice_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_vice_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1' => ['required', 'array'],
            'signatories.bac_member_1.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_member_1.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_1.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2' => ['required', 'array'],
            'signatories.bac_member_2.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_member_2.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_2.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3' => ['required', 'array'],
            'signatories.bac_member_3.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_member_3.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_3.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat' => ['required', 'array'],
            'signatories.head_bac_secretariat.input_mode' => ['required', 'in:select,manual'],
            'signatories.head_bac_secretariat.user_id' => ['nullable', 'exists:users,id'],
            'signatories.head_bac_secretariat.name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo' => ['required', 'array'],
            'signatories.ceo.input_mode' => ['required', 'in:select,manual'],
            'signatories.ceo.user_id' => ['nullable', 'exists:users,id'],
            'signatories.ceo.name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo.suffix' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $signatoryData = $this->prepareSignatoryData($validated['signatories']);

            $aoqService = new AoqService;
            $aoqGeneration = $aoqService->generateAoqDocument($purchaseRequest, auth()->user(), $signatoryData);

            // Save signatories to database
            $this->saveAoqSignatories($aoqGeneration, $validated['signatories']);

            Log::info('AOQ generated', [
                'pr_number' => $purchaseRequest->pr_number,
                'aoq_reference' => $aoqGeneration->aoq_reference_number,
                'generated_by' => auth()->user()->name,
            ]);

            return back()->with('status', "Abstract of Quotations generated successfully. Reference: {$aoqGeneration->aoq_reference_number}");
        } catch (\Exception $e) {
            Log::error('Failed to generate AOQ: '.$e->getMessage());

            return back()->with('error', 'Failed to generate AOQ: '.$e->getMessage());
        }
    }

    /**
     * Save AOQ signatories to database
     */
    private function saveAoqSignatories(\App\Models\AoqGeneration $aoqGeneration, array $signatories): void
    {
        foreach ($signatories as $position => $data) {
            if ($data['input_mode'] === 'select' && ! empty($data['user_id'])) {
                \App\Models\AoqSignatory::create([
                    'aoq_generation_id' => $aoqGeneration->id,
                    'position' => $position,
                    'user_id' => $data['user_id'],
                    'name' => null,
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
            } elseif ($data['input_mode'] === 'select' && ! empty($data['selected_name'])) {
                \App\Models\AoqSignatory::create([
                    'aoq_generation_id' => $aoqGeneration->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['selected_name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
            } elseif ($data['input_mode'] === 'manual' && ! empty($data['name'])) {
                \App\Models\AoqSignatory::create([
                    'aoq_generation_id' => $aoqGeneration->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
            }
        }
    }

    /**
     * Download AOQ document
     */
    public function downloadAoq(PurchaseRequest $purchaseRequest, int $aoqGenerationId): StreamedResponse
    {
        $aoqGeneration = $purchaseRequest->aoqGenerations()->findOrFail($aoqGenerationId);

        if (! Storage::exists($aoqGeneration->file_path)) {
            abort(404, 'AOQ file not found in storage.');
        }

        $fileName = "AOQ_{$aoqGeneration->aoq_reference_number}.docx";

        return Storage::download($aoqGeneration->file_path, $fileName);
    }

    /**
     * Generate AOQ for a specific item group
     */
    public function generateAoqForGroup(Request $request, \App\Models\PrItemGroup $itemGroup): RedirectResponse
    {
        $purchaseRequest = $itemGroup->purchaseRequest;
        abort_unless($purchaseRequest->status === 'bac_evaluation' || $purchaseRequest->status === 'bac_approved', 403);

        $validated = $request->validate([
            'signatories' => ['required', 'array'],
            'signatories.bac_head.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_head.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_head.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_head.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_head.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo.user_id' => ['nullable', 'exists:users,id'],
            'signatories.ceo.name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo.suffix' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $signatoryData = $this->prepareSignatoryData($validated['signatories']);

            $aoqService = new AoqService;
            $aoqGeneration = $aoqService->generateAoqDocumentForGroup($itemGroup, auth()->user(), $signatoryData);

            // Save signatories to database
            $this->saveAoqSignatories($aoqGeneration, $validated['signatories']);

            Log::info('AOQ generated for group', [
                'pr_number' => $purchaseRequest->pr_number,
                'group_code' => $itemGroup->group_code,
                'aoq_reference' => $aoqGeneration->aoq_reference_number,
                'generated_by' => auth()->user()->name,
            ]);

            return back()->with('status', "Abstract of Quotations generated successfully for {$itemGroup->group_name}. Reference: {$aoqGeneration->aoq_reference_number}");
        } catch (\Exception $e) {
            Log::error('Failed to generate AOQ for group: '.$e->getMessage());

            return back()->with('error', 'Failed to generate AOQ: '.$e->getMessage());
        }
    }

    /**
     * Download AOQ for a specific item group
     */
    public function downloadAoqForGroup(PrItemGroup $itemGroup, int $aoqGenerationId): StreamedResponse
    {
        $aoqGeneration = $itemGroup->aoqGeneration;

        if (! $aoqGeneration || $aoqGeneration->id != $aoqGenerationId) {
            abort(404, 'AOQ not found for this group.');
        }

        if (! Storage::exists($aoqGeneration->file_path)) {
            abort(404, 'AOQ file not found in storage.');
        }

        $fileName = "AOQ_{$aoqGeneration->aoq_reference_number}_{$itemGroup->group_code}.docx";

        return Storage::download($aoqGeneration->file_path, $fileName);
    }

    /**
     * Process supplier withdrawal for a quotation item
     */
    public function processWithdrawal(Request $request, QuotationItem $quotationItem): RedirectResponse
    {
        $quotation = $quotationItem->quotation;
        $purchaseRequest = $quotation->purchaseRequest;

        abort_unless(
            in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved']),
            403,
            'Withdrawals are only allowed during BAC evaluation or after approval.'
        );

        $validated = $request->validate([
            'withdrawal_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $withdrawalService = new SupplierWithdrawalService(new AoqService);
        $result = $withdrawalService->withdraw($quotationItem, $validated['withdrawal_reason'], auth()->user());

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        Log::info('Supplier withdrawal processed', [
            'pr_number' => $purchaseRequest->pr_number,
            'supplier' => $quotation->supplier->business_name,
            'item' => $quotationItem->purchaseRequestItem->item_name,
            'has_successor' => $result['has_successor'],
            'processed_by' => auth()->user()->name,
        ]);

        return back()->with('status', $result['message']);
    }

    /**
     * Mark a PR item as failed procurement
     */
    public function markItemFailed(Request $request, PurchaseRequestItem $prItem): RedirectResponse
    {
        $purchaseRequest = $prItem->purchaseRequest;

        abort_unless(
            in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved']),
            403,
            'Cannot mark items as failed outside of BAC evaluation or approval stage.'
        );

        $validated = $request->validate([
            'failure_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        // Check if there are any eligible bidders remaining
        $hasEligibleBidders = $prItem->quotationItems()
            ->eligible()
            ->exists();

        if ($hasEligibleBidders) {
            return back()->with('error', 'Cannot mark item as failed - there are still eligible bidders.');
        }

        $prItem->markAsFailed($validated['failure_reason']);

        Log::info('PR item marked as failed procurement', [
            'pr_number' => $purchaseRequest->pr_number,
            'item' => $prItem->item_name,
            'reason' => $validated['failure_reason'],
            'marked_by' => auth()->user()->name,
        ]);

        return back()->with('status', "Item '{$prItem->item_name}' has been marked as failed procurement.");
    }

    /**
     * Create replacement PR for failed items
     */
    public function createReplacementPr(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless(
            in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved']),
            403,
            'Cannot create replacement PR at this stage.'
        );

        $aoqService = new AoqService;
        $failedItems = $aoqService->getFailedItemsNeedingRePr($purchaseRequest);

        if ($failedItems->isEmpty()) {
            return back()->with('error', 'No failed items found that need a replacement PR.');
        }

        try {
            $replacementPr = $aoqService->handleFailedProcurement($failedItems, $purchaseRequest, auth()->user());

            if (! $replacementPr) {
                return back()->with('error', 'Failed to create replacement PR.');
            }

            Log::info('Replacement PR created for failed items', [
                'original_pr' => $purchaseRequest->pr_number,
                'replacement_pr' => $replacementPr->pr_number,
                'items_count' => $failedItems->count(),
                'created_by' => auth()->user()->name,
            ]);

            return back()->with('status', "Replacement PR created successfully: {$replacementPr->pr_number}. It contains {$failedItems->count()} item(s) from failed procurement.");
        } catch (\Exception $e) {
            Log::error('Failed to create replacement PR: '.$e->getMessage());

            return back()->with('error', 'Failed to create replacement PR: '.$e->getMessage());
        }
    }

    /**
     * Generate consolidated AOQ for all groups
     */
    public function generateConsolidatedAoq(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless(
            in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved']),
            403,
            'Cannot generate consolidated AOQ at this stage.'
        );

        // Check if PR has item groups
        if ($purchaseRequest->itemGroups()->count() === 0) {
            return back()->with('error', 'This PR does not have item groups. Use the regular AOQ generation instead.');
        }

        // Validate signatories
        $validated = $request->validate([
            'signatories' => ['required', 'array'],
            'signatories.bac_chairman' => ['required', 'array'],
            'signatories.bac_chairman.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman' => ['required', 'array'],
            'signatories.bac_vice_chairman.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_vice_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_vice_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1' => ['required', 'array'],
            'signatories.bac_member_1.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_member_1.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_1.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2' => ['required', 'array'],
            'signatories.bac_member_2.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_member_2.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_2.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3' => ['required', 'array'],
            'signatories.bac_member_3.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_member_3.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_3.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat' => ['required', 'array'],
            'signatories.head_bac_secretariat.input_mode' => ['required', 'in:select,manual'],
            'signatories.head_bac_secretariat.user_id' => ['nullable', 'exists:users,id'],
            'signatories.head_bac_secretariat.name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo' => ['required', 'array'],
            'signatories.ceo.input_mode' => ['required', 'in:select,manual'],
            'signatories.ceo.user_id' => ['nullable', 'exists:users,id'],
            'signatories.ceo.name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo.suffix' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $signatoryData = $this->prepareSignatoryData($validated['signatories']);

            $aoqService = new AoqService;
            $aoqGeneration = $aoqService->generateConsolidatedAoq($purchaseRequest, auth()->user(), $signatoryData);

            // Save signatories to database
            $this->saveAoqSignatories($aoqGeneration, $validated['signatories']);

            Log::info('Consolidated AOQ generated', [
                'pr_number' => $purchaseRequest->pr_number,
                'aoq_reference' => $aoqGeneration->aoq_reference_number,
                'groups_count' => $purchaseRequest->itemGroups()->count(),
                'generated_by' => auth()->user()->name,
            ]);

            return back()->with('status', "Consolidated Abstract of Quotations generated successfully. Reference: {$aoqGeneration->aoq_reference_number}");
        } catch (\Exception $e) {
            Log::error('Failed to generate consolidated AOQ: '.$e->getMessage());

            return back()->with('error', 'Failed to generate consolidated AOQ: '.$e->getMessage());
        }
    }

    /**
     * Get withdrawal preview (AJAX endpoint)
     */
    public function withdrawalPreview(QuotationItem $quotationItem)
    {
        $withdrawalService = new SupplierWithdrawalService(new AoqService);

        $canWithdraw = $withdrawalService->canWithdraw($quotationItem);
        $nextBidder = $withdrawalService->getNextBidderPreview($quotationItem);
        $wouldCauseFailure = $withdrawalService->wouldCauseFailure($quotationItem);

        return response()->json([
            'can_withdraw' => $canWithdraw['can_withdraw'],
            'reason' => $canWithdraw['reason'] ?? null,
            'would_cause_failure' => $wouldCauseFailure,
            'next_bidder' => $nextBidder,
            'current_item' => [
                'supplier' => $quotationItem->quotation->supplier->business_name,
                'unit_price' => $quotationItem->unit_price,
                'total_price' => $quotationItem->total_price,
                'item_name' => $quotationItem->purchaseRequestItem->item_name,
            ],
        ]);
    }

    /**
     * Get withdrawal history for a PR (AJAX endpoint)
     */
    public function withdrawalHistory(PurchaseRequest $purchaseRequest)
    {
        $withdrawalService = new SupplierWithdrawalService(new AoqService);
        $history = $withdrawalService->getWithdrawalHistory($purchaseRequest);

        return response()->json([
            'history' => $history->map(function ($withdrawal) {
                return [
                    'id' => $withdrawal->id,
                    'item_name' => $withdrawal->purchaseRequestItem->item_name,
                    'supplier' => $withdrawal->supplier->business_name,
                    'reason' => $withdrawal->withdrawal_reason,
                    'withdrawn_at' => $withdrawal->withdrawn_at->format('M d, Y H:i'),
                    'withdrawn_by' => $withdrawal->withdrawnBy->name,
                    'resulted_in_failure' => $withdrawal->resulted_in_failure,
                    'successor' => $withdrawal->successorQuotationItem
                        ? $withdrawal->successorQuotationItem->quotation->supplier->business_name
                        : null,
                ];
            }),
            'summary' => $withdrawalService->getWithdrawalSummary($purchaseRequest),
        ]);
    }
}
