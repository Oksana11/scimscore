<?php

namespace Tests\Feature;

use App\Mail\SupportReplyMail;
use App\Models\Admin;
use App\Models\SupportMessage;
use App\Models\SupportReply;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportTest extends TestCase
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

    public function test_visitor_sends_message_stored_encrypted_not_emailed(): void
    {
        Mail::fake();

        $this->get('/support')->assertOk()->assertSee('Contact support');
        $this->post('/support', ['email' => 'Doc@Example.com', 'subject' => 'Export', 'message' => 'How do I export data?'])
            ->assertRedirect('/support');

        $msg = SupportMessage::query()->sole();
        $this->assertSame('doc@example.com', $msg->email);
        $this->assertSame('new', $msg->status);
        $raw = DB::table('support_messages')->first();
        $this->assertStringNotContainsString('doc@example.com', $raw->email);
        $this->assertStringNotContainsString('export', $raw->message);
        Mail::assertNothingSent();
    }

    public function test_honeypot_drops_bots(): void
    {
        $this->post('/support', ['email' => 'bot@spam.com', 'subject' => 'x', 'message' => 'buy buy buy buy', 'website' => 'http://spam'])
            ->assertRedirect('/support');
        $this->assertSame(0, SupportMessage::query()->count());
    }

    public function test_admin_replies_by_email_and_status_changes(): void
    {
        Mail::fake();
        $msg = SupportMessage::query()->create(['email' => 'doc@example.com', 'subject' => 'Help', 'message' => 'Question <b>here</b>']);
        $this->admin();

        $this->get('/admin/support')->assertOk()->assertSee('Help');
        $this->get("/admin/support/{$msg->public_id}")->assertOk()->assertSee('Question &lt;b&gt;here&lt;/b&gt;', false);

        $this->post("/admin/support/{$msg->public_id}/reply", ['body' => "Hello\nAnswer"])->assertRedirect();
        Mail::assertSent(SupportReplyMail::class, fn ($m) => $m->hasTo('doc@example.com'));
        $this->assertSame('answered', $msg->fresh()->status);
        $this->assertSame(1, $msg->replies()->count());

        $this->post("/admin/support/{$msg->public_id}/status", ['status' => 'closed'])->assertRedirect();
        $this->assertSame('closed', $msg->fresh()->status);
    }

    public function test_reply_email_renders_escaped(): void
    {
        $msg = SupportMessage::query()->create(['email' => 'doc@example.com', 'subject' => 'Help', 'message' => '<script>x</script>']);
        $reply = $msg->replies()->create(['body' => 'Answer']);

        $html = (new SupportReplyMail($msg, $reply))->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Answer', $html);
    }

    public function test_guests_and_doctors_cannot_read_messages(): void
    {
        $msg = SupportMessage::query()->create(['email' => 'doc@example.com', 'subject' => 'Help', 'message' => 'Secret question']);

        $this->get('/admin/support')->assertRedirect('/admin/login');
        $this->get("/admin/support/{$msg->public_id}")->assertRedirect('/admin/login');
    }

    public function test_admin_deletes_message_with_replies(): void
    {
        $msg = SupportMessage::query()->create(['email' => 'doc@example.com', 'subject' => 'Help', 'message' => 'Question']);
        $msg->replies()->create(['body' => 'Answer']);
        $this->admin();

        $this->delete("/admin/support/{$msg->public_id}")->assertRedirect('/admin/support');
        $this->assertSame(0, SupportMessage::query()->count());
        $this->assertSame(0, SupportReply::query()->count());
    }
}
