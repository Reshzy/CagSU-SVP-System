<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_create_a_purchase_request()
    {
        $this->seed();
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->post('/purchase-requests', [
            'purpose' => 'Test purpose',
            'justification' => 'Needed for testing',
            'date_needed' => now()->addWeek()->format('Y-m-d'),
            'priority' => 'medium',
            'estimated_total' => 1000,
            'procurement_type' => 'supplies_materials',
            'item_name' => 'Item A',
            'detailed_specifications' => 'Specs',
            'unit_of_measure' => 'pcs',
            'quantity_requested' => 2,
            'estimated_unit_cost' => 500,
        ]);

        $resp->assertRedirect('/purchase-requests');
        $this->assertDatabaseHas('purchase_requests', ['purpose' => 'Test purpose']);
    }
}


