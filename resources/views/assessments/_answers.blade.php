{{-- Item answers with their labels. Expects: $assessment, $definition. --}}
<div class="space-y-6">
    @foreach ($definition->sections() as $section)
        <div>
            <h3 class="text-sm font-semibold">{{ $section['title'] }}</h3>
            <table class="mt-2 w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @foreach ($section['items'] as $item)
                        @php
                            $score = $assessment->answers[$item['key']] ?? null;
                            $label = collect($item['options'])->firstWhere('score', $score)['label'] ?? '—';
                        @endphp
                        <tr>
                            <td class="w-1/3 py-2 pr-4 align-top text-slate-600">{{ $item['number'] }}. {{ isset($item['group']) ? \Illuminate\Support\Str::before($item['group'], ' (').' — ' : '' }}{{ $item['title'] }}</td>
                            <td class="w-10 py-2 pr-3 text-right align-top font-semibold tabular-nums">{{ $score }}</td>
                            <td class="py-2 align-top">{{ $label }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
