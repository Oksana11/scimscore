@extends('admin.layout')
@section('title', 'Administrators')
@section('content')
    <h1 class="text-2xl font-semibold">Administrators</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="card overflow-x-auto p-0 lg:col-span-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase text-slate-500">
                        <th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Authenticator</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $a)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3 font-medium">{{ $a->name }} @if ($a->is($me)) <span class="text-xs text-slate-400">(you)</span> @endif</td>
                            <td class="px-4 py-3">{{ $a->email }}</td>
                            <td class="px-4 py-3">{{ $a->hasMfa() ? 'Set up' : 'Not yet' }}</td>
                            <td class="px-4 py-3 {{ $a->is_active ? 'text-green-700' : 'text-slate-400' }}">{{ $a->is_active ? 'Active' : 'Deactivated' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @unless ($a->is($me))
                                    @if ($a->hasMfa())
                                        <form method="POST" action="{{ route('admin.admins.reset-mfa', $a) }}" class="inline">@csrf
                                            <button class="btn-link">Reset authenticator</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.admins.toggle', $a) }}" class="ml-3 inline">@csrf
                                        <button class="btn-link {{ $a->is_active ? 'text-red-700' : '' }}">{{ $a->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2 class="font-semibold">Add administrator</h2>
            <form method="POST" action="{{ route('admin.admins.store') }}" class="mt-3 space-y-4">@csrf
                <div>
                    <label for="name" class="label">Name</label>
                    <input id="name" name="name" class="input" maxlength="100" required value="{{ old('name') }}">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" class="input" maxlength="254" required value="{{ old('email') }}">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary">Add</button>
                <p class="hint">They sign in at /admin/login with an email code and set up the authenticator app at first sign-in.</p>
            </form>
        </section>
    </div>
@endsection
