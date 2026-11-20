<?php

namespace Tests\Feature\Security;

use App\Mail\SecurityAlertMail;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Support\Security\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecurityMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);
        Admin::query()->create(['email' => 'off@scim.org', 'name' => 'Off', 'is_active' => false]);
    }

    public function test_brute_force_alerts_active_admins_once_per_hour(): void
    {
        $audit = app(AuditLogger::class);
        for ($i = 0; $i < 9; $i++) {
            $audit->log('auth.otp_failed', actorType: 'guest');
        }
        Mail::assertNothingSent();

        $audit->log('auth.otp_failed', actorType: 'guest');
        $audit->log('auth.otp_failed', actorType: 'guest');

        Mail::assertSent(SecurityAlertMail::class, 1);
        Mail::assertSent(SecurityAlertMail::class, fn ($m) => $m->hasTo('boss@scim.org') && ! $m->hasTo('off@scim.org'));
        $this->assertTrue(AuditLog::query()->where('action', 'security.alert_sent')->exists());
    }

    public function test_large_export_alerts(): void
    {
        app(AuditLogger::class)->log('admin.export_assessments', context: ['format' => 'xlsx', 'rows' => 1200], actorType: 'admin', actorId: 1);

        Mail::assertSent(SecurityAlertMail::class, fn ($m) => str_contains($m->title, 'Large data export'));
    }

    public function test_admin_login_from_new_ip_alerts_but_not_first_login(): void
    {
        $audit = app(AuditLogger::class);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1']);
        request()->server->set('REMOTE_ADDR', '10.0.0.1');
        $audit->log('admin.login', actorType: 'admin', actorId: 1);
        Mail::assertNothingSent();

        request()->server->set('REMOTE_ADDR', '10.0.0.1');
        $audit->log('admin.login', actorType: 'admin', actorId: 1);
        Mail::assertNothingSent();

        request()->server->set('REMOTE_ADDR', '203.0.113.7');
        $audit->log('admin.login', actorType: 'admin', actorId: 1);
        Mail::assertSent(SecurityAlertMail::class, fn ($m) => ($m->facts['IP'] ?? null) === '203.0.113.7');
    }

    public function test_alert_email_has_no_personal_data(): void
    {
        $html = (new SecurityAlertMail('Large data export.', ['Who' => 'user#5', 'What' => '900 rows']))->render();

        $this->assertStringContainsString('user#5', $html);
        $this->assertStringContainsString('/admin/audit?category=security', $html);
    }
}
