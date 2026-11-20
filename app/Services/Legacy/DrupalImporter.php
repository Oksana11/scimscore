<?php

namespace App\Services\Legacy;

use App\Enums\AisGrade;
use App\Enums\AssessmentSource;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Models\Assessment;
use App\Models\UserConsent;
use App\Scoring\InvalidScimAnswers;
use App\Scoring\ScimCalculator;
use App\Scoring\ScimInput;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Imports SCIM III submissions of the old Drupal 6 site (webform module) as anonymous
 * assessments (source = legacy, no user, no patient).
 *
 * Only these fields are read: answers v1–v19, patient age, gender, AMS, AIS, neurological
 * level and the submission date. Caregiver name, email, fax, hospital, country, profession,
 * the patient code and the IP address are never read into the new system.
 * Results are recalculated with the current formula; the old stored results are ignored.
 */
final class DrupalImporter
{
    /** Fields read from the old form (form_key => our name). Everything else is ignored. */
    private const FIELDS = [
        'patient_age' => 'age',
        'patient_kind' => 'gender',
        'american_spinal_injury_association_motor_score_ams' => 'ams',
        'american_spinal_injury_association_impairment_scale_ais_grade' => 'ais',
        'spinal_cord_lesion_neurological_level' => 'nli',
    ];

    public function __construct(private readonly ScimCalculator $calculator) {}

    /**
     * @return array{total: int, imported: int, already: int, skipped: array<string, int>}
     */
    public function import(string $dumpPath, bool $dryRun = false): array
    {
        $reader = new MysqlDumpReader($dumpPath);

        // component id => our key (v1..v19 or a FIELDS name)
        $components = [];
        foreach ($reader->rows('webform_component') as [$nid, $cid, , $key]) {
            if (preg_match('/^v(1[0-9]|[1-9])$/', (string) $key)) {
                $components[$nid.':'.$cid] = $key;
            } elseif (isset(self::FIELDS[$key])) {
                $components[$nid.':'.$cid] = self::FIELDS[$key];
            }
        }

        $submissions = [];
        foreach ($reader->rows('webform_submissions') as [$sid, $nid, , $submitted, , $isDraft]) {
            if ((int) $isDraft === 0) {
                $submissions[(int) $sid] = ['nid' => $nid, 'submitted' => (int) $submitted, 'data' => []];
            }
        }

        foreach ($reader->rows('webform_submitted_data') as [$nid, $sid, $cid, , $value]) {
            $key = $components[$nid.':'.$cid] ?? null;
            if ($key !== null && isset($submissions[(int) $sid])) {
                $submissions[(int) $sid]['data'][$key] = trim((string) $value);
            }
        }

        $stats = ['total' => count($submissions), 'imported' => 0, 'already' => 0, 'skipped' => []];
        $skip = function (string $reason) use (&$stats): void {
            $stats['skipped'][$reason] = ($stats['skipped'][$reason] ?? 0) + 1;
        };

        $existing = Assessment::query()->whereNotNull('legacy_ref')->pluck('legacy_ref')->flip();

        foreach (array_chunk($submissions, 200, true) as $chunk) {
            DB::transaction(function () use ($chunk, $dryRun, $existing, &$stats, $skip) {
                foreach ($chunk as $sid => $sub) {
                    $ref = hash('sha256', 'drupal:'.$sid);
                    if ($existing->has($ref)) {
                        $stats['already']++;

                        continue;
                    }

                    $d = $sub['data'];
                    $gender = Gender::tryFrom((int) ($d['gender'] ?? 0));
                    $age = $this->age($d['age'] ?? '');

                    if ($gender === null) {
                        $skip('missing gender');

                        continue;
                    }
                    if ($age === null) {
                        $skip('invalid age');

                        continue;
                    }
                    if ($sub['submitted'] <= 0) {
                        $skip('missing date');

                        continue;
                    }

                    $answers = [];
                    for ($i = 1; $i <= 19; $i++) {
                        $answers['v'.$i] = $d['v'.$i] ?? null;
                    }
                    $ams = $this->ams($d['ams'] ?? '');

                    try {
                        $result = $this->calculator->calculate(ScimVersion::III, new ScimInput($answers, $ams, $age, $gender));
                    } catch (InvalidScimAnswers) {
                        $skip('invalid answers');

                        continue;
                    }

                    $stats['imported']++;
                    if ($dryRun) {
                        continue;
                    }

                    $date = Carbon::createFromTimestamp($sub['submitted'], 'UTC');
                    $consent = UserConsent::query()->create([
                        'user_id' => null,
                        'terms_version' => 'legacy-drupal',
                        'accepted_at' => $date,
                    ]);

                    $assessment = Assessment::query()->create([
                        'source' => AssessmentSource::Legacy,
                        'consent_id' => $consent->id,
                        'scim_version' => ScimVersion::III,
                        'assessed_on' => $date->toDateString(),
                        'is_first' => false,
                        'gender' => $gender,
                        'age' => $age,
                        'nli' => $this->level($d['nli'] ?? ''),
                        'ais' => $this->ais($d['ais'] ?? ''),
                        'ams' => $ams,
                        'answers' => array_map('intval', $answers),
                        'calc_version' => $result->calcVersion,
                        'legacy_ref' => $ref,
                    ]);
                    $assessment->result()->create($result->toResultAttributes());
                }
            });
        }

        return $stats;
    }

    private function age(string $v): ?int
    {
        if (! is_numeric($v)) {
            return null;
        }
        $age = (int) floor((float) $v);

        return $age >= 0 && $age <= 120 ? $age : null;
    }

    private function ams(string $v): ?int
    {
        return ctype_digit($v) && (int) $v <= 100 ? (int) $v : null;
    }

    private function ais(string $v): ?AisGrade
    {
        return preg_match('/^[A-E]$/i', $v) ? AisGrade::from(strtoupper($v)) : null;
    }

    /**
     * Free text such as "c5-c6", "Th1", "D8", "T12 incomplete" → first level mentioned, or null.
     */
    private function level(string $v): ?NeurologicalLevel
    {
        if (! preg_match('/^\s*(C|TH|T|D|L|S)\s*-?\s*(\d{1,2})\b/i', $v, $m)) {
            return null;
        }
        $segment = match (strtoupper($m[1])) {
            'TH', 'D' => 'T',
            default => strtoupper($m[1]),
        };

        return NeurologicalLevel::tryFrom($segment.(int) $m[2]);
    }
}
