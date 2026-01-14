<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity_requested' => 'integer',
        'estimated_unit_cost' => 'decimal:2',
        'estimated_total_cost' => 'decimal:2',
        'approved_budget' => 'decimal:2',
        'awarded_unit_price' => 'decimal:2',
        'awarded_total_price' => 'decimal:2',
        'needed_by_date' => 'date',
        'is_available_locally' => 'boolean',
        'failed_at' => 'datetime',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the item group this item belongs to
     */
    public function prItemGroup(): BelongsTo
    {
        return $this->belongsTo(PrItemGroup::class, 'pr_item_group_id');
    }

    /**
     * Get the PPMP item associated with this PR item
     */
    public function ppmpItem(): BelongsTo
    {
        return $this->belongsTo(PpmpItem::class);
    }

    /**
     * Get the quotation items for this PR item
     */
    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'purchase_request_item_id');
    }

    /**
     * Get the supplier withdrawals for this PR item
     */
    public function supplierWithdrawals(): HasMany
    {
        return $this->hasMany(SupplierWithdrawal::class);
    }

    /**
     * Get the replacement PR (if this item failed and was re-PR'd)
     */
    public function replacementPr(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'replacement_pr_id');
    }

    /**
     * Get the item name (from PPMP or custom)
     */
    public function getItemNameAttribute($value)
    {
        // If item_name is set, return it
        if ($value) {
            return $value;
        }

        // Otherwise, get from PPMP item
        return $this->ppmpItem ? $this->ppmpItem->item_name : null;
    }

    /**
     * Get unit of measure (from PPMP or custom)
     */
    public function getUnitOfMeasureAttribute($value)
    {
        if ($value) {
            return $value;
        }

        return $this->ppmpItem ? $this->ppmpItem->unit_of_measure : null;
    }

    /**
     * Check if procurement for this item has failed
     */
    public function hasFailed(): bool
    {
        return $this->procurement_status === 'failed';
    }

    /**
     * Check if this item has been awarded
     */
    public function isAwarded(): bool
    {
        return $this->procurement_status === 'awarded';
    }

    /**
     * Check if a replacement PR has been created for this item
     */
    public function hasReplacementPr(): bool
    {
        return $this->procurement_status === 're_pr_created' && $this->replacement_pr_id !== null;
    }

    /**
     * Mark this item as failed procurement
     */
    public function markAsFailed(string $reason): bool
    {
        $this->update([
            'procurement_status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Mark this item as awarded
     */
    public function markAsAwarded(int $supplierId, float $unitPrice): bool
    {
        $totalPrice = $unitPrice * $this->quantity_requested;

        $this->update([
            'procurement_status' => 'awarded',
            'awarded_supplier_id' => $supplierId,
            'awarded_unit_price' => $unitPrice,
            'awarded_total_price' => $totalPrice,
        ]);

        return true;
    }

    /**
     * Return quantity back to PPMP after failed procurement
     * This effectively "releases" the quantity so it can be used in a new PR
     */
    public function returnQuantityToPpmp(): bool
    {
        // The PPMP quantity tracking is based on active PRs
        // By marking this PR item as failed, the quantity will automatically
        // become available again because getRemainingQuantity() excludes
        // items from cancelled/rejected/returned PRs
        //
        // However, we need to ensure the PR status reflects the failure
        // This is handled at the PR level when all items fail

        return true;
    }

    /**
     * Link this item to a replacement PR
     */
    public function linkToReplacementPr(PurchaseRequest $replacementPr): bool
    {
        $this->update([
            'procurement_status' => 're_pr_created',
            'replacement_pr_id' => $replacementPr->id,
        ]);

        return true;
    }

    /**
     * Get the winning quotation item for this PR item
     */
    public function getWinningQuotationItem(): ?QuotationItem
    {
        return $this->quotationItems()
            ->where('is_winner', true)
            ->where('is_withdrawn', false)
            ->first();
    }

    /**
     * Check if this item has any eligible quotations remaining
     */
    public function hasEligibleQuotations(): bool
    {
        return $this->quotationItems()
            ->eligible()
            ->exists();
    }

    /**
     * Scope to get failed items
     */
    public function scopeFailed($query)
    {
        return $query->where('procurement_status', 'failed');
    }

    /**
     * Scope to get awarded items
     */
    public function scopeAwarded($query)
    {
        return $query->where('procurement_status', 'awarded');
    }

    /**
     * Scope to get pending items
     */
    public function scopePending($query)
    {
        return $query->where('procurement_status', 'pending');
    }

    /**
     * Scope to get items that need re-PR
     */
    public function scopeNeedsRePr($query)
    {
        return $query->where('procurement_status', 'failed')
            ->whereNull('replacement_pr_id');
    }
}
