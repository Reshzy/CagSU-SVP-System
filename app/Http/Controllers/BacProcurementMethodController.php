<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\WorkflowApproval;
use App\Notifications\PurchaseRequestStatusUpdated;
use App\Services\BacResolutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BacProcurementMethodController extends Controller
{
    /**
     * Display a listing of PRs needing procurement method determination
     */
    public function index(Request $request): View
    {
        $requests = PurchaseRequest::with(['requester', 'department', 'items'])
            ->where('status', 'bac_evaluation')
            ->whereNull('procurement_method')
            ->latest()
            ->paginate(15);

        return view('bac.procurement_method.index', compact('requests'));
    }

    /**
     * Show the form for setting procurement method
     */
    public function edit(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403);
        
        $purchaseRequest->load(['items', 'requester', 'department']);
        
        // Get CEO and Budget approval details
        $ceoApproval = WorkflowApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('step_name', 'ceo_initial_approval')
            ->first();
        
        $budgetApproval = WorkflowApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('step_name', 'budget_office_earmarking')
            ->first();
        
        return view('bac.procurement_method.edit', compact('purchaseRequest', 'ceoApproval', 'budgetApproval'));
    }

    /**
     * Save the procurement method and generate resolution
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403);
        abort_if(!empty($purchaseRequest->procurement_method), 403, 'Procurement method already set.');

        $validated = $request->validate([
            'procurement_method' => ['required', 'in:small_value_procurement,public_bidding,direct_contracting,negotiated_procurement'],
            'remarks' => ['nullable', 'string'],
        ]);

        // Generate resolution number if not already set
        if (empty($purchaseRequest->resolution_number)) {
            $purchaseRequest->resolution_number = PurchaseRequest::generateNextResolutionNumber();
        }

        // Update PR with procurement method
        $purchaseRequest->procurement_method = $validated['procurement_method'];
        
        if (!empty($validated['remarks'])) {
            $purchaseRequest->current_step_notes = $validated['remarks'];
        }
        
        $purchaseRequest->procurement_method_set_at = now();
        $purchaseRequest->procurement_method_set_by = Auth::id();
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Auto-generate BAC Resolution document
        try {
            $resolutionService = new BacResolutionService();
            $resolutionService->generateResolution($purchaseRequest);
        } catch (\Exception $e) {
            \Log::error('Failed to generate BAC resolution for PR ' . $purchaseRequest->pr_number . ': ' . $e->getMessage());
            // Continue with workflow even if resolution generation fails
            return redirect()->route('bac.quotations.manage', $purchaseRequest)
                ->with('error', 'Procurement method set, but resolution generation failed. Please try regenerating the resolution.');
        }

        // Notify requester
        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated(
                $purchaseRequest, 
                'bac_evaluation', 
                'bac_evaluation'
            ));
        }

        return redirect()->route('bac.quotations.manage', $purchaseRequest)
            ->with('status', 'Procurement method set and resolution generated successfully. You can now collect quotations.');
    }
}

