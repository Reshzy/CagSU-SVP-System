<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppBottomNavigationTest extends TestCase
{
    public function test_bottom_navigation_partial_contains_dashboard_route_and_aria_labels(): void
    {
        $html = view('layouts.bottom-navigation')->render();

        $this->assertStringContainsString(route('dashboard'), $html);
        $this->assertStringContainsString('aria-label="'.__('Go to dashboard').'"', $html);
        $this->assertStringContainsString('aria-label="'.__('Back to top').'"', $html);
        $this->assertStringContainsString("getElementById('main-app-navigation')", $html);
    }

    public function test_app_layout_includes_bottom_navigation_and_main_padding(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('<main class="pb-24">', $contents);
        $this->assertStringContainsString("@include('layouts.bottom-navigation')", $contents);
    }

    public function test_main_navigation_exposes_id_for_bottom_bar_scroll_threshold(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        $this->assertStringContainsString('id="main-app-navigation"', $contents);
    }
}
