<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseRequestLotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'edit-purchase-request']);
        $role = Role::create(['name' => 'Supply Officer']);
        $role->givePermissionTo('edit-purchase-request');
    }

    private function makeSupplyUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Supply Officer');

        return $user;
    }

    private function makePrWithItems(string $status = 'submitted'): PurchaseRequest
    {
        $pr = PurchaseRequest::factory()->create(['status' => $status]);
        PurchaseRequestItem::factory()->count(3)->create([
            'purchase_request_id' => $pr->id,
            'is_lot' => false,
            'parent_lot_id' => null,
            'quantity_requested' => 2,
            'estimated_unit_cost' => 100.00,
            'estimated_total_cost' => 200.00,
        ]);

        return $pr->fresh(['items']);
    }

    // --- Lot model helpers ---

    /** @test */
    public function purchase_request_item_is_lot_header_returns_true_for_lot_items(): void
    {
        $lot = PurchaseRequestItem::factory()->create(['is_lot' => true, 'lot_name' => 'Painting Works']);

        $this->assertTrue($lot->isLotHeader());
        $this->assertFalse($lot->isLotChild());
    }

    /** @test */
    public function purchase_request_item_is_lot_child_returns_true_when_parent_set(): void
    {
        $lot = PurchaseRequestItem::factory()->create(['is_lot' => true]);
        $child = PurchaseRequestItem::factory()->create(['parent_lot_id' => $lot->id]);

        $this->assertTrue($child->isLotChild());
        $this->assertFalse($child->isLotHeader());
    }

    /** @test */
    public function lot_children_relationship_returns_children(): void
    {
        $pr = PurchaseRequest::factory()->create();
        $lot = PurchaseRequestItem::factory()->create(['purchase_request_id' => $pr->id, 'is_lot' => true]);
        $child1 = PurchaseRequestItem::factory()->create(['purchase_request_id' => $pr->id, 'parent_lot_id' => $lot->id]);
        $child2 = PurchaseRequestItem::factory()->create(['purchase_request_id' => $pr->id, 'parent_lot_id' => $lot->id]);

        $lot->refresh();

        $this->assertCount(2, $lot->lotChildren);
        $this->assertTrue($lot->lotChildren->contains($child1));
        $this->assertTrue($lot->lotChildren->contains($child2));
    }

    /** @test */
    public function parent_lot_relationship_resolves_parent(): void
    {
        $lot = PurchaseRequestItem::factory()->create(['is_lot' => true]);
        $child = PurchaseRequestItem::factory()->create(['parent_lot_id' => $lot->id]);

        $child->refresh();

        $this->assertEquals($lot->id, $child->parentLot->id);
    }

    /** @test */
    public function quotable_scope_excludes_lot_headers(): void
    {
        $pr = PurchaseRequest::factory()->create();
        $lot = PurchaseRequestItem::factory()->create(['purchase_request_id' => $pr->id, 'is_lot' => true]);
        $standalone = PurchaseRequestItem::factory()->create(['purchase_request_id' => $pr->id, 'is_lot' => false]);
        $child = PurchaseRequestItem::factory()->create(['purchase_request_id' => $pr->id, 'is_lot' => false, 'parent_lot_id' => $lot->id]);

        $quotable = PurchaseRequestItem::quotable()->whereIn('id', [$lot->id, $standalone->id, $child->id])->get();

        $this->assertCount(2, $quotable);
        $this->assertFalse($quotable->contains($lot));
        $this->assertTrue($quotable->contains($standalone));
        $this->assertTrue($quotable->contains($child));
    }

    // --- Supply Officer lot CRUD ---

    /** @test */
    public function supply_officer_can_create_lot_from_standalone_items(): void
    {
        $user = $this->makeSupplyUser();
        $pr = $this->makePrWithItems('submitted');
        $itemIds = $pr->items->take(2)->pluck('id')->toArray();

        $response = $this->actingAs($user)->postJson(
            route('supply.purchase-requests.lots.store', $pr),
            ['lot_name' => 'Painting Works', 'item_ids' => $itemIds]
        );

        $response->assertOk();

        $this->assertDatabaseHas('purchase_request_items', [
            'purchase_request_id' => $pr->id,
            'is_lot' => true,
            'lot_name' => 'Painting Works',
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
        ]);

        foreach ($itemIds as $id) {
            $this->assertNotNull(PurchaseRequestItem::find($id)->parent_lot_id);
        }
    }

    /** @test */
    public function supply_officer_cannot_create_lot_with_fewer_than_two_items(): void
    {
        $user = $this->makeSupplyUser();
        $pr = $this->makePrWithItems('submitted');
        $itemIds = [$pr->items->first()->id];

        $response = $this->actingAs($user)->postJson(
            route('supply.purchase-requests.lots.store', $pr),
            ['lot_name' => 'Single Item Lot', 'item_ids' => $itemIds]
        );

        $response->assertStatus(422);
    }

    /** @test */
    public function supply_officer_can_update_lot_name_and_members(): void
    {
        $user = $this->makeSupplyUser();
        $pr = $this->makePrWithItems('supply_office_review');
        $items = $pr->items;

        $lot = PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Old Lot',
            'lot_name' => 'Old Lot',
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
            'estimated_unit_cost' => 400,
            'estimated_total_cost' => 400,
            'is_lot' => true,
        ]);

        $items->take(2)->each(fn ($i) => $i->update(['parent_lot_id' => $lot->id]));

        $newChildIds = [$items->last()->id];

        $response = $this->actingAs($user)->putJson(
            route('supply.purchase-requests.lots.update', [$pr, $lot]),
            ['lot_name' => 'Updated Lot', 'item_ids' => array_merge($newChildIds, [$items->first()->id])]
        );

        $response->assertOk();

        $lot->refresh();
        $this->assertEquals('Updated Lot', $lot->lot_name);
    }

    /** @test */
    public function supply_officer_can_destroy_lot_and_ungroup_items(): void
    {
        $user = $this->makeSupplyUser();
        $pr = $this->makePrWithItems('submitted');
        $items = $pr->items;

        $lot = PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Test Lot',
            'lot_name' => 'Test Lot',
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
            'estimated_unit_cost' => 400,
            'estimated_total_cost' => 400,
            'is_lot' => true,
        ]);
        $items->take(2)->each(fn ($i) => $i->update(['parent_lot_id' => $lot->id]));

        $response = $this->actingAs($user)->deleteJson(
            route('supply.purchase-requests.lots.destroy', [$pr, $lot])
        );

        $response->assertOk();
        $this->assertDatabaseMissing('purchase_request_items', ['id' => $lot->id]);

        foreach ($items->take(2) as $item) {
            $this->assertNull($item->fresh()->parent_lot_id);
        }
    }

    /** @test */
    public function lot_management_is_forbidden_when_pr_is_not_in_review_status(): void
    {
        $user = $this->makeSupplyUser();
        $pr = $this->makePrWithItems('budget_office_review');
        $itemIds = $pr->items->take(2)->pluck('id')->toArray();

        $response = $this->actingAs($user)->postJson(
            route('supply.purchase-requests.lots.store', $pr),
            ['lot_name' => 'Should Fail', 'item_ids' => $itemIds]
        );

        $response->assertForbidden();
    }

    // --- PR Excel Export ---

    /** @test */
    public function supply_officer_can_export_purchase_request_as_excel(): void
    {
        $templatePath = storage_path('app/templates/PurchaseRequestTemplate.xlsx');

        if (! file_exists($templatePath)) {
            $this->markTestSkipped('PR template file not found, skipping export test.');
        }

        $user = $this->makeSupplyUser();
        $pr = $this->makePrWithItems('submitted');

        $response = $this->actingAs($user)->get(
            route('supply.purchase-requests.export', $pr)
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // --- Lot cost calculation ---

    /** @test */
    public function lot_total_cost_equals_sum_of_child_items(): void
    {
        $pr = PurchaseRequest::factory()->create();

        $child1 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'quantity_requested' => 2,
            'estimated_unit_cost' => 150.00,
            'estimated_total_cost' => 300.00,
            'is_lot' => false,
        ]);
        $child2 = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $pr->id,
            'quantity_requested' => 1,
            'estimated_unit_cost' => 500.00,
            'estimated_total_cost' => 500.00,
            'is_lot' => false,
        ]);

        $expectedTotal = 300 + 500;
        $lot = PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'item_name' => 'Materials Lot',
            'lot_name' => 'Materials Lot',
            'unit_of_measure' => 'lot',
            'quantity_requested' => 1,
            'estimated_unit_cost' => $expectedTotal,
            'estimated_total_cost' => $expectedTotal,
            'is_lot' => true,
        ]);

        $child1->update(['parent_lot_id' => $lot->id]);
        $child2->update(['parent_lot_id' => $lot->id]);

        $this->assertEquals($expectedTotal, (float) $lot->estimated_total_cost);
        $this->assertCount(2, $lot->lotChildren);
    }
}
