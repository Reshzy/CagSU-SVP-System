<?php

namespace Tests\Feature;

use App\Models\AoqGeneration;
use App\Models\Department;
use App\Models\PrItemGroup;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupedPurchaseOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole('Supply Officer');
    }

    public function test_pr_with_item_groups_shows_groups_card(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_evaluation',
        ]);

        $group1 = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $group2 = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'IT Equipment',
            'group_code' => 'G2',
            'display_order' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-requests.show', $pr));

        $response->assertOk();
        $response->assertSee('Item Groups & Quotations');
        $response->assertSee('G1: Office Supplies');
        $response->assertSee('G2: IT Equipment');
    }

    public function test_pr_with_item_groups_shows_dropdown_in_sidebar(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_approved',
        ]);

        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-requests.show', $pr));

        $response->assertOk();
        $response->assertSee('Create Purchase Order');
        $response->assertSee('G1: Office Supplies');
    }

    public function test_group_without_aoq_shows_awaiting_status(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_evaluation',
        ]);

        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-requests.show', $pr));

        $response->assertOk();
        $response->assertSee('Awaiting AOQ');
    }

    public function test_group_with_aoq_shows_ready_status(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_evaluation',
        ]);

        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        AoqGeneration::create([
            'aoq_reference_number' => 'AOQ-0226-0001',
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'generated_by' => $this->user->id,
            'document_hash' => 'test_hash',
            'exported_data_snapshot' => ['test' => 'data'],
            'file_path' => 'test/path.docx',
            'file_format' => 'docx',
            'total_items' => 5,
            'total_suppliers' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-requests.show', $pr));

        $response->assertOk();
        $response->assertSee('AOQ Generated');
        $response->assertSee('AOQ-0226-0001');
    }

    public function test_po_create_page_accepts_group_parameter(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_approved',
        ]);

        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $supplier = Supplier::factory()->create();
        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
            'is_winning_bid' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-orders.create', ['purchaseRequest' => $pr, 'group' => $group->id]));

        $response->assertOk();
        $response->assertSee('G1: Office Supplies');
        $response->assertSee('Creating PO for Item Group');
    }

    public function test_po_can_be_created_for_specific_group(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_approved',
        ]);

        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'estimated_total_cost' => 10000,
        ]);

        $supplier = Supplier::factory()->create();
        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
            'is_winning_bid' => true,
            'total_amount' => 10000,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('supply.purchase-orders.store', $pr), [
                'pr_item_group_id' => $group->id,
                'supplier_id' => $supplier->id,
                'quotation_id' => $quotation->id,
                'funds_cluster' => 'Cluster A',
                'funds_available' => 50000.00,
                'ors_burs_no' => 'ORS-2026-001',
                'ors_burs_date' => '2026-02-11',
                'total_amount' => 10000.00,
                'delivery_address' => 'Test Campus',
                'delivery_date_required' => '2026-03-01',
                'terms_and_conditions' => 'Standard terms',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', [
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_multiple_pos_can_be_created_for_different_groups(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_approved',
        ]);

        $group1 = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $group2 = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'IT Equipment',
            'group_code' => 'G2',
            'display_order' => 2,
        ]);

        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        $quotation1 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group1->id,
            'supplier_id' => $supplier1->id,
            'is_winning_bid' => true,
            'total_amount' => 10000,
        ]);

        $quotation2 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group2->id,
            'supplier_id' => $supplier2->id,
            'is_winning_bid' => true,
            'total_amount' => 20000,
        ]);

        // Create PO for group 1
        $this->actingAs($this->user)
            ->post(route('supply.purchase-orders.store', $pr), [
                'pr_item_group_id' => $group1->id,
                'supplier_id' => $supplier1->id,
                'quotation_id' => $quotation1->id,
                'funds_cluster' => 'Cluster A',
                'funds_available' => 50000.00,
                'ors_burs_no' => 'ORS-2026-001',
                'ors_burs_date' => '2026-02-11',
                'total_amount' => 10000.00,
                'delivery_address' => 'Test Campus',
                'delivery_date_required' => '2026-03-01',
                'terms_and_conditions' => 'Standard terms',
            ]);

        // Create PO for group 2
        $this->actingAs($this->user)
            ->post(route('supply.purchase-orders.store', $pr), [
                'pr_item_group_id' => $group2->id,
                'supplier_id' => $supplier2->id,
                'quotation_id' => $quotation2->id,
                'funds_cluster' => 'Cluster B',
                'funds_available' => 60000.00,
                'ors_burs_no' => 'ORS-2026-002',
                'ors_burs_date' => '2026-02-11',
                'total_amount' => 20000.00,
                'delivery_address' => 'Test Campus',
                'delivery_date_required' => '2026-03-01',
                'terms_and_conditions' => 'Standard terms',
            ]);

        $this->assertDatabaseHas('purchase_orders', [
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group1->id,
            'supplier_id' => $supplier1->id,
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group2->id,
            'supplier_id' => $supplier2->id,
        ]);

        $this->assertEquals(2, PurchaseOrder::where('purchase_request_id', $pr->id)->count());
    }

    public function test_pr_item_group_helper_methods_work_correctly(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_evaluation',
        ]);

        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        // Test isReadyForPo() - should be false without AOQ
        $this->assertFalse($group->isReadyForPo());

        // Create AOQ
        AoqGeneration::create([
            'aoq_reference_number' => 'AOQ-0226-0001',
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'generated_by' => $this->user->id,
            'document_hash' => 'test_hash',
            'exported_data_snapshot' => ['test' => 'data'],
            'file_path' => 'test/path.docx',
            'file_format' => 'docx',
            'total_items' => 5,
            'total_suppliers' => 3,
        ]);

        // Refresh and test again
        $group = $group->fresh();
        $this->assertTrue($group->isReadyForPo());

        // Test hasExistingPo() - should be false
        $this->assertFalse($group->hasExistingPo());

        // Create PO
        $supplier = Supplier::factory()->create();
        PurchaseOrder::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
        ]);

        // Refresh and test again
        $group = $group->fresh();
        $this->assertTrue($group->hasExistingPo());

        // Test getWinningQuotation()
        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
            'is_winning_bid' => true,
        ]);

        $group = $group->fresh();
        $this->assertNotNull($group->getWinningQuotation());
        $this->assertEquals($quotation->id, $group->getWinningQuotation()->id);
    }
}
