<?php

namespace Tests\Feature;

use App\Models\AppItem;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpmpCreateCollapseAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_ppmp_create_page_shows_collapse_all_control(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        AppItem::factory()->create([
            'fiscal_year' => date('Y'),
            'category' => 'OFFICE SUPPLIES',
            'unit_price' => 10.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('ppmp.create'));

        $response->assertStatus(200);
        $response->assertSee('Collapse all');
    }
}
