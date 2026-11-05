@extends('layouts.app')

@section('title', $patient->system_id)

@section('content')
    <a href="{{ route('patients.index') }}" class="btn-link">‹ My Patients</a>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $patient->system_id }}</h1>
            <p class="mt-1 text-sm text-slate-600">
                Research ID <strong>{{ $patient->research_id }}</strong> · {{ $patient->alias }} ·
                {{ $patient->gender->label() }} · born {{ $patient->birth_year }}
                @if ($patient->first_nli || $patient->first_ais || $patient->first_ams !== null)
                    · first: {{ $patient->first_nli?->value ?? '—' }} / AIS {{ $patient->first_ais?->value ?? '—' }} / AMS {{ $patient->first_ams ?? '—' }}
                @endif
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('patients.edit', $patient) }}" class="btn-secondary">Edit details</a>
            <a href="{{ route('patients.assessments.create', $patient) }}" class="btn-primary">+ New assessment</a>
        </div>
    </div>

    <form method="GET" action="{{ route('patients.compare', $patient) }}" class="card mt-6 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Compare A</span>A</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Compare B</span>B</th>
                    <th class="px-4 py-3 font-medium">Date</th>
                    <th class="px-4 py-3 font-medium">Version</th>
                    <th class="px-4 py-3 font-medium">Level · AIS</th>
                    <th class="px-4 py-3 text-right font-medium">AMS</th>
                    <th class="px-4 py-3 text-right font-medium">Self-care</th>
                    <th class="px-4 py-3 text-right font-medium">Resp./sph.</th>
                    <th class="px-4 py-3 text-right font-medium">Mobility</th>
                    <th class="px-4 py-3 text-right font-medium">Total</th>
                    <th class="px-4 py-3 text-right font-medium">Δ</th>
                    <th class="px-4 py-3 text-right font-medium">SCI-ARMI</th>
                    <th class="px-4 py-3 font-medium">Grade</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $prev = null; @endphp
                @forelse ($assessments as $a)
                    @php $delta = $prev ? $a->result->total - $prev->result->total : null; $prev = $a; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><input type="radio" name="a" value="{{ $a->public_id }}" @checked($loop->index === max(0, $loop->count - 2)) aria-label="Compare A"></td>
                        <td class="px-4 py-3"><input type="radio" name="b" value="{{ $a->public_id }}" @checked($loop->last) aria-label="Compare B"></td>
                        <td class="px-4 py-3"><a class="btn-link" href="{{ route('assessments.show', $a) }}">{{ $a->assessed_on->format('d M Y') }}</a>
                            @if ($a->is_first)<span class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 text-xs text-violet-800">First</span>@endif</td>
                        <td class="px-4 py-3">SCIM {{ $a->scim_version->value }}</td>
                        <td class="px-4 py-3">{{ $a->nli?->value }} · {{ $a->ais?->value }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $a->ams ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $a->result->self_care }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $a->result->respiration_sphincter }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $a->result->mobility }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $a->result->total }}</td>
                        <td class="px-4 py-3 text-right tabular-nums {{ $delta === null ? 'text-slate-400' : ($delta >= 0 ? 'text-green-700' : 'text-red-700') }}">
                            {{ $delta === null ? 'Baseline' : ($delta >= 0 ? '+' : '').$delta }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $a->result->sciarmi_total ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->result->grade ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="px-4 py-8 text-center text-slate-500">No assessments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($assessments->count() >= 2)
            <div class="border-t border-slate-200 px-4 py-3">
                <button type="submit" class="btn-secondary">Compare A and B</button>
            </div>
        @endif
    </form>
@endsection
