<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Auth\OtpService;
use App\Support\Security\AuditLogger;
use App\Support\Security\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Administrator sign-in, two factors:
 *   1) one-time code sent to the admin email (possession of the mailbox);
 *   2) TOTP from an authenticator app (possession of the device).
 * On first sign-in the authenticator is enrolled. Codes cannot be replayed.
 */
class AdminAuthController extends Controller
{
    private const PENDING = 'admin.pending_id';

    public function __construct(private readonly OtpService $otp, private readonly AuditLogger $audit) {}

    public function showEmail(): View
    {
        return view('admin.auth.login');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->validate(['email' => ['required', 'email:rfc', 'max:254']])['email']));

        // Same response whether or not the address belongs to an administrator.
        $admin = Admin::query()->whereBlind('email', $email)->where('is_active', true)->first();
        if ($admin) {
            $this->otp->send(OtpPurpose::AdminLogin, $email);
        }
        $this->audit->log('admin.otp_requested', context: ['known' => (bool) $admin], actorType: 'guest');

        $request->session()->put('auth.otp_email', $email);
        $request->session()->forget(self::PENDING);

        return redirect()->route('admin.login.code');
    }

    public function showCode(Request $request): View|RedirectResponse
    {
        return $request->session()->has('auth.otp_email')
            ? view('admin.auth.code', ['email' => $request->session()->get('auth.otp_email')])
            : redirect()->route('admin.login');
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get('auth.otp_email');
        $code = (string) $request->validate(['code' => ['required', 'string', 'max:20']])['code'];

        if (! $email || ! $this->otp->verify(OtpPurpose::AdminLogin, $email, $code)) {
            $this->audit->log('admin.otp_failed', actorType: 'guest');

            return back()->withErrors(['code' => __('The code is invalid or has expired.')]);
        }

        $admin = Admin::query()->whereBlind('email', $email)->where('is_active', true)->firstOrFail();

        $request->session()->forget('auth.otp_email');
        $request->session()->regenerate();
        $request->session()->put(self::PENDING, $admin->id);

        if (! $admin->hasMfa()) {
            $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => null])->save();
        }

        return redirect()->route('admin.login.mfa');
    }

    public function showMfa(Request $request): View|RedirectResponse
    {
        $admin = $this->pending($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.mfa', [
            'enrolling' => ! $admin->hasMfa(),
            'secret' => $admin->hasMfa() ? null : $admin->totp_secret,
            'uri' => $admin->hasMfa() ? null : Totp::uri($admin->totp_secret, $admin->email),
        ]);
    }

    public function verifyMfa(Request $request): RedirectResponse
    {
        $admin = $this->pending($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $code = (string) $request->validate(['code' => ['required', 'string', 'max:20']])['code'];
        $step = Totp::verify((string) $admin->totp_secret, $code, $admin->totp_last_step);

        if ($step === null) {
            $this->audit->log('admin.mfa_failed', $admin, actorType: 'guest');

            return back()->withErrors(['code' => __('The authenticator code is invalid.')]);
        }

        $enrolled = ! $admin->hasMfa();
        $admin->forceFill([
            'totp_last_step' => $step,
            'totp_confirmed_at' => $admin->totp_confirmed_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        $request->session()->forget(self::PENDING);
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        $this->audit->log($enrolled ? 'admin.mfa_enrolled' : 'admin.login', $admin, actorType: 'admin', actorId: $admin->id);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($admin) {
            $this->audit->log('admin.logout', $admin, actorType: 'admin', actorId: $admin->id);
        }

        return redirect()->route('admin.login');
    }

    private function pending(Request $request): ?Admin
    {
        $id = $request->session()->get(self::PENDING);

        return $id ? Admin::query()->whereKey($id)->where('is_active', true)->first() : null;
    }
}
