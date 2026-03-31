<?php

namespace Tests\Feature;

use App\Livewire\Ceo\UsersTable;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CeoUserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Executive Officer']);
    }

    public function test_executive_officer_sees_pagination_controls_above_and_below_the_users_table(): void
    {
        $ceo = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $ceo->assignRole('Executive Officer');

        $department = Department::factory()->create();

        User::factory()->count(21)->create([
            'department_id' => $department->id,
            'approval_status' => 'pending',
            'is_active' => false,
        ]);

        $response = $this->actingAs($ceo)->get(route('ceo.users.index'));

        $response
            ->assertOk()
            ->assertSee('User Management');

        $this->assertSame(
            2,
            substr_count($response->getContent(), 'aria-label="Pagination Navigation"')
        );
    }

    public function test_livewire_search_filters_users_by_name(): void
    {
        $ceo = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $ceo->assignRole('Executive Officer');

        $department = Department::factory()->create();

        User::factory()->create([
            'name' => 'Alpha Manager',
            'email' => 'alpha@example.com',
            'department_id' => $department->id,
        ]);

        User::factory()->create([
            'name' => 'Beta Manager',
            'email' => 'beta@example.com',
            'department_id' => $department->id,
        ]);

        $this->actingAs($ceo);

        Livewire::test(UsersTable::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha Manager')
            ->assertDontSee('Beta Manager');
    }

    public function test_livewire_search_filters_users_by_email(): void
    {
        $ceo = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $ceo->assignRole('Executive Officer');

        $department = Department::factory()->create();

        User::factory()->create([
            'name' => 'Delta User',
            'email' => 'delta@example.com',
            'department_id' => $department->id,
        ]);

        User::factory()->create([
            'name' => 'Gamma User',
            'email' => 'gamma@example.com',
            'department_id' => $department->id,
        ]);

        $this->actingAs($ceo);

        Livewire::test(UsersTable::class)
            ->set('search', 'gamma@')
            ->assertSee('Gamma User')
            ->assertDontSee('Delta User');
    }

    public function test_livewire_search_resets_pagination_to_first_page(): void
    {
        $ceo = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $ceo->assignRole('Executive Officer');

        $department = Department::factory()->create();

        User::factory()->count(25)->create([
            'department_id' => $department->id,
        ]);

        User::factory()->create([
            'name' => 'Zeta Search Match',
            'email' => 'zeta@example.com',
            'department_id' => $department->id,
        ]);

        $this->actingAs($ceo);

        Livewire::test(UsersTable::class)
            ->set('page', 2)
            ->assertSet('page', 2)
            ->set('search', 'Zeta')
            ->assertSet('page', 1)
            ->assertSee('Zeta Search Match');
    }
}
