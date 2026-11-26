@extends('layouts.account')

@section('title', 'Profile')
@section('breadcrumb', 'Profile')

@section('content')
    <div class="mx-auto max-w-4xl">
        <h1 class="page-title">Profile</h1>
        <p class="page-sub">Your professional details and sign-in email.</p>

        <form method="POST" action="{{ route('profile.update') }}" class="card mt-6" novalidate>
            @csrf
            @method('PUT')
            @include('profile._fields')
            <div class="mt-6 flex justify-end border-t border-line pt-5">
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>

        <div class="card mt-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="section-title">Sign-in email</h2>
                    <p class="mt-1 text-[15px] text-slate-600">{{ $user->email }}</p>
                </div>
                @unless ($pendingEmail)
                    <a href="#change-email" class="btn-secondary">Change email</a>
                @endunless
            </div>

            @if ($pendingEmail)
                <form method="POST" action="{{ route('profile.email.confirm') }}" class="mt-5 border-t border-line pt-5" novalidate>
                    @csrf
                    <label for="code" class="label">Code sent to {{ $pendingEmail->new_email }}</label>
                    <div class="flex gap-3">
                        <input id="code" name="code" type="text" class="input max-w-44 font-mono tracking-widest" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required>
                        <button type="submit" class="btn-primary">Confirm</button>
                    </div>
                    @error('code') <p class="field-error">{{ $message }}</p> @enderror
                    <p class="hint">Your patients and assessments stay with your account. A notice is sent to your current address.</p>
                </form>
            @endif

            <form id="change-email" method="POST" action="{{ route('profile.email.store') }}"
                  class="mt-5 border-t border-line pt-5 {{ $errors->has('new_email') || $pendingEmail ? '' : 'hidden target:block' }}" novalidate>
                @csrf
                <label for="new_email" class="label">{{ $pendingEmail ? 'Send the code to another address' : 'New email address' }}</label>
                <div class="flex gap-3">
                    <input id="new_email" name="new_email" type="email" class="input" autocomplete="email" value="{{ old('new_email') }}" required>
                    <button type="submit" class="btn-secondary whitespace-nowrap">Send code</button>
                </div>
                @error('new_email') <p class="field-error">{{ $message }}</p> @enderror
            </form>
        </div>

        <div class="card mt-5 border-red-200">
            <h2 class="text-lg font-semibold text-red-800">Delete account</h2>
            <p class="mt-1 text-sm text-slate-600">Your email and profile are erased and you are signed out. Your patients and assessments stay in the research database <strong>anonymously</strong>: Research IDs and aliases are wiped, so nobody (including you) can link them to a person again. This cannot be undone.</p>
            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf @method('DELETE')
                <div>
                    <label for="confirm-account" class="label">Type DELETE to confirm</label>
                    <input id="confirm-account" name="confirm" class="input" autocomplete="off" required pattern="DELETE">
                    @error('confirm') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-secondary text-red-700">Delete my account</button>
            </form>
        </div>
    </div>
@endsection
