@extends('layouts.app')

@section('title', 'Enter code')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card">
            <h1 class="text-xl font-semibold">Enter your code</h1>
            <p class="mt-1 text-sm text-slate-600">
                If <strong>{{ $email }}</strong> can receive email, a 6-digit code has been sent to it.
                The code is valid for {{ config('scim.otp.ttl_minutes') }} minutes and can be used once.
            </p>

            <form method="POST" action="{{ route('login.verify') }}" class="mt-6 space-y-4" novalidate>
                @csrf
                <div>
                    <label for="code" class="label">One-time code</label>
                    <input id="code" name="code" type="text" class="input text-center font-mono text-lg tracking-[0.5em]"
                           inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus
                           @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
                    @error('code') <p id="code-error" class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary w-full">Verify &amp; continue</button>
            </form>

            <div class="mt-6 flex items-center justify-between text-sm">
                <form method="POST" action="{{ route('login.resend') }}">
                    @csrf
                    <button type="submit" class="btn-link" data-resend data-wait="{{ $resendIn }}" @disabled($resendIn > 0)>
                        Resend code<span data-resend-label>@if ($resendIn > 0) in {{ $resendIn }} s @endif</span>
                    </button>
                </form>
                <a href="{{ route('login') }}" class="btn-link">Change email</a>
            </div>
        </div>
    </div>
@endsection
