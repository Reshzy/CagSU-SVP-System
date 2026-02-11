<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PrItemGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the purchase request that owns this group
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the items in this group
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class, 'pr_item_group_id');
    }

    /**
     * Get the RFQ generation for this group
     */
    public function rfqGeneration(): HasOne
    {
        return $this->hasOne(RfqGeneration::class, 'pr_item_group_id');
    }

    /**
     * Get the quotations for this group
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'pr_item_group_id');
    }

    /**
     * Get the AOQ generation for this group
     */
    public function aoqGeneration(): HasOne
    {
        return $this->hasOne(AoqGeneration::class, 'pr_item_group_id');
    }

    /**
     * Get the purchase orders for this group
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'pr_item_group_id');
    }

    /**
     * Generate next group code for a purchase request (G1, G2, G3, etc.)
     */
    public static function generateNextGroupCode(PurchaseRequest $purchaseRequest): string
    {
        $lastGroup = static::where('purchase_request_id', $purchaseRequest->id)
            ->orderByDesc('group_code')
            ->value('group_code');

        if (! $lastGroup) {
            return 'G1';
        }

        $number = (int) substr($lastGroup, 1);
        $nextNumber = $number + 1;

        return 'G'.$nextNumber;
    }

    /**
     * Calculate total estimated cost of items in this group
     */
    public function calculateTotalCost(): float
    {
        return (float) $this->items->sum('estimated_total_cost');
    }

    /**
     * Check if this group is ready for PO creation
     * A group is ready when an AOQ has been generated for it
     */
    public function isReadyForPo(): bool
    {
        return $this->aoqGeneration()->exists();
    }

    /**
     * Check if this group already has a PO created
     */
    public function hasExistingPo(): bool
    {
        return $this->purchaseOrders()->exists();
    }

    /**
     * Get the winning quotation for this group
     */
    public function getWinningQuotation(): ?Quotation
    {
        return $this->quotations()->where('is_winning_bid', true)->first();
    }
}
