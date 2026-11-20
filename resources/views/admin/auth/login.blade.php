@extends('admin.layout')
@section('title', 'Sign in')
@section('content')
    <div class="card mx-auto max-w-md">
        <h1 class="text-xl font-semibold">Administrator sign-in</h1>
        <p class="mt-1 text-sm text-slate-600">Step 1 of 2 — a one-time code is sent to your administrator email.</p>
        <form method="POST" action="{{ route('admin.login.send') }}" class="mt-6 space-y-4" novalidate>
            @csrf
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" class="input" autocomplete="username" required autofocus value="{{ old('email') }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button class="btn-primary w-full">Send code</button>
        </form>
    </div>
@endsection
