<?php

namespace App\Http\Controllers;

use App\Enums\AisGrade;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Http\Requests\PatientAssessmentRequest;
use App\Models\Assessment;
use App\Models\Patient;
use App\Scoring\InvalidScimAnswers;
use App\Scoring\ScimDefinition;
use App\Services\Assessments\AssessmentRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientAssessmentController extends Controller
{
    public function __construct(private readonly AssessmentRecorder $recorder) {}

    public function create(Request $request, Patient $patient): View
    {
        Gate::authorize('view', $patient);
        Gate::authorize('create', [Assessment::class, $patient]);

        $version = ScimVersion::tryFrom((string) $request->query('version', 'III')) ?? ScimVersion::III;
        if (! ScimDefinition::isAvailable($version)) {
            $version = ScimVersion::III;
        }

        $last = $patient->assessments()->latest('assessed_on')->latest('id')->first();
        $prefill = $request->session()->get("assessment.prefill.{$patient->public_id}")
            ?? ['nli' => $last?->nli?->value, 'ais' => $last?->ais?->value, 'ams' => $last?->ams];

        return view('patients.assessment-create', [
            'patient' => $patient,
            'version' => $version,
            'versions' => array_filter(ScimVersion::cases(), fn ($v) => ScimDefinition::isAvailable($v)),
            'definition' => ScimDefinition::for($version),
            'levels' => NeurologicalLevel::cases(),
            'aisGrades' => AisGrade::cases(),
            'prefill' => $prefill,
            'isFirst' => $last === null,
        ]);
    }

    public function store(PatientAssessmentRequest $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('view', $patient);
        Gate::authorize('create', [Assessment::class, $patient]);

        try {
            $assessment = $this->recorder->recordForPatient(
                $patient,
                $request->version(),
                $request->answers(),
                $request->snapshot($patient),
                $request->assessedOn(),
                $request->user(),
            );
        } catch (InvalidScimAnswers $e) {
            return back()->withInput()->withErrors(
                collect($e->errors)->mapWithKeys(fn ($msg, $key) => ["answers.{$key}" => $msg])->all()
            );
        }

        $request->session()->forget("assessment.prefill.{$patient->public_id}");

        return redirect()->route('assessments.show', $assessment)->with('status', __('Assessment saved.'));
    }
}
