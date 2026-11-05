<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->profile) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
