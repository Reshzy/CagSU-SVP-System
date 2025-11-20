<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            'System Administrator',
            'Supply Officer',
            'Employee',
            'Budget Officer',
            'Executive Officer',
            'BAC Chairman',
            'BAC Member',
            'BAC Secretary',
            'Accounting Officer',
            'Canvassing Officer',
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(['name' => $position]);
        }
    }
}
