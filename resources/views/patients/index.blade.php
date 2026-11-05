@extends('layouts.app')

@section('title', 'My Patients')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">My Patients</h1>
            <p class="text-sm text-slate-600">Only you can see your patients. Search for an existing patient before creating a new one.</p>
        </div>
        <a href="{{ route('patients.create') }}" class="btn-primary">+ New patient</a>
    </div>

    <form method="GET" action="{{ route('patients.index') }}" class="card mt-6 grid gap-4 sm:grid-cols-4 lg:grid-cols-8">
        <div class="lg:col-span-2">
            <label for="f_rid" class="label">Research ID</label>
            <input id="f_rid" name="research_id" class="input" value="{{ $filters['research_id'] ?? '' }}" autocomplete="off">
        </div>
        <div class="lg:col-span-2">
            <label for="f_alias" class="label">Alias</label>
            <input id="f_alias" name="alias" class="input" value="{{ $filters['alias'] ?? '' }}" autocomplete="off">
        </div>
        <div>
            <label for="f_gender" class="label">Gender</label>
            <select id="f_gender" name="gender" class="input">
                <option value="">Any</option>
                @foreach ($genders as $g)<option value="{{ $g->value }}" @selected((string) ($filters['gender'] ?? '') === (string) $g->value)>{{ $g->label() }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="f_yob" class="label">Year of birth</label>
            <input id="f_yob" name="birth_year" type="number" class="input" value="{{ $filters['birth_year'] ?? '' }}">
        </div>
        <div>
            <label for="f_nli" class="label">Level</label>
            <select id="f_nli" name="nli" class="input">
                <option value="">Any</option>
                @foreach ($levels as $l)<option value="{{ $l->value }}" @selected(($filters['nli'] ?? '') === $l->value)>{{ $l->value }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="f_ais" class="label">AIS</label>
            <select id="f_ais" name="ais" class="input">
                <option value="">Any</option>
                @foreach ($aisGrades as $a)<option value="{{ $a->value }}" @selected(($filters['ais'] ?? '') === $a->value)>{{ $a->value }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="f_ams" class="label">AMS</label>
            <input id="f_ams" name="ams" type="number" min="0" max="100" class="input" value="{{ $filters['ams'] ?? '' }}">
        </div>
        <div class="flex items-end gap-2 sm:col-span-3 lg:col-span-7">
            <button type="submit" class="btn-secondary">Search</button>
            @if (array_filter($filters))<a href="{{ route('patients.index') }}" class="btn-link">Clear</a>@endif
        </div>
    </form>
    <p class="mt-2 text-xs text-slate-500">Research ID and alias are matched exactly (they are stored encrypted).</p>

    <div class="card mt-6 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">System ID</th>
                    <th class="px-4 py-3 font-medium">Research ID</th>
                    <th class="px-4 py-3 font-medium">Alias</th>
                    <th class="px-4 py-3 font-medium">Gender</th>
                    <th class="px-4 py-3 font-medium">Born</th>
                    <th class="px-4 py-3 text-right font-medium">Assessments</th>
                    <th class="px-4 py-3 font-medium">Last</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($patients as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><a class="btn-link" href="{{ route('patients.show', $p) }}">{{ $p->system_id }}</a></td>
                        <td class="px-4 py-3">{{ $p->research_id }}</td>
                        <td class="px-4 py-3">{{ $p->alias }}</td>
                        <td class="px-4 py-3">{{ $p->gender->label() }}</td>
                        <td class="px-4 py-3">{{ $p->birth_year }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $p->assessments_count }}</td>
                        <td class="px-4 py-3">{{ $p->assessments_max_assessed_on ? \Illuminate\Support\Carbon::parse($p->assessments_max_assessed_on)->format('d M Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ array_filter($filters) ? 'No matching patients.' : 'No patients yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $patients->links() }}</div>
@endsection
