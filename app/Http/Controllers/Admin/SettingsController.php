<?php

namespace App\Http\Controllers\Admin;

use App\Models\Country;
use App\Models\Institution;
use App\Models\Patient;
use App\Models\Profession;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reference data: countries list, professions, custom institutions (rename / merge into ROR).
 */
class SettingsController extends AdminController
{
    public function index(): View
    {
        return view('admin.settings', [
            'countries' => Country::query()->orderBy('name')->get(),
            'professions' => Profession::query()->orderBy('sort')->get(),
            'customInstitutions' => Institution::query()->where('is_custom', true)->whereNull('merged_into_id')
                ->withCount('profiles')->orderBy('name')->get(),
        ]);
    }

    public function toggleCountry(Country $country): RedirectResponse
    {
        $country->update(['is_active' => ! $country->is_active]);
        $this->log('admin.country_toggled', $country, ['active' => $country->is_active]);

        return back()->with('status', "{$country->name}: ".($country->is_active ? 'active' : 'hidden'));
    }

    public function storeProfession(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('professions', 'name')]]);
        $p = Profession::query()->create(['name' => $data['name'], 'sort' => (int) Profession::query()->max('sort') + 10]);
        $this->log('admin.profession_created', $p);

        return back()->with('status', 'Profession added.');
    }

    public function toggleProfession(Profession $profession): RedirectResponse
    {
        $profession->update(['is_active' => ! $profession->is_active]);
        $this->log('admin.profession_toggled', $profession, ['active' => $profession->is_active]);

        return back()->with('status', 'Profession updated.');
    }

    public function renameInstitution(Request $request, Institution $institution): RedirectResponse
    {
        abort_unless($institution->is_custom, 404);
        $data = $request->validate(['name' => ['required', 'string', 'min:3', 'max:255']]);
        $institution->update(['name' => $data['name']]);
        $this->log('admin.institution_renamed', $institution);

        return back()->with('status', 'Institution renamed.');
    }

    /**
     * Merge a custom institution into another one (e.g. its official ROR record):
     * profiles and patients are moved, the custom record points to the target.
     */
    public function mergeInstitution(Request $request, Institution $institution): RedirectResponse
    {
        abort_unless($institution->is_custom, 404);
        $data = $request->validate(['target_id' => ['required', 'integer', Rule::exists('institutions', 'id'), Rule::notIn([$institution->id])]]);

        DB::transaction(function () use ($institution, $data) {
            UserProfile::query()->where('institution_id', $institution->id)->update(['institution_id' => $data['target_id']]);
            Patient::query()->where('institution_id', $institution->id)->update(['institution_id' => $data['target_id']]);
            $institution->update(['merged_into_id' => $data['target_id']]);
        });

        $this->log('admin.institution_merged', $institution, ['target_id' => $data['target_id']]);

        return back()->with('status', 'Institution merged.');
    }
}
