<?php

namespace App\Services\Admin;

use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Administrator breakdowns (spec, Appendix B): users by country / institution / profession,
 * assessments by SCIM version, patients by gender / year of birth / injury level / AIS,
 * and assessment counts per period. Aggregates only — no identifiers.
 */
final class AdminStatistics
{
    /**
     * @param  array{date_from?: string|null, date_to?: string|null, period?: string|null}  $f
     * @return array<string, list<array{label: string, count: int}>>
     */
    public function breakdowns(array $f): array
    {
        $assessments = fn (): Builder => Assessment::query()
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->whereDate('assessed_on', '>=', $v))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->whereDate('assessed_on', '<=', $v));

        return [
            'Users by country' => $this->rows(User::query()
                ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->join('countries', 'countries.code', '=', 'user_profiles.country_code')
                ->groupBy('countries.name')->selectRaw('countries.name as label, count(*) as c')),
            'Users by institution' => $this->rows(User::query()
                ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->join('institutions', 'institutions.id', '=', 'user_profiles.institution_id')
                ->groupBy('institutions.name')->selectRaw('institutions.name as label, count(*) as c'), 50),
            'Users by profession' => $this->rows(User::query()
                ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->join('professions', 'professions.id', '=', 'user_profiles.profession_id')
                ->groupBy('professions.name')->selectRaw('professions.name as label, count(*) as c')),
            'Assessments by SCIM version' => $this->rows($assessments()->groupBy('scim_version')->selectRaw('scim_version as label, count(*) as c'), map: fn ($v) => 'SCIM '.$v),
            'Assessments by source' => $this->rows($assessments()->groupBy('source')->selectRaw('source as label, count(*) as c')),
            'Assessments by period' => $this->periods($assessments(), $f['period'] ?? 'monthly'),
            'Patients by gender' => $this->rows(Patient::query()->groupBy('gender')->selectRaw('gender as label, count(*) as c'), map: fn ($v) => ((int) $v) === 1 ? 'Male' : 'Female'),
            'Patients by decade of birth' => $this->rows(Patient::query()->groupByRaw('birth_year - (birth_year % 10)')->selectRaw('(birth_year - (birth_year % 10)) as label, count(*) as c'), map: fn ($v) => ((int) $v).'s', sortByLabel: true),
            'Assessments by injury level' => $this->rows($assessments()->whereNotNull('nli')->groupBy('nli')->selectRaw('nli as label, count(*) as c')),
            'Assessments by AIS grade' => $this->rows($assessments()->whereNotNull('ais')->groupBy('ais')->selectRaw('ais as label, count(*) as c'), sortByLabel: true),
        ];
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function rows(Builder $query, int $limit = 100, ?callable $map = null, bool $sortByLabel = false): array
    {
        $rows = $query->toBase()->get()->map(fn ($r) => [
            'label' => $map ? $map($r->label) : (string) $r->label,
            'count' => (int) $r->c,
        ]);

        $rows = $sortByLabel ? $rows->sortBy('label') : $rows->sortByDesc('count');

        return $rows->take($limit)->values()->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function periods(Builder $query, string $period): array
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $format = match ($period) {
            'daily' => '%Y-%m-%d',
            'yearly' => '%Y',
            default => '%Y-%m',
        };
        $expr = $sqlite ? "strftime('{$format}', assessed_on)" : "DATE_FORMAT(assessed_on, '{$format}')";

        return $query->toBase()->selectRaw("{$expr} as label, count(*) as c")->groupByRaw($expr)->orderBy('label')->limit(400)->get()
            ->map(fn ($r) => ['label' => (string) $r->label, 'count' => (int) $r->c])->all();
    }
}
