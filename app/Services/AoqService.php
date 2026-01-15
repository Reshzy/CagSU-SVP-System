<?php

namespace App\Services;

use App\Models\AoqGeneration;
use App\Models\AoqItemDecision;
use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\QuotationItem;
use App\Models\SupplierWithdrawal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class AoqService
{
    /**
     * Calculate winners, detect ties, and update quotation_items
     * This is the core logic for determining bid outcomes
     */
    public function calculateWinnersAndTies(PurchaseRequest $purchaseRequest, ?PrItemGroup $itemGroup = null): array
    {
        $results = [];

        // Determine which items to process
        if ($itemGroup) {
            $items = $itemGroup->items;
            $quotations = $itemGroup->quotations()
                ->with(['supplier', 'quotationItems.purchaseRequestItem'])
                ->get();
        } else {
            $items = $purchaseRequest->items;
            $quotations = $purchaseRequest->quotations()
                ->with(['supplier', 'quotationItems.purchaseRequestItem'])
                ->get();
        }

        // Group quotation items by purchase request item
        foreach ($items as $prItem) {
            $itemQuotes = [];
            $allQuotes = []; // Include all quotes for display (including disqualified/withdrawn)

            // Collect all quotes for this item
            foreach ($quotations as $quotation) {
                $quoteItem = $quotation->quotationItems()
                    ->where('purchase_request_item_id', $prItem->id)
                    ->first();

                if ($quoteItem && $quoteItem->isQuoted()) {
                    // Auto-disqualify if exceeds ABC
                    if (! $quoteItem->isWithinAbc() && ! $quoteItem->isDisqualified()) {
                        $quoteItem->disqualification_reason = 'Exceeds Approved Budget for Contract (ABC)';
                        $quoteItem->is_winner = false;
                        $quoteItem->save();
                    }

                    $quoteData = [
                        'quotation_item' => $quoteItem,
                        'quotation' => $quotation,
                        'total_price' => (float) $quoteItem->total_price,
                    ];

                    // Add to all quotes for display
                    $allQuotes[] = $quoteData;

                    // Only include eligible quotes in winner calculation
                    // (not disqualified, not withdrawn)
                    if (! $quoteItem->isDisqualified() && ! $quoteItem->isWithdrawn()) {
                        $itemQuotes[] = $quoteData;
                    }
                }
            }

            if (empty($itemQuotes)) {
                $results[$prItem->id] = [
                    'item' => $prItem,
                    'quotes' => $allQuotes,
                    'lowest_price' => null,
                    'winners' => [],
                    'has_tie' => false,
                    'has_eligible_bidders' => false,
                ];

                continue;
            }

            // Sort by total price ascending
            usort($itemQuotes, fn ($a, $b) => $a['total_price'] <=> $b['total_price']);

            // Assign ranks
            foreach ($itemQuotes as $index => $quote) {
                $quote['quotation_item']->rank = $index + 1;
                $quote['quotation_item']->save();
            }

            // Find lowest price
            $lowestPrice = $itemQuotes[0]['total_price'];

            // Find all quotes with lowest price (potential tie)
            $lowestQuotes = array_filter($itemQuotes, fn ($q) => $q['total_price'] == $lowestPrice);

            // Mark is_lowest and is_tied
            foreach ($itemQuotes as $quote) {
                $isLowest = $quote['total_price'] == $lowestPrice;
                $isTied = $isLowest && count($lowestQuotes) > 1;

                $quote['quotation_item']->is_lowest = $isLowest;
                $quote['quotation_item']->is_tied = $isTied;
                $quote['quotation_item']->save();
            }

            $hasTie = count($lowestQuotes) > 1;

            // Check for existing decision
            $existingDecision = AoqItemDecision::where('purchase_request_item_id', $prItem->id)
                ->where('is_active', true)
                ->first();

            if ($existingDecision) {
                // Check if the existing winner is still eligible
                $existingWinner = QuotationItem::find($existingDecision->winning_quotation_item_id);
                if ($existingWinner && ! $existingWinner->isWithdrawn() && ! $existingWinner->isDisqualified()) {
                    // Use existing decision
                    $winnerId = $existingDecision->winning_quotation_item_id;
                    foreach ($itemQuotes as $quote) {
                        $quote['quotation_item']->is_winner = ($quote['quotation_item']->id == $winnerId);
                        $quote['quotation_item']->save();
                    }
                } else {
                    // Existing winner is no longer eligible, need to reassign
                    $this->reassignWinnerAfterWithdrawal($prItem, $purchaseRequest);
                }
            } else {
                // Auto-assign winner if no tie
                if (! $hasTie) {
                    $lowestQuotes[0]['quotation_item']->is_winner = true;
                    $lowestQuotes[0]['quotation_item']->save();

                    // Create automatic decision record
                    AoqItemDecision::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'purchase_request_item_id' => $prItem->id,
                        'winning_quotation_item_id' => $lowestQuotes[0]['quotation_item']->id,
                        'decision_type' => 'auto',
                        'is_active' => true,
                        'decided_at' => now(),
                    ]);
                }
            }

            $results[$prItem->id] = [
                'item' => $prItem,
                'quotes' => $allQuotes,
                'lowest_price' => $lowestPrice,
                'winners' => array_filter($itemQuotes, fn ($q) => $q['quotation_item']->is_winner),
                'has_tie' => $hasTie,
                'has_eligible_bidders' => ! empty($itemQuotes),
            ];
        }

        return $results;
    }

    /**
     * Reassign winner after a withdrawal
     */
    protected function reassignWinnerAfterWithdrawal(PurchaseRequestItem $prItem, PurchaseRequest $purchaseRequest): ?QuotationItem
    {
        // Get the next eligible bidder
        $eligibleBidders = QuotationItem::where('purchase_request_item_id', $prItem->id)
            ->eligible()
            ->orderBy('rank', 'asc')
            ->get();

        if ($eligibleBidders->isEmpty()) {
            return null;
        }

        $newWinner = $eligibleBidders->first();

        // Deactivate existing decisions
        AoqItemDecision::where('purchase_request_item_id', $prItem->id)
            ->update(['is_active' => false]);

        // Clear all winners for this item
        QuotationItem::where('purchase_request_item_id', $prItem->id)
            ->update(['is_winner' => false]);

        // Mark new winner
        $newWinner->update(['is_winner' => true]);

        // Create new decision
        AoqItemDecision::create([
            'purchase_request_id' => $purchaseRequest->id,
            'purchase_request_item_id' => $prItem->id,
            'winning_quotation_item_id' => $newWinner->id,
            'decision_type' => 'withdrawal_succession',
            'is_active' => true,
            'decided_at' => now(),
        ]);

        return $newWinner;
    }

    /**
     * Process supplier withdrawal for a specific quotation item
     */
    public function processSupplierWithdrawal(
        QuotationItem $quotationItem,
        string $reason,
        User $processedBy
    ): array {
        return DB::transaction(function () use ($quotationItem, $reason, $processedBy) {
            $prItem = $quotationItem->purchaseRequestItem;
            $quotation = $quotationItem->quotation;
            $purchaseRequest = $quotation->purchaseRequest;

            // #region agent log
            @file_put_contents(base_path('.cursor/debug.log'), json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'withdrawal-debug',
                'hypothesisId' => 'H2',
                'location' => 'AoqService.php:processSupplierWithdrawal',
                'message' => 'Processing withdrawal',
                'data' => [
                    'quotation_item_id' => $quotationItem->id,
                    'purchase_request_item_id' => $prItem->id,
                    'purchase_request_id' => $purchaseRequest->id,
                    'successor_id' => $quotationItem->getNextRankedBidder()?->id,
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ]).PHP_EOL, FILE_APPEND);
            // #endregion

            // Withdraw the quotation item
            $quotationItem->withdraw($reason);

            // Find the next ranked bidder
            $successor = $quotationItem->getNextRankedBidder();

            // Create withdrawal record
            $withdrawal = SupplierWithdrawal::create([
                'quotation_item_id' => $quotationItem->id,
                'supplier_id' => $quotation->supplier_id,
                'purchase_request_item_id' => $prItem->id,
                'pr_item_group_id' => $prItem->pr_item_group_id,
                'withdrawal_reason' => $reason,
                'withdrawn_at' => now(),
                'withdrawn_by' => $processedBy->id,
                'successor_quotation_item_id' => $successor?->id,
                'resulted_in_failure' => $successor === null,
            ]);

            if ($successor) {
                // Promote the next winner
                $this->promoteNextWinner($prItem, $successor, $purchaseRequest);

                return [
                    'success' => true,
                    'has_successor' => true,
                    'successor' => $successor,
                    'withdrawal' => $withdrawal,
                    'message' => "Withdrawal processed. {$successor->quotation->supplier->business_name} is now the winner.",
                ];
            } else {
                // No eligible successor - mark item as failed
                $prItem->markAsFailed('All suppliers have withdrawn or been disqualified');

                return [
                    'success' => true,
                    'has_successor' => false,
                    'successor' => null,
                    'withdrawal' => $withdrawal,
                    'message' => 'Withdrawal processed. No eligible bidders remaining - item marked as failed procurement.',
                ];
            }
        });
    }

    /**
     * Promote the next winner after a withdrawal
     */
    protected function promoteNextWinner(
        PurchaseRequestItem $prItem,
        QuotationItem $newWinner,
        PurchaseRequest $purchaseRequest
    ): void {
        // Deactivate existing decisions
        AoqItemDecision::where('purchase_request_item_id', $prItem->id)
            ->update(['is_active' => false]);

        // Clear all winners for this item
        QuotationItem::where('purchase_request_item_id', $prItem->id)
            ->update(['is_winner' => false]);

        // Mark new winner
        $newWinner->update(['is_winner' => true]);

        // Create new decision
        // #region agent log
        @file_put_contents(base_path('.cursor/debug.log'), json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'withdrawal-debug',
            'hypothesisId' => 'H2',
            'location' => 'AoqService.php:promoteNextWinner',
            'message' => 'Creating withdrawal decision',
            'data' => [
                'purchase_request_item_id' => $prItem->id,
                'winning_quotation_item_id' => $newWinner->id,
                'decision_type' => 'withdrawal_succession',
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion

        AoqItemDecision::create([
            'purchase_request_id' => $purchaseRequest->id,
            'purchase_request_item_id' => $prItem->id,
            'winning_quotation_item_id' => $newWinner->id,
            'decision_type' => 'withdrawal_succession',
            'is_active' => true,
            'decided_at' => now(),
        ]);

        // #region agent log
        @file_put_contents(base_path('.cursor/debug.log'), json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'withdrawal-debug',
            'hypothesisId' => 'H2',
            'location' => 'AoqService.php:promoteNextWinner',
            'message' => 'Withdrawal decision created',
            'data' => [
                'purchase_request_item_id' => $prItem->id,
                'winning_quotation_item_id' => $newWinner->id,
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion
    }

    /**
     * Handle failed procurement for items with no eligible bidders
     * Creates a replacement PR automatically
     */
    public function handleFailedProcurement(
        Collection $failedItems,
        PurchaseRequest $originalPr,
        User $processedBy
    ): ?PurchaseRequest {
        if ($failedItems->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($failedItems, $originalPr) {
            // Calculate total cost for replacement PR
            $totalCost = $failedItems->sum(function ($item) {
                return $item->quantity_requested * $item->estimated_unit_cost;
            });

            // Create replacement PR
            $replacementPr = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generateNextPrNumber(),
                'requester_id' => $originalPr->requester_id,
                'department_id' => $originalPr->department_id,
                'purpose' => "Replacement PR for failed items from {$originalPr->pr_number}",
                'justification' => 'Auto-created replacement PR. Original items failed procurement due to all suppliers withdrawing.',
                'date_needed' => null,
                'estimated_total' => $totalCost,
                'funding_source' => $originalPr->funding_source,
                'budget_code' => $originalPr->budget_code,
                'procurement_type' => $originalPr->procurement_type,
                'procurement_method' => null,
                'status' => 'supply_office_review',
                'submitted_at' => now(),
                'status_updated_at' => now(),
                'current_handler_id' => null,
                'has_ppmp' => true,
                'replaces_pr_id' => $originalPr->id,
            ]);

            // Create items for replacement PR
            foreach ($failedItems as $originalItem) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $replacementPr->id,
                    'ppmp_item_id' => $originalItem->ppmp_item_id,
                    'ppmp_quarter' => $originalItem->ppmp_quarter,
                    'ppmp_planned_qty_for_quarter' => $originalItem->ppmp_planned_qty_for_quarter,
                    'ppmp_remaining_qty_at_creation' => $originalItem->ppmp_remaining_qty_at_creation,
                    'item_code' => $originalItem->item_code,
                    'item_name' => $originalItem->getRawOriginal('item_name'),
                    'detailed_specifications' => $originalItem->detailed_specifications,
                    'unit_of_measure' => $originalItem->getRawOriginal('unit_of_measure'),
                    'quantity_requested' => $originalItem->quantity_requested,
                    'estimated_unit_cost' => $originalItem->estimated_unit_cost,
                    'estimated_total_cost' => $originalItem->estimated_total_cost,
                    'item_category' => $originalItem->item_category,
                    'special_requirements' => $originalItem->special_requirements,
                    'needed_by_date' => $originalItem->needed_by_date,
                    'is_available_locally' => $originalItem->is_available_locally,
                    'budget_line_item' => $originalItem->budget_line_item,
                    'approved_budget' => $originalItem->approved_budget,
                    'item_status' => 'pending',
                    'procurement_status' => 'pending',
                ]);

                // Link original item to replacement PR
                $originalItem->linkToReplacementPr($replacementPr);

                // Return quantity to PPMP
                $originalItem->returnQuantityToPpmp();
            }

            return $replacementPr;
        });
    }

    /**
     * Get failed items that need re-PR
     */
    public function getFailedItemsNeedingRePr(PurchaseRequest $purchaseRequest): Collection
    {
        return $purchaseRequest->items()
            ->where('procurement_status', 'failed')
            ->whereNull('replacement_pr_id')
            ->get();
    }

    /**
     * Resolve a tie by selecting a winner
     */
    public function resolveTie(
        PurchaseRequest $purchaseRequest,
        int $purchaseRequestItemId,
        int $winningQuotationItemId,
        string $justification,
        User $decidedBy
    ): AoqItemDecision {
        return DB::transaction(function () use (
            $purchaseRequest,
            $purchaseRequestItemId,
            $winningQuotationItemId,
            $justification,
            $decidedBy
        ) {
            // Deactivate existing decisions
            AoqItemDecision::where('purchase_request_item_id', $purchaseRequestItemId)
                ->update(['is_active' => false]);

            // Create new decision
            $decision = AoqItemDecision::create([
                'purchase_request_id' => $purchaseRequest->id,
                'purchase_request_item_id' => $purchaseRequestItemId,
                'winning_quotation_item_id' => $winningQuotationItemId,
                'decision_type' => 'tie_resolution',
                'justification' => $justification,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
                'is_active' => true,
            ]);

            // Update all quotation items for this PR item
            QuotationItem::where('purchase_request_item_id', $purchaseRequestItemId)
                ->update(['is_winner' => false]);

            // Mark the winner
            QuotationItem::where('id', $winningQuotationItemId)
                ->update(['is_winner' => true]);

            return $decision;
        });
    }

    /**
     * Apply BAC override to change winner
     */
    public function applyBacOverride(
        PurchaseRequest $purchaseRequest,
        int $purchaseRequestItemId,
        int $winningQuotationItemId,
        string $justification,
        User $decidedBy
    ): AoqItemDecision {
        return DB::transaction(function () use (
            $purchaseRequest,
            $purchaseRequestItemId,
            $winningQuotationItemId,
            $justification,
            $decidedBy
        ) {
            // Deactivate existing decisions
            AoqItemDecision::where('purchase_request_item_id', $purchaseRequestItemId)
                ->update(['is_active' => false]);

            // Create new decision
            $decision = AoqItemDecision::create([
                'purchase_request_id' => $purchaseRequest->id,
                'purchase_request_item_id' => $purchaseRequestItemId,
                'winning_quotation_item_id' => $winningQuotationItemId,
                'decision_type' => 'bac_override',
                'justification' => $justification,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
                'is_active' => true,
            ]);

            // Update all quotation items for this PR item
            QuotationItem::where('purchase_request_item_id', $purchaseRequestItemId)
                ->update(['is_winner' => false]);

            // Mark the winner
            QuotationItem::where('id', $winningQuotationItemId)
                ->update(['is_winner' => true]);

            return $decision;
        });
    }

    /**
     * Check if PR is ready for AOQ generation (for non-grouped PRs)
     */
    public function canGenerateAoq(PurchaseRequest $purchaseRequest): array
    {
        $errors = [];

        // Must be in bac_evaluation status
        if ($purchaseRequest->status !== 'bac_evaluation') {
            $errors[] = 'Purchase request must be in BAC evaluation stage.';
        }

        // Must have quotations
        if ($purchaseRequest->quotations()->count() === 0) {
            $errors[] = 'No quotations have been submitted yet.';
        }

        // Must have all items with quotes (excluding failed items)
        foreach ($purchaseRequest->items as $item) {
            if ($item->procurement_status === 'failed') {
                continue; // Skip failed items
            }

            $hasQuote = QuotationItem::where('purchase_request_item_id', $item->id)
                ->whereNotNull('unit_price')
                ->where('is_withdrawn', false)
                ->exists();

            if (! $hasQuote) {
                $errors[] = "Item '{$item->item_name}' has no valid quotations.";
            }
        }

        // Check for unresolved ties
        $unresolvedTies = [];
        foreach ($purchaseRequest->items as $item) {
            if ($item->procurement_status === 'failed') {
                continue;
            }

            $tiedItems = QuotationItem::where('purchase_request_item_id', $item->id)
                ->where('is_tied', true)
                ->where('is_winner', false)
                ->where('is_withdrawn', false)
                ->count();

            if ($tiedItems > 0) {
                $decision = AoqItemDecision::where('purchase_request_item_id', $item->id)
                    ->where('is_active', true)
                    ->exists();

                if (! $decision) {
                    $unresolvedTies[] = $item->item_name;
                }
            }
        }

        if (! empty($unresolvedTies)) {
            $errors[] = 'The following items have unresolved ties: '.implode(', ', $unresolvedTies);
        }

        return [
            'can_generate' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if AOQ can be generated for a specific group
     */
    public function canGenerateAoqForGroup(PrItemGroup $itemGroup): array
    {
        $errors = [];
        $purchaseRequest = $itemGroup->purchaseRequest;

        // Must be in bac_evaluation status
        if ($purchaseRequest->status !== 'bac_evaluation') {
            $errors[] = 'Purchase request must be in BAC evaluation stage.';
        }

        // Check if there are quotations for this group
        $quotationsCount = $itemGroup->quotations()->count();
        if ($quotationsCount === 0) {
            $errors[] = 'No quotations have been submitted for this group yet.';
        }

        // Check each item in the group
        foreach ($itemGroup->items as $item) {
            if ($item->procurement_status === 'failed') {
                continue; // Skip failed items
            }

            $hasQuote = QuotationItem::where('purchase_request_item_id', $item->id)
                ->whereNotNull('unit_price')
                ->where('is_withdrawn', false)
                ->exists();

            if (! $hasQuote) {
                $errors[] = "Item '{$item->item_name}' has no valid quotations.";
            }
        }

        // Check for unresolved ties in this group
        $unresolvedTies = [];
        foreach ($itemGroup->items as $item) {
            if ($item->procurement_status === 'failed') {
                continue;
            }

            $tiedItems = QuotationItem::where('purchase_request_item_id', $item->id)
                ->where('is_tied', true)
                ->where('is_winner', false)
                ->where('is_withdrawn', false)
                ->count();

            if ($tiedItems > 0) {
                $decision = AoqItemDecision::where('purchase_request_item_id', $item->id)
                    ->where('is_active', true)
                    ->exists();

                if (! $decision) {
                    $unresolvedTies[] = $item->item_name;
                }
            }
        }

        if (! empty($unresolvedTies)) {
            $errors[] = 'The following items have unresolved ties: '.implode(', ', $unresolvedTies);
        }

        return [
            'can_generate' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Generate AOQ document (Word format) for non-grouped PRs
     */
    public function generateAoqDocument(PurchaseRequest $purchaseRequest, User $generatedBy, ?array $signatoryData = null): AoqGeneration
    {
        // Recalculate winners and ties
        $aoqData = $this->calculateWinnersAndTies($purchaseRequest);

        // Check if can generate
        $validation = $this->canGenerateAoq($purchaseRequest);
        if (! $validation['can_generate']) {
            throw new \Exception('Cannot generate AOQ: '.implode('; ', $validation['errors']));
        }

        // Generate reference number
        $referenceNumber = AoqGeneration::generateNextReferenceNumber();

        // Prepare data snapshot
        $dataSnapshot = $this->prepareDataSnapshot($purchaseRequest, $aoqData);

        // Generate Word document
        $filePath = $this->createWordDocument($purchaseRequest, $aoqData, $referenceNumber, $signatoryData);

        // Calculate hash
        $documentHash = hash('sha256', json_encode($dataSnapshot));

        // Create AOQ generation record
        $aoqGeneration = AoqGeneration::create([
            'aoq_reference_number' => $referenceNumber,
            'purchase_request_id' => $purchaseRequest->id,
            'generated_by' => $generatedBy->id,
            'document_hash' => $documentHash,
            'exported_data_snapshot' => $dataSnapshot,
            'file_path' => $filePath,
            'file_format' => 'docx',
            'total_items' => count($aoqData),
            'total_suppliers' => $purchaseRequest->quotations()->count(),
        ]);

        return $aoqGeneration;
    }

    /**
     * Generate AOQ document for a specific item group
     */
    public function generateAoqDocumentForGroup(PrItemGroup $itemGroup, User $generatedBy, ?array $signatoryData = null): AoqGeneration
    {
        $purchaseRequest = $itemGroup->purchaseRequest;

        // Recalculate winners and ties for this group only
        $aoqData = $this->calculateWinnersAndTies($purchaseRequest, $itemGroup);

        // Check if can generate for this group
        $validation = $this->canGenerateAoqForGroup($itemGroup);
        if (! $validation['can_generate']) {
            throw new \Exception('Cannot generate AOQ: '.implode('; ', $validation['errors']));
        }

        // Generate reference number
        $referenceNumber = AoqGeneration::generateNextReferenceNumber();

        // Prepare data snapshot for group
        $dataSnapshot = $this->prepareDataSnapshotForGroup($itemGroup, $aoqData);

        // Generate Word document for group
        $filePath = $this->createWordDocumentForGroup($itemGroup, $aoqData, $referenceNumber, $signatoryData);

        // Calculate hash
        $documentHash = hash('sha256', json_encode($dataSnapshot));

        // Create AOQ generation record
        $aoqGeneration = AoqGeneration::create([
            'aoq_reference_number' => $referenceNumber,
            'purchase_request_id' => $purchaseRequest->id,
            'pr_item_group_id' => $itemGroup->id,
            'generated_by' => $generatedBy->id,
            'document_hash' => $documentHash,
            'exported_data_snapshot' => $dataSnapshot,
            'file_path' => $filePath,
            'file_format' => 'docx',
            'total_items' => count($aoqData),
            'total_suppliers' => $itemGroup->quotations()->count(),
        ]);

        return $aoqGeneration;
    }

    /**
     * Generate consolidated AOQ document for all groups in a PR
     */
    public function generateConsolidatedAoq(PurchaseRequest $purchaseRequest, User $generatedBy, ?array $signatoryData = null): AoqGeneration
    {
        // Collect AOQ data from all groups
        $allAoqData = [];
        $totalItems = 0;
        $totalSuppliers = 0;

        if ($purchaseRequest->itemGroups->count() > 0) {
            foreach ($purchaseRequest->itemGroups as $group) {
                $groupData = $this->calculateWinnersAndTies($purchaseRequest, $group);
                foreach ($groupData as $itemId => $data) {
                    $allAoqData[$itemId] = $data;
                    $allAoqData[$itemId]['group'] = $group;
                }
                $totalItems += count($groupData);
                $totalSuppliers = max($totalSuppliers, $group->quotations()->count());
            }
        } else {
            $allAoqData = $this->calculateWinnersAndTies($purchaseRequest);
            $totalItems = count($allAoqData);
            $totalSuppliers = $purchaseRequest->quotations()->count();
        }

        // Generate reference number
        $referenceNumber = AoqGeneration::generateNextReferenceNumber();

        // Prepare consolidated data snapshot
        $dataSnapshot = [
            'pr_number' => $purchaseRequest->pr_number,
            'is_consolidated' => true,
            'groups' => $purchaseRequest->itemGroups->map(fn ($g) => [
                'code' => $g->group_code,
                'name' => $g->group_name,
            ])->toArray(),
            'generated_at' => now()->toISOString(),
            'items' => [],
        ];

        foreach ($allAoqData as $itemId => $data) {
            $item = $data['item'];
            $dataSnapshot['items'][] = [
                'item_id' => $item->id,
                'item_name' => $item->item_name,
                'group_code' => isset($data['group']) ? $data['group']->group_code : null,
                'quantity' => $item->quantity_requested,
                'quotes' => array_map(function ($quote) {
                    return [
                        'supplier' => $quote['quotation']->supplier->business_name,
                        'unit_price' => $quote['quotation_item']->unit_price,
                        'total_price' => $quote['total_price'],
                        'rank' => $quote['quotation_item']->rank,
                        'is_winner' => $quote['quotation_item']->is_winner,
                        'is_withdrawn' => $quote['quotation_item']->is_withdrawn,
                    ];
                }, $data['quotes']),
            ];
        }

        // Generate Word document
        $filePath = $this->createWordDocument($purchaseRequest, $allAoqData, $referenceNumber, $signatoryData, true);

        // Calculate hash
        $documentHash = hash('sha256', json_encode($dataSnapshot));

        // Create AOQ generation record
        $aoqGeneration = AoqGeneration::create([
            'aoq_reference_number' => $referenceNumber,
            'purchase_request_id' => $purchaseRequest->id,
            'pr_item_group_id' => null, // Consolidated = no specific group
            'generated_by' => $generatedBy->id,
            'document_hash' => $documentHash,
            'exported_data_snapshot' => $dataSnapshot,
            'file_path' => $filePath,
            'file_format' => 'docx',
            'total_items' => $totalItems,
            'total_suppliers' => $totalSuppliers,
            'generation_notes' => 'Consolidated AOQ for all item groups',
        ]);

        return $aoqGeneration;
    }

    /**
     * Prepare data snapshot for a specific group
     */
    protected function prepareDataSnapshotForGroup(PrItemGroup $itemGroup, array $aoqData): array
    {
        $purchaseRequest = $itemGroup->purchaseRequest;

        $snapshot = [
            'pr_number' => $purchaseRequest->pr_number,
            'group_name' => $itemGroup->group_name,
            'group_code' => $itemGroup->group_code,
            'generated_at' => now()->toDateTimeString(),
            'items' => [],
        ];

        foreach ($aoqData as $itemId => $data) {
            $item = $data['item'];
            $snapshot['items'][] = [
                'item_id' => $item->id,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity_requested,
                'procurement_status' => $item->procurement_status,
                'quotes' => array_map(function ($quote) {
                    return [
                        'supplier' => $quote['quotation']->supplier->business_name,
                        'unit_price' => $quote['quotation_item']->unit_price,
                        'total_price' => $quote['total_price'],
                        'rank' => $quote['quotation_item']->rank,
                        'is_winner' => $quote['quotation_item']->is_winner,
                        'is_withdrawn' => $quote['quotation_item']->is_withdrawn,
                    ];
                }, $data['quotes']),
            ];
        }

        return $snapshot;
    }

    /**
     * Create Word document for a specific group
     */
    protected function createWordDocumentForGroup(PrItemGroup $itemGroup, array $aoqData, string $referenceNumber, ?array $signatoryData = null): string
    {
        $purchaseRequest = $itemGroup->purchaseRequest;

        // Filter to only include group's quotations
        $groupQuotations = $itemGroup->quotations()->with('supplier')->get();

        $filePath = $this->createWordDocumentWithQuotations(
            $purchaseRequest,
            $aoqData,
            $referenceNumber,
            $signatoryData,
            $groupQuotations,
            $itemGroup->group_name
        );

        // Rename to include group code
        $newPath = str_replace('.docx', '_'.$itemGroup->group_code.'.docx', $filePath);
        Storage::move($filePath, $newPath);

        return $newPath;
    }

    /**
     * Prepare data snapshot for audit
     */
    protected function prepareDataSnapshot(PurchaseRequest $purchaseRequest, array $aoqData): array
    {
        $snapshot = [
            'pr_number' => $purchaseRequest->pr_number,
            'generated_at' => now()->toISOString(),
            'items' => [],
        ];

        foreach ($aoqData as $itemId => $data) {
            $item = $data['item'];
            $snapshot['items'][] = [
                'item_id' => $item->id,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity_requested,
                'procurement_status' => $item->procurement_status ?? 'pending',
                'quotes' => array_map(function ($quote) {
                    return [
                        'supplier' => $quote['quotation']->supplier->business_name,
                        'unit_price' => $quote['quotation_item']->unit_price,
                        'total_price' => $quote['total_price'],
                        'rank' => $quote['quotation_item']->rank,
                        'is_winner' => $quote['quotation_item']->is_winner,
                        'is_withdrawn' => $quote['quotation_item']->is_withdrawn ?? false,
                    ];
                }, $data['quotes']),
            ];
        }

        return $snapshot;
    }

    /**
     * Create Word document using PhpWord - matches existing template format
     */
    protected function createWordDocument(
        PurchaseRequest $purchaseRequest,
        array $aoqData,
        string $referenceNumber,
        ?array $signatoryData = null,
        bool $isConsolidated = false
    ): string {
        $quotations = $purchaseRequest->quotations()->with('supplier')->get();

        return $this->createWordDocumentWithQuotations(
            $purchaseRequest,
            $aoqData,
            $referenceNumber,
            $signatoryData,
            $quotations,
            $isConsolidated ? 'Consolidated' : null
        );
    }

    /**
     * Create Word document with specific quotations
     */
    protected function createWordDocumentWithQuotations(
        PurchaseRequest $purchaseRequest,
        array $aoqData,
        string $referenceNumber,
        ?array $signatoryData,
        $quotations,
        ?string $groupLabel = null
    ): string {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Century Gothic');
        $phpWord->setDefaultFontSize(7);

        $section = $phpWord->addSection([
            'marginLeft' => Converter::inchToTwip(0.25),
            'marginRight' => Converter::inchToTwip(0.25),
            'marginTop' => Converter::inchToTwip(0.25),
            'marginBottom' => Converter::inchToTwip(0.25),
            'orientation' => 'landscape',
            'pageSizeW' => Converter::inchToTwip(13),
            'pageSizeH' => Converter::inchToTwip(8.5),
        ]);

        // Define all styles
        $phpWord->addTableStyle('quotationTable', [
            'borderSize' => 1,
            'borderColor' => '000000',
            'cellMargin' => 5,
        ], ['alignment' => JcTable::CENTER]);

        $phpWord->addTableStyle('metaTable', [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
        ], ['alignment' => JcTable::CENTER]);

        $phpWord->addTableStyle('signatureTable', [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
        ], ['alignment' => JcTable::CENTER]);

        // Text styles
        $header = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic', 'allCaps' => true];
        $supplierHeader = ['bold' => true, 'size' => 8, 'name' => 'Century Gothic', 'allCaps' => true];
        $locationStyle = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic', 'allCaps' => true];
        $priceLabelStyle = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic'];
        $dataText = ['size' => 7, 'name' => 'Century Gothic'];
        $unitDataText = array_merge($dataText, ['allCaps' => true]);
        $articleDataText = array_merge($dataText, ['allCaps' => true]);
        $withdrawnText = array_merge($dataText, ['strikethrough' => true, 'color' => '999999']);
        $signatureNameStyle = ['bold' => true, 'size' => 7, 'name' => 'Century Gothic', 'allCaps' => true];
        $signaturePositionStyle = ['bold' => true, 'size' => 6, 'name' => 'Century Gothic'];
        $metaLabelStyle = ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'];
        $metaValueStyle = ['bold' => true, 'size' => 6, 'name' => 'Century Gothic', 'underline' => 'single'];
        $metaPurposeStyle = ['bold' => true, 'size' => 6, 'name' => 'Century Gothic', 'underline' => 'single', 'allCaps' => true];

        // Paragraph styles
        $noSpacing = ['spaceAfter' => 0, 'spaceBefore' => 0];
        $paragraphCenter = array_merge($noSpacing, ['alignment' => Jc::CENTER]);
        $paragraphLeft = array_merge($noSpacing, ['alignment' => Jc::START]);
        $paragraphRight = array_merge($noSpacing, ['alignment' => Jc::END]);
        $cellMiddle = ['valign' => 'center'];

        $tightHeaderParagraph = array_merge($paragraphCenter, [
            'spacing' => 0,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineSpacingRule' => 'exact',
            'lineSpacing' => 120,
        ]);

        // Header
        $section->addText('Republic of the Philippines', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);
        $section->addText('CAGAYAN STATE UNIVERSITY', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);
        $section->addText('Sanchez Mira, Cagayan', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);

        $aoqTitle = 'ABSTRACT OF QUOTATIONS';
        if ($groupLabel) {
            $aoqTitle .= ' - '.strtoupper($groupLabel);
        }
        $section->addText($aoqTitle, ['bold' => true, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);

        // Meta table
        $metaCellStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF'];
        $metaRowHeight = Converter::inchToTwip(0.15);
        $metaColumnWidths = [
            'purposeLabel' => Converter::inchToTwip(0.5),
            'purposeValue' => Converter::inchToTwip(6.6),
            'leftLabel' => Converter::inchToTwip(0.85),
            'leftValue' => Converter::inchToTwip(1.5),
            'rightLabel' => Converter::inchToTwip(0.85),
            'rightValue' => Converter::inchToTwip(1.5),
        ];

        $metaTable = $section->addTable('metaTable');
        $metaTable->addRow($metaRowHeight, ['exactHeight' => true]);
        $metaTable->addCell($metaColumnWidths['purposeLabel'], ['valign' => 'center', 'vMerge' => 'restart'] + $metaCellStyle)
            ->addText('PURPOSE:', $metaLabelStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['purposeValue'], ['valign' => 'center', 'vMerge' => 'restart'] + $metaCellStyle)
            ->addText($purchaseRequest->purpose ?? 'N/A', $metaPurposeStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['leftLabel'], ['valign' => 'center'] + $metaCellStyle)
            ->addText('AOQ. NO.: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['leftValue'], ['valign' => 'center'] + $metaCellStyle)
            ->addText($referenceNumber, $metaValueStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['rightLabel'], ['valign' => 'center'] + $metaCellStyle)
            ->addText('RFQ.NO.: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['rightValue'], ['valign' => 'center'] + $metaCellStyle)
            ->addText($purchaseRequest->rfq_number ?? 'N/A', $metaValueStyle, $paragraphLeft);

        $metaTable->addRow($metaRowHeight, ['exactHeight' => true]);
        $metaTable->addCell($metaColumnWidths['purposeLabel'], ['valign' => 'top', 'vMerge' => 'continue'] + $metaCellStyle);
        $metaTable->addCell($metaColumnWidths['purposeValue'], ['valign' => 'top', 'vMerge' => 'continue'] + $metaCellStyle);
        $metaTable->addCell($metaColumnWidths['leftLabel'], ['valign' => 'top'] + $metaCellStyle)
            ->addText('P.R. NO.: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['leftValue'], ['valign' => 'top'] + $metaCellStyle)
            ->addText($purchaseRequest->pr_number, $metaValueStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['rightLabel'], ['valign' => 'top'] + $metaCellStyle)
            ->addText('DATE: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['rightValue'], ['valign' => 'top'] + $metaCellStyle)
            ->addText(now()->format('m.d.Y'), $metaValueStyle, $paragraphLeft);

        $section->addTextBreak(0.05);

        // Get all suppliers
        $suppliers = $quotations->pluck('supplier')->unique('id')->values();

        $minimumSuppliers = 4;
        if ($suppliers->count() < $minimumSuppliers) {
            $placeholdersNeeded = $minimumSuppliers - $suppliers->count();
            for ($i = 0; $i < $placeholdersNeeded; $i++) {
                $placeholder = (object) [
                    'id' => null,
                    'business_name' => '',
                    'address' => '',
                ];
                $suppliers->push($placeholder);
            }
        }

        $supplierCount = $suppliers->count();

        // Main table
        $table = $section->addTable('quotationTable');

        $widths = [
            'no' => 250,
            'qty' => 350,
            'unit' => 700,
            'article' => 5200,
            'vendor' => 1500,
        ];

        // Header row 1 - Column headers and supplier names
        $table->addRow();
        foreach (['NO.', 'QTY.', 'UNIT', 'ARTICLES TO BE PURCHASED WORK TO BE DONE'] as $index => $label) {
            $table->addCell(array_values($widths)[$index], ['vMerge' => 'restart'] + $cellMiddle)
                ->addText($label, $header, $paragraphCenter);
        }

        foreach ($suppliers as $supplier) {
            $table->addCell($widths['vendor'] * 2, ['gridSpan' => 2] + $cellMiddle)
                ->addText($supplier->business_name, $supplierHeader, $paragraphCenter);
        }

        // Header row 2 - Supplier locations
        $table->addRow();
        for ($i = 0; $i < 4; $i++) {
            $table->addCell(array_values($widths)[$i], ['vMerge' => 'continue']);
        }

        foreach ($suppliers as $supplier) {
            $table->addCell($widths['vendor'] * 2, ['gridSpan' => 2] + $cellMiddle)
                ->addText($supplier->address ?? '', $locationStyle, $paragraphCenter);
        }

        // Header row 3 - Price labels
        $table->addRow();
        for ($i = 0; $i < 4; $i++) {
            $table->addCell(array_values($widths)[$i], ['vMerge' => 'continue']);
        }

        foreach ($suppliers as $_) {
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText('U.PRICE', $priceLabelStyle, $paragraphCenter);
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText('T.PRICE', $priceLabelStyle, $paragraphCenter);
        }

        // Calculate item winners and totals
        $totalPrices = array_fill(0, $supplierCount, 0.0);
        $totalAmountAwarded = array_fill(0, $supplierCount, 0.0);

        // Data rows
        $rowNum = 1;
        foreach ($aoqData as $itemId => $data) {
            $item = $data['item'];
            $winners = [];

            // Skip failed items in the document
            if ($item->procurement_status === 'failed') {
                continue;
            }

            // Find winners for this item
            foreach ($data['quotes'] as $quote) {
                if ($quote['quotation_item']->is_winner && ! $quote['quotation_item']->is_withdrawn) {
                    $winners[] = $suppliers->search(fn ($s) => $s->id === $quote['quotation']->supplier_id);
                }
            }

            $table->addRow();
            $table->addCell($widths['no'], $cellMiddle)->addText((string) $rowNum, $dataText, $paragraphCenter);
            $table->addCell($widths['qty'], $cellMiddle)->addText((string) $item->quantity_requested, $dataText, $paragraphCenter);
            $table->addCell($widths['unit'], $cellMiddle)->addText($item->unit_of_measure ?? '', $unitDataText, $paragraphCenter);
            $table->addCell($widths['article'], ['valign' => 'top'])->addText($item->item_name, $articleDataText, $paragraphLeft);

            foreach ($suppliers as $supplierIndex => $supplier) {
                $quote = collect($data['quotes'])->first(fn ($q) => $q['quotation']->supplier_id === $supplier->id);

                $isWinner = in_array($supplierIndex, $winners);
                $isWithdrawn = $quote && $quote['quotation_item']->is_withdrawn;

                $cellStyle = $cellMiddle;
                if ($isWinner) {
                    $cellStyle = array_merge($cellMiddle, ['bgColor' => 'FFFF00']);
                } elseif ($isWithdrawn) {
                    $cellStyle = array_merge($cellMiddle, ['bgColor' => 'EEEEEE']);
                }

                if ($quote && $quote['quotation_item']->isQuoted()) {
                    $textStyle = $isWithdrawn ? $withdrawnText : $dataText;
                    $unitPrice = number_format($quote['quotation_item']->unit_price, 2);
                    $totalPrice = number_format($quote['total_price'], 2);

                    if ($isWithdrawn) {
                        $unitPrice .= ' (W)';
                    }

                    $table->addCell($widths['vendor'], $cellStyle)
                        ->addText($unitPrice, $textStyle, $paragraphRight);
                    $table->addCell($widths['vendor'], $cellStyle)
                        ->addText($totalPrice, $textStyle, $paragraphRight);

                    if (! $isWithdrawn) {
                        $totalPrices[$supplierIndex] += $quote['total_price'];
                        if ($isWinner) {
                            $totalAmountAwarded[$supplierIndex] += $quote['total_price'];
                        }
                    }
                } else {
                    $placeholderText = $supplier->id === null ? '' : 'NONE';
                    $table->addCell($widths['vendor'], $cellStyle)
                        ->addText($placeholderText, $dataText, $paragraphLeft);
                    $table->addCell($widths['vendor'], $cellStyle)
                        ->addText('', $dataText, $paragraphRight);
                }
            }

            $rowNum++;
        }

        // Ensure minimum 10 rows
        $minimumVisibleRows = 10;
        for ($r = $rowNum; $r <= $minimumVisibleRows; $r++) {
            $table->addRow();
            $table->addCell($widths['no'], $cellMiddle)->addText(' ', $dataText, $paragraphCenter);
            $table->addCell($widths['qty'], $cellMiddle)->addText(' ', $dataText, $paragraphCenter);
            $table->addCell($widths['unit'], $cellMiddle)->addText(' ', $dataText, $paragraphCenter);
            $table->addCell($widths['article'], ['valign' => 'top'])->addText(' ', $dataText, $paragraphLeft);

            for ($s = 0; $s < $supplierCount; $s++) {
                $table->addCell($widths['vendor'], $cellMiddle)->addText(' ', $dataText, $paragraphRight);
                $table->addCell($widths['vendor'], $cellMiddle)->addText(' ', $dataText, $paragraphRight);
            }
        }

        // Total Price row
        $table->addRow();
        $baseColumns = [$widths['no'], $widths['qty'], $widths['unit'], $widths['article']];
        $table->addCell(array_sum($baseColumns), ['gridSpan' => 4] + $cellMiddle)
            ->addText('TOTAL PRICE', ['bold' => true, 'name' => 'Century Gothic'], $paragraphRight);

        $totalPriceStyle = ['name' => 'Century Gothic', 'underline' => 'single'];
        foreach ($suppliers as $supplierIndex => $supplier) {
            $table->addCell($widths['vendor'], $cellMiddle)->addText('', null, $paragraphRight);
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText($totalPrices[$supplierIndex] > 0 ? number_format($totalPrices[$supplierIndex], 2) : '', $totalPriceStyle, $paragraphRight);
        }

        // Total Amount Awarded row
        $table->addRow();
        $table->addCell(array_sum($baseColumns), ['gridSpan' => 4] + $cellMiddle)
            ->addText('TOTAL AMOUNT AWARDED', ['bold' => true, 'color' => 'FF0000', 'name' => 'Century Gothic'], $paragraphRight);

        $totalAmountAwardedStyle = ['name' => 'Century Gothic', 'underline' => 'single', 'bold' => true];
        foreach ($suppliers as $supplierIndex => $supplier) {
            $table->addCell($widths['vendor'], $cellMiddle)->addText('', null, $paragraphRight);

            $awardedCellStyle = $totalAmountAwarded[$supplierIndex] > 0 ?
                array_merge($cellMiddle, ['bgColor' => 'FFFF00']) : $cellMiddle;

            $table->addCell($widths['vendor'], $awardedCellStyle)
                ->addText($totalAmountAwarded[$supplierIndex] > 0 ? number_format($totalAmountAwarded[$supplierIndex], 2) : '', $totalAmountAwardedStyle, $paragraphRight);
        }

        $section->addText(' ', ['size' => 2], $noSpacing);

        // Certification text
        $certificationParagraphs = [
            'WE HEREBY CERTIFY that we, the members of the Bids and Awards Committee have opened, evaluated and ranked the above mentioned bid proposals under the alternative mode of procurement, negotiated procurement (small value) under Sec. 53.9 of the Revised IRR of R.A. 9184.',
            'After careful deliberation, the committee has decided to recommend the procurement items to the lowest bidder whose price offered is considered reasonable and advantageous to the best interest.',
        ];

        $certificationTextStyle = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic'];
        $certificationParagraphStyle = array_merge($paragraphLeft, [
            'indentation' => ['hanging' => Converter::inchToTwip(-0.25)],
        ]);

        foreach ($certificationParagraphs as $text) {
            $section->addText($text, $certificationTextStyle, $certificationParagraphStyle);
        }

        $section->addTextBreak();

        $formatSignatoryName = function (?array $data, string $fallback = 'N/A') {
            if (! $data) {
                return $fallback;
            }

            $name = trim($data['name'] ?? '');
            if ($name === '') {
                $name = $fallback;
            }

            if (! empty($data['prefix'])) {
                $name = trim($data['prefix']).' '.ltrim($name);
            }

            if (! empty($data['suffix'])) {
                $name = trim($name).', '.trim($data['suffix']);
            }

            return trim($name);
        };

        // Build signatory list
        $signatories = [];
        if ($signatoryData) {
            $positions = ['bac_chairman', 'bac_vice_chairman', 'bac_member_1', 'bac_member_2', 'bac_member_3'];
            foreach ($positions as $position) {
                if (isset($signatoryData[$position])) {
                    $signatories[] = [
                        'name' => $formatSignatoryName($signatoryData[$position]),
                        'position' => match ($position) {
                            'bac_chairman' => 'BAC Chairman',
                            'bac_vice_chairman' => 'BAC Vice Chairman',
                            default => 'BAC Member',
                        },
                    ];
                }
            }
        } else {
            $signatoryLoader = new SignatoryLoaderService;
            $requiredPositions = ['bac_chairman', 'bac_vice_chairman', 'bac_member_1', 'bac_member_2', 'bac_member_3'];
            $bacSignatoriesData = $signatoryLoader->loadActiveSignatories($requiredPositions, false);

            if (! empty($bacSignatoriesData)) {
                foreach ($requiredPositions as $position) {
                    if (isset($bacSignatoriesData[$position])) {
                        $signatories[] = [
                            'name' => $formatSignatoryName($bacSignatoriesData[$position]),
                            'position' => match ($position) {
                                'bac_chairman' => 'BAC Chairman',
                                'bac_vice_chairman' => 'BAC Vice Chairman',
                                default => 'BAC Member',
                            },
                        ];
                    }
                }
            } else {
                $bacSignatories = \App\Models\BacSignatory::with('user')->active()->get();
                foreach ($bacSignatories->take(5) as $signatory) {
                    $signatories[] = [
                        'name' => $signatory->full_name,
                        'position' => $signatory->position_name,
                    ];
                }
            }
        }

        // Signature table
        $signatureTable = $section->addTable('signatureTable');
        $signatureColumnWidth = Converter::inchToTwip(2.5);

        $signatureTable->addRow();
        foreach ($signatories as $signatory) {
            $cell = $signatureTable->addCell($signatureColumnWidth, ['valign' => 'top']);
            $cell->addText(' ', $signatureNameStyle, $paragraphCenter);
            $cell->addTextBreak(0.3);
            $cell->addText($signatory['name'], $signatureNameStyle, $paragraphCenter);
            $cell->addText($signatory['position'], $signaturePositionStyle, $paragraphCenter);
        }

        $section->addText(' ', ['size' => 10], $noSpacing);
        $section->addText(
            'I hereby certify that the foregoing is a true and correct copy of the Abstract of Quotation regularly presented to and adopted by the bids and awards committee and that the signatures set',
            ['size' => 5, 'name' => 'Century Gothic'],
            array_merge($paragraphLeft, ['indentation' => ['left' => Converter::inchToTwip(0.25)]])
        );
        $section->addText(
            'above the respected names of the committee members are their true and genuine signatures.',
            ['size' => 5, 'name' => 'Century Gothic'],
            $paragraphLeft
        );

        // Approver signatures
        $headBacName = 'N/A';
        $ceoName = 'N/A';

        if ($signatoryData) {
            $headBacName = $formatSignatoryName($signatoryData['head_bac_secretariat'] ?? null);
            $ceoName = $formatSignatoryName($signatoryData['ceo'] ?? null);
        } else {
            $headBacSignatory = \App\Models\BacSignatory::with('user')->active()->where('position', 'head_bac_secretariat')->first();
            $ceoUser = \App\Models\User::role('Executive Officer')->first();
            $headBacName = $headBacSignatory ? $headBacSignatory->full_name : 'N/A';
            $ceoName = $ceoUser ? $ceoUser->name : 'N/A';
        }

        $approverTable = $section->addTable('signatureTable');
        $approverColumnWidth = Converter::inchToTwip(4.0);
        $approverTable->addRow();

        $bacHeadCell = $approverTable->addCell($approverColumnWidth, ['valign' => 'bottom']);
        $bacHeadCell->addText($headBacName, $signatureNameStyle, $paragraphCenter);
        $bacHeadCell->addTextBreak(0.3);
        $bacHeadCell->addText('HEAD - BAC Secretariat', $signaturePositionStyle, $paragraphCenter);

        $approvedCell = $approverTable->addCell($approverColumnWidth, ['valign' => 'bottom']);
        $approvedCell->addText('ㅤㅤㅤㅤㅤAPPROVED BY:', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $paragraphLeft);
        $approvedCell->addText($ceoName, $signatureNameStyle, $paragraphCenter);
        $approvedCell->addTextBreak(0.3);
        $approvedCell->addText('Campus Executive Officer', $signaturePositionStyle, $paragraphCenter);

        $approverTable->addCell($approverColumnWidth, ['valign' => 'bottom'])
            ->addText(' ', $signatureNameStyle, $paragraphCenter);

        // Save document
        $fileName = "AOQ_{$referenceNumber}_".now()->format('Ymd_His').'.docx';
        $storagePath = "aoq_documents/{$fileName}";

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $tempPath = storage_path("app/temp_{$fileName}");
        $writer->save($tempPath);

        Storage::disk('local')->put($storagePath, file_get_contents($tempPath));
        unlink($tempPath);

        return $storagePath;
    }

    /**
     * Get withdrawal history for a purchase request
     */
    public function getWithdrawalHistory(PurchaseRequest $purchaseRequest): Collection
    {
        return SupplierWithdrawal::whereHas('purchaseRequestItem', function ($query) use ($purchaseRequest) {
            $query->where('purchase_request_id', $purchaseRequest->id);
        })
            ->with(['supplier', 'quotationItem', 'purchaseRequestItem', 'withdrawnBy', 'successorQuotationItem'])
            ->orderByDesc('withdrawn_at')
            ->get();
    }
}
