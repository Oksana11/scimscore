<?php

namespace App\Http\Requests;

use App\Enums\AisGrade;
use App\Enums\Etiology;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Rules\NotIdentifying;
use App\Scoring\ScimDefinition;
use App\Services\Assessments\PatientSnapshot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Patient data for a calculation. Only gender, age and AMS take part in the formula;
 * level and AIS are kept for statistics. No identifying fields exist here.
 */
class QuickAssessmentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $available = array_values(array_filter(
            array_map(fn (ScimVersion $v) => $v->value, ScimVersion::cases()),
            fn (string $v) => ScimDefinition::isAvailable(ScimVersion::from($v)),
        ));

        return [
            'scim_version' => ['required', Rule::in($available)],
            'assessed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:2000-01-01'],
            'patient_code' => ['required', 'string', 'min:1', 'max:50', new NotIdentifying],
            'gender' => ['required', Rule::enum(Gender::class)],
            'age' => ['required', 'integer', 'between:0,120'],
            'ams' => ['required', 'integer', 'between:0,100'],
            'etiology' => ['nullable', Rule::enum(Etiology::class)],
            'nli' => ['required', Rule::enum(NeurologicalLevel::class)],
            'ais' => ['required', Rule::enum(AisGrade::class)],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'between:0,15'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.*.required' => 'Please answer every item.',
            'accept_terms.accepted' => 'You must accept the terms to calculate.',
        ];
    }

    public function version(): ScimVersion
    {
        return ScimVersion::from($this->validated('scim_version'));
    }

    public function snapshot(): PatientSnapshot
    {
        return new PatientSnapshot(
            gender: Gender::from((int) $this->validated('gender')),
            age: (int) $this->validated('age'),
            ams: $this->validated('ams') === null ? null : (int) $this->validated('ams'),
            nli: NeurologicalLevel::from($this->validated('nli')),
            ais: AisGrade::from($this->validated('ais')),
        );
    }

    public function assessedOn(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->validated('assessed_on'))->startOfDay();
    }

    /**
     * @param  array<string, mixed>  $caregiver  from the session (step 1)
     * @return array<string, mixed>
     */
    public function details(array $caregiver): array
    {
        return [
            'caregiver_profession_id' => $caregiver['profession_id'] ?? null,
            'caregiver_country_code' => $caregiver['country_code'] ?? null,
            'caregiver_institution_id' => $caregiver['institution_id'] ?? null,
            'caregiver_title' => $caregiver['professional_title'] ?? null,
            'caregiver_organization' => $caregiver['organization'] ?? null,
            'caregiver_department' => $caregiver['department'] ?? null,
            'patient_code' => trim((string) $this->validated('patient_code')),
            'etiology' => Etiology::tryFrom((string) $this->validated('etiology')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        return (array) $this->validated('answers');
    }
}
