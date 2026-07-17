<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartmentBudget>
 */
class DepartmentBudgetFactory extends Factory
{
    protected $model = DepartmentBudget::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'fiscal_year' => (int) date('Y'),
            'allocated_budget' => fake()->randomFloat(2, 1000, 100000),
            'utilized_budget' => 0,
            'reserved_budget' => 0,
            'notes' => null,
            'set_by' => null,
        ];
    }
}
