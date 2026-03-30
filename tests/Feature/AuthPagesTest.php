<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    // ─── Login page ─────────────────────────────────────────────────────────────

    public function test_login_page_loads_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Log in');
    }

    public function test_login_page_shows_register_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Register');
    }

    public function test_login_page_shows_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Forgot your password');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    // ─── Register page ──────────────────────────────────────────────────────────

    public function test_register_page_loads_for_guests(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create an account');
    }

    public function test_register_page_shows_login_link(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Log in');
    }

    public function test_register_page_shows_request_new_department_link(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Request a new department');
    }

    public function test_authenticated_user_is_redirected_away_from_register(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('dashboard'));
    }

    // ─── Registration submission ─────────────────────────────────────────────────

    public function test_user_can_register_and_is_redirected_to_login_pending_approval(): void
    {
        Storage::fake('public');

        $department = Department::factory()->create(['is_active' => true]);
        $position = Position::create(['name' => 'Employee']);

        $this->post(route('register'), [
            'name' => 'Juan dela Cruz',
            'email' => 'juan@cagsu.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'id_proof' => UploadedFile::fake()->create('id.pdf', 500, 'application/pdf'),
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => 'juan@cagsu.edu.ph',
            'approval_status' => 'pending',
            'is_active' => false,
        ]);
    }

    public function test_pending_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@cagsu.edu.ph',
            'password' => bcrypt('password'),
            'approval_status' => 'pending',
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => 'pending@cagsu.edu.ph',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_approved_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'approved@cagsu.edu.ph',
            'password' => bcrypt('password'),
            'approval_status' => 'approved',
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'approved@cagsu.edu.ph',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_registration_requires_id_proof(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $position = Position::create(['name' => 'Employee']);

        $this->post(route('register'), [
            'name' => 'No ID User',
            'email' => 'noid@cagsu.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'department_id' => $department->id,
            'position_id' => $position->id,
        ])->assertSessionHasErrors('id_proof');
    }

    public function test_registration_requires_department(): void
    {
        Storage::fake('public');
        $position = Position::create(['name' => 'Employee']);

        $this->post(route('register'), [
            'name' => 'No Dept User',
            'email' => 'nodept@cagsu.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'position_id' => $position->id,
            'id_proof' => UploadedFile::fake()->create('id.pdf', 500, 'application/pdf'),
        ])->assertSessionHasErrors('department_id');
    }
}
