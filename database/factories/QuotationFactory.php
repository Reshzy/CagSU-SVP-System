<?php

namespace Database\Factories;

use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = 'QUO-'.now()->format('my').'-';
        $last = \App\Models\Quotation::where('quotation_number', 'like', $prefix.'%')
            ->orderByDesc('quotation_number')
            ->value('quotation_number');
        $next = $last ? (intval(substr($last, strlen($prefix))) + 1) : 1;
        $quotationNumber = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        return [
            'quotation_number' => $quotationNumber,
            'purchase_request_id' => PurchaseRequest::factory(),
            'pr_item_group_id' => null,
            'supplier_id' => Supplier::factory(),
            'supplier_location' => fake()->address(),
            'quotation_date' => now()->subDays(fake()->numberBetween(1, 30)),
            'validity_date' => now()->addDays(fake()->numberBetween(30, 90)),
            'total_amount' => fake()->randomFloat(2, 1000, 100000),
            'exceeds_abc' => false,
            'terms_and_conditions' => 'Standard terms.',
            'delivery_days' => fake()->numberBetween(7, 30),
            'delivery_terms' => 'Within '.fake()->numberBetween(7, 30).' days.',
            'payment_terms' => '30 days net.',
            'bac_status' => 'awarded',
            'is_winning_bid' => true,
            'supporting_documents' => null,
        ];
    }
}
