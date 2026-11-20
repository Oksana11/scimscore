<?php

namespace Tests\Feature\Security;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\SupportMessage;
use App\Models\User;
use App\Support\Security\AuditLogger;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataErasureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function patientWithAssessment(User $user): array
    {
        $p = Patient::factory()->for($user)->create(['research_id' => 'R-77', 'alias' => 'Bob']);
        $a = Assessment::factory()->forPatient($p)->create();
        $a->result()->create(['total' => 40, 'self_care' => 10, 'respiration_sphincter' => 20, 'mobility' => 10, 'grade' => 'D']);

        return [$p, $a];
    }

    public function test_user_deletes_account_research_data_stays_anonymous(): void
    {
        $user = User::factory()->withProfile()->create(['email' => 'doc@clinic.org']);
        [$p, $a] = $this->patientWithAssessment($user);
        SupportMessage::query()->create(['user_id' => $user->id, 'email' => 'doc@clinic.org', 'subject' => 's', 'message' => 'hello there']);

        $this->actingAs($user)->delete('/profile', ['confirm' => 'nope'])->assertSessionHasErrors('confirm');
        $this->actingAs($user)->delete('/profile', ['confirm' => 'DELETE'])->assertRedirect('/');
        $this->assertGuest();

        $user->refresh();
        $this->assertNotNull($user->anonymized_at);
        $this->assertFalse($user->is_active);
        $this->assertStringNotContainsString('doc@clinic.org', $user->email);
        $this->assertNull($user->profile()->first());
        $this->assertSame(0, SupportMessage::query()->count());
        $this->assertNull(User::query()->whereBlind('email', 'doc@clinic.org')->first());

        $p->refresh();
        $this->assertNull($p->research_id);
        $this->assertNull($p->alias);
        $this->assertNotNull(Assessment::query()->find($a->id), 'assessments stay as research data');
        $this->assertTrue(AuditLog::query()->where('action', 'privacy.account_erased')->exists());
    }

    public function test_owner_deletes_patient_with_assessments_but_not_someone_elses(): void
    {
        $user = User::factory()->withProfile()->create();
        $other = User::factory()->withProfile()->create();
        [$p, $a] = $this->patientWithAssessment($user);

        $this->actingAs($other)->delete("/patients/{$p->public_id}", ['confirm' => 'DELETE'])->assertNotFound();
        $this->assertNotNull(Patient::query()->find($p->id));

        $this->actingAs($user)->delete("/patients/{$p->public_id}", ['confirm' => 'DELETE'])->assertRedirect('/patients');
        $this->assertNull(Patient::query()->find($p->id));
        $this->assertNull(Assessment::query()->find($a->id));
        $this->assertSame(0, DB::table('assessment_results')->where('assessment_id', $a->id)->count());
    }

    public function test_admin_erases_account_on_request(): void
    {
        $user = User::factory()->withProfile()->create(['email' => 'doc2@clinic.org']);
        $admin = Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();
        $this->actingAs($admin, 'admin');

        $this->delete("/admin/users/{$user->public_id}", ['confirm' => 'DELETE'])->assertRedirect();
        $this->assertNotNull($user->fresh()->anonymized_at);
        $this->post("/admin/users/{$user->public_id}/toggle")->assertNotFound();
        $this->get('/admin/users')->assertSee('Deleted user')->assertDontSee('doc2@clinic.org');
    }

    public function test_prune_removes_expired_data_and_audit_chain_still_verifies(): void
    {
        $audit = app(AuditLogger::class);
        $this->travelTo(now()->subYears(3));
        $audit->log('old.one');
        $audit->log('old.two');
        SupportMessage::query()->create(['email' => 'x@y.org', 'subject' => 's', 'message' => 'old message']);
        DB::table('otp_requests')->insert(['purpose' => 'login', 'email_bidx' => str_repeat('a', 64), 'code_hash' => str_repeat('b', 64), 'expires_at' => now(), 'created_at' => now()]);
        $this->travelBack();
        $audit->log('new.one');
        $audit->log('new.two');

        $this->assertSame(0, Artisan::call('data:prune'));
        $this->assertSame(0, SupportMessage::query()->count());
        $this->assertSame(0, DB::table('otp_requests')->count());
        $this->assertFalse(AuditLog::query()->where('action', 'like', 'old.%')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'new.one')->exists());

        $this->assertSame(0, Artisan::call('audit:verify'), Artisan::output());

        // Deleting a record after the anchor is still detected.
        AuditLog::query()->where('action', 'new.one')->delete();
        $this->assertSame(1, Artisan::call('audit:verify'));
    }
}
