@extends('layouts.account')

@section('title', 'New assessment')
@section('breadcrumb', 'New Assessment')

@section('content')
    <div class="mx-auto max-w-[900px]">
        <a href="{{ route('patients.show', $patient) }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Cancel</a>

        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $isFirst ? 'First assessment' : 'Follow-up assessment' }}</h1>
                <p class="page-sub">SCIM {{ $version->value }} · {{ count($definition->allowedScores()) }} items · 0–100 · {{ now()->format('j M Y') }}</p>
            </div>
            @include('assessments._version-toggle', ['url' => fn ($v) => route('patients.assessments.create', [$patient, 'version' => $v->value])])
        </div>

        <div class="card mt-5 flex items-center justify-between gap-3 px-5 py-3.5">
            <div class="flex items-center gap-3">
                <span class="avatar">{{ $patient->initials() }}</span>
                <div class="leading-tight">
                    <p><strong>{{ $patient->alias }}</strong> <span class="font-mono text-xs text-slate-500">{{ $patient->research_id }}</span></p>
                    <p class="font-mono text-xs text-slate-400">{{ $patient->system_id }} · {{ $patient->gender->label() }} · born {{ $patient->birth_year }}</p>
                </div>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isFirst ? 'bg-violet-100 text-violet-700' : 'bg-green-100 text-green-700' }}">{{ $isFirst ? 'First' : 'Follow-up' }}</span>
        </div>

        @if ($errors->any())
            <div class="alert-error mt-5" role="alert">Please check the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('patients.assessments.store', $patient) }}" class="mt-5 space-y-5" novalidate>
            @csrf
            <input type="hidden" name="scim_version" value="{{ $version->value }}">

            @include('assessments._progress')

            <section class="card">
                <h2 class="section-title">At the time of this assessment</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-4">
                    <div>
                        <label for="assessed_on" class="label">Date <span class="req">*</span></label>
                        <input id="assessed_on" name="assessed_on" type="date" class="input" max="{{ now()->toDateString() }}" value="{{ old('assessed_on', now()->toDateString()) }}" required>
                        @error('assessed_on') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nli" class="label">Neurological level <span class="req">*</span></label>
                        <select id="nli" name="nli" class="input" required>
                            <option value="">Select…</option>
                            @foreach ($levels as $l)<option value="{{ $l->value }}" @selected(old('nli', $prefill['nli'] ?? null) === $l->value)>{{ $l->value }}</option>@endforeach
                        </select>
                        @error('nli') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ais" class="label">AIS grade <span class="req">*</span></label>
                        <select id="ais" name="ais" class="input" required>
                            <option value="">Select…</option>
                            @foreach ($aisGrades as $a)<option value="{{ $a->value }}" @selected(old('ais', $prefill['ais'] ?? null) === $a->value)>{{ $a->value }}</option>@endforeach
                        </select>
                        @error('ais') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ams" class="label">AMS (0–100)</label>
                        <input id="ams" name="ams" type="number" min="0" max="100" class="input" value="{{ old('ams', $prefill['ams'] ?? null) }}">
                        @error('ams') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="hint">Age for SCI-ARMI is calculated from the year of birth and the assessment date.</p>
            </section>

            @include('assessments._scim-items')

            <div class="card space-y-4">
                @include('assessments._consent')
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary px-6 py-3 text-[15px]">Calculate &amp; save →</button>
                </div>
            </div>
        </form>
    </div>
@endsection
