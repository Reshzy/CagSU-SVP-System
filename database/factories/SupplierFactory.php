<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_code' => 'SUP-'.fake()->unique()->numberBetween(1000, 9999),
            'business_name' => fake()->company(),
            'trade_name' => fake()->optional()->company(),
            'business_type' => fake()->randomElement(['sole_proprietorship', 'partnership', 'corporation', 'cooperative']),
            'contact_person' => fake()->name(),
            'position' => fake()->jobTitle(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->optional()->phoneNumber(),
            'fax' => fake()->optional()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'tin' => fake()->optional()->numerify('###-###-###'),
            'business_permit' => fake()->optional()->numerify('BP-######'),
            'permit_expiry' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'philgeps_registration' => fake()->optional()->numerify('PG-######'),
            'status' => 'active',
            'specialization' => fake()->optional()->text(200),
            'performance_rating' => fake()->randomFloat(2, 0, 5),
            'total_contracts' => 0,
            'total_contract_value' => 0,
        ];
    }
}
