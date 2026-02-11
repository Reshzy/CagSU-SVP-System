<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'po_date' => 'date',
        'total_amount' => 'decimal:2',
        'delivery_date_required' => 'date',
        'approved_at' => 'datetime',
        'sent_to_supplier_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'actual_delivery_date' => 'date',
        'delivery_complete' => 'boolean',
        'ors_burs_date' => 'date',
        'funds_available' => 'decimal:2',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function prItemGroup()
    {
        return $this->belongsTo(PrItemGroup::class, 'pr_item_group_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Get the items in this purchase order
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Generate next PO number in format: PO-MMYY-####
     * Example: PO-0126-0001 (January 2026)
     */
    public static function generateNextPoNumber(): string
    {
        $monthYear = now()->format('my'); // MMYY format
        $prefix = 'PO-'.$monthYear.'-';
        $last = static::where('po_number', 'like', $prefix.'%')
            ->orderByDesc('po_number')
            ->value('po_number');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
