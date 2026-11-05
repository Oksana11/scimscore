@extends('layouts.app')

@section('title', 'Compare assessments')

@section('content')
    @php
        $rows = [
            ['Self-Care', 'self_care', 20],
            ['Respiration and Sphincter Management', 'respiration_sphincter', 40],
            ['Mobility', 'mobility', 40],
            ['SCIM total', 'total', 100],
        ];
        $fmt = fn ($d) => $d === null ? '—' : (($d > 0 ? '+' : '').$d);
        $cls = fn ($d) => $d === null || $d == 0 ? 'text-slate-500' : ($d > 0 ? 'text-green-700' : 'text-red-700');
        $sameVersion = $a->scim_version === $b->scim_version;
        $defA = $definitions[$a->scim_version->value];
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <a href="{{ route('patients.show', $patient) }}" class="btn-link print:hidden">‹ {{ $patient->system_id }}</a>
        <div class="flex items-start justify-between gap-4">
            <h1 class="text-2xl font-semibold">Compare assessments</h1>
            <button type="button" class="btn-secondary print:hidden" data-print>Print</button>
        </div>
        <p class="text-sm text-slate-600">{{ $patient->system_id }} · Research ID <strong>{{ $patient->research_id }}</strong> · {{ $patient->alias }}</p>

        <div class="card overflow-x-auto p-0">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium"></th>
                        <th class="px-4 py-3 text-right font-medium">A · {{ $a->assessed_on->format('d M Y') }}<br><span class="font-normal">SCIM {{ $a->scim_version->value }}</span></th>
                        <th class="px-4 py-3 text-right font-medium">B · {{ $b->assessed_on->format('d M Y') }}<br><span class="font-normal">SCIM {{ $b->scim_version->value }}</span></th>
                        <th class="px-4 py-3 text-right font-medium">Change</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as [$label, $key, $max])
                        @php $d = $b->result->{$key} - $a->result->{$key}; @endphp
                        <tr class="{{ $key === 'total' ? 'font-semibold' : '' }}">
                            <td class="px-4 py-2">{{ $label }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $a->result->{$key} }} / {{ $max }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $b->result->{$key} }} / {{ $max }}</td>
                            <td class="px-4 py-2 text-right tabular-nums {{ $cls($d) }}">{{ $fmt($d) }}</td>
                        </tr>
                    @endforeach
                    @php $da = ($a->result->sciarmi_total !== null && $b->result->sciarmi_total !== null) ? round((float) $b->result->sciarmi_total - (float) $a->result->sciarmi_total, 2) : null; @endphp
                    <tr>
                        <td class="px-4 py-2">SCI-ARMI</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $a->result->sciarmi_total ?? '—' }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $b->result->sciarmi_total ?? '—' }}</td>
                        <td class="px-4 py-2 text-right tabular-nums {{ $cls($da) }}">{{ $fmt($da) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Grade</td>
                        <td class="px-4 py-2 text-right">{{ $a->result->grade ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $b->result->grade ?? '—' }}</td>
                        <td></td>
                    </tr>
                    <tr class="text-slate-600">
                        <td class="px-4 py-2">Level · AIS · AMS</td>
                        <td class="px-4 py-2 text-right">{{ $a->nli?->value }} · {{ $a->ais?->value }} · {{ $a->ams ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $b->nli?->value }} · {{ $b->ais?->value }} · {{ $b->ams ?? '—' }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($sameVersion)
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Item</th>
                            <th class="px-4 py-3 text-right font-medium">A</th>
                            <th class="px-4 py-3 text-right font-medium">B</th>
                            <th class="px-4 py-3 text-right font-medium">Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($defA->sections() as $section)
                            @foreach ($section['items'] as $item)
                                @php $va = $a->answers[$item['key']] ?? null; $vb = $b->answers[$item['key']] ?? null; $d = ($va !== null && $vb !== null) ? $vb - $va : null; @endphp
                                <tr>
                                    <td class="px-4 py-2">{{ $item['number'] }}. {{ isset($item['group']) ? \Illuminate\Support\Str::before($item['group'], ' (').' — ' : '' }}{{ \Illuminate\Support\Str::limit($item['title'], 60) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $va }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $vb }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums {{ $cls($d) }}">{{ $fmt($d) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">Item-by-item comparison is available for assessments of the same SCIM version.</p>
        @endif
    </div>
@endsection
