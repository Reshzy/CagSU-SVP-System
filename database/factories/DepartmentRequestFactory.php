<?php

namespace Database\Factories;

use App\Models\DepartmentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartmentRequest>
 */
class DepartmentRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'description' => fake()->optional()->sentence(),
            'requester_email' => fake()->safeEmail(),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
