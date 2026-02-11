<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the purchase order that owns this item
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the purchase request item
     */
    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    /**
     * Get the quotation item (winning quote)
     */
    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }
}
