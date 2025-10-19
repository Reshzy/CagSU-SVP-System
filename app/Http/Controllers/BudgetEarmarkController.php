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
        $purchaseRequest->load('items');
        return view('budget.purchase_requests.edit', compact('purchaseRequest'));
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
            'comments' => ['nullable', 'string'],
        ]);

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
                'comments' => $validated['comments'] ?? null,
                'assigned_at' => now()->subDay(),
                'responded_at' => now(),
                'days_to_respond' => 1,
            ]
        );

        // Update PR with approved budget, procurement details, and forward to CEO approval
        $purchaseRequest->estimated_total = (float) $validated['approved_budget_total'];
        $purchaseRequest->date_needed = $validated['date_needed'];
        $purchaseRequest->funding_source = $validated['funding_source'] ?? null;
        $purchaseRequest->budget_code = $validated['budget_code'] ?? null;
        $purchaseRequest->procurement_type = $validated['procurement_type'];
        $purchaseRequest->procurement_method = $validated['procurement_method'] ?? null;

        if (!empty($validated['comments'])) {
            $purchaseRequest->current_step_notes = $validated['comments'];
        }
        $purchaseRequest->status = 'ceo_approval';
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Create pending approval for CEO
        WorkflowRouter::createPendingForRole($purchaseRequest, 'ceo_initial_approval', 'Executive Officer');

        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, 'budget_office_review', 'ceo_approval'));
        }

        return redirect()->route('budget.purchase-requests.index')->with('status', 'Earmark approved and forwarded to CEO.');
    }
}
