<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Support\Security\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('audit:verify')]
#[Description('Verify the HMAC chain of the audit log (detects edited or deleted records)')]
class AuditVerify extends Command
{
    public function handle(): int
    {
        $prev = AuditLogger::GENESIS;
        $checked = 0;
        $broken = [];

        AuditLog::query()->orderBy('id')->chunk(1000, function ($rows) use (&$prev, &$checked, &$broken) {
            foreach ($rows as $row) {
                $raw = $row->getAttributes();

                if ($raw['prev_hash'] !== $prev) {
                    $broken[] = "#{$raw['id']}: chain broken (previous record deleted or changed)";
                }

                if (! hash_equals(AuditLogger::hash($raw, $raw['prev_hash']), $raw['hash'])) {
                    $broken[] = "#{$raw['id']}: record content was modified";
                }

                $prev = $raw['hash'];
                $checked++;
            }
        });

        if ($broken !== []) {
            foreach ($broken as $line) {
                $this->error($line);
            }
            $this->error("Audit log integrity FAILED ({$checked} records checked).");

            return self::FAILURE;
        }

        $this->info("Audit log OK: {$checked} records, chain intact.");

        return self::SUCCESS;
    }
}
