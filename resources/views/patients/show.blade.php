@extends('layouts.account')

@section('title', $patient->system_id)
@section('breadcrumb')<a href="{{ route('patients.index') }}" class="hover:underline">My Patients</a> / Patient@endsection

@section('content')
    @php
        $latest = $assessments->last();
        $prev = $assessments->count() > 1 ? $assessments[$assessments->count() - 2] : null;
        $delta = $latest && $prev ? $latest->result->total - $prev->result->total : null;
        $chart = $assessments->take(-8)->values();
    @endphp

    <a href="{{ route('patients.index') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> My Patients</a>

    <section class="card mt-3 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="avatar size-14 text-lg">{{ $patient->initials() }}</span>
            <div>
                <h1 class="text-2xl font-bold">{{ $patient->alias }}</h1>
                <div class="mt-1.5 flex flex-wrap gap-2 text-sm">
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-500">System ID <strong class="font-mono text-ink">{{ $patient->system_id }}</strong></span>
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-500">Research ID <strong class="font-mono text-ink">{{ $patient->research_id }}</strong></span>
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-700">{{ $patient->gender->label() }} · born {{ $patient->birth_year }}</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('patients.edit', $patient) }}" class="btn-secondary">Edit details</a>
            <a href="{{ route('patients.assessments.create', $patient) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New assessment</a>
        </div>
    </section>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="card">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Latest SCIM total</p>
            @if ($latest)
                <p class="mt-1"><span class="text-5xl font-bold tabular-nums">{{ $latest->result->total }}</span><span class="ml-1 text-lg text-slate-400">/ 100</span></p>
                @if ($delta !== null)
                    <p class="mt-1 text-sm font-semibold {{ $delta >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $delta >= 0 ? '▲ +' : '▼ ' }}{{ $delta }} since previous</p>
                @else
                    <p class="mt-1 text-sm text-slate-400">Baseline</p>
                @endif
                {{-- mini column chart: SVG attributes only (CSP forbids inline styles) --}}
                <svg class="mt-4 h-28 w-full" viewBox="0 0 {{ max(3, $chart->count()) * 60 }} 110" role="img" aria-label="SCIM totals over time">
                    @foreach ($chart as $i => $a)
                        @php $h = max(2, round($a->result->total * 0.7)); $x = $i * 60 + 18; @endphp
                        <text x="{{ $x + 12 }}" y="{{ 84 - $h - 6 }}" text-anchor="middle" class="fill-slate-600 text-[11px] font-semibold">{{ $a->result->total }}</text>
                        <rect x="{{ $x }}" y="{{ 84 - $h }}" width="24" height="{{ $h }}" rx="3" class="{{ $loop->last ? 'fill-brand' : 'fill-blue-200' }}"><title>{{ $a->assessed_on->format('j M Y') }}: {{ $a->result->total }}</title></rect>
                        <text x="{{ $x + 12 }}" y="102" text-anchor="middle" class="fill-slate-400 text-[10px]">{{ $a->assessed_on->format("M 'y") }}</text>
                    @endforeach
                </svg>
            @else
                <p class="mt-3 text-[15px] text-slate-500">No assessments yet.</p>
            @endif
        </section>

        <section class="card">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Clinical data</p>
            <table class="mt-3 w-full text-[15px]">
                <thead><tr class="text-xs text-slate-400 uppercase"><th></th><th class="w-16 pb-1 text-left font-semibold">First</th><th class="w-16 pb-1 text-left font-semibold">Latest</th></tr></thead>
                <tbody>
                    <tr><td class="py-1 text-slate-600">Neurological level</td><td class="font-semibold">{{ $patient->first_nli?->value ?? $assessments->first()?->nli?->value ?? '—' }}</td><td class="font-semibold">{{ $latest?->nli?->value ?? '—' }}</td></tr>
                    <tr><td class="py-1 text-slate-600">AIS grade</td><td class="font-semibold">{{ $patient->first_ais?->value ?? $assessments->first()?->ais?->value ?? '—' }}</td><td class="font-semibold">{{ $latest?->ais?->value ?? '—' }}</td></tr>
                    <tr><td class="py-1 text-slate-600">AMS</td><td class="font-semibold">{{ $patient->first_ams ?? $assessments->first()?->ams ?? '—' }}</td><td class="font-semibold">{{ $latest?->ams ?? '—' }}</td></tr>
                </tbody>
            </table>
            <div class="mt-4 space-y-1 border-t border-line pt-4 text-[15px]">
                <p class="flex justify-between"><span class="text-slate-600">Assessments</span><strong>{{ $assessments->count() }}</strong></p>
                <p class="flex justify-between"><span class="text-slate-600">Created</span><strong>{{ $patient->created_at->format('j M Y') }}</strong></p>
            </div>
        </section>
    </div>

    <section class="card mt-4 overflow-hidden p-0">
        <h2 class="section-title px-5 py-4">Assessment history</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-[15px]">
                <thead class="border-y border-line"><tr>
                    <th class="th">Date</th><th class="th">Version</th><th class="th">Level · AIS</th><th class="th">Self-care</th><th class="th">Resp/Sph</th><th class="th">Mobility</th><th class="th">Total</th><th class="th">SCI-ARMI</th><th class="th">Δ</th><th class="th"></th>
                </tr></thead>
                <tbody class="divide-y divide-line">
                    @php $rows = $assessments->values(); @endphp
                    @forelse ($rows->reverse() as $i => $a)
                        @php $p = $i > 0 ? $rows[$i - 1] : null; $d = $p ? $a->result->total - $p->result->total : null; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="td font-semibold"><a href="{{ route('assessments.show', $a) }}" class="hover:underline">{{ $a->assessed_on->format('j M Y') }}</a>
                                @if ($a->is_first)<span class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 text-xs text-violet-700">First</span>@endif</td>
                            <td class="td">@include('partials.version-pill', ['version' => $a->scim_version])</td>
                            <td class="td">{{ $a->nli?->value }} · {{ $a->ais?->value }}</td>
                            <td class="td tabular-nums">{{ $a->result->self_care }}</td>
                            <td class="td tabular-nums">{{ $a->result->respiration_sphincter }}</td>
                            <td class="td tabular-nums">{{ $a->result->mobility }}</td>
                            <td class="td font-bold tabular-nums">{{ $a->result->total }}</td>
                            <td class="td tabular-nums">{{ $a->result->sciarmi_total !== null ? (float) $a->result->sciarmi_total : '—' }}</td>
                            <td class="td text-sm font-semibold tabular-nums {{ $d === null ? 'text-slate-400' : ($d >= 0 ? 'text-green-600' : 'text-red-600') }}">{{ $d === null ? 'Baseline' : ($d >= 0 ? '+' : '').$d }}</td>
                            <td class="td text-right"><a href="{{ route('assessments.show', $a) }}" aria-label="Open"><x-icon name="chevron-right" class="size-4 text-slate-300" /></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="td py-10 text-center text-slate-500">No assessments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($assessments->count() >= 2)
            <form method="GET" action="{{ route('patients.compare', $patient) }}" class="flex flex-wrap items-end gap-3 border-t border-line px-5 py-4">
                <div>
                    <label for="cmp_a" class="label">Compare</label>
                    <select id="cmp_a" name="a" class="input">
                        @foreach ($assessments as $a)<option value="{{ $a->public_id }}" @selected($loop->index === $assessments->count() - 2)>{{ $a->assessed_on->format('j M Y') }} · SCIM {{ $a->scim_version->value }} · {{ $a->result->total }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="cmp_b" class="label">with</label>
                    <select id="cmp_b" name="b" class="input">
                        @foreach ($assessments as $a)<option value="{{ $a->public_id }}" @selected($loop->last)>{{ $a->assessed_on->format('j M Y') }} · SCIM {{ $a->scim_version->value }} · {{ $a->result->total }}</option>@endforeach
                    </select>
                </div>
                <button type="submit" class="btn-secondary">Compare</button>
            </form>
        @endif
    </section>
@endsection
