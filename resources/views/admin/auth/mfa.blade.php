@extends('admin.layout')
@section('title', 'Authenticator')
@section('content')
    <div class="card mx-auto max-w-md">
        <h1 class="text-xl font-semibold">{{ $enrolling ? 'Set up your authenticator app' : 'Authenticator code' }}</h1>
        @if ($enrolling)
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-700">
                <li>Open an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password…).</li>
                <li>Scan this QR code:
                    <canvas data-totp-qr="{{ $uri }}" class="mt-2 block rounded-lg border border-line" aria-label="QR code for the authenticator app"></canvas>
                    <details class="mt-2 text-xs text-slate-500">
                        <summary class="cursor-pointer">Can't scan? Enter the key manually</summary>
                        <code class="mt-1 block rounded bg-slate-100 px-2 py-1 font-mono text-xs break-all select-all">{{ $secret }}</code>
                        <span class="mt-1 block">Time-based, 6 digits.</span>
                    </details>
                </li>
                <li>Enter the 6-digit code the app shows.</li>
            </ol>
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
