<?php

namespace App\Console\Commands;

use App\Services\Legacy\DrupalImporter;
use App\Support\Security\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('legacy:import-drupal {dump : Path to the mysqldump .sql file of the old site} {--dry-run : Only report what would be imported}')]
#[Description('Import old scimscore.com (Drupal) SCIM III submissions as anonymous assessments')]
class LegacyImportDrupal extends Command
{
    public function handle(DrupalImporter $importer, AuditLogger $audit): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info(($dry ? '[dry run] ' : '').'Reading '.$this->argument('dump').' …');

        $stats = $importer->import((string) $this->argument('dump'), $dry);

        $this->table(['Submissions', $dry ? 'Would import' : 'Imported', 'Already imported', 'Skipped'], [[
            $stats['total'], $stats['imported'], $stats['already'], array_sum($stats['skipped']),
        ]]);
        foreach ($stats['skipped'] as $reason => $n) {
            $this->line("  skipped ({$reason}): {$n}");
        }

        if (! $dry) {
            $audit->log('legacy.drupal_imported', context: ['imported' => $stats['imported'], 'skipped' => array_sum($stats['skipped'])], actorType: 'system');
            $this->warn('Delete the dump file now: it contains personal data of the old site (caregiver names, emails, patient codes).');
        }

        return self::SUCCESS;
    }
}
