<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('profile.institution', 'profile.profession');

        return view('dashboard', [
            'user' => $user,
            'patientCount' => Patient::query()->ownedBy($user)->count(),
            'assessmentCount' => Assessment::query()->ownedBy($user)->count(),
            'recent' => Assessment::query()->ownedBy($user)
                ->with(['result', 'patient:id,public_id,system_id,research_id'])
                ->latest('assessed_on')->latest('id')->limit(8)->get(),
        ]);
    }
}
