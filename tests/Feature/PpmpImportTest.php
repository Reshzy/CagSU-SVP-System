<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PpmpImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a minimal APP-CSE CSV string with the given item rows.
     *
     * Each item row must be an associative array with keys:
     *   row, item_code, item_name, uom, q1, q2, q3, q4, price
     *
     * @param  array<int, array{row: int, item_code: string, item_name: string, uom: string, q1: int, q2: int, q3: int, q4: int, price: float}>  $items
     */
    private function buildCsvContent(array $items, string $category = 'OFFICE SUPPLIES'): string
    {
        $handle = fopen('php://temp', 'r+');

        // Minimal header rows the parser can safely skip
        fputcsv($handle, array_merge(['APP-CSE 2025 FORM'], array_fill(0, 26, '')));
        fputcsv($handle, array_fill(0, 27, ''));

        // Category header (all-caps, longer than 10 chars)
        fputcsv($handle, array_merge([$category], array_fill(0, 26, '')));

        foreach ($items as $item) {
            // col0=row, col1=item_code, col2=item_name, col3=uom,
            // col4=Jan, col5=Feb, col6=Mar, col7=Q1,
            // col8=Q1_amt,
            // col9=Apr, col10=May, col11=Jun, col12=Q2,
            // col13=Q2_amt,
            // col14=Jul, col15=Aug, col16=Sep, col17=Q3,
            // col18=Q3_amt,
            // col19=Oct, col20=Nov, col21=Dec, col22=Q4,
            // col23=Q4_amt,
            // col24=total_qty, col25=price, col26=total_amt
            $q1 = $item['q1'];
            $q2 = $item['q2'];
            $q3 = $item['q3'];
            $q4 = $item['q4'];
            $price = $item['price'];
            $total = $q1 + $q2 + $q3 + $q4;

            fputcsv($handle, [
                $item['row'],       // 0
                $item['item_code'], // 1
                $item['item_name'], // 2
                $item['uom'],       // 3
                0, 0, $q1, $q1,    // 4 Jan, 5 Feb, 6 Mar, 7 Q1
                0,                  // 8 Q1_amt
                0, 0, $q2, $q2,    // 9 Apr, 10 May, 11 Jun, 12 Q2
                0,                  // 13 Q2_amt
                0, 0, $q3, $q3,    // 14 Jul, 15 Aug, 16 Sep, 17 Q3
                0,                  // 18 Q3_amt
                0, 0, $q4, $q4,    // 19 Oct, 20 Nov, 21 Dec, 22 Q4
                0,                  // 23 Q4_amt
                $total,             // 24 total_qty
                $price,             // 25 price
                0,                  // 26 total_amt
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function uploadCsv(string $content, string $name = 'ppmp_test.csv'): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    public function test_import_form_requires_authentication(): void
    {
        $response = $this->get(route('ppmp.import'));

        $response->assertRedirect(route('login'));
    }

    public function test_import_process_requires_authentication(): void
    {
        $response = $this->post(route('ppmp.import.process'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_department_is_redirected_from_import_form(): void
    {
        $user = User::factory()->create(['department_id' => null]);

        $response = $this->actingAs($user)->get(route('ppmp.import'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_without_department_cannot_process_import(): void
    {
        $user = User::factory()->create(['department_id' => null]);

        $response = $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv(''),
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_import_form_renders_for_department_user(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($user)->get(route('ppmp.import'));

        $response->assertStatus(200);
        $response->assertSee('Import PPMP');
    }

    public function test_valid_csv_imports_ppmp_items_with_quarterly_quantities(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $appItem = AppItem::factory()->create([
            'item_code' => '12191601-AL-E04',
            'fiscal_year' => date('Y'),
            'unit_price' => 50.43,
        ]);

        $csvContent = $this->buildCsvContent([
            [
                'row' => 1,
                'item_code' => '12191601-AL-E04',
                'item_name' => 'ALCOHOL, Ethyl, 500 mL',
                'uom' => 'bottle',
                'q1' => 5,
                'q2' => 3,
                'q3' => 4,
                'q4' => 2,
                'price' => 50.43,
            ],
        ]);

        $response = $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv($csvContent),
        ]);

        $response->assertRedirect(route('ppmp.index'));
        $response->assertSessionHas('success');

        $ppmp = Ppmp::where('department_id', $department->id)->first();
        $this->assertNotNull($ppmp);

        $ppmpItem = PpmpItem::where('ppmp_id', $ppmp->id)
            ->where('app_item_id', $appItem->id)
            ->first();

        $this->assertNotNull($ppmpItem);
        $this->assertEquals(5, $ppmpItem->q1_quantity);
        $this->assertEquals(3, $ppmpItem->q2_quantity);
        $this->assertEquals(4, $ppmpItem->q3_quantity);
        $this->assertEquals(2, $ppmpItem->q4_quantity);
        $this->assertEquals(14, $ppmpItem->total_quantity);
        $this->assertEquals(50.43, (float) $ppmpItem->estimated_unit_cost);
    }

    public function test_items_with_all_zero_quantities_are_skipped(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        AppItem::factory()->create([
            'item_code' => '12191601-AL-E04',
            'fiscal_year' => date('Y'),
            'unit_price' => 50.43,
        ]);

        $csvContent = $this->buildCsvContent([
            [
                'row' => 1,
                'item_code' => '12191601-AL-E04',
                'item_name' => 'ALCOHOL, Ethyl, 500 mL',
                'uom' => 'bottle',
                'q1' => 0,
                'q2' => 0,
                'q3' => 0,
                'q4' => 0,
                'price' => 50.43,
            ],
        ]);

        $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv($csvContent),
        ]);

        $ppmp = Ppmp::where('department_id', $department->id)->first();
        $this->assertEquals(0, $ppmp->items()->count());
    }

    public function test_items_not_in_app_catalog_are_skipped(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        // No AppItem seeded — import should skip the row

        $csvContent = $this->buildCsvContent([
            [
                'row' => 1,
                'item_code' => 'NONEXISTENT-CODE',
                'item_name' => 'Some Item',
                'uom' => 'piece',
                'q1' => 5,
                'q2' => 0,
                'q3' => 0,
                'q4' => 0,
                'price' => 100.00,
            ],
        ]);

        $response = $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv($csvContent),
        ]);

        $response->assertRedirect(route('ppmp.index'));

        $ppmp = Ppmp::where('department_id', $department->id)->first();
        $this->assertEquals(0, $ppmp->items()->count());
    }

    public function test_import_merges_with_existing_ppmp_items(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $appItem1 = AppItem::factory()->create([
            'item_code' => 'ITEM-001',
            'fiscal_year' => date('Y'),
            'unit_price' => 100.00,
        ]);

        $appItem2 = AppItem::factory()->create([
            'item_code' => 'ITEM-002',
            'fiscal_year' => date('Y'),
            'unit_price' => 200.00,
        ]);

        // Seed an existing PPMP with appItem1
        $ppmp = Ppmp::getOrCreateForDepartment($department->id, (int) date('Y'));
        PpmpItem::factory()->create([
            'ppmp_id' => $ppmp->id,
            'app_item_id' => $appItem1->id,
            'q1_quantity' => 1,
            'q2_quantity' => 1,
            'q3_quantity' => 1,
            'q4_quantity' => 1,
            'total_quantity' => 4,
            'estimated_unit_cost' => 100.00,
            'estimated_total_cost' => 400.00,
        ]);

        // CSV only contains appItem2 — appItem1 should remain untouched
        $csvContent = $this->buildCsvContent([
            [
                'row' => 1,
                'item_code' => 'ITEM-002',
                'item_name' => 'Item Two',
                'uom' => 'piece',
                'q1' => 10,
                'q2' => 0,
                'q3' => 0,
                'q4' => 0,
                'price' => 200.00,
            ],
        ]);

        $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv($csvContent),
        ]);

        $ppmp->refresh();

        $this->assertEquals(2, $ppmp->items()->count());

        $item1 = $ppmp->items()->where('app_item_id', $appItem1->id)->first();
        $this->assertEquals(4, $item1->total_quantity);

        $item2 = $ppmp->items()->where('app_item_id', $appItem2->id)->first();
        $this->assertNotNull($item2);
        $this->assertEquals(10, $item2->q1_quantity);
    }

    public function test_import_updates_existing_ppmp_item_quantities(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $appItem = AppItem::factory()->create([
            'item_code' => 'ITEM-UPDATE',
            'fiscal_year' => date('Y'),
            'unit_price' => 50.00,
        ]);

        $ppmp = Ppmp::getOrCreateForDepartment($department->id, (int) date('Y'));
        PpmpItem::factory()->create([
            'ppmp_id' => $ppmp->id,
            'app_item_id' => $appItem->id,
            'q1_quantity' => 1,
            'q2_quantity' => 1,
            'q3_quantity' => 1,
            'q4_quantity' => 1,
            'total_quantity' => 4,
            'estimated_unit_cost' => 50.00,
            'estimated_total_cost' => 200.00,
        ]);

        // CSV has new quantities for the same item
        $csvContent = $this->buildCsvContent([
            [
                'row' => 1,
                'item_code' => 'ITEM-UPDATE',
                'item_name' => 'Updateable Item',
                'uom' => 'piece',
                'q1' => 10,
                'q2' => 20,
                'q3' => 5,
                'q4' => 0,
                'price' => 50.00,
            ],
        ]);

        $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv($csvContent),
        ]);

        $ppmp->refresh();
        $this->assertEquals(1, $ppmp->items()->count());

        $ppmpItem = $ppmp->items()->first();
        $this->assertEquals(10, $ppmpItem->q1_quantity);
        $this->assertEquals(20, $ppmpItem->q2_quantity);
        $this->assertEquals(5, $ppmpItem->q3_quantity);
        $this->assertEquals(0, $ppmpItem->q4_quantity);
        $this->assertEquals(35, $ppmpItem->total_quantity);
    }

    public function test_csv_price_is_used_for_items_without_app_price(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $appItem = AppItem::factory()->create([
            'item_code' => 'SOFTWARE-001',
            'fiscal_year' => date('Y'),
            'unit_price' => null,
            'category' => 'SOFTWARE',
        ]);

        $csvContent = $this->buildCsvContent([
            [
                'row' => 1,
                'item_code' => 'SOFTWARE-001',
                'item_name' => 'Some Software',
                'uom' => 'license',
                'q1' => 2,
                'q2' => 0,
                'q3' => 0,
                'q4' => 0,
                'price' => 5000.00,
            ],
        ], 'SOFTWARE');

        $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv($csvContent),
        ]);

        $ppmp = Ppmp::where('department_id', $department->id)->first();
        $ppmpItem = $ppmp->items()->where('app_item_id', $appItem->id)->first();

        $this->assertNotNull($ppmpItem);
        $this->assertEquals(5000.00, (float) $ppmpItem->estimated_unit_cost);
    }

    public function test_ppmp_total_cost_is_recalculated_after_import(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        AppItem::factory()->create([
            'item_code' => 'ITEM-COST',
            'fiscal_year' => date('Y'),
            'unit_price' => 100.00,
        ]);

        $csvContent = $this->buildCsvContent([
            [
                'row' => 1,
                'item_code' => 'ITEM-COST',
                'item_name' => 'Cost Item',
                'uom' => 'piece',
                'q1' => 10,
                'q2' => 5,
                'q3' => 0,
                'q4' => 0,
                'price' => 100.00,
            ],
        ]);

        $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
            'csv_file' => $this->uploadCsv($csvContent),
        ]);

        $ppmp = Ppmp::where('department_id', $department->id)->first();

        $this->assertEquals(1500.00, (float) $ppmp->total_estimated_cost);
    }

    public function test_validation_rejects_missing_csv_file(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($user)->post(route('ppmp.import.process'), [
            'fiscal_year' => date('Y'),
        ]);

        $response->assertSessionHasErrors('csv_file');
    }

    public function test_validation_rejects_missing_fiscal_year(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($user)->post(route('ppmp.import.process'), [
            'csv_file' => $this->uploadCsv('some,content'),
        ]);

        $response->assertSessionHasErrors('fiscal_year');
    }
}
