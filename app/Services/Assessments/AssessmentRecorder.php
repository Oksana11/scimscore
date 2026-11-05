<?php

namespace App\Services\Assessments;

use App\Enums\AssessmentSource;
use App\Enums\ScimVersion;
use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserConsent;
use App\Scoring\ScimCalculator;
use App\Scoring\ScimDefinition;
use App\Scoring\ScimInput;
use App\Support\Security\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Calculates and stores an assessment together with its result in one transaction.
 * Saved assessments are never modified afterwards.
 */
final class AssessmentRecorder
{
    public function __construct(
        private readonly ScimCalculator $calculator,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Quick Assessment: no account, no patient. Consent is stored with date and time.
     *
     * @param  array<string, mixed>  $answers
     */
    public function recordQuick(ScimVersion $version, array $answers, PatientSnapshot $patient, CarbonInterface $assessedOn, ?User $user = null): Assessment
    {
        return $this->record(AssessmentSource::Quick, $version, $answers, $patient, $assessedOn, $user, null, false);
    }

    /**
     * Assessment saved to a patient in the user's account.
     *
     * @param  array<string, mixed>  $answers
     */
    public function recordForPatient(Patient $patientModel, ScimVersion $version, array $answers, PatientSnapshot $patient, CarbonInterface $assessedOn, User $user): Assessment
    {
        $isFirst = ! $patientModel->assessments()->exists();

        return $this->record(AssessmentSource::Account, $version, $answers, $patient, $assessedOn, $user, $patientModel, $isFirst);
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private function record(
        AssessmentSource $source,
        ScimVersion $version,
        array $answers,
        PatientSnapshot $patient,
        CarbonInterface $assessedOn,
        ?User $user,
        ?Patient $patientModel,
        bool $isFirst,
    ): Assessment {
        // Validates answers (throws InvalidScimAnswers) before anything is written.
        $result = $this->calculator->calculate($version, new ScimInput($answers, $patient->ams, $patient->age, $patient->gender));
        $clean = array_map('intval', array_intersect_key($answers, array_flip(array_keys(ScimDefinition::for($version)->allowedScores()))));

        $assessment = DB::transaction(function () use ($source, $version, $clean, $patient, $assessedOn, $user, $patientModel, $isFirst, $result) {
            $consent = UserConsent::query()->create([
                'user_id' => $source === AssessmentSource::Quick ? null : $user?->id,
                'terms_version' => (string) config('scim.terms_version'),
                'accepted_at' => now(),
            ]);

            $assessment = Assessment::query()->create([
                'source' => $source,
                'user_id' => $source === AssessmentSource::Quick ? null : $user?->id,
                'patient_id' => $patientModel?->id,
                'consent_id' => $consent->id,
                'scim_version' => $version,
                'assessed_on' => $assessedOn->toDateString(),
                'is_first' => $isFirst,
                'gender' => $patient->gender,
                'age' => $patient->age,
                'nli' => $patient->nli,
                'ais' => $patient->ais,
                'ams' => $patient->ams,
                'answers' => $clean,
                'calc_version' => $result->calcVersion,
            ]);

            $assessment->result()->create($result->toResultAttributes());

            return $assessment;
        });

        // Quick assessments are anonymous: no user id in the audit record.
        $this->audit->log(
            $source === AssessmentSource::Quick ? 'assessment.quick_created' : 'assessment.created',
            $assessment,
            ['version' => $version->value],
            anonymous: $source === AssessmentSource::Quick,
        );

        return $assessment->load('result');
    }
}
