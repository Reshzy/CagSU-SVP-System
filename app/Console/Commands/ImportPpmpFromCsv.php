<?php

namespace App\Console\Commands;

use App\Models\AppItem;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPpmpFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppmp:import-csv
                            {file : Path to the APP-CSE CSV file}
                            {--year= : Fiscal year for the PPMP (defaults to current year)}
                            {--department= : Department ID to import PPMP for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import PPMP quarterly quantities from an APP-CSE CSV file';

    /**
     * Column indices in the APP-CSE CSV format.
     */
    private const COL_ITEM_CODE = 1;

    private const COL_ITEM_NAME = 2;

    private const COL_UOM = 3;

    private const COL_Q1_QTY = 7;

    private const COL_Q2_QTY = 12;

    private const COL_Q3_QTY = 17;

    private const COL_Q4_QTY = 22;

    private const COL_PRICE = 25;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $fiscalYear = (int) ($this->option('year') ?? date('Y'));
        $departmentId = $this->option('department');

        if (! $departmentId) {
            $this->error('A department ID must be provided via --department.');

            return Command::FAILURE;
        }

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        $file = fopen($filePath, 'r');
        if (! $file) {
            $this->error("Failed to open file: {$filePath}");

            return Command::FAILURE;
        }

        $this->info("Starting PPMP import for department {$departmentId}, fiscal year {$fiscalYear}");

        $currentCategory = null;
        $lineNumber = 0;
        $imported = 0;
        $updated = 0;
        $skippedZeroQty = 0;
        $skippedNotInApp = 0;

        DB::beginTransaction();

        try {
            $ppmp = Ppmp::getOrCreateForDepartment((int) $departmentId, $fiscalYear);

            while (($row = fgetcsv($file)) !== false) {
                $lineNumber++;

                if (empty(array_filter($row))) {
                    continue;
                }

                $firstCol = trim($row[0] ?? '');

                // Detect the SOFTWARE category header
                if (! empty($firstCol) && strtoupper($firstCol) === 'SOFTWARE') {
                    $currentCategory = 'SOFTWARE';

                    continue;
                }

                // Detect PART II header
                $fullRowText = implode(' ', array_map('trim', $row));
                if (str_contains($fullRowText, 'PART II') && str_contains($fullRowText, 'OTHER ITEMS')) {
                    $currentCategory = 'PART II - OTHER ITEMS NOT AVAILABLE AT PS-DBM';

                    continue;
                }

                // Detect other all-caps category headers
                if (
                    ! empty($firstCol) &&
                    ! is_numeric($firstCol) &&
                    strtoupper($firstCol) === $firstCol &&
                    ! str_contains($firstCol, 'PART I.') &&
                    ! str_contains($firstCol, 'APP-CSE') &&
                    ! str_contains($firstCol, 'ANNUAL') &&
                    strlen($firstCol) > 10
                ) {
                    $currentCategory = $firstCol;

                    continue;
                }

                // Process item rows (first column is a numeric sequence number)
                if (! is_numeric($firstCol) || empty($row[self::COL_ITEM_CODE]) || empty($row[self::COL_ITEM_NAME])) {
                    continue;
                }

                $itemCode = trim($row[self::COL_ITEM_CODE]);
                $q1Quantity = (int) ($row[self::COL_Q1_QTY] ?? 0);
                $q2Quantity = (int) ($row[self::COL_Q2_QTY] ?? 0);
                $q3Quantity = (int) ($row[self::COL_Q3_QTY] ?? 0);
                $q4Quantity = (int) ($row[self::COL_Q4_QTY] ?? 0);

                // Skip items with no planned quantities in any quarter
                if (($q1Quantity + $q2Quantity + $q3Quantity + $q4Quantity) === 0) {
                    $skippedZeroQty++;

                    continue;
                }

                // Look up the APP item by item code and fiscal year
                $appItem = AppItem::query()
                    ->where('item_code', $itemCode)
                    ->where('fiscal_year', $fiscalYear)
                    ->first();

                if (! $appItem) {
                    $this->warn("APP item not found for code '{$itemCode}' in FY {$fiscalYear} — skipping.");
                    $skippedNotInApp++;

                    continue;
                }

                // Determine the unit cost: use CSV price column if available and APP item has no price
                $customPrice = null;
                if (isset($row[self::COL_PRICE])) {
                    $priceStr = preg_replace('/[₱,\s]/', '', trim($row[self::COL_PRICE]));
                    $priceValue = floatval($priceStr);
                    if ($priceValue > 0) {
                        $customPrice = $priceValue;
                    }
                }

                $estimatedUnitCost = $appItem->unit_price ?? $customPrice;

                if ($estimatedUnitCost === null || $estimatedUnitCost <= 0) {
                    $this->warn("No price available for item '{$itemCode}' — skipping.");
                    $skippedNotInApp++;

                    continue;
                }

                $totalQuantity = $q1Quantity + $q2Quantity + $q3Quantity + $q4Quantity;
                $estimatedTotalCost = $totalQuantity * $estimatedUnitCost;

                $existingItem = PpmpItem::query()
                    ->where('ppmp_id', $ppmp->id)
                    ->where('app_item_id', $appItem->id)
                    ->first();

                if ($existingItem) {
                    $existingItem->update([
                        'q1_quantity' => $q1Quantity,
                        'q2_quantity' => $q2Quantity,
                        'q3_quantity' => $q3Quantity,
                        'q4_quantity' => $q4Quantity,
                        'total_quantity' => $totalQuantity,
                        'estimated_unit_cost' => $estimatedUnitCost,
                        'estimated_total_cost' => $estimatedTotalCost,
                    ]);
                    $updated++;
                } else {
                    PpmpItem::create([
                        'ppmp_id' => $ppmp->id,
                        'app_item_id' => $appItem->id,
                        'q1_quantity' => $q1Quantity,
                        'q2_quantity' => $q2Quantity,
                        'q3_quantity' => $q3Quantity,
                        'q4_quantity' => $q4Quantity,
                        'total_quantity' => $totalQuantity,
                        'estimated_unit_cost' => $estimatedUnitCost,
                        'estimated_total_cost' => $estimatedTotalCost,
                    ]);
                    $imported++;
                }
            }

            fclose($file);

            // Recalculate PPMP total cost
            $ppmp->total_estimated_cost = $ppmp->calculateTotalCost();
            $ppmp->save();

            DB::commit();

            $this->info('PPMP import completed successfully!');
            $this->info("Items imported (new): {$imported}");
            $this->info("Items updated (existing): {$updated}");
            $this->info("Items skipped (zero quantity): {$skippedZeroQty}");
            $this->info("Items skipped (not in APP catalog): {$skippedNotInApp}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);

            $this->error('Import failed: '.$e->getMessage());
            $this->error("At line: {$lineNumber}");

            return Command::FAILURE;
        }
    }
}
