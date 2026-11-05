<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AisGrade;
use App\Enums\AssessmentSource;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Models\Assessment;
use App\Models\Country;
use App\Models\Patient;
use App\Services\Assessments\AssessmentFilter;
use App\Services\Export\AssessmentExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only view of patients and assessments for administrators.
 * No Research ID or alias is ever shown or exported here; there are no edit routes.
 */
class DataController extends AdminController
{
    private const MAX_EXPORT_ROWS = 100_000;

    public function patients(Request $request): View
    {
        $f = $request->validate([
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_year' => ['nullable', 'integer', 'between:1900,2100'],
            'system_id' => ['nullable', 'string', 'max:32'],
        ]);

        $patients = Patient::query()
            ->select(['id', 'public_id', 'system_id', 'user_id', 'institution_id', 'gender', 'birth_year', 'first_nli', 'first_ais', 'first_ams', 'created_at'])
            ->with('institution:id,name')
            ->withCount('assessments')
            ->when($f['gender'] ?? null, fn ($q, $v) => $q->where('gender', $v))
            ->when($f['birth_year'] ?? null, fn ($q, $v) => $q->where('birth_year', $v))
            ->when($f['system_id'] ?? null, fn ($q, $v) => $q->where('system_id', $v))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $this->log('admin.view_patients');

        return view('admin.patients', ['patients' => $patients, 'filters' => $f, 'genders' => Gender::cases()]);
    }

    public function assessments(Request $request): View
    {
        $f = $this->filters($request);

        $assessments = $this->query($f)
            ->with(['result', 'patient:id,system_id,birth_year,institution_id', 'patient.institution:id,name'])
            ->latest('assessed_on')->latest('id')
            ->paginate(50)
            ->withQueryString();

        $this->log('admin.view_assessments');

        return view('admin.assessments', [
            'assessments' => $assessments,
            'filters' => $f,
            'genders' => Gender::cases(),
            'levels' => NeurologicalLevel::cases(),
            'aisGrades' => AisGrade::cases(),
            'versions' => ScimVersion::cases(),
            'grades' => AssessmentFilter::GRADES,
            'countries' => Country::query()->orderBy('name')->get(['code', 'name']),
        ]);
    }

    public function export(Request $request, AssessmentExporter $exporter, string $format): StreamedResponse|RedirectResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);
        $f = $this->filters($request);

        $count = $this->query($f)->count();
        if ($count > self::MAX_EXPORT_ROWS) {
            return back()->withErrors(['export' => "Too many rows ({$count}). Please narrow the filters."]);
        }

        $this->log('admin.export_assessments', context: ['format' => $format, 'rows' => $count]);

        $rows = $this->query($f)
            ->with(['result', 'patient.institution', 'user.profile.institution', 'user.profile.profession', 'caregiverInstitution', 'caregiverProfession'])
            ->orderBy('id')
            ->lazy(500);
        $name = 'scim-assessments-all-'.now()->format('Ymd-His').'.'.$format;

        return $format === 'csv'
            ? $exporter->csv($rows, $name, AssessmentExporter::MODE_ADMIN)
            : $exporter->xlsx($rows, $name, AssessmentExporter::MODE_ADMIN);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate(AssessmentFilter::rules() + [
            'source' => ['nullable', Rule::enum(AssessmentSource::class)],
            'country' => ['nullable', 'string', 'size:2', Rule::exists('countries', 'code')],
            'system_id' => ['nullable', 'string', 'max:32'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $f
     * @return Builder<Assessment>
     */
    private function query(array $f): Builder
    {
        return AssessmentFilter::apply(Assessment::query(), $f)
            ->when($f['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($f['country'] ?? null, fn ($q, $v) => $q->whereHas('user.profile', fn ($p) => $p->where('country_code', $v)))
            ->when($f['system_id'] ?? null, fn ($q, $v) => $q->whereHas('patient', fn ($p) => $p->where('system_id', $v)));
    }
}
