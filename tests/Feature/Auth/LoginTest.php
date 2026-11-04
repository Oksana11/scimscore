<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpCodeMail;
use App\Models\OtpRequest;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
    }

    private function requestCode(string $email = 'doc@clinic.org'): string
    {
        $this->post('/login', ['email' => $email, 'accept_terms' => '1'])->assertRedirect('/login/code');

        $code = null;
        Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code, $email) {
            $code = $mail->code;

            return $mail->hasTo(mb_strtolower(trim($email)));
        });

        return $code;
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post('/login', ['email' => 'doc@clinic.org'])->assertSessionHasErrors('accept_terms');
        Mail::assertNothingSent();
    }

    public function test_neither_email_nor_code_is_stored_in_clear_text(): void
    {
        $code = $this->requestCode('doc@clinic.org');

        $row = (array) DB::table('otp_requests')->first();
        $dump = json_encode($row);
        $this->assertStringNotContainsString('clinic.org', $dump);
        $this->assertStringNotContainsString($code, $dump);
    }

    public function test_new_email_creates_account_with_consent_and_goes_to_profile(): void
    {
        $code = $this->requestCode('new@clinic.org');

        $this->post('/login/code', ['code' => $code])->assertRedirect('/profile/complete');

        $user = User::query()->whereBlind('email', 'new@clinic.org')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(UserConsent::query()->where('user_id', $user->id)->where('terms_version', config('scim.terms_version'))->exists());
    }

    public function test_existing_user_with_profile_goes_to_dashboard(): void
    {
        $user = User::factory()->withProfile()->create(['email' => 'doc@clinic.org']);
        $code = $this->requestCode('DOC@clinic.org ');

        $this->post('/login/code', ['code' => $code])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_response_is_the_same_for_registered_and_unknown_emails(): void
    {
        User::factory()->create(['email' => 'known@clinic.org']);

        $a = $this->post('/login', ['email' => 'known@clinic.org', 'accept_terms' => '1']);
        $this->flushSession();
        $b = $this->post('/login', ['email' => 'unknown@clinic.org', 'accept_terms' => '1']);

        $this->assertSame($a->getStatusCode(), $b->getStatusCode());
        $this->assertSame($a->headers->get('Location'), $b->headers->get('Location'));
    }

    public function test_code_is_single_use(): void
    {
        $code = $this->requestCode();
        $this->post('/login/code', ['code' => $code])->assertRedirect();
        $this->post('/logout');

        $this->withSession(['auth.otp_email' => 'doc@clinic.org'])
            ->post('/login/code', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_code_is_burned_after_five_wrong_attempts(): void
    {
        $code = $this->requestCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/code', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        $this->post('/login/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        $code = $this->requestCode();
        $this->travel(11)->minutes();

        $this->post('/login/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_new_code_invalidates_previous(): void
    {
        $first = $this->requestCode();
        OtpRequest::query()->update(['created_at' => now()->subMinutes(2)]);
        $this->travel(61)->seconds();

        Mail::fake();
        $this->post('/login/resend')->assertRedirect('/login/code');

        $this->post('/login/code', ['code' => $first])->assertSessionHasErrors('code');
    }

    public function test_sending_is_rate_limited_per_email(): void
    {
        $this->requestCode();

        $this->from('/login')->post('/login', ['email' => 'doc@clinic.org', 'accept_terms' => '1'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_deactivated_user_cannot_sign_in(): void
    {
        User::factory()->inactive()->create(['email' => 'off@clinic.org']);
        $code = $this->requestCode('off@clinic.org');

        $this->post('/login/code', ['code' => $code])->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $user = User::factory()->withProfile()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->update(['is_active' => false]);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guests_are_redirected_and_profile_is_required(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/profile/complete');
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->withProfile()->create())->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
