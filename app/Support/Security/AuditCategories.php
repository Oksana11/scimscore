<?php

namespace App\Support\Security;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Groups audit actions for the admin Audit log tabs. Based on the action name only.
 */
final class AuditCategories
{
    public const DEFAULT = 'activity';

    /** @var array<string, string> */
    public const LABELS = [
        'activity' => 'Activity',
        'security' => 'Security',
        'data' => 'Data changes',
        'exports' => 'Exports & emails',
        'admin' => 'Admin actions',
        'views' => 'Views',
        'all' => 'All',
    ];

    private const VIEWS = ['%.viewed', '%.list_viewed', '%.edit_opened', 'assessment.compared', 'assessment.quick_viewed', 'admin.view\_%', 'admin.support_viewed'];

    private const SECURITY = ['auth.%', 'access.denied', 'admin.login', 'admin.logout', 'admin.otp\_%', 'admin.mfa\_%', 'admin.audit_verified'];

    private const EXPORTS = ['export.%', 'admin.export\_%', 'assessment.emailed', 'assessment.quick_emailed', 'admin.email_template_test'];

    /**
     * @param  Builder<AuditLog>  $q
     * @return Builder<AuditLog>
     */
    public static function apply(Builder $q, string $category): Builder
    {
        return match ($category) {
            'views' => self::any($q, self::VIEWS),
            'security' => self::any($q, self::SECURITY),
            'exports' => self::any($q, self::EXPORTS),
            'admin' => self::none(self::none(self::none($q->where('action', 'like', 'admin.%'), self::VIEWS), self::SECURITY), self::EXPORTS),
            'data' => self::none(self::none(self::none($q->where('action', 'not like', 'admin.%'), self::VIEWS), self::SECURITY), self::EXPORTS),
            'all' => $q,
            default => self::none($q, self::VIEWS), // activity: everything except page views
        };
    }

    public static function of(string $action): string
    {
        foreach (['views' => self::VIEWS, 'security' => self::SECURITY, 'exports' => self::EXPORTS] as $cat => $patterns) {
            foreach ($patterns as $p) {
                // LIKE pattern -> regex: "%" is a wildcard, "\_" a literal underscore.
                $regex = '/^'.str_replace('%', '.*', preg_quote(str_replace('\_', '_', $p), '/')).'$/';
                if (preg_match($regex, $action)) {
                    return $cat;
                }
            }
        }

        return str_starts_with($action, 'admin.') ? 'admin' : 'data';
    }

    /** @param list<string> $patterns */
    private static function any(Builder $q, array $patterns): Builder
    {
        return $q->where(function (Builder $w) use ($patterns) {
            foreach ($patterns as $p) {
                $w->orWhere('action', 'like', $p);
            }
        });
    }

    /** @param list<string> $patterns */
    private static function none(Builder $q, array $patterns): Builder
    {
        foreach ($patterns as $p) {
            $q->where('action', 'not like', $p);
        }

        return $q;
    }
}
