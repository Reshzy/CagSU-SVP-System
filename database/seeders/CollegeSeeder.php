<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class CollegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colleges = [
            [
                'name' => 'Calayan Extension',
                'code' => 'CALEXT',
                'description' => 'CagSU Calayan Extension Campus',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Agriculture',
                'code' => 'COA',
                'description' => 'College of Agriculture',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Business Entrepreneurship and Accountancy',
                'code' => 'CBEA',
                'description' => 'College of Business Entrepreneurship and Accountancy',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Criminal Justice Education',
                'code' => 'CCJE',
                'description' => 'College of Criminal Justice Education',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Engineering',
                'code' => 'COE',
                'description' => 'College of Engineering',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Hospitality Management',
                'code' => 'CHM',
                'description' => 'College of Hospitality Management',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Industrial Technology',
                'code' => 'CIT',
                'description' => 'College of Industrial Technology',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Information and Computing Sciences',
                'code' => 'CICS',
                'description' => 'College of Information and Computing Sciences',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'College of Teacher Education',
                'code' => 'CTE',
                'description' => 'College of Teacher Education',
                'is_active' => true,
                'is_archived' => false,
            ],
            [
                'name' => 'Graduate School',
                'code' => 'GRADSCH',
                'description' => 'Graduate School',
                'is_active' => true,
                'is_archived' => false,
            ],
        ];

        foreach ($colleges as $college) {
            // Check if a department with this name or code exists
            $existing = Department::where('name', $college['name'])
                ->orWhere('code', $college['code'])
                ->first();

            if ($existing) {
                // Update the existing (archived) department
                $existing->update($college);
            } else {
                // Create new department
                Department::create($college);
            }
        }

        $this->command->info('✅ Successfully seeded 10 colleges.');
    }
}
