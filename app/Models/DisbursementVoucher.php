<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementVoucher extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'voucher_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Generate next voucher number in format: DV-MMYY-####
     * Example: DV-0126-0001 (January 2026)
     */
    public static function generateNextVoucherNumber(): string
    {
        $monthYear = now()->format('my'); // MMYY format
        $prefix = 'DV-'.$monthYear.'-';
        $last = static::where('voucher_number', 'like', $prefix.'%')
            ->orderByDesc('voucher_number')
            ->value('voucher_number');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
