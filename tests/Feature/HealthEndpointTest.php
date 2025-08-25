<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    /** @test */
    public function health_endpoint_returns_ok()
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status','time'])
                 ->assertJson(['status' => 'ok']);
    }
}


