<?php

namespace Database\Factories;

use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalAmount = fake()->randomFloat(2, 1000, 100000);

        return [
            'po_number' => 'PO-'.now()->format('my').'-'.fake()->unique()->numberBetween(1, 9999),
            'purchase_request_id' => PurchaseRequest::factory(),
            'pr_item_group_id' => null,
            'supplier_id' => Supplier::factory(),
            'quotation_id' => null,
            'tin' => fake()->optional()->numerify('###-###-###'),
            'supplier_name_override' => null,
            'funds_cluster' => fake()->randomElement(['01', '02', '03', '04']),
            'funds_available' => $totalAmount,
            'ors_burs_no' => 'ORS-'.fake()->numberBetween(1000, 9999),
            'ors_burs_date' => now()->subDays(fake()->numberBetween(1, 30)),
            'po_date' => now(),
            'total_amount' => $totalAmount,
            'delivery_address' => fake()->address(),
            'delivery_date_required' => now()->addDays(fake()->numberBetween(7, 60)),
            'terms_and_conditions' => 'Standard terms and conditions apply.',
            'special_instructions' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['draft', 'pending_approval', 'approved', 'sent_to_supplier', 'completed']),
            'approved_by' => null,
            'approved_at' => null,
            'sent_to_supplier_at' => null,
            'acknowledged_at' => null,
            'actual_delivery_date' => null,
            'delivery_notes' => null,
            'delivery_complete' => false,
        ];
    }
}
