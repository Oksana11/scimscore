@extends('layouts.app')

@section('title', 'Assessment history')

@section('content')
    @php $sel = fn ($k) => $filters[$k] ?? ''; $qs = request()->query(); @endphp
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Assessment history</h1>
            <p class="text-sm text-slate-600">Filter your data and export it for SPSS or any other research tool.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('assessments.export.xlsx', $qs) }}" class="btn-secondary">Export Excel</a>
            <a href="{{ route('assessments.export.pdf', $qs) }}" class="btn-secondary">Export PDF</a>
        </div>
    </div>
    @error('export') <div class="alert-error mt-4" role="alert">{{ $message }}</div> @enderror

    <details class="card mt-6" @if ($filtered) open @endif>
        <summary class="cursor-pointer font-semibold">Filters @if ($filtered)<span class="ml-2 text-sm font-normal text-blue-700">active</span>@endif</summary>
        <form method="GET" action="{{ route('assessments.index') }}" class="mt-4 space-y-5">
            <fieldset>
                <legend class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Patient details</legend>
                <div class="mt-2 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <div>
                        <label class="label" for="f_gender">Gender</label>
                        <select id="f_gender" name="gender" class="input"><option value="">Any</option>
                            @foreach ($genders as $g)<option value="{{ $g->value }}" @selected((string) $sel('gender') === (string) $g->value)>{{ $g->label() }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="f_by1">Born from</label>
                        <input id="f_by1" name="birth_year_from" type="number" class="input" value="{{ $sel('birth_year_from') }}">
                    </div>
                    <div>
                        <label class="label" for="f_by2">Born to</label>
                        <input id="f_by2" name="birth_year_to" type="number" class="input" value="{{ $sel('birth_year_to') }}">
                    </div>
                    <div>
                        <label class="label" for="f_nli">Injury level</label>
                        <select id="f_nli" name="nli" class="input"><option value="">Any</option>
                            @foreach ($levels as $l)<option value="{{ $l->value }}" @selected($sel('nli') === $l->value)>{{ $l->value }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="f_ais">AIS</label>
                        <select id="f_ais" name="ais" class="input"><option value="">Any</option>
                            @foreach ($aisGrades as $a)<option value="{{ $a->value }}" @selected($sel('ais') === $a->value)>{{ $a->value }}</option>@endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label" for="f_ams1">AMS from</label><input id="f_ams1" name="ams_from" type="number" min="0" max="100" class="input" value="{{ $sel('ams_from') }}"></div>
                        <div><label class="label" for="f_ams2">to</label><input id="f_ams2" name="ams_to" type="number" min="0" max="100" class="input" value="{{ $sel('ams_to') }}"></div>
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Assessment details</legend>
                <div class="mt-2 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <div>
                        <label class="label" for="f_ver">SCIM version</label>
                        <select id="f_ver" name="version" class="input"><option value="">Any</option>
                            @foreach ($versions as $v)<option value="{{ $v->value }}" @selected($sel('version') === $v->value)>SCIM {{ $v->value }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="f_type">Type</label>
                        <select id="f_type" name="type" class="input"><option value="">Any</option>
                            <option value="first" @selected($sel('type') === 'first')>First assessment</option>
                            <option value="followup" @selected($sel('type') === 'followup')>Follow-up</option>
                        </select>
                    </div>
                    <div><label class="label" for="f_d1">Date from</label><input id="f_d1" name="date_from" type="date" class="input" value="{{ $sel('date_from') }}"></div>
                    <div><label class="label" for="f_d2">Date to</label><input id="f_d2" name="date_to" type="date" class="input" value="{{ $sel('date_to') }}"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label" for="f_t1">SCIM from</label><input id="f_t1" name="total_from" type="number" min="0" max="100" class="input" value="{{ $sel('total_from') }}"></div>
                        <div><label class="label" for="f_t2">to</label><input id="f_t2" name="total_to" type="number" min="0" max="100" class="input" value="{{ $sel('total_to') }}"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label" for="f_s1">SCI-ARMI from</label><input id="f_s1" name="sciarmi_from" type="number" step="0.01" class="input" value="{{ $sel('sciarmi_from') }}"></div>
                        <div><label class="label" for="f_s2">to</label><input id="f_s2" name="sciarmi_to" type="number" step="0.01" class="input" value="{{ $sel('sciarmi_to') }}"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="label" for="f_g1">Grade from</label>
                            <select id="f_g1" name="grade_from" class="input"><option value="">—</option>
                                @foreach ($grades as $g)<option @selected($sel('grade_from') === $g)>{{ $g }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="f_g2">to</label>
                            <select id="f_g2" name="grade_to" class="input"><option value="">—</option>
                                @foreach ($grades as $g)<option @selected($sel('grade_to') === $g)>{{ $g }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>
            @if ($errors->any() && ! $errors->has('export'))
                <div class="alert-error">{{ $errors->first() }}</div>
            @endif
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">View results</button>
                @if ($filtered)<a href="{{ route('assessments.index') }}" class="btn-secondary">Clear</a>@endif
            </div>
        </form>
    </details>

    <p class="mt-4 text-sm text-slate-600">{{ $assessments->total() }} assessments</p>

    <div class="card mt-2 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Date</th>
                    <th class="px-4 py-3 font-medium">Patient</th>
                    <th class="px-4 py-3 font-medium">Version</th>
                    <th class="px-4 py-3 font-medium">Level · AIS</th>
                    <th class="px-4 py-3 text-right font-medium">Total</th>
                    <th class="px-4 py-3 text-right font-medium">SCI-ARMI</th>
                    <th class="px-4 py-3 font-medium">Grade</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assessments as $a)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><a class="btn-link" href="{{ route('assessments.show', $a) }}">{{ $a->assessed_on->format('d M Y') }}</a></td>
                        <td class="px-4 py-3"><a class="btn-link" href="{{ route('patients.show', $a->patient->public_id) }}">{{ $a->patient->system_id }}</a> · {{ $a->patient->research_id }}</td>
                        <td class="px-4 py-3">SCIM {{ $a->scim_version->value }}</td>
                        <td class="px-4 py-3">{{ $a->nli?->value }} · {{ $a->ais?->value }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $a->result->total }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $a->result->sciarmi_total ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->result->grade ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ $filtered ? 'No assessments match the filters.' : 'No assessments yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $assessments->links() }}</div>
@endsection
