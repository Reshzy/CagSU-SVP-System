<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetManagementController extends Controller
{
    /**
     * Display all departments with their budget information
     */
    public function index(Request $request)
    {
        $fiscalYear = $request->input('fiscal_year', date('Y'));

        $departments = Department::with(['currentBudget'])
            ->active()
            ->get()
            ->map(function ($department) use ($fiscalYear) {
                $budget = $department->getBudgetForYear($fiscalYear);

                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'allocated_budget' => $budget->allocated_budget,
                    'utilized_budget' => $budget->utilized_budget,
                    'reserved_budget' => $budget->reserved_budget,
                    'available_budget' => $budget->getAvailableBudget(),
                    'utilization_percentage' => $budget->getUtilizationPercentage(),
                ];
            });

        return view('budget.department-budgets', [
            'departments' => $departments,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    /**
     * Show form to set/update budget for a department
     */
    public function edit(Department $department, Request $request)
    {
        $fiscalYear = $request->input('fiscal_year', date('Y'));
        $budget = $department->getBudgetForYear($fiscalYear);

        return view('budget.set-budget', [
            'department' => $department,
            'budget' => $budget,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    /**
     * Update department budget
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'allocated_budget' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $budget = DepartmentBudget::updateOrCreate(
            [
                'department_id' => $department->id,
                'fiscal_year' => $validated['fiscal_year'],
            ],
            [
                'allocated_budget' => $validated['allocated_budget'],
                'notes' => $validated['notes'] ?? null,
                'set_by' => Auth::id(),
            ]
        );

        return redirect()
            ->route('budget.index', ['fiscal_year' => $validated['fiscal_year']])
            ->with('success', "Budget set for {$department->name} for fiscal year {$validated['fiscal_year']}");
    }

    /**
     * View detailed budget information for a department
     */
    public function show(Department $department, Request $request)
    {
        $fiscalYear = $request->input('fiscal_year', date('Y'));
        $budget = $department->getBudgetForYear($fiscalYear);

        // Get all PRs for this department and fiscal year
        $purchaseRequests = $department->purchaseRequests()
            ->whereYear('created_at', $fiscalYear)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pr) {
                return [
                    'pr_number' => $pr->pr_number,
                    'purpose' => $pr->purpose,
                    'status' => $pr->status,
                    'total_cost' => $pr->calculateTotalCost(),
                    'created_at' => $pr->created_at,
                ];
            });

        return view('budget.department-detail', [
            'department' => $department,
            'budget' => $budget,
            'fiscalYear' => $fiscalYear,
            'purchaseRequests' => $purchaseRequests,
        ]);
    }
}
