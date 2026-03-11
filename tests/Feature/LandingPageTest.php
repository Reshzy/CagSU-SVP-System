<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_loads(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('CagSU SVP Portal');
    }

    public function test_supplier_public_pages_are_not_available(): void
    {
        $this->get('/suppliers/register')->assertNotFound();
        $this->post('/suppliers/register')->assertNotFound();

        $this->get('/suppliers/quotations/submit')->assertNotFound();
        $this->post('/suppliers/quotations/submit')->assertNotFound();

        $this->get('/suppliers/po-status')->assertNotFound();

        $this->get('/suppliers/contact')->assertNotFound();
        $this->post('/suppliers/contact')->assertNotFound();
    }
}
