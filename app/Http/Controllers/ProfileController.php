<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaregiverRequest;
use App\Models\Country;
use App\Models\Profession;
use App\Services\Auth\EmailChangeService;
use App\Services\Institutions\InstitutionResolver;
use App\Services\Privacy\DataEraser;
use App\Support\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('profile.complete', $this->formData($request));
    }

    /**
     * Deletes the signed-in user's account. Patients and assessments stay as anonymous research data.
     */
    public function destroy(Request $request, DataEraser $eraser): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:DELETE']], ['confirm.in' => 'Type DELETE to confirm.']);
        $user = $request->user();

        Auth::guard('web')->logout();
        $eraser->eraseAccount($user, 'self');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('Your account has been deleted.'));
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', $this->formData($request));
    }

    public function update(CaregiverRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

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

        $p = $user->profile;

        return [
            'user' => $user,
            'profile' => $p,
            'values' => $p ? [
                'profession_id' => $p->profession_id,
                'country_code' => $p->country_code,
                'institution' => 'local:'.$p->institution_id,
                'institution_display' => $p->institution?->name,
                'professional_title' => $p->professional_title,
                'organization' => $p->organization,
                'department' => $p->department,
            ] : [],
            'professions' => Profession::query()->active()->get(['id', 'name']),
            'countries' => Country::query()->active()->orderBy('name')->get(['code', 'name']),
            'pendingEmail' => $this->emailChange->pending($user),
        ];
    }
}
