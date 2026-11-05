<?php

namespace Tests\Feature\Admin;

use App\Mail\OtpCodeMail;
use App\Models\Admin;
use App\Models\Assessment;
use App\Models\Institution;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
    }

    private function emailCode(string $email): string
    {
        $code = null;
        Mail::assertSent(OtpCodeMail::class, function ($m) use (&$code, $email) {
            $code = $m->code;

            return $m->hasTo($email);
        });

        return $code;
    }

    private function signedInAdmin(): Admin
    {
        $admin = Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_full_sign_in_with_email_code_and_authenticator_enrolment(): void
    {
        $this->artisan('admin:create', ['email' => 'boss@scim.org', 'name' => 'Boss'])->assertSuccessful();

        $this->post('/admin/login', ['email' => 'boss@scim.org'])->assertRedirect('/admin/login/code');
        $this->post('/admin/login/code', ['code' => $this->emailCode('boss@scim.org')])->assertRedirect('/admin/login/mfa');

        $admin = Admin::query()->sole();
        $this->assertNotNull($admin->totp_secret);
        $this->assertFalse($admin->hasMfa());
        $this->get('/admin/login/mfa')->assertOk()->assertSee($admin->totp_secret);
        $this->assertGuest('admin');

        $this->post('/admin/login/mfa', ['code' => Totp::code($admin->totp_secret)])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin->fresh(), 'admin');
        $this->assertTrue($admin->fresh()->hasMfa());
        $this->get('/admin')->assertOk()->assertSee('Statistics');
    }

    public function test_wrong_authenticator_code_does_not_sign_in_and_secret_is_hidden_after_enrolment(): void
    {
        $admin = Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();

        $this->post('/admin/login', ['email' => 'boss@scim.org']);
        $this->post('/admin/login/code', ['code' => $this->emailCode('boss@scim.org')]);

        $this->get('/admin/login/mfa')->assertOk()->assertDontSee($admin->totp_secret);
        $this->post('/admin/login/mfa', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest('admin');
    }

    public function test_mfa_step_requires_email_step(): void
    {
        $this->get('/admin/login/mfa')->assertRedirect('/admin/login');
        $this->post('/admin/login/mfa', ['code' => '123456'])->assertRedirect('/admin/login');
    }

    public function test_unknown_email_gets_same_response_and_no_mail(): void
    {
        $this->post('/admin/login', ['email' => 'nobody@scim.org'])->assertRedirect('/admin/login/code');
        Mail::assertNothingSent();
    }

    public function test_doctors_cannot_open_admin_and_admins_are_not_doctors(): void
    {
        $doctor = User::factory()->withProfile()->create();
        $this->actingAs($doctor)->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/users')->assertRedirect('/admin/login');

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->signedInAdmin();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_deactivate_and_activate_user(): void
    {
        $this->signedInAdmin();
        $user = User::factory()->withProfile()->create();

        $this->post("/admin/users/{$user->public_id}/toggle")->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.user_deactivated', 'actor_type' => 'admin']);

        $this->post("/admin/users/{$user->public_id}/toggle");
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_pages_render_and_never_show_research_ids_or_aliases(): void
    {
        $this->signedInAdmin();
        $p = Patient::factory()->create(['research_id' => 'SECRET-RID', 'alias' => 'Secret Alias']);
        $a = Assessment::factory()->forPatient($p)->create();
        $a->result()->create(['total' => 33, 'self_care' => 10, 'respiration_sphincter' => 13, 'mobility' => 10, 'grade' => 'D']);

        foreach (['/admin', '/admin/users', '/admin/patients', '/admin/assessments', '/admin/settings', '/admin/audit'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('SECRET-RID')->assertDontSee('Secret Alias');
        }

        $this->get('/admin/patients')->assertSee($p->system_id);
        $this->get('/admin/assessments')->assertSee($p->system_id);
    }

    public function test_admin_exports_are_anonymous(): void
    {
        $this->signedInAdmin();
        $p = Patient::factory()->create(['research_id' => 'SECRET-RID', 'alias' => 'Secret Alias']);
        $a = Assessment::factory()->forPatient($p)->create();
        $a->result()->create(['total' => 33, 'self_care' => 10, 'respiration_sphincter' => 13, 'mobility' => 10]);

        $csv = $this->get('/admin/assessments/export/csv')->assertOk()->streamedContent();
        $this->assertStringContainsString($p->system_id, $csv);
        $this->assertStringNotContainsString('SECRET-RID', $csv);
        $this->assertStringNotContainsString('Secret Alias', $csv);

        $this->get('/admin/assessments/export/xlsx')->assertOk();
        foreach (['csv', 'xlsx', 'pdf'] as $fmt) {
            $this->get("/admin/statistics/export/{$fmt}")->assertOk();
        }
    }

    public function test_csv_neutralises_formulas(): void
    {
        $this->signedInAdmin();
        $inst = Institution::factory()->create(['name' => '=cmd|calc']);
        $user = User::factory()->withProfile($inst->id)->create();
        $a = Assessment::factory()->forPatient(Patient::factory()->for($user)->create())->create();
        $a->result()->create(['total' => 1, 'self_care' => 1, 'respiration_sphincter' => 0, 'mobility' => 0]);

        $csv = $this->get('/admin/assessments/export/csv')->streamedContent();
        $this->assertStringContainsString("'=cmd|calc", $csv);
    }

    public function test_there_are_no_admin_routes_to_edit_patients_or_assessments(): void
    {
        $this->signedInAdmin();
        $p = Patient::factory()->create();

        $this->put("/admin/patients/{$p->public_id}", [])->assertNotFound();
        $this->delete("/admin/patients/{$p->public_id}")->assertNotFound();
        $this->put('/admin/assessments/1', [])->assertNotFound();
    }

    public function test_merge_custom_institution_moves_profiles_and_patients(): void
    {
        $this->signedInAdmin();
        $custom = Institution::query()->create(['name' => 'Typo Hospital', 'is_custom' => true]);
        $official = Institution::factory()->create();
        $user = User::factory()->withProfile($custom->id)->create();
        $patient = Patient::factory()->for($user)->create();

        $this->post("/admin/settings/institutions/{$custom->id}/merge", ['target_id' => $official->id])->assertRedirect();

        $this->assertSame($official->id, UserProfile::query()->where('user_id', $user->id)->value('institution_id'));
        $this->assertSame($official->id, $patient->fresh()->institution_id);
        $this->assertSame($official->id, $custom->fresh()->merged_into_id);
    }

    public function test_idle_admin_session_expires(): void
    {
        $this->signedInAdmin();
        $this->get('/admin')->assertOk();

        $this->travel(31)->minutes();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    public function test_audit_integrity_check_from_admin(): void
    {
        $this->signedInAdmin();
        $this->from('/admin/audit')->post('/admin/audit/verify')->assertRedirect('/admin/audit')->assertSessionHas('status');
    }
}
