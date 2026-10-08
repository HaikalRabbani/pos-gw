<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_is_served_at_root_and_links_to_auth_pages(): void
    {
        $response = $this->withoutVite()->get('/');

        $response->assertOk();
        $response->assertSee('PesenApa');
        $response->assertSee('href="/register"', false);
        $response->assertSee('href="/login"', false);
    }

    public function test_admin_spa_routes_still_served_by_catch_all(): void
    {
        $this->withoutVite()->get('/login')->assertOk();
        $this->withoutVite()->get('/dashboard')->assertOk();
    }
}
