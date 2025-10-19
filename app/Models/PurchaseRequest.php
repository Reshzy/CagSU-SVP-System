<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PurchaseRequest extends Model
{
    use HasFactory;

    /**
     * Guard no attributes; we'll validate at controller level.
     */
    protected $guarded = [];

    protected $casts = [
        'date_needed' => 'date',
        'estimated_total' => 'decimal:2',
        'status_updated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function currentHandler()
    {
        return $this->belongsTo(User::class, 'current_handler_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public static function generateNextPrNumber(?Carbon $asOf = null): string
    {
        $asOf = $asOf ?: now();
        $year = $asOf->year;
        $prefix = 'PR-' . $year . '-';
        $last = static::where('pr_number', 'like', $prefix . '%')
            ->orderByDesc('pr_number')
            ->value('pr_number');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix . str_pad((string)$nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total cost of all items in this PR
     */
    public function calculateTotalCost(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->quantity_requested * $item->estimated_unit_cost;
        });
    }

    /**
     * Reserve budget for this PR
     */
    public function reserveDepartmentBudget(): bool
    {
        $fiscalYear = $this->created_at->year;
        $budget = DepartmentBudget::getOrCreateForDepartment($this->department_id, $fiscalYear);

        $totalCost = $this->calculateTotalCost();

        return $budget->reserveBudget($totalCost);
    }

    /**
     * Utilize budget when PR is completed
     */
    public function utilizeDepartmentBudget(): bool
    {
        $fiscalYear = $this->created_at->year;
        $budget = DepartmentBudget::getOrCreateForDepartment($this->department_id, $fiscalYear);

        $totalCost = $this->calculateTotalCost();

        return $budget->utilizeBudget($totalCost);
    }

    /**
     * Release reserved budget when PR is cancelled/rejected
     */
    public function releaseReservedBudget(): bool
    {
        $fiscalYear = $this->created_at->year;
        $budget = DepartmentBudget::getOrCreateForDepartment($this->department_id, $fiscalYear);

        $totalCost = $this->calculateTotalCost();

        return $budget->releaseReservedBudget($totalCost);
    }
}
