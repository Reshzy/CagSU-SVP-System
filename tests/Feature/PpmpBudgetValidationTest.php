<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Ppmp;
use App\Models\User;
use App\Services\PpmpBudgetValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpmpBudgetValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ppmp_within_budget_can_be_validated(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        
        DepartmentBudget::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'allocated_budget' => 10000,
        ]);

        $appItem = AppItem::factory()->create([
            'fiscal_year' => date('Y'),
            'unit_price' => 100,
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

        $response = $this->actingAs($user)->post(route('ppmp.validate', $ppmp));

        $response->assertRedirect(route('ppmp.index'));
        $response->assertSessionHas('success');

        $ppmp->refresh();
        $this->assertEquals('validated', $ppmp->status);
        $this->assertNotNull($ppmp->validated_at);
    }

    public function test_ppmp_exceeding_budget_cannot_be_validated(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        
        DepartmentBudget::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'allocated_budget' => 1000,
        ]);

        $appItem = AppItem::factory()->create([
            'fiscal_year' => date('Y'),
            'unit_price' => 100,
        ]);

        $this->actingAs($user)->post(route('ppmp.store'), [
            'items' => [
                [
                    'app_item_id' => $appItem->id,
                    'q1_quantity' => 50,
                    'q2_quantity' => 50,
                    'q3_quantity' => 50,
                    'q4_quantity' => 50,
                ],
            ],
        ]);

        $ppmp = Ppmp::where('department_id', $department->id)->first();

        $response = $this->actingAs($user)->post(route('ppmp.validate', $ppmp));

        $response->assertSessionHasErrors('budget');

        $ppmp->refresh();
        $this->assertEquals('draft', $ppmp->status);
        $this->assertNull($ppmp->validated_at);
    }

    public function test_budget_validator_service_works_correctly(): void
    {
        $department = Department::factory()->create();
        
        DepartmentBudget::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'allocated_budget' => 5000,
        ]);

        $ppmp = Ppmp::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'total_estimated_cost' => 3000,
        ]);

        $validator = new PpmpBudgetValidator();
        
        $this->assertTrue($validator->validatePpmpAgainstBudget($ppmp));

        $ppmp->total_estimated_cost = 6000;
        $ppmp->save();

        $this->assertFalse($validator->validatePpmpAgainstBudget($ppmp));
    }

    public function test_budget_status_provides_correct_information(): void
    {
        $department = Department::factory()->create();
        
        DepartmentBudget::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'allocated_budget' => 10000,
        ]);

        $ppmp = Ppmp::factory()->create([
            'department_id' => $department->id,
            'fiscal_year' => date('Y'),
            'total_estimated_cost' => 7000,
        ]);

        $validator = new PpmpBudgetValidator();
        $status = $validator->getBudgetStatus($ppmp);

        $this->assertEquals(10000, $status['allocated']);
        $this->assertEquals(7000, $status['planned']);
        $this->assertTrue($status['is_within_budget']);
        $this->assertEquals(70, $status['utilization_percentage']);
    }
}
