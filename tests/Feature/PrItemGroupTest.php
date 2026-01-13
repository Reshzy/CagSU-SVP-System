<?php

namespace Tests\Feature;

use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrItemGroupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that BAC officer can create item groups
     */
    public function test_bac_officer_can_create_item_groups(): void
    {
        $user = User::factory()->create();
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        $items = PurchaseRequestItem::factory()->count(5)->create(['purchase_request_id' => $pr->id]);

        $response = $this->actingAs($user)->post(route('bac.item-groups.store', $pr), [
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
        $user = User::factory()->create();
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        $items = PurchaseRequestItem::factory()->count(3)->create(['purchase_request_id' => $pr->id]);

        $this->actingAs($user)->post(route('bac.item-groups.store', $pr), [
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
        $user = User::factory()->create();
        $pr = PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        $items = PurchaseRequestItem::factory()->count(2)->create(['purchase_request_id' => $pr->id]);

        $this->actingAs($user)->post(route('bac.item-groups.store', $pr), [
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
        $group = PrItemGroup::factory()->create(['purchase_request_id' => $pr->id]);

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
}
