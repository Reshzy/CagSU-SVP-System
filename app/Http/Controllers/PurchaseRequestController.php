<?php

namespace App\Http\Controllers;

use App\Models\DepartmentBudget;
use App\Models\Document;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Notifications\PurchaseRequestSubmitted;
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
        $user = Auth::user();

        // Get PPMP items for user's college only
        $ppmpQuery = PpmpItem::active();

        if ($user->department_id) {
            $ppmpQuery->forCollege($user->department_id);
        }

        $ppmpCategories = (clone $ppmpQuery)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $ppmpItems = $ppmpQuery
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        // Get department budget information
        $fiscalYear = date('Y');
        $departmentBudget = null;

        if ($user->department_id) {
            $departmentBudget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);
        }

        return view('purchase_requests.create', [
            'ppmpCategories' => $ppmpCategories,
            'ppmpItems' => $ppmpItems,
            'departmentBudget' => $departmentBudget,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'justification' => ['nullable', 'string'],

            // Multiple items from PPMP or custom
            'items' => ['required', 'array', 'min:1'],
            'items.*.ppmp_item_id' => ['nullable', 'exists:ppmp_items,id'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.detailed_specifications' => ['nullable', 'string'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:50'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
            'items.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],

            // Attachments (optional)
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $user = Auth::user();

        // Calculate total cost
        $totalCost = 0;
        foreach ($validated['items'] as $item) {
            $totalCost += (float) $item['estimated_unit_cost'] * (int) $item['quantity_requested'];
        }

        // Check budget availability
        if ($user->department_id) {
            $fiscalYear = date('Y');
            $budget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);

            if (! $budget->canReserve($totalCost)) {
                return back()
                    ->withInput()
                    ->withErrors(['budget' => 'Insufficient budget. Available: ₱'.number_format($budget->getAvailableBudget(), 2).', Required: ₱'.number_format($totalCost, 2)]);
            }
        }

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
            foreach ($validated['items'] as $itemData) {
                $estimatedTotal = (float) $itemData['estimated_unit_cost'] * (int) $itemData['quantity_requested'];

                // Determine item category
                $itemCategory = null;
                if (! empty($itemData['ppmp_item_id'])) {
                    // Get PPMP item to extract category
                    $ppmpItem = PpmpItem::find($itemData['ppmp_item_id']);
                    if ($ppmpItem) {
                        // Store the PPMP category as-is
                        $itemCategory = $ppmpItem->category;
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

            // Notify Supply Office for review
            try {
                $supplyUsers = \App\Models\User::role('Supply Officer')->get();
                foreach ($supplyUsers as $user) {
                    $user->notify(new PurchaseRequestSubmitted($purchaseRequest));
                }
            } catch (\Throwable $e) {
                // silently ignore if roles not set yet
            }
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

        $user = Auth::user();

        // Get PPMP items for user's college only
        $ppmpQuery = PpmpItem::active();

        if ($user->department_id) {
            $ppmpQuery->forCollege($user->department_id);
        }

        $ppmpCategories = (clone $ppmpQuery)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $ppmpItems = $ppmpQuery
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        // Get department budget information
        $fiscalYear = date('Y');
        $departmentBudget = null;

        if ($user->department_id) {
            $departmentBudget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);
        }

        // Load the original PR with items
        $purchaseRequest->load(['items.ppmpItem', 'returnedBy']);

        return view('purchase_requests.create_replacement', [
            'ppmpCategories' => $ppmpCategories,
            'ppmpItems' => $ppmpItems,
            'departmentBudget' => $departmentBudget,
            'fiscalYear' => $fiscalYear,
            'originalPr' => $purchaseRequest,
        ]);
    }

    public function storeReplacement(Request $request, PurchaseRequest $originalPr): RedirectResponse
    {
        // Ensure the PR is returned and belongs to the current user
        if ($originalPr->status !== 'returned_by_supply' || $originalPr->requester_id !== Auth::id()) {
            abort(403, 'Unauthorized to create replacement for this PR.');
        }

        $validated = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'justification' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ppmp_item_id' => ['nullable', 'exists:ppmp_items,id'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.detailed_specifications' => ['nullable', 'string'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:50'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
            'items.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $user = Auth::user();

        // Calculate total cost
        $totalCost = 0;
        foreach ($validated['items'] as $item) {
            $totalCost += (float) $item['estimated_unit_cost'] * (int) $item['quantity_requested'];
        }

        // Check budget availability
        if ($user->department_id) {
            $fiscalYear = date('Y');
            $budget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);

            if (! $budget->canReserve($totalCost)) {
                return back()
                    ->withInput()
                    ->withErrors(['budget' => 'Insufficient budget. Available: ₱'.number_format($budget->getAvailableBudget(), 2).', Required: ₱'.number_format($totalCost, 2)]);
            }
        }

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
            foreach ($validated['items'] as $itemData) {
                $estimatedTotal = (float) $itemData['estimated_unit_cost'] * (int) $itemData['quantity_requested'];
                $itemCategory = null;

                if (! empty($itemData['ppmp_item_id'])) {
                    $ppmpItem = PpmpItem::find($itemData['ppmp_item_id']);
                    if ($ppmpItem) {
                        $itemCategory = $ppmpItem->category;
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

            // Notify Supply Office
            try {
                $supplyUsers = \App\Models\User::role('Supply Officer')->get();
                foreach ($supplyUsers as $user) {
                    $user->notify(new PurchaseRequestSubmitted($purchaseRequest));
                }
            } catch (\Throwable $e) {
                // silently ignore if roles not set yet
            }
        });

        return redirect()->route('purchase-requests.index')
            ->with('status', 'Replacement PR created successfully and submitted for review.');
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
