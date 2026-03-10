<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetEarmarkRequest;
use App\Models\PurchaseRequest;
use App\Models\WorkflowApproval;
use App\Notifications\PurchaseRequestStatusUpdated;
use App\Services\EarmarkExportService;
use App\Services\PurchaseRequestActivityLogger;
use App\Services\WorkflowRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BudgetEarmarkController extends Controller
{
    public function index(Request $request): View
    {
        $pendingRequests = PurchaseRequest::with(['requester', 'department'])
            ->where('status', 'budget_office_review')
            ->latest()
            ->paginate(15, ['*'], 'pending_page');

        $earmarkedRequests = PurchaseRequest::with(['requester', 'department'])
            ->whereNotNull('earmark_id')
            ->whereNotIn('status', ['budget_office_review'])
            ->latest()
            ->paginate(15, ['*'], 'earmarked_page');

        return view('budget.purchase_requests.index', compact('pendingRequests', 'earmarkedRequests'));
    }

    public function edit(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'budget_office_review', 403);
        $purchaseRequest->load(['items', 'requester', 'activities.user', 'department', 'documents']);

        // Get CEO approval details
        $ceoApproval = WorkflowApproval::where('purchase_request_id', $purchaseRequest->id)
            ->where('step_name', 'ceo_initial_approval')
            ->first();

        return view('budget.purchase_requests.edit', compact('purchaseRequest', 'ceoApproval'));
    }

    public function update(BudgetEarmarkRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'budget_office_review', 403);

        $validated = $request->validated();

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

        // Generate earmark ID if not already set
        if (empty($purchaseRequest->earmark_id)) {
            $purchaseRequest->earmark_id = PurchaseRequest::generateNextEarmarkId();
        }

        // Update PR with approved budget, procurement details, earmark fields, and forward to CEO approval
        $purchaseRequest->estimated_total = (float) $validated['approved_budget_total'];
        $purchaseRequest->date_needed = $validated['date_needed'];
        $purchaseRequest->funding_source = $validated['funding_source'] ?? null;
        $purchaseRequest->budget_code = $validated['budget_code'] ?? null;
        $purchaseRequest->procurement_type = $validated['procurement_type'];
        $purchaseRequest->legal_basis = $validated['legal_basis'] ?? null;
        $purchaseRequest->earmark_programs_activities = $validated['earmark_programs_activities'] ?? null;
        $purchaseRequest->earmark_responsibility_center = $validated['earmark_responsibility_center'] ?? null;
        $purchaseRequest->earmark_date_to = $validated['earmark_date_to'] ?? null;

        // Normalize and save Object of Expenditures rows (drop fully empty rows)
        if (isset($validated['earmark_object_expenditures']) && is_array($validated['earmark_object_expenditures'])) {
            $normalized = [];
            foreach ($validated['earmark_object_expenditures'] as $row) {
                $code = isset($row['code']) ? trim((string) $row['code']) : '';
                $description = isset($row['description']) ? trim((string) $row['description']) : '';
                $amount = $row['amount'] ?? null;

                if ($code === '' && $description === '' && ($amount === null || $amount === '')) {
                    continue;
                }

                $normalized[] = [
                    'code' => $code !== '' ? $code : null,
                    'description' => $description !== '' ? $description : null,
                    'amount' => $amount !== '' ? $amount : null,
                ];
            }

            $purchaseRequest->earmark_object_expenditures = $normalized ?: null;
        }

        if (! empty($validated['remarks'])) {
            $purchaseRequest->current_step_notes = $validated['remarks'];
        }
        $purchaseRequest->status = 'ceo_approval';
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Create pending approval for CEO
        WorkflowRouter::createPendingForRole($purchaseRequest, 'ceo_initial_approval', 'Executive Officer');

        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, 'budget_office_review', 'ceo_approval'));
        }

        return redirect()->route('budget.purchase-requests.index')->with('status', 'Earmark approved and forwarded to CEO for approval.');
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
        if (! empty($validated['remarks'])) {
            $purchaseRequest->current_step_notes = $validated['remarks'];
        }
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        // Notify requester of rejection
        if ($purchaseRequest->requester) {
            $purchaseRequest->requester->notify(new PurchaseRequestStatusUpdated($purchaseRequest, $oldStatus, 'rejected'));
        }

        return redirect()->route('budget.purchase-requests.index')->with('status', 'Purchase request has been deferred.');
    }

    /**
     * Export the earmark document as an Excel file.
     * Works at any stage: budget_office_review (reserves earmark_id) or post-approval.
     */
    public function export(PurchaseRequest $purchaseRequest): BinaryFileResponse
    {
        $isPreview = $purchaseRequest->status === 'budget_office_review';
        $hasEarmarkId = ! empty($purchaseRequest->earmark_id);

        abort_unless($isPreview || $hasEarmarkId, 404);

        // Reserve the official earmark ID early if not yet assigned
        if (empty($purchaseRequest->earmark_id)) {
            DB::transaction(function () use ($purchaseRequest) {
                $attempts = 0;
                while ($attempts < 5) {
                    try {
                        $purchaseRequest->earmark_id = PurchaseRequest::generateNextEarmarkId();
                        $purchaseRequest->save();
                        break;
                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        $attempts++;
                        if ($attempts >= 5) {
                            throw $e;
                        }
                    }
                }
            });
        }

        $purchaseRequest->load(['requester', 'items', 'department']);

        $exportService = new EarmarkExportService;
        $tempFile = $exportService->generateExcel($purchaseRequest);

        $filename = 'Earmark-'.$purchaseRequest->earmark_id.'.xlsx';

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Show the earmark amendment form for post-approval editing.
     */
    public function showAmend(PurchaseRequest $purchaseRequest): View
    {
        abort_unless(! empty($purchaseRequest->earmark_id), 404);

        $purchaseRequest->load(['items.lotChildren', 'requester', 'department', 'activities.user']);

        $amendmentHistory = $purchaseRequest->activities()
            ->where('action', 'earmark_amended')
            ->orderByDesc('created_at')
            ->get();

        return view('budget.purchase_requests.amend', compact('purchaseRequest', 'amendmentHistory'));
    }

    /**
     * Save an earmark amendment (post-approval, does not affect workflow or status).
     */
    public function amend(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless(! empty($purchaseRequest->earmark_id), 404);
        abort_if($purchaseRequest->status === 'budget_office_review', 403);

        $validated = $request->validate([
            'funding_source' => ['nullable', 'string', 'max:255'],
            'legal_basis' => ['nullable', 'string', 'max:500'],
            'earmark_programs_activities' => ['nullable', 'string', 'max:1000'],
            'earmark_responsibility_center' => ['nullable', 'string', 'max:255'],
            'earmark_date_to' => ['nullable', 'date'],
            'approved_budget_total' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'earmark_object_expenditures' => ['nullable', 'array'],
            'earmark_object_expenditures.*.code' => ['nullable', 'string', 'max:50'],
            'earmark_object_expenditures.*.description' => ['nullable', 'string', 'max:255'],
            'earmark_object_expenditures.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        /** @var array<string, string> $amendableFields */
        $amendableFields = [
            'funding_source' => 'funding_source',
            'legal_basis' => 'legal_basis',
            'earmark_programs_activities' => 'earmark_programs_activities',
            'earmark_responsibility_center' => 'earmark_responsibility_center',
            'earmark_date_to' => 'earmark_date_to',
            'approved_budget_total' => 'estimated_total',
        ];

        $oldValues = [];
        $newValues = [];

        foreach ($amendableFields as $inputKey => $modelKey) {
            if (! array_key_exists($inputKey, $validated)) {
                continue;
            }

            $incoming = $validated[$inputKey];
            $current = $purchaseRequest->$modelKey;

            // Normalise dates to string for comparison
            if ($current instanceof \Illuminate\Support\Carbon) {
                $current = $current->toDateString();
            }

            if ((string) $current !== (string) $incoming) {
                $oldValues[$inputKey] = $current;
                $newValues[$inputKey] = $incoming;
                $purchaseRequest->$modelKey = $incoming;
            }
        }

        // Object of Expenditures (array diff)
        if (array_key_exists('earmark_object_expenditures', $validated)) {
            $currentArray = $purchaseRequest->earmark_object_expenditures ?? [];
            $incomingArray = $validated['earmark_object_expenditures'] ?? [];

            $normalize = static function ($rows): array {
                if (! is_array($rows)) {
                    return [];
                }

                $normalized = [];

                foreach ($rows as $row) {
                    $code = isset($row['code']) ? trim((string) $row['code']) : '';
                    $description = isset($row['description']) ? trim((string) $row['description']) : '';
                    $amount = $row['amount'] ?? null;

                    if ($code === '' && $description === '' && ($amount === null || $amount === '')) {
                        continue;
                    }

                    $normalized[] = [
                        'code' => $code !== '' ? $code : null,
                        'description' => $description !== '' ? $description : null,
                        'amount' => $amount !== '' ? $amount : null,
                    ];
                }

                return array_values($normalized);
            };

            $normalizedCurrent = $normalize($currentArray);
            $normalizedIncoming = $normalize($incomingArray);

            if (json_encode($normalizedCurrent) !== json_encode($normalizedIncoming)) {
                $oldValues['earmark_object_expenditures'] = $normalizedCurrent;
                $newValues['earmark_object_expenditures'] = $normalizedIncoming;
                $purchaseRequest->earmark_object_expenditures = $normalizedIncoming ?: null;
            }
        }

        // Remarks go to current_step_notes if provided
        if (! empty($validated['remarks'])) {
            $oldValues['remarks'] = $purchaseRequest->current_step_notes;
            $newValues['remarks'] = $validated['remarks'];
            $purchaseRequest->current_step_notes = $validated['remarks'];
        }

        if (empty($oldValues)) {
            return back()->with('status', 'No changes detected.');
        }

        $purchaseRequest->save();

        $logger = new PurchaseRequestActivityLogger;
        $logger->logEarmarkAmended($purchaseRequest, $oldValues, $newValues);

        return back()->with('status', 'Earmark amended successfully. '.count($oldValues).' field(s) updated.');
    }
}
