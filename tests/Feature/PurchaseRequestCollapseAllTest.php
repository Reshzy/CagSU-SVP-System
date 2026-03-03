<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestCollapseAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_request_create_page_shows_collapse_all_control(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $ppmp = Ppmp::factory()
            ->validated()
            ->create([
                'department_id' => $department->id,
                'fiscal_year' => date('Y'),
            ]);

        $appItem = AppItem::factory()->create([
            'fiscal_year' => date('Y'),
            'category' => 'OFFICE SUPPLIES',
            'unit_price' => 10.00,
        ]);

        $currentQuarter = (int) ceil(now()->month / 3);
        $q1 = $currentQuarter === 1 ? 5 : 0;
        $q2 = $currentQuarter === 2 ? 5 : 0;
        $q3 = $currentQuarter === 3 ? 5 : 0;
        $q4 = $currentQuarter === 4 ? 5 : 0;
        $total = $q1 + $q2 + $q3 + $q4;

        PpmpItem::factory()->create([
            'ppmp_id' => $ppmp->id,
            'app_item_id' => $appItem->id,
            'q1_quantity' => $q1,
            'q2_quantity' => $q2,
            'q3_quantity' => $q3,
            'q4_quantity' => $q4,
            'total_quantity' => $total,
            'estimated_unit_cost' => 10.00,
            'estimated_total_cost' => $total * 10.00,
        ]);

        $response = $this->actingAs($user)->get(route('purchase-requests.create'));

        $response->assertStatus(200);
        $response->assertSee('Collapse all');
    }
}
