{{-- Caregiver details: used by the profile and by the Quick Assessment step 1.
     Expects: $professions, $countries, $values (array: profession_id, country_code, institution, institution_display, professional_title, organization, department). --}}
@php $val = fn ($k) => old($k, $values[$k] ?? null); @endphp
<h2 class="section-title">Required</h2>
<div class="mt-4 grid gap-5 sm:grid-cols-2">
    <div>
        <label for="profession_id" class="label">Caregiver profession <span class="req">*</span></label>
        <select id="profession_id" name="profession_id" class="input" required>
            <option value="">Select…</option>
            @foreach ($professions as $p)
                <option value="{{ $p->id }}" @selected((string) $val('profession_id') === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        @error('profession_id') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="country_code" class="label">Country <span class="req">*</span></label>
        <select id="country_code" name="country_code" class="input" required>
            <option value="">Select from list…</option>
            @foreach ($countries as $c)
                <option value="{{ $c->code }}" @selected($val('country_code') === $c->code)>{{ $c->name }}</option>
            @endforeach
        </select>
        @error('country_code') <p class="field-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-5" data-institution-picker data-search-url="{{ route('institutions.search') }}">
    <label for="institution_search" class="label">Hospital / Institution <span class="req">*</span></label>
    <input type="hidden" name="institution" value="{{ $val('institution') }}" data-institution-ref>
    <input type="hidden" name="institution_display" value="{{ $val('institution_display') }}" data-institution-display>
    <input id="institution_search" type="text" class="input" autocomplete="off" value="{{ $val('institution_display') }}"
           placeholder="Start typing — searches the Research Organization Registry (ROR)" role="combobox" aria-expanded="false" aria-controls="institution_list" data-institution-search>
    <ul id="institution_list" role="listbox" class="mt-1 hidden max-h-72 overflow-auto rounded-xl border border-line bg-white text-sm shadow-lg" data-institution-list></ul>
    <p class="hint">We store the institution's ROR ID so spelling variations can't create duplicates. Not listed? Choose “Add new institution”.</p>
    <div class="mt-3 {{ old('institution') === 'custom' ? '' : 'hidden' }}" data-institution-custom>
        <label for="institution_custom_name" class="label">New institution name <span class="req">*</span></label>
        <input id="institution_custom_name" name="institution_custom_name" type="text" class="input" value="{{ old('institution_custom_name') }}" maxlength="255">
        @error('institution_custom_name') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    @error('institution') <p class="field-error">{{ $message }}</p> @enderror
</div>

<div class="my-6 border-t border-line"></div>

<h2 class="section-title">Optional</h2>
<div class="mt-4 grid gap-5 sm:grid-cols-3">
    <div>
        <label for="professional_title" class="label">Professional title</label>
        <input id="professional_title" name="professional_title" type="text" class="input" maxlength="100" placeholder="e.g. Senior physician" value="{{ $val('professional_title') }}">
    </div>
    <div>
        <label for="organization" class="label">Organization</label>
        <input id="organization" name="organization" type="text" class="input" maxlength="255" placeholder="e.g. Research network" value="{{ $val('organization') }}">
    </div>
    <div>
        <label for="department" class="label">Department</label>
        <input id="department" name="department" type="text" class="input" maxlength="255" placeholder="e.g. Spinal rehab" value="{{ $val('department') }}">
    </div>
</div>
