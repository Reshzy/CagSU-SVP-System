<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Database\Seeders\RolePermissionSeeder;

class PurchaseOrderShowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('Supply Officer');
    }

    public function test_show_displays_po_items_when_present(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
        ]);

        $supplier = Supplier::factory()->create();

        $po = PurchaseOrder::factory()->create([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'total_amount' => 1000,
        ]);

        $prItem = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Bond paper',
            'unit_of_measure' => 'ream',
            'quantity_requested' => 10,
        ]);

        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
        ]);

        $quotationItem = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $prItem->id,
            'unit_price' => 100,
            'total_price' => 1000,
            'is_winner' => true,
            'is_withdrawn' => false,
        ]);

        $po->update([
            'quotation_id' => $quotation->id,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'purchase_request_item_id' => $prItem->id,
            'quotation_item_id' => $quotationItem->id,
            'quantity' => 10,
            'unit_price' => 100,
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-orders.show', $po));

        $response->assertOk();
        $response->assertSee('Bond paper');
        $response->assertSee('₱100.00');
        $response->assertSee('₱1,000.00');
    }

    public function test_show_falls_back_to_quotation_items_when_no_po_items(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
        ]);

        $supplier = Supplier::factory()->create();

        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'is_winning_bid' => true,
        ]);

        $prItem = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Printer ink',
            'unit_of_measure' => 'bottle',
            'quantity_requested' => 2,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $prItem->id,
            'unit_price' => 500,
            'total_price' => 1000,
            'is_winner' => true,
            'is_withdrawn' => false,
        ]);

        $po = PurchaseOrder::factory()->create([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'quotation_id' => $quotation->id,
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-orders.show', $po));

        $response->assertOk();
        $response->assertSee('Printer ink');
        $response->assertSee('₱500.00');
        $response->assertSee('₱1,000.00');
    }
}
