<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Profession;
use App\Services\Auth\EmailChangeService;
use App\Services\Institutions\InstitutionResolver;
use App\Support\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly InstitutionResolver $institutions,
        private readonly EmailChangeService $emailChange,
        private readonly AuditLogger $audit,
    ) {}

    public function complete(Request $request): View|RedirectResponse
    {
        if ($request->user()->profile) {
            return redirect()->route('profile.edit');
        }

        return view('profile.edit', $this->formData($request) + ['firstTime' => true]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', $this->formData($request) + ['firstTime' => false]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'profession_id' => ['required', 'integer', Rule::exists('professions', 'id')->where('is_active', true)],
            'country_code' => ['required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'institution' => ['required', 'string', 'max:40', 'regex:/^(local:\d+|ror:0[a-z0-9]{6}[0-9]{2}|custom)$/'],
            'institution_custom_name' => ['nullable', 'required_if:institution,custom', 'string', 'min:3', 'max:255'],
            'professional_title' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        $institution = $this->institutions->resolve($data['institution'], $data['institution_custom_name'] ?? null, $data['country_code'], $user);

        if (! $institution) {
            return back()->withInput()->withErrors(['institution' => __('Please choose an institution from the list or add a new one.')]);
        }

        $firstTime = ! $user->profile;

        $user->profile()->updateOrCreate([], [
            'profession_id' => $data['profession_id'],
            'country_code' => $data['country_code'],
            'institution_id' => $institution->id,
            'professional_title' => $data['professional_title'] ?? null,
            'organization' => $data['organization'] ?? null,
            'department' => $data['department'] ?? null,
        ]);

        $this->audit->log($firstTime ? 'profile.completed' : 'profile.updated', $user, ['institution_id' => $institution->id]);

        return $firstTime
            ? redirect()->route('dashboard')
            : redirect()->route('profile.edit')->with('status', __('Profile saved.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        $user = $request->user()->load('profile.institution');

        return [
            'user' => $user,
            'profile' => $user->profile,
            'professions' => Profession::query()->active()->get(['id', 'name']),
            'countries' => Country::query()->active()->orderBy('name')->get(['code', 'name']),
            'pendingEmail' => $this->emailChange->pending($user),
        ];
    }
}
