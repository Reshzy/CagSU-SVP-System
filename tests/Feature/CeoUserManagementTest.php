<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
