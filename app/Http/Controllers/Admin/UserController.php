<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\Privacy\DataEraser;
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
        abort_if($user->anonymized_at !== null, 404); // deleted accounts cannot be reactivated
        $active = ! $user->is_active;
        $user->forceFill(['is_active' => $active, 'deactivated_at' => $active ? null : now()])->save();
        $this->log($active ? 'admin.user_activated' : 'admin.user_deactivated', $user);

        return back()->with('status', $active ? 'User activated.' : 'User deactivated. They are signed out on their next request.');
    }

    /**
     * Erasure on request: same as the user deleting their own account.
     */
    public function destroy(Request $request, User $user, DataEraser $eraser): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:DELETE']], ['confirm.in' => 'Type DELETE to confirm.']);
        abort_if($user->anonymized_at !== null, 404);

        $eraser->eraseAccount($user, 'admin#'.$this->admin()->id);

        return back()->with('status', 'Account deleted. Patients and assessments were kept as anonymous data.');
    }
}
