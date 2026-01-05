<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Ppmp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpmpCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_ppmp_index(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($user)->get(route('ppmp.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_create_ppmp_with_app_items(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        
        // Set budget
        DepartmentBudget::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'allocated_budget' => 100000,
        ]);

        // Create APP items
        $appItem1 = AppItem::factory()->create([
            'fiscal_year' => date('Y'),
            'unit_price' => 100,
        ]);
        $appItem2 = AppItem::factory()->create([
            'fiscal_year' => date('Y'),
            'unit_price' => 200,
        ]);

        $response = $this->actingAs($user)->post(route('ppmp.store'), [
            'items' => [
                [
                    'app_item_id' => $appItem1->id,
                    'q1_quantity' => 10,
                    'q2_quantity' => 20,
                    'q3_quantity' => 15,
                    'q4_quantity' => 5,
                ],
                [
                    'app_item_id' => $appItem2->id,
                    'q1_quantity' => 5,
                    'q2_quantity' => 10,
                    'q3_quantity' => 5,
                    'q4_quantity' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('ppmp.index'));
        
        $ppmp = Ppmp::where('department_id', $department->id)->first();
        $this->assertNotNull($ppmp);
        $this->assertEquals(2, $ppmp->items->count());
        $this->assertEquals(50 * 100 + 20 * 200, $ppmp->total_estimated_cost);
    }

    public function test_ppmp_calculates_totals_correctly(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        
        DepartmentBudget::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'allocated_budget' => 100000,
        ]);

        $appItem = AppItem::factory()->create([
            'fiscal_year' => date('Y'),
            'unit_price' => 150,
        ]);

        $this->actingAs($user)->post(route('ppmp.store'), [
            'items' => [
                [
                    'app_item_id' => $appItem->id,
                    'q1_quantity' => 10,
                    'q2_quantity' => 10,
                    'q3_quantity' => 10,
                    'q4_quantity' => 10,
                ],
            ],
        ]);

        $ppmp = Ppmp::where('department_id', $department->id)->first();
        $ppmpItem = $ppmp->items->first();

        $this->assertEquals(40, $ppmpItem->total_quantity);
        $this->assertEquals(150, $ppmpItem->estimated_unit_cost);
        $this->assertEquals(6000, $ppmpItem->estimated_total_cost);
    }
}
