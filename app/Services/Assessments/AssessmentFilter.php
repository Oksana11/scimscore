<?php

namespace App\Services\Assessments;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Models\Assessment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * "Filtering and Data Exploration Export" for a regular user (spec, Appendix B).
 * Always applied on top of a query already limited to the user's own assessments.
 */
final class AssessmentFilter
{
    public const GRADES = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_year_from' => ['nullable', 'integer', 'between:1900,2100'],
            'birth_year_to' => ['nullable', 'integer', 'between:1900,2100', 'gte:birth_year_from'],
            'nli' => ['nullable', Rule::enum(NeurologicalLevel::class)],
            'ais' => ['nullable', Rule::enum(AisGrade::class)],
            'ams_from' => ['nullable', 'integer', 'between:0,100'],
            'ams_to' => ['nullable', 'integer', 'between:0,100', 'gte:ams_from'],
            'version' => ['nullable', Rule::enum(ScimVersion::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'total_from' => ['nullable', 'integer', 'between:0,100'],
            'total_to' => ['nullable', 'integer', 'between:0,100', 'gte:total_from'],
            'sciarmi_from' => ['nullable', 'numeric', 'between:0,1000'],
            'sciarmi_to' => ['nullable', 'numeric', 'between:0,1000', 'gte:sciarmi_from'],
            'grade_from' => ['nullable', Rule::in(self::GRADES)],
            'grade_to' => ['nullable', Rule::in(self::GRADES)],
            'type' => ['nullable', Rule::in(['first', 'followup'])],
        ];
    }

    /**
     * @param  Builder<Assessment>  $query
     * @param  array<string, mixed>  $f  validated filters
     * @return Builder<Assessment>
     */
    public static function apply(Builder $query, array $f): Builder
    {
        $has = fn (string $k) => isset($f[$k]) && $f[$k] !== '';

        return $query
            ->when($has('gender'), fn ($q) => $q->where('gender', $f['gender']))
            ->when($has('birth_year_from'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('birth_year', '>=', $f['birth_year_from'])))
            ->when($has('birth_year_to'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('birth_year', '<=', $f['birth_year_to'])))
            ->when($has('nli'), fn ($q) => $q->where('nli', $f['nli']))
            ->when($has('ais'), fn ($q) => $q->where('ais', $f['ais']))
            ->when($has('ams_from'), fn ($q) => $q->where('ams', '>=', $f['ams_from']))
            ->when($has('ams_to'), fn ($q) => $q->where('ams', '<=', $f['ams_to']))
            ->when($has('version'), fn ($q) => $q->where('scim_version', $f['version']))
            ->when($has('date_from'), fn ($q) => $q->whereDate('assessed_on', '>=', $f['date_from']))
            ->when($has('date_to'), fn ($q) => $q->whereDate('assessed_on', '<=', $f['date_to']))
            ->when($has('type'), fn ($q) => $q->where('is_first', $f['type'] === 'first'))
            ->whereHas('result', function ($r) use ($f, $has) {
                $r->when($has('total_from'), fn ($q) => $q->where('total', '>=', $f['total_from']))
                    ->when($has('total_to'), fn ($q) => $q->where('total', '<=', $f['total_to']))
                    ->when($has('sciarmi_from'), fn ($q) => $q->where('sciarmi_total', '>=', $f['sciarmi_from']))
                    ->when($has('sciarmi_to'), fn ($q) => $q->where('sciarmi_total', '<=', $f['sciarmi_to']))
                    ->when($has('grade_from'), fn ($q) => $q->where('grade', '>=', $f['grade_from']))
                    ->when($has('grade_to'), fn ($q) => $q->where('grade', '<=', $f['grade_to']));
            });
    }

    /**
     * @param  array<string, mixed>  $f
     */
    public static function isActive(array $f): bool
    {
        return array_filter($f, fn ($v) => $v !== null && $v !== '') !== [];
    }
}
