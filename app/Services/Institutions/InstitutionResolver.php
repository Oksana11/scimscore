<?php

namespace App\Services\Institutions;

use App\Models\Country;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Turns the user's choice in the profile form into an Institution row:
 *   "local:<id>"  — existing institution in our database
 *   "ror:<id>"    — institution from ROR (details re-fetched server-side, the browser is not trusted)
 *   "custom"      — "Add New Institution": a manually entered name, marked is_custom
 */
final class InstitutionResolver
{
    public function __construct(private readonly RorClient $ror) {}

    /**
     * @return array<string, mixed> local + ROR suggestions for the autocomplete
     */
    public function suggestions(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return ['local' => [], 'ror' => []];
        }

        $local = Institution::query()
            ->whereNull('merged_into_id')
            ->where('name', 'like', '%'.addcslashes($query, '%_\\').'%')
            ->orderBy('is_custom')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'country_code', 'ror_id', 'is_custom']);

        $known = $local->pluck('ror_id')->filter()->all();

        $ror = array_values(array_filter(
            $this->ror->search($query),
            fn ($r) => ! in_array($r['ror_id'], $known, true)
        ));

        return [
            'local' => $local->map(fn (Institution $i) => [
                'ref' => 'local:'.$i->id,
                'name' => $i->name,
                'country_code' => $i->country_code,
                'custom' => $i->is_custom,
            ])->all(),
            'ror' => array_map(fn ($r) => [
                'ref' => 'ror:'.$r['ror_id'],
                'name' => $r['name'],
                'country_code' => $r['country_code'],
                'city' => $r['city'],
            ], $ror),
        ];
    }

    /**
     * @return Institution|null null when the reference is invalid
     */
    public function resolve(string $ref, ?string $customName, string $countryCode, User $user): ?Institution
    {
        if (preg_match('/^local:(\d+)$/', $ref, $m)) {
            $institution = Institution::query()->find((int) $m[1]);

            return $institution?->merged_into_id ? $institution->mergedInto : $institution;
        }

        if (preg_match('/^ror:(.+)$/', $ref, $m)) {
            if ($existing = Institution::query()->where('ror_id', $m[1])->first()) {
                return $existing;
            }

            $org = $this->ror->find($m[1]);

            if (! $org) {
                return null;
            }

            return Institution::query()->firstOrCreate(['ror_id' => $org['ror_id']], [
                'name' => $org['name'],
                'country_code' => $org['country_code'] && Country::query()->whereKey($org['country_code'])->exists() ? $org['country_code'] : null,
                'is_custom' => false,
            ]);
        }

        if ($ref === 'custom') {
            $name = trim(preg_replace('/\s+/u', ' ', (string) $customName) ?? '');

            if (mb_strlen($name) < 3) {
                return null;
            }

            return DB::transaction(function () use ($name, $countryCode, $user) {
                $existing = Institution::query()
                    ->where('is_custom', true)
                    ->where('country_code', $countryCode)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();

                return $existing ?? Institution::query()->create([
                    'name' => mb_substr($name, 0, 255),
                    'country_code' => $countryCode,
                    'is_custom' => true,
                    'created_by_user_id' => $user->id,
                ]);
            });
        }

        return null;
    }
}
