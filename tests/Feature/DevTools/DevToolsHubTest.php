<?php

namespace Tests\Feature\DevTools;

use App\Livewire\DevTools\Hub;
use App\Models\AppItem;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Document;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestActivity;
use App\Models\User;
use App\Models\WorkflowApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DevToolsHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Department $department;

    protected User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        config(['dev-tools.enabled' => true]);

        Role::findOrCreate('System Admin');
        Role::findOrCreate('End User');
        Role::findOrCreate('Dean');

        $this->department = Department::factory()->create([
            'name' => 'College of Computing',
            'code' => 'CICS',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('System Admin');

        $this->requester = User::factory()->create([
            'department_id' => $this->department->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);
        $this->requester->assignRole('End User');

        DepartmentBudget::factory()->create([
            'department_id' => $this->department->id,
            'fiscal_year' => (int) date('Y'),
            'allocated_budget' => 500000,
            'reserved_budget' => 0,
            'utilized_budget' => 0,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_can_create_purchase_request_for_selected_requester(): void
    {
        $ppmp = Ppmp::factory()->validated()->create([
            'department_id' => $this->department->id,
            'fiscal_year' => (int) date('Y'),
            'total_estimated_cost' => 1000,
        ]);

        $appItem = AppItem::factory()->create([
            'fiscal_year' => (int) date('Y'),
            'item_name' => 'Bond Paper A4',
            'item_code' => 'BP-A4',
            'unit_of_measure' => 'ream',
            'unit_price' => 250,
        ]);

        $ppmpItem = PpmpItem::factory()->create([
            'ppmp_id' => $ppmp->id,
            'app_item_id' => $appItem->id,
            'q1_quantity' => 10,
            'q2_quantity' => 10,
            'q3_quantity' => 10,
            'q4_quantity' => 10,
            'total_quantity' => 40,
            'estimated_unit_cost' => 250,
            'estimated_total_cost' => 10000,
        ]);

        Livewire::test(Hub::class)
            ->set('departmentId', $this->department->id)
            ->set('requesterId', $this->requester->id)
            ->set('purpose', 'Dev tools test PR')
            ->set('justification', 'Needed for automated testing')
            ->set('landingStatus', 'supply_office_review')
            ->set('itemMode', 'ppmp')
            ->call('togglePpmpItem', $ppmpItem->id)
            ->set('createStep', 4)
            ->call('createPurchaseRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('purchase_requests', [
            'purpose' => 'Dev tools test PR',
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'supply_office_review',
        ]);

        $pr = PurchaseRequest::query()->where('purpose', 'Dev tools test PR')->first();
        $this->assertNotNull($pr);
        $this->assertSame(1, $pr->items()->count());
        $this->assertSame($ppmpItem->id, $pr->items()->first()->ppmp_item_id);

        $this->assertTrue(
            PurchaseRequestActivity::query()
                ->where('purchase_request_id', $pr->id)
                ->where('user_id', $this->admin->id)
                ->where('description', 'like', 'Created via Dev Tools%')
                ->exists()
        );

        $budget = DepartmentBudget::query()
            ->where('department_id', $this->department->id)
            ->where('fiscal_year', (int) date('Y'))
            ->first();

        $this->assertGreaterThan(0, (float) $budget->reserved_budget);
    }

    public function test_can_create_purchase_request_with_manual_items(): void
    {
        Livewire::test(Hub::class)
            ->set('departmentId', $this->department->id)
            ->set('requesterId', $this->requester->id)
            ->set('purpose', 'Manual item PR')
            ->set('justification', 'Manual path testing')
            ->set('landingStatus', 'supply_office_review')
            ->set('itemMode', 'manual')
            ->set('manualItems', [[
                'item_name' => 'Test Stapler',
                'unit_of_measure' => 'pcs',
                'quantity_requested' => 2,
                'estimated_unit_cost' => 150,
                'detailed_specifications' => 'Heavy duty',
            ]])
            ->set('createStep', 4)
            ->call('createPurchaseRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('purchase_requests', [
            'purpose' => 'Manual item PR',
            'requester_id' => $this->requester->id,
        ]);

        $pr = PurchaseRequest::query()->where('purpose', 'Manual item PR')->first();
        $this->assertSame('Test Stapler', $pr->items()->first()->item_name);
    }

    public function test_can_create_purchase_request_with_lot(): void
    {
        Livewire::test(Hub::class)
            ->set('departmentId', $this->department->id)
            ->set('requesterId', $this->requester->id)
            ->set('purpose', 'Lot PR test')
            ->set('justification', 'Lot grouping via Dev Tools')
            ->set('landingStatus', 'supply_office_review')
            ->set('itemMode', 'manual')
            ->set('groupAsLot', true)
            ->set('lotName', 'Office Supplies Lot')
            ->set('manualItems', [
                [
                    'item_name' => 'Bond Paper',
                    'unit_of_measure' => 'ream',
                    'quantity_requested' => 5,
                    'estimated_unit_cost' => 200,
                    'detailed_specifications' => '',
                ],
                [
                    'item_name' => 'Ballpen',
                    'unit_of_measure' => 'box',
                    'quantity_requested' => 3,
                    'estimated_unit_cost' => 100,
                    'detailed_specifications' => '',
                ],
            ])
            ->set('createStep', 4)
            ->call('createPurchaseRequest')
            ->assertHasNoErrors();

        $pr = PurchaseRequest::query()->where('purpose', 'Lot PR test')->first();
        $this->assertNotNull($pr);

        $lotHeader = $pr->items()->where('is_lot', true)->first();
        $this->assertNotNull($lotHeader);
        $this->assertSame('Office Supplies Lot', $lotHeader->lot_name);
        $this->assertSame('lot', $lotHeader->unit_of_measure);
        $this->assertEquals(1300.0, (float) $lotHeader->estimated_unit_cost);

        $children = $pr->items()->where('parent_lot_id', $lotHeader->id)->get();
        $this->assertCount(2, $children);
        $this->assertTrue($children->contains(fn ($item) => $item->item_name === 'Bond Paper'));
        $this->assertTrue($children->contains(fn ($item) => $item->item_name === 'Ballpen'));
    }

    public function test_can_set_department_budget(): void
    {
        Livewire::test(Hub::class)
            ->set('fiscalYear', (int) date('Y'))
            ->call('startEditBudget', $this->department->id)
            ->set('editingAllocatedBudget', '750000')
            ->set('editingBudgetNotes', 'Dev tools allocation')
            ->call('saveBudget')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('department_budgets', [
            'department_id' => $this->department->id,
            'fiscal_year' => (int) date('Y'),
            'allocated_budget' => 750000,
            'notes' => 'Dev tools allocation',
            'set_by' => $this->admin->id,
        ]);
    }

    public function test_can_jump_purchase_request_status(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'supply_office_review',
            'estimated_total' => 100,
        ]);

        Livewire::test(Hub::class)
            ->set('departmentId', $this->department->id)
            ->set('tab', 'workflow')
            ->call('selectPurchaseRequest', $pr->id)
            ->set('workflowTargetStatus', 'ceo_approval')
            ->call('applyWorkflowStatus')
            ->assertHasNoErrors();

        $pr->refresh();
        $this->assertSame('ceo_approval', $pr->status);

        $this->assertTrue(
            PurchaseRequestActivity::query()
                ->where('purchase_request_id', $pr->id)
                ->where('user_id', $this->admin->id)
                ->where('description', 'like', 'Dev Tools override%')
                ->exists()
        );
    }

    public function test_jump_preset_updates_status(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'supply_office_review',
            'estimated_total' => 100,
        ]);

        Livewire::test(Hub::class)
            ->call('selectPurchaseRequest', $pr->id)
            ->call('jumpPreset', 'bac')
            ->assertHasNoErrors()
            ->assertSet('workflowTargetStatus', 'bac_evaluation');

        $this->assertSame('bac_evaluation', $pr->fresh()->status);
    }

    public function test_jump_to_bac_sets_procurement_method_and_generates_resolution(): void
    {
        Storage::fake('local');

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'ceo_approval',
            'estimated_total' => 2500,
            'purpose' => 'BAC jump test',
            'procurement_method' => null,
            'resolution_number' => null,
        ]);

        Livewire::test(Hub::class)
            ->call('selectPurchaseRequest', $pr->id)
            ->call('jumpPreset', 'bac')
            ->assertHasNoErrors();

        $pr->refresh();

        $this->assertSame('bac_evaluation', $pr->status);
        $this->assertSame('small_value_procurement', $pr->procurement_method);
        $this->assertNotEmpty($pr->resolution_number);

        $this->assertTrue(
            WorkflowApproval::query()
                ->where('purchase_request_id', $pr->id)
                ->where('step_name', 'ceo_initial_approval')
                ->where('status', 'approved')
                ->exists()
        );

        $this->assertTrue(
            Document::query()
                ->where('documentable_type', PurchaseRequest::class)
                ->where('documentable_id', $pr->id)
                ->where('document_type', 'bac_resolution')
                ->exists()
        );
    }

    public function test_can_validate_ppmp_from_hub(): void
    {
        $ppmp = Ppmp::factory()->create([
            'department_id' => $this->department->id,
            'fiscal_year' => (int) date('Y'),
            'status' => 'draft',
            'total_estimated_cost' => 1000,
        ]);

        Livewire::test(Hub::class)
            ->set('departmentId', $this->department->id)
            ->set('fiscalYear', (int) date('Y'))
            ->call('validatePpmp')
            ->assertHasNoErrors();

        $this->assertSame('validated', $ppmp->fresh()->status);
    }

    public function test_can_import_ppmp_csv(): void
    {
        AppItem::factory()->create([
            'fiscal_year' => (int) date('Y'),
            'item_code' => 'PEN-001',
            'item_name' => 'Ballpoint Pen',
            'unit_of_measure' => 'box',
            'unit_price' => 50,
            'category' => 'OFFICE SUPPLIES',
            'is_active' => true,
        ]);

        $csv = $this->buildMinimalPpmpCsv([
            [
                'row' => 1,
                'item_code' => 'PEN-001',
                'item_name' => 'Ballpoint Pen',
                'uom' => 'box',
                'q1' => 5,
                'q2' => 5,
                'q3' => 5,
                'q4' => 5,
                'price' => 50,
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('devtools_ppmp.csv', $csv);

        Livewire::test(Hub::class)
            ->set('departmentId', $this->department->id)
            ->set('fiscalYear', (int) date('Y'))
            ->set('csvFile', $file)
            ->call('importPpmp')
            ->assertHasNoErrors();

        $this->assertTrue(
            Ppmp::query()
                ->where('department_id', $this->department->id)
                ->where('fiscal_year', (int) date('Y'))
                ->exists()
        );
    }

    public function test_system_admin_can_view_another_department_ppmp_summary(): void
    {
        $adminDepartment = Department::factory()->create([
            'name' => 'Administrative Office',
            'code' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->admin->update(['department_id' => $adminDepartment->id]);

        Ppmp::factory()->create([
            'department_id' => $adminDepartment->id,
            'fiscal_year' => (int) date('Y'),
            'status' => 'validated',
            'total_estimated_cost' => 500,
        ]);

        $targetDepartment = Department::factory()->create([
            'name' => 'Calayan Extension',
            'code' => 'CALAYAN',
            'is_active' => true,
        ]);

        DepartmentBudget::factory()->create([
            'department_id' => $targetDepartment->id,
            'fiscal_year' => (int) date('Y'),
            'allocated_budget' => 100000,
        ]);

        $targetPpmp = Ppmp::factory()->create([
            'department_id' => $targetDepartment->id,
            'fiscal_year' => (int) date('Y'),
            'status' => 'validated',
            'total_estimated_cost' => 1000,
        ]);

        $this->actingAs($this->admin)
            ->get(route('ppmp.summary', $targetPpmp))
            ->assertOk()
            ->assertSee('PPMP Summary', false)
            ->assertSee('Calayan Extension', false);
    }

    public function test_end_user_cannot_view_another_department_ppmp_summary(): void
    {
        $otherDepartment = Department::factory()->create([
            'name' => 'Other College',
            'code' => 'OTHER',
            'is_active' => true,
        ]);

        DepartmentBudget::factory()->create([
            'department_id' => $otherDepartment->id,
            'fiscal_year' => (int) date('Y'),
            'allocated_budget' => 100000,
        ]);

        $otherPpmp = Ppmp::factory()->create([
            'department_id' => $otherDepartment->id,
            'fiscal_year' => (int) date('Y'),
            'status' => 'validated',
            'total_estimated_cost' => 1000,
        ]);

        $this->actingAs($this->requester)
            ->get(route('ppmp.summary', $otherPpmp))
            ->assertRedirect(route('ppmp.index'));
    }

    /**
     * @param  list<array{row: int, item_code: string, item_name: string, uom: string, q1: int, q2: int, q3: int, q4: int, price: float}>  $items
     */
    private function buildMinimalPpmpCsv(array $items, string $category = 'OFFICE SUPPLIES'): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, array_merge(['APP-CSE 2025 FORM'], array_fill(0, 26, '')));
        fputcsv($handle, array_fill(0, 27, ''));
        fputcsv($handle, array_merge([$category], array_fill(0, 26, '')));

        foreach ($items as $item) {
            $q1 = $item['q1'];
            $q2 = $item['q2'];
            $q3 = $item['q3'];
            $q4 = $item['q4'];
            $price = $item['price'];
            $total = $q1 + $q2 + $q3 + $q4;

            fputcsv($handle, [
                $item['row'],
                $item['item_code'],
                $item['item_name'],
                $item['uom'],
                0, 0, $q1, $q1,
                0,
                0, 0, $q2, $q2,
                0,
                0, 0, $q3, $q3,
                0,
                0, 0, $q4, $q4,
                0,
                $total,
                $price,
                0,
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }
}
