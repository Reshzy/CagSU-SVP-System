<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AoqItemDecision extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'decided_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the purchase request
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the purchase request item
     */
    public function purchaseRequestItem()
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    /**
     * Get the winning quotation item
     */
    public function winningQuotationItem()
    {
        return $this->belongsTo(QuotationItem::class, 'winning_quotation_item_id');
    }

    /**
     * Get the BAC officer who made the decision
     */
    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Check if this was an automatic decision (no ties)
     */
    public function isAutomatic(): bool
    {
        return $this->decision_type === 'auto';
    }

    /**
     * Check if this was a tie resolution
     */
    public function isTieResolution(): bool
    {
        return $this->decision_type === 'tie_resolution';
    }

    /**
     * Check if this was a BAC override
     */
    public function isBacOverride(): bool
    {
        return $this->decision_type === 'bac_override';
    }

    /**
     * Get display label for decision type
     */
    public function getDecisionTypeLabel(): string
    {
        return match ($this->decision_type) {
            'auto' => 'Automatic (Lowest Bid)',
            'tie_resolution' => 'Tie Resolution',
            'bac_override' => 'BAC Override',
            'withdrawal_succession' => 'Withdrawal Succession',
            default => 'Unknown'
        };
    }
}
