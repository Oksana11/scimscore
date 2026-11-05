<?php

namespace App\Http\Requests;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Rules\NotIdentifying;
use App\Services\Patients\PatientData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function isCreate(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $current = $this->isCreate() ? 'required' : 'nullable';

        return [
            'research_id' => ['required', 'string', 'min:2', 'max:50', new NotIdentifying],
            'alias' => ['required', 'string', 'min:1', 'max:50', new NotIdentifying],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_year' => ['required', 'integer', 'between:1900,'.now()->year],
            'nli' => [$current, Rule::enum(NeurologicalLevel::class)],
            'ais' => [$current, Rule::enum(AisGrade::class)],
            'ams' => ['nullable', 'integer', 'between:0,100'],
            'first_nli' => ['nullable', Rule::enum(NeurologicalLevel::class)],
            'first_ais' => ['nullable', Rule::enum(AisGrade::class)],
            'first_ams' => ['nullable', 'integer', 'between:0,100'],
            'confirm_not_duplicate' => ['nullable', 'boolean'],
        ];
    }

    public function data(): PatientData
    {
        $int = fn (string $k) => $this->validated($k) === null ? null : (int) $this->validated($k);

        return new PatientData(
            researchId: trim((string) $this->validated('research_id')),
            alias: trim((string) $this->validated('alias')),
            gender: Gender::from((int) $this->validated('gender')),
            birthYear: (int) $this->validated('birth_year'),
            currentNli: NeurologicalLevel::tryFrom((string) $this->validated('nli')),
            currentAis: AisGrade::tryFrom((string) $this->validated('ais')),
            currentAms: $int('ams'),
            firstNli: NeurologicalLevel::tryFrom((string) $this->validated('first_nli')),
            firstAis: AisGrade::tryFrom((string) $this->validated('first_ais')),
            firstAms: $int('first_ams'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function patientAttributes(): array
    {
        $d = $this->data();

        return [
            'research_id' => $d->researchId,
            'alias' => $d->alias,
            'gender' => $d->gender,
            'birth_year' => $d->birthYear,
            'first_nli' => $d->firstNli,
            'first_ais' => $d->firstAis,
            'first_ams' => $d->firstAms,
        ];
    }
}
