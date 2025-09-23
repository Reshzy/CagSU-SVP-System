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
     * Generate next sequential document number like DOC-2025-0001
     */
    public static function generateNextDocumentNumber(?\Illuminate\Support\Carbon $asOf = null): string
    {
        $asOf = $asOf ?: now();
        $year = $asOf->year;
        $prefix = 'DOC-' . $year . '-';
        $last = static::where('document_number', 'like', $prefix . '%')
            ->orderByDesc('document_number')
            ->value('document_number');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
}


