@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card">
            <h1 class="text-xl font-semibold">Sign in or create an account</h1>
            <p class="mt-1 text-sm text-slate-600">
                Enter your email and we'll send you a one-time code. New emails get an account automatically. No password.
            </p>

            <form method="POST" action="{{ route('login.send') }}" class="mt-6 space-y-4" novalidate>
                @csrf
                <div>
                    <label for="email" class="label">Email address</label>
                    <input id="email" name="email" type="email" class="input" value="{{ old('email') }}"
                           autocomplete="email" inputmode="email" required autofocus
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p id="email-error" class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="accept_terms" value="1" class="mt-0.5" @checked(old('accept_terms')) required>
                        <span>
                            I confirm that the data entered into the system will be stored anonymously and used for research,
                            statistics and system improvement. I confirm that no identifying details of the patient will be
                            entered or stored. <a href="{{ route('terms') }}" class="btn-link" target="_blank" rel="noopener">Terms of Use</a>
                        </span>
                    </label>
                    @error('accept_terms') <p class="field-error">You must accept the terms to continue.</p> @enderror
                </div>

                <button type="submit" class="btn-primary w-full">Send code</button>
            </form>
        </div>
    </div>
@endsection
