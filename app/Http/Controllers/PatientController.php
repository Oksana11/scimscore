<?php

namespace App\Http\Controllers;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use App\Services\Patients\DuplicateDetector;
use App\Support\Security\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(
        private readonly DuplicateDetector $duplicates,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Patient::class);

        $f = $request->validate([
            'research_id' => ['nullable', 'string', 'max:50'],
            'alias' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_year' => ['nullable', 'integer', 'between:1900,2100'],
            'nli' => ['nullable', Rule::enum(NeurologicalLevel::class)],
            'ais' => ['nullable', Rule::enum(AisGrade::class)],
            'ams' => ['nullable', 'integer', 'between:0,100'],
        ]);

        $patients = Patient::query()
            ->ownedBy($request->user())
            ->when($f['research_id'] ?? null, fn (Builder $q, $v) => $q->whereBlind('research_id', $v))
            ->when($f['alias'] ?? null, fn (Builder $q, $v) => $q->whereBlind('alias', $v))
            ->when($f['gender'] ?? null, fn (Builder $q, $v) => $q->where('gender', $v))
            ->when($f['birth_year'] ?? null, fn (Builder $q, $v) => $q->where('birth_year', $v))
            ->when($f['nli'] ?? null, fn (Builder $q, $v) => $q->where(fn ($w) => $w->where('first_nli', $v)->orWhereHas('assessments', fn ($a) => $a->where('nli', $v))))
            ->when($f['ais'] ?? null, fn (Builder $q, $v) => $q->where(fn ($w) => $w->where('first_ais', $v)->orWhereHas('assessments', fn ($a) => $a->where('ais', $v))))
            ->when(isset($f['ams']), fn (Builder $q) => $q->where(fn ($w) => $w->where('first_ams', $f['ams'])->orWhereHas('assessments', fn ($a) => $a->where('ams', $f['ams']))))
            ->withCount('assessments')
            ->withMax('assessments', 'assessed_on')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('patients.index', $this->options() + ['patients' => $patients, 'filters' => $f]);
    }

    public function create(): View
    {
        Gate::authorize('create', Patient::class);

        return view('patients.create', $this->options());
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        Gate::authorize('create', Patient::class);

        $user = $request->user();
        $data = $request->data();
        $check = $this->duplicates->check($user, $data);

        if ($check->blocked()) {
            return back()->withInput()->with('duplicate', [
                'blocked' => true,
                'own' => $check->sameResearchIdOwn?->public_id,
            ])->withErrors(['research_id' => $check->researchIdTakenInAccount
                ? __('This Patient Research ID already exists in your list.')
                : __('This Patient Research ID is already used at your institution.')]);
        }

        if ($check->hasWarnings() && ! $request->boolean('confirm_not_duplicate')) {
            return back()->withInput()->with('duplicate', [
                'blocked' => false,
                'similar_own' => $check->similarOwn->map(fn (Patient $p) => [
                    'public_id' => $p->public_id,
                    'system_id' => $p->system_id,
                    'research_id' => $p->research_id,
                    'alias' => $p->alias,
                ])->all(),
                'institution' => $check->similarInInstitution,
            ]);
        }

        $patient = DB::transaction(function () use ($request, $user) {
            $patient = new Patient($request->patientAttributes());
            $patient->user_id = $user->id;
            $patient->institution_id = $user->institutionId();
            $patient->save();

            return $patient;
        });

        $this->audit->log('patient.created', $patient, $check->hasWarnings() ? ['duplicate_warning_overridden' => true] : []);

        // Values "at the time of this assessment" prefill the first assessment form.
        $request->session()->put("assessment.prefill.{$patient->public_id}", [
            'nli' => $data->currentNli?->value,
            'ais' => $data->currentAis?->value,
            'ams' => $data->currentAms,
        ]);

        return redirect()->route('patients.assessments.create', $patient)
            ->with('status', __('Patient :id created. Please save the Patient Research ID in your local records and use exactly the same code for all future assessments of this patient.', ['id' => $patient->system_id]));
    }

    public function show(Request $request, Patient $patient): View
    {
        Gate::authorize('view', $patient);

        $assessments = $patient->assessments()->with('result')->orderBy('assessed_on')->orderBy('id')->get();

        return view('patients.show', ['patient' => $patient, 'assessments' => $assessments]);
    }

    public function edit(Patient $patient): View
    {
        Gate::authorize('update', $patient);

        return view('patients.edit', $this->options() + ['patient' => $patient]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        $check = $this->duplicates->check($request->user(), $request->data(), except: $patient);

        if ($check->blocked()) {
            return back()->withInput()->withErrors(['research_id' => $check->researchIdTakenInAccount
                ? __('This Patient Research ID already exists in your list.')
                : __('This Patient Research ID is already used at your institution.')]);
        }

        $patient->fill($request->patientAttributes())->save();
        $this->audit->log('patient.updated', $patient);

        return redirect()->route('patients.show', $patient)->with('status', __('Patient details saved.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'genders' => Gender::cases(),
            'levels' => NeurologicalLevel::cases(),
            'aisGrades' => AisGrade::cases(),
        ];
    }
}
