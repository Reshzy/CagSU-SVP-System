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
}
