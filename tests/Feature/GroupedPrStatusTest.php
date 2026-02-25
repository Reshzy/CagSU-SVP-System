<?php

namespace Tests\Feature;

use App\Models\AoqGeneration;
use App\Models\Department;
use App\Models\PrItemGroup;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupedPrStatusTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseRequest $pr;

    protected PrItemGroup $group1;

    protected PrItemGroup $group2;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create([
            'name' => 'Test Department',
            'code' => 'TEST-'.fake()->unique()->numberBetween(1, 999),
        ]);

        $user = User::factory()->create();

        $this->pr = PurchaseRequest::create([
            'pr_number' => 'PR-0226-0001',
            'requester_id' => $user->id,
            'department_id' => $department->id,
            'purpose' => 'Testing',
            'status' => 'bac_evaluation',
            'date_needed' => now()->addDays(30),
            'estimated_total' => 10000,
        ]);

        $this->group1 = PrItemGroup::create([
            'purchase_request_id' => $this->pr->id,
            'group_name' => 'Group 1',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $this->group2 = PrItemGroup::create([
            'purchase_request_id' => $this->pr->id,
            'group_name' => 'Group 2',
            'group_code' => 'G2',
            'display_order' => 2,
        ]);
    }

    // -----------------------------------------------------------------------
    // PrItemGroup::computeStatus() tests
    // -----------------------------------------------------------------------

    /** @test */
    public function group_with_no_aoq_and_no_po_is_pending(): void
    {
        $this->assertSame('pending', $this->group1->computeStatus());
    }

    /** @test */
    public function group_with_aoq_and_no_po_is_aoq_generated(): void
    {
        $this->makeAoq($this->group1);

        $this->assertSame('aoq_generated', $this->group1->computeStatus());
    }

    /** @test */
    public function group_with_pending_approval_po_is_po_created(): void
    {
        $this->makePo($this->group1, 'pending_approval');

        $this->assertSame('po_created', $this->group1->computeStatus());
    }

    /** @test */
    public function group_with_all_pos_approved_is_all_po_approved(): void
    {
        $this->makePo($this->group1, 'approved');

        $this->assertSame('all_po_approved', $this->group1->computeStatus());
    }

    /** @test */
    public function group_with_po_sent_to_supplier_is_processing(): void
    {
        $this->makePo($this->group1, 'sent_to_supplier');

        $this->assertSame('processing', $this->group1->computeStatus());
    }

    /** @test */
    public function group_is_delivered_when_all_pos_are_delivered(): void
    {
        $this->makePo($this->group1, 'delivered');

        $this->assertSame('delivered', $this->group1->computeStatus());
    }

    /** @test */
    public function group_is_not_delivered_when_one_po_is_still_processing(): void
    {
        $this->makePo($this->group1, 'delivered');
        $this->makePo($this->group1, 'sent_to_supplier');

        $this->assertSame('processing', $this->group1->computeStatus());
    }

    /** @test */
    public function group_is_completed_only_when_all_pos_are_completed(): void
    {
        $this->makePo($this->group1, 'completed');

        $this->assertTrue($this->group1->isCompleted());
    }

    /** @test */
    public function group_is_not_completed_when_one_po_is_only_delivered(): void
    {
        $this->makePo($this->group1, 'completed');
        $this->makePo($this->group1, 'delivered');

        $this->assertSame('delivered', $this->group1->computeStatus());
        $this->assertFalse($this->group1->isCompleted());
    }

    // -----------------------------------------------------------------------
    // PrItemGroup permission method tests
    // -----------------------------------------------------------------------

    /** @test */
    public function group_without_aoq_or_po_can_create_aoq(): void
    {
        $this->assertTrue($this->group1->canCreateAoq());
    }

    /** @test */
    public function group_with_existing_po_cannot_create_aoq(): void
    {
        $this->makePo($this->group1, 'pending_approval');

        $this->assertFalse($this->group1->canCreateAoq());
    }

    /** @test */
    public function group_with_aoq_can_create_po(): void
    {
        $this->makeAoq($this->group1);

        $this->assertTrue($this->group1->canCreatePo());
    }

    /** @test */
    public function group_with_po_sent_to_supplier_cannot_create_new_po(): void
    {
        $this->makePo($this->group1, 'sent_to_supplier');

        $this->assertFalse($this->group1->canCreatePo());
    }

    /** @test */
    public function group_with_pending_approval_po_cannot_create_new_po(): void
    {
        $this->makePo($this->group1, 'pending_approval');

        $this->assertFalse($this->group1->canCreatePo());
    }

    // -----------------------------------------------------------------------
    // PurchaseRequest::computeStatusFromGroups() tests
    // -----------------------------------------------------------------------

    /** @test */
    public function non_grouped_pr_returns_null_from_compute_status(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-0226-0002',
            'requester_id' => User::factory()->create()->id,
            'department_id' => $this->pr->department_id,
            'purpose' => 'Testing non-grouped',
            'status' => 'bac_evaluation',
            'date_needed' => now()->addDays(30),
            'estimated_total' => 5000,
        ]);

        $this->assertNull($pr->computeStatusFromGroups());
        $this->assertSame('bac_evaluation', $pr->getEffectiveStatus());
    }

    /** @test */
    public function pr_effective_status_is_bac_evaluation_when_all_groups_are_pending(): void
    {
        $this->pr->load('itemGroups');

        $this->assertSame('bac_evaluation', $this->pr->computeStatusFromGroups());
    }

    /** @test */
    public function pr_status_reflects_earliest_group_when_groups_are_at_different_stages(): void
    {
        // Group 1 has a completed PO, Group 2 is still pending
        $this->makePo($this->group1, 'completed');

        $this->pr->load('itemGroups.purchaseOrders');

        // Minimum group status is 'pending' (group 2) → PR status should be 'bac_evaluation'
        $this->assertSame('bac_evaluation', $this->pr->computeStatusFromGroups());
    }

    /** @test */
    public function pr_status_is_partial_po_generation_when_one_group_has_po_and_other_is_pending(): void
    {
        // Group 1 has a PO, Group 2 still has no AOQ
        $this->makePo($this->group1, 'pending_approval');

        $this->pr->load('itemGroups.purchaseOrders');

        // min status: group2=pending → bac_evaluation; but group1=po_created → partial_po_generation
        // The minimum is 'pending' → 'bac_evaluation'
        $this->assertSame('bac_evaluation', $this->pr->computeStatusFromGroups());
    }

    /** @test */
    public function pr_status_is_supplier_processing_when_minimum_group_is_processing(): void
    {
        $this->makePo($this->group1, 'completed');
        $this->makePo($this->group2, 'sent_to_supplier');

        $this->pr->load('itemGroups.purchaseOrders');

        // Minimum group status: group2=processing → supplier_processing
        $this->assertSame('supplier_processing', $this->pr->computeStatusFromGroups());
    }

    /** @test */
    public function pr_status_is_completed_only_when_all_groups_are_completed(): void
    {
        $this->makePo($this->group1, 'completed');
        $this->makePo($this->group2, 'completed');

        $this->pr->load('itemGroups.purchaseOrders');

        $this->assertSame('completed', $this->pr->computeStatusFromGroups());
    }

    /** @test */
    public function pr_status_is_not_completed_when_only_one_group_is_completed(): void
    {
        $this->makePo($this->group1, 'completed');
        // group2 still pending

        $this->pr->load('itemGroups.purchaseOrders');

        $this->assertNotSame('completed', $this->pr->computeStatusFromGroups());
    }

    // -----------------------------------------------------------------------
    // PurchaseRequest permission method tests
    // -----------------------------------------------------------------------

    /** @test */
    public function pr_can_create_aoq_when_at_least_one_group_is_pending(): void
    {
        $this->makePo($this->group1, 'completed');
        // group2 is still pending

        $this->pr->load('itemGroups.purchaseOrders');

        $this->assertTrue($this->pr->canCreateAoq());
    }

    /** @test */
    public function pr_cannot_create_aoq_when_all_groups_have_pos(): void
    {
        $this->makePo($this->group1, 'completed');
        $this->makePo($this->group2, 'completed');

        $this->pr->load('itemGroups.purchaseOrders');

        $this->assertFalse($this->pr->canCreateAoq());
    }

    /** @test */
    public function pr_can_create_po_when_at_least_one_group_has_aoq(): void
    {
        $this->makeAoq($this->group1);

        $this->pr->load('itemGroups.purchaseOrders');

        $this->assertTrue($this->pr->canCreatePo());
    }

    /** @test */
    public function pr_cannot_create_po_when_all_groups_are_processing_or_beyond(): void
    {
        $this->makePo($this->group1, 'sent_to_supplier');
        $this->makePo($this->group2, 'delivered');

        $this->pr->load('itemGroups.purchaseOrders');

        $this->assertFalse($this->pr->canCreatePo());
    }

    // -----------------------------------------------------------------------
    // syncStatusFromGroups() tests
    // -----------------------------------------------------------------------

    /** @test */
    public function sync_status_updates_stored_pr_status_from_group_statuses(): void
    {
        $this->makePo($this->group1, 'completed');
        $this->makePo($this->group2, 'completed');

        $this->pr->load('itemGroups.purchaseOrders');
        $this->pr->syncStatusFromGroups();

        $this->assertSame('completed', $this->pr->fresh()->status);
    }

    /** @test */
    public function sync_status_does_not_mark_pr_completed_when_one_group_is_pending(): void
    {
        $this->makePo($this->group1, 'completed');
        // group2 still pending

        $this->pr->load('itemGroups.purchaseOrders');
        $this->pr->syncStatusFromGroups();

        $this->assertNotSame('completed', $this->pr->fresh()->status);
    }

    /** @test */
    public function sync_status_sets_completed_at_when_all_groups_complete(): void
    {
        $this->makePo($this->group1, 'completed');
        $this->makePo($this->group2, 'completed');

        $this->pr->load('itemGroups.purchaseOrders');
        $this->pr->syncStatusFromGroups();

        $this->assertNotNull($this->pr->fresh()->completed_at);
    }

    // -----------------------------------------------------------------------
    // Backward compatibility: non-grouped PRs
    // -----------------------------------------------------------------------

    /** @test */
    public function non_grouped_pr_can_create_aoq_when_in_bac_evaluation(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-0226-0003',
            'requester_id' => User::factory()->create()->id,
            'department_id' => $this->pr->department_id,
            'purpose' => 'Testing',
            'status' => 'bac_evaluation',
            'date_needed' => now()->addDays(30),
            'estimated_total' => 5000,
        ]);

        $this->assertTrue($pr->canCreateAoq());
    }

    /** @test */
    public function non_grouped_pr_cannot_create_aoq_when_completed(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-0226-0004',
            'requester_id' => User::factory()->create()->id,
            'department_id' => $this->pr->department_id,
            'purpose' => 'Testing',
            'status' => 'completed',
            'date_needed' => now()->addDays(30),
            'estimated_total' => 5000,
        ]);

        $this->assertFalse($pr->canCreateAoq());
    }

    /** @test */
    public function non_grouped_pr_can_create_po_when_bac_approved(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-0226-0005',
            'requester_id' => User::factory()->create()->id,
            'department_id' => $this->pr->department_id,
            'purpose' => 'Testing',
            'status' => 'bac_approved',
            'date_needed' => now()->addDays(30),
            'estimated_total' => 5000,
        ]);

        $this->assertTrue($pr->canCreatePo());
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makePo(PrItemGroup $group, string $status): PurchaseOrder
    {
        $supplier = Supplier::factory()->create();
        $quotation = Quotation::create([
            'purchase_request_id' => $this->pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
            'quotation_number' => 'QUO-'.now()->year.'-'.fake()->unique()->numberBetween(1000, 9999),
            'quotation_date' => now()->toDateString(),
            'validity_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1000.00,
            'bac_status' => 'awarded',
        ]);

        return PurchaseOrder::create([
            'po_number' => 'PO-'.now()->format('my').'-'.fake()->unique()->numberBetween(1, 9999),
            'purchase_request_id' => $this->pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
            'quotation_id' => $quotation->id,
            'po_date' => now(),
            'delivery_address' => 'Test Address',
            'delivery_date_required' => now()->addDays(30),
            'terms_and_conditions' => 'Standard terms.',
            'status' => $status,
        ]);
    }

    private function makeAoq(PrItemGroup $group): AoqGeneration
    {
        return AoqGeneration::create([
            'aoq_reference_number' => 'AOQ-'.fake()->unique()->numberBetween(1000, 9999),
            'purchase_request_id' => $this->pr->id,
            'pr_item_group_id' => $group->id,
            'generated_by' => User::factory()->create()->id,
            'file_path' => 'aoq_documents/test.docx',
            'file_format' => 'docx',
            'total_items' => 0,
            'total_suppliers' => 0,
            'exported_data_snapshot' => '{}',
        ]);
    }
}
