<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\BacRfqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class BacRfqLotItemNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfq_item_numbers_only_apply_to_lot_headers_and_standalone_items(): void
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

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Exterior Paint',
            'is_lot' => false,
            'parent_lot_id' => $lot->id,
            'unit_of_measure' => 'gallons',
            'quantity_requested' => 5,
            'estimated_unit_cost' => 40.00,
            'estimated_total_cost' => 200.00,
        ]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Primer',
            'is_lot' => false,
            'parent_lot_id' => $lot->id,
            'unit_of_measure' => 'gallons',
            'quantity_requested' => 2,
            'estimated_unit_cost' => 50.00,
            'estimated_total_cost' => 100.00,
        ]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Office Chair',
            'is_lot' => false,
            'parent_lot_id' => null,
            'unit_of_measure' => 'pcs',
            'quantity_requested' => 2,
            'estimated_unit_cost' => 100.00,
            'estimated_total_cost' => 200.00,
        ]);

        $items = $pr->fresh(['items.lotChildren'])->items;

        $method = new ReflectionMethod(BacRfqService::class, 'buildRfqDisplayItems');
        $rows = $method->invoke(new BacRfqService, $items);

        $this->assertCount(4, $rows);
        $this->assertTrue($rows[0]['is_bid_line']);
        $this->assertFalse($rows[1]['is_bid_line']);
        $this->assertFalse($rows[2]['is_bid_line']);
        $this->assertTrue($rows[3]['is_bid_line']);

        $this->assertSame(['1', '', '', '2'], BacRfqService::resolveItemNumbers($rows));
    }

    public function test_resolve_item_numbers_leaves_non_bid_lines_blank(): void
    {
        $numbers = BacRfqService::resolveItemNumbers([
            ['is_bid_line' => true],
            ['is_bid_line' => false],
            ['is_bid_line' => true],
        ]);

        $this->assertSame(['1', '', '2'], $numbers);
    }
}
