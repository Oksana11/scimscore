@extends('admin.layout')
@section('title', 'Assessments')
@section('content')
    @php $sel = fn ($k) => $filters[$k] ?? ''; @endphp
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Assessments</h1>
            <p class="text-sm text-slate-600">Read-only. Exports contain no Research IDs or aliases.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.assessments.export', ['format' => 'xlsx'] + request()->query()) }}" class="btn-secondary">Export Excel</a>
            <a href="{{ route('admin.assessments.export', ['format' => 'csv'] + request()->query()) }}" class="btn-secondary">Export CSV</a>
        </div>
    </div>

    <form method="GET" class="card mt-6 grid gap-4 sm:grid-cols-4 lg:grid-cols-8">
        <div><label class="label" for="src">Source</label><select id="src" name="source" class="input"><option value="">Any</option><option value="account" @selected($sel('source') === 'account')>Account</option><option value="quick" @selected($sel('source') === 'quick')>Quick</option><option value="legacy" @selected($sel('source') === 'legacy')>Legacy (old site)</option></select></div>
        <div><label class="label" for="ctry">Country</label><select id="ctry" name="country" class="input"><option value="">Any</option>@foreach ($countries as $c)<option value="{{ $c->code }}" @selected($sel('country') === $c->code)>{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="label" for="ver">Version</label><select id="ver" name="version" class="input"><option value="">Any</option>@foreach ($versions as $v)<option value="{{ $v->value }}" @selected($sel('version') === $v->value)>SCIM {{ $v->value }}</option>@endforeach</select></div>
        <div><label class="label" for="gen">Gender</label><select id="gen" name="gender" class="input"><option value="">Any</option>@foreach ($genders as $g)<option value="{{ $g->value }}" @selected((string) $sel('gender') === (string) $g->value)>{{ $g->label() }}</option>@endforeach</select></div>
        <div><label class="label" for="nli">Level</label><select id="nli" name="nli" class="input"><option value="">Any</option>@foreach ($levels as $l)<option @selected($sel('nli') === $l->value)>{{ $l->value }}</option>@endforeach</select></div>
        <div><label class="label" for="ais">AIS</label><select id="ais" name="ais" class="input"><option value="">Any</option>@foreach ($aisGrades as $a)<option @selected($sel('ais') === $a->value)>{{ $a->value }}</option>@endforeach</select></div>
        <div><label class="label" for="d1">From</label><input id="d1" type="date" name="date_from" class="input" value="{{ $sel('date_from') }}"></div>
        <div><label class="label" for="d2">To</label><input id="d2" type="date" name="date_to" class="input" value="{{ $sel('date_to') }}"></div>
        <div><label class="label" for="t1">SCIM from</label><input id="t1" type="number" name="total_from" class="input" value="{{ $sel('total_from') }}"></div>
        <div><label class="label" for="t2">SCIM to</label><input id="t2" type="number" name="total_to" class="input" value="{{ $sel('total_to') }}"></div>
        <div class="flex items-end"><button class="btn-primary">Apply</button></div>
        @if ($errors->any() && ! $errors->has('export'))<p class="field-error sm:col-span-4">{{ $errors->first() }}</p>@endif
    </form>

    <p class="mt-4 text-sm text-slate-600">{{ $assessments->total() }} assessments</p>
    <div class="card mt-2 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 text-left text-slate-500"><tr>
                <th class="px-4 py-3">Date</th><th class="px-4 py-3">Source</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Institution</th><th class="px-4 py-3">Version</th>
                <th class="px-4 py-3">Gender / age</th><th class="px-4 py-3">Level · AIS · AMS</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">SCI-ARMI</th><th class="px-4 py-3">Grade</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assessments as $a)
                    <tr>
                        <td class="px-4 py-3">{{ $a->assessed_on->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $a->source->value }}</td>
                        <td class="px-4 py-3">{{ $a->patient?->system_id ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->patient?->institution?->name ?? '—' }}</td>
                        <td class="px-4 py-3">SCIM {{ $a->scim_version->value }}</td>
                        <td class="px-4 py-3">{{ $a->gender->label() }}, {{ $a->age }}</td>
                        <td class="px-4 py-3">{{ $a->nli?->value }} · {{ $a->ais?->value }} · {{ $a->ams ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $a->result->total }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $a->result->sciarmi_total ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->result->grade ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500">No assessments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $assessments->links() }}</div>
@endsection
