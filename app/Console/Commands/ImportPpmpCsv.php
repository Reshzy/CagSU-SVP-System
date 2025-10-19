<?php

namespace App\Console\Commands;

use App\Models\PpmpItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPpmpCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppmp:import {file? : The CSV file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import PPMP items from CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file') ?? 'APP-CSE 2025 Form CICS.csv';

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Starting import from: {$filePath}");

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->error("Failed to open file: {$filePath}");
            return Command::FAILURE;
        }

        $currentCategory = null;
        $lineNumber = 0;
        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== false) {
                $lineNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Check if this is a category header (all caps, typically)
                $firstCol = trim($row[0] ?? '');
                
                // Check for specific important categories first
                if (!empty($firstCol)) {
                    // Check for SOFTWARE category
                    if (strtoupper($firstCol) === 'SOFTWARE') {
                        $currentCategory = 'SOFTWARE';
                        $this->info("Found category: {$currentCategory}");
                        continue;
                    }
                    
                    // Check for PART II category (looking at the full row content)
                    $fullRowText = implode(' ', array_map('trim', $row));
                    if (str_contains($fullRowText, 'PART II') && str_contains($fullRowText, 'OTHER ITEMS')) {
                        $currentCategory = 'PART II - OTHER ITEMS NOT AVAILABLE AT PS-DBM';
                        $this->info("Found category: {$currentCategory}");
                        continue;
                    }
                }

                // Categories are in all caps and don't start with a number
                if (!empty($firstCol) && !is_numeric($firstCol) && strtoupper($firstCol) === $firstCol) {
                    // Check if it looks like a category
                    if (
                        !str_contains($firstCol, 'PART I.') &&
                        !str_contains($firstCol, 'APP-CSE') &&
                        !str_contains($firstCol, 'ANNUAL') &&
                        strlen($firstCol) > 10
                    ) {
                        $currentCategory = $firstCol;
                        $this->info("Found category: {$currentCategory}");
                        continue;
                    }
                }

                // Check if this is an item row (starts with a number)
                if (is_numeric($firstCol) && !empty($row[1]) && !empty($row[2])) {
                    $itemCode = trim($row[1]);
                    $itemName = trim($row[2]);
                    $unitOfMeasure = trim($row[3] ?? '');

                    // Price is in column with index 25 (Total Quantity for the year Price column)
                    $price = 0;
                    if (isset($row[25])) {
                        $priceStr = trim($row[25]);
                        // Remove currency symbols and commas
                        $priceStr = preg_replace('/[₱,\s]/', '', $priceStr);
                        $price = floatval($priceStr);
                    }

                    // Skip if no category, item code, or item name
                    if (empty($currentCategory) || empty($itemCode) || empty($itemName)) {
                        $skipped++;
                        continue;
                    }

                    // For SOFTWARE and PART II categories, allow price 0 (custom pricing)
                    $allowZeroPrice = str_contains($currentCategory, 'SOFTWARE') || 
                                     str_contains($currentCategory, 'PART II');
                    
                    // Skip if price is 0 (likely incomplete data), except for categories with custom pricing
                    if ($price <= 0 && !$allowZeroPrice) {
                        $skipped++;
                        continue;
                    }

                    // Create or update the item
                    PpmpItem::updateOrCreate(
                        ['item_code' => $itemCode],
                        [
                            'category' => $currentCategory,
                            'item_name' => $itemName,
                            'unit_of_measure' => $unitOfMeasure,
                            'unit_price' => $price,
                            'specifications' => $itemName, // Use item name as specification for now
                            'is_active' => true,
                        ]
                    );

                    $imported++;

                    if ($imported % 10 === 0) {
                        $this->info("Imported {$imported} items...");
                    }
                }
            }

            fclose($file);

            DB::commit();

            $this->info("Import completed successfully!");
            $this->info("Items imported: {$imported}");
            $this->info("Items skipped: {$skipped}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);

            $this->error("Import failed: " . $e->getMessage());
            $this->error("Line number: " . $lineNumber);

            return Command::FAILURE;
        }
    }
}
