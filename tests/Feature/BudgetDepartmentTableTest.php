<?php

namespace Tests\Feature;

use App\Livewire\Budget\DepartmentBudgetsTable;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetDepartmentTableTest extends TestCase
{
    use RefreshDatabase;

    protected User $budgetOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Budget Office']);

        $this->budgetOfficer = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
            'is_archived' => false,
        ]);
        $this->budgetOfficer->assignRole('Budget Office');

        $this->actingAs($this->budgetOfficer);
    }

    public function test_budget_departments_table_shows_top_and_bottom_pagination_controls(): void
    {
        $fiscalYear = 2026;

        collect(range(1, 21))->each(function (int $index) use ($fiscalYear): void {
            $department = Department::factory()->create([
                'name' => "Department {$index}",
                'code' => sprintf('D%03d', $index),
            ]);

            DepartmentBudget::create([
                'department_id' => $department->id,
                'fiscal_year' => $fiscalYear,
                'allocated_budget' => 10000,
                'utilized_budget' => 1000,
                'reserved_budget' => 500,
            ]);
        });

        $response = $this->get(route('budget.index', ['fiscal_year' => $fiscalYear]));

        $response->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'aria-label="Pagination Navigation"'));
    }

    public function test_livewire_search_filters_departments_by_name_and_code(): void
    {
        $fiscalYear = 2026;
        $alpha = Department::factory()->create(['name' => 'Alpha Engineering', 'code' => 'AENG']);
        $beta = Department::factory()->create(['name' => 'Beta Procurement', 'code' => 'BPROC']);

        DepartmentBudget::create([
            'department_id' => $alpha->id,
            'fiscal_year' => $fiscalYear,
            'allocated_budget' => 30000,
            'utilized_budget' => 10000,
            'reserved_budget' => 5000,
        ]);
        DepartmentBudget::create([
            'department_id' => $beta->id,
            'fiscal_year' => $fiscalYear,
            'allocated_budget' => 20000,
            'utilized_budget' => 5000,
            'reserved_budget' => 1000,
        ]);

        Livewire::test(DepartmentBudgetsTable::class, ['fiscalYear' => $fiscalYear])
            ->set('search', 'Alpha')
            ->assertSee('Alpha Engineering')
            ->assertDontSee('Beta Procurement')
            ->set('search', 'BPROC')
            ->assertSee('Beta Procurement')
            ->assertDontSee('Alpha Engineering');
    }

    public function test_livewire_sorting_and_toggle_column_behaviors_work(): void
    {
        $fiscalYear = 2026;
        $first = Department::factory()->create(['name' => 'First Department', 'code' => 'FIRST']);
        $second = Department::factory()->create(['name' => 'Second Department', 'code' => 'SECOND']);

        DepartmentBudget::create([
            'department_id' => $first->id,
            'fiscal_year' => $fiscalYear,
            'allocated_budget' => 100000,
            'utilized_budget' => 20000,
            'reserved_budget' => 10000,
        ]);
        DepartmentBudget::create([
            'department_id' => $second->id,
            'fiscal_year' => $fiscalYear,
            'allocated_budget' => 100000,
            'utilized_budget' => 50000,
            'reserved_budget' => 20000,
        ]);

        Livewire::test(DepartmentBudgetsTable::class, ['fiscalYear' => $fiscalYear])
            ->call('sortBy', 'available')
            ->assertSet('sortField', 'available')
            ->assertSet('sortDirection', 'desc')
            ->assertSeeInOrder(['First Department', 'Second Department'])
            ->call('toggleColumn', 'reserved')
            ->assertSet('visibleColumns', ['code', 'allocated', 'utilized', 'available', 'utilization'])
            ->set('visibleColumns', ['code'])
            ->call('toggleColumn', 'code')
            ->assertSet('visibleColumns', ['code']);
    }

    public function test_livewire_uses_selected_fiscal_year_budget_values(): void
    {
        $department = Department::factory()->create([
            'name' => 'Fiscal Test Department',
            'code' => 'FISCAL',
        ]);

        DepartmentBudget::create([
            'department_id' => $department->id,
            'fiscal_year' => 2025,
            'allocated_budget' => 10000,
            'utilized_budget' => 1000,
            'reserved_budget' => 500,
        ]);

        DepartmentBudget::create([
            'department_id' => $department->id,
            'fiscal_year' => 2026,
            'allocated_budget' => 25000,
            'utilized_budget' => 2000,
            'reserved_budget' => 1000,
        ]);

        Livewire::test(DepartmentBudgetsTable::class, ['fiscalYear' => 2025])
            ->assertSee('₱10,000.00')
            ->assertDontSee('₱25,000.00');

        Livewire::test(DepartmentBudgetsTable::class, ['fiscalYear' => 2026])
            ->assertSee('₱25,000.00')
            ->assertDontSee('₱10,000.00');
    }
}
