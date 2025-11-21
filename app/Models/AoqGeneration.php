<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AoqGeneration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'exported_data_snapshot' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the purchase request for this AOQ generation
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the user who generated this AOQ
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the signatories for this AOQ
     */
    public function aoqSignatories()
    {
        return $this->hasMany(AoqSignatory::class);
    }

    /**
     * Generate next AOQ reference number
     */
    public static function generateNextReferenceNumber(): string
    {
        $year = now()->year;
        $prefix = 'AOQ-' . $year . '-';
        $last = static::where('aoq_reference_number', 'like', $prefix . '%')
            ->orderByDesc('aoq_reference_number')
            ->value('aoq_reference_number');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate hash of the exported data for tamper detection
     */
    public function calculateHash(): string
    {
        return hash('sha256', json_encode($this->exported_data_snapshot));
    }

    /**
     * Verify document integrity
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->document_hash || !$this->exported_data_snapshot) {
            return false;
        }
        return $this->document_hash === $this->calculateHash();
    }
}
