<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_home_page_sends_security_headers(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeaderMissing('X-Powered-By');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'/", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function test_csp_nonce_changes_per_request(): void
    {
        $this->withoutVite();

        $a = $this->get('/')->headers->get('Content-Security-Policy');
        $b = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertNotSame($a, $b);
    }

    public function test_hsts_is_sent_only_over_https_when_enabled(): void
    {
        $this->withoutVite();
        config(['security.headers.hsts' => true]);

        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
    }
}
