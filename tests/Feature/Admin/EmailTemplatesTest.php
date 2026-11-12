<?php

namespace Tests\Feature\Admin;

use App\Enums\OtpPurpose;
use App\Mail\EmailChangedMail;
use App\Mail\OtpCodeMail;
use App\Models\Admin;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_defaults_include_support_email(): void
    {
        Setting::put('support_email', 'help@scimscore.com');

        $html = (new EmailChangedMail)->render();

        $this->assertStringContainsString('mailto:help@scimscore.com', $html);
    }

    public function test_admin_edits_template_and_text_is_escaped(): void
    {
        $this->admin();

        $this->get('/admin/emails')->assertOk()->assertSee('Sign-in email changed');
        $this->put('/admin/emails/email_changed', [
            'subject' => 'Changed',
            'heading' => 'Hello',
            'body' => "<script>alert(1)</script>\nWrite to {support_email}",
        ])->assertRedirect('/admin/emails/email_changed');

        $html = (new EmailChangedMail)->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('mailto:', $html);
        $this->assertSame('Changed', (new EmailChangedMail)->envelope()->subject);

        $this->post('/admin/emails/email_changed/reset')->assertRedirect();
        $this->assertSame(0, EmailTemplate::query()->count());
    }

    public function test_code_is_always_in_body_and_never_in_subject(): void
    {
        EmailTemplate::query()->create(['key' => 'login_code', 'subject' => 'Code 123456', 'heading' => 'H', 'body' => 'No code here']);

        $mail = new OtpCodeMail('123456', OtpPurpose::Login);

        $this->assertStringNotContainsString('123456', $mail->envelope()->subject);
        $this->assertStringContainsString('123456', $mail->render());
    }

    public function test_preview_uses_locked_down_csp_and_test_goes_to_admin_only(): void
    {
        $this->admin();
        Mail::fake();

        $res = $this->get('/admin/emails/assessment_result/preview')->assertOk();
        $this->assertStringContainsString("default-src 'none'", (string) $res->headers->get('Content-Security-Policy'));
        $this->assertSame('SAMEORIGIN', $res->headers->get('X-Frame-Options'));

        $this->post('/admin/emails/login_code/test')->assertRedirect();
        Mail::assertSent(OtpCodeMail::class, fn ($m) => $m->hasTo('boss@scim.org'));

        $this->get('/admin/emails/unknown')->assertNotFound();
    }

    public function test_guests_cannot_edit(): void
    {
        $this->get('/admin/emails')->assertRedirect('/admin/login');
        $this->put('/admin/emails/login_code', ['subject' => 'x', 'heading' => 'x', 'body' => 'x'])->assertRedirect('/admin/login');
    }
}
