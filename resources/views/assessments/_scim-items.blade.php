{{-- SCIM questionnaire. Expects: $definition (App\Scoring\ScimDefinition). Answers posted as answers[vN]. --}}
<div class="space-y-5" data-scim-form>
    <div class="flex items-baseline justify-between">
        <h2 class="text-xl font-bold">SCIM Scores</h2>
        <span class="text-sm text-slate-500">{{ count($definition->allowedScores()) }} items · {{ count($definition->sections()) }} domains · 0–100</span>
    </div>

    @foreach ($definition->sections() as $section)
        @php
            $max = collect($section['items'])->sum(fn ($i) => max(array_column($i['options'], 'score')));
            $groups = collect($section['items'])->groupBy(fn ($i) => $i['group'] ?? '_'.$i['key']);
        @endphp
        <section class="card" data-scim-section>
            <div class="flex items-center justify-between border-b border-line pb-3">
                <h3 class="section-title">{{ $section['title'] }}</h3>
                <span class="text-sm text-slate-500"><strong class="text-ink tabular-nums" data-section-sum>0</strong> / {{ $max }}</span>
            </div>

            <div class="mt-4 space-y-6">
                @foreach ($groups as $groupKey => $items)
                    @php $grouped = ! str_starts_with($groupKey, '_'); @endphp
                    <div @class(['rounded-2xl border border-line bg-slate-50/70 p-4 sm:p-5' => $grouped])>
                        @if ($grouped)
                            <p class="mb-3 font-semibold text-brand">{{ rtrim($items->first()['number'], 'AB') }}: {{ $groupKey }}</p>
                        @endif
                        <div class="space-y-5">
                            @foreach ($items as $item)
                                @php $field = 'answers.'.$item['key']; @endphp
                                <fieldset @error($field) aria-invalid="true" @enderror>
                                    <legend class="mb-2 text-[15px] font-semibold text-ink">
                                        {{ $grouped ? substr($item['number'], -1).': '.lcfirst($item['title']) : $item['number'].': '.$item['title'] }}: <span class="req">*</span>
                                    </legend>
                                    <div class="space-y-1">
                                        @foreach ($item['options'] as $opt)
                                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-transparent px-3 py-2 text-[15px] text-slate-700 hover:bg-slate-50 has-[:checked]:border-blue-200 has-[:checked]:bg-blue-50">
                                                <input type="radio" class="mt-1 size-4 accent-brand" name="answers[{{ $item['key'] }}]" value="{{ $opt['score'] }}"
                                                       @checked((string) old($field) === (string) $opt['score']) required>
                                                <span><span class="mr-1 font-mono font-semibold text-ink">{{ $opt['score'] }}:</span>{{ $opt['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error($field) <p class="field-error">{{ $message }}</p> @enderror
                                </fieldset>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
