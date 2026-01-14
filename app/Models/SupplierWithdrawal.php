<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierWithdrawal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'withdrawn_at' => 'datetime',
            'resulted_in_failure' => 'boolean',
        ];
    }

    /**
     * Get the quotation item that was withdrawn
     */
    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }

    /**
     * Get the supplier who withdrew
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase request item this withdrawal is for
     */
    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    /**
     * Get the item group (if grouped PR)
     */
    public function prItemGroup(): BelongsTo
    {
        return $this->belongsTo(PrItemGroup::class);
    }

    /**
     * Get the user who processed the withdrawal
     */
    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }

    /**
     * Get the successor quotation item (new winner)
     */
    public function successorQuotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class, 'successor_quotation_item_id');
    }

    /**
     * Scope to get withdrawals that resulted in procurement failure
     */
    public function scopeResultedInFailure($query)
    {
        return $query->where('resulted_in_failure', true);
    }

    /**
     * Scope to get withdrawals for a specific purchase request item
     */
    public function scopeForPrItem($query, int $prItemId)
    {
        return $query->where('purchase_request_item_id', $prItemId);
    }

    /**
     * Scope to get withdrawals for a specific supplier
     */
    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }
}
