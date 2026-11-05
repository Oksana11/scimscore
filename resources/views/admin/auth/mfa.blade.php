@extends('admin.layout')
@section('title', 'Authenticator')
@section('content')
    <div class="card mx-auto max-w-md">
        <h1 class="text-xl font-semibold">{{ $enrolling ? 'Set up your authenticator app' : 'Authenticator code' }}</h1>
        @if ($enrolling)
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-700">
                <li>Open an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password…).</li>
                <li>Add an account manually with this key (time-based, 6 digits):
                    <code class="mt-1 block rounded bg-slate-100 px-2 py-1 font-mono text-xs break-all select-all">{{ $secret }}</code>
                </li>
                <li>Enter the 6-digit code the app shows.</li>
            </ol>
            <p class="mt-2 text-xs text-slate-500 break-all">Setup URI: {{ $uri }}</p>
        @else
            <p class="mt-1 text-sm text-slate-600">Step 2 of 2 — enter the 6-digit code from your authenticator app.</p>
        @endif
        <form method="POST" action="{{ route('admin.login.mfa.verify') }}" class="mt-6 space-y-4" novalidate>
            @csrf
            <input name="code" class="input text-center font-mono text-lg tracking-[0.5em]" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus aria-label="Authenticator code">
            @error('code') <p class="field-error">{{ $message }}</p> @enderror
            <button class="btn-primary w-full">{{ $enrolling ? 'Confirm & sign in' : 'Sign in' }}</button>
        </form>
    </div>
@endsection
