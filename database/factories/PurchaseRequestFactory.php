<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = [
            'draft',
            'submitted',
            'supply_office_review',
            'budget_office_review',
            'ceo_approval',
            'bac_evaluation',
            'bac_approved',
            'po_generation',
            'po_approved',
            'supplier_processing',
            'delivered',
            'completed'
        ];

        $procurementTypes = [
            'supplies_materials',
            'equipment',
            'infrastructure',
            'services',
            'consulting_services'
        ];

        $procurementMethods = [
            'small_value_procurement',
            'public_bidding',
            'direct_contracting',
            'negotiated_procurement'
        ];

        $priorities = ['low', 'medium', 'high', 'urgent'];

        $purposes = [
            'Office supplies for daily operations',
            'IT equipment upgrade for department',
            'Maintenance materials for facilities',
            'Laboratory equipment for research',
            'Furniture for new office setup',
            'Cleaning supplies for maintenance',
            'Security equipment installation',
            'Training materials and resources',
            'Vehicle maintenance supplies',
            'Communication equipment upgrade'
        ];

        $justifications = [
            'Required for normal business operations and productivity',
            'Current equipment is outdated and needs replacement',
            'Essential for maintaining facility standards',
            'Needed to support increased workload and staff',
            'Critical for health and safety compliance',
            'Required for regulatory compliance',
            'Necessary for improved efficiency and service delivery',
            'Essential for maintaining quality standards',
            'Required for emergency preparedness',
            'Needed for staff training and development'
        ];

        $fundingSources = [
            'General Fund',
            'Special Fund',
            'Development Fund',
            'Maintenance Fund',
            'Equipment Fund',
            'Training Fund'
        ];

        $estimatedTotal = $this->faker->randomFloat(2, 1000, 50000); // Max 50k for small value procurement
        $status = $this->faker->randomElement($statuses);

        // Generate dates based on status
        $createdAt = $this->faker->dateTimeBetween('-6 months', 'now');
        $submittedAt = in_array($status, ['submitted', 'supply_office_review', 'budget_office_review', 'ceo_approval', 'bac_evaluation', 'bac_approved', 'po_generation', 'po_approved', 'supplier_processing', 'delivered', 'completed'])
            ? $this->faker->dateTimeBetween($createdAt, 'now') : null;
        $approvedAt = in_array($status, ['po_approved', 'supplier_processing', 'delivered', 'completed'])
            ? $this->faker->dateTimeBetween($submittedAt ?: $createdAt, 'now') : null;
        $completedAt = $status === 'completed'
            ? $this->faker->dateTimeBetween($approvedAt ?: $createdAt, 'now') : null;

        // Generate unique PR number to avoid conflicts during batch creation
        $prNumber = null;
        $attempts = 0;
        do {
            $prNumber = PurchaseRequest::generateNextPrNumber();
            $attempts++;
            if ($attempts > 10) {
                // Fallback: add random suffix if we can't generate unique number
                $prNumber = PurchaseRequest::generateNextPrNumber() . '-' . $this->faker->randomNumber(3);
                break;
            }
        } while (PurchaseRequest::where('pr_number', $prNumber)->exists());

        return [
            'pr_number' => $prNumber,
            'requester_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'department_id' => Department::inRandomOrder()->first()?->id ?? Department::factory(),
            'purpose' => $this->faker->randomElement($purposes),
            'justification' => $this->faker->randomElement($justifications),
            'date_needed' => $this->faker->dateTimeBetween('now', '+3 months'),
            'priority' => $this->faker->randomElement($priorities),
            'estimated_total' => $estimatedTotal,
            'funding_source' => $this->faker->randomElement($fundingSources),
            'budget_code' => $this->faker->regexify('[A-Z]{2}[0-9]{4}'),
            'procurement_type' => $this->faker->randomElement($procurementTypes),
            'procurement_method' => $estimatedTotal >= 50000 ? 'public_bidding' : 'small_value_procurement', // Always small value since max is 50k
            'status' => $status,
            'current_handler_id' => $status !== 'completed' ? (User::inRandomOrder()->first()?->id ?? User::factory()) : null,
            'current_step_notes' => $status !== 'completed' ? $this->faker->optional(0.7)->sentence() : null,
            'status_updated_at' => $this->faker->dateTimeBetween($createdAt, 'now'),
            'has_ppmp' => $this->faker->boolean(30),
            'ppmp_reference' => $this->faker->optional(0.3)->regexify('PPMP-[0-9]{4}-[A-Z]{3}'),
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
            'completed_at' => $completedAt,
            'total_processing_days' => $completedAt ? $this->faker->numberBetween(10, 90) : null,
            'created_at' => $createdAt,
            'updated_at' => $this->faker->dateTimeBetween($createdAt, 'now'),
        ];
    }

    /**
     * Indicate that the purchase request is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
            'completed_at' => null,
            'current_handler_id' => null,
            'current_step_notes' => null,
        ]);
    }

    /**
     * Indicate that the purchase request is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'approved_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the purchase request is completed.
     */
    public function completed(): static
    {
        $submittedAt = $this->faker->dateTimeBetween('-3 months', '-1 month');
        $approvedAt = $this->faker->dateTimeBetween($submittedAt, '-2 weeks');
        $completedAt = $this->faker->dateTimeBetween($approvedAt, 'now');

        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
            'completed_at' => $completedAt,
            'total_processing_days' => $this->faker->numberBetween(10, 60),
            'current_handler_id' => null,
            'current_step_notes' => 'Purchase request completed successfully.',
        ]);
    }

    /**
     * Indicate that the purchase request is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the purchase request is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => 'urgent',
            'date_needed' => $this->faker->dateTimeBetween('now', '+2 weeks'),
        ]);
    }

    /**
     * Indicate that the purchase request requires public bidding.
     */
    public function publicBidding(): static
    {
        return $this->state(fn(array $attributes) => [
            'estimated_total' => $this->faker->randomFloat(2, 45000, 50000), // Close to 50k limit
            'procurement_method' => 'small_value_procurement', // Still small value since under 50k
        ]);
    }

    /**
     * Indicate that the purchase request is small value procurement.
     */
    public function smallValue(): static
    {
        return $this->state(fn(array $attributes) => [
            'estimated_total' => $this->faker->randomFloat(2, 1000, 49999),
            'procurement_method' => 'small_value_procurement',
        ]);
    }
}
