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

    /**
     * Compute the current status of this group based on its PO statuses and AOQ.
     *
     * Group status hierarchy (from earliest to latest):
     * - pending: No AOQ generated yet
     * - aoq_generated: AOQ done, ready for PO
     * - po_created: At least one PO exists (pending approval)
     * - all_po_approved: All POs approved
     * - processing: At least one PO sent to supplier
     * - delivered: All POs delivered or completed
     * - completed: All POs completed
     */
    public function computeStatus(): string
    {
        $pos = $this->purchaseOrders;

        if ($pos->isEmpty()) {
            return $this->aoqGeneration()->exists() ? 'aoq_generated' : 'pending';
        }

        if ($pos->every(fn ($po) => $po->status === 'completed')) {
            return 'completed';
        }

        if ($pos->every(fn ($po) => in_array($po->status, ['delivered', 'completed']))) {
            return 'delivered';
        }

        if ($pos->some(fn ($po) => in_array($po->status, ['sent_to_supplier', 'acknowledged_by_supplier', 'delivered', 'completed']))) {
            return 'processing';
        }

        if ($pos->every(fn ($po) => in_array($po->status, ['approved', 'sent_to_supplier', 'acknowledged_by_supplier', 'delivered', 'completed']))) {
            return 'all_po_approved';
        }

        return 'po_created';
    }

    /**
     * Determine if this group can have a new AOQ generated.
     * Only allowed when the group has no AOQ or PO yet.
     */
    public function canCreateAoq(): bool
    {
        return $this->computeStatus() === 'pending';
    }

    /**
     * Determine if this group can regenerate its AOQ (e.g. winner changed or supplier withdrew).
     * Allowed when the group has an AOQ but no Purchase Order yet.
     */
    public function canRegenerateAoq(): bool
    {
        return $this->computeStatus() === 'aoq_generated';
    }

    /**
     * Determine if this group can have a new PO created.
     * Allowed once an AOQ exists and the group has not yet been fully processed.
     */
    public function canCreatePo(): bool
    {
        return in_array($this->computeStatus(), ['aoq_generated', 'po_created']);
    }

    /**
     * Determine if this group has been fully completed.
     */
    public function isCompleted(): bool
    {
        return $this->computeStatus() === 'completed';
    }
}
