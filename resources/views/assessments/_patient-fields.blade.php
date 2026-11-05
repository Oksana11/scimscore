{{-- Patient data at the time of assessment. Expects: $genders, $levels, $aisGrades. --}}
<div class="grid gap-5 sm:grid-cols-3">
    <div>
        <label for="gender" class="label">Gender *</label>
        <select id="gender" name="gender" class="input" required>
            <option value="">Select…</option>
            @foreach ($genders as $g)
                <option value="{{ $g->value }}" @selected((string) old('gender') === (string) $g->value)>{{ $g->label() }}</option>
            @endforeach
        </select>
        @error('gender') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="age" class="label">Age (years) *</label>
        <input id="age" name="age" type="number" min="0" max="120" class="input" value="{{ old('age') }}" required>
        @error('age') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="ams" class="label">AMS (0–100)</label>
        <input id="ams" name="ams" type="number" min="0" max="100" class="input" value="{{ old('ams') }}">
        <p class="mt-1 text-xs text-slate-500">Needed for SCI-ARMI.</p>
        @error('ams') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="nli" class="label">Neurological level *</label>
        <select id="nli" name="nli" class="input" required>
            <option value="">Select…</option>
            @foreach ($levels as $l)
                <option value="{{ $l->value }}" @selected(old('nli') === $l->value)>{{ $l->value }}</option>
            @endforeach
        </select>
        @error('nli') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="ais" class="label">AIS grade *</label>
        <select id="ais" name="ais" class="input" required>
            <option value="">Select…</option>
            @foreach ($aisGrades as $a)
                <option value="{{ $a->value }}" @selected(old('ais') === $a->value)>{{ $a->value }}</option>
            @endforeach
        </select>
        @error('ais') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="assessed_on" class="label">Assessment date *</label>
        <input id="assessed_on" name="assessed_on" type="date" class="input" max="{{ now()->toDateString() }}"
               value="{{ old('assessed_on', now()->toDateString()) }}" required>
        @error('assessed_on') <p class="field-error">{{ $message }}</p> @enderror
    </div>
</div>
