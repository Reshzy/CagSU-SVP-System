<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'business_name' => 'Lienmavel',
                'contact_person' => 'Owner',
                'phone' => '09051800309',
                'address' => 'H66J+GXC, Centro 1',
                'city' => 'Sanchez-Mira',
                'province' => 'Cagayan',
                'postal_code' => '3518',
                'business_type' => 'sole_proprietorship',
                'email' => 'lienmavel@supplier.local',
            ],
            [
                'business_name' => 'ME',
                'contact_person' => 'Manager',
                'phone' => '09353313031',
                'address' => 'H65P+3X5, Centro 1',
                'city' => 'Sanchez-Mira',
                'province' => 'Cagayan',
                'postal_code' => '3518',
                'business_type' => 'corporation',
                'email' => 'me@supplier.local',
            ],
            [
                'business_name' => 'Mr. DIY',
                'contact_person' => 'Store Manager',
                'phone' => '0284630855',
                'address' => 'H64Q+X3, Centro 1',
                'city' => 'Sanchez-Mira',
                'province' => 'Cagayan',
                'postal_code' => '3518',
                'business_type' => 'corporation',
                'email' => 'mrdiy@supplier.local',
            ],
            [
                'business_name' => 'AW Commercial',
                'contact_person' => 'Owner',
                'phone' => 'N/A',
                'address' => 'H65P+2X, Centro 1',
                'city' => 'Sanchez-Mira',
                'province' => 'Cagayan',
                'postal_code' => '3518',
                'business_type' => 'sole_proprietorship',
                'email' => 'awcommercial@supplier.local',
            ],
            [
                'business_name' => 'Pandayan',
                'contact_person' => 'Manager',
                'phone' => 'N/A',
                'address' => 'H65P+F6P, Centro 1',
                'city' => 'Sanchez-Mira',
                'province' => 'Cagayan',
                'postal_code' => '3518',
                'business_type' => 'sole_proprietorship',
                'email' => 'pandayan@supplier.local',
            ],
            [
                'business_name' => 'Derima',
                'contact_person' => 'Owner',
                'phone' => 'N/A',
                'address' => 'H64Q+X3M, Centro 1',
                'city' => 'Sanchez-Mira',
                'province' => 'Cagayan',
                'postal_code' => '3518',
                'business_type' => 'sole_proprietorship',
                'email' => 'derima@supplier.local',
            ],
            [
                'business_name' => 'Migrants',
                'contact_person' => 'Owner',
                'phone' => 'N/A',
                'address' => '016 Juglas Street',
                'city' => 'Sanchez-Mira',
                'province' => 'Cagayan',
                'postal_code' => '3518',
                'business_type' => 'sole_proprietorship',
                'email' => 'migrants@supplier.local',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            // Check if supplier already exists by email or business name
            $existing = Supplier::where('email', $supplierData['email'])
                ->orWhere('business_name', $supplierData['business_name'])
                ->first();

            if ($existing) {
                // Update existing supplier
                $existing->update(array_merge($supplierData, [
                    'supplier_code' => $existing->supplier_code, // Keep existing code
                    'status' => 'active',
                ]));
                $this->command->info("Updated supplier: {$supplierData['business_name']}");
            } else {
                // Create new supplier
                Supplier::create(array_merge($supplierData, [
                    'supplier_code' => Supplier::generateSupplierCode(),
                    'status' => 'active',
                ]));
                $this->command->info("Created supplier: {$supplierData['business_name']}");
            }
        }

        $this->command->info('✅ Successfully seeded '.count($suppliers).' suppliers.');
    }
}
