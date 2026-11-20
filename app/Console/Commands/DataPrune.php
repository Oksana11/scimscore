<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\SupportMessage;
use App\Support\Security\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('data:prune {--dry-run : Only report what would be deleted}')]
#[Description('Delete data past its retention period (codes, sessions, cache, support messages, audit log)')]
class DataPrune extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $dry = (bool) $this->option('dry-run');
        $r = config('scim.retention');
        $now = now();

        $sets = [
            'otp_requests' => DB::table('otp_requests')->where('created_at', '<', $now->copy()->subDays($r['otp_days'])),
            'email_change_requests' => DB::table('email_change_requests')->where('created_at', '<', $now->copy()->subDays($r['otp_days'])),
            'sessions' => DB::table('sessions')->where('last_activity', '<', $now->copy()->subMinutes((int) config('session.lifetime'))->getTimestamp()),
            'cache' => DB::table('cache')->where('expiration', '<', $now->getTimestamp()),
            'support_messages' => SupportMessage::query()->where('created_at', '<', $now->copy()->subDays($r['support_days'])),
        ];

        $counts = [];
        foreach ($sets as $name => $query) {
            $counts[$name] = $dry ? $query->count() : $query->delete();
        }

        $counts['audit_logs'] = $this->pruneAudit($now->copy()->subDays($r['audit_days']), $dry);

        $this->table(['Data', $dry ? 'Would delete' : 'Deleted'], collect($counts)->map(fn ($n, $k) => [$k, $n])->values()->all());

        if (! $dry && array_sum($counts) > 0) {
            $audit->log('privacy.data_pruned', context: $counts, actorType: 'system');
        }

        return self::SUCCESS;
    }

    /**
     * Old audit records are removed from the start of the HMAC chain. The first remaining
     * record and its prev_hash are saved as a signed anchor, so audit:verify still detects
     * any later deletion or change (the anchor itself can't be forged without the key).
     */
    private function pruneAudit(\DateTimeInterface $before, bool $dry): int
    {
        $last = AuditLog::query()->where('occurred_at', '<', $before)->max('id');
        if ($last === null) {
            return 0;
        }

        $count = AuditLog::query()->where('id', '<=', $last)->count();
        if ($dry) {
            return $count;
        }

        DB::transaction(function () use ($last) {
            $first = AuditLog::query()->where('id', '>', $last)->orderBy('id')->first(['id', 'prev_hash']);
            if ($first) {
                Setting::put(AuditLogger::ANCHOR_KEY, AuditLogger::signAnchor((int) $first->id, (string) $first->prev_hash));
            }
            AuditLog::query()->where('id', '<=', $last)->delete();
        });

        return $count;
    }
}
