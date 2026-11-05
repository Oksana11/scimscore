@extends('layouts.app')

@section('title', 'Quick Assessment')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="text-2xl font-semibold">Quick SCIM Assessment</h1>
        <p class="mt-1 text-sm text-slate-600">One-time calculation without an account. Nothing is linked to a patient.</p>

        @if (count($versions) > 1)
            <nav class="mt-4 inline-flex rounded-lg border border-slate-300 bg-white p-1 text-sm" aria-label="SCIM version">
                @foreach ($versions as $v)
                    <a href="{{ route('quick.create', ['version' => $v->value]) }}"
                       class="rounded-md px-3 py-1 {{ $v === $version ? 'bg-slate-900 text-white' : 'text-slate-700' }}">SCIM {{ $v->value }}</a>
                @endforeach
            </nav>
        @endif

        @if ($errors->any())
            <div class="alert-error mt-6" role="alert">Please check the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('quick.store') }}" class="mt-6 space-y-6" novalidate>
            @csrf
            <input type="hidden" name="scim_version" value="{{ $version->value }}">

            <div class="card">
                <h2 class="font-semibold">Patient data</h2>
                <p class="mt-1 mb-4 text-xs text-slate-500">Do not enter names, ID numbers, phone numbers, addresses or emails.</p>
                @include('assessments._patient-fields')
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
                <button type="submit" class="btn-primary">Calculate</button>
            </div>
        </form>
    </div>
@endsection
