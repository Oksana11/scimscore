<?php

namespace App\Http\Controllers;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Mail\AssessmentResultMail;
use App\Models\Assessment;
use App\Models\Patient;
use App\Scoring\ScimDefinition;
use App\Services\Assessments\AssessmentFilter;
use App\Services\Export\AssessmentExporter;
use App\Support\Security\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentController extends Controller
{
    /** Upper limits per export, to prevent bulk extraction and keep files usable. */
    private const MAX_XLSX_ROWS = 10_000;

    private const MAX_PDF_ROWS = 1_000;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AssessmentExporter $exporter,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Assessment::class);

        $filters = $request->validate(AssessmentFilter::rules());

        $assessments = $this->filtered($request, $filters)
            ->with(['result', 'patient:id,public_id,system_id,research_id,birth_year'])
            ->latest('assessed_on')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('assessments.index', [
            'assessments' => $assessments,
            'filters' => $filters,
            'filtered' => AssessmentFilter::isActive($filters),
            'genders' => Gender::cases(),
            'levels' => NeurologicalLevel::cases(),
            'aisGrades' => AisGrade::cases(),
            'versions' => ScimVersion::cases(),
            'grades' => AssessmentFilter::GRADES,
        ]);
    }

    public function exportXlsx(Request $request): StreamedResponse|RedirectResponse
    {
        Gate::authorize('viewAny', Assessment::class);
        $filters = $request->validate(AssessmentFilter::rules());

        $count = $this->filtered($request, $filters)->count();
        if ($count > self::MAX_XLSX_ROWS) {
            return back()->withErrors(['export' => __('Too many assessments (:n). Please narrow the filters (max :max).', ['n' => $count, 'max' => self::MAX_XLSX_ROWS])]);
        }

        $this->audit->log('export.xlsx', context: ['rows' => $count, 'filters' => array_keys(array_filter($filters, fn ($v) => $v !== null && $v !== ''))]);

        $rows = $this->filtered($request, $filters)
            ->with(['result', 'patient:id,system_id,research_id,birth_year'])
            ->orderBy('assessed_on')->orderBy('id')
            ->lazy(500);

        return $this->exporter->xlsx($rows, 'scim-assessments-'.now()->format('Ymd-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Assessment::class);
        $filters = $request->validate(AssessmentFilter::rules());

        $count = $this->filtered($request, $filters)->count();
        if ($count > self::MAX_PDF_ROWS) {
            return back()->withErrors(['export' => __('Too many assessments for PDF (:n). Please narrow the filters (max :max) or export to Excel.', ['n' => $count, 'max' => self::MAX_PDF_ROWS])]);
        }

        $this->audit->log('export.pdf', context: ['rows' => $count, 'filters' => array_keys(array_filter($filters, fn ($v) => $v !== null && $v !== ''))]);

        $rows = $this->filtered($request, $filters)
            ->with(['result', 'patient:id,system_id,research_id,birth_year'])
            ->orderBy('assessed_on')->orderBy('id')
            ->get();

        return $this->exporter->pdfList($rows, $filters, 'scim-assessments-'.now()->format('Ymd-His').'.pdf');
    }

    public function pdf(Assessment $assessment): Response
    {
        Gate::authorize('view', $assessment);
        $this->audit->log('export.pdf', $assessment);

        return $this->exporter->pdf($assessment->load(['result', 'patient']), $this->filename($assessment, 'pdf'));
    }

    public function xlsx(Assessment $assessment): StreamedResponse
    {
        Gate::authorize('view', $assessment);
        $this->audit->log('export.xlsx', $assessment);

        return $this->exporter->xlsx([$assessment->load(['result', 'patient'])], $this->filename($assessment, 'xlsx'));
    }

    public function show(Assessment $assessment): View
    {
        Gate::authorize('view', $assessment);

        $assessment->load(['result', 'patient']);

        return view('assessments.show', [
            'assessment' => $assessment,
            'definition' => ScimDefinition::for($assessment->scim_version),
        ]);
    }

    /**
     * Sends the result to the account's own (already verified) email address.
     */
    public function email(Request $request, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('view', $assessment);

        Mail::to($request->user()->email)->send(new AssessmentResultMail($assessment->load(['result', 'patient'])));
        $this->audit->log('assessment.emailed', $assessment);

        return back()->with('status', __('The result has been sent to your email.'));
    }

    public function compare(Request $request, Patient $patient): View|RedirectResponse
    {
        Gate::authorize('view', $patient);

        $ids = $request->validate([
            'a' => ['required', 'string', 'size:26'],
            'b' => ['required', 'string', 'size:26', 'different:a'],
        ]);

        $pair = $patient->assessments()->with('result')->whereIn('public_id', [$ids['a'], $ids['b']])->orderBy('assessed_on')->orderBy('id')->get();
        abort_unless($pair->count() === 2, 404);

        return view('assessments.compare', [
            'patient' => $patient,
            'a' => $pair[0],
            'b' => $pair[1],
            'definitions' => [
                $pair[0]->scim_version->value => ScimDefinition::for($pair[0]->scim_version),
                $pair[1]->scim_version->value => ScimDefinition::for($pair[1]->scim_version),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Assessment>
     */
    private function filtered(Request $request, array $filters): Builder
    {
        return AssessmentFilter::apply(Assessment::query()->ownedBy($request->user()), $filters);
    }

    private function filename(Assessment $a, string $ext): string
    {
        return 'scim-'.($a->patient?->system_id ?? 'quick').'-'.$a->assessed_on->format('Ymd').'.'.$ext;
    }
}
