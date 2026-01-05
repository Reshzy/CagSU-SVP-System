<?php

namespace Database\Factories;

use App\Models\AppItem;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PpmpItemFactory extends Factory
{
    protected $model = PpmpItem::class;

    public function definition(): array
    {
        $q1 = fake()->numberBetween(0, 50);
        $q2 = fake()->numberBetween(0, 50);
        $q3 = fake()->numberBetween(0, 50);
        $q4 = fake()->numberBetween(0, 50);
        $total = $q1 + $q2 + $q3 + $q4;
        $unitCost = fake()->randomFloat(2, 10, 1000);

        return [
            'ppmp_id' => Ppmp::factory(),
            'app_item_id' => AppItem::factory(),
            'q1_quantity' => $q1,
            'q2_quantity' => $q2,
            'q3_quantity' => $q3,
            'q4_quantity' => $q4,
            'total_quantity' => $total,
            'estimated_unit_cost' => $unitCost,
            'estimated_total_cost' => $total * $unitCost,
        ];
    }
}
