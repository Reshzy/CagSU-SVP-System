<?php

namespace Tests\Feature;

use App\Models\PoSignatory;
use App\Models\PrItemGroup;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BatchPurchaseOrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_preview_redirects_to_single_po_create_when_one_supplier(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_approved']);
        $group = PrItemGroup::factory()->create(['purchase_request_id' => $pr->id]);
        $supplier = Supplier::factory()->create();
        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier->id,
        ]);

        $prItem = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $prItem->id,
            'is_winner' => true,
            'is_withdrawn' => false,
        ]);

        $response = $this->get(route('supply.purchase-orders.preview', [
            'purchaseRequest' => $pr,
            'group' => $group->id,
        ]));

        $response->assertRedirect(route('supply.purchase-orders.create', [
            'purchaseRequest' => $pr,
            'group' => $group->id,
        ]));
    }

    public function test_preview_shows_multiple_suppliers(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_approved']);
        $group = PrItemGroup::factory()->create(['purchase_request_id' => $pr->id]);

        $supplier1 = Supplier::factory()->create(['business_name' => 'Supplier One']);
        $supplier2 = Supplier::factory()->create(['business_name' => 'Supplier Two']);

        $quotation1 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier1->id,
        ]);

        $quotation2 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier2->id,
        ]);

        $prItem1 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
        ]);

        $prItem2 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation1->id,
            'purchase_request_item_id' => $prItem1->id,
            'is_winner' => true,
            'is_withdrawn' => false,
            'unit_price' => 100,
            'total_price' => 1000,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation2->id,
            'purchase_request_item_id' => $prItem2->id,
            'is_winner' => true,
            'is_withdrawn' => false,
            'unit_price' => 200,
            'total_price' => 2000,
        ]);

        $response = $this->get(route('supply.purchase-orders.preview', [
            'purchaseRequest' => $pr,
            'group' => $group->id,
        ]));

        $response->assertOk();
        $response->assertSee('Supplier One');
        $response->assertSee('Supplier Two');
        $response->assertSee('2 Purchase Orders');
    }

    public function test_batch_create_shows_form_for_multiple_suppliers(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_approved']);
        $group = PrItemGroup::factory()->create(['purchase_request_id' => $pr->id]);

        $supplier1 = Supplier::factory()->create(['business_name' => 'ABC Supplies']);
        $supplier2 = Supplier::factory()->create(['business_name' => 'XYZ Trading']);

        $quotation1 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier1->id,
        ]);

        $quotation2 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier2->id,
        ]);

        $prItem1 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
        ]);

        $prItem2 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation1->id,
            'purchase_request_item_id' => $prItem1->id,
            'is_winner' => true,
            'is_withdrawn' => false,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation2->id,
            'purchase_request_item_id' => $prItem2->id,
            'is_winner' => true,
            'is_withdrawn' => false,
        ]);

        PoSignatory::factory()->create(['position' => 'ceo', 'is_active' => true]);
        PoSignatory::factory()->create(['position' => 'chief_accountant', 'is_active' => true]);

        $response = $this->get(route('supply.purchase-orders.batch-create', [
            'purchaseRequest' => $pr,
            'group' => $group->id,
        ]));

        $response->assertOk();
        $response->assertSee('ABC Supplies');
        $response->assertSee('XYZ Trading');
        $response->assertSee('PO #1');
        $response->assertSee('PO #2');
    }

    public function test_batch_store_creates_multiple_pos_successfully(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_approved']);
        $group = PrItemGroup::factory()->create(['purchase_request_id' => $pr->id]);

        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        $quotation1 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier1->id,
        ]);

        $quotation2 = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'supplier_id' => $supplier2->id,
        ]);

        $prItem1 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'quantity_requested' => 10,
        ]);

        $prItem2 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'quantity_requested' => 5,
        ]);

        $quotationItem1 = QuotationItem::factory()->create([
            'quotation_id' => $quotation1->id,
            'purchase_request_item_id' => $prItem1->id,
            'is_winner' => true,
            'is_withdrawn' => false,
            'unit_price' => 100,
            'total_price' => 1000,
        ]);

        $quotationItem2 = QuotationItem::factory()->create([
            'quotation_id' => $quotation2->id,
            'purchase_request_item_id' => $prItem2->id,
            'is_winner' => true,
            'is_withdrawn' => false,
            'unit_price' => 200,
            'total_price' => 1000,
        ]);

        $poData = [
            'pr_item_group_id' => $group->id,
            'purchase_orders' => [
                [
                    'supplier_id' => $supplier1->id,
                    'quotation_id' => $quotation1->id,
                    'total_amount' => 1000,
                    'funds_cluster' => 'FC-001',
                    'funds_available' => 5000,
                    'ors_burs_no' => 'ORS-001',
                    'ors_burs_date' => now()->format('Y-m-d'),
                    'delivery_address' => 'Test Address 1',
                    'delivery_date_required' => now()->addDays(30)->format('Y-m-d'),
                    'terms_and_conditions' => 'Standard terms',
                    'items' => [
                        [
                            'purchase_request_item_id' => $prItem1->id,
                            'quotation_item_id' => $quotationItem1->id,
                            'quantity' => 10,
                            'unit_price' => 100,
                            'total_price' => 1000,
                        ],
                    ],
                ],
                [
                    'supplier_id' => $supplier2->id,
                    'quotation_id' => $quotation2->id,
                    'total_amount' => 1000,
                    'funds_cluster' => 'FC-002',
                    'funds_available' => 3000,
                    'ors_burs_no' => 'ORS-002',
                    'ors_burs_date' => now()->format('Y-m-d'),
                    'delivery_address' => 'Test Address 2',
                    'delivery_date_required' => now()->addDays(30)->format('Y-m-d'),
                    'terms_and_conditions' => 'Standard terms',
                    'items' => [
                        [
                            'purchase_request_item_id' => $prItem2->id,
                            'quotation_item_id' => $quotationItem2->id,
                            'quantity' => 5,
                            'unit_price' => 200,
                            'total_price' => 1000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(route('supply.purchase-orders.batch-store', $pr), $poData);

        $response->assertRedirect(route('supply.purchase-requests.show', $pr));
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('purchase_orders', 2);
        $this->assertDatabaseCount('purchase_order_items', 2);

        $po1 = PurchaseOrder::where('supplier_id', $supplier1->id)->first();
        $po2 = PurchaseOrder::where('supplier_id', $supplier2->id)->first();

        $this->assertNotNull($po1);
        $this->assertNotNull($po2);
        $this->assertEquals(1000, $po1->total_amount);
        $this->assertEquals(1000, $po2->total_amount);
        $this->assertEquals('FC-001', $po1->funds_cluster);
        $this->assertEquals('FC-002', $po2->funds_cluster);

        $pr->refresh();
        $this->assertEquals('po_generation', $pr->status);
    }

    public function test_batch_store_validates_required_fields(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_approved']);

        $response = $this->post(route('supply.purchase-orders.batch-store', $pr), [
            'purchase_orders' => [
                [
                    'supplier_id' => 1,
                    // Missing required fields
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'purchase_orders.0.quotation_id',
            'purchase_orders.0.funds_cluster',
            'purchase_orders.0.funds_available',
        ]);
    }

    public function test_preview_requires_bac_approved_status(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'submitted']);

        $response = $this->get(route('supply.purchase-orders.preview', $pr));

        $response->assertForbidden();
    }

    public function test_batch_store_requires_bac_approved_status(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'submitted']);

        $response = $this->post(route('supply.purchase-orders.batch-store', $pr), [
            'purchase_orders' => [],
        ]);

        $response->assertForbidden();
    }
}
