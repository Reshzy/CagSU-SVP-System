<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppWorkflowRevampTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bac_secretariat_can_access_ps_dbms_reference_management(): void
    {
        $bacSecretariat = User::factory()->create();
        $bacSecretariat->assignRole('BAC Secretariat');

        $response = $this->actingAs($bacSecretariat)->get(route('ps-dbms.index'));

        $response->assertOk();
    }

    public function test_end_user_cannot_access_ps_dbms_reference_management(): void
    {
        $endUser = User::factory()->create();
        $endUser->assignRole('End User');

        $response = $this->actingAs($endUser)->get(route('ps-dbms.index'));

        $response->assertForbidden();
    }

    public function test_consolidated_app_uses_validated_ppmps_only(): void
    {
        $bacSecretariat = User::factory()->create();
        $bacSecretariat->assignRole('BAC Secretariat');

        $fiscalYear = (int) date('Y');
        $departmentOne = Department::factory()->create();
        $departmentTwo = Department::factory()->create();

        $referenceItem = AppItem::factory()->create([
            'fiscal_year' => $fiscalYear,
            'item_code' => 'PSDBMS-001',
            'item_name' => 'Reference Item One',
        ]);

        $validatedPpmp = Ppmp::factory()->create([
            'department_id' => $departmentOne->id,
            'fiscal_year' => $fiscalYear,
            'status' => 'validated',
        ]);

        PpmpItem::factory()->create([
            'ppmp_id' => $validatedPpmp->id,
            'app_item_id' => $referenceItem->id,
            'q1_quantity' => 1,
            'q2_quantity' => 1,
            'q3_quantity' => 1,
            'q4_quantity' => 0,
            'total_quantity' => 3,
            'estimated_unit_cost' => 100,
            'estimated_total_cost' => 300,
        ]);

        $draftPpmp = Ppmp::factory()->create([
            'department_id' => $departmentTwo->id,
            'fiscal_year' => $fiscalYear,
            'status' => 'draft',
        ]);

        PpmpItem::factory()->create([
            'ppmp_id' => $draftPpmp->id,
            'app_item_id' => $referenceItem->id,
            'q1_quantity' => 9,
            'q2_quantity' => 9,
            'q3_quantity' => 9,
            'q4_quantity' => 9,
            'total_quantity' => 36,
            'estimated_unit_cost' => 100,
            'estimated_total_cost' => 3600,
        ]);

        $response = $this->actingAs($bacSecretariat)->get(route('bac.app.index', ['fiscal_year' => $fiscalYear]));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['validated_ppmps'] === 1
                && $stats['departments_included'] === 1
                && $stats['total_items'] === 1
                && $stats['grand_total_cost'] === 300.0;
        });

        $response->assertViewHas('groupedItems', function ($groupedItems): bool {
            $firstItem = $groupedItems->flatten(1)->first();

            if (! $firstItem) {
                return false;
            }

            return (int) $firstItem->total_quantity === 3;
        });
    }
}
