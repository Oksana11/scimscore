@extends('layouts.app')

@section('title', $firstTime ? 'Complete your profile' : 'Profile')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="card">
            <h1 class="text-xl font-semibold">{{ $firstTime ? 'Complete your profile' : 'Profile' }}</h1>
            @if ($firstTime)
                <p class="mt-1 text-sm text-slate-600">A few details about you. They are used for statistics only and are never shown to other users.</p>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-5" novalidate>
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="profession_id" class="label">Caregiver profession *</label>
                        <select id="profession_id" name="profession_id" class="input" required>
                            <option value="">Select…</option>
                            @foreach ($professions as $p)
                                <option value="{{ $p->id }}" @selected(old('profession_id', $profile?->profession_id) == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('profession_id') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="country_code" class="label">Country *</label>
                        <select id="country_code" name="country_code" class="input" required>
                            <option value="">Select…</option>
                            @foreach ($countries as $c)
                                <option value="{{ $c->code }}" @selected(old('country_code', $profile?->country_code) === $c->code)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('country_code') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Institution: autocomplete over our database + ROR (resources/js/institution-picker.js) --}}
                @php
                    $instRef = old('institution', $profile ? 'local:'.$profile->institution_id : '');
                    $instName = old('institution_display', $profile?->institution?->name ?? '');
                @endphp
                <div data-institution-picker data-search-url="{{ route('institutions.search') }}">
                    <label for="institution_search" class="label">Hospital / Institution *</label>
                    <input type="hidden" name="institution" value="{{ $instRef }}" data-institution-ref>
                    <input type="hidden" name="institution_display" value="{{ $instName }}" data-institution-display>
                    <input id="institution_search" type="text" class="input" autocomplete="off" value="{{ $instName }}"
                           placeholder="Start typing the name…" role="combobox" aria-expanded="false" aria-controls="institution_list"
                           data-institution-search>
                    <ul id="institution_list" role="listbox" class="mt-1 hidden max-h-72 overflow-auto rounded-lg border border-slate-200 bg-white text-sm shadow-lg" data-institution-list></ul>
                    <p class="mt-1 text-xs text-slate-500">Institutions come from the Research Organization Registry (ROR). Not listed? Choose “Add new institution”.</p>

                    <div class="mt-3 {{ old('institution') === 'custom' ? '' : 'hidden' }}" data-institution-custom>
                        <label for="institution_custom_name" class="label">New institution name *</label>
                        <input id="institution_custom_name" name="institution_custom_name" type="text" class="input" value="{{ old('institution_custom_name') }}" maxlength="255">
                        @error('institution_custom_name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    @error('institution') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="professional_title" class="label">Professional title</label>
                        <input id="professional_title" name="professional_title" type="text" class="input" maxlength="100" value="{{ old('professional_title', $profile?->professional_title) }}">
                    </div>
                    <div>
                        <label for="organization" class="label">Organization</label>
                        <input id="organization" name="organization" type="text" class="input" maxlength="255" value="{{ old('organization', $profile?->organization) }}">
                    </div>
                    <div>
                        <label for="department" class="label">Department</label>
                        <input id="department" name="department" type="text" class="input" maxlength="255" value="{{ old('department', $profile?->department) }}">
                    </div>
                </div>

                <button type="submit" class="btn-primary">{{ $firstTime ? 'Save & continue' : 'Save profile' }}</button>
            </form>
        </div>

        @unless ($firstTime)
            <div class="card">
                <h2 class="text-lg font-semibold">Sign-in email</h2>
                <p class="mt-1 text-sm text-slate-600">Current: <strong>{{ $user->email }}</strong>. Your patients and assessments stay with your account.</p>

                @if ($pendingEmail)
                    <form method="POST" action="{{ route('profile.email.confirm') }}" class="mt-4 space-y-3" novalidate>
                        @csrf
                        <label for="code" class="label">Code sent to {{ $pendingEmail->new_email }}</label>
                        <div class="flex gap-3">
                            <input id="code" name="code" type="text" class="input max-w-40 font-mono tracking-widest" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required>
                            <button type="submit" class="btn-primary">Confirm</button>
                        </div>
                        @error('code') <p class="field-error">{{ $message }}</p> @enderror
                    </form>
                @endif

                <form method="POST" action="{{ route('profile.email.store') }}" class="mt-4 space-y-3" novalidate>
                    @csrf
                    <label for="new_email" class="label">New email address</label>
                    <div class="flex gap-3">
                        <input id="new_email" name="new_email" type="email" class="input" autocomplete="email" value="{{ old('new_email') }}" required>
                        <button type="submit" class="btn-secondary whitespace-nowrap">Send code</button>
                    </div>
                    @error('new_email') <p class="field-error">{{ $message }}</p> @enderror
                </form>
            </div>
        @endunless
    </div>
@endsection
