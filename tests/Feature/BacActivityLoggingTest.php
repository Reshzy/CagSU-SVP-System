<?php

namespace Tests\Feature;

use App\Models\AoqGeneration;
use App\Models\Department;
use App\Models\Position;
use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestActivity;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseRequestActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    protected PurchaseRequest $pr;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary positions
        Position::factory()->create(['name' => 'Supply Officer']);
        Position::factory()->create(['name' => 'Dean']);
        Position::factory()->create(['name' => 'BAC Chair']);

        // Create department and user
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => Position::where('name', 'BAC Chair')->first()->id,
        ]);

        // Create a PR in BAC evaluation status
        $this->pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => 'bac_evaluation',
            'resolution_number' => 'RES-0126-0001',
            'procurement_method' => 'small_value_procurement',
        ]);
    }

    public function test_resolution_generated_activity_is_logged(): void
    {
        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logResolutionGenerated($this->pr, 'RES-0126-0001');

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'resolution_generated',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'resolution_generated')
            ->first();

        $this->assertEquals('RES-0126-0001', $activity->new_value['resolution_number']);
        $this->assertStringContainsString('BAC Resolution generated', $activity->description);
    }

    public function test_resolution_regenerated_activity_is_logged(): void
    {
        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logResolutionRegenerated($this->pr, 'RES-0126-0001');

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'resolution_regenerated',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'resolution_regenerated')
            ->first();

        $this->assertStringContainsString('regenerated', $activity->description);
    }

    public function test_rfq_generated_activity_is_logged_without_group(): void
    {
        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logRfqGenerated($this->pr);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'rfq_generated',
            'pr_item_group_id' => null,
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'rfq_generated')
            ->first();

        $this->assertStringContainsString('Request for Quotation (RFQ) generated', $activity->description);
    }

    public function test_rfq_generated_activity_is_logged_with_group(): void
    {
        $group = PrItemGroup::create([
            'purchase_request_id' => $this->pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logRfqGenerated($this->pr, $group);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'rfq_generated',
            'pr_item_group_id' => $group->id,
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'rfq_generated')
            ->first();

        $this->assertEquals('G1', $activity->new_value['group_code']);
        $this->assertEquals('Office Supplies', $activity->new_value['group_name']);
        $this->assertStringContainsString('G1', $activity->description);
        $this->assertStringContainsString('Office Supplies', $activity->description);
    }

    public function test_quotation_submitted_activity_is_logged(): void
    {
        $supplier = Supplier::factory()->create([
            'business_name' => 'ABC Supplier',
            'status' => 'active',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-2026-0001',
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $supplier->id,
            'quotation_date' => now(),
            'validity_date' => now()->addDays(10),
            'total_amount' => 10000,
            'exceeds_abc' => false,
            'bac_status' => 'pending_evaluation',
        ]);

        $quotation->load('supplier', 'prItemGroup');

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logQuotationSubmitted($this->pr, $quotation);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'quotation_submitted',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'quotation_submitted')
            ->first();

        $this->assertEquals('ABC Supplier', $activity->new_value['supplier_name']);
        $this->assertStringContainsString('ABC Supplier', $activity->description);
    }

    public function test_quotation_evaluated_activity_is_logged(): void
    {
        $supplier = Supplier::factory()->create([
            'business_name' => 'XYZ Supplier',
            'status' => 'active',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-2026-0002',
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $supplier->id,
            'quotation_date' => now(),
            'validity_date' => now()->addDays(10),
            'total_amount' => 8000,
            'exceeds_abc' => false,
            'bac_status' => 'compliant',
        ]);

        $quotation->load('supplier', 'prItemGroup', 'purchaseRequest');

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logQuotationEvaluated($this->pr, $quotation, 'responsive');

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'quotation_evaluated',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'quotation_evaluated')
            ->first();

        $this->assertEquals('responsive', $activity->new_value['evaluation_status']);
        $this->assertStringContainsString('Responsive', $activity->description);
    }

    public function test_aoq_generated_activity_is_logged(): void
    {
        $aoqGeneration = AoqGeneration::create([
            'purchase_request_id' => $this->pr->id,
            'aoq_reference_number' => 'AOQ-0126-0001',
            'file_path' => 'aoq_documents/test.docx',
            'generated_by' => $this->user->id,
            'generated_at' => now(),
        ]);

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logAoqGenerated($this->pr, $aoqGeneration);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'aoq_generated',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'aoq_generated')
            ->first();

        $this->assertEquals('AOQ-0126-0001', $activity->new_value['reference_number']);
        $this->assertStringContainsString('AOQ-0126-0001', $activity->description);
    }

    public function test_aoq_generated_activity_with_group_is_logged(): void
    {
        $group = PrItemGroup::create([
            'purchase_request_id' => $this->pr->id,
            'group_name' => 'IT Equipment',
            'group_code' => 'G2',
            'display_order' => 2,
        ]);

        $aoqGeneration = AoqGeneration::create([
            'purchase_request_id' => $this->pr->id,
            'pr_item_group_id' => $group->id,
            'aoq_reference_number' => 'AOQ-0126-0002',
            'file_path' => 'aoq_documents/test_g2.docx',
            'generated_by' => $this->user->id,
            'generated_at' => now(),
        ]);

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logAoqGenerated($this->pr, $aoqGeneration, $group);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'aoq_generated')
            ->first();

        $this->assertEquals($group->id, $activity->pr_item_group_id);
        $this->assertEquals('G2', $activity->new_value['group_code']);
        $this->assertStringContainsString('G2', $activity->description);
        $this->assertStringContainsString('IT Equipment', $activity->description);
    }

    public function test_tie_resolved_activity_is_logged(): void
    {
        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logTieResolved($this->pr, [
            [
                'item_id' => 1,
                'item_description' => 'Ballpoint Pens',
                'winner_supplier_id' => 1,
                'winner_supplier_name' => 'ABC Supplier',
            ],
        ]);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'tie_resolved',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'tie_resolved')
            ->first();

        $this->assertEquals(1, $activity->new_value['item_count']);
        $this->assertStringContainsString('1 item(s)', $activity->description);
    }

    public function test_bac_override_activity_is_logged(): void
    {
        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logBacOverride($this->pr, [
            [
                'item_id' => 1,
                'item_description' => 'Office Chair',
                'original_winner' => 'XYZ Supplier',
                'new_winner' => 'ABC Supplier',
                'reason' => 'Better quality product',
            ],
        ]);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'bac_override',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'bac_override')
            ->first();

        $this->assertStringContainsString('BAC override applied', $activity->description);
    }

    public function test_supplier_withdrawal_activity_is_logged(): void
    {
        $supplier = Supplier::factory()->create([
            'business_name' => 'Withdrawing Supplier',
            'status' => 'active',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-2026-0003',
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $supplier->id,
            'quotation_date' => now(),
            'validity_date' => now()->addDays(10),
            'total_amount' => 15000,
            'exceeds_abc' => false,
            'bac_status' => 'awarded',
        ]);

        $quotation->load('supplier', 'prItemGroup', 'purchaseRequest');

        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logSupplierWithdrawal($this->pr, $quotation, 'Unable to fulfill order', 'Second Best Supplier');

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'supplier_withdrawal',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'supplier_withdrawal')
            ->first();

        $this->assertEquals('Withdrawing Supplier', $activity->new_value['supplier_name']);
        $this->assertEquals('Unable to fulfill order', $activity->new_value['withdrawal_reason']);
        $this->assertEquals('Second Best Supplier', $activity->new_value['successor_supplier']);
        $this->assertStringContainsString('withdrew', $activity->description);
    }

    public function test_item_groups_created_activity_is_logged(): void
    {
        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logItemGroupsCreated($this->pr, [
            ['group_code' => 'G1', 'group_name' => 'Office Supplies', 'item_count' => 5],
            ['group_code' => 'G2', 'group_name' => 'IT Equipment', 'item_count' => 3],
        ]);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'item_groups_created',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'item_groups_created')
            ->first();

        $this->assertEquals(2, $activity->new_value['group_count']);
        $this->assertStringContainsString('G1, G2', $activity->description);
        $this->assertStringContainsString('2 groups', $activity->description);
    }

    public function test_item_groups_updated_activity_is_logged(): void
    {
        $logger = app(PurchaseRequestActivityLogger::class);
        $logger->logItemGroupsUpdated($this->pr, [
            ['group_code' => 'G1', 'group_name' => 'Office Supplies Updated', 'item_count' => 6],
            ['group_code' => 'G2', 'group_name' => 'IT Equipment', 'item_count' => 2],
            ['group_code' => 'G3', 'group_name' => 'Maintenance', 'item_count' => 4],
        ]);

        $this->assertDatabaseHas('purchase_request_activities', [
            'purchase_request_id' => $this->pr->id,
            'action' => 'item_groups_updated',
        ]);

        $activity = PurchaseRequestActivity::where('purchase_request_id', $this->pr->id)
            ->where('action', 'item_groups_updated')
            ->first();

        $this->assertEquals(3, $activity->new_value['group_count']);
        $this->assertStringContainsString('updated', $activity->description);
    }

    public function test_activity_model_has_correct_icons_for_bac_actions(): void
    {
        $actions = [
            'resolution_generated',
            'resolution_regenerated',
            'rfq_generated',
            'quotation_submitted',
            'quotation_evaluated',
            'aoq_generated',
            'tie_resolved',
            'bac_override',
            'supplier_withdrawal',
            'item_groups_created',
            'item_groups_updated',
        ];

        foreach ($actions as $action) {
            PurchaseRequestActivity::create([
                'purchase_request_id' => $this->pr->id,
                'action' => $action,
                'description' => "Test {$action}",
                'created_at' => now(),
            ]);
        }

        $activities = PurchaseRequestActivity::whereIn('action', $actions)->get();

        foreach ($activities as $activity) {
            $this->assertNotEmpty($activity->icon, "Icon is empty for action: {$activity->action}");
            $this->assertStringContainsString('svg', $activity->icon, "Icon is not an SVG for action: {$activity->action}");
            $this->assertNotEmpty($activity->color_class, "Color class is empty for action: {$activity->action}");
        }
    }

    public function test_activity_model_has_pr_item_group_relationship(): void
    {
        $group = PrItemGroup::create([
            'purchase_request_id' => $this->pr->id,
            'group_name' => 'Test Group',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $activity = PurchaseRequestActivity::create([
            'purchase_request_id' => $this->pr->id,
            'pr_item_group_id' => $group->id,
            'action' => 'rfq_generated',
            'description' => 'RFQ generated for group',
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(PrItemGroup::class, $activity->prItemGroup);
        $this->assertEquals($group->id, $activity->prItemGroup->id);
        $this->assertEquals('Test Group', $activity->prItemGroup->group_name);
    }
}
