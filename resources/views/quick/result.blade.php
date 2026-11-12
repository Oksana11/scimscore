@extends('layouts.app')

@section('title', 'Result')

@section('content')
    <div class="mx-auto max-w-[1040px] space-y-5">
        <div class="print:hidden">
            <a href="{{ route('quick.assessment') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> New quick assessment</a>
            <div class="mt-4">@include('quick._steps', ['step' => 3])</div>
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div id="print-summary">
                <h1 class="page-title">SCIM {{ $assessment->scim_version->value }} result</h1>
                <p class="page-sub">Quick assessment · <span class="font-mono">{{ $assessment->patient_code }}</span> · {{ $assessment->assessed_on->format('j M Y') }}</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <button type="button" class="btn-secondary" data-print="#print-summary, #print-results"><x-icon name="print" class="size-4" /> Print</button>
                <a href="{{ route('quick.pdf', $assessment) }}" class="btn-secondary text-orange-700"><x-icon name="file" class="size-4" /> PDF</a>
                <a href="{{ route('quick.xlsx', $assessment) }}" class="btn-secondary text-green-700"><x-icon name="sheet" class="size-4" /> Excel</a>
            </div>
        </div>

        <div id="print-results" class="space-y-5">
            @include('assessments._result')
            @include('assessments._answers')

            <p class="text-sm text-slate-500">
            Caregiver: {{ $assessment->caregiverProfession?->name }} · {{ $assessment->caregiverCountry?->name }} · {{ $assessment->caregiverInstitution?->name }}
            · Terms of Use accepted {{ $assessment->consent->accepted_at->format('j M Y, H:i') }} UTC
            </p>
        </div>

        <section class="card print:hidden">
            <h2 class="section-title flex items-center gap-2"><x-icon name="mail" class="size-5 text-slate-400" /> Send the result by email</h2>
            <p class="mt-1 text-sm text-slate-500">We'll verify the address with a one-time code first. Tip: keep these emails in a dedicated folder to find patient codes for follow-up assessments.</p>

            @if ($emailStep === 'email')
                <form method="POST" action="{{ route('quick.email.code', $assessment) }}" class="mt-4 flex max-w-lg gap-3" novalidate>
                    @csrf
                    <input name="email" type="email" class="input" autocomplete="email" placeholder="you@clinic.org" value="{{ old('email') }}" required aria-label="Email address">
                    <button type="submit" class="btn-secondary whitespace-nowrap">Send code</button>
                </form>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            @else
                <form method="POST" action="{{ route('quick.email.send', $assessment) }}" class="mt-4 flex max-w-lg gap-3" novalidate>
                    @csrf
                    <input name="code" type="text" class="input max-w-44 font-mono tracking-widest" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required aria-label="Code sent to {{ $emailTo }}">
                    <button type="submit" class="btn-primary whitespace-nowrap">Send result</button>
                </form>
                @error('code') <p class="field-error">{{ $message }}</p> @enderror
                <form method="POST" action="{{ route('quick.email.change', $assessment) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn-link">Use another email</button>
                </form>
            @endif
        </section>

        @guest
            <div class="flex justify-center print:hidden">
                <a href="{{ route('login') }}" class="btn-link">Create an account to save assessments to anonymous patients →</a>
            </div>
        @endguest
    </div>
@endsection
