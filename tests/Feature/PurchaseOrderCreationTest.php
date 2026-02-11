<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PoSignatory;
use App\Models\PrItemGroup;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole('Supply Officer');
    }

    public function test_po_creation_page_loads_with_new_fields(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_approved',
        ]);

        $supplier = Supplier::factory()->create();
        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'is_winning_bid' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-orders.create', $pr));

        $response->assertOk();
        $response->assertSee('Funds Cluster');
        $response->assertSee('Funds Available');
        $response->assertSee('ORS/BURS No.');
        $response->assertSee('Date of ORS/BURS');
        $response->assertSee('TIN');
    }

    public function test_po_can_be_created_with_new_financial_fields(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_approved',
        ]);

        $supplier = Supplier::factory()->create();
        $quotation = Quotation::factory()->create([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'is_winning_bid' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('supply.purchase-orders.store', $pr), [
                'supplier_id' => $supplier->id,
                'quotation_id' => $quotation->id,
                'tin' => '123-456-789',
                'funds_cluster' => 'Cluster A',
                'funds_available' => 50000.00,
                'ors_burs_no' => 'ORS-2026-001',
                'ors_burs_date' => '2026-02-11',
                'total_amount' => 45000.00,
                'delivery_address' => 'Test Campus',
                'delivery_date_required' => '2026-03-01',
                'terms_and_conditions' => 'Standard terms',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', [
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'tin' => '123-456-789',
            'funds_cluster' => 'Cluster A',
            'funds_available' => 50000.00,
            'ors_burs_no' => 'ORS-2026-001',
        ]);
    }

    public function test_po_requires_financial_fields(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'bac_approved',
        ]);

        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('supply.purchase-orders.store', $pr), [
                'supplier_id' => $supplier->id,
                'total_amount' => 45000.00,
                'delivery_address' => 'Test Campus',
                'delivery_date_required' => '2026-03-01',
                'terms_and_conditions' => 'Standard terms',
                // Missing: funds_cluster, funds_available, ors_burs_no, ors_burs_date
            ]);

        $response->assertSessionHasErrors(['funds_cluster', 'funds_available', 'ors_burs_no', 'ors_burs_date']);
    }

    public function test_po_signatories_can_be_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('supply.po-signatories.store'), [
                'input_type' => 'manual',
                'manual_name' => 'John Doe',
                'position' => 'ceo',
                'prefix' => 'Dr.',
                'suffix' => 'Ph.D.',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('supply.po-signatories.index'));
        $this->assertDatabaseHas('po_signatories', [
            'manual_name' => 'John Doe',
            'position' => 'ceo',
            'prefix' => 'Dr.',
            'suffix' => 'Ph.D.',
            'is_active' => true,
        ]);
    }

    public function test_only_one_active_signatory_per_position_allowed(): void
    {
        PoSignatory::create([
            'manual_name' => 'Jane Smith',
            'position' => 'ceo',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('supply.po-signatories.store'), [
                'input_type' => 'manual',
                'manual_name' => 'John Doe',
                'position' => 'ceo',
                'is_active' => true,
            ]);

        $response->assertSessionHas('error');
    }

    public function test_po_export_route_exists(): void
    {
        $department = Department::factory()->create();
        $pr = PurchaseRequest::factory()->create([
            'department_id' => $department->id,
            'status' => 'po_generation',
        ]);

        $supplier = Supplier::factory()->create();
        $po = PurchaseOrder::factory()->create([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('supply.purchase-orders.export', $po));

        // This will fail if template doesn't exist, but verifies route works
        $response->assertStatus(200);
    }
}
