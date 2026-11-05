<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends AdminController
{
    public function index(Request $request): View
    {
        $f = $request->validate([
            'email' => ['nullable', 'string', 'max:254'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $users = User::query()
            ->with(['profile.institution', 'profile.profession', 'profile.country'])
            ->withCount(['patients', 'assessments'])
            ->when($f['email'] ?? null, fn ($q, $v) => $q->whereBlind('email', $v))
            ->when(($f['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($f['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $this->log('admin.view_users');

        return view('admin.users', ['users' => $users, 'filters' => $f]);
    }

    public function toggle(User $user): RedirectResponse
    {
        $active = ! $user->is_active;
        $user->forceFill(['is_active' => $active, 'deactivated_at' => $active ? null : now()])->save();
        $this->log($active ? 'admin.user_activated' : 'admin.user_deactivated', $user);

        return back()->with('status', $active ? 'User activated.' : 'User deactivated. They are signed out on their next request.');
    }
}
