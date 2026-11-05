<?php

namespace App\Http\Controllers;

use App\Enums\ScimVersion;
use App\Models\Assessment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('profile.institution', 'profile.profession');
        $byVersion = Assessment::query()->ownedBy($user)->toBase()->selectRaw('scim_version, count(*) as c')->groupBy('scim_version')->pluck('c', 'scim_version');

        $hour = (int) now()->format('G');

        return view('dashboard', [
            'user' => $user,
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'),
            'patientCount' => Patient::query()->ownedBy($user)->count(),
            'assessmentCount' => (int) $byVersion->sum(),
            'iii' => (int) ($byVersion[ScimVersion::III->value] ?? 0),
            'iv' => (int) ($byVersion[ScimVersion::IV->value] ?? 0),
            'recent' => Patient::query()->ownedBy($user)
                ->with('latestAssessment.result')
                ->withMax('assessments', 'assessed_on')
                ->orderByDesc('assessments_max_assessed_on')->latest('id')
                ->limit(5)->get(),
        ]);
    }
}
