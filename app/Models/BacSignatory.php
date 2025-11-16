<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacSignatory extends Model
{
    protected $fillable = [
        'user_id',
        'manual_name',
        'position',
        'prefix',
        'suffix',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user associated with this signatory
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active signatories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get signatories by position
     */
    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Get the display name (without titles)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->manual_name ?? ($this->user ? $this->user->name : 'N/A');
    }

    /**
     * Get formatted full name with prefix and suffix
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->display_name;
        
        if ($this->prefix) {
            $name = $this->prefix . ' ' . $name;
        }
        
        if ($this->suffix) {
            $name .= ', ' . $this->suffix;
        }
        
        return $name;
    }

    /**
     * Get human-readable position name
     */
    public function getPositionNameAttribute(): string
    {
        return match($this->position) {
            'bac_chairman' => 'BAC Chairman',
            'bac_vice_chairman' => 'BAC Vice Chairman',
            'bac_member' => 'BAC Member',
            'head_bac_secretariat' => 'Head, BAC Secretariat',
            'ceo' => 'CEO',
            'canvassing_officer' => 'Canvassing Officer',
            default => $this->position,
        };
    }
}
