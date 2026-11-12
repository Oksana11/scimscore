@extends('layouts.account')

@section('title', 'Assessment')
@section('breadcrumb')<a href="{{ route('patients.index') }}" class="hover:underline">My Patients</a> / {{ $assessment->patient->system_id }} / Result @endsection

@section('content')
    <div class="space-y-5">
        <a href="{{ route('patients.show', $assessment->patient) }}" class="back-link print:hidden"><x-icon name="chevron-left" class="size-4" /> {{ $assessment->patient->system_id }}</a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div id="print-summary">
                <h1 class="page-title">SCIM {{ $assessment->scim_version->value }} result</h1>
                <p class="page-sub">
                    {{ $assessment->patient->alias }} · <span class="font-mono">{{ $assessment->patient->research_id }}</span> · {{ $assessment->assessed_on->format('j M Y') }}
                    · {{ $assessment->is_first ? 'First assessment' : 'Follow-up' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <button type="button" class="btn-secondary" data-print="#print-summary, #print-results"><x-icon name="print" class="size-4" /> Print</button>
                <a href="{{ route('assessments.pdf', $assessment) }}" class="btn-secondary text-orange-700"><x-icon name="file" class="size-4" /> PDF</a>
                <a href="{{ route('assessments.xlsx', $assessment) }}" class="btn-secondary text-green-700"><x-icon name="sheet" class="size-4" /> Excel</a>
                <form method="POST" action="{{ route('assessments.email', $assessment) }}">@csrf
                    <button type="submit" class="btn-secondary"><x-icon name="mail" class="size-4" /> Email to me</button>
                </form>
            </div>
        </div>

        <div id="print-results" class="space-y-5">
            @include('assessments._result')
            @include('assessments._answers')
        </div>

        <p class="text-sm text-slate-500">Saved assessments cannot be changed · algorithm {{ $assessment->calc_version }}</p>
    </div>
@endsection
