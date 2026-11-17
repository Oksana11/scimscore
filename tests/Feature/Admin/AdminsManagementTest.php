<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function signIn(): Admin
    {
        $admin = Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_admin_adds_another_admin(): void
    {
        $this->signIn();

        $this->get('/admin/admins')->assertOk()->assertSee('boss@scim.org');
        $this->post('/admin/admins', ['email' => 'New@Scim.org', 'name' => 'New'])->assertRedirect();

        $new = Admin::query()->whereBlind('email', 'new@scim.org')->first();
        $this->assertNotNull($new);
        $this->assertFalse($new->hasMfa());
        $this->assertTrue(AuditLog::query()->where('action', 'admin.created')->exists());

        $this->post('/admin/admins', ['email' => 'new@scim.org', 'name' => 'Dup'])->assertSessionHasErrors('email');
    }

    public function test_deactivate_and_reset_mfa_of_others_but_not_self(): void
    {
        $me = $this->signIn();
        $other = Admin::query()->create(['email' => 'other@scim.org', 'name' => 'Other']);
        $other->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();

        $this->post("/admin/admins/{$other->id}/reset-mfa")->assertRedirect();
        $this->assertFalse($other->fresh()->hasMfa());

        $this->post("/admin/admins/{$other->id}/toggle")->assertRedirect();
        $this->assertFalse($other->fresh()->is_active);

        $this->post("/admin/admins/{$me->id}/toggle")->assertForbidden();
        $this->post("/admin/admins/{$me->id}/reset-mfa")->assertForbidden();
        $this->assertTrue($me->fresh()->is_active);
    }

    public function test_guests_cannot_add_admins(): void
    {
        $this->post('/admin/admins', ['email' => 'x@scim.org', 'name' => 'X'])->assertRedirect('/admin/login');
        $this->assertSame(0, Admin::query()->count());
    }
}
