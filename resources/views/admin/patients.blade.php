@extends('admin.layout')
@section('title', 'Patients')
@section('content')
    <h1 class="text-2xl font-semibold">Patients</h1>
    <p class="text-sm text-slate-600">Read-only. Research IDs and aliases belong to the users and are not shown.</p>
    <form method="GET" class="card mt-6 flex flex-wrap items-end gap-4">
        <div><label class="label" for="sid">System ID</label><input id="sid" name="system_id" class="input" value="{{ $filters['system_id'] ?? '' }}"></div>
        <div><label class="label" for="g">Gender</label>
            <select id="g" name="gender" class="input"><option value="">Any</option>
                @foreach ($genders as $g)<option value="{{ $g->value }}" @selected((string) ($filters['gender'] ?? '') === (string) $g->value)>{{ $g->label() }}</option>@endforeach
            </select>
        </div>
        <div><label class="label" for="by">Year of birth</label><input id="by" name="birth_year" type="number" class="input" value="{{ $filters['birth_year'] ?? '' }}"></div>
        <button class="btn-primary">Search</button>
    </form>
    <div class="card mt-6 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 text-left text-slate-500"><tr>
                <th class="px-4 py-3">System ID</th><th class="px-4 py-3">Institution</th><th class="px-4 py-3">Gender</th><th class="px-4 py-3">Born</th>
                <th class="px-4 py-3">First (level / AIS / AMS)</th><th class="px-4 py-3 text-right">Assessments</th><th class="px-4 py-3">Created</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($patients as $p)
                    <tr>
                        <td class="px-4 py-3"><a class="btn-link" href="{{ route('admin.assessments', ['system_id' => $p->system_id]) }}">{{ $p->system_id }}</a></td>
                        <td class="px-4 py-3">{{ $p->institution?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $p->gender->label() }}</td>
                        <td class="px-4 py-3">{{ $p->birth_year }}</td>
                        <td class="px-4 py-3">{{ $p->first_nli?->value ?? '—' }} / {{ $p->first_ais?->value ?? '—' }} / {{ $p->first_ams ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $p->assessments_count }}</td>
                        <td class="px-4 py-3">{{ $p->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No patients.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $patients->links() }}</div>
@endsection
