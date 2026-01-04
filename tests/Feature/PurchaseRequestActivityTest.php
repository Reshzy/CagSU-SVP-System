<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestActivity;
use App\Models\User;
use App\Services\PurchaseRequestActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestActivityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary positions
        Position::factory()->create(['name' => 'Supply Officer']);
        Position::factory()->create(['name' => 'Dean']);

        // Create department and user
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => Position::where('name', 'Dean')->first()->id,
        ]);
    }

    public function test_activity_is_logged_when_pr_is_created(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => 'draft',
        ]);

        // Check that creation activity was logged
        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $pr->id,
            'action' => 'created',
        ]);
    }

    public function test_activity_is_logged_when_pr_status_changes(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => 'draft',
        ]);

        // Change status
        $pr->update(['status' => 'supply_office_review']);

        // Check that status change activity was logged
        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $pr->id,
            'action' => 'status_changed',
        ]);

        // Verify old and new values were recorded
        $activity = PurchaseRequestActivity::where('purchase_request_id', $pr->id)
            ->where('action', 'status_changed')
            ->first();

        $this->assertEquals('draft', $activity->old_value['status']);
        $this->assertEquals('supply_office_review', $activity->new_value['status']);
    }

    public function test_activity_logger_logs_status_change(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logStatusChange($pr, 'draft', 'submitted', $this->user->id);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $pr->id,
            'action' => 'status_changed',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_activity_logger_logs_return(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logReturn($pr, 'Please revise the quantities', $this->user->id);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $pr->id)
            ->where('action', 'returned')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Please revise the quantities', $activity->new_value['return_remarks']);
    }

    public function test_purchase_request_can_be_viewed_with_activities(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('purchase-requests.show', $pr));

        $response->assertStatus(200);
        $response->assertSee('Activity Timeline');
    }

    public function test_returned_prs_are_displayed_prominently_on_index(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => 'returned_by_supply',
            'return_remarks' => 'Please add more details',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('purchase-requests.index'));

        $response->assertStatus(200);
        $response->assertSee('Action Required: Returned Purchase Requests');
        $response->assertSee('Please add more details');
        $response->assertSee('Create Replacement PR');
    }

    public function test_replacement_pr_creation_route_exists(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => 'returned_by_supply',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('purchase-requests.replacement.create', $pr));

        $response->assertStatus(200);
    }

    public function test_activity_model_has_correct_relationships(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $activity = PurchaseRequestActivity::create([
            'purchase_request_id' => $pr->id,
            'user_id' => $this->user->id,
            'action' => 'created',
            'description' => 'Purchase request created',
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(PurchaseRequest::class, $activity->purchaseRequest);
        $this->assertInstanceOf(User::class, $activity->user);
        $this->assertEquals($pr->id, $activity->purchaseRequest->id);
        $this->assertEquals($this->user->id, $activity->user->id);
    }

    public function test_purchase_request_has_activities_relationship(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        // Activities should have been auto-created by observer
        $this->assertTrue($pr->activities()->exists());
        $this->assertGreaterThan(0, $pr->activities->count());
    }

    public function test_activity_icons_and_colors_are_defined(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $activity = $pr->activities()->first();

        $this->assertNotEmpty($activity->icon);
        $this->assertNotEmpty($activity->color_class);
        $this->assertStringContainsString('svg', $activity->icon);
    }
}
