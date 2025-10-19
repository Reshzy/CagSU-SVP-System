<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'fiscal_year',
        'allocated_budget',
        'utilized_budget',
        'reserved_budget',
        'notes',
        'set_by',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'allocated_budget' => 'decimal:2',
        'utilized_budget' => 'decimal:2',
        'reserved_budget' => 'decimal:2',
    ];

    /**
     * Get the department that owns this budget
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user (budget officer) who set this budget
     */
    public function setBudgetBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * Calculate available budget
     * Available = Allocated - Utilized - Reserved
     */
    public function getAvailableBudget(): float
    {
        return (float) ($this->allocated_budget - $this->utilized_budget - $this->reserved_budget);
    }

    /**
     * Reserve budget for a pending PR
     */
    public function reserveBudget(float $amount): bool
    {
        if ($this->getAvailableBudget() < $amount) {
            return false;
        }

        $this->reserved_budget += $amount;
        return $this->save();
    }

    /**
     * Utilize budget when PR is completed
     * Moves from reserved to utilized
     */
    public function utilizeBudget(float $amount): bool
    {
        $this->reserved_budget -= $amount;
        $this->utilized_budget += $amount;

        // Ensure values don't go negative
        $this->reserved_budget = max(0, $this->reserved_budget);

        return $this->save();
    }

    /**
     * Release reserved budget when PR is cancelled/rejected
     */
    public function releaseReservedBudget(float $amount): bool
    {
        $this->reserved_budget -= $amount;

        // Ensure reserved budget doesn't go negative
        $this->reserved_budget = max(0, $this->reserved_budget);

        return $this->save();
    }

    /**
     * Get or create budget for a department and fiscal year
     */
    public static function getOrCreateForDepartment(int $departmentId, int $fiscalYear): self
    {
        return static::firstOrCreate(
            [
                'department_id' => $departmentId,
                'fiscal_year' => $fiscalYear,
            ],
            [
                'allocated_budget' => 0,
                'utilized_budget' => 0,
                'reserved_budget' => 0,
            ]
        );
    }

    /**
     * Check if amount can be reserved
     */
    public function canReserve(float $amount): bool
    {
        return $this->getAvailableBudget() >= $amount;
    }

    /**
     * Get budget utilization percentage
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->allocated_budget <= 0) {
            return 0;
        }

        return ($this->utilized_budget / $this->allocated_budget) * 100;
    }

    /**
     * Get total committed budget (utilized + reserved)
     */
    public function getCommittedBudget(): float
    {
        return (float) ($this->utilized_budget + $this->reserved_budget);
    }
}
