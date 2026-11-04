<?php

namespace App\Providers;

use App\Support\Security\AuditLogger;
use App\Support\Security\BlindIndex;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(BlindIndex::class, fn () => BlindIndex::fromConfig());
    }

    public function boot(): void
    {
        $production = $this->app->isProduction();

        if ($production) {
            // Safety net: debug output in production would leak config and stack traces.
            config(['app.debug' => false]);
            URL::forceHttps();
        }

        // Fail loudly on lazy loading, unknown attributes and silently discarded fields (dev/test).
        Model::shouldBeStrict(! $production);

        // Blocks migrate:fresh, db:wipe, etc. in production.
        DB::prohibitDestructiveCommands($production);

        $this->configureRateLimiting();
        $this->registerAuditListeners();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('web', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        // Used by OTP endpoints (stage 4).
        RateLimiter::for('otp-send', fn (Request $request) => [
            Limit::perMinute(1)->by('otp-send:email:'.strtolower((string) $request->input('email'))),
            Limit::perHour(10)->by('otp-send:email-h:'.strtolower((string) $request->input('email'))),
            Limit::perHour(30)->by('otp-send:ip:'.$request->ip()),
        ]);

        RateLimiter::for('otp-verify', fn (Request $request) => [
            Limit::perMinutes(15, 10)->by('otp-verify:ip:'.$request->ip()),
        ]);
    }

    private function registerAuditListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            app(AuditLogger::class)->log('auth.login', context: ['guard' => $event->guard], actorType: $event->guard === 'web' ? 'user' : $event->guard, actorId: (int) $event->user->getAuthIdentifier());
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                app(AuditLogger::class)->log('auth.logout', context: ['guard' => $event->guard], actorType: $event->guard === 'web' ? 'user' : $event->guard, actorId: (int) $event->user->getAuthIdentifier());
            }
        });
    }
}
