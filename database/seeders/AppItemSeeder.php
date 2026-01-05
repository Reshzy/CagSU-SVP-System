<?php

namespace Database\Seeders;

use App\Models\AppItem;
use Illuminate\Database\Seeder;

class AppItemSeeder extends Seeder
{
    public function run(): void
    {
        $fiscalYear = date('Y');

        // Create sample APP items for testing
        $categories = [
            'OFFICE SUPPLIES' => [
                ['code' => '10101501-BK-B01', 'name' => 'BALLPEN, BLACK', 'unit' => 'box', 'price' => 120.00],
                ['code' => '10101501-BK-B02', 'name' => 'BALLPEN, BLUE', 'unit' => 'box', 'price' => 120.00],
                ['code' => '10101502-RE-B01', 'name' => 'BALLPEN, RED', 'unit' => 'box', 'price' => 120.00],
            ],
            'ICT EQUIPMENT' => [
                ['code' => '43211507-DT-001', 'name' => 'DESKTOP COMPUTER', 'unit' => 'unit', 'price' => 35000.00],
                ['code' => '43211503-LP-001', 'name' => 'LAPTOP COMPUTER', 'unit' => 'unit', 'price' => 45000.00],
                ['code' => '43212102-PR-001', 'name' => 'PRINTER, INKJET', 'unit' => 'unit', 'price' => 8000.00],
            ],
            'SOFTWARE' => [
                ['code' => 'SW-001', 'name' => 'Microsoft Office 365 License', 'unit' => 'license', 'price' => 0],
                ['code' => 'SW-002', 'name' => 'Antivirus Software', 'unit' => 'license', 'price' => 0],
            ],
        ];

        foreach ($categories as $category => $items) {
            foreach ($items as $item) {
                AppItem::create([
                    'fiscal_year' => $fiscalYear,
                    'category' => $category,
                    'item_code' => $item['code'],
                    'item_name' => $item['name'],
                    'unit_of_measure' => $item['unit'],
                    'unit_price' => $item['price'],
                    'specifications' => $item['name'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
