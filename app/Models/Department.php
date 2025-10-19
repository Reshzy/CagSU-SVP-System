<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'head_name',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for the department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get active users for the department.
     */
    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }

    /**
     * Get purchase requests from this department.
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    /**
     * Scope a query to only include active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get department budgets
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(DepartmentBudget::class);
    }

    /**
     * Get current fiscal year budget
     */
    public function currentBudget()
    {
        return $this->hasOne(DepartmentBudget::class)
            ->where('fiscal_year', date('Y'));
    }

    /**
     * Get budget for a specific fiscal year
     */
    public function getBudgetForYear(int $fiscalYear)
    {
        return DepartmentBudget::getOrCreateForDepartment($this->id, $fiscalYear);
    }
}
