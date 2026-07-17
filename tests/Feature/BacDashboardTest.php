<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BacDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_bac_dashboard_shows_pending_evaluations_count(): void
    {
        Role::findOrCreate('BAC Chair');

        $bacChair = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $bacChair->assignRole('BAC Chair');

        PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        PurchaseRequest::factory()->create(['status' => 'bac_evaluation']);
        PurchaseRequest::factory()->create(['status' => 'draft']);
        PurchaseRequest::factory()->create(['status' => 'bac_approved']);

        $response = $this->actingAs($bacChair)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Pending Evaluations',
            '>2</dd>',
        ], false);
    }

    public function test_bac_dashboard_shows_zero_when_no_pending_evaluations(): void
    {
        Role::findOrCreate('BAC Secretariat');

        $bacUser = User::factory()->create([
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $bacUser->assignRole('BAC Secretariat');

        PurchaseRequest::factory()->create(['status' => 'draft']);
        PurchaseRequest::factory()->create(['status' => 'bac_approved']);

        $response = $this->actingAs($bacUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Pending Evaluations',
            '>0</dd>',
        ], false);
    }
}
