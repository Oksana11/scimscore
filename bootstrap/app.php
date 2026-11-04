<?php

use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $proxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        if ($proxies !== []) {
            $middleware->trustProxies(at: $proxies);
        }

        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: ['throttle:web']);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'profile.complete' => EnsureProfileComplete::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Never write OTP codes or other secrets into logs/error reports.
        $exceptions->dontFlash(['code', 'otp', 'email_code', 'token']);

        // Forms: show "too many attempts" next to the field instead of a bare 429 page.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson() || $request->isMethod('GET')) {
                return null;
            }

            $seconds = (int) ($e->getHeaders()['Retry-After'] ?? 60);
            $field = $request->has('code') ? 'code' : ($request->has('new_email') ? 'new_email' : 'email');

            return back()->withInput($request->except(['code']))
                ->withErrors([$field => __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds])]);
        });
    })->create();
