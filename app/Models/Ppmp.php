<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ppmp extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'fiscal_year',
        'status',
        'total_estimated_cost',
        'validated_at',
        'validated_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'total_estimated_cost' => 'decimal:2',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * Get the department that owns this PPMP
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who validated this PPMP
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Get the items in this PPMP
     */
    public function items(): HasMany
    {
        return $this->hasMany(PpmpItem::class);
    }

    /**
     * Calculate the total cost of all items in this PPMP
     */
    public function calculateTotalCost(): float
    {
        return (float) $this->items()
            ->sum('estimated_total_cost');
    }

    /**
     * Check if this PPMP can be edited
     */
    public function canEdit(): bool
    {
        // Can always edit in this system (per requirements)
        return true;
    }

    /**
     * Check if this PPMP is within budget
     */
    public function isWithinBudget(): bool
    {
        $budget = DepartmentBudget::getOrCreateForDepartment(
            $this->department_id,
            $this->fiscal_year
        );

        return $this->total_estimated_cost <= $budget->allocated_budget;
    }

    /**
     * Validate this PPMP (budget check + mark as validated)
     */
    public function validate(int $userId): bool
    {
        if (!$this->isWithinBudget()) {
            return false;
        }

        $this->status = 'validated';
        $this->validated_at = now();
        $this->validated_by = $userId;

        return $this->save();
    }

    /**
     * Scope to filter by department
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter by fiscal year
     */
    public function scopeForFiscalYear($query, int $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    /**
     * Scope to filter validated PPMPs
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /**
     * Scope to filter draft PPMPs
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get or create PPMP for a department and fiscal year
     */
    public static function getOrCreateForDepartment(int $departmentId, int $fiscalYear): self
    {
        return static::firstOrCreate(
            [
                'department_id' => $departmentId,
                'fiscal_year' => $fiscalYear,
            ],
            [
                'status' => 'draft',
                'total_estimated_cost' => 0,
            ]
        );
    }
}
