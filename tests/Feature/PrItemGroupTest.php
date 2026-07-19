<?php

namespace Tests\Feature;

use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrItemGroupTest extends TestCase
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
     * Test that BAC officer can create item groups
     */
    public function test_bac_officer_can_create_item_groups(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        $items = PurchaseRequestItem::factory()->count(5)->create(['purchase_request_id' => $pr->id]);

        $response = $this->actingAs($this->bacUser)->post(route('bac.item-groups.store', $pr), [
            'groups' => [
                [
                    'name' => 'Office Supplies',
                    'items' => [$items[0]->id, $items[1]->id],
                ],
                [
                    'name' => 'IT Equipment',
                    'items' => [$items[2]->id, $items[3]->id, $items[4]->id],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pr_item_groups', [
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
        ]);
        $this->assertDatabaseHas('pr_item_groups', [
            'purchase_request_id' => $pr->id,
            'group_name' => 'IT Equipment',
        ]);
    }

    /**
     * Test that items are correctly assigned to groups
     */
    public function test_items_are_correctly_assigned_to_groups(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        $items = PurchaseRequestItem::factory()->count(3)->create(['purchase_request_id' => $pr->id]);

        $this->actingAs($this->bacUser)->post(route('bac.item-groups.store', $pr), [
            'groups' => [
                [
                    'name' => 'Group 1',
                    'items' => [$items[0]->id, $items[1]->id],
                ],
                [
                    'name' => 'Group 2',
                    'items' => [$items[2]->id],
                ],
            ],
        ]);

        $group1 = PrItemGroup::where('group_name', 'Group 1')->first();
        $group2 = PrItemGroup::where('group_name', 'Group 2')->first();

        $this->assertEquals(2, $group1->items()->count());
        $this->assertEquals(1, $group2->items()->count());
    }

    /**
     * Test that group code is auto-generated
     */
    public function test_group_code_is_auto_generated(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        $items = PurchaseRequestItem::factory()->count(2)->create(['purchase_request_id' => $pr->id]);

        $this->actingAs($this->bacUser)->post(route('bac.item-groups.store', $pr), [
            'groups' => [
                ['name' => 'Group A', 'items' => [$items[0]->id]],
                ['name' => 'Group B', 'items' => [$items[1]->id]],
            ],
        ]);

        $this->assertDatabaseHas('pr_item_groups', [
            'purchase_request_id' => $pr->id,
            'group_code' => 'G1',
        ]);
        $this->assertDatabaseHas('pr_item_groups', [
            'purchase_request_id' => $pr->id,
            'group_code' => 'G2',
        ]);
    }

    /**
     * Test that total cost is calculated correctly for groups
     */
    public function test_group_calculates_total_cost_correctly(): void
    {
        $pr = PurchaseRequest::factory()->create();
        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Cost Group',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'estimated_total_cost' => 1000.00,
        ]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'estimated_total_cost' => 2000.00,
        ]);

        $this->assertEquals(3000.00, $group->fresh()->calculateTotalCost());
    }

    public function test_assigning_lot_header_cascades_to_lot_children(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);

        $lot = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Paper Lot',
            'lot_name' => 'Paper',
            'is_lot' => true,
            'parent_lot_id' => null,
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
            'estimated_unit_cost' => 300.00,
            'estimated_total_cost' => 300.00,
        ]);

        $child1 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'PAPER, MULTICOPY A4',
            'is_lot' => false,
            'parent_lot_id' => $lot->id,
            'unit_of_measure' => 'ream',
            'quantity_requested' => 5,
            'estimated_unit_cost' => 40.00,
            'estimated_total_cost' => 200.00,
        ]);

        $child2 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'PAPER, MULTICOPY LEGAL',
            'is_lot' => false,
            'parent_lot_id' => $lot->id,
            'unit_of_measure' => 'ream',
            'quantity_requested' => 2,
            'estimated_unit_cost' => 50.00,
            'estimated_total_cost' => 100.00,
        ]);

        $standalone = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Air Freshener',
            'is_lot' => false,
            'parent_lot_id' => null,
            'unit_of_measure' => 'can',
            'quantity_requested' => 3,
            'estimated_unit_cost' => 10.00,
            'estimated_total_cost' => 30.00,
        ]);

        $this->actingAs($this->bacUser)->post(route('bac.item-groups.store', $pr), [
            'groups' => [
                [
                    'name' => 'Supplies',
                    'items' => [$lot->id, $standalone->id],
                ],
            ],
        ]);

        $group = PrItemGroup::where('group_name', 'Supplies')->first();

        $this->assertNotNull($group);
        $this->assertEquals($group->id, $lot->fresh()->pr_item_group_id);
        $this->assertEquals($group->id, $child1->fresh()->pr_item_group_id);
        $this->assertEquals($group->id, $child2->fresh()->pr_item_group_id);
        $this->assertEquals($group->id, $standalone->fresh()->pr_item_group_id);
        $this->assertEquals(4, $group->items()->count());
    }

    public function test_create_page_lists_lots_not_children_as_checkboxes(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);

        $lot = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Paper Lot',
            'lot_name' => 'Paper',
            'is_lot' => true,
            'parent_lot_id' => null,
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
        ]);

        $child = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'PAPER, MULTICOPY A4',
            'is_lot' => false,
            'parent_lot_id' => $lot->id,
            'unit_of_measure' => 'ream',
            'quantity_requested' => 5,
        ]);

        $standalone = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Air Freshener',
            'is_lot' => false,
            'parent_lot_id' => null,
            'unit_of_measure' => 'can',
            'quantity_requested' => 3,
        ]);

        $response = $this->actingAs($this->bacUser)
            ->get(route('bac.item-groups.create', $pr));

        $response->assertOk();
        $response->assertSee('Paper');
        $response->assertSee('Air Freshener');
        $response->assertSee('Show items');
        $response->assertSee('value="'.$lot->id.'"', false);
        $response->assertSee('value="'.$standalone->id.'"', false);
        $response->assertDontSee('value="'.$child->id.'"', false);
        $response->assertSee('PAPER, MULTICOPY A4');
    }

    public function test_edit_page_lists_lots_not_children_as_checkboxes(): void
    {
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);

        $group = PrItemGroup::create([
            'purchase_request_id' => $pr->id,
            'group_name' => 'Office Supplies',
            'group_code' => 'G1',
            'display_order' => 1,
        ]);

        $lot = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'item_name' => 'Markers Lot',
            'lot_name' => 'Markers',
            'is_lot' => true,
            'parent_lot_id' => null,
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
        ]);

        $child = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'pr_item_group_id' => $group->id,
            'item_name' => 'MARKER, Whiteboard, Black',
            'is_lot' => false,
            'parent_lot_id' => $lot->id,
            'unit_of_measure' => 'piece',
            'quantity_requested' => 5,
        ]);

        $response = $this->actingAs($this->bacUser)
            ->get(route('bac.item-groups.edit', $pr));

        $response->assertOk();
        $response->assertSee('Markers');
        $response->assertSee('value="'.$lot->id.'"', false);
        $response->assertDontSee('value="'.$child->id.'"', false);
        $response->assertSee('MARKER, Whiteboard, Black');
    }
}
