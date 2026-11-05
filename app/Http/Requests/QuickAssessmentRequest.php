<?php

namespace App\Http\Requests;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
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
            'gender' => ['required', Rule::enum(Gender::class)],
            'age' => ['required', 'integer', 'between:0,120'],
            'ams' => ['nullable', 'integer', 'between:0,100'],
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
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        return (array) $this->validated('answers');
    }
}
