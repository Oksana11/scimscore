<label class="flex items-start gap-2.5 text-sm text-slate-600">
    <input type="checkbox" name="accept_terms" value="1" class="mt-0.5 size-4 accent-brand" @checked(old('accept_terms')) required>
    <span>I confirm that the data entered into the system will be stored anonymously and used for research, statistics and system
        improvement. I confirm that no identifying details of the patient will be entered or stored.
        <a href="{{ route('terms') }}" class="btn-link" target="_blank" rel="noopener">Terms of Use</a></span>
</label>
@error('accept_terms') <p class="field-error">{{ $message }}</p> @enderror
