<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\WorkflowApproval;
use App\Notifications\PurchaseRequestStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\WorkflowRouter;

class CeoApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $requests = PurchaseRequest::with(['requester', 'department'])
            ->where('status', 'ceo_approval')
            ->latest()
            ->paginate(15);

        return view('ceo.purchase_requests.index', compact('requests'));
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'ceo_approval', 403);
        $purchaseRequest->load(['requester', 'department', 'items', 'documents']);
        return view('ceo.purchase_requests.show', compact('purchaseRequest'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'ceo_approval', 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'comments' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
        ]);

        $decision = $validated['decision'];
        $newStatus = $decision === 'approve' ? 'bac_evaluation' : 'rejected';

        WorkflowApproval::updateOrCreate(
            [
                'purchase_request_id' => $purchaseRequest->id,
                'step_name' => 'ceo_initial_approval',
            ],
            [
                'step_order' => 3,
                'approver_id' => Auth::id(),
                'approved_by' => $decision === 'approve' ? Auth::id() : null,
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'comments' => $validated['comments'] ?? null,
                'rejection_reason' => $validated['rejection_reason'] ?? null,
                'assigned_at' => now()->subDay(),
                'responded_at' => now(),
                'days_to_respond' => 1,
            ]
        );

        $oldStatus = $purchaseRequest->status;
        $purchaseRequest->status = $newStatus;
        if ($newStatus === 'rejected') {
            $purchaseRequest->rejection_reason = $validated['rejection_reason'] ?? 'Not specified';
            $purchaseRequest->rejected_by = Auth::id();
            $purchaseRequest->rejected_at = now();
        }
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Create pending approval for BAC
        if ($newStatus === 'bac_evaluation') {
            WorkflowRouter::createPendingForRole($purchaseRequest, 'bac_evaluation', 'BAC Secretariat');
        }

        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, $oldStatus, $newStatus));
        }

        return back()->with('status', 'Decision recorded.');
    }
}


