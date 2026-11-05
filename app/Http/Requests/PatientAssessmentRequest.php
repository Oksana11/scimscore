<?php

namespace App\Http\Requests;

use App\Enums\AisGrade;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Models\Patient;
use App\Scoring\ScimDefinition;
use App\Services\Assessments\PatientSnapshot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Assessment of a registered patient: gender comes from the patient record,
 * age is derived from the year of birth and the assessment date.
 */
class PatientAssessmentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $available = array_values(array_map(
            fn (ScimVersion $v) => $v->value,
            array_filter(ScimVersion::cases(), fn (ScimVersion $v) => ScimDefinition::isAvailable($v)),
        ));

        /** @var Patient $patient */
        $patient = $this->route('patient');

        return [
            'scim_version' => ['required', Rule::in($available)],
            'assessed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$patient->birth_year.'-01-01'],
            'ams' => ['nullable', 'integer', 'between:0,100'],
            'nli' => ['required', Rule::enum(NeurologicalLevel::class)],
            'ais' => ['required', Rule::enum(AisGrade::class)],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'between:0,15'],
            'accept_terms' => ['accepted'],
        ];
    }

    public function version(): ScimVersion
    {
        return ScimVersion::from($this->validated('scim_version'));
    }

    public function assessedOn(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->validated('assessed_on'))->startOfDay();
    }

    public function snapshot(Patient $patient): PatientSnapshot
    {
        return new PatientSnapshot(
            gender: $patient->gender,
            age: max(0, min(120, $this->assessedOn()->year - $patient->birth_year)),
            ams: $this->validated('ams') === null ? null : (int) $this->validated('ams'),
            nli: NeurologicalLevel::from($this->validated('nli')),
            ais: AisGrade::from($this->validated('ais')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        return (array) $this->validated('answers');
    }
}
