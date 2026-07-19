<?php

namespace Tests\Feature\DevTools;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DevToolsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('System Admin');
        Role::findOrCreate('End User');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        config(['dev-tools.enabled' => true]);

        $this->get(route('dev-tools.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_receives_forbidden_when_enabled(): void
    {
        config(['dev-tools.enabled' => true]);

        $user = User::factory()->create();
        $user->assignRole('End User');

        $this->actingAs($user)
            ->get(route('dev-tools.index'))
            ->assertForbidden();
    }

    public function test_admin_receives_not_found_when_disabled(): void
    {
        config(['dev-tools.enabled' => false]);

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $this->actingAs($admin)
            ->get(route('dev-tools.index'))
            ->assertNotFound();
    }

    public function test_admin_can_access_when_enabled(): void
    {
        config(['dev-tools.enabled' => true]);

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $this->actingAs($admin)
            ->get(route('dev-tools.index'))
            ->assertOk()
            ->assertSee('Dev Tools', false)
            ->assertSee('testing overrides', false);
    }
}
