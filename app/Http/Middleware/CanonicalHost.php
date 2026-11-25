<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * www.<domain> → <domain> (301). The site is served from one origin only: assets, forms,
 * cookies and the security headers (CSP, COEP/CORP) are all bound to the APP_URL host, so a
 * page opened on www would load without styles and its forms would fail.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $host = strtolower($request->getHost());

        if ($canonical !== '' && $host === 'www.'.$canonical) {
            return redirect()->away(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
