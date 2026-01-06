<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'visible_to_roles' => 'array',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Generate next sequential document number in format: DOC-MMYY-####
     * Example: DOC-0126-0001 (January 2026)
     */
    public static function generateNextDocumentNumber(?\Illuminate\Support\Carbon $asOf = null): string
    {
        $asOf = $asOf ?: now();
        $monthYear = $asOf->format('my'); // MMYY format
        $prefix = 'DOC-'.$monthYear.'-';
        $last = static::where('document_number', 'like', $prefix.'%')
            ->orderByDesc('document_number')
            ->value('document_number');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
