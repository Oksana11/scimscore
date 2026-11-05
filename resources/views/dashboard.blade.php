@extends('layouts.account')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    <h1 class="page-title">{{ $greeting }}</h1>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <a href="{{ route('patients.create') }}" class="card flex items-start gap-4 hover:border-slate-300">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand"><x-icon name="user-plus" /></span>
            <span>
                <span class="block text-[17px] font-bold">New Patient — First Assessment</span>
                <span class="mt-1 block text-[15px] text-slate-500">Create an anonymous patient record and complete their first SCIM.</span>
            </span>
        </a>
        <a href="{{ route('patients.index') }}" class="flex items-start gap-4 rounded-2xl bg-brand p-6 text-white shadow-[0_14px_30px_-16px_rgba(37,99,235,0.8)] hover:bg-brand-dark">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/15"><x-icon name="search" /></span>
            <span>
                <span class="block text-[17px] font-bold">Follow-up — Existing Patient</span>
                <span class="mt-1 block text-[15px] text-blue-100">Search your patients first, then add an assessment to their history.</span>
            </span>
        </a>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <div class="card"><p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Patients</p><p class="mt-1 text-3xl font-bold tabular-nums">{{ $patientCount }}</p></div>
        <div class="card"><p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Assessments</p><p class="mt-1 text-3xl font-bold tabular-nums">{{ $assessmentCount }}</p></div>
        <div class="card"><p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">SCIM III / IV</p><p class="mt-1 text-3xl font-bold tabular-nums">{{ $iii }} <span class="text-slate-300">/</span> {{ $iv }}</p></div>
    </div>

    <section class="card mt-4 overflow-hidden p-0">
        <div class="flex items-center justify-between px-5 py-4">
            <h2 class="section-title">Recent patients</h2>
            <a href="{{ route('patients.index') }}" class="btn-link">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[15px]">
                <thead class="border-y border-line"><tr>
                    <th class="th">Patient</th><th class="th">System ID</th><th class="th">Level · AIS</th><th class="th">Last assessment</th><th class="th">Version</th><th class="th">Total</th>
                </tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($recent as $p)
                        @php $a = $p->latestAssessment; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="td"><a href="{{ route('patients.show', $p) }}">@include('partials.patient-cell')</a></td>
                            <td class="td font-mono text-sm">{{ $p->system_id }}</td>
                            <td class="td">{{ $a?->nli?->value ?? '—' }} · AIS {{ $a?->ais?->value ?? '—' }}</td>
                            <td class="td">{{ $a?->assessed_on->format('j M Y') ?? '—' }}</td>
                            <td class="td">@include('partials.version-pill', ['version' => $a?->scim_version])</td>
                            <td class="td font-bold tabular-nums">{{ $a?->result?->total ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="td py-10 text-center text-slate-500">No patients yet. Start with <a class="btn-link" href="{{ route('patients.create') }}">a new patient</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
