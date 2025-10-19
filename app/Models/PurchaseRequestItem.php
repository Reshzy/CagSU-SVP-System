<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the PPMP item associated with this PR item
     */
    public function ppmpItem()
    {
        return $this->belongsTo(PpmpItem::class);
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
}
