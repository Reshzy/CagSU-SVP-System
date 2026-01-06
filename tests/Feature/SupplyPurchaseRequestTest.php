<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplyPurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $supplyOfficer;

    protected User $requester;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'Supply Officer']);
        Role::create(['name' => 'Dean']);

        // Create department
        $this->department = Department::factory()->create([
            'name' => 'Test Department',
            'code' => 'TEST',
            'is_archived' => false,
        ]);

        // Create supply officer
        $supplyPosition = Position::factory()->create(['name' => 'Supply Officer']);
        $this->supplyOfficer = User::factory()->create([
            'position_id' => $supplyPosition->id,
            'is_archived' => false,
        ]);
        $this->supplyOfficer->assignRole('Supply Officer');

        // Create requester
        $this->requester = User::factory()->create([
            'department_id' => $this->department->id,
            'is_archived' => false,
        ]);
        $this->requester->assignRole('Dean');
    }

    public function test_supply_officer_can_view_purchase_requests_index(): void
    {
        $this->actingAs($this->supplyOfficer);

        $response = $this->get(route('supply.purchase-requests.index'));

        $response->assertStatus(200);
        $response->assertViewIs('supply.purchase_requests.index');
    }

    public function test_supply_officer_can_view_purchase_request_details(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'submitted',
        ]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->get(route('supply.purchase-requests.show', $pr));

        $response->assertStatus(200);
        $response->assertViewIs('supply.purchase_requests.show');
        $response->assertSee($pr->pr_number);
    }

    public function test_supply_officer_can_start_review(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->put(route('supply.purchase-requests.status', $pr), [
            'action' => 'start_review',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'status' => 'supply_office_review',
            'current_handler_id' => $this->supplyOfficer->id,
        ]);
    }

    public function test_supply_officer_can_activate_pr(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'supply_office_review',
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->put(route('supply.purchase-requests.status', $pr), [
            'action' => 'activate',
            'notes' => 'Approved for budget review',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'status' => 'budget_office_review',
        ]);
    }

    public function test_supply_officer_can_return_pr_to_department(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'supply_office_review',
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->put(route('supply.purchase-requests.status', $pr), [
            'action' => 'return',
            'return_remarks' => 'Missing required documentation',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'status' => 'returned_by_supply',
            'return_remarks' => 'Missing required documentation',
            'returned_by' => $this->supplyOfficer->id,
        ]);
    }

    public function test_supply_officer_can_reject_pr(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'supply_office_review',
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->put(route('supply.purchase-requests.status', $pr), [
            'action' => 'reject',
            'rejection_reason' => 'Not aligned with procurement plan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'status' => 'rejected',
            'rejection_reason' => 'Not aligned with procurement plan',
            'rejected_by' => $this->supplyOfficer->id,
        ]);
    }

    public function test_index_filters_by_status(): void
    {
        PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'submitted',
        ]);

        PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'rejected',
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->get(route('supply.purchase-requests.index', ['status' => 'submitted']));

        $response->assertStatus(200);
        $response->assertSee('submitted');
    }

    public function test_index_searches_by_pr_number(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'pr_number' => 'PR-0126-0001',
            'status' => 'submitted',
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->get(route('supply.purchase-requests.index', ['search' => 'PR-0126-0001']));

        $response->assertStatus(200);
        $response->assertSee('PR-0126-0001');
    }

    public function test_return_requires_remarks(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'supply_office_review',
        ]);

        $this->actingAs($this->supplyOfficer);

        $response = $this->put(route('supply.purchase-requests.status', $pr), [
            'action' => 'return',
            // Missing return_remarks
        ]);

        $response->assertSessionHasErrors('return_remarks');
    }
}
