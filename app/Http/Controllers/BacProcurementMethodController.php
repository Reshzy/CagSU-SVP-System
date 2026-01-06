<?php

namespace App\Http\Controllers;

use App\Models\BacSignatory;
use App\Models\PurchaseRequest;
use App\Models\ResolutionSignatory;
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

        $purchaseRequest->load(['items', 'requester', 'department', 'resolutionSignatories']);

        // Get CEO and Budget approval details
        $ceoApproval = WorkflowApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('step_name', 'ceo_initial_approval')
            ->first();

        $budgetApproval = WorkflowApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('step_name', 'budget_office_earmarking')
            ->first();

        // Get BAC signatories grouped by position
        $bacSignatories = BacSignatory::with('user')->active()->get()->groupBy('position');

        return view('bac.procurement_method.edit', compact('purchaseRequest', 'ceoApproval', 'budgetApproval', 'bacSignatories'));
    }

    /**
     * Save the procurement method and generate resolution
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403);
        abort_if(! empty($purchaseRequest->procurement_method), 403, 'Procurement method already set.');

        // Check if BAC signatories are configured
        $signatoryLoader = new \App\Services\SignatoryLoaderService;
        $requiredPositions = ['bac_chairman', 'bac_vice_chairman', 'bac_member_1', 'bac_member_2', 'bac_member_3', 'head_bac_secretariat', 'ceo'];
        $missingPositions = $signatoryLoader->getMissingPositions($requiredPositions);

        // If signatories are missing and not provided in request, prompt user
        if (! empty($missingPositions) && ! $request->has('signatories')) {
            return back()->with('error', 'Please configure the following BAC signatories first: '.implode(', ', $missingPositions).'. <a href="'.route('bac.signatories.index').'" class="underline">Configure Signatories</a>');
        }

        $validated = $request->validate([
            'procurement_method' => ['required', 'in:small_value_procurement,public_bidding,direct_contracting,negotiated_procurement'],
            'remarks' => ['nullable', 'string'],
            'signatories' => ['nullable', 'array'],
            'signatories.bac_chairman' => ['nullable', 'array'],
            'signatories.bac_chairman.input_mode' => ['required', 'in:select,manual'],
            'signatories.bac_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman' => ['nullable', 'array'],
            'signatories.bac_vice_chairman.input_mode' => ['required_with:signatories.bac_vice_chairman', 'in:select,manual'],
            'signatories.bac_vice_chairman.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_vice_chairman.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_vice_chairman.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_vice_chairman.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1' => ['nullable', 'array'],
            'signatories.bac_member_1.input_mode' => ['required_with:signatories.bac_member_1', 'in:select,manual'],
            'signatories.bac_member_1.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_1.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_1.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_1.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2' => ['nullable', 'array'],
            'signatories.bac_member_2.input_mode' => ['required_with:signatories.bac_member_2', 'in:select,manual'],
            'signatories.bac_member_2.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_2.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_2.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_2.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3' => ['nullable', 'array'],
            'signatories.bac_member_3.input_mode' => ['required_with:signatories.bac_member_3', 'in:select,manual'],
            'signatories.bac_member_3.user_id' => ['nullable', 'exists:users,id'],
            'signatories.bac_member_3.name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.bac_member_3.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.bac_member_3.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat' => ['nullable', 'array'],
            'signatories.head_bac_secretariat.input_mode' => ['required_with:signatories.head_bac_secretariat', 'in:select,manual'],
            'signatories.head_bac_secretariat.user_id' => ['nullable', 'exists:users,id'],
            'signatories.head_bac_secretariat.name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.head_bac_secretariat.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.head_bac_secretariat.suffix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo' => ['nullable', 'array'],
            'signatories.ceo.input_mode' => ['required_with:signatories.ceo', 'in:select,manual'],
            'signatories.ceo.user_id' => ['nullable', 'exists:users,id'],
            'signatories.ceo.name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.selected_name' => ['nullable', 'string', 'max:255'],
            'signatories.ceo.prefix' => ['nullable', 'string', 'max:50'],
            'signatories.ceo.suffix' => ['nullable', 'string', 'max:50'],
        ]);

        // Generate resolution number if not already set
        if (empty($purchaseRequest->resolution_number)) {
            $purchaseRequest->resolution_number = PurchaseRequest::generateNextResolutionNumber();
        }

        // Update PR with procurement method
        $purchaseRequest->procurement_method = $validated['procurement_method'];

        if (! empty($validated['remarks'])) {
            $purchaseRequest->current_step_notes = $validated['remarks'];
        }

        $purchaseRequest->procurement_method_set_at = now();
        $purchaseRequest->procurement_method_set_by = Auth::id();
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Save signatories to database if provided (override)
        $signatoryData = null;
        if (! empty($validated['signatories'])) {
            $this->saveSignatories($purchaseRequest, $validated['signatories']);
            $signatoryData = $this->prepareSignatoryData($validated['signatories']);

            // Refresh the relationship so the service loads fresh signatory data
            $purchaseRequest->refresh();
            $purchaseRequest->load('resolutionSignatories');
        }
        // If signatories not provided, service will auto-load from BAC Signatories setup

        // Auto-generate BAC Resolution document
        try {
            $resolutionService = new BacResolutionService;
            $resolutionService->generateResolution($purchaseRequest, $signatoryData);
        } catch (\Exception $e) {
            \Log::error('Failed to generate BAC resolution for PR '.$purchaseRequest->pr_number.': '.$e->getMessage());

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

    /**
     * Save signatories to database
     */
    private function saveSignatories(PurchaseRequest $purchaseRequest, array $signatories): void
    {
        // Delete existing signatories
        $purchaseRequest->resolutionSignatories()->delete();

        \Log::info('Saving signatories', [
            'pr_number' => $purchaseRequest->pr_number,
            'positions' => array_keys($signatories),
        ]);

        // Save new signatories
        $savedCount = 0;
        foreach ($signatories as $position => $data) {
            \Log::debug('Processing signatory', [
                'position' => $position,
                'input_mode' => $data['input_mode'] ?? 'not set',
                'has_user_id' => ! empty($data['user_id']),
                'has_selected_name' => ! empty($data['selected_name']),
                'has_name' => ! empty($data['name']),
            ]);

            if ($data['input_mode'] === 'select' && ! empty($data['user_id'])) {
                // User selected from registered user accounts
                ResolutionSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => $data['user_id'],
                    'name' => null,
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                \Log::debug("Saved user-based signatory for position: {$position}");
            } elseif ($data['input_mode'] === 'select' && ! empty($data['selected_name'])) {
                // Pre-configured signatory with manual name (no user account)
                ResolutionSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['selected_name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                \Log::debug("Saved pre-configured manual signatory for position: {$position}");
            } elseif ($data['input_mode'] === 'manual' && ! empty($data['name'])) {
                // Manually entered name
                ResolutionSignatory::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'position' => $position,
                    'user_id' => null,
                    'name' => $data['name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
                $savedCount++;
                \Log::debug("Saved manual signatory for position: {$position}");
            } else {
                \Log::warning("Skipped signatory for position: {$position}", $data);
            }
        }

        \Log::info("Total signatories saved: {$savedCount}");
    }

    /**
     * Prepare signatory data for resolution service
     */
    private function prepareSignatoryData(array $signatories): array
    {
        $result = [];

        foreach ($signatories as $position => $data) {
            if ($data['input_mode'] === 'select' && ! empty($data['user_id'])) {
                // Registered user from dropdown
                $user = \App\Models\User::find($data['user_id']);
                $result[$position] = [
                    'name' => $user->name ?? 'N/A',
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            } elseif ($data['input_mode'] === 'select' && ! empty($data['selected_name'])) {
                // Pre-configured signatory with manual name
                $result[$position] = [
                    'name' => $data['selected_name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            } elseif ($data['input_mode'] === 'manual' && ! empty($data['name'])) {
                // Manually entered name
                $result[$position] = [
                    'name' => $data['name'],
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ];
            }
        }

        return $result;
    }
}
