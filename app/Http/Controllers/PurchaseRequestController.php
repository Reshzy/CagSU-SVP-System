<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequestRequest;
use App\Http\Requests\StoreReplacementPurchaseRequestRequest;
use App\Models\DepartmentBudget;
use App\Models\Document;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Notifications\PurchaseRequestSubmitted;
use App\Services\PpmpQuarterlyTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = PurchaseRequest::with(['department', 'replacesPr', 'replacedByPr'])
            ->where('requester_id', Auth::id())
            ->where('is_archived', false)
            ->latest()
            ->paginate(10);

        // Get returned PRs separately
        $returnedPrs = PurchaseRequest::with('department')
            ->where('requester_id', Auth::id())
            ->where('status', 'returned_by_supply')
            ->where('is_archived', false)
            ->latest()
            ->get();

        return view('purchase_requests.index', compact('requests', 'returnedPrs'));
    }

    public function create(Request $request): View
    {
        $data = $this->preparePrCreationData();

        return view('purchase_requests.create', $data);
    }

    public function store(StorePurchaseRequestRequest $request): RedirectResponse
    {
        // Check budget availability
        $budgetCheck = $request->checkBudgetAvailability();
        if (! $budgetCheck['can_reserve']) {
            return back()
                ->withInput()
                ->withErrors(['budget' => $budgetCheck['error']]);
        }

        $validated = $request->validated();
        $totalCost = $request->calculateTotalCost();
        $user = Auth::user();

        DB::transaction(function () use ($validated, $request, $totalCost, $user) {
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generateNextPrNumber(),
                'requester_id' => Auth::id(),
                'department_id' => $user->department_id,
                'purpose' => $validated['purpose'],
                'justification' => $validated['justification'] ?? null,
                'date_needed' => null, // Will be filled by Budget Office
                'estimated_total' => $totalCost,
                'funding_source' => null, // Will be filled by Budget Office
                'budget_code' => null, // Will be filled by Budget Office
                'procurement_type' => null, // Will be filled by Budget Office
                'procurement_method' => null, // Will be filled by BAC
                'status' => 'supply_office_review', // New workflow: starts at Supply Office
                'submitted_at' => now(),
                'status_updated_at' => now(),
                'current_handler_id' => null,
                'has_ppmp' => true,
            ]);

            // Create items
            $this->createPurchaseRequestItems($purchaseRequest, $validated['items']);

            // Handle attachments
            $this->handleAttachments($request, $purchaseRequest);

            // Notify Supply Office for review
            $this->notifySupplyOffice($purchaseRequest);
        });

        return redirect()->route('purchase-requests.index')
            ->with('status', 'Purchase Request submitted successfully.');
    }

    public function createReplacement(PurchaseRequest $purchaseRequest): View
    {
        // Ensure the PR is returned and belongs to the current user
        if ($purchaseRequest->status !== 'returned_by_supply' || $purchaseRequest->requester_id !== Auth::id()) {
            abort(403, 'Unauthorized to create replacement for this PR.');
        }

        $data = $this->preparePrCreationData();

        // Load the original PR with items
        $purchaseRequest->load(['items.ppmpItem', 'returnedBy']);

        // Add original PR to the data
        $data['originalPr'] = $purchaseRequest;

        return view('purchase_requests.create_replacement', $data);
    }

    public function storeReplacement(StoreReplacementPurchaseRequestRequest $request, PurchaseRequest $originalPr): RedirectResponse
    {
        // Check budget availability
        $budgetCheck = $request->checkBudgetAvailability();
        if (! $budgetCheck['can_reserve']) {
            return back()
                ->withInput()
                ->withErrors(['budget' => $budgetCheck['error']]);
        }

        $validated = $request->validated();
        $totalCost = $request->calculateTotalCost();
        $user = Auth::user();

        DB::transaction(function () use ($validated, $request, $totalCost, $user, $originalPr) {
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generateNextPrNumber(),
                'requester_id' => Auth::id(),
                'department_id' => $user->department_id,
                'purpose' => $validated['purpose'],
                'justification' => $validated['justification'] ?? null,
                'date_needed' => null,
                'estimated_total' => $totalCost,
                'funding_source' => null,
                'budget_code' => null,
                'procurement_type' => null,
                'procurement_method' => null,
                'status' => 'supply_office_review', // New workflow starts at Supply Office
                'submitted_at' => now(),
                'status_updated_at' => now(),
                'current_handler_id' => null,
                'has_ppmp' => true,
                'replaces_pr_id' => $originalPr->id, // Link to original PR
            ]);

            // Update original PR to link back to replacement
            $originalPr->update([
                'replaced_by_pr_id' => $purchaseRequest->id,
                'is_archived' => true, // Archive original PR automatically
                'archived_at' => now(),
            ]);

            // Create items
            $this->createPurchaseRequestItems($purchaseRequest, $validated['items']);

            // Handle attachments
            $this->handleAttachments($request, $purchaseRequest);

            // Notify Supply Office
            $this->notifySupplyOffice($purchaseRequest);
        });

        return redirect()->route('purchase-requests.index')
            ->with('status', 'Replacement PR created successfully and submitted for review.');
    }

    /**
     * Prepare common data for PR creation views.
     */
    protected function preparePrCreationData(): array
    {
        $user = Auth::user();
        $fiscalYear = date('Y');

        if (! $user->department_id) {
            abort(403, 'You must be assigned to a department to create purchase requests.');
        }

        // Get department's validated PPMP
        $ppmp = Ppmp::forDepartment($user->department_id)
            ->forFiscalYear($fiscalYear)
            ->validated()
            ->with(['items.appItem'])
            ->first();

        if (! $ppmp) {
            abort(403, 'Your department must have a validated PPMP before creating purchase requests.');
        }

        // Group PPMP items by APP item category
        $ppmpItems = $ppmp->items
            ->filter(function ($item) {
                return $item->appItem !== null;
            })
            ->groupBy(function ($item) {
                return $item->appItem->category;
            });

        $ppmpCategories = $ppmpItems->keys()->sort()->values();

        // Get department budget information
        $departmentBudget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);

        // Get current quarter for quarterly tracking
        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();

        return [
            'ppmp' => $ppmp,
            'ppmpCategories' => $ppmpCategories,
            'ppmpItems' => $ppmpItems,
            'departmentBudget' => $departmentBudget,
            'fiscalYear' => $fiscalYear,
            'currentQuarter' => $currentQuarter,
        ];
    }

    /**
     * Create purchase request items from validated data.
     */
    protected function createPurchaseRequestItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        foreach ($items as $itemData) {
            $estimatedTotal = (float) $itemData['estimated_unit_cost'] * (int) $itemData['quantity_requested'];

            // Determine item category from APP item
            $itemCategory = null;
            if (! empty($itemData['ppmp_item_id'])) {
                $ppmpItem = PpmpItem::with('appItem')->find($itemData['ppmp_item_id']);
                if ($ppmpItem && $ppmpItem->appItem) {
                    $itemCategory = $ppmpItem->appItem->category;
                }
            }

            PurchaseRequestItem::create([
                'purchase_request_id' => $purchaseRequest->id,
                'ppmp_item_id' => $itemData['ppmp_item_id'] ?? null,
                'item_code' => $itemData['item_code'] ?? null,
                'item_name' => $itemData['item_name'] ?? null,
                'detailed_specifications' => $itemData['detailed_specifications'] ?? null,
                'unit_of_measure' => $itemData['unit_of_measure'] ?? null,
                'quantity_requested' => $itemData['quantity_requested'],
                'estimated_unit_cost' => $itemData['estimated_unit_cost'],
                'estimated_total_cost' => $estimatedTotal,
                'item_category' => $itemCategory,
            ]);
        }
    }

    /**
     * Handle file attachments for the purchase request.
     */
    protected function handleAttachments($request, PurchaseRequest $purchaseRequest): void
    {
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('documents', 'public');
                Document::create([
                    'document_number' => self::generateNextDocumentNumber(),
                    'documentable_type' => PurchaseRequest::class,
                    'documentable_id' => $purchaseRequest->id,
                    'document_type' => 'purchase_request',
                    'title' => $file->getClientOriginalName(),
                    'description' => 'PR Attachment',
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                    'uploaded_by' => Auth::id(),
                    'is_public' => false,
                    'status' => 'approved',
                ]);
            }
        }
    }

    /**
     * Notify Supply Office about the new purchase request.
     */
    protected function notifySupplyOffice(PurchaseRequest $purchaseRequest): void
    {
        try {
            $supplyUsers = \App\Models\User::role('Supply Officer')->get();
            foreach ($supplyUsers as $user) {
                $user->notify(new PurchaseRequestSubmitted($purchaseRequest));
            }
        } catch (\Throwable $e) {
            // silently ignore if roles not set yet
        }
    }

    protected static function generateNextDocumentNumber(): string
    {
        $year = now()->year;
        $prefix = 'DOC-'.$year.'-';
        $last = Document::where('document_number', 'like', $prefix.'%')
            ->orderByDesc('document_number')
            ->value('document_number');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
