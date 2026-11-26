@extends('admin.layout')
@section('title', 'Settings')
@section('content')
    <h1 class="text-2xl font-semibold">Settings</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card">
            <h2 class="font-semibold">Professions</h2>
            <table class="mt-3 w-full text-sm">
                @foreach ($professions as $p)
                    <tr class="border-b border-slate-100">
                        <td class="py-2">{{ $p->name }}</td>
                        <td class="py-2 text-right">
                            <form method="POST" action="{{ route('admin.professions.toggle', $p) }}">@csrf
                                <span class="{{ $p->is_active ? 'text-green-700' : 'text-slate-400' }}">{{ $p->is_active ? 'Shown' : 'Hidden' }}</span>
                                <button class="btn-link ml-2">{{ $p->is_active ? 'Hide' : 'Show' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
            <form method="POST" action="{{ route('admin.professions.store') }}" class="mt-4 flex gap-2">@csrf
                <input name="name" class="input" placeholder="New profession" required maxlength="100" aria-label="New profession">
                <button class="btn-secondary">Add</button>
            </form>
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </section>

        <section class="card">
            <h2 class="font-semibold">Custom institutions</h2>
            <p class="mt-1 text-xs text-slate-500">Entered manually by users. Rename, or merge into an official (ROR) institution by its ID.</p>
            <div class="mt-3 space-y-4">
                @forelse ($customInstitutions as $i)
                    <div class="rounded-lg border border-slate-200 p-3 text-sm">
                        <p><strong>#{{ $i->id }}</strong> {{ $i->name }} · {{ $i->country_code }} · {{ $i->profiles_count }} users</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.institutions.rename', $i) }}" class="flex gap-2">@csrf
                                <input name="name" class="input py-1" value="{{ $i->name }}" aria-label="Name"><button class="btn-secondary py-1">Rename</button>
                            </form>
                            <form method="POST" action="{{ route('admin.institutions.merge', $i) }}" class="flex gap-2">@csrf
                                <input name="target_id" type="number" class="input w-28 py-1" placeholder="Target ID" aria-label="Target institution ID"><button class="btn-secondary py-1">Merge</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No custom institutions.</p>
                @endforelse
                @error('target_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="card lg:col-span-2">
            <h2 class="font-semibold">Countries</h2>
            <p class="mt-1 text-xs text-slate-500">Hidden countries are not offered in the profile form.</p>
            <div class="mt-3 grid max-h-96 gap-x-6 overflow-auto text-sm sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($countries as $c)
                    <form method="POST" action="{{ route('admin.countries.toggle', $c) }}" class="flex items-center justify-between border-b border-slate-100 py-1">@csrf
                        <span class="{{ $c->is_active ? '' : 'text-slate-400 line-through' }}">{{ $c->name }}</span>
                        <button class="btn-link text-xs">{{ $c->is_active ? 'Hide' : 'Show' }}</button>
                    </form>
                @endforeach
            </div>
        </section>
    </div>
@endsection
