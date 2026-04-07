<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrReturnReplacementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Create roles
        Role::findOrCreate('Dean');
        Role::findOrCreate('Supply Officer');
        Role::findOrCreate('System Admin');
    }

    public function test_supply_officer_can_return_pr_with_remarks(): void
    {
        $college = Department::factory()->create(['is_archived' => false]);
        $supplyPosition = Position::query()->create(['name' => 'Supply Officer']);

        $supplyOfficer = User::factory()->create([
            'position_id' => $supplyPosition->id,
            'is_archived' => false,
        ]);
        $supplyOfficer->assignRole('Supply Officer');

        $pr = PurchaseRequest::factory()->create([
            'status' => 'supply_office_review',
            'department_id' => $college->id,
        ]);

        $this->actingAs($supplyOfficer);

        $response = $this->put(route('supply.purchase-requests.status', $pr), [
            'action' => 'return',
            'return_remarks' => 'Specifications incomplete',
        ]);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'status' => 'returned_by_supply',
            'return_remarks' => 'Specifications incomplete',
            'returned_by' => $supplyOfficer->id,
        ]);

        $this->assertNotNull($pr->fresh()->returned_at);
    }

    public function test_supply_officer_can_activate_pr(): void
    {
        $college = Department::factory()->create(['is_archived' => false]);
        $supplyPosition = Position::query()->create(['name' => 'Supply Officer']);

        $supplyOfficer = User::factory()->create([
            'position_id' => $supplyPosition->id,
            'is_archived' => false,
        ]);
        $supplyOfficer->assignRole('Supply Officer');

        $pr = PurchaseRequest::factory()->create([
            'status' => 'supply_office_review',
            'department_id' => $college->id,
        ]);

        $this->actingAs($supplyOfficer);

        $response = $this->put(route('supply.purchase-requests.status', $pr), [
            'action' => 'activate',
            'notes' => 'PR validated and activated',
        ]);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'status' => 'budget_office_review',
        ]);
    }

    public function test_dean_can_create_replacement_pr(): void
    {
        $college = Department::factory()->create(['is_archived' => false]);
        $deanPosition = Position::query()->create(['name' => 'Dean']);

        $dean = User::factory()->create([
            'department_id' => $college->id,
            'position_id' => $deanPosition->id,
            'is_archived' => false,
        ]);
        $dean->assignRole('Dean');

        $originalPr = PurchaseRequest::factory()->create([
            'requester_id' => $dean->id,
            'department_id' => $college->id,
            'status' => 'returned_by_supply',
            'return_remarks' => 'Need more details',
        ]);

        $ppmp = Ppmp::factory()->validated()->create([
            'department_id' => $college->id,
            'fiscal_year' => (int) date('Y'),
        ]);
        PpmpItem::factory()->create(['ppmp_id' => $ppmp->id]);

        $this->actingAs($dean);

        $response = $this->get(route('purchase-requests.replacement.create', $originalPr));
        $response->assertStatus(200);
    }

    public function test_replacement_pr_links_to_original(): void
    {
        $college = Department::factory()->create(['is_archived' => false]);
        $deanPosition = Position::query()->create(['name' => 'Dean']);

        $dean = User::factory()->create([
            'department_id' => $college->id,
            'position_id' => $deanPosition->id,
            'is_archived' => false,
        ]);
        $dean->assignRole('Dean');

        $originalPr = PurchaseRequest::factory()->create([
            'requester_id' => $dean->id,
            'department_id' => $college->id,
            'status' => 'returned_by_supply',
            'return_remarks' => 'Need more details',
        ]);

        // Simulate creating a replacement
        $replacementPr = PurchaseRequest::factory()->create([
            'requester_id' => $dean->id,
            'department_id' => $college->id,
            'status' => 'supply_office_review',
            'replaces_pr_id' => $originalPr->id,
        ]);

        $originalPr->update([
            'replaced_by_pr_id' => $replacementPr->id,
            'is_archived' => true,
        ]);

        $this->assertEquals($originalPr->id, $replacementPr->replaces_pr_id);
        $this->assertEquals($replacementPr->id, $originalPr->fresh()->replaced_by_pr_id);
        $this->assertTrue((bool) $originalPr->fresh()->is_archived);
    }

    public function test_returned_prs_do_not_appear_in_active_list(): void
    {
        $college = Department::factory()->create(['is_archived' => false]);

        $activePr = PurchaseRequest::factory()->create([
            'status' => 'supply_office_review',
            'department_id' => $college->id,
            'is_archived' => false,
        ]);

        $returnedPr = PurchaseRequest::factory()->create([
            'status' => 'returned_by_supply',
            'department_id' => $college->id,
            'is_archived' => false,
        ]);

        $activePrs = PurchaseRequest::activeStatus()->notArchived()->get();
        $returnedPrs = PurchaseRequest::returned()->notArchived()->get();

        $this->assertTrue($activePrs->contains($activePr));
        $this->assertFalse($activePrs->contains($returnedPr));
        $this->assertTrue($returnedPrs->contains($returnedPr));
    }

    public function test_archived_prs_excluded_from_queries(): void
    {
        $college = Department::factory()->create(['is_archived' => false]);

        $activePr = PurchaseRequest::factory()->create([
            'status' => 'supply_office_review',
            'department_id' => $college->id,
            'is_archived' => false,
        ]);

        $archivedPr = PurchaseRequest::factory()->create([
            'status' => 'returned_by_supply',
            'department_id' => $college->id,
            'is_archived' => true,
        ]);

        $nonArchivedPrs = PurchaseRequest::notArchived()->get();

        $this->assertTrue($nonArchivedPrs->contains($activePr));
        $this->assertFalse($nonArchivedPrs->contains($archivedPr));
    }
}
