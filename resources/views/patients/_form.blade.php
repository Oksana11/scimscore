{{-- Patient form. Expects: $genders, $levels, $aisGrades, optional $patient, $isCreate. --}}
@php
    $v = fn ($key, $default = null) => old($key, $patient?->{$key} instanceof \BackedEnum ? $patient->{$key}->value : ($patient?->{$key} ?? $default));
@endphp

<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    No names, ID numbers, phone numbers, addresses or emails. Use only your own research code and an internal alias.
</div>

<div class="mt-5">
    <h2 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Identification · required</h2>
    <div class="mt-3 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="research_id" class="label">Patient Research ID *</label>
            <input id="research_id" name="research_id" class="input" maxlength="50" value="{{ $v('research_id') }}" placeholder="e.g. JD1985" required autocomplete="off">
            @error('research_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="alias" class="label">Name / internal alias *</label>
            <input id="alias" name="alias" class="input" maxlength="50" value="{{ $v('alias') }}" placeholder="e.g. J.D." required autocomplete="off">
            @error('alias') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="gender" class="label">Gender *</label>
            <select id="gender" name="gender" class="input" required>
                <option value="">Select…</option>
                @foreach ($genders as $g)
                    <option value="{{ $g->value }}" @selected((string) $v('gender') === (string) $g->value)>{{ $g->label() }}</option>
                @endforeach
            </select>
            @error('gender') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="birth_year" class="label">Year of birth *</label>
            <input id="birth_year" name="birth_year" type="number" min="1900" max="{{ now()->year }}" class="input" value="{{ $v('birth_year') }}" placeholder="e.g. 1985" required>
            @error('birth_year') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

@if ($isCreate)
    <div class="mt-6">
        <h2 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">At the time of this assessment</h2>
        <div class="mt-3 grid gap-5 sm:grid-cols-3">
            <div>
                <label for="nli" class="label">Neurological level *</label>
                <select id="nli" name="nli" class="input" required>
                    <option value="">Select…</option>
                    @foreach ($levels as $l)<option value="{{ $l->value }}" @selected(old('nli') === $l->value)>{{ $l->value }}</option>@endforeach
                </select>
                @error('nli') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ais" class="label">AIS grade *</label>
                <select id="ais" name="ais" class="input" required>
                    <option value="">Select…</option>
                    @foreach ($aisGrades as $a)<option value="{{ $a->value }}" @selected(old('ais') === $a->value)>{{ $a->value }}</option>@endforeach
                </select>
                @error('ais') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ams" class="label">AMS (0–100)</label>
                <input id="ams" name="ams" type="number" min="0" max="100" class="input" value="{{ old('ams') }}">
                @error('ams') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
@endif

<div class="mt-6">
    <h2 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">At the first SCIM assessment · optional</h2>
    <p class="mt-1 text-xs text-slate-500">Helps identify patients who may appear under more than one identifier.</p>
    <div class="mt-3 grid gap-5 sm:grid-cols-3">
        <div>
            <label for="first_nli" class="label">Neurological level</label>
            <select id="first_nli" name="first_nli" class="input">
                <option value="">—</option>
                @foreach ($levels as $l)<option value="{{ $l->value }}" @selected($v('first_nli') === $l->value)>{{ $l->value }}</option>@endforeach
            </select>
            @error('first_nli') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="first_ais" class="label">AIS grade</label>
            <select id="first_ais" name="first_ais" class="input">
                <option value="">—</option>
                @foreach ($aisGrades as $a)<option value="{{ $a->value }}" @selected($v('first_ais') === $a->value)>{{ $a->value }}</option>@endforeach
            </select>
            @error('first_ais') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="first_ams" class="label">AMS (0–100)</label>
            <input id="first_ams" name="first_ams" type="number" min="0" max="100" class="input" value="{{ $v('first_ams') }}">
            @error('first_ams') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
