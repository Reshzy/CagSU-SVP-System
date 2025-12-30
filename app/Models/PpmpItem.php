<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PpmpItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'college_id',
        'category',
        'item_code',
        'item_name',
        'unit_of_measure',
        'unit_price',
        'specifications',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the college that owns the PPMP item
     */
    public function college()
    {
        return $this->belongsTo(Department::class, 'college_id');
    }

    /**
     * Get purchase request items that use this PPMP item
     */
    public function purchaseRequestItems()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    /**
     * Scope to filter by college
     */
    public function scopeForCollege($query, int $collegeId)
    {
        return $query->where('college_id', $collegeId);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter only active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search items by name or code
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('item_name', 'like', "%{$search}%")
                ->orWhere('item_code', 'like', "%{$search}%");
        });
    }

    /**
     * Get all unique categories
     */
    public static function getCategories()
    {
        return static::active()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }
}
