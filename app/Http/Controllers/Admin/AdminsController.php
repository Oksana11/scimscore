<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administrators manage other administrators. A new admin signs in with an email code
 * and sets up the authenticator app at first sign-in. Nobody can act on their own account
 * here, so the last active admin cannot lock everyone out.
 */
class AdminsController extends AdminController
{
    public function index(): View
    {
        return view('admin.admins', [
            'admins' => Admin::query()->orderBy('id')->get(),
            'me' => $this->admin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'name' => ['required', 'string', 'max:100'],
        ]);
        $email = mb_strtolower(trim($data['email']));

        if (Admin::query()->whereBlind('email', $email)->exists()) {
            return back()->withInput()->withErrors(['email' => 'An administrator with this email already exists.']);
        }

        $admin = Admin::query()->create(['email' => $email, 'name' => trim($data['name'])]);
        $this->log('admin.created', $admin);

        return back()->with('status', 'Administrator added. They sign in at '.url('/admin/login').' and set up the authenticator app at first sign-in.');
    }

    public function toggle(Admin $admin): RedirectResponse
    {
        $this->guardNotSelf($admin);

        $admin->forceFill(['is_active' => ! $admin->is_active])->save();
        $this->log($admin->is_active ? 'admin.activated' : 'admin.deactivated', $admin);

        return back()->with('status', $admin->is_active ? 'Administrator activated.' : 'Administrator deactivated.');
    }

    public function resetMfa(Admin $admin): RedirectResponse
    {
        $this->guardNotSelf($admin);

        $admin->forceFill(['totp_secret' => null, 'totp_confirmed_at' => null, 'totp_last_step' => null])->save();
        $this->log('admin.mfa_reset', $admin);

        return back()->with('status', 'Authenticator reset. A new QR code is shown at their next sign-in.');
    }

    private function guardNotSelf(Admin $admin): void
    {
        abort_if($admin->is($this->admin()), 403, 'You cannot change your own administrator account here.');
    }
}
