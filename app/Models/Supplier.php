<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public static function generateSupplierCode(): string
    {
        $prefix = 'SUP-' . now()->year . '-';
        $last = static::where('supplier_code', 'like', $prefix . '%')
            ->orderByDesc('supplier_code')
            ->value('supplier_code');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
}


