@extends('layouts.app')

@section('title', 'Quick Assessment')

@section('content')
    <div class="mx-auto max-w-[1000px]">
        <a href="{{ route('quick.create') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Cancel</a>

        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">Quick Assessment</h1>
                <p class="page-sub">SCIM {{ $version->value }} · {{ count($definition->allowedScores()) }} items · 0–100 · {{ now()->format('j M Y') }} · not linked to a patient</p>
            </div>
            @include('assessments._version-toggle', ['url' => fn ($v) => route('quick.assessment', ['version' => $v->value])])
        </div>

        <div class="card mt-5 flex flex-wrap items-center justify-between gap-3 px-5 py-3.5">
            <p class="text-[15px] text-slate-500">
                <span class="mr-2 rounded-full bg-brand-soft px-2.5 py-1 text-xs font-semibold text-brand">Step 2 of 3</span>
                Caregiver: <strong class="text-ink">{{ $caregiver['profession'] }} · {{ $caregiver['country'] }} · {{ $caregiver['institution'] }}</strong>
            </p>
            <a href="{{ route('quick.create') }}" class="btn-link">Edit</a>
        </div>

        @if ($errors->any())
            <div class="alert-error mt-5" role="alert">Please check the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('quick.store') }}" class="mt-5 space-y-5" novalidate>
            @csrf
            <input type="hidden" name="scim_version" value="{{ $version->value }}">

            @include('assessments._progress')

            <section class="card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="section-title">Patient data</h2>
                        <p class="hint mt-0.5">Anonymous only — don't enter names, ID numbers or contact details.</p>
                    </div>
                    <span class="text-xs text-slate-400"><span class="req">*</span> required</span>
                </div>

                <div class="mt-5">
                    <label for="patient_code" class="label">Patient code (given by the caregiver for identification) <span class="req">*</span></label>
                    <input id="patient_code" name="patient_code" class="input font-mono" maxlength="50" placeholder="e.g. JD1985" value="{{ old('patient_code') }}" required autocomplete="off">
                    @error('patient_code') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-3">
                    <div>
                        <label for="age" class="label">Patient age <span class="req">*</span></label>
                        <input id="age" name="age" type="number" min="0" max="120" class="input" placeholder="Years" value="{{ old('age') }}" required>
                        @error('age') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <fieldset>
                        <legend class="label">Patient gender <span class="req">*</span></legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($genders as $g)
                                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[15px] font-semibold has-[:checked]:border-brand has-[:checked]:bg-brand-soft has-[:checked]:text-brand">
                                    <input type="radio" name="gender" value="{{ $g->value }}" class="size-4 accent-brand" @checked((string) old('gender') === (string) $g->value) required> {{ $g->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('gender') <p class="field-error">{{ $message }}</p> @enderror
                    </fieldset>
                    <div>
                        <label for="nli" class="label">Neurological level (C1–S5) <span class="req">*</span></label>
                        <select id="nli" name="nli" class="input" required>
                            <option value="">Select…</option>
                            @foreach ($levels as $l)<option value="{{ $l->value }}" @selected(old('nli') === $l->value)>{{ $l->value }}</option>@endforeach
                        </select>
                        @error('nli') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ais" class="label">AIS grade <span class="req">*</span></label>
                        <select id="ais" name="ais" class="input" required>
                            <option value="">Select…</option>
                            @foreach ($aisGrades as $a)<option value="{{ $a->value }}" @selected(old('ais') === $a->value)>{{ $a->value }}</option>@endforeach
                        </select>
                        @error('ais') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ams" class="label">AMS — motor score (0–100) <span class="req">*</span></label>
                        <input id="ams" name="ams" type="number" min="0" max="100" class="input" placeholder="0–100" value="{{ old('ams') }}" required>
                        @error('ams') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="assessed_on" class="label">Assessment date <span class="req">*</span></label>
                        <input id="assessed_on" name="assessed_on" type="date" class="input" max="{{ now()->toDateString() }}" value="{{ old('assessed_on', now()->toDateString()) }}" required>
                        @error('assessed_on') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <label for="etiology" class="label">Spinal cord lesion etiology</label>
                    <select id="etiology" name="etiology" class="input">
                        <option value="">Optional — select…</option>
                        @foreach ($etiologies as $e)<option value="{{ $e->value }}" @selected(old('etiology') === $e->value)>{{ $e->label() }}</option>@endforeach
                    </select>
                    @error('etiology') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </section>

            @include('assessments._scim-items')

            <div class="card space-y-4">
                @include('assessments._consent')
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary px-6 py-3 text-[15px]">Calculate SCIM →</button>
                </div>
            </div>
        </form>
    </div>
@endsection
