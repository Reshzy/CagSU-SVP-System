<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BacQuotationLotFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $bacUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('BAC Chair');

        $this->bacUser = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $this->bacUser->assignRole('BAC Chair');
    }

    /**
     * @return array{0: PurchaseRequest, 1: PurchaseRequestItem, 2: PurchaseRequestItem, 3: PurchaseRequestItem}
     */
    private function makePrWithLotAndStandalone(): array
    {
        $pr = PurchaseRequest::factory()->create([
            'status' => 'bac_evaluation',
            'procurement_method' => 'small_value_procurement',
            'estimated_total' => 500.00,
        ]);

        $lot = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Painting Works Lot',
            'lot_name' => 'Painting Works',
            'is_lot' => true,
            'parent_lot_id' => null,
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
            'estimated_unit_cost' => 300.00,
            'estimated_total_cost' => 300.00,
        ]);

        $child = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Exterior Paint',
            'is_lot' => false,
            'parent_lot_id' => $lot->id,
            'unit_of_measure' => 'gallons',
            'quantity_requested' => 5,
            'estimated_unit_cost' => 40.00,
            'estimated_total_cost' => 200.00,
        ]);

        $standalone = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Office Chair',
            'is_lot' => false,
            'parent_lot_id' => null,
            'unit_of_measure' => 'pcs',
            'quantity_requested' => 2,
            'estimated_unit_cost' => 100.00,
            'estimated_total_cost' => 200.00,
        ]);

        return [$pr->fresh(['items.lotChildren']), $lot->fresh(), $child, $standalone];
    }

    public function test_manage_quotations_page_shows_lot_not_children_as_bid_rows(): void
    {
        [$pr, $lot, $child, $standalone] = $this->makePrWithLotAndStandalone();

        $response = $this->actingAs($this->bacUser)
            ->get(route('bac.quotations.manage', $pr));

        $response->assertOk();
        $response->assertSee('Painting Works');
        $response->assertSee('Office Chair');
        $response->assertSee('1 items');
        $response->assertSee('value="'.$lot->id.'"', false);
        $response->assertSee('value="'.$standalone->id.'"', false);
        $response->assertDontSee('[pr_item_id]" value="'.$child->id.'"', false);
        $response->assertSee('Exterior Paint');
        $response->assertSee('Included in lot bid');
    }

    public function test_manage_quotations_page_defaults_quotation_date_to_today(): void
    {
        [$pr] = $this->makePrWithLotAndStandalone();

        $response = $this->actingAs($this->bacUser)
            ->get(route('bac.quotations.manage', $pr));

        $response->assertOk();
        $response->assertSee('name="quotation_date"', false);
        $response->assertSee('value="'.now()->format('Y-m-d').'"', false);
    }

    public function test_store_quotation_saves_lot_header_price_and_skips_children(): void
    {
        [$pr, $lot, $child, $standalone] = $this->makePrWithLotAndStandalone();
        $supplier = Supplier::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->bacUser)
            ->post(route('bac.quotations.store', $pr), [
                'supplier_id' => $supplier->id,
                'supplier_location' => 'Cagayan de Oro',
                'quotation_date' => now()->toDateString(),
                'items' => [
                    [
                        'pr_item_id' => $lot->id,
                        'unit_price' => 280.00,
                    ],
                    [
                        'pr_item_id' => $child->id,
                        'unit_price' => 35.00,
                    ],
                    [
                        'pr_item_id' => $standalone->id,
                        'unit_price' => 90.00,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $quotation = Quotation::where('purchase_request_id', $pr->id)
            ->where('supplier_id', $supplier->id)
            ->first();

        $this->assertNotNull($quotation);
        $this->assertEquals(2, $quotation->quotationItems()->count());
        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $lot->id,
            'unit_price' => 280.00,
        ]);
        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $standalone->id,
            'unit_price' => 90.00,
        ]);
        $this->assertDatabaseMissing('quotation_items', [
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $child->id,
        ]);
    }

    public function test_store_quotation_rejects_when_only_lot_child_is_priced(): void
    {
        [$pr, $lot, $child] = $this->makePrWithLotAndStandalone();
        $supplier = Supplier::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->bacUser)
            ->from(route('bac.quotations.manage', $pr))
            ->post(route('bac.quotations.store', $pr), [
                'supplier_id' => $supplier->id,
                'quotation_date' => now()->toDateString(),
                'items' => [
                    [
                        'pr_item_id' => $child->id,
                        'unit_price' => 35.00,
                    ],
                ],
            ]);

        $response->assertRedirect(route('bac.quotations.manage', $pr));
        $response->assertSessionHasErrors('items');
        $this->assertEquals(0, Quotation::where('purchase_request_id', $pr->id)->count());
        $this->assertEquals(0, QuotationItem::count());
    }

    public function test_store_quotation_works_for_standalone_items_only(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'status' => 'bac_evaluation',
            'procurement_method' => 'small_value_procurement',
        ]);

        $item = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Bond Paper',
            'is_lot' => false,
            'parent_lot_id' => null,
            'quantity_requested' => 10,
            'estimated_unit_cost' => 50.00,
            'estimated_total_cost' => 500.00,
        ]);

        $supplier = Supplier::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->bacUser)
            ->post(route('bac.quotations.store', $pr), [
                'supplier_id' => $supplier->id,
                'quotation_date' => now()->toDateString(),
                'items' => [
                    [
                        'pr_item_id' => $item->id,
                        'unit_price' => 45.00,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('quotations', [
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
        ]);
        $this->assertDatabaseHas('quotation_items', [
            'purchase_request_item_id' => $item->id,
            'unit_price' => 45.00,
        ]);
    }

    public function test_marking_lot_failed_cascades_to_children(): void
    {
        [$pr, $lot, $child] = $this->makePrWithLotAndStandalone();

        $lot->markAsFailed('No eligible bidders');

        $this->assertEquals('failed', $lot->fresh()->procurement_status);
        $this->assertEquals('failed', $child->fresh()->procurement_status);
    }
}
