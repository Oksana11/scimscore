@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="mx-auto max-w-[520px]">
        <a href="{{ route('home') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Back</a>
        <div class="card mt-3 p-9">
            <h1 class="text-2xl font-bold">Sign in or create an account</h1>
            <p class="mt-2 text-[15px] text-slate-500">Enter your email and we'll send you a 6-digit code. New here? An account is created automatically.</p>

            <form method="POST" action="{{ route('login.send') }}" class="mt-7 space-y-5" novalidate>
                @csrf
                <div>
                    <label for="email" class="label">Email address</label>
                    <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" placeholder="you@clinic.org"
                           autocomplete="email" inputmode="email" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p id="email-error" class="field-error">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-start gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="accept_terms" value="1" class="mt-0.5 size-4 accent-brand" @checked(old('accept_terms')) required>
                    <span>I confirm that the data entered into the system will be stored anonymously and used for research, statistics and system
                        improvement. I confirm that no identifying details of the patient will be entered or stored.
                        <a href="{{ route('terms') }}" class="btn-link" target="_blank" rel="noopener">Terms of Use</a></span>
                </label>
                @error('accept_terms') <p class="field-error">You must accept the terms to continue.</p> @enderror

                <button type="submit" class="btn-primary w-full py-3 text-[15px]">Send code →</button>
            </form>
            <p class="mt-5 text-center text-xs text-slate-500">No passwords are stored. We verify your email with a one-time code. Patient data is kept anonymous.</p>
        </div>
    </div>
@endsection
