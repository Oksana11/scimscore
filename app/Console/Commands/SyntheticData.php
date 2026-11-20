<?php

namespace App\Console\Commands;

use App\Enums\AisGrade;
use App\Enums\Etiology;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Models\Institution;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Scoring\ScimDefinition;
use App\Services\Assessments\AssessmentRecorder;
use App\Services\Assessments\PatientSnapshot;
use App\Support\Security\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills a dev/demo copy with made-up data, optionally after wiping all real data.
 * Refuses to run unless ALLOW_SYNTHETIC_DATA=true and the site is not scimscore.com.
 * Kept: administrators, pages, email texts, settings, reference lists (countries, professions).
 */
#[Signature('dev:synthetic-data
    {--wipe : First delete ALL users, patients, assessments, support messages and the audit log}
    {--users=5 : Demo clinician accounts}
    {--patients=8 : Patients per clinician}
    {--quick=40 : Quick assessments without an account}
    {--force : Do not ask for confirmation}')]
#[Description('DEV ONLY: replace data with synthetic demo data')]
class SyntheticData extends Command
{
    private const PRODUCTION_HOSTS = ['scimscore.com', 'www.scimscore.com'];

    /** Wiped in this order (children first). */
    private const WIPE = [
        'support_replies', 'support_messages', 'assessment_results', 'assessments', 'user_consents',
        'patients', 'email_change_requests', 'otp_requests', 'user_profiles', 'sessions', 'users', 'audit_logs',
    ];

    public function handle(AssessmentRecorder $recorder, AuditLogger $audit): int
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if (! config('scim.allow_synthetic_data') || in_array($host, self::PRODUCTION_HOSTS, true)) {
            $this->error('Refused: set ALLOW_SYNTHETIC_DATA=true in .env of a dev/demo copy (never on '.implode(' / ', self::PRODUCTION_HOSTS).').');

            return self::FAILURE;
        }

        $wipe = (bool) $this->option('wipe');
        if (! $this->option('force') && ! $this->confirm($wipe ? "Delete ALL real data on {$host} and add synthetic data?" : "Add synthetic data on {$host}?")) {
            return self::FAILURE;
        }

        mt_srand(20261120); // same demo data every run

        if ($wipe) {
            DB::transaction(function () {
                DB::table('institutions')->update(['created_by_user_id' => null]);
                foreach (self::WIPE as $table) {
                    DB::table($table)->delete();
                }
                Setting::put(AuditLogger::ANCHOR_KEY, null); // new, empty audit chain
            });
            $this->info('Real data deleted: '.implode(', ', self::WIPE).'.');
        }

        $professions = DB::table('professions')->pluck('id')->all();
        $countries = ['IL', 'IL', 'IL', 'US', 'DE', 'IT', 'CH'];
        $definition = ScimDefinition::for(ScimVersion::III);
        $allowed = $definition->allowedScores();

        $institutions = collect(range(1, 3))->map(fn ($i) => Institution::query()->firstOrCreate(
            ['name' => "Demo Rehabilitation Center {$i}"],
            ['country_code' => 'IL', 'is_custom' => true],
        ));

        $counts = ['users' => 0, 'patients' => 0, 'assessments' => 0, 'quick' => 0];

        for ($u = 1; $u <= (int) $this->option('users'); $u++) {
            $email = "demo{$u}@example.test";
            $existing = User::query()->whereBlind('email', $email)->first();
            $user = $existing ?? User::query()->create(['email' => $email]);
            $user->profile()->updateOrCreate([], [
                'profession_id' => $professions[array_rand($professions)],
                'country_code' => $countries[array_rand($countries)],
                'institution_id' => $institutions->random()->id,
            ]);
            $counts['users']++;

            if ($existing && $user->patients()->exists()) {
                continue; // demo user already filled on an earlier run
            }

            for ($p = 1; $p <= (int) $this->option('patients'); $p++) {
                $nli = $this->pick(NeurologicalLevel::cases());
                $ais = $this->pick(AisGrade::cases());
                $patient = new Patient([
                    'research_id' => sprintf('DEMO-%02d-%03d', $u, $p),
                    'alias' => "Demo patient {$u}.{$p}",
                    'gender' => $this->pick(Gender::cases()),
                    'birth_year' => mt_rand(1948, 2006),
                ]);
                $patient->user()->associate($user);
                $patient->save();
                $counts['patients']++;

                // 1–4 visits over the last two years; independence tends to grow over time.
                $level = mt_rand(5, 60) / 100;
                $date = CarbonImmutable::now()->subDays(mt_rand(200, 720));
                for ($v = 0, $visits = mt_rand(1, 4); $v < $visits; $v++) {
                    $recorder->recordForPatient($patient, ScimVersion::III, $this->answers($allowed, $level), new PatientSnapshot(
                        gender: $patient->gender,
                        age: max(0, $date->year - $patient->birth_year),
                        ams: mt_rand(0, 100),
                        nli: $nli,
                        ais: $ais,
                    ), $date, $user);
                    $counts['assessments']++;
                    $level = min(1, $level + mt_rand(5, 20) / 100);
                    $date = $date->addDays(mt_rand(30, 120));
                    if ($date->isFuture()) {
                        break;
                    }
                }
            }
        }

        for ($q = 1; $q <= (int) $this->option('quick'); $q++) {
            $date = CarbonImmutable::now()->subDays(mt_rand(1, 700));
            $recorder->recordQuick(ScimVersion::III, $this->answers($allowed, mt_rand(5, 95) / 100), new PatientSnapshot(
                gender: $this->pick(Gender::cases()),
                age: mt_rand(18, 80),
                ams: mt_rand(0, 100),
                nli: $this->pick(NeurologicalLevel::cases()),
                ais: $this->pick(AisGrade::cases()),
            ), $date, [
                'caregiver_profession_id' => $professions[array_rand($professions)],
                'caregiver_country_code' => $countries[array_rand($countries)],
                'caregiver_institution_id' => $institutions->random()->id,
                'patient_code' => sprintf('DEMO-Q-%03d', $q),
                'etiology' => $this->pick(Etiology::cases()),
            ]);
            $counts['quick']++;
        }

        $audit->log('dev.synthetic_data', context: $counts + ['wiped' => $wipe], actorType: 'system');
        $this->table(array_keys($counts), [array_values($counts)]);
        $this->line('Demo sign-in emails: demo1@example.test … (codes are written to the mail log only if MAIL_MAILER=log).');

        return self::SUCCESS;
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return T
     */
    private function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    /**
     * Each item scored around the same share of its maximum, with some noise.
     *
     * @param  array<string, list<int>>  $allowed
     * @return array<string, int>
     */
    private function answers(array $allowed, float $level): array
    {
        $out = [];
        foreach ($allowed as $key => $scores) {
            $i = (int) round(max(0, min(1, $level + mt_rand(-20, 20) / 100)) * (count($scores) - 1));
            $out[$key] = $scores[$i];
        }

        return $out;
    }
}
