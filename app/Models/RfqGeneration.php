<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqGeneration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    /**
     * Get the item group that owns this RFQ generation
     */
    public function prItemGroup(): BelongsTo
    {
        return $this->belongsTo(PrItemGroup::class, 'pr_item_group_id');
    }

    /**
     * Get the user who generated this RFQ
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the signatories for this RFQ
     */
    public function rfqSignatories(): HasMany
    {
        return $this->hasMany(RfqSignatory::class);
    }

    /**
     * Generate next RFQ number in format: RFQ-MMYY-####
     * Example: RFQ-0126-0001 (January 2026)
     */
    public static function generateNextRfqNumber(): string
    {
        $monthYear = now()->format('my'); // MMYY format
        $prefix = 'RFQ-'.$monthYear.'-';
        $last = static::where('rfq_number', 'like', $prefix.'%')
            ->orderByDesc('rfq_number')
            ->value('rfq_number');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
