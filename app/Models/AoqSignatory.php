<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AoqSignatory extends Model
{
    protected $fillable = [
        'aoq_generation_id',
        'position',
        'user_id',
        'name',
        'prefix',
        'suffix',
    ];

    /**
     * Get the AOQ generation associated with this signatory
     */
    public function aoqGeneration(): BelongsTo
    {
        return $this->belongsTo(AoqGeneration::class);
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
            $name = $this->prefix . ' ' . $name;
        }
        
        if ($this->suffix) {
            $name .= ', ' . $this->suffix;
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
        return match($this->position) {
            'bac_chairman' => 'BAC Chairman',
            'bac_vice_chairman' => 'BAC Vice Chairman',
            'bac_member_1' => 'BAC Member',
            'bac_member_2' => 'BAC Member',
            'bac_member_3' => 'BAC Member',
            'head_bac_secretariat' => 'Head, BAC Secretariat',
            'ceo' => 'Campus Executive Officer',
            default => $this->position,
        };
    }
}
