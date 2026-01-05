<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Ppmp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PpmpFactory extends Factory
{
    protected $model = Ppmp::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'fiscal_year' => date('Y'),
            'status' => 'draft',
            'total_estimated_cost' => 0,
            'validated_at' => null,
            'validated_by' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => User::factory(),
        ]);
    }
}
