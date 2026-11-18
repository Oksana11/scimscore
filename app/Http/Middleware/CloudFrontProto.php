<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CloudFront tells the origin the viewer protocol in "CloudFront-Forwarded-Proto" (it does not
 * always send X-Forwarded-Proto). Copy it so the trusted-proxy logic detects HTTPS.
 * Only meaningful when TRUSTED_PROXIES is set; the EC2 security group must accept port 80
 * from CloudFront only, otherwise these headers could be forged.
 */
class CloudFrontProto
{
    public function handle(Request $request, Closure $next): Response
    {
        $proto = strtolower((string) $request->headers->get('CloudFront-Forwarded-Proto'));
        if (in_array($proto, ['http', 'https'], true) && ! $request->headers->has('X-Forwarded-Proto')) {
            $request->headers->set('X-Forwarded-Proto', $proto);
        }

        return $next($request);
    }
}
