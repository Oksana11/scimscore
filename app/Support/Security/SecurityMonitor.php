<?php

namespace App\Support\Security;

use App\Mail\SecurityAlertMail;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Watches audit records and emails all active administrators about suspicious activity.
 * Each alert (type + who/where) is sent at most once per hour. Alerts never contain
 * personal or medical data — only counts, IPs, actor ids and a link to the audit log.
 */
class SecurityMonitor
{
    /** Thresholds: [count, minutes]. */
    public const RULES = [
        'failed_logins' => [10, 15],        // wrong sign-in codes from one IP
        'admin_failed' => [3, 15],          // wrong admin email/authenticator codes from one IP
        'access_denied' => [5, 15],         // attempts to open other people's data
        'exports' => [10, 60],              // exports by one user
    ];

    public const BIG_EXPORT_ROWS = 500;

    private bool $sending = false;

    public function inspect(AuditLog $log): void
    {
        if ($this->sending || str_starts_with($log->action, 'security.')) {
            return;
        }

        try {
            match (true) {
                $log->action === 'auth.otp_failed' => $this->threshold('failed_logins', $log, ['auth.otp_failed'], 'ip',
                    'Many wrong sign-in codes from one IP address (possible brute force).'),
                in_array($log->action, ['admin.otp_failed', 'admin.mfa_failed'], true) => $this->threshold('admin_failed', $log, ['admin.otp_failed', 'admin.mfa_failed'], 'ip',
                    'Repeated failed administrator sign-in attempts.'),
                $log->action === 'access.denied' => $this->threshold('access_denied', $log, ['access.denied'], $log->actor_id ? 'actor' : 'ip',
                    'Repeated attempts to open data that belongs to someone else.'),
                str_starts_with($log->action, 'export.') || str_starts_with($log->action, 'admin.export_') => $this->exports($log),
                $log->action === 'admin.login' => $this->adminNewIp($log),
                default => null,
            };
        } catch (Throwable $e) {
            Log::warning('Security monitor failed: '.$e->getMessage());
        }
    }

    /**
     * @param  list<string>  $actions  action names or SQL LIKE patterns
     */
    private function threshold(string $rule, AuditLog $log, array $actions, string $by, string $title): void
    {
        [$limit, $minutes] = self::RULES[$rule];
        $q = AuditLog::query()
            ->where(function ($w) use ($actions) {
                foreach ($actions as $a) {
                    $w->orWhere('action', 'like', $a);
                }
            })
            ->where('occurred_at', '>=', now()->subMinutes($minutes));
        $by === 'ip' ? $q->where('ip', $log->ip) : $q->where('actor_type', $log->actor_type)->where('actor_id', $log->actor_id);

        $count = $q->count();
        if ($count >= $limit) {
            $who = $by === 'ip' ? 'IP '.($log->ip ?? 'unknown') : "{$log->actor_type}#{$log->actor_id}";
            $this->alert("{$rule}:{$who}", $title, [
                'What' => "{$count} events in {$minutes} minutes",
                'Who' => $who,
            ], $log);
        }
    }

    private function exports(AuditLog $log): void
    {
        $context = json_decode((string) $log->context, true) ?: [];
        $rows = (int) ($context['rows'] ?? 0);
        $who = $log->actor_id ? "{$log->actor_type}#{$log->actor_id}" : 'IP '.($log->ip ?? 'unknown');

        if ($rows >= self::BIG_EXPORT_ROWS) {
            $this->alert("big_export:{$who}", 'Large data export.', ['What' => "{$rows} rows ({$log->action})", 'Who' => $who], $log);

            return;
        }

        if ($log->actor_id) {
            $this->threshold('exports', $log, ['export.%', 'admin.export\_%'], 'actor',
                'Unusually many exports by one account.');
        }
    }

    private function adminNewIp(AuditLog $log): void
    {
        if (! $log->ip) {
            return;
        }
        $seen = AuditLog::query()
            ->where('action', 'admin.login')->where('actor_id', $log->actor_id)
            ->where('ip', $log->ip)->where('id', '<', $log->id)
            ->where('occurred_at', '>=', now()->subDays(90))
            ->exists();
        $first = ! AuditLog::query()->where('action', 'admin.login')->where('actor_id', $log->actor_id)->where('id', '<', $log->id)->exists();

        if (! $seen && ! $first) {
            $this->alert("admin_new_ip:{$log->actor_id}:{$log->ip}", 'Administrator signed in from a new IP address.', [
                'Who' => "admin#{$log->actor_id}",
                'IP' => $log->ip,
            ], $log);
        }
    }

    /**
     * @param  array<string, string>  $facts
     */
    private function alert(string $key, string $title, array $facts, AuditLog $log): void
    {
        if (! Cache::add('security-alert:'.hash('sha256', $key), true, now()->addHour())) {
            return; // already alerted within the last hour
        }

        $recipients = Admin::query()->where('is_active', true)->get()->pluck('email')->filter()->all();
        if ($recipients === []) {
            return;
        }

        $this->sending = true;
        try {
            Mail::to($recipients)->send(new SecurityAlertMail($title, $facts + ['Time (UTC)' => (string) $log->occurred_at, 'Audit record' => "#{$log->id}"]));
            app(AuditLogger::class)->log('security.alert_sent', context: ['alert' => explode(':', $key)[0], 'recipients' => count($recipients)], actorType: 'system');
        } finally {
            $this->sending = false;
        }
    }
}
