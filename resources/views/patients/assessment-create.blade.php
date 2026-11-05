@extends('layouts.app')

@section('title', 'New assessment')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('patients.show', $patient) }}" class="btn-link">‹ {{ $patient->system_id }}</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $isFirst ? 'First assessment' : 'Follow-up assessment' }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            {{ $patient->system_id }} · Research ID <strong>{{ $patient->research_id }}</strong> · {{ $patient->alias }} ·
            {{ $patient->gender->label() }} · born {{ $patient->birth_year }}
        </p>

        @if (count($versions) > 1)
            <nav class="mt-4 inline-flex rounded-lg border border-slate-300 bg-white p-1 text-sm" aria-label="SCIM version">
                @foreach ($versions as $v)
                    <a href="{{ route('patients.assessments.create', [$patient, 'version' => $v->value]) }}"
                       class="rounded-md px-3 py-1 {{ $v === $version ? 'bg-slate-900 text-white' : 'text-slate-700' }}">SCIM {{ $v->value }}</a>
                @endforeach
            </nav>
        @endif

        @if ($errors->any())
            <div class="alert-error mt-6" role="alert">Please check the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('patients.assessments.store', $patient) }}" class="mt-6 space-y-6" novalidate>
            @csrf
            <input type="hidden" name="scim_version" value="{{ $version->value }}">

            <div class="card">
                <h2 class="font-semibold">At the time of this assessment</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-4">
                    <div>
                        <label for="assessed_on" class="label">Date *</label>
                        <input id="assessed_on" name="assessed_on" type="date" class="input" max="{{ now()->toDateString() }}" value="{{ old('assessed_on', now()->toDateString()) }}" required>
                        @error('assessed_on') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nli" class="label">Neurological level *</label>
                        <select id="nli" name="nli" class="input" required>
                            <option value="">Select…</option>
                            @foreach ($levels as $l)<option value="{{ $l->value }}" @selected(old('nli', $prefill['nli'] ?? null) === $l->value)>{{ $l->value }}</option>@endforeach
                        </select>
                        @error('nli') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ais" class="label">AIS grade *</label>
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
                <p class="mt-3 text-xs text-slate-500">Age for SCI-ARMI is calculated from the year of birth and the assessment date.</p>
            </div>

            @include('assessments._scim-items', ['definition' => $definition])

            <div class="card space-y-4">
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="accept_terms" value="1" class="mt-0.5" @checked(old('accept_terms')) required>
                    <span>
                        I confirm that the data entered into the system will be stored anonymously and used for research,
                        statistics and system improvement. I confirm that no identifying details of the patient will be
                        entered or stored. <a href="{{ route('terms') }}" class="btn-link" target="_blank" rel="noopener">Terms of Use</a>
                    </span>
                </label>
                @error('accept_terms') <p class="field-error">{{ $message }}</p> @enderror
                <button type="submit" class="btn-primary">Calculate &amp; save</button>
            </div>
        </form>
    </div>
@endsection
