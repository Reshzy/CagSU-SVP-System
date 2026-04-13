<?php

namespace Tests\Feature;

use App\Livewire\Supply\PurchaseOrderTable;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplyPurchaseOrderLiveSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createSupplyOfficerUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Supply Officer');

        return $user;
    }

    public function test_purchase_order_index_page_loads_with_livewire_component(): void
    {
        $user = $this->createSupplyOfficerUser();

        $response = $this->actingAs($user)->get(route('supply.purchase-orders.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(PurchaseOrderTable::class);
    }

    public function test_livewire_component_displays_purchase_orders(): void
    {
        $user = $this->createSupplyOfficerUser();
        $supplier = Supplier::factory()->create(['business_name' => 'Test Supplier Inc']);
        $pr = PurchaseRequest::factory()->create(['pr_number' => 'PR-2026-0001']);
        $po = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0001',
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'total_amount' => 10000.00,
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderTable::class)
            ->assertSee('PO-0226-0001')
            ->assertSee('PR-2026-0001')
            ->assertSee('Test Supplier Inc')
            ->assertSee('10,000.00');
    }

    public function test_search_filters_by_po_number(): void
    {
        $user = $this->createSupplyOfficerUser();
        $supplier = Supplier::factory()->create();
        $pr = PurchaseRequest::factory()->create();

        $po1 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0001',
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
        ]);

        $po2 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0002',
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderTable::class)
            ->set('poNumberSearch', 'PO-0226-0001')
            ->assertSee('PO-0226-0001')
            ->assertDontSee('PO-0226-0002');
    }

    public function test_search_filters_by_pr_number(): void
    {
        $user = $this->createSupplyOfficerUser();
        $supplier = Supplier::factory()->create();

        $pr1 = PurchaseRequest::factory()->create(['pr_number' => 'PR-2026-0001']);
        $pr2 = PurchaseRequest::factory()->create(['pr_number' => 'PR-2026-0002']);

        $po1 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0001',
            'purchase_request_id' => $pr1->id,
            'supplier_id' => $supplier->id,
        ]);

        $po2 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0002',
            'purchase_request_id' => $pr2->id,
            'supplier_id' => $supplier->id,
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderTable::class)
            ->set('prNumberFilter', 'PR-2026-0001')
            ->assertSee('PO-0226-0001')
            ->assertDontSee('PO-0226-0002');
    }

    public function test_search_filters_by_supplier_name(): void
    {
        $user = $this->createSupplyOfficerUser();
        $pr = PurchaseRequest::factory()->create();

        $supplier1 = Supplier::factory()->create(['business_name' => 'Alpha Supplies Inc']);
        $supplier2 = Supplier::factory()->create(['business_name' => 'Beta Corporation']);

        $po1 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0001',
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier1->id,
        ]);

        $po2 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0002',
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier2->id,
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderTable::class)
            ->set('supplierFilter', (string) $supplier1->id)
            ->assertSee('PO-0226-0001')
            ->assertDontSee('PO-0226-0002');
    }

    public function test_status_filter_works(): void
    {
        $user = $this->createSupplyOfficerUser();
        $supplier = Supplier::factory()->create();
        $pr = PurchaseRequest::factory()->create();

        $po1 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0001',
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending_approval',
        ]);

        $po2 = PurchaseOrder::factory()->create([
            'po_number' => 'PO-0226-0002',
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'status' => 'completed',
        ]);

        Livewire::actingAs($user)
            ->test(PurchaseOrderTable::class)
            ->set('statusFilter', 'pending_approval')
            ->assertSee('PO-0226-0001')
            ->assertDontSee('PO-0226-0002');
    }

    public function test_search_updates_url_with_query_parameter(): void
    {
        $user = $this->createSupplyOfficerUser();

        Livewire::actingAs($user)
            ->withQueryParams(['po' => 'PO-0226'])
            ->test(PurchaseOrderTable::class)
            ->assertSet('poNumberSearch', 'PO-0226');
    }

    public function test_pagination_resets_on_search(): void
    {
        $user = $this->createSupplyOfficerUser();
        $supplier = Supplier::factory()->create();
        $pr = PurchaseRequest::factory()->create();

        // Create more than 15 POs to trigger pagination
        for ($i = 1; $i <= 20; $i++) {
            PurchaseOrder::factory()->create([
                'po_number' => 'PO-0226-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'purchase_request_id' => $pr->id,
                'supplier_id' => $supplier->id,
            ]);
        }

        Livewire::actingAs($user)
            ->test(PurchaseOrderTable::class)
            ->call('setPage', 2)
            ->set('poNumberSearch', 'PO-0226-0001')
            ->assertSee('PO-0226-0001')
            ->assertDontSee('PO-0226-0020');
    }

    public function test_shows_no_results_message_when_search_returns_nothing(): void
    {
        $user = $this->createSupplyOfficerUser();

        Livewire::actingAs($user)
            ->test(PurchaseOrderTable::class)
            ->set('poNumberSearch', 'nonexistent-po-number')
            ->assertSee('No purchase orders found matching your search criteria.');
    }
}
