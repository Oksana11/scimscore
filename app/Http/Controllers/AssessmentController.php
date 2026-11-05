<?php

namespace App\Http\Controllers;

use App\Mail\AssessmentResultMail;
use App\Models\Assessment;
use App\Models\Patient;
use App\Scoring\ScimDefinition;
use App\Support\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Assessment::class);

        $assessments = Assessment::query()
            ->ownedBy($request->user())
            ->with(['result', 'patient:id,public_id,system_id,research_id'])
            ->latest('assessed_on')
            ->latest('id')
            ->paginate(25);

        return view('assessments.index', ['assessments' => $assessments]);
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
}
