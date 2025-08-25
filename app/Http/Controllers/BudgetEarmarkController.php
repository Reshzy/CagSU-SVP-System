<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\WorkflowApproval;
use App\Notifications\PurchaseRequestStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

        // Update PR and forward to CEO approval
        $purchaseRequest->status = 'ceo_approval';
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, 'budget_office_review', 'ceo_approval'));
        }

        return redirect()->route('budget.purchase-requests.index')->with('status', 'Earmark approved and forwarded to CEO.');
    }
}


