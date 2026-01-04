<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpmpItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ppmp_id',
        'app_item_id',
        'q1_quantity',
        'q2_quantity',
        'q3_quantity',
        'q4_quantity',
        'total_quantity',
        'estimated_unit_cost',
        'estimated_total_cost',
    ];

    protected function casts(): array
    {
        return [
            'q1_quantity' => 'integer',
            'q2_quantity' => 'integer',
            'q3_quantity' => 'integer',
            'q4_quantity' => 'integer',
            'total_quantity' => 'integer',
            'estimated_unit_cost' => 'decimal:2',
            'estimated_total_cost' => 'decimal:2',
        ];
    }

    /**
     * Get the PPMP that owns this item
     */
    public function ppmp(): BelongsTo
    {
        return $this->belongsTo(Ppmp::class);
    }

    /**
     * Get the APP item this references
     */
    public function appItem(): BelongsTo
    {
        return $this->belongsTo(AppItem::class);
    }

    /**
     * Get purchase request items that use this PPMP item
     */
    public function purchaseRequestItems(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    /**
     * Get the total quantity across all quarters
     */
    public function getTotalQuantity(): int
    {
        return $this->q1_quantity + $this->q2_quantity + $this->q3_quantity + $this->q4_quantity;
    }

    /**
     * Get quantity for a specific quarter
     */
    public function getQuarterlyQuantity(int $quarter): int
    {
        return match ($quarter) {
            1 => $this->q1_quantity,
            2 => $this->q2_quantity,
            3 => $this->q3_quantity,
            4 => $this->q4_quantity,
            default => 0,
        };
    }

    /**
     * Get remaining quantity for a specific quarter (not yet in PRs)
     */
    public function getRemainingQuantity(?int $quarter = null): int
    {
        $plannedQty = $quarter ? $this->getQuarterlyQuantity($quarter) : $this->total_quantity;

        $usedQty = $this->purchaseRequestItems()
            ->whereHas('purchaseRequest', function ($query) use ($quarter) {
                if ($quarter) {
                    // Filter by quarter based on created_at
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;
                    $query->whereMonth('created_at', '>=', $startMonth)
                        ->whereMonth('created_at', '<=', $endMonth);
                }
            })
            ->sum('quantity_requested');

        return max(0, $plannedQty - $usedQty);
    }

    /**
     * Calculate estimated total cost
     */
    public function calculateEstimatedTotalCost(): float
    {
        return (float) ($this->total_quantity * $this->estimated_unit_cost);
    }

    /**
     * Scope to filter by PPMP
     */
    public function scopeForPpmp($query, int $ppmpId)
    {
        return $query->where('ppmp_id', $ppmpId);
    }

    /**
     * Scope to filter by APP item
     */
    public function scopeForAppItem($query, int $appItemId)
    {
        return $query->where('app_item_id', $appItemId);
    }

    /**
     * Check if this item is available for the current quarter
     */
    public function isAvailableForCurrentQuarter(): bool
    {
        $currentQuarter = $this->getCurrentQuarter();

        return $this->hasQuantityForQuarter($currentQuarter)
            && $this->getRemainingQuantity($currentQuarter) > 0;
    }

    /**
     * Check if this item has quantity allocated for a specific quarter
     */
    public function hasQuantityForQuarter(int $quarter): bool
    {
        return $this->getQuarterlyQuantity($quarter) > 0;
    }

    /**
     * Get the availability status for a specific quarter
     * Returns: 'past', 'current', 'future', or 'unavailable'
     */
    public function getQuarterStatus(int $currentQuarter): string
    {
        // Find which quarter(s) this item has quantity allocated
        $allocatedQuarters = [];
        for ($q = 1; $q <= 4; $q++) {
            if ($this->hasQuantityForQuarter($q)) {
                $allocatedQuarters[] = $q;
            }
        }

        if (empty($allocatedQuarters)) {
            return 'unavailable';
        }

        // Check if any allocated quarter is the current quarter
        if (in_array($currentQuarter, $allocatedQuarters)) {
            return 'current';
        }

        // Check if all allocated quarters are in the past
        if (max($allocatedQuarters) < $currentQuarter) {
            return 'past';
        }

        // Otherwise, it's in a future quarter
        return 'future';
    }

    /**
     * Get remaining quantity for current quarter with real-time PR usage tracking
     */
    public function getRemainingQuantityForCurrentQuarter(): int
    {
        $currentQuarter = $this->getCurrentQuarter();

        return $this->getRemainingQuantity($currentQuarter);
    }

    /**
     * Get the next available quarter for this item
     */
    public function getNextAvailableQuarter(): ?int
    {
        $currentQuarter = $this->getCurrentQuarter();

        for ($q = $currentQuarter + 1; $q <= 4; $q++) {
            if ($this->hasQuantityForQuarter($q)) {
                return $q;
            }
        }

        return null;
    }

    /**
     * Get the month range label for a specific quarter
     */
    public function getQuarterMonths(?int $quarter = null): string
    {
        if ($quarter === null) {
            $quarter = $this->getNextAvailableQuarter();
        }

        return match ($quarter) {
            1 => 'January to March',
            2 => 'April to June',
            3 => 'July to September',
            4 => 'October to December',
            default => 'Unknown',
        };
    }

    /**
     * Get the current quarter based on today's date
     */
    protected function getCurrentQuarter(): int
    {
        $month = now()->month;

        return (int) ceil($month / 3);
    }
}
