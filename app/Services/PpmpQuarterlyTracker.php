<?php

namespace App\Services;

use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Carbon\Carbon;

class PpmpQuarterlyTracker
{
    /**
     * Get remaining quantity for a PPMP item in a specific quarter
     */
    public function getRemainingQuantity(PpmpItem $ppmpItem, ?int $quarter = null): int
    {
        if ($quarter === null) {
            // Return total remaining quantity
            return $ppmpItem->getRemainingQuantity();
        }

        return $ppmpItem->getRemainingQuantity($quarter);
    }

    /**
     * Check if a PR can be created with the given quantity
     */
    public function canCreatePR(PpmpItem $ppmpItem, int $quantity, ?int $quarter = null): bool
    {
        $remaining = $this->getRemainingQuantity($ppmpItem, $quarter);

        return $quantity <= $remaining;
    }

    /**
     * Track PR quantities against PPMP
     */
    public function trackPrAgainstPpmp(PurchaseRequest $pr): array
    {
        $tracked = [];

        foreach ($pr->items as $prItem) {
            if ($prItem->ppmp_item_id) {
                $ppmpItem = $prItem->ppmpItem;
                $quarter = $this->getQuarterFromDate($pr->created_at);

                $tracked[] = [
                    'ppmp_item_id' => $ppmpItem->id,
                    'pr_item_id' => $prItem->id,
                    'quarter' => $quarter,
                    'quantity_requested' => $prItem->quantity_requested,
                    'remaining_in_quarter' => $this->getRemainingQuantity($ppmpItem, $quarter),
                    'remaining_total' => $this->getRemainingQuantity($ppmpItem),
                ];
            }
        }

        return $tracked;
    }

    /**
     * Get current quarter (1-4) from date
     */
    public function getQuarterFromDate(?Carbon $date = null): int
    {
        $date = $date ?? now();
        $month = $date->month;

        return (int) ceil($month / 3);
    }

    /**
     * Get quarter date range
     */
    public function getQuarterDateRange(int $quarter, int $year): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        return [
            'start' => Carbon::create($year, $startMonth, 1)->startOfMonth(),
            'end' => Carbon::create($year, $endMonth, 1)->endOfMonth(),
        ];
    }

    /**
     * Get usage summary for a PPMP item
     */
    public function getUsageSummary(PpmpItem $ppmpItem): array
    {
        $summary = [
            'total_planned' => $ppmpItem->total_quantity,
            'quarters' => [],
        ];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $planned = $ppmpItem->getQuarterlyQuantity($quarter);
            $used = $this->getUsedQuantity($ppmpItem, $quarter);
            $remaining = max(0, $planned - $used);

            $summary['quarters'][$quarter] = [
                'quarter' => $quarter,
                'planned' => $planned,
                'used' => $used,
                'remaining' => $remaining,
                'utilization_percentage' => $planned > 0 ? ($used / $planned) * 100 : 0,
            ];
        }

        $totalUsed = array_sum(array_column($summary['quarters'], 'used'));
        $summary['total_used'] = $totalUsed;
        $summary['total_remaining'] = max(0, $summary['total_planned'] - $totalUsed);
        $summary['total_utilization_percentage'] = $summary['total_planned'] > 0
            ? ($totalUsed / $summary['total_planned']) * 100
            : 0;

        return $summary;
    }

    /**
     * Get used quantity for a PPMP item in a specific quarter
     */
    protected function getUsedQuantity(PpmpItem $ppmpItem, int $quarter): int
    {
        $fiscalYear = $ppmpItem->ppmp->fiscal_year;
        $dateRange = $this->getQuarterDateRange($quarter, $fiscalYear);

        return $ppmpItem->purchaseRequestItems()
            ->whereHas('purchaseRequest', function ($query) use ($dateRange) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            })
            ->sum('quantity_requested');
    }

    /**
     * Check if any PPMP items would exceed their quarterly limits
     */
    public function validatePrQuantities(PurchaseRequest $pr): array
    {
        $warnings = [];
        $quarter = $this->getQuarterFromDate($pr->created_at);

        foreach ($pr->items as $prItem) {
            if ($prItem->ppmp_item_id) {
                $ppmpItem = $prItem->ppmpItem;
                $remaining = $this->getRemainingQuantity($ppmpItem, $quarter);

                if ($prItem->quantity_requested > $remaining) {
                    $warnings[] = [
                        'item_name' => $ppmpItem->appItem->item_name,
                        'requested' => $prItem->quantity_requested,
                        'remaining' => $remaining,
                        'quarter' => $quarter,
                        'message' => "Requested quantity ({$prItem->quantity_requested}) exceeds remaining PPMP quantity ({$remaining}) for Q{$quarter}",
                    ];
                }
            }
        }

        return $warnings;
    }

    /**
     * Get quarter label (e.g., "January to March")
     */
    public function getQuarterLabel(int $quarter): string
    {
        return match ($quarter) {
            1 => 'January to March',
            2 => 'April to June',
            3 => 'July to September',
            4 => 'October to December',
            default => 'Unknown Quarter',
        };
    }

    /**
     * Check if current date is in specified quarter
     */
    public function isCurrentQuarter(int $quarter, int $year): bool
    {
        $currentQuarter = $this->getQuarterFromDate();
        $currentYear = now()->year;

        return $currentQuarter === $quarter && $currentYear === $year;
    }

    /**
     * Get all PPMP items available for current quarter
     */
    public function getAvailableItemsForCurrentQuarter(Ppmp $ppmp): \Illuminate\Support\Collection
    {
        $currentQuarter = $this->getQuarterFromDate();

        return $ppmp->items->filter(function ($item) use ($currentQuarter) {
            return $item->hasQuantityForQuarter($currentQuarter)
                && $item->getRemainingQuantity($currentQuarter) > 0;
        });
    }

    /**
     * Check if current date is within grace period for a specific quarter
     */
    public function isWithinGracePeriod(int $targetQuarter, ?int $year = null): bool
    {
        // Check if grace period is enabled
        if (! config('ppmp.enable_grace_period', true)) {
            return false;
        }

        $year = $year ?? now()->year;
        $currentDate = now();
        $currentQuarter = $this->getQuarterFromDate($currentDate);

        // Grace period only applies when we're in the quarter AFTER the target quarter
        if ($currentQuarter !== $targetQuarter + 1 && ! ($targetQuarter === 4 && $currentQuarter === 1)) {
            return false;
        }

        // For Q4 -> Q1 transition, check year
        if ($targetQuarter === 4 && $currentQuarter === 1) {
            if ($currentDate->year !== $year + 1) {
                return false;
            }
        }

        $gracePeriodEndDate = $this->getGracePeriodEndDate($targetQuarter, $year);

        return $currentDate->lte($gracePeriodEndDate);
    }

    /**
     * Get the end date of the grace period for a specific quarter
     */
    public function getGracePeriodEndDate(int $quarter, int $year): Carbon
    {
        $gracePeriodDays = config('ppmp.quarter_grace_period_days', 14);
        $dateRange = $this->getQuarterDateRange($quarter, $year);

        // Grace period starts the day after quarter ends
        return $dateRange['end']->copy()->addDays($gracePeriodDays);
    }

    /**
     * Get available quarters for replacement PRs (current + previous if in grace period)
     */
    public function getAvailableQuartersForReplacement(?int $year = null): array
    {
        $year = $year ?? now()->year;
        $currentQuarter = $this->getQuarterFromDate();
        $availableQuarters = [$currentQuarter];

        // Check if previous quarter is available via grace period
        $previousQuarter = $currentQuarter - 1;
        $previousYear = $year;

        // Handle Q1 -> check Q4 of previous year
        if ($previousQuarter === 0) {
            $previousQuarter = 4;
            $previousYear = $year - 1;
        }

        if ($this->isWithinGracePeriod($previousQuarter, $previousYear)) {
            array_unshift($availableQuarters, $previousQuarter);
        }

        return $availableQuarters;
    }

    /**
     * Get grace period information for display
     */
    public function getGracePeriodInfo(): ?array
    {
        if (! config('ppmp.enable_grace_period', true)) {
            return null;
        }

        $currentQuarter = $this->getQuarterFromDate();
        $currentYear = now()->year;
        $previousQuarter = $currentQuarter - 1;
        $previousYear = $currentYear;

        // Handle Q1 -> check Q4 of previous year
        if ($previousQuarter === 0) {
            $previousQuarter = 4;
            $previousYear = $currentYear - 1;
        }

        if ($this->isWithinGracePeriod($previousQuarter, $previousYear)) {
            $endDate = $this->getGracePeriodEndDate($previousQuarter, $previousYear);
            $daysRemaining = now()->diffInDays($endDate, false);

            return [
                'active' => true,
                'quarter' => $previousQuarter,
                'year' => $previousYear,
                'quarter_label' => $this->getQuarterLabel($previousQuarter),
                'end_date' => $endDate,
                'end_date_formatted' => $endDate->format('F j, Y'),
                'days_remaining' => (int) ceil($daysRemaining),
                'expiring_soon' => $daysRemaining <= 3,
            ];
        }

        return null;
    }

    /**
     * Return quantity from a failed PR item back to PPMP
     * This makes the quantity available again for new PRs
     */
    public function returnQuantityFromFailedPr(PurchaseRequestItem $prItem): array
    {
        // The PPMP quantity tracking is based on active PRs
        // When a PR item fails, we need to ensure it's excluded from "used" calculations
        // This happens automatically when the PR is marked with a status that excludes it
        // from the getRemainingQuantity calculation

        $ppmpItem = $prItem->ppmpItem;
        if (! $ppmpItem) {
            return [
                'success' => false,
                'message' => 'PR item is not linked to a PPMP item.',
            ];
        }

        $quarter = $prItem->ppmp_quarter ?? $this->getQuarterFromDate($prItem->purchaseRequest->created_at);

        // Get quantities before and after (for logging/audit purposes)
        $quantityReturned = $prItem->quantity_requested;

        // The actual "return" happens because the PR item's status is now 'failed'
        // and the getRemainingQuantity method excludes failed/cancelled/rejected PRs
        // So we just need to verify the quantity is now available

        $remainingAfter = $this->getRemainingQuantity($ppmpItem, $quarter);

        return [
            'success' => true,
            'ppmp_item_id' => $ppmpItem->id,
            'item_name' => $ppmpItem->appItem->item_name ?? $prItem->item_name,
            'quantity_returned' => $quantityReturned,
            'quarter' => $quarter,
            'remaining_after_return' => $remainingAfter,
            'message' => "Returned {$quantityReturned} units to PPMP for Q{$quarter}.",
        ];
    }

    /**
     * Bulk return quantities for multiple failed PR items
     */
    public function returnQuantitiesFromFailedItems(array $prItems): array
    {
        $results = [];
        $totalReturned = 0;

        foreach ($prItems as $prItem) {
            if ($prItem instanceof PurchaseRequestItem) {
                $result = $this->returnQuantityFromFailedPr($prItem);
                $results[] = $result;
                if ($result['success']) {
                    $totalReturned += $result['quantity_returned'];
                }
            }
        }

        return [
            'success' => true,
            'items_processed' => count($results),
            'total_quantity_returned' => $totalReturned,
            'details' => $results,
        ];
    }

    /**
     * Check if a PPMP item has available quantity after accounting for failed PRs
     */
    public function hasAvailableQuantityAfterFailures(PpmpItem $ppmpItem, ?int $quarter = null): bool
    {
        return $this->getRemainingQuantity($ppmpItem, $quarter) > 0;
    }

    /**
     * Get summary of quantities returned due to failed procurement
     */
    public function getReturnedQuantitiesSummary(PpmpItem $ppmpItem): array
    {
        // Get all failed PR items that were linked to this PPMP item
        $failedItems = $ppmpItem->purchaseRequestItems()
            ->whereHas('purchaseRequest', function ($query) {
                $query->whereIn('status', ['cancelled', 'rejected']);
            })
            ->orWhere('procurement_status', 'failed')
            ->get();

        $totalReturned = $failedItems->sum('quantity_requested');
        $byQuarter = [];

        foreach ($failedItems as $item) {
            $quarter = $item->ppmp_quarter ?? 0;
            if (! isset($byQuarter[$quarter])) {
                $byQuarter[$quarter] = 0;
            }
            $byQuarter[$quarter] += $item->quantity_requested;
        }

        return [
            'ppmp_item_id' => $ppmpItem->id,
            'total_returned' => $totalReturned,
            'by_quarter' => $byQuarter,
            'failed_items_count' => $failedItems->count(),
        ];
    }
}
