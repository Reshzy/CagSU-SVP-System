<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year',
        'category',
        'item_code',
        'item_name',
        'unit_of_measure',
        'unit_price',
        'specifications',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get PPMP items that reference this APP item
     */
    public function ppmpItems(): HasMany
    {
        return $this->hasMany(PpmpItem::class);
    }

    /**
     * Scope to filter only active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by fiscal year
     */
    public function scopeForFiscalYear($query, int $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
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
     * Get all unique categories for a fiscal year
     */
    public static function getCategories(?int $fiscalYear = null): \Illuminate\Support\Collection
    {
        $query = static::active();
        
        if ($fiscalYear) {
            $query->forFiscalYear($fiscalYear);
        }
        
        return $query
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }
}
