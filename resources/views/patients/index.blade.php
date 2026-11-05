@extends('layouts.account')

@section('title', 'My Patients')
@section('breadcrumb', 'My Patients')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="page-title">My Patients</h1>
            <p class="page-sub">Only patients you created are shown.</p>
        </div>
        <a href="{{ route('patients.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New patient</a>
    </div>

    <form method="GET" action="{{ route('patients.index') }}" class="card mt-6 flex flex-wrap gap-3 p-4">
        <label class="relative min-w-60 flex-1">
            <span class="sr-only">Search</span>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input name="q" class="input pl-10" value="{{ $filters['q'] ?? '' }}" placeholder="Research ID, alias or year of birth" autocomplete="off">
        </label>
        <select name="gender" class="input w-auto" aria-label="Gender">
            <option value="">Any gender</option>
            @foreach ($genders as $g)<option value="{{ $g->value }}" @selected((string) ($filters['gender'] ?? '') === (string) $g->value)>{{ $g->label() }}</option>@endforeach
        </select>
        <select name="version" class="input w-auto" aria-label="SCIM version">
            <option value="">Any SCIM version</option>
            @foreach (\App\Enums\ScimVersion::cases() as $v)<option value="{{ $v->value }}" @selected(($filters['version'] ?? '') === $v->value)>SCIM {{ $v->value }}</option>@endforeach
        </select>
        <button type="submit" class="btn-secondary">Search</button>
        @if (array_filter($filters))<a href="{{ route('patients.index') }}" class="btn-link self-center">Clear</a>@endif
    </form>
    <p class="mt-2 text-xs text-slate-500">Research ID and alias are matched exactly — they are stored encrypted.</p>

    <section class="card mt-4 overflow-hidden p-0">
        <div class="flex items-center justify-between px-5 py-3.5">
            <p class="text-[15px] text-slate-600">{{ $patients->total() }} patients</p>
            <button type="button" class="btn-secondary py-1.5 print:hidden" data-print><x-icon name="print" class="size-4" /> Print</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[15px]">
                <thead class="border-y border-line"><tr>
                    <th class="th">Patient</th><th class="th">System ID</th><th class="th">Level · AIS</th><th class="th">Visits</th><th class="th">Last assessment</th><th class="th">Version</th><th class="th">Total</th>
                </tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($patients as $p)
                        @php $a = $p->latestAssessment; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="td"><a href="{{ route('patients.show', $p) }}">@include('partials.patient-cell')</a></td>
                            <td class="td font-mono text-sm"><a href="{{ route('patients.show', $p) }}" class="hover:underline">{{ $p->system_id }}</a></td>
                            <td class="td">{{ $a?->nli?->value ?? $p->first_nli?->value ?? '—' }} · AIS {{ $a?->ais?->value ?? $p->first_ais?->value ?? '—' }}</td>
                            <td class="td tabular-nums">{{ $p->assessments_count }}</td>
                            <td class="td">{{ $a?->assessed_on->format('j M Y') ?? '—' }}</td>
                            <td class="td">@include('partials.version-pill', ['version' => $a?->scim_version])</td>
                            <td class="td font-bold tabular-nums">{{ $a?->result?->total ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="td py-10 text-center text-slate-500">{{ array_filter($filters) ? 'No matching patients.' : 'No patients yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $patients->links() }}</div>
@endsection
