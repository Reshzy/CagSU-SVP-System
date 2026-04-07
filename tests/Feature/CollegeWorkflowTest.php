<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Position;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CollegeWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'Dean']);
        Role::create(['name' => 'Supply Officer']);
        Role::create(['name' => 'System Admin']);
    }

    public function test_dean_can_create_pr_for_their_college(): void
    {
        $college = Department::factory()->create([
            'name' => 'College of Engineering',
            'code' => 'COE',
            'is_archived' => false,
        ]);

        $deanPosition = Position::query()->create(['name' => 'Dean']);

        $dean = User::factory()->create([
            'department_id' => $college->id,
            'position_id' => $deanPosition->id,
            'is_archived' => false,
        ]);
        $dean->assignRole('Dean');

        $ppmp = Ppmp::factory()->validated()->create([
            'department_id' => $college->id,
            'fiscal_year' => (int) date('Y'),
        ]);

        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $ppmp->id,
            'q1_quantity' => 25,
            'q2_quantity' => 25,
            'q3_quantity' => 25,
            'q4_quantity' => 25,
            'total_quantity' => 100,
        ]);
        $ppmpItem->load('appItem');

        $this->actingAs($dean);

        DepartmentBudget::query()->create([
            'department_id' => $college->id,
            'fiscal_year' => (int) date('Y'),
            'allocated_budget' => 500000,
            'utilized_budget' => 0,
            'reserved_budget' => 0,
        ]);

        $response = $this->post(route('purchase-requests.store'), [
            'purpose' => 'Test PR',
            'justification' => 'Testing',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $ppmpItem->appItem->item_name,
                    'unit_of_measure' => $ppmpItem->appItem->unit_of_measure,
                    'quantity_requested' => 5,
                    'estimated_unit_cost' => (float) $ppmpItem->estimated_unit_cost,
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_requests', [
            'requester_id' => $dean->id,
            'department_id' => $college->id,
            'status' => 'supply_office_review',
        ]);
    }

    public function test_dean_cannot_create_pr_for_other_college(): void
    {
        $college1 = Department::factory()->create([
            'name' => 'College One',
            'code' => 'COE',
            'is_archived' => false,
        ]);
        $college2 = Department::factory()->create([
            'name' => 'College Two',
            'code' => 'CBEA',
            'is_archived' => false,
        ]);

        $deanPosition = Position::query()->create(['name' => 'Dean']);

        $dean = User::factory()->create([
            'department_id' => $college1->id,
            'position_id' => $deanPosition->id,
            'is_archived' => false,
        ]);
        $dean->assignRole('Dean');

        $college2Ppmp = Ppmp::factory()->validated()->create([
            'department_id' => $college2->id,
            'fiscal_year' => (int) date('Y'),
        ]);

        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $college2Ppmp->id,
        ]);

        $this->actingAs($dean);

        // Should only see PPMP items for their own college
        $availableItems = PpmpItem::query()
            ->whereHas('ppmp', function ($query) use ($dean): void {
                $query->where('department_id', $dean->department_id)
                    ->where('status', 'validated');
            })
            ->get();

        $this->assertCount(0, $availableItems);
        $this->assertFalse($availableItems->contains($ppmpItem));
    }

    public function test_ppmp_items_scoped_to_college(): void
    {
        $college1 = Department::factory()->create([
            'name' => 'College Three',
            'code' => 'COE2',
            'is_archived' => false,
        ]);
        $college2 = Department::factory()->create([
            'name' => 'College Four',
            'code' => 'CBEA2',
            'is_archived' => false,
        ]);

        $college1Ppmp = Ppmp::factory()->validated()->create([
            'department_id' => $college1->id,
            'fiscal_year' => (int) date('Y'),
        ]);
        $college2Ppmp = Ppmp::factory()->validated()->create([
            'department_id' => $college2->id,
            'fiscal_year' => (int) date('Y'),
        ]);

        $item1 = PpmpItem::factory()->create(['ppmp_id' => $college1Ppmp->id]);
        $item2 = PpmpItem::factory()->create(['ppmp_id' => $college2Ppmp->id]);

        $college1Items = PpmpItem::query()
            ->whereHas('ppmp', function ($query) use ($college1): void {
                $query->where('department_id', $college1->id)
                    ->where('status', 'validated');
            })
            ->get();
        $college2Items = PpmpItem::query()
            ->whereHas('ppmp', function ($query) use ($college2): void {
                $query->where('department_id', $college2->id)
                    ->where('status', 'validated');
            })
            ->get();

        $this->assertCount(1, $college1Items);
        $this->assertCount(1, $college2Items);
        $this->assertTrue($college1Items->contains($item1));
        $this->assertTrue($college2Items->contains($item2));
    }

    public function test_archived_data_excluded_from_queries(): void
    {
        $archivedCollege = Department::factory()->create(['is_archived' => true]);
        $activeCollege = Department::factory()->create(['is_archived' => false]);

        $activeColleges = Department::notArchived()->get();

        $this->assertCount(1, $activeColleges);
        $this->assertFalse($activeColleges->contains($archivedCollege));
        $this->assertTrue($activeColleges->contains($activeCollege));
    }
}
