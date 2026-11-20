<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Setting;
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

        // After retention pruning the chain starts at a signed anchor instead of the genesis.
        $anchorRaw = Setting::get(AuditLogger::ANCHOR_KEY);
        $firstId = AuditLog::query()->min('id');
        if ($anchorRaw !== null) {
            $anchor = AuditLogger::readAnchor($anchorRaw);
            if ($anchor === null) {
                $broken[] = 'chain anchor is invalid (modified without the key)';
            } elseif ($firstId !== null && (int) $firstId !== $anchor['id']) {
                $broken[] = "#{$firstId}: chain start does not match the anchor (#{$anchor['id']}) — records deleted";
            } else {
                $prev = $anchor['prev_hash'];
            }
        }

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
