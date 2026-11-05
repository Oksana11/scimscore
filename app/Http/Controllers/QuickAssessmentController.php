<?php

namespace App\Http\Controllers;

use App\Enums\AisGrade;
use App\Enums\AssessmentSource;
use App\Enums\Etiology;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\OtpPurpose;
use App\Enums\ScimVersion;
use App\Http\Requests\CaregiverRequest;
use App\Http\Requests\QuickAssessmentRequest;
use App\Mail\AssessmentResultMail;
use App\Models\Assessment;
use App\Models\Country;
use App\Models\Profession;
use App\Scoring\InvalidScimAnswers;
use App\Scoring\ScimDefinition;
use App\Services\Assessments\AssessmentRecorder;
use App\Services\Auth\OtpService;
use App\Services\Export\AssessmentExporter;
use App\Services\Institutions\InstitutionResolver;
use App\Support\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Quick Assessment: one-time calculation without an account, in three steps:
 *   1) caregiver details (profession, country, institution — no name, no email);
 *   2) patient data and SCIM items; 3) result.
 * The result is visible only in the browser session that created it
 * (no guessable links); it can be emailed after OTP verification.
 */
class QuickAssessmentController extends Controller
{
    private const SESSION_KEY = 'quick.results';

    private const SESSION_LIMIT = 20;

    private const CAREGIVER = 'quick.caregiver';

    public function __construct(
        private readonly AssessmentRecorder $recorder,
        private readonly OtpService $otp,
        private readonly AuditLogger $audit,
        private readonly AssessmentExporter $exporter,
    ) {}

    /** Step 1: the assessing professional (prefilled from the profile when signed in). */
    public function create(Request $request): View
    {
        $saved = $request->session()->get(self::CAREGIVER);
        $profile = $request->user()?->profile;

        $values = $saved ?? ($profile ? [
            'profession_id' => $profile->profession_id,
            'country_code' => $profile->country_code,
            'institution' => 'local:'.$profile->institution_id,
            'institution_display' => $profile->institution?->name,
            'professional_title' => $profile->professional_title,
            'organization' => $profile->organization,
            'department' => $profile->department,
        ] : []);

        return view('quick.caregiver', [
            'values' => $values,
            'professions' => Profession::query()->active()->get(['id', 'name']),
            'countries' => Country::query()->active()->orderBy('name')->get(['code', 'name']),
        ]);
    }

    public function saveCaregiver(CaregiverRequest $request, InstitutionResolver $institutions): RedirectResponse
    {
        $d = $request->validated();
        $institution = $institutions->resolve($d['institution'], $d['institution_custom_name'] ?? null, $d['country_code']);

        if (! $institution) {
            return back()->withInput()->withErrors(['institution' => __('Please choose an institution from the list or add a new one.')]);
        }

        $request->session()->put(self::CAREGIVER, [
            'profession_id' => (int) $d['profession_id'],
            'country_code' => $d['country_code'],
            'institution_id' => $institution->id,
            'institution' => 'local:'.$institution->id,
            'institution_display' => $institution->name,
            'professional_title' => $d['professional_title'] ?? null,
            'organization' => $d['organization'] ?? null,
            'department' => $d['department'] ?? null,
        ]);

        return redirect()->route('quick.assessment', $request->only('version'));
    }

    /** Step 2: patient data + SCIM items. */
    public function assessment(Request $request): View|RedirectResponse
    {
        $caregiver = $request->session()->get(self::CAREGIVER);
        if (! $caregiver) {
            return redirect()->route('quick.create');
        }

        $version = ScimVersion::tryFrom((string) $request->query('version', 'III')) ?? ScimVersion::III;
        if (! ScimDefinition::isAvailable($version)) {
            $version = ScimVersion::III;
        }

        return view('quick.create', [
            'version' => $version,
            'versions' => array_filter(ScimVersion::cases(), fn ($v) => ScimDefinition::isAvailable($v)),
            'definition' => ScimDefinition::for($version),
            'genders' => Gender::cases(),
            'levels' => NeurologicalLevel::cases(),
            'aisGrades' => AisGrade::cases(),
            'etiologies' => Etiology::cases(),
            'caregiver' => [
                'profession' => Profession::query()->find($caregiver['profession_id'])?->name,
                'country' => Country::query()->find($caregiver['country_code'])?->name,
                'institution' => $caregiver['institution_display'],
            ],
        ]);
    }

    public function store(QuickAssessmentRequest $request): RedirectResponse
    {
        $caregiver = $request->session()->get(self::CAREGIVER);
        if (! $caregiver) {
            return redirect()->route('quick.create');
        }

        try {
            $assessment = $this->recorder->recordQuick(
                $request->version(),
                $request->answers(),
                $request->snapshot(),
                $request->assessedOn(),
                $request->details($caregiver),
            );
        } catch (InvalidScimAnswers $e) {
            return back()->withInput()->withErrors(
                collect($e->errors)->mapWithKeys(fn ($msg, $key) => ["answers.{$key}" => $msg])->all()
            );
        }

        $ids = array_slice(array_merge($request->session()->get(self::SESSION_KEY, []), [$assessment->public_id]), -self::SESSION_LIMIT);
        $request->session()->put(self::SESSION_KEY, $ids);

        return redirect()->route('quick.result', $assessment);
    }

    public function result(Request $request, Assessment $assessment): View
    {
        $this->authorizeSession($request, $assessment);

        $definition = ScimDefinition::for($assessment->scim_version);

        return view('quick.result', [
            'assessment' => $assessment->load(['result', 'consent', 'caregiverProfession', 'caregiverCountry', 'caregiverInstitution']),
            'definition' => $definition,
            'emailStep' => $request->session()->get("quick.email.{$assessment->public_id}") ? 'code' : 'email',
            'emailTo' => $request->session()->get("quick.email.{$assessment->public_id}"),
        ]);
    }

    public function sendCode(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeSession($request, $assessment);

        $email = mb_strtolower(trim((string) $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
        ])['email']));

        $this->otp->send(OtpPurpose::QuickResultEmail, $email);
        $request->session()->put("quick.email.{$assessment->public_id}", $email);

        return redirect()->route('quick.result', $assessment)->with('status', __('We sent a code to :email.', ['email' => $email]));
    }

    public function sendResult(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeSession($request, $assessment);

        $email = $request->session()->get("quick.email.{$assessment->public_id}");
        $code = (string) $request->validate(['code' => ['required', 'string', 'max:20']])['code'];

        if (! $email || ! $this->otp->verify(OtpPurpose::QuickResultEmail, $email, $code)) {
            return back()->withErrors(['code' => __('The code is invalid or has expired.')]);
        }

        Mail::to($email)->send(new AssessmentResultMail($assessment->load('result')));
        $request->session()->forget("quick.email.{$assessment->public_id}");
        $this->audit->log('assessment.quick_emailed', $assessment, anonymous: true);

        return redirect()->route('quick.result', $assessment)->with('status', __('The result has been sent to :email.', ['email' => $email]));
    }

    public function pdf(Request $request, Assessment $assessment): Response
    {
        $this->authorizeSession($request, $assessment);

        return $this->exporter->pdf($assessment->load('result'), 'scim-quick-'.$assessment->assessed_on->format('Ymd').'.pdf');
    }

    public function xlsx(Request $request, Assessment $assessment): StreamedResponse
    {
        $this->authorizeSession($request, $assessment);

        return $this->exporter->xlsx([$assessment->load('result')], 'scim-quick-'.$assessment->assessed_on->format('Ymd').'.xlsx', mode: AssessmentExporter::MODE_QUICK);
    }

    public function changeEmail(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeSession($request, $assessment);
        $request->session()->forget("quick.email.{$assessment->public_id}");

        return redirect()->route('quick.result', $assessment);
    }

    /**
     * Only the session that created a quick assessment may see it; otherwise 404 (not 403),
     * so the existence of an id is not revealed.
     */
    private function authorizeSession(Request $request, Assessment $assessment): void
    {
        abort_unless(
            $assessment->source === AssessmentSource::Quick
            && in_array($assessment->public_id, $request->session()->get(self::SESSION_KEY, []), true),
            404
        );
    }
}
