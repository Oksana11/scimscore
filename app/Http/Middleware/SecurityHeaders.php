<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce is generated before the view renders so @vite tags carry it.
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // Pages may contain personal data: never store in browser/proxy/CDN caches.
        if (! $headers->has('Cache-Control') || ! str_contains((string) $headers->get('Cache-Control'), 'no-store')) {
            $headers->set('Cache-Control', 'no-store, private');
        }
        $headers->set('Pragma', 'no-cache');

        if (config('security.headers.csp')) {
            $headers->set('Content-Security-Policy', $this->csp($nonce, $request));
        }

        if (config('security.headers.hsts') && $request->isSecure()) {
            $headers->set(
                'Strict-Transport-Security',
                'max-age='.config('security.headers.hsts_max_age').'; includeSubDomains'
            );
        }

        $headers->remove('X-Powered-By');

        return $response;
    }

    private function csp(string $nonce, Request $request): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
