{{-- SCIM questionnaire. Expects: $definition (App\Scoring\ScimDefinition). Answers posted as answers[vN]. --}}
<div class="space-y-4" data-scim-form>
    @foreach ($definition->sections() as $section)
        @php $max = collect($section['items'])->sum(fn ($i) => max(array_column($i['options'], 'score'))); @endphp
        <details class="card p-0" open data-scim-section>
            <summary class="flex cursor-pointer items-center justify-between gap-4 px-6 py-4">
                <span class="font-semibold">{{ $section['title'] }}</span>
                <span class="text-sm text-slate-600"><span data-section-sum>0</span> / {{ $max }}</span>
            </summary>
            <div class="space-y-6 border-t border-slate-200 px-6 py-5">
                @php $lastGroup = null; @endphp
                @foreach ($section['items'] as $item)
                    @if (($item['group'] ?? null) && ($item['group'] ?? null) !== $lastGroup)
                        <p class="font-medium text-slate-800">{{ rtrim($item['number'], 'AB') }}. {{ $item['group'] }}</p>
                    @endif
                    @php $lastGroup = $item['group'] ?? null; $field = 'answers.'.$item['key']; @endphp
                    <fieldset class="{{ isset($item['group']) ? 'pl-4' : '' }}" @error($field) aria-invalid="true" @enderror>
                        <legend class="mb-2 text-sm font-medium text-slate-900">
                            {{ $item['number'] }}. {{ $item['title'] }}
                        </legend>
                        <div class="space-y-1.5">
                            @foreach ($item['options'] as $opt)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50 has-[:checked]:bg-blue-50">
                                    <input type="radio" class="mt-0.5" name="answers[{{ $item['key'] }}]" value="{{ $opt['score'] }}"
                                           @checked((string) old($field) === (string) $opt['score']) required>
                                    <span><span class="inline-block w-6 font-semibold tabular-nums">{{ $opt['score'] }}</span>{{ $opt['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error($field) <p class="field-error">{{ $message }}</p> @enderror
                    </fieldset>
                @endforeach
            </div>
        </details>
    @endforeach

    <div class="card flex items-center justify-between">
        <span class="font-semibold">SCIM total</span>
        <span class="text-lg font-semibold tabular-nums"><span data-scim-total>0</span> / 100</span>
    </div>
</div>
