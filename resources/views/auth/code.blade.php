@extends('layouts.app')

@section('title', 'Enter code')

@section('content')
    <div class="mx-auto max-w-[520px]">
        <a href="{{ route('login') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Back</a>
        <div class="card mt-3 p-9">
            <h1 class="text-2xl font-bold">Enter your code</h1>
            <p class="mt-2 text-[15px] text-slate-500">
                If <strong class="text-ink">{{ $email }}</strong> can receive email, a 6-digit code has been sent to it.
                It is valid for {{ config('scim.otp.ttl_minutes') }} minutes and can be used once.
            </p>

            <form method="POST" action="{{ route('login.verify') }}" class="mt-7 space-y-5" novalidate>
                @csrf
                <div>
                    <label for="code" class="label">One-time code</label>
                    <input id="code" name="code" type="text" class="input py-3 text-center font-mono text-2xl tracking-[0.6em]"
                           inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="······" required autofocus
                           @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
                    @error('code') <p id="code-error" class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary w-full py-3 text-[15px]">Verify &amp; continue →</button>
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
