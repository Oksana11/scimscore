<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\Auth\OtpService;
use App\Support\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Passwordless sign-in / registration: email → one-time code → signed in.
 * The response never reveals whether the email is registered.
 */
class LoginController extends Controller
{
    public function __construct(private readonly OtpService $otp, private readonly AuditLogger $audit) {}

    public function showEmail(): View
    {
        return view('auth.login');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'accept_terms' => ['accepted'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        $this->otp->send(OtpPurpose::Login, $email);

        $request->session()->put('auth.otp_email', $email);
        $request->session()->put('auth.otp_sent_at', now()->timestamp);
        $request->session()->put('auth.terms_accepted_at', now()->toIso8601String());

        $this->audit->log('auth.otp_sent');

        return redirect()->route('login.code');
    }

    public function showCode(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('auth.otp_email')) {
            return redirect()->route('login');
        }

        $sentAt = (int) $request->session()->get('auth.otp_sent_at', 0);

        return view('auth.code', [
            'email' => $request->session()->get('auth.otp_email'),
            'resendIn' => max(0, $sentAt + (int) config('scim.otp.resend_seconds') - now()->timestamp),
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('auth.otp_email');

        if (! $email) {
            return redirect()->route('login');
        }

        $this->otp->send(OtpPurpose::Login, $email);
        $request->session()->put('auth.otp_sent_at', now()->timestamp);
        $this->audit->log('auth.otp_resent');

        return redirect()->route('login.code')->with('status', __('A new code has been sent.'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get('auth.otp_email');

        if (! $email) {
            return redirect()->route('login');
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        if (! $this->otp->verify(OtpPurpose::Login, $email, $data['code'])) {
            $this->audit->log('auth.otp_failed');

            return back()->withErrors(['code' => __('The code is invalid or has expired.')]);
        }

        $user = DB::transaction(function () use ($email, $request) {
            $user = User::query()->whereBlind('email', $email)->lockForUpdate()->first();

            if (! $user) {
                $user = User::query()->create(['email' => $email, 'email_verified_at' => now()]);
                $this->audit->log('auth.registered', $user, actorType: 'user', actorId: $user->id);
            }

            $version = (string) config('scim.terms_version');
            if ($user->is_active && ! UserConsent::query()->where('user_id', $user->id)->where('terms_version', $version)->exists()) {
                UserConsent::query()->create([
                    'user_id' => $user->id,
                    'terms_version' => $version,
                    'accepted_at' => $request->session()->get('auth.terms_accepted_at', now()),
                ]);
            }

            return $user;
        });

        $request->session()->forget(['auth.otp_email', 'auth.otp_sent_at', 'auth.terms_accepted_at']);

        if (! $user->is_active) {
            $this->audit->log('auth.login_blocked_inactive', $user);

            return redirect()->route('login')->withErrors(['email' => __('This account has been deactivated. Please contact support.')]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended($user->profile ? route('dashboard') : route('profile.complete'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
