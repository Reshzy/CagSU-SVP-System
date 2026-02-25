<?php

namespace Tests\Feature;

use App\Livewire\Supply\PurchaseOrderTable;
use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderTableFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('Supply Officer');

        $this->department = Department::factory()->create();
    }

    private function createPoWithPr(Supplier $supplier, string $poNumber, string $prNumber, string $status = 'approved'): PurchaseOrder
    {
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $this->department->id,
            'pr_number' => $prNumber,
            'status' => 'bac_approved',
        ]);

        return PurchaseOrder::factory()->create([
            'po_number' => $poNumber,
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'status' => $status,
        ]);
    }

    public function test_purchase_order_table_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PurchaseOrderTable::class)
            ->assertStatus(200);
    }

    public function test_filter_by_po_number_returns_matching_results(): void
    {
        $this->actingAs($this->user);
        $supplier = Supplier::factory()->create();

        $this->createPoWithPr($supplier, 'PO-0226-0001', 'PR-2026-0001');
        $this->createPoWithPr($supplier, 'PO-0226-0002', 'PR-2026-0002');
        $this->createPoWithPr($supplier, 'PO-0226-0099', 'PR-2026-0003');

        Livewire::test(PurchaseOrderTable::class)
            ->set('poNumberSearch', 'PO-0226-0001')
            ->assertSee('PO-0226-0001')
            ->assertDontSee('PO-0226-0002')
            ->assertDontSee('PO-0226-0099');
    }

    public function test_filter_by_po_number_partial_match(): void
    {
        $this->actingAs($this->user);
        $supplier = Supplier::factory()->create();

        $this->createPoWithPr($supplier, 'PO-0226-0001', 'PR-2026-0001');
        $this->createPoWithPr($supplier, 'PO-0226-0002', 'PR-2026-0002');
        $this->createPoWithPr($supplier, 'PO-0125-0001', 'PR-2026-0003');

        Livewire::test(PurchaseOrderTable::class)
            ->set('poNumberSearch', 'PO-0226')
            ->assertSee('PO-0226-0001')
            ->assertSee('PO-0226-0002')
            ->assertDontSee('PO-0125-0001');
    }

    public function test_filter_by_supplier_returns_matching_results(): void
    {
        $this->actingAs($this->user);
        $supplierA = Supplier::factory()->create(['business_name' => 'Acme Corp']);
        $supplierB = Supplier::factory()->create(['business_name' => 'Beta Supplies']);

        $poA = $this->createPoWithPr($supplierA, 'PO-0226-1001', 'PR-2026-1001');
        $poB = $this->createPoWithPr($supplierB, 'PO-0226-1002', 'PR-2026-1002');

        Livewire::test(PurchaseOrderTable::class)
            ->set('supplierFilter', (string) $supplierA->id)
            ->assertSee('PO-0226-1001')
            ->assertDontSee('PO-0226-1002');
    }

    public function test_filter_by_pr_number_returns_matching_results(): void
    {
        $this->actingAs($this->user);
        $supplier = Supplier::factory()->create();

        $this->createPoWithPr($supplier, 'PO-0226-2001', 'PR-2026-2001');
        $this->createPoWithPr($supplier, 'PO-0226-2002', 'PR-2026-2002');

        Livewire::test(PurchaseOrderTable::class)
            ->set('prNumberFilter', 'PR-2026-2001')
            ->assertSee('PO-0226-2001')
            ->assertDontSee('PO-0226-2002');
    }

    public function test_filter_by_status_returns_matching_results(): void
    {
        $this->actingAs($this->user);
        $supplier = Supplier::factory()->create();

        $this->createPoWithPr($supplier, 'PO-0226-3001', 'PR-2026-3001', 'approved');
        $this->createPoWithPr($supplier, 'PO-0226-3002', 'PR-2026-3002', 'cancelled');

        Livewire::test(PurchaseOrderTable::class)
            ->set('statusFilter', 'approved')
            ->assertSee('PO-0226-3001')
            ->assertDontSee('PO-0226-3002');
    }

    public function test_combined_filters_narrow_results(): void
    {
        $this->actingAs($this->user);
        $supplierA = Supplier::factory()->create(['business_name' => 'Corp Alpha']);
        $supplierB = Supplier::factory()->create(['business_name' => 'Corp Beta']);

        $this->createPoWithPr($supplierA, 'PO-0226-4001', 'PR-2026-4001', 'approved');
        $this->createPoWithPr($supplierA, 'PO-0226-4002', 'PR-2026-4002', 'cancelled');
        $this->createPoWithPr($supplierB, 'PO-0226-4003', 'PR-2026-4003', 'approved');

        Livewire::test(PurchaseOrderTable::class)
            ->set('supplierFilter', (string) $supplierA->id)
            ->set('statusFilter', 'approved')
            ->assertSee('PO-0226-4001')
            ->assertDontSee('PO-0226-4002')
            ->assertDontSee('PO-0226-4003');
    }

    public function test_empty_filters_returns_all_results(): void
    {
        $this->actingAs($this->user);
        $supplier = Supplier::factory()->create();

        $this->createPoWithPr($supplier, 'PO-0226-5001', 'PR-2026-5001');
        $this->createPoWithPr($supplier, 'PO-0226-5002', 'PR-2026-5002');

        Livewire::test(PurchaseOrderTable::class)
            ->assertSee('PO-0226-5001')
            ->assertSee('PO-0226-5002');
    }

    public function test_no_results_message_shown_when_filters_active(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PurchaseOrderTable::class)
            ->set('poNumberSearch', 'PO-NONEXISTENT-9999')
            ->assertSee('No purchase orders found matching your search criteria.');
    }

    public function test_default_message_shown_when_no_orders_and_no_filters(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PurchaseOrderTable::class)
            ->assertSee('No purchase orders.');
    }

    public function test_clear_filters_resets_all_filter_properties(): void
    {
        $this->actingAs($this->user);
        $supplier = Supplier::factory()->create();
        $this->createPoWithPr($supplier, 'PO-0226-6001', 'PR-2026-6001');

        $component = Livewire::test(PurchaseOrderTable::class)
            ->set('poNumberSearch', 'PO-0226')
            ->set('statusFilter', 'approved')
            ->set('supplierFilter', (string) $supplier->id)
            ->set('prNumberFilter', 'PR-2026-6001');

        $component->assertSet('poNumberSearch', 'PO-0226')
            ->assertSet('statusFilter', 'approved');

        $component
            ->set('poNumberSearch', '')
            ->set('supplierFilter', '')
            ->set('prNumberFilter', '')
            ->set('statusFilter', '')
            ->assertSet('poNumberSearch', '')
            ->assertSet('supplierFilter', '')
            ->assertSet('prNumberFilter', '')
            ->assertSet('statusFilter', '');
    }
}
