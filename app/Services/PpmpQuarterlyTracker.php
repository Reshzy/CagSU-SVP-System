<?php

namespace App\Services;

use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
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
}

