@extends('layouts.app')

@section('title', 'Assessment history')

@section('content')
    <h1 class="text-2xl font-semibold">Assessment history</h1>
    <p class="text-sm text-slate-600">All assessments you saved, newest first.</p>

    <div class="card mt-6 overflow-x-auto p-0">
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
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No assessments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $assessments->links() }}</div>
@endsection
