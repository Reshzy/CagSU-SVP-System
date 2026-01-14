<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'is_withdrawn' => 'boolean',
        'withdrawn_at' => 'datetime',
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
     * Get the withdrawal records for this quotation item
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(SupplierWithdrawal::class);
    }

    /**
     * Get cases where this item became the successor (new winner after withdrawal)
     */
    public function successorOf(): HasMany
    {
        return $this->hasMany(SupplierWithdrawal::class, 'successor_quotation_item_id');
    }

    /**
     * Check if this item's unit price is within the ABC
     */
    public function isWithinAbc(): bool
    {
        $prItem = $this->purchaseRequestItem;

        if (! $prItem) {
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
     */
    public function isQuoted(): bool
    {
        return $this->unit_price !== null && $this->unit_price !== 0;
    }

    /**
     * Get the ABC (Approved Budget for Contract) for this item
     */
    public function getAbc(): ?float
    {
        return $this->purchaseRequestItem?->estimated_unit_cost;
    }

    /**
     * Calculate the difference between unit price and ABC
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
    public function aoqDecision(): HasOne
    {
        return $this->hasOne(AoqItemDecision::class, 'winning_quotation_item_id');
    }

    /**
     * Check if this item is disqualified
     */
    public function isDisqualified(): bool
    {
        return ! empty($this->disqualification_reason);
    }

    /**
     * Check if this item has been withdrawn
     */
    public function isWithdrawn(): bool
    {
        return $this->is_withdrawn === true;
    }

    /**
     * Check if this quotation item can be withdrawn
     * Only winning items that haven't been withdrawn can be withdrawn
     */
    public function canWithdraw(): bool
    {
        // Must be a winner
        if (! $this->is_winner) {
            return false;
        }

        // Must not already be withdrawn
        if ($this->isWithdrawn()) {
            return false;
        }

        // Must be quoted
        if (! $this->isQuoted()) {
            return false;
        }

        // Must not be disqualified
        if ($this->isDisqualified()) {
            return false;
        }

        return true;
    }

    /**
     * Withdraw this quotation item
     */
    public function withdraw(string $reason): bool
    {
        if (! $this->canWithdraw()) {
            return false;
        }

        $this->update([
            'is_withdrawn' => true,
            'withdrawn_at' => now(),
            'withdrawal_reason' => $reason,
            'is_winner' => false,
        ]);

        return true;
    }

    /**
     * Get the next ranked eligible bidder for the same PR item
     * Returns null if no eligible successor exists
     */
    public function getNextRankedBidder(): ?QuotationItem
    {
        $prItemId = $this->purchase_request_item_id;

        // Find all quotation items for the same PR item
        // that are: quoted, within ABC, not disqualified, not withdrawn, and ranked higher than current
        return static::where('purchase_request_item_id', $prItemId)
            ->where('id', '!=', $this->id)
            ->whereNotNull('unit_price')
            ->where('is_within_abc', true)
            ->where(function ($query) {
                $query->whereNull('disqualification_reason')
                    ->orWhere('disqualification_reason', '');
            })
            ->where('is_withdrawn', false)
            ->where('is_winner', false)
            ->orderBy('rank', 'asc')
            ->first();
    }

    /**
     * Get all eligible bidders for the same PR item (excluding this one)
     */
    public function getEligibleBidders()
    {
        $prItemId = $this->purchase_request_item_id;

        return static::where('purchase_request_item_id', $prItemId)
            ->where('id', '!=', $this->id)
            ->whereNotNull('unit_price')
            ->where('is_within_abc', true)
            ->where(function ($query) {
                $query->whereNull('disqualification_reason')
                    ->orWhere('disqualification_reason', '');
            })
            ->where('is_withdrawn', false)
            ->orderBy('rank', 'asc')
            ->get();
    }

    /**
     * Get status label for AOQ display
     */
    public function getAoqStatusLabel(): string
    {
        if ($this->isWithdrawn()) {
            return 'Withdrawn';
        }
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
        if ($this->isWithdrawn()) {
            return 'orange';
        }
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

    /**
     * Scope to get non-withdrawn items
     */
    public function scopeNotWithdrawn($query)
    {
        return $query->where('is_withdrawn', false);
    }

    /**
     * Scope to get withdrawn items
     */
    public function scopeWithdrawn($query)
    {
        return $query->where('is_withdrawn', true);
    }

    /**
     * Scope to get eligible items (quoted, within ABC, not disqualified, not withdrawn)
     */
    public function scopeEligible($query)
    {
        return $query->whereNotNull('unit_price')
            ->where('is_within_abc', true)
            ->where(function ($q) {
                $q->whereNull('disqualification_reason')
                    ->orWhere('disqualification_reason', '');
            })
            ->where('is_withdrawn', false);
    }
}
