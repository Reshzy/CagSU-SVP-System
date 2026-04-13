<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the CagSU SVP System with roles, permissions, departments, and sample users
        $this->call([
            PositionSeeder::class, // Run first to populate positions table
            RolePermissionSeeder::class,
            CollegeSeeder::class,
            ComprehensiveUserSeeder::class,
            SupplierSeeder::class,
            AppItemSeeder::class,
            // PurchaseRequestSeeder::class,
        ]);
    }
}
