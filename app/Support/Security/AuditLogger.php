<?php

namespace App\Support\Security;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AuditLogger
{
    public const GENESIS = '0000000000000000000000000000000000000000000000000000000000000000';

    private const REDACTED = '[redacted]';

    /**
     * @param  array<string, mixed>  $context  Never put OTP codes, tokens or medical data here.
     */
    public function log(string $action, ?Model $subject = null, array $context = [], ?string $actorType = null, ?int $actorId = null): AuditLog
    {
        [$detectedType, $detectedId] = $this->detectActor();

        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        $record = [
            'occurred_at' => now()->format('Y-m-d H:i:s.u'),
            'actor_type' => $actorType ?? $detectedType,
            'actor_id' => $actorId ?? $detectedId,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject ? (string) $subject->getKey() : null,
            'ip' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            'context' => $context === [] ? null : self::canonicalJson($this->redact($context)),
        ];

        return DB::transaction(function () use ($record) {
            $prev = AuditLog::query()->orderByDesc('id')->lockForUpdate()->value('hash') ?? self::GENESIS;

            $record['prev_hash'] = $prev;
            $record['hash'] = self::hash($record, $prev);

            return AuditLog::query()->create($record);
        });
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public static function hash(array $record, string $prevHash): string
    {
        $fields = ['occurred_at', 'actor_type', 'actor_id', 'action', 'subject_type', 'subject_id', 'ip', 'user_agent', 'context'];

        $payload = [];
        foreach ($fields as $field) {
            $value = $record[$field] ?? null;
            $payload[$field] = $value === null ? null : (string) $value;
        }

        return hash_hmac('sha256', $prevHash."\n".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), self::key());
    }

    private static function key(): string
    {
        $key = Keys::decode((string) config('security.audit_hmac_key'));

        if (strlen($key) < 32) {
            throw new RuntimeException('AUDIT_HMAC_KEY is missing or shorter than 32 bytes. Run: php artisan security:generate-keys');
        }

        return $key;
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function detectActor(): array
    {
        foreach (array_keys(config('auth.guards', [])) as $guard) {
            /** @var Authenticatable|null $user */
            $user = Auth::guard($guard)->user();

            if ($user) {
                return [$guard === 'web' ? 'user' : $guard, (int) $user->getAuthIdentifier()];
            }
        }

        return [app()->runningInConsole() && ! app()->runningUnitTests() ? 'system' : 'guest', null];
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function redact(array $data): array
    {
        $keys = array_map('strtolower', config('security.audit_redact_keys', []));

        foreach ($data as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), $keys, true)) {
                $data[$k] = self::REDACTED;
            } elseif (is_array($v)) {
                $data[$k] = $this->redact($v);
            }
        }

        return $data;
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function canonicalJson(array $data): string
    {
        $sort = function (array $a) use (&$sort): array {
            if (! array_is_list($a)) {
                ksort($a);
            }

            return array_map(fn ($v) => is_array($v) ? $sort($v) : $v, $a);
        };

        return json_encode($sort($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
