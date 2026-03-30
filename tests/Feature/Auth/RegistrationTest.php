<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Storage::fake('public');
        $department = Department::factory()->create(['is_active' => true]);
        $position = Position::create(['name' => 'Employee']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'id_proof' => UploadedFile::fake()->create('id.pdf', 500, 'application/pdf'),
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }
}
