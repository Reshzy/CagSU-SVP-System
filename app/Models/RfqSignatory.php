<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqSignatory extends Model
{
    protected $fillable = [
        'rfq_generation_id',
        'position',
        'user_id',
        'name',
        'prefix',
        'suffix',
    ];

    /**
     * Get the RFQ generation associated with this signatory
     */
    public function rfqGeneration(): BelongsTo
    {
        return $this->belongsTo(RfqGeneration::class);
    }

    /**
     * Get the user associated with this signatory (if selected from list)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted full name with prefix and suffix
     */
    public function getFullNameAttribute(): string
    {
        // Use manual name if provided, otherwise use user's name
        $name = $this->name ?? ($this->user ? $this->user->name : 'N/A');

        if ($this->prefix) {
            $name = $this->prefix.' '.$name;
        }

        if ($this->suffix) {
            $name .= ', '.$this->suffix;
        }

        return $name;
    }

    /**
     * Get the display name (name only without prefix/suffix)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? ($this->user ? $this->user->name : 'N/A');
    }

    /**
     * Get human-readable position name
     */
    public function getPositionNameAttribute(): string
    {
        return match ($this->position) {
            'bac_chairperson' => 'BAC Chairperson',
            'canvassing_officer' => 'Canvassing Officer',
            default => $this->position,
        };
    }
}
