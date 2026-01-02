<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Ppmp;

class PpmpBudgetValidator
{
    /**
     * Validate PPMP against allocated budget
     */
    public function validatePpmpAgainstBudget(Ppmp $ppmp): bool
    {
        $budget = $this->getAvailableBudget($ppmp->department, $ppmp->fiscal_year);
        
        return $ppmp->total_estimated_cost <= $budget;
    }

    /**
     * Calculate total cost from items array
     */
    public function calculatePpmpTotal(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $quantity = ($item['q1_quantity'] ?? 0) + 
                       ($item['q2_quantity'] ?? 0) + 
                       ($item['q3_quantity'] ?? 0) + 
                       ($item['q4_quantity'] ?? 0);
            
            $unitCost = $item['estimated_unit_cost'] ?? 0;
            
            $total += $quantity * $unitCost;
        }

        return $total;
    }

    /**
     * Get available budget for a department in a fiscal year
     */
    public function getAvailableBudget(Department $department, int $fiscalYear): float
    {
        $budget = DepartmentBudget::getOrCreateForDepartment($department->id, $fiscalYear);
        
        return $budget->getAvailableBudget();
    }

    /**
     * Check if adding items would exceed budget
     */
    public function wouldExceedBudget(Ppmp $ppmp, float $additionalCost): bool
    {
        $budget = $this->getAvailableBudget($ppmp->department, $ppmp->fiscal_year);
        $newTotal = $ppmp->total_estimated_cost + $additionalCost;
        
        return $newTotal > $budget;
    }

    /**
     * Get budget status information
     */
    public function getBudgetStatus(Ppmp $ppmp): array
    {
        $departmentBudget = DepartmentBudget::getOrCreateForDepartment(
            $ppmp->department_id,
            $ppmp->fiscal_year
        );

        $allocated = (float) $departmentBudget->allocated_budget;
        $planned = (float) $ppmp->total_estimated_cost;
        $available = $departmentBudget->getAvailableBudget();
        $utilized = (float) $departmentBudget->utilized_budget;
        $reserved = (float) $departmentBudget->reserved_budget;

        return [
            'allocated' => $allocated,
            'planned' => $planned,
            'available' => $available,
            'utilized' => $utilized,
            'reserved' => $reserved,
            'remaining_after_ppmp' => $available - $planned,
            'is_within_budget' => $planned <= $allocated,
            'utilization_percentage' => $allocated > 0 ? ($planned / $allocated) * 100 : 0,
        ];
    }
}

