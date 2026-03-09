<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function requester_can_export_their_purchase_request_as_excel(): void
    {
        $templatePath = storage_path('app/templates/PurchaseRequestTemplate.xlsx');

        if (! file_exists($templatePath)) {
            $this->markTestSkipped('PR template file not found, skipping requester export test.');
        }

        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->create([
            'requester_id' => $user->id,
        ]);

        PurchaseRequestItem::factory()->count(2)->create([
            'purchase_request_id' => $purchaseRequest->id,
        ]);

        $response = $this->actingAs($user)->get(
            route('purchase-requests.export', $purchaseRequest)
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function non_requester_cannot_export_another_users_purchase_request(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $purchaseRequest = PurchaseRequest::factory()->create([
            'requester_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->get(
            route('purchase-requests.export', $purchaseRequest)
        );

        $response->assertForbidden();
    }
}
