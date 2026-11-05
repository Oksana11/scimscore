@extends('layouts.app')

@section('title', 'Assessment result')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="card">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-xl font-semibold">Assessment result</h1>
                <button type="button" class="btn-secondary print:hidden" data-print>Print</button>
            </div>
            <div class="mt-6">
                @include('assessments._result')
            </div>
        </div>

        <div class="card print:hidden">
            <h2 class="font-semibold">Send the result by email</h2>
            <p class="mt-1 text-sm text-slate-600">We'll verify the address with a one-time code first.</p>

            @if ($emailStep === 'email')
                <form method="POST" action="{{ route('quick.email.code', $assessment) }}" class="mt-4 flex gap-3" novalidate>
                    @csrf
                    <input name="email" type="email" class="input" autocomplete="email" placeholder="you@clinic.org" value="{{ old('email') }}" required aria-label="Email address">
                    <button type="submit" class="btn-secondary whitespace-nowrap">Send code</button>
                </form>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            @else
                <form method="POST" action="{{ route('quick.email.send', $assessment) }}" class="mt-4 flex gap-3" novalidate>
                    @csrf
                    <input name="code" type="text" class="input max-w-40 font-mono tracking-widest" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required aria-label="Code sent to {{ $emailTo }}">
                    <button type="submit" class="btn-primary whitespace-nowrap">Send result</button>
                </form>
                @error('code') <p class="field-error">{{ $message }}</p> @enderror
                <form method="POST" action="{{ route('quick.email.change', $assessment) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn-link">Use another email</button>
                </form>
            @endif
        </div>

        <div class="flex gap-3 print:hidden">
            <a href="{{ route('quick.create') }}" class="btn-secondary">New quick assessment</a>
            @guest
                <a href="{{ route('login') }}" class="btn-primary">Create an account to save assessments</a>
            @endguest
        </div>
    </div>
@endsection
