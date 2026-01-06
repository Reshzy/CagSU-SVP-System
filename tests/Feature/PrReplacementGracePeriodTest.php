<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PpmpQuarterlyTracker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrReplacementGracePeriodTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;

    protected Department $department;

    protected Ppmp $ppmp;

    protected AppItem $appItem;

    protected PpmpItem $ppmpItem;

    protected PurchaseRequest $returnedPr;

    protected function setUp(): void
    {
        parent::setUp();

        // Create department
        $this->department = Department::factory()->create([
            'name' => 'Test Department',
        ]);

        // Create budget
        DepartmentBudget::factory()->create([
            'department_id' => $this->department->id,
            'fiscal_year' => now()->year,
            'allocated_budget' => 1000000,
        ]);

        // Create requester
        $this->requester = User::factory()->create([
            'department_id' => $this->department->id,
        ]);

        // Create validated PPMP
        $this->ppmp = Ppmp::factory()->create([
            'department_id' => $this->department->id,
            'fiscal_year' => now()->year,
            'status' => 'validated',
        ]);

        // Create APP item
        $this->appItem = AppItem::factory()->create([
            'item_name' => 'Test Item',
            'category' => 'Office Supplies',
            'unit_of_measure' => 'pcs',
        ]);

        // Create PPMP item with Q1 allocation
        $this->ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $this->ppmp->id,
            'app_item_id' => $this->appItem->id,
            'q1_quantity' => 100,
            'q2_quantity' => 0,
            'q3_quantity' => 0,
            'q4_quantity' => 0,
            'total_quantity' => 100,
        ]);

        // Create a returned PR
        $this->returnedPr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'returned_by_supply',
            'return_remarks' => 'Missing documentation',
            'returned_at' => now(),
        ]);
    }

    public function test_grace_period_allows_previous_quarter_items_within_period(): void
    {
        // Set config for 14 day grace period
        config(['ppmp.quarter_grace_period_days' => 14]);
        config(['ppmp.enable_grace_period' => true]);

        // Set current date to April 5 (Q2, within grace period for Q1)
        Carbon::setTestNow(Carbon::create(now()->year, 4, 5));

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // Verify we're in grace period
        $this->assertTrue($quarterlyTracker->isWithinGracePeriod(1));

        // Attempt to create replacement PR with Q1 item
        $response = $this->actingAs($this->requester)->post(
            route('purchase-requests.replacement.store', $this->returnedPr),
            [
                'purpose' => 'Test replacement PR',
                'justification' => 'Testing grace period',
                'items' => [
                    [
                        'ppmp_item_id' => $this->ppmpItem->id,
                        'item_code' => $this->appItem->item_code,
                        'item_name' => $this->appItem->item_name,
                        'detailed_specifications' => 'Test specs',
                        'unit_of_measure' => $this->appItem->unit_of_measure,
                        'quantity_requested' => 10,
                        'estimated_unit_cost' => 100,
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('purchase-requests.index'));
        $response->assertSessionHas('status');

        // Verify PR was created
        $this->assertDatabaseHas('purchase_requests', [
            'requester_id' => $this->requester->id,
            'replaces_pr_id' => $this->returnedPr->id,
            'status' => 'supply_office_review',
        ]);

        Carbon::setTestNow();
    }

    public function test_grace_period_denies_previous_quarter_items_after_period_expires(): void
    {
        // Set config for 14 day grace period
        config(['ppmp.quarter_grace_period_days' => 14]);
        config(['ppmp.enable_grace_period' => true]);

        // Set current date to April 20 (Q2, AFTER grace period for Q1)
        Carbon::setTestNow(Carbon::create(now()->year, 4, 20));

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // Verify we're NOT in grace period
        $this->assertFalse($quarterlyTracker->isWithinGracePeriod(1));

        // Attempt to create replacement PR with Q1 item (should fail)
        $response = $this->actingAs($this->requester)->post(
            route('purchase-requests.replacement.store', $this->returnedPr),
            [
                'purpose' => 'Test replacement PR',
                'justification' => 'Testing grace period expiry',
                'items' => [
                    [
                        'ppmp_item_id' => $this->ppmpItem->id,
                        'item_code' => $this->appItem->item_code,
                        'item_name' => $this->appItem->item_name,
                        'detailed_specifications' => 'Test specs',
                        'unit_of_measure' => $this->appItem->unit_of_measure,
                        'quantity_requested' => 10,
                        'estimated_unit_cost' => 100,
                    ],
                ],
            ]
        );

        $response->assertSessionHasErrors();

        Carbon::setTestNow();
    }

    public function test_regular_prs_cannot_use_grace_period(): void
    {
        // Set config for 14 day grace period
        config(['ppmp.quarter_grace_period_days' => 14]);
        config(['ppmp.enable_grace_period' => true]);

        // Set current date to April 5 (Q2, within grace period for Q1)
        Carbon::setTestNow(Carbon::create(now()->year, 4, 5));

        // Attempt to create regular PR (not replacement) with Q1 item
        $response = $this->actingAs($this->requester)->post(
            route('purchase-requests.store'),
            [
                'purpose' => 'Test regular PR',
                'justification' => 'Testing that regular PRs cannot use grace period',
                'items' => [
                    [
                        'ppmp_item_id' => $this->ppmpItem->id,
                        'item_code' => $this->appItem->item_code,
                        'item_name' => $this->appItem->item_name,
                        'detailed_specifications' => 'Test specs',
                        'unit_of_measure' => $this->appItem->unit_of_measure,
                        'quantity_requested' => 10,
                        'estimated_unit_cost' => 100,
                    ],
                ],
            ]
        );

        // Should fail because regular PRs don't get grace period
        $response->assertSessionHasErrors();

        Carbon::setTestNow();
    }

    public function test_grace_period_calculation_across_quarter_boundaries(): void
    {
        config(['ppmp.quarter_grace_period_days' => 14]);

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // Test Q1 -> Q2 transition
        Carbon::setTestNow(Carbon::create(now()->year, 4, 1)); // First day of Q2
        $this->assertTrue($quarterlyTracker->isWithinGracePeriod(1));

        Carbon::setTestNow(Carbon::create(now()->year, 4, 14)); // Last day of grace period
        $this->assertTrue($quarterlyTracker->isWithinGracePeriod(1));

        Carbon::setTestNow(Carbon::create(now()->year, 4, 15)); // Day after grace period
        $this->assertFalse($quarterlyTracker->isWithinGracePeriod(1));

        Carbon::setTestNow();
    }

    public function test_grace_period_end_date_calculation(): void
    {
        config(['ppmp.quarter_grace_period_days' => 14]);

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // Q1 ends March 31, grace period should end April 14
        $endDate = $quarterlyTracker->getGracePeriodEndDate(1, now()->year);
        $this->assertEquals(4, $endDate->month);
        $this->assertEquals(14, $endDate->day);

        // Q2 ends June 30, grace period should end July 14
        $endDate = $quarterlyTracker->getGracePeriodEndDate(2, now()->year);
        $this->assertEquals(7, $endDate->month);
        $this->assertEquals(14, $endDate->day);
    }

    public function test_available_quarters_for_replacement_includes_grace_period(): void
    {
        config(['ppmp.quarter_grace_period_days' => 14]);

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // Within grace period - should return [1, 2] (Q1 + Q2)
        Carbon::setTestNow(Carbon::create(now()->year, 4, 5));
        $availableQuarters = $quarterlyTracker->getAvailableQuartersForReplacement();
        $this->assertContains(1, $availableQuarters);
        $this->assertContains(2, $availableQuarters);

        // After grace period - should return only [2] (Q2)
        Carbon::setTestNow(Carbon::create(now()->year, 4, 20));
        $availableQuarters = $quarterlyTracker->getAvailableQuartersForReplacement();
        $this->assertNotContains(1, $availableQuarters);
        $this->assertContains(2, $availableQuarters);

        Carbon::setTestNow();
    }

    public function test_grace_period_info_returns_correct_data(): void
    {
        config(['ppmp.quarter_grace_period_days' => 14]);
        config(['ppmp.enable_grace_period' => true]);

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // Within grace period
        Carbon::setTestNow(Carbon::create(now()->year, 4, 5));
        $info = $quarterlyTracker->getGracePeriodInfo();

        $this->assertNotNull($info);
        $this->assertTrue($info['active']);
        $this->assertEquals(1, $info['quarter']);
        $this->assertEquals('January to March', $info['quarter_label']);
        $this->assertGreaterThan(0, $info['days_remaining']);

        // After grace period
        Carbon::setTestNow(Carbon::create(now()->year, 4, 20));
        $info = $quarterlyTracker->getGracePeriodInfo();
        $this->assertNull($info);

        Carbon::setTestNow();
    }

    public function test_grace_period_can_be_disabled_via_config(): void
    {
        config(['ppmp.enable_grace_period' => false]);

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // Even within what would be grace period, it should return false
        Carbon::setTestNow(Carbon::create(now()->year, 4, 5));
        $this->assertFalse($quarterlyTracker->isWithinGracePeriod(1));

        $info = $quarterlyTracker->getGracePeriodInfo();
        $this->assertNull($info);

        Carbon::setTestNow();
    }

    public function test_pr_quarter_is_automatically_set_on_creation(): void
    {
        // Set to Q2
        Carbon::setTestNow(Carbon::create(now()->year, 5, 15));

        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
        ]);

        $this->assertEquals(2, $pr->pr_quarter);

        Carbon::setTestNow();
    }

    public function test_grace_period_expiring_soon_flag(): void
    {
        config(['ppmp.quarter_grace_period_days' => 14]);

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);

        // 2 days before expiry - should be expiring soon
        Carbon::setTestNow(Carbon::create(now()->year, 4, 12));
        $info = $quarterlyTracker->getGracePeriodInfo();
        $this->assertTrue($info['expiring_soon']);

        // 5 days before expiry - should NOT be expiring soon
        Carbon::setTestNow(Carbon::create(now()->year, 4, 9));
        $info = $quarterlyTracker->getGracePeriodInfo();
        $this->assertFalse($info['expiring_soon']);

        Carbon::setTestNow();
    }
}
