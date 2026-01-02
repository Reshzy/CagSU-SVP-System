<?php

namespace Database\Factories;

use App\Models\AppItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppItemFactory extends Factory
{
    protected $model = AppItem::class;

    public function definition(): array
    {
        return [
            'fiscal_year' => date('Y'),
            'category' => fake()->randomElement([
                'OFFICE SUPPLIES',
                'ICT EQUIPMENT',
                'FURNITURE AND FIXTURES',
                'SOFTWARE',
            ]),
            'item_code' => fake()->unique()->numerify('####-####-##'),
            'item_name' => fake()->words(3, true),
            'unit_of_measure' => fake()->randomElement(['piece', 'set', 'unit', 'box', 'pack']),
            'unit_price' => fake()->randomFloat(2, 10, 10000),
            'specifications' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
