<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetEarmarkExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $budgetOfficer;

    protected User $requester;

    protected Department $department;

    protected PurchaseRequest $pendingPr;

    protected PurchaseRequest $earmarkedPr;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Budget Office']);
        Role::create(['name' => 'Dean']);
        Role::create(['name' => 'Executive Officer']);

        $this->department = Department::factory()->create([
            'name' => 'Test Department',
            'code' => 'TEST',
            'is_archived' => false,
        ]);

        $this->budgetOfficer = User::factory()->create(['is_archived' => false]);
        $this->budgetOfficer->assignRole('Budget Office');

        $this->requester = User::factory()->create([
            'department_id' => $this->department->id,
            'is_archived' => false,
        ]);
        $this->requester->assignRole('Dean');

        $this->pendingPr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'budget_office_review',
            'earmark_id' => null,
        ]);

        $this->earmarkedPr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'ceo_approval',
            'earmark_id' => 'EM-03-0001',
        ]);
    }

    public function test_budget_officer_can_view_index_with_pending_and_earmarked_tabs(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.index')
        );

        $response->assertOk();
        $response->assertSee($this->pendingPr->pr_number);
        $response->assertSee('EM-03-0001');
    }

    public function test_budget_officer_can_view_earmark_review_form(): void
    {
        $lotHeader = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $this->pendingPr->id,
            'is_lot' => true,
            'lot_name' => 'Office Supplies Lot',
            'quantity_requested' => 1,
            'estimated_unit_cost' => 100,
            'estimated_total_cost' => 100,
        ]);

        $lotChild = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $this->pendingPr->id,
            'parent_lot_id' => $lotHeader->id,
            'is_lot' => false,
            'item_name' => 'Bond Paper A4',
            'unit_of_measure' => 'reams',
            'quantity_requested' => 2,
        ]);

        $standalone = PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $this->pendingPr->id,
            'is_lot' => false,
            'parent_lot_id' => null,
            'item_name' => 'Stapler',
        ]);

        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.edit', $this->pendingPr)
        );

        $response->assertOk();
        $response->assertSee('Earmark Review');
        $response->assertSee('Legal Basis');
        $response->assertSee('Programs / Projects / Activities');
        $response->assertSee('Responsibility Center');
        $response->assertSee('Earmark Date To');

        // Lot rendering
        $response->assertSee(strtoupper($lotHeader->lot_name));
        $response->assertSee($lotChild->item_name);
        $response->assertSee($standalone->item_name);
    }

    public function test_budget_officer_can_approve_earmark_with_new_fields(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->put(
            route('budget.purchase-requests.update', $this->pendingPr),
            [
                'approved_budget_total' => 15000.00,
                'date_needed' => now()->format('Y-m-d'),
                'funding_source' => 'General Fund',
                'budget_code' => 'GF-2025',
                'procurement_type' => 'supplies_materials',
                'remarks' => 'Earmark approved for procurement of office supplies.',
                'legal_basis' => 'Section 86 of RA 9184',
                'earmark_programs_activities' => 'Administrative Support Program',
                'earmark_responsibility_center' => 'Office of the Vice President for Administration',
                'earmark_date_to' => now()->addMonths(3)->format('Y-m-d'),
            ]
        );

        $response->assertRedirect(route('budget.purchase-requests.index'));

        $this->pendingPr->refresh();
        $this->assertNotNull($this->pendingPr->earmark_id);
        $this->assertEquals('ceo_approval', $this->pendingPr->status);
        $this->assertEquals('Section 86 of RA 9184', $this->pendingPr->legal_basis);
        $this->assertEquals('Administrative Support Program', $this->pendingPr->earmark_programs_activities);
        $this->assertEquals('Office of the Vice President for Administration', $this->pendingPr->earmark_responsibility_center);
    }

    public function test_earmark_approval_fails_without_required_remarks(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->put(
            route('budget.purchase-requests.update', $this->pendingPr),
            [
                'approved_budget_total' => 15000.00,
                'date_needed' => now()->format('Y-m-d'),
                'procurement_type' => 'supplies_materials',
                'remarks' => '',
            ]
        );

        $response->assertSessionHasErrors('remarks');
        $this->assertEquals('budget_office_review', $this->pendingPr->fresh()->status);
    }

    public function test_earmark_approval_fails_without_approved_budget(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->put(
            route('budget.purchase-requests.update', $this->pendingPr),
            [
                'approved_budget_total' => '',
                'date_needed' => now()->format('Y-m-d'),
                'procurement_type' => 'supplies_materials',
                'remarks' => 'Some remarks here',
            ]
        );

        $response->assertSessionHasErrors('approved_budget_total');
    }

    public function test_budget_officer_can_export_earmark_excel(): void
    {
        $templatePath = storage_path('app/templates/EarmarkTemplate.xlsx');

        if (! file_exists($templatePath)) {
            $this->markTestSkipped('Earmark template file not found, skipping export test.');
        }

        PurchaseRequestItem::factory()->count(2)->create([
            'purchase_request_id' => $this->earmarkedPr->id,
        ]);

        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.export-earmark', $this->earmarkedPr)
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_cannot_export_pr_without_earmark_id(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.export-earmark', $this->pendingPr)
        );

        $response->assertNotFound();
    }

    public function test_non_budget_officer_cannot_access_earmark_index(): void
    {
        $response = $this->actingAs($this->requester)->get(
            route('budget.purchase-requests.index')
        );

        $response->assertForbidden();
    }

    public function test_budget_officer_can_defer_a_purchase_request(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->post(
            route('budget.purchase-requests.reject', $this->pendingPr),
            [
                'rejection_reason' => 'Insufficient documentation provided for review.',
                'remarks' => 'Please complete all required documents before resubmitting.',
            ]
        );

        $response->assertRedirect(route('budget.purchase-requests.index'));

        $this->pendingPr->refresh();
        $this->assertEquals('rejected', $this->pendingPr->status);
        $this->assertNotNull($this->pendingPr->rejection_reason);
    }

    public function test_deferral_fails_with_short_reason(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->post(
            route('budget.purchase-requests.reject', $this->pendingPr),
            [
                'rejection_reason' => 'Too short',
            ]
        );

        $response->assertSessionHasErrors('rejection_reason');
        $this->assertEquals('budget_office_review', $this->pendingPr->fresh()->status);
    }
}
