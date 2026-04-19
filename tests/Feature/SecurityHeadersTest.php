<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_always_on_security_headers_are_present(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    public function test_production_only_headers_are_absent_in_testing(): void
    {
        $response = $this->get('/login');

        $response->assertHeaderMissing('Strict-Transport-Security');
        $response->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_cspnonce_is_shared_to_blade_and_unique_per_request(): void
    {
        $a = $this->get('/login');
        $nonce1 = view()->shared('cspNonce');

        $b = $this->get('/login');
        $nonce2 = view()->shared('cspNonce');

        $this->assertIsString($nonce1);
        $this->assertNotSame('', $nonce1);
        $this->assertNotSame($nonce1, $nonce2, 'CSP nonce must change per response');
        $a->assertOk();
        $b->assertOk();
    }

    public function test_production_csp_includes_nonce_and_forbids_unsafe_inline(): void
    {
        config()->set('app.env', 'production');
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/login');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp, 'production response must set CSP');
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
        $this->assertMatchesRegularExpression("/script-src [^;]*'nonce-[A-Za-z0-9_-]+'/", $csp);
        $this->assertMatchesRegularExpression("/style-src [^;]*'nonce-[A-Za-z0-9_-]+'/", $csp);
        $this->assertStringContainsString("'strict-dynamic'", $csp);
    }
}
