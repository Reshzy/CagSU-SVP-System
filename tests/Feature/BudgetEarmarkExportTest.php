<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
                'earmark_object_expenditures' => [
                    ['code' => '(50213040-02)', 'description' => 'R & M School Buildings', 'amount' => 15000.00],
                ],
            ]
        );

        $response->assertRedirect(route('budget.purchase-requests.index'));

        $this->pendingPr->refresh();
        $this->assertNotNull($this->pendingPr->earmark_id);
        $this->assertEquals('ceo_approval', $this->pendingPr->status);
        $this->assertEquals('Section 86 of RA 9184', $this->pendingPr->legal_basis);
        $this->assertEquals('Administrative Support Program', $this->pendingPr->earmark_programs_activities);
        $this->assertEquals('Office of the Vice President for Administration', $this->pendingPr->earmark_responsibility_center);
        $this->assertIsArray($this->pendingPr->earmark_object_expenditures);
        $this->assertCount(1, $this->pendingPr->earmark_object_expenditures);
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

    public function test_preview_export_on_budget_office_review_reserves_earmark_id(): void
    {
        $templatePath = storage_path('app/templates/EarmarkTemplate.xlsx');

        if (! file_exists($templatePath)) {
            $this->markTestSkipped('Earmark template file not found, skipping export test.');
        }

        $this->assertNull($this->pendingPr->earmark_id);

        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.export-earmark', $this->pendingPr)
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->pendingPr->refresh();
        $this->assertNotNull($this->pendingPr->earmark_id, 'earmark_id should be reserved on preview export');
    }

    public function test_cannot_export_pr_that_is_not_in_review_and_has_no_earmark_id(): void
    {
        $otherPr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'submitted',
            'earmark_id' => null,
        ]);

        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.export-earmark', $otherPr)
        );

        $response->assertNotFound();
    }

    public function test_budget_officer_can_view_amend_page(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.amend', $this->earmarkedPr)
        );

        $response->assertOk();
        $response->assertSee($this->earmarkedPr->earmark_id);
        $response->assertSee('Amend Earmark');
        $response->assertSee('Save Amendment');
    }

    public function test_amend_page_returns_404_without_earmark_id(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.amend', $this->pendingPr)
        );

        $response->assertNotFound();
    }

    public function test_budget_officer_can_amend_earmark_fields(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->patch(
            route('budget.purchase-requests.amend-earmark', $this->earmarkedPr),
            [
                'legal_basis' => 'Updated Legal Basis',
                'funding_source' => 'Special Fund',
                'earmark_programs_activities' => 'Updated Program',
                'earmark_responsibility_center' => 'Updated RC',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->earmarkedPr->refresh();
        $this->assertEquals('Updated Legal Basis', $this->earmarkedPr->legal_basis);
        $this->assertEquals('Special Fund', $this->earmarkedPr->funding_source);
        $this->assertEquals('Updated Program', $this->earmarkedPr->earmark_programs_activities);
    }

    public function test_amendment_creates_activity_log_with_old_and_new_values(): void
    {
        $this->earmarkedPr->update([
            'legal_basis' => 'Original Legal Basis',
            'earmark_object_expenditures' => [
                ['code' => '(50213040-02)', 'description' => 'R & M School Buildings', 'amount' => 10000],
            ],
        ]);

        $this->actingAs($this->budgetOfficer)->patch(
            route('budget.purchase-requests.amend-earmark', $this->earmarkedPr),
            [
                'legal_basis' => 'Amended Legal Basis',
                'earmark_object_expenditures' => [
                    ['code' => '(50213040-02)', 'description' => 'R & M School Buildings', 'amount' => 12000],
                    ['code' => '(50213040-03)', 'description' => 'Another Object', 'amount' => 3000],
                ],
            ]
        );

        $activity = $this->earmarkedPr->activities()->where('action', 'earmark_amended')->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Original Legal Basis', $activity->old_value['legal_basis']);
        $this->assertEquals('Amended Legal Basis', $activity->new_value['legal_basis']);
        $this->assertArrayHasKey('earmark_object_expenditures', $activity->old_value);
        $this->assertArrayHasKey('earmark_object_expenditures', $activity->new_value);
        $this->assertCount(1, $activity->old_value['earmark_object_expenditures']);
        $this->assertCount(2, $activity->new_value['earmark_object_expenditures']);
    }

    public function test_amendment_returns_no_changes_message_when_nothing_changed(): void
    {
        $this->earmarkedPr->update(['legal_basis' => 'Same Value']);

        $response = $this->actingAs($this->budgetOfficer)->patch(
            route('budget.purchase-requests.amend-earmark', $this->earmarkedPr),
            ['legal_basis' => 'Same Value']
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'No changes detected.');

        $this->assertDatabaseMissing('purchase_request_activities', [
            'purchase_request_id' => $this->earmarkedPr->id,
            'action' => 'earmark_amended',
        ]);
    }

    public function test_object_of_expenditures_written_to_excel_rows(): void
    {
        $templatePath = storage_path('app/templates/EarmarkTemplate.xlsx');

        if (! file_exists($templatePath)) {
            $this->markTestSkipped('Earmark template file not found, skipping export mapping test.');
        }

        $this->earmarkedPr->update([
            'estimated_total' => 18000,
            'earmark_object_expenditures' => [
                ['code' => '(50213040-02)', 'description' => 'R & M School Buildings', 'amount' => 15000],
                ['code' => '(50213040-03)', 'description' => 'Other Object', 'amount' => 3000],
            ],
        ]);

        $response = $this->actingAs($this->budgetOfficer)->get(
            route('budget.purchase-requests.export-earmark', $this->earmarkedPr)
        );

        $response->assertOk();

        $binary = $response->baseResponse;
        $file = $binary->getFile();
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('(50213040-02). R & M School Buildings', $sheet->getCell('A19')->getValue());
        $this->assertSame('(50213040-03). Other Object', $sheet->getCell('A20')->getValue());
        $this->assertEquals(15000, $sheet->getCell('C19')->getCalculatedValue());
        $this->assertEquals(3000, $sheet->getCell('C20')->getCalculatedValue());
    }

    public function test_amendment_works_from_ceo_approval_status(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'ceo_approval',
            'earmark_id' => 'EM-03-0010',
        ]);

        $response = $this->actingAs($this->budgetOfficer)->patch(
            route('budget.purchase-requests.amend-earmark', $pr),
            ['legal_basis' => 'Amendment during CEO approval']
        );

        $response->assertRedirect();
        $pr->refresh();
        $this->assertEquals('Amendment during CEO approval', $pr->legal_basis);
    }

    public function test_amendment_works_from_bac_evaluation_status(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'bac_evaluation',
            'earmark_id' => 'EM-03-0011',
        ]);

        $response = $this->actingAs($this->budgetOfficer)->patch(
            route('budget.purchase-requests.amend-earmark', $pr),
            ['funding_source' => 'Amended Fund during BAC']
        );

        $response->assertRedirect();
        $pr->refresh();
        $this->assertEquals('Amended Fund during BAC', $pr->funding_source);
    }

    public function test_amendment_blocked_when_pr_still_in_budget_office_review(): void
    {
        $this->pendingPr->update(['earmark_id' => 'EM-03-0099']);

        $response = $this->actingAs($this->budgetOfficer)->patch(
            route('budget.purchase-requests.amend-earmark', $this->pendingPr),
            ['legal_basis' => 'Should not work']
        );

        $response->assertForbidden();
    }

    public function test_amendment_returns_404_without_earmark_id(): void
    {
        $response = $this->actingAs($this->budgetOfficer)->patch(
            route('budget.purchase-requests.amend-earmark', $this->pendingPr),
            ['legal_basis' => 'Should not work']
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
