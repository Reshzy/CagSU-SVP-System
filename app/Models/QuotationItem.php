<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_within_abc' => 'boolean',
        'is_lowest' => 'boolean',
        'is_tied' => 'boolean',
        'is_winner' => 'boolean',
    ];

    /**
     * Get the quotation that owns this item
     */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Get the purchase request item this quotation item refers to
     */
    public function purchaseRequestItem()
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    /**
     * Check if this item's unit price is within the ABC
     * 
     * @return bool
     */
    public function isWithinAbc(): bool
    {
        $prItem = $this->purchaseRequestItem;
        
        if (!$prItem) {
            return false;
        }

        // If item wasn't quoted (null price), it doesn't affect ABC compliance
        if ($this->unit_price === null) {
            return true;
        }

        return $this->unit_price <= $prItem->estimated_unit_cost;
    }

    /**
     * Check if this item was quoted by the supplier
     * 
     * @return bool
     */
    public function isQuoted(): bool
    {
        return $this->unit_price !== null && $this->unit_price !== 0;
    }

    /**
     * Get the ABC (Approved Budget for Contract) for this item
     * 
     * @return float|null
     */
    public function getAbc(): ?float
    {
        return $this->purchaseRequestItem?->estimated_unit_cost;
    }

    /**
     * Calculate the difference between unit price and ABC
     * 
     * @return float|null
     */
    public function getAbcDifference(): ?float
    {
        $abc = $this->getAbc();
        
        if ($abc === null) {
            return null;
        }

        return $this->unit_price - $abc;
    }

    /**
     * Get AOQ decision for this item
     */
    public function aoqDecision()
    {
        return $this->hasOne(AoqItemDecision::class, 'winning_quotation_item_id');
    }

    /**
     * Check if this item is disqualified
     */
    public function isDisqualified(): bool
    {
        return !empty($this->disqualification_reason);
    }

    /**
     * Get status label for AOQ display
     */
    public function getAoqStatusLabel(): string
    {
        if ($this->isDisqualified()) {
            return 'Disqualified';
        }
        if ($this->is_winner) {
            return 'Winner';
        }
        if ($this->is_tied) {
            return 'Tied';
        }
        if ($this->is_lowest) {
            return 'Lowest Bid';
        }
        return 'Not Selected';
    }

    /**
     * Get status color for UI display
     */
    public function getAoqStatusColor(): string
    {
        if ($this->isDisqualified()) {
            return 'red';
        }
        if ($this->is_winner) {
            return 'green';
        }
        if ($this->is_tied) {
            return 'yellow';
        }
        if ($this->is_lowest) {
            return 'blue';
        }
        return 'gray';
    }
}

