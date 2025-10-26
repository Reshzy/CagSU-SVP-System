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

class BudgetEarmarkController extends Controller
{
    public function index(Request $request): View
    {
        $requests = PurchaseRequest::with(['requester', 'department'])
            ->where('status', 'budget_office_review')
            ->latest()
            ->paginate(15);

        return view('budget.purchase_requests.index', compact('requests'));
    }

    public function edit(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'budget_office_review', 403);
        $purchaseRequest->load(['items', 'requester']);
        
        // Get CEO approval details
        $ceoApproval = WorkflowApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('step_name', 'ceo_initial_approval')
            ->first();
        
        return view('budget.purchase_requests.edit', compact('purchaseRequest', 'ceoApproval'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'budget_office_review', 403);

        $validated = $request->validate([
            'approved_budget_total' => ['required', 'numeric', 'min:0'],
            'date_needed' => ['required', 'date'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'budget_code' => ['nullable', 'string', 'max:255'],
            'procurement_type' => ['required', 'in:supplies_materials,equipment,infrastructure,services,consulting_services'],
            'procurement_method' => ['nullable', 'in:small_value_procurement,public_bidding,direct_contracting,negotiated_procurement'],
            'remarks' => ['required', 'string', 'min:1'],
        ]);

        // Additional validation to check remarks is not just whitespace
        if (empty(trim($validated['remarks']))) {
            return back()->withErrors(['remarks' => 'Remarks cannot be empty or contain only spaces.'])->withInput();
        }

        // Record approval log
        WorkflowApproval::updateOrCreate(
            [
                'purchase_request_id' => $purchaseRequest->id,
                'step_name' => 'budget_office_earmarking',
            ],
            [
                'step_order' => 2,
                'approver_id' => Auth::id(),
                'approved_by' => Auth::id(),
                'status' => 'approved',
                'remarks' => $validated['remarks'] ?? null,
                'assigned_at' => now()->subDay(),
                'responded_at' => now(),
                'days_to_respond' => 1,
            ]
        );

        // Update PR with approved budget, procurement details, and forward to BAC evaluation
        $purchaseRequest->estimated_total = (float) $validated['approved_budget_total'];
        $purchaseRequest->date_needed = $validated['date_needed'];
        $purchaseRequest->funding_source = $validated['funding_source'] ?? null;
        $purchaseRequest->budget_code = $validated['budget_code'] ?? null;
        $purchaseRequest->procurement_type = $validated['procurement_type'];
        $purchaseRequest->procurement_method = $validated['procurement_method'] ?? null;

        if (!empty($validated['remarks'])) {
            $purchaseRequest->current_step_notes = $validated['remarks'];
        }
        $purchaseRequest->status = 'bac_evaluation';
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Create pending approval for BAC
        WorkflowRouter::createPendingForRole($purchaseRequest, 'bac_evaluation', 'BAC Secretariat');

        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, 'budget_office_review', 'bac_evaluation'));
        }

        return redirect()->route('budget.purchase-requests.index')->with('status', 'Earmark approved and forwarded to BAC.');
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'budget_office_review', 403);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10'],
            'remarks' => ['nullable', 'string'],
        ]);

        // Record rejection in workflow
        WorkflowApproval::updateOrCreate(
            [
                'purchase_request_id' => $purchaseRequest->id,
                'step_name' => 'budget_office_earmarking',
            ],
            [
                'step_order' => 2,
                'approver_id' => Auth::id(),
                'approved_by' => null,
                'status' => 'rejected',
                'remarks' => $validated['remarks'] ?? null,
                'rejection_reason' => $validated['rejection_reason'],
                'assigned_at' => now()->subDay(),
                'responded_at' => now(),
                'days_to_respond' => 1,
            ]
        );

        $oldStatus = $purchaseRequest->status;
        $purchaseRequest->status = 'rejected';
        $purchaseRequest->rejection_reason = $validated['rejection_reason'];
        $purchaseRequest->rejected_by = Auth::id();
        $purchaseRequest->rejected_at = now();
        if (!empty($validated['remarks'])) {
            $purchaseRequest->current_step_notes = $validated['remarks'];
        }
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Notify requester of rejection
        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, $oldStatus, 'rejected'));
        }

        return redirect()->route('budget.purchase-requests.index')->with('status', 'Purchase request has been rejected.');
    }
}
