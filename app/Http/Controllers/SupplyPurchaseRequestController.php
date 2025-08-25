<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Notifications\PurchaseRequestStatusUpdated;
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
            ->latest();

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        } else {
            $query->whereIn('status', ['submitted', 'supply_office_review']);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('supply.purchase_requests.index', compact('requests', 'statusFilter'));
    }

    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:start_review,send_to_budget,reject,cancel'],
            'notes' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
        ]);

        $originalStatus = $purchaseRequest->status;

        switch ($validated['action']) {
            case 'start_review':
                $purchaseRequest->status = 'supply_office_review';
                $purchaseRequest->current_handler_id = Auth::id();
                break;
            case 'send_to_budget':
                $purchaseRequest->status = 'budget_office_review';
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

        return back()->with('status', 'Status updated successfully.');
    }
}


