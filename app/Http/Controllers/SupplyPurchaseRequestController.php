<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Notifications\PurchaseRequestStatusUpdated;
use App\Services\PurchaseRequestExportService;
use App\Services\WorkflowRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplyPurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->string('status')->toString();
        $searchTerm = $request->string('search')->toString();
        $departmentFilter = $request->integer('department');
        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortOrder = $request->string('sort_order', 'desc')->toString();

        $query = PurchaseRequest::with(['requester', 'department', 'items'])
            ->withCount('items')
            ->where('is_archived', false);

        // Search by PR number, purpose, or requester name
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('pr_number', 'like', "%{$searchTerm}%")
                    ->orWhere('purpose', 'like', "%{$searchTerm}%")
                    ->orWhereHas('requester', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Filter by status
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        } else {
            // \"All Active\" status set
            $query->whereIn('status', [
                'submitted',
                'supply_office_review',
                'bac_evaluation',
                'bac_approved',
                'partial_po_generation',
                'po_generation',
                'supplier_processing',
                'completed',
            ]);
        }

        // Filter by department
        if ($departmentFilter) {
            $query->where('department_id', $departmentFilter);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('submitted_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('submitted_at', '<=', $dateTo);
        }

        // Sorting
        $allowedSorts = ['created_at', 'submitted_at', 'estimated_total', 'pr_number', 'status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $requests = $query->paginate(15)->withQueryString();

        // Get departments for filter dropdown
        $departments = \App\Models\Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('supply.purchase_requests.index', compact(
            'requests',
            'statusFilter',
            'searchTerm',
            'departmentFilter',
            'dateFrom',
            'dateTo',
            'sortBy',
            'sortOrder',
            'departments'
        ));
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $purchaseRequest->load([
            'requester',
            'department',
            'items.ppmpItem',
            'returnedBy',
            'replacesPr.requester',
            'replacedByPr',
            'activities.user',
            'documents',
            'itemGroups.aoqGeneration',
            'itemGroups.quotations.supplier',
            'itemGroups.purchaseOrders',
            'itemGroups.items',
        ]);

        return view('supply.purchase_requests.show', compact('purchaseRequest'));
    }

    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:start_review,activate,return,reject,cancel'],
            'notes' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
            'return_remarks' => ['nullable', 'string', 'required_if:action,return'],
        ]);

        $originalStatus = $purchaseRequest->status;

        switch ($validated['action']) {
            case 'start_review':
                $purchaseRequest->status = 'supply_office_review';
                $purchaseRequest->current_handler_id = Auth::id();
                break;

            case 'activate':
                // Activate PR - officially starts procurement process
                $purchaseRequest->status = 'budget_office_review';
                $purchaseRequest->current_handler_id = null;
                // Create pending approval for Budget Office
                WorkflowRouter::createPendingForRole($purchaseRequest, 'budget_office_earmarking', 'Budget Office');
                break;

            case 'return':
                // Return PR to dean with remarks
                $purchaseRequest->status = 'returned_by_supply';
                $purchaseRequest->return_remarks = $validated['return_remarks'];
                $purchaseRequest->returned_by = Auth::id();
                $purchaseRequest->returned_at = now();
                $purchaseRequest->current_handler_id = null;
                break;

            case 'reject':
                $purchaseRequest->status = 'rejected';
                $purchaseRequest->rejection_reason = $validated['rejection_reason'] ?? 'Not specified';
                $purchaseRequest->rejected_by = Auth::id();
                $purchaseRequest->rejected_at = now();
                $purchaseRequest->current_handler_id = null;
                break;

            case 'cancel':
                $purchaseRequest->status = 'cancelled';
                $purchaseRequest->current_handler_id = null;
                break;
        }

        $purchaseRequest->current_step_notes = $validated['notes'] ?? null;
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Notify requester of status update
        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, $originalStatus, $purchaseRequest->status));
        }

        $message = match ($validated['action']) {
            'activate' => 'PR activated and forwarded to Budget Office.',
            'return' => 'PR returned to dean with remarks.',
            default => 'Status updated successfully.',
        };

        return back()->with('status', $message);
    }

    /**
     * Create a lot from selected standalone items during Supply Office review.
     */
    public function storeLot(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        abort_unless(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']), 403, 'Lots can only be managed during Supply Office review.');

        $validated = $request->validate([
            'lot_name' => ['required', 'string', 'max:255'],
            'item_ids' => ['required', 'array', 'min:2'],
            'item_ids.*' => ['required', 'exists:purchase_request_items,id'],
        ]);

        $children = PurchaseRequestItem::whereIn('id', $validated['item_ids'])
            ->where('purchase_request_id', $purchaseRequest->id)
            ->where('is_lot', false)
            ->whereNull('parent_lot_id')
            ->get();

        if ($children->count() < 2) {
            return response()->json(['message' => 'At least 2 standalone items are required to create a lot.'], 422);
        }

        DB::transaction(function () use ($validated, $purchaseRequest, $children) {
            $lotTotal = $children->sum(fn ($i) => $i->estimated_total_cost);

            $lotItem = PurchaseRequestItem::create([
                'purchase_request_id' => $purchaseRequest->id,
                'item_name' => $validated['lot_name'],
                'lot_name' => $validated['lot_name'],
                'unit_of_measure' => 'lot',
                'quantity_requested' => 1,
                'estimated_unit_cost' => $lotTotal,
                'estimated_total_cost' => $lotTotal,
                'is_lot' => true,
                'ppmp_quarter' => $children->first()->ppmp_quarter,
                'item_category' => null,
            ]);

            $children->each(fn ($item) => $item->update(['parent_lot_id' => $lotItem->id]));

            // Update PR total
            $purchaseRequest->estimated_total = $purchaseRequest->items()
                ->whereNull('parent_lot_id')
                ->sum('estimated_total_cost');
            $purchaseRequest->save();
        });

        return response()->json(['message' => 'Lot created successfully.']);
    }

    /**
     * Update an existing lot (rename or reassign items).
     */
    public function updateLot(Request $request, PurchaseRequest $purchaseRequest, PurchaseRequestItem $lot): JsonResponse
    {
        abort_unless(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']), 403);
        abort_unless($lot->purchase_request_id === $purchaseRequest->id && $lot->is_lot, 404);

        $validated = $request->validate([
            'lot_name' => ['required', 'string', 'max:255'],
            'item_ids' => ['required', 'array', 'min:2'],
            'item_ids.*' => ['required', 'exists:purchase_request_items,id'],
        ]);

        DB::transaction(function () use ($validated, $purchaseRequest, $lot) {
            // Detach all current children
            PurchaseRequestItem::where('parent_lot_id', $lot->id)
                ->update(['parent_lot_id' => null]);

            // Assign new children (must be standalone items belonging to this PR)
            $newChildren = PurchaseRequestItem::whereIn('id', $validated['item_ids'])
                ->where('purchase_request_id', $purchaseRequest->id)
                ->where('is_lot', false)
                ->whereNull('parent_lot_id')
                ->get();

            $lotTotal = $newChildren->sum(fn ($i) => $i->estimated_total_cost);
            $newChildren->each(fn ($item) => $item->update(['parent_lot_id' => $lot->id]));

            $lot->update([
                'item_name' => $validated['lot_name'],
                'lot_name' => $validated['lot_name'],
                'estimated_unit_cost' => $lotTotal,
                'estimated_total_cost' => $lotTotal,
            ]);

            $purchaseRequest->estimated_total = $purchaseRequest->items()
                ->whereNull('parent_lot_id')
                ->sum('estimated_total_cost');
            $purchaseRequest->save();
        });

        return response()->json(['message' => 'Lot updated successfully.']);
    }

    /**
     * Ungroup a lot: detach children and delete the lot header.
     */
    public function destroyLot(PurchaseRequest $purchaseRequest, PurchaseRequestItem $lot): JsonResponse
    {
        abort_unless(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']), 403);
        abort_unless($lot->purchase_request_id === $purchaseRequest->id && $lot->is_lot, 404);

        DB::transaction(function () use ($purchaseRequest, $lot) {
            PurchaseRequestItem::where('parent_lot_id', $lot->id)
                ->update(['parent_lot_id' => null]);

            $lot->delete();

            $purchaseRequest->estimated_total = $purchaseRequest->items()
                ->whereNull('parent_lot_id')
                ->sum('estimated_total_cost');
            $purchaseRequest->save();
        });

        return response()->json(['message' => 'Lot removed. Items are now standalone.']);
    }

    /**
     * Export PR as Excel file using the official template.
     */
    public function export(PurchaseRequest $purchaseRequest): BinaryFileResponse
    {
        $purchaseRequest->load(['requester', 'items.lotChildren']);

        $exportService = new PurchaseRequestExportService;
        $tempFile = $exportService->generateExcel($purchaseRequest);

        return response()->download($tempFile, "PR-{$purchaseRequest->pr_number}.xlsx")->deleteFileAfterSend(true);
    }
}
