<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Executive Officer']);
    }

    // ─── Guest: view department request form ────────────────────────────────────

    public function test_guest_can_view_request_department_form(): void
    {
        $this->get(route('register.request-department'))
            ->assertOk()
            ->assertSee('Request a New Department');
    }

    public function test_authenticated_user_cannot_access_department_request_form(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('register.request-department'))
            ->assertRedirect(route('dashboard'));
    }

    // ─── Guest: submit department request ───────────────────────────────────────

    public function test_guest_can_submit_a_department_request(): void
    {
        $this->post(route('register.request-department.store'), [
            'name' => 'College of Engineering',
            'code' => 'COE',
            'description' => 'Engineering faculty',
            'head_name' => 'Dr. Maria Santos',
            'contact_email' => 'dean.eng@cagsu.edu.ph',
            'contact_phone' => '+639171234567',
            'requester_email' => 'eng.dean@cagsu.edu.ph',
        ])
            ->assertRedirect(route('register'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('department_requests', [
            'name' => 'College of Engineering',
            'code' => 'COE',
            'head_name' => 'Dr. Maria Santos',
            'contact_email' => 'dean.eng@cagsu.edu.ph',
            'contact_phone' => '+639171234567',
            'requester_email' => 'eng.dean@cagsu.edu.ph',
            'status' => 'pending',
        ]);
    }

    public function test_code_is_stored_uppercased(): void
    {
        $this->post(route('register.request-department.store'), [
            'name' => 'College of Arts',
            'code' => 'coa',
            'head_name' => 'Prof. Anne Reyes',
            'requester_email' => 'arts@cagsu.edu.ph',
        ])->assertRedirect(route('register'));

        $this->assertDatabaseHas('department_requests', ['code' => 'COA']);
    }

    public function test_department_request_requires_name(): void
    {
        $this->post(route('register.request-department.store'), [
            'code' => 'ABC',
            'head_name' => 'Prof. Test Head',
            'requester_email' => 'test@example.com',
        ])->assertSessionHasErrors('name');
    }

    public function test_department_request_requires_head_name(): void
    {
        $this->post(route('register.request-department.store'), [
            'name' => 'Test Dept',
            'code' => 'TST',
            'requester_email' => 'test@example.com',
        ])->assertSessionHasErrors('head_name');
    }

    public function test_department_request_requires_valid_email(): void
    {
        $this->post(route('register.request-department.store'), [
            'name' => 'Test Dept',
            'code' => 'TDT',
            'head_name' => 'Prof. Test Head',
            'requester_email' => 'not-an-email',
        ])->assertSessionHasErrors('requester_email');
    }

    public function test_department_request_accepts_optional_contact_fields(): void
    {
        $this->post(route('register.request-department.store'), [
            'name' => 'College of Tourism',
            'code' => 'COT',
            'head_name' => 'Dr. Leila Abad',
            'requester_email' => 'tourism@cagsu.edu.ph',
        ])->assertRedirect(route('register'));

        $this->assertDatabaseHas('department_requests', [
            'code' => 'COT',
            'head_name' => 'Dr. Leila Abad',
            'contact_email' => null,
            'contact_phone' => null,
        ]);
    }

    public function test_department_request_code_must_be_alphanumeric(): void
    {
        $this->post(route('register.request-department.store'), [
            'name' => 'Test Dept',
            'code' => 'TD-T',
            'head_name' => 'Prof. Test Head',
            'requester_email' => 'test@example.com',
        ])->assertSessionHasErrors('code');
    }

    public function test_department_request_code_must_be_unique_across_departments(): void
    {
        Department::factory()->create(['name' => 'Existing Dept', 'code' => 'EXD']);

        $this->post(route('register.request-department.store'), [
            'name' => 'New Dept',
            'code' => 'EXD',
            'head_name' => 'Prof. Test Head',
            'requester_email' => 'test@example.com',
        ])->assertSessionHasErrors('code');
    }

    public function test_department_request_code_must_be_unique_across_existing_requests(): void
    {
        DepartmentRequest::factory()->create(['code' => 'DUP']);

        $this->post(route('register.request-department.store'), [
            'name' => 'Another Dept',
            'code' => 'DUP',
            'head_name' => 'Prof. Test Head',
            'requester_email' => 'other@example.com',
        ])->assertSessionHasErrors('code');
    }

    // ─── CEO: view department requests list ─────────────────────────────────────

    public function test_executive_officer_can_view_department_requests_list(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        DepartmentRequest::factory()->count(3)->pending()->create();

        $this->actingAs($ceo)
            ->get(route('ceo.department-requests.index'))
            ->assertOk()
            ->assertSee('Department Requests');
    }

    public function test_non_executive_cannot_access_ceo_department_requests(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        Role::create(['name' => 'End User']);
        $user->assignRole('End User');

        $this->actingAs($user)
            ->get(route('ceo.department-requests.index'))
            ->assertForbidden();
    }

    // ─── CEO: view individual department request ─────────────────────────────────

    public function test_executive_officer_can_view_a_department_request(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        $dr = DepartmentRequest::factory()->pending()->create(['name' => 'College of Science']);

        $this->actingAs($ceo)
            ->get(route('ceo.department-requests.show', $dr))
            ->assertOk()
            ->assertSee('College of Science');
    }

    // ─── CEO: approve department request ────────────────────────────────────────

    public function test_executive_officer_can_approve_a_department_request(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        $dr = DepartmentRequest::factory()->pending()->create([
            'name' => 'College of Nursing',
            'code' => 'CON',
            'description' => 'Nursing faculty',
            'head_name' => 'Dr. Elena Cruz',
            'contact_email' => 'nursing.head@cagsu.edu.ph',
            'contact_phone' => '+639185551111',
        ]);

        $this->actingAs($ceo)
            ->post(route('ceo.department-requests.approve', $dr))
            ->assertRedirect(route('ceo.department-requests.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('department_requests', [
            'id' => $dr->id,
            'status' => 'approved',
            'reviewed_by' => $ceo->id,
        ]);

        $this->assertDatabaseHas('departments', [
            'name' => 'College of Nursing',
            'code' => 'CON',
            'head_name' => 'Dr. Elena Cruz',
            'contact_email' => 'nursing.head@cagsu.edu.ph',
            'contact_phone' => '+639185551111',
        ]);
    }

    public function test_approving_creates_a_department_with_correct_data(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        $dr = DepartmentRequest::factory()->pending()->create([
            'name' => 'College of Law',
            'code' => 'col',
            'description' => 'Law school',
        ]);

        $this->actingAs($ceo)->post(route('ceo.department-requests.approve', $dr));

        $dept = Department::where('code', 'COL')->first();
        $this->assertNotNull($dept);
        $this->assertEquals('College of Law', $dept->name);
        $this->assertTrue($dept->is_active);
    }

    public function test_cannot_approve_already_reviewed_request(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        $dr = DepartmentRequest::factory()->approved()->create();

        $this->actingAs($ceo)
            ->post(route('ceo.department-requests.approve', $dr))
            ->assertRedirect(route('ceo.department-requests.index'));

        $this->assertDatabaseMissing('departments', ['name' => $dr->name.$dr->name]);
    }

    public function test_approving_fails_if_department_code_already_exists(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        Department::factory()->create(['name' => 'Already There', 'code' => 'ATH']);

        $dr = DepartmentRequest::factory()->pending()->create([
            'name' => 'Also There',
            'code' => 'ath',
        ]);

        $this->actingAs($ceo)
            ->post(route('ceo.department-requests.approve', $dr))
            ->assertRedirect(route('ceo.department-requests.show', $dr))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('department_requests', ['id' => $dr->id, 'status' => 'pending']);
    }

    // ─── CEO: reject department request ─────────────────────────────────────────

    public function test_executive_officer_can_reject_a_department_request(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        $dr = DepartmentRequest::factory()->pending()->create(['name' => 'Duplicate College']);

        $this->actingAs($ceo)
            ->post(route('ceo.department-requests.reject', $dr), [
                'rejection_reason' => 'This department already exists under a different name.',
            ])
            ->assertRedirect(route('ceo.department-requests.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('department_requests', [
            'id' => $dr->id,
            'status' => 'rejected',
            'rejection_reason' => 'This department already exists under a different name.',
            'reviewed_by' => $ceo->id,
        ]);

        $this->assertDatabaseMissing('departments', ['name' => 'Duplicate College']);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $ceo = User::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $ceo->assignRole('Executive Officer');

        $dr = DepartmentRequest::factory()->pending()->create();

        $this->actingAs($ceo)
            ->post(route('ceo.department-requests.reject', $dr), [
                'rejection_reason' => '',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertDatabaseHas('department_requests', ['id' => $dr->id, 'status' => 'pending']);
    }

    // ─── Register dropdown: only approved departments shown ─────────────────────

    public function test_register_form_shows_only_approved_departments(): void
    {
        $approved = Department::factory()->create(['name' => 'Approved Dept', 'code' => 'APD', 'is_active' => true]);

        // A pending request should NOT appear in the register dropdown
        DepartmentRequest::factory()->pending()->create(['name' => 'Pending Dept']);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Approved Dept')
            ->assertDontSee('Pending Dept');
    }
}
