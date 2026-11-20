@extends('admin.layout')
@section('title', 'Email code')
@section('content')
    <div class="card mx-auto max-w-md">
        <h1 class="text-xl font-semibold">Email code</h1>
        <p class="mt-1 text-sm text-slate-600">If {{ $email }} is an administrator address, a code has been sent to it.</p>
        <form method="POST" action="{{ route('admin.login.verify') }}" class="mt-6 space-y-4" novalidate>
            @csrf
            <input name="code" class="input text-center font-mono text-lg tracking-[0.5em]" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus aria-label="Code">
            @error('code') <p class="field-error">{{ $message }}</p> @enderror
            <button class="btn-primary w-full">Continue</button>
        </form>
        <a href="{{ route('admin.login') }}" class="btn-link mt-4 inline-block">Start over</a>
    </div>
@endsection
