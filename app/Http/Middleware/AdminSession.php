<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrator sessions: signed out after 30 minutes of inactivity or when the
 * admin account is deactivated.
 */
class AdminSession
{
    private const IDLE_SECONDS = 1800;

    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        $last = (int) $request->session()->get('admin.last_seen', 0);

        if ($admin && (! $admin->is_active || ($last > 0 && now()->getTimestamp() - $last > self::IDLE_SECONDS))) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors(['email' => 'Your admin session has ended. Please sign in again.']);
        }

        $request->session()->put('admin.last_seen', now()->getTimestamp());

        return $next($request);
    }
}
