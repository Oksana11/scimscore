@extends('layouts.app')

@section('title', 'Assessment')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route('patients.show', $assessment->patient) }}" class="btn-link print:hidden">‹ {{ $assessment->patient->system_id }}</a>

        <div class="card">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">SCIM {{ $assessment->scim_version->value }} assessment</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $assessment->patient->system_id }} · Research ID <strong>{{ $assessment->patient->research_id }}</strong> · {{ $assessment->patient->alias }}
                        @if ($assessment->is_first) · <span class="text-violet-700">First assessment</span>@endif
                    </p>
                </div>
                <div class="flex gap-2 print:hidden">
                    <button type="button" class="btn-secondary" data-print>Print</button>
                    <form method="POST" action="{{ route('assessments.email', $assessment) }}">
                        @csrf
                        <button type="submit" class="btn-secondary">Email to me</button>
                    </form>
                </div>
            </div>
            <div class="mt-6">@include('assessments._result')</div>
        </div>

        <div class="card">
            <h2 class="font-semibold">Answers</h2>
            <div class="mt-4">@include('assessments._answers')</div>
            <p class="mt-4 text-xs text-slate-500">Saved assessments cannot be changed. Algorithm {{ $assessment->calc_version }}.</p>
        </div>
    </div>
@endsection
