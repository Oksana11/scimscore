{{-- Item scores grid. Expects: $assessment, $definition. --}}
<section class="card">
    <h2 class="section-title">Item scores</h2>
    <div class="mt-4 grid gap-x-8 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($definition->sections() as $section)
            @foreach ($section['items'] as $item)
                @php
                    $score = $assessment->answers[$item['key']] ?? null;
                    $label = collect($item['options'])->firstWhere('score', $score)['label'] ?? '—';
                    $name = isset($item['group']) ? \Illuminate\Support\Str::before($item['group'], ' (').' — '.mb_strtolower($item['title']) : \Illuminate\Support\Str::before($item['title'], ' (');
                @endphp
                <div class="flex items-start justify-between gap-3 border-b border-line py-2.5 text-[15px]" title="{{ $label }}">
                    <span><span class="mr-1.5 font-mono text-sm text-slate-400">{{ $item['number'] }}</span>{{ $name }}</span>
                    <strong class="tabular-nums">{{ $score }}</strong>
                </div>
            @endforeach
        @endforeach
    </div>
</section>
