<?php

namespace App\Services;

use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\QuotationItem;
use App\Models\SupplierWithdrawal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierWithdrawalService
{
    protected AoqService $aoqService;

    public function __construct(AoqService $aoqService)
    {
        $this->aoqService = $aoqService;
    }

    /**
     * Process a withdrawal for a specific quotation item
     */
    public function withdraw(QuotationItem $quotationItem, string $reason, User $processedBy): array
    {
        // Validate the withdrawal can happen
        $validation = $this->canWithdraw($quotationItem);
        if (! $validation['can_withdraw']) {
            return [
                'success' => false,
                'message' => $validation['reason'],
            ];
        }

        // Delegate to AoqService for the actual withdrawal processing
        $result = $this->aoqService->processSupplierWithdrawal($quotationItem, $reason, $processedBy);

        // Log activity for supplier withdrawal
        if ($result['success']) {
            $quotation = $quotationItem->quotation;
            $quotation->load('supplier', 'prItemGroup', 'purchaseRequest');

            $successorName = $result['has_successor']
                ? $result['successor']->quotation->supplier->business_name
                : null;

            $activityLogger = new PurchaseRequestActivityLogger;
            $activityLogger->logSupplierWithdrawal(
                $quotation->purchaseRequest,
                $quotation,
                $reason,
                $successorName
            );
        }

        return $result;
    }

    /**
     * Check if a quotation item can be withdrawn
     */
    public function canWithdraw(QuotationItem $quotationItem): array
    {
        // Must be a winner
        if (! $quotationItem->is_winner) {
            return [
                'can_withdraw' => false,
                'reason' => 'Only winning quotations can be withdrawn.',
            ];
        }

        // Must not already be withdrawn
        if ($quotationItem->isWithdrawn()) {
            return [
                'can_withdraw' => false,
                'reason' => 'This quotation has already been withdrawn.',
            ];
        }

        // Must be quoted
        if (! $quotationItem->isQuoted()) {
            return [
                'can_withdraw' => false,
                'reason' => 'Cannot withdraw an unquoted item.',
            ];
        }

        // Must not be disqualified
        if ($quotationItem->isDisqualified()) {
            return [
                'can_withdraw' => false,
                'reason' => 'Cannot withdraw a disqualified quotation.',
            ];
        }

        // Check if the PR is still in a state that allows withdrawals
        $purchaseRequest = $quotationItem->quotation->purchaseRequest;
        if (! in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved'])) {
            return [
                'can_withdraw' => false,
                'reason' => 'Withdrawals are only allowed during BAC evaluation or after approval.',
            ];
        }

        return [
            'can_withdraw' => true,
            'reason' => null,
        ];
    }

    /**
     * Get withdrawal history for a purchase request
     */
    public function getWithdrawalHistory(PurchaseRequest $purchaseRequest): Collection
    {
        return $this->aoqService->getWithdrawalHistory($purchaseRequest);
    }

    /**
     * Get withdrawal history for a specific item group
     */
    public function getWithdrawalHistoryForGroup(PrItemGroup $itemGroup): Collection
    {
        return SupplierWithdrawal::where('pr_item_group_id', $itemGroup->id)
            ->with(['supplier', 'quotationItem', 'purchaseRequestItem', 'withdrawnBy', 'successorQuotationItem'])
            ->orderByDesc('withdrawn_at')
            ->get();
    }

    /**
     * Get withdrawal history for a specific PR item
     */
    public function getWithdrawalHistoryForItem(PurchaseRequestItem $prItem): Collection
    {
        return SupplierWithdrawal::where('purchase_request_item_id', $prItem->id)
            ->with(['supplier', 'quotationItem', 'withdrawnBy', 'successorQuotationItem'])
            ->orderByDesc('withdrawn_at')
            ->get();
    }

    /**
     * Get all items that have failed procurement due to withdrawals
     */
    public function getFailedItemsDueToWithdrawals(PurchaseRequest $purchaseRequest): Collection
    {
        return $purchaseRequest->items()
            ->where('procurement_status', 'failed')
            ->whereHas('supplierWithdrawals', function ($query) {
                $query->where('resulted_in_failure', true);
            })
            ->get();
    }

    /**
     * Check if any items need re-PR due to failed procurement
     */
    public function hasItemsNeedingRePr(PurchaseRequest $purchaseRequest): bool
    {
        return $purchaseRequest->items()
            ->where('procurement_status', 'failed')
            ->whereNull('replacement_pr_id')
            ->exists();
    }

    /**
     * Get summary of withdrawals for a PR
     */
    public function getWithdrawalSummary(PurchaseRequest $purchaseRequest): array
    {
        $withdrawals = $this->getWithdrawalHistory($purchaseRequest);

        return [
            'total_withdrawals' => $withdrawals->count(),
            'withdrawals_with_successors' => $withdrawals->where('resulted_in_failure', false)->count(),
            'withdrawals_causing_failure' => $withdrawals->where('resulted_in_failure', true)->count(),
            'unique_suppliers_withdrawn' => $withdrawals->pluck('supplier_id')->unique()->count(),
            'unique_items_affected' => $withdrawals->pluck('purchase_request_item_id')->unique()->count(),
        ];
    }

    /**
     * Bulk withdraw all winning items for a supplier from a PR
     * Useful when a supplier wants to withdraw entirely
     */
    public function withdrawAllForSupplier(
        PurchaseRequest $purchaseRequest,
        int $supplierId,
        string $reason,
        User $processedBy
    ): array {
        $results = [];

        return DB::transaction(function () use ($purchaseRequest, $supplierId, $reason, $processedBy, &$results) {
            // Get all winning quotation items for this supplier in this PR
            $winningItems = QuotationItem::whereHas('quotation', function ($query) use ($purchaseRequest, $supplierId) {
                $query->where('purchase_request_id', $purchaseRequest->id)
                    ->where('supplier_id', $supplierId);
            })
                ->where('is_winner', true)
                ->where('is_withdrawn', false)
                ->get();

            foreach ($winningItems as $quotationItem) {
                $result = $this->withdraw($quotationItem, $reason, $processedBy);
                $results[] = [
                    'item_name' => $quotationItem->purchaseRequestItem->item_name,
                    'result' => $result,
                ];
            }

            return [
                'success' => true,
                'items_processed' => count($results),
                'details' => $results,
            ];
        });
    }

    /**
     * Get the next eligible bidder info for a quotation item
     * Useful for showing what will happen if withdrawal proceeds
     */
    public function getNextBidderPreview(QuotationItem $quotationItem): ?array
    {
        $nextBidder = $quotationItem->getNextRankedBidder();

        if (! $nextBidder) {
            return null;
        }

        return [
            'supplier_name' => $nextBidder->quotation->supplier->business_name,
            'unit_price' => $nextBidder->unit_price,
            'total_price' => $nextBidder->total_price,
            'rank' => $nextBidder->rank,
        ];
    }

    /**
     * Check if withdrawal would result in failed procurement
     */
    public function wouldCauseFailure(QuotationItem $quotationItem): bool
    {
        return $quotationItem->getNextRankedBidder() === null;
    }
}
