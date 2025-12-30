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

        $query = PurchaseRequest::with(['requester', 'department'])
            ->where('is_archived', false)
            ->latest();

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        } else {
            $query->whereIn('status', ['submitted', 'supply_office_review', 'bac_evaluation', 'bac_approved']);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('supply.purchase_requests.index', compact('requests', 'statusFilter'));
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
