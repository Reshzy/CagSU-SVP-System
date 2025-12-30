<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
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

        $deanPosition = Position::factory()->create(['name' => 'Dean']);

        $dean = User::factory()->create([
            'department_id' => $college->id,
            'position_id' => $deanPosition->id,
            'is_archived' => false,
        ]);
        $dean->assignRole('Dean');

        $ppmpItem = PpmpItem::factory()->create([
            'college_id' => $college->id,
            'is_active' => true,
        ]);

        $this->actingAs($dean);

        $response = $this->post(route('purchase-requests.store'), [
            'purpose' => 'Test PR',
            'justification' => 'Testing',
            'items' => [
                [
                    'ppmp_item_id' => $ppmpItem->id,
                    'item_name' => $ppmpItem->item_name,
                    'unit_of_measure' => $ppmpItem->unit_of_measure,
                    'quantity_requested' => 5,
                    'estimated_unit_cost' => 100,
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
        $college1 = Department::factory()->create(['code' => 'COE', 'is_archived' => false]);
        $college2 = Department::factory()->create(['code' => 'CBEA', 'is_archived' => false]);

        $deanPosition = Position::factory()->create(['name' => 'Dean']);

        $dean = User::factory()->create([
            'department_id' => $college1->id,
            'position_id' => $deanPosition->id,
            'is_archived' => false,
        ]);
        $dean->assignRole('Dean');

        $ppmpItem = PpmpItem::factory()->create([
            'college_id' => $college2->id,
            'is_active' => true,
        ]);

        $this->actingAs($dean);

        // Should only see PPMP items for their own college
        $availableItems = PpmpItem::active()
            ->forCollege($dean->department_id)
            ->get();

        $this->assertCount(0, $availableItems);
        $this->assertFalse($availableItems->contains($ppmpItem));
    }

    public function test_ppmp_items_scoped_to_college(): void
    {
        $college1 = Department::factory()->create(['code' => 'COE', 'is_archived' => false]);
        $college2 = Department::factory()->create(['code' => 'CBEA', 'is_archived' => false]);

        $item1 = PpmpItem::factory()->create(['college_id' => $college1->id, 'is_active' => true]);
        $item2 = PpmpItem::factory()->create(['college_id' => $college2->id, 'is_active' => true]);

        $college1Items = PpmpItem::active()->forCollege($college1->id)->get();
        $college2Items = PpmpItem::active()->forCollege($college2->id)->get();

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
