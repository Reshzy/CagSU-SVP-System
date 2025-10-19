<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Procurement', 'code' => 'PROC'],
            ['name' => 'Academic Affairs', 'code' => 'ACAD'],
            ['name' => 'Student Affairs', 'code' => 'SA'],
            ['name' => 'Research and Development', 'code' => 'RND'],
            ['name' => 'Physical Plant', 'code' => 'PP'],
            ['name' => 'Security', 'code' => 'SEC'],
            ['name' => 'Library', 'code' => 'LIB'],
        ];

        $dept = $this->faker->randomElement($departments);

        return [
            'name' => $dept['name'],
            'code' => $dept['code'],
            'description' => $dept['name'] . ' Department',
            'head_name' => $this->faker->name(),
            'contact_email' => strtolower($dept['code']) . '@cagsu.edu.ph',
            'contact_phone' => $this->faker->phoneNumber(),
            'is_active' => true,
        ];
    }
}
