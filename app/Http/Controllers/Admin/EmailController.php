<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\OtpPurpose;
use App\Enums\ScimVersion;
use App\Mail\AssessmentResultMail;
use App\Mail\EmailChangedMail;
use App\Mail\OtpCodeMail;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Support\Mail\EmailTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Editable texts of system emails + support email / common footer.
 */
class EmailController extends AdminController
{
    public function index(): View
    {
        return view('admin.emails.index', [
            'definitions' => EmailTemplates::definitions(),
            'customized' => EmailTemplate::query()->pluck('updated_at', 'key'),
            'supportEmail' => Setting::supportEmail(),
            'footer' => Setting::get(EmailTemplates::FOOTER_KEY, EmailTemplates::DEFAULT_FOOTER),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'support_email' => ['required', 'email:rfc', 'max:254'],
            'email_footer' => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::put('support_email', mb_strtolower(trim($data['support_email'])));
        Setting::put(EmailTemplates::FOOTER_KEY, $data['email_footer'] ?? '');
        $this->log('admin.email_settings_updated');

        return back()->with('status', 'Email settings saved.');
    }

    public function edit(string $key): View
    {
        abort_unless(EmailTemplates::exists($key), 404);

        return view('admin.emails.edit', [
            'key' => $key,
            'definition' => EmailTemplates::definitions()[$key],
            'current' => EmailTemplates::current($key),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'heading' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        EmailTemplate::query()->updateOrCreate(['key' => $key], $data + ['updated_by_admin_id' => $this->admin()->id]);
        $this->log('admin.email_template_updated', null, ['template' => $key]);

        return redirect()->route('admin.emails.edit', $key)->with('status', 'Email saved.');
    }

    public function reset(string $key): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);

        EmailTemplate::query()->where('key', $key)->delete();
        $this->log('admin.email_template_reset', null, ['template' => $key]);

        return redirect()->route('admin.emails.edit', $key)->with('status', 'Default text restored.');
    }

    public function preview(Request $request, string $key): Response
    {
        abort_unless(EmailTemplates::exists($key), 404);
        $request->attributes->set('csp', 'email-preview');

        return response($this->sample($key)->render());
    }

    /**
     * Sends a sample (fake code / fake result) to the signed-in administrator only.
     */
    public function test(string $key): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($key), 404);

        Mail::to($this->admin()->email)->send($this->sample($key));
        $this->log('admin.email_template_test', null, ['template' => $key]);

        return back()->with('status', 'Test email sent to your address.');
    }

    private function sample(string $key): Mailable
    {
        foreach (OtpPurpose::cases() as $purpose) {
            if (EmailTemplates::keyForOtp($purpose) === $key) {
                return new OtpCodeMail('123456', $purpose);
            }
        }

        return match ($key) {
            'email_changed' => new EmailChangedMail,
            'assessment_result' => new AssessmentResultMail($this->sampleAssessment()),
        };
    }

    /**
     * In-memory example (never saved): no real patient data in previews.
     */
    private function sampleAssessment(): Assessment
    {
        $a = new Assessment;
        $a->forceFill([
            'scim_version' => ScimVersion::III,
            'assessed_on' => now()->toDateString(),
            'gender' => Gender::cases()[0],
            'age' => 40,
            'nli' => NeurologicalLevel::cases()[0],
            'ais' => AisGrade::cases()[0],
            'ams' => 50,
            'answers' => ['q1' => 1],
            'patient_code' => 'EXAMPLE-01',
        ]);
        $a->setRelation('patient', null);
        $a->setRelation('result', (new AssessmentResult)->forceFill([
            'total' => 48, 'self_care' => 10, 'respiration_sphincter' => 20, 'mobility' => 18,
            'sciarmi_total' => 61.5, 'grade' => 'C',
        ]));

        return $a;
    }
}
