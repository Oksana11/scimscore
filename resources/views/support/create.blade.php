@extends('layouts.app')

@section('title', 'Contact support')

@section('content')
    <div class="card mx-auto max-w-2xl p-8">
        <h1 class="text-2xl font-bold">Contact support</h1>
        <p class="mt-1 text-sm text-slate-500">We reply by email. Please don't include any identifying details of patients.</p>

        <form method="POST" action="{{ route('support.store') }}" class="mt-6 space-y-4">
            @csrf
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div>
                <label for="email" class="label">Your email <span class="text-red-600">*</span></label>
                <input id="email" name="email" type="email" class="input" maxlength="254" required autocomplete="email" value="{{ old('email', $email) }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="subject" class="label">Subject <span class="text-red-600">*</span></label>
                <input id="subject" name="subject" class="input" maxlength="150" required value="{{ old('subject') }}">
                @error('subject') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="message" class="label">Message <span class="text-red-600">*</span></label>
                <textarea id="message" name="message" rows="7" class="input" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea>
                @error('message') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary">Send message</button>
        </form>
    </div>
@endsection
