<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebReleaseHardeningTest extends TestCase
{
    public function test_public_web_smoke_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Lewati ke konten utama');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_custom_error_pages_do_not_expose_internal_details(): void
    {
        foreach ([403, 404, 419, 422, 429, 500] as $status) {
            $response = $this->get("/__missing-page-{$status}");
            if ($status === 404) {
                $response->assertNotFound()->assertSee('Halaman tidak ditemukan')->assertDontSee('Stack trace');
            } else {
                $view = $this->view("errors.{$status}");
                $view->assertSee((string) $status)->assertDontSee('Stack trace');
            }
        }
    }

    public function test_web_responses_include_security_headers(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy');
    }
}
