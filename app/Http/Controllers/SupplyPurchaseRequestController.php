<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Notifications\PurchaseRequestStatusUpdated;
use App\Services\WorkflowRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
                'po_generation',
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
}
