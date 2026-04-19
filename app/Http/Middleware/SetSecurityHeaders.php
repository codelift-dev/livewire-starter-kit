<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Per-request CSP nonce. Consumed automatically by:
        //   - Laravel's @vite(...) directive (via Vite::useCspNonce below)
        //   - Livewire's auto-injected <script>/<style> tags, which fall back
        //     to Vite::cspNonce() when no explicit nonce option is provided
        //   - Flux's @fluxScripts / @fluxAppearance directives, when the
        //     'nonce' option is threaded through in Blade templates
        //   - Manual <script nonce="{{ $cspNonce }}">...</script> in views
        //
        // 16 random bytes → base64url (~22 chars). CSP only needs the value
        // to be cryptographically random and unique per response; short is
        // fine.
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');

        app()->instance('csp-nonce', $nonce);
        Vite::useCspNonce($nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );

            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            // 'strict-dynamic' lets nonce-approved scripts load further
            // scripts without each one carrying the nonce — needed for
            // Vite bundles that dynamically import additional chunks.
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic'",
            // style-src keeps 'self' for @vite-linked stylesheets and nonce
            // for the inline <style> blocks emitted by Flux and by
            // welcome.blade.php. Bunny Fonts needs its host allow-listed.
            "style-src 'self' 'nonce-{$nonce}' https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
    }
}
