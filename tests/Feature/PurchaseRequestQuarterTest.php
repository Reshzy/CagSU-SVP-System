<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\User;
use App\Services\PpmpQuarterlyTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PurchaseRequestQuarterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Department $department;

    protected Ppmp $ppmp;

    protected AppItem $appItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test department
        $this->department = Department::factory()->create();

        // Create test user
        $this->user = User::factory()->create([
            'department_id' => $this->department->id,
        ]);

        // Create department budget
        DepartmentBudget::factory()->create([
            'department_id' => $this->department->id,
            'fiscal_year' => date('Y'),
            'allocated_budget' => 1000000,
        ]);

        // Create validated PPMP
        $this->ppmp = Ppmp::factory()->create([
            'department_id' => $this->department->id,
            'fiscal_year' => date('Y'),
            'status' => 'validated',
        ]);

        // Create APP item
        $this->appItem = AppItem::factory()->create([
            'category' => 'Office Supplies',
        ]);
    }

    public function test_can_only_create_pr_from_current_quarter_items(): void
    {
        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();

        // Create PPMP item with quantity only in current quarter
        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $this->ppmp->id,
            'app_item_id' => $this->appItem->id,
            'q1_quantity' => $currentQuarter === 1 ? 10 : 0,
            'q2_quantity' => $currentQuarter === 2 ? 10 : 0,
            'q3_quantity' => $currentQuarter === 3 ? 10 : 0,
            'q4_quantity' => $currentQuarter === 4 ? 10 : 0,
            'total_quantity' => 10,
            'estimated_unit_cost' => 100,
        ]);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'purpose' => 'Test PR',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $this->appItem->item_name,
                    'unit_of_measure' => $this->appItem->unit_of_measure,
                    'quantity_requested' => 5,
                    'estimated_unit_cost' => 100,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.index'));
        $this->assertDatabaseHas('purchase_requests', [
            'requester_id' => $this->user->id,
            'purpose' => 'Test PR',
        ]);
    }

    public function test_cannot_select_past_quarter_items(): void
    {
        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();

        // Create PPMP item with quantity only in a past quarter
        $pastQuarter = max(1, $currentQuarter - 1);
        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $this->ppmp->id,
            'app_item_id' => $this->appItem->id,
            'q1_quantity' => $pastQuarter === 1 ? 10 : 0,
            'q2_quantity' => $pastQuarter === 2 ? 10 : 0,
            'q3_quantity' => $pastQuarter === 3 ? 10 : 0,
            'q4_quantity' => $pastQuarter === 4 ? 10 : 0,
            'total_quantity' => 10,
            'estimated_unit_cost' => 100,
        ]);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'purpose' => 'Test PR',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $this->appItem->item_name,
                    'unit_of_measure' => $this->appItem->unit_of_measure,
                    'quantity_requested' => 5,
                    'estimated_unit_cost' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_cannot_select_future_quarter_items(): void
    {
        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();

        // Create PPMP item with quantity only in a future quarter
        $futureQuarter = min(4, $currentQuarter + 1);
        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $this->ppmp->id,
            'app_item_id' => $this->appItem->id,
            'q1_quantity' => $futureQuarter === 1 ? 10 : 0,
            'q2_quantity' => $futureQuarter === 2 ? 10 : 0,
            'q3_quantity' => $futureQuarter === 3 ? 10 : 0,
            'q4_quantity' => $futureQuarter === 4 ? 10 : 0,
            'total_quantity' => 10,
            'estimated_unit_cost' => 100,
        ]);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'purpose' => 'Test PR',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $this->appItem->item_name,
                    'unit_of_measure' => $this->appItem->unit_of_measure,
                    'quantity_requested' => 5,
                    'estimated_unit_cost' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_quantity_validation_against_quarter_allocation(): void
    {
        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();

        // Create PPMP item with limited quantity in current quarter
        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $this->ppmp->id,
            'app_item_id' => $this->appItem->id,
            'q1_quantity' => $currentQuarter === 1 ? 5 : 0,
            'q2_quantity' => $currentQuarter === 2 ? 5 : 0,
            'q3_quantity' => $currentQuarter === 3 ? 5 : 0,
            'q4_quantity' => $currentQuarter === 4 ? 5 : 0,
            'total_quantity' => 5,
            'estimated_unit_cost' => 100,
        ]);

        // Try to request more than allocated
        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'purpose' => 'Test PR',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $this->appItem->item_name,
                    'unit_of_measure' => $this->appItem->unit_of_measure,
                    'quantity_requested' => 10, // More than allocated 5
                    'estimated_unit_cost' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_remaining_quantity_updates_correctly(): void
    {
        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();

        // Create PPMP item with quantity in current quarter
        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $this->ppmp->id,
            'app_item_id' => $this->appItem->id,
            'q1_quantity' => $currentQuarter === 1 ? 10 : 0,
            'q2_quantity' => $currentQuarter === 2 ? 10 : 0,
            'q3_quantity' => $currentQuarter === 3 ? 10 : 0,
            'q4_quantity' => $currentQuarter === 4 ? 10 : 0,
            'total_quantity' => 10,
            'estimated_unit_cost' => 100,
        ]);

        // Initial remaining quantity should be 10
        $this->assertEquals(10, $ppmpItem->getRemainingQuantity($currentQuarter));

        // Create first PR
        $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'purpose' => 'First PR',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $this->appItem->item_name,
                    'unit_of_measure' => $this->appItem->unit_of_measure,
                    'quantity_requested' => 3,
                    'estimated_unit_cost' => 100,
                ],
            ],
        ]);

        // Refresh the model
        $ppmpItem->refresh();

        // Remaining quantity should now be 7
        $this->assertEquals(7, $ppmpItem->getRemainingQuantity($currentQuarter));

        // Create second PR
        $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'purpose' => 'Second PR',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $this->appItem->item_name,
                    'unit_of_measure' => $this->appItem->unit_of_measure,
                    'quantity_requested' => 4,
                    'estimated_unit_cost' => 100,
                ],
            ],
        ]);

        // Refresh the model
        $ppmpItem->refresh();

        // Remaining quantity should now be 3
        $this->assertEquals(3, $ppmpItem->getRemainingQuantity($currentQuarter));
    }

    public function test_quarter_status_methods_work_correctly(): void
    {
        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();

        // Create PPMP item with quantity in current quarter
        $currentQuarterItem = PpmpItem::factory()->create([
            'ppmp_id' => $this->ppmp->id,
            'app_item_id' => $this->appItem->id,
            'q1_quantity' => $currentQuarter === 1 ? 10 : 0,
            'q2_quantity' => $currentQuarter === 2 ? 10 : 0,
            'q3_quantity' => $currentQuarter === 3 ? 10 : 0,
            'q4_quantity' => $currentQuarter === 4 ? 10 : 0,
            'total_quantity' => 10,
            'estimated_unit_cost' => 100,
        ]);

        $this->assertTrue($currentQuarterItem->isAvailableForCurrentQuarter());
        $this->assertTrue($currentQuarterItem->hasQuantityForQuarter($currentQuarter));
        $this->assertEquals('current', $currentQuarterItem->getQuarterStatus($currentQuarter));
        $this->assertEquals(10, $currentQuarterItem->getRemainingQuantityForCurrentQuarter());
    }
}
