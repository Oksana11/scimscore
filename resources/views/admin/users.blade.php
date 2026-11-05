@extends('admin.layout')
@section('title', 'Users')
@section('content')
    <h1 class="text-2xl font-semibold">Users</h1>
    <form method="GET" class="card mt-6 flex flex-wrap items-end gap-4">
        <div><label class="label" for="em">Email (exact)</label><input id="em" name="email" type="email" class="input" value="{{ $filters['email'] ?? '' }}"></div>
        <div><label class="label" for="st">Status</label>
            <select id="st" name="status" class="input"><option value="">All</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
        </div>
        <button class="btn-primary">Search</button>
    </form>

    <div class="card mt-6 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 text-left text-slate-500"><tr>
                <th class="px-4 py-3">Email</th><th class="px-4 py-3">Profession</th><th class="px-4 py-3">Institution</th><th class="px-4 py-3">Country</th>
                <th class="px-4 py-3 text-right">Patients</th><th class="px-4 py-3 text-right">Assessments</th><th class="px-4 py-3">Registered</th><th class="px-4 py-3">Last login</th><th class="px-4 py-3">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $u)
                    <tr>
                        <td class="px-4 py-3">{{ $u->email }}</td>
                        <td class="px-4 py-3">{{ $u->profile?->profession?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $u->profile?->institution?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $u->profile?->country?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $u->patients_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $u->assessments_count }}</td>
                        <td class="px-4 py-3">{{ $u->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $u->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="flex items-center gap-2">
                                @csrf
                                <span class="{{ $u->is_active ? 'text-green-700' : 'text-red-700' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span>
                                <button class="btn-link">{{ $u->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">No users.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
