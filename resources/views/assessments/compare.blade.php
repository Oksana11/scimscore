@extends('layouts.account')

@section('title', 'Compare assessments')
@section('breadcrumb')<a href="{{ route('patients.index') }}" class="hover:underline">My Patients</a> / Compare @endsection

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
        <a href="{{ route('patients.show', $patient) }}" class="back-link print:hidden"><x-icon name="chevron-left" class="size-4" /> {{ $patient->system_id }}</a>
        <div class="flex items-start justify-between gap-4">
            <h1 class="page-title">Compare assessments</h1>
            <button type="button" class="btn-secondary print:hidden" data-print><x-icon name="print" class="size-4" /> Print</button>
        </div>
        <p class="page-sub">{{ $patient->system_id }} · Research ID <strong>{{ $patient->research_id }}</strong> · {{ $patient->alias }}</p>

        <div class="card overflow-x-auto p-0">
            <table class="w-full text-[15px]">
                <thead class="border-b border-line">
                    <tr>
                        <th class="th"></th>
                        <th class="th text-right">A · {{ $a->assessed_on->format('d M Y') }}<br><span class="font-normal">SCIM {{ $a->scim_version->value }}</span></th>
                        <th class="th text-right">B · {{ $b->assessed_on->format('d M Y') }}<br><span class="font-normal">SCIM {{ $b->scim_version->value }}</span></th>
                        <th class="th text-right">Change</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as [$label, $key, $max])
                        @php $d = $b->result->{$key} - $a->result->{$key}; @endphp
                        <tr class="{{ $key === 'total' ? 'font-semibold' : '' }}">
                            <td class="px-5 py-2.5">{{ $label }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums">{{ $a->result->{$key} }} / {{ $max }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums">{{ $b->result->{$key} }} / {{ $max }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums {{ $cls($d) }}">{{ $fmt($d) }}</td>
                        </tr>
                    @endforeach
                    @php $da = ($a->result->sciarmi_total !== null && $b->result->sciarmi_total !== null) ? round((float) $b->result->sciarmi_total - (float) $a->result->sciarmi_total, 2) : null; @endphp
                    <tr>
                        <td class="px-5 py-2.5">SCI-ARMI</td>
                        <td class="px-5 py-2.5 text-right tabular-nums">{{ $a->result->sciarmi_total ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-right tabular-nums">{{ $b->result->sciarmi_total ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-right tabular-nums {{ $cls($da) }}">{{ $fmt($da) }}</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-2.5">Grade</td>
                        <td class="px-5 py-2.5 text-right">{{ $a->result->grade ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $b->result->grade ?? '—' }}</td>
                        <td></td>
                    </tr>
                    <tr class="text-slate-600">
                        <td class="px-5 py-2.5">Level · AIS · AMS</td>
                        <td class="px-5 py-2.5 text-right">{{ $a->nli?->value }} · {{ $a->ais?->value }} · {{ $a->ams ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $b->nli?->value }} · {{ $b->ais?->value }} · {{ $b->ams ?? '—' }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($sameVersion)
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-[15px]">
                    <thead class="border-b border-line">
                        <tr>
                            <th class="th">Item</th>
                            <th class="th text-right">A</th>
                            <th class="th text-right">B</th>
                            <th class="th text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($defA->sections() as $section)
                            @foreach ($section['items'] as $item)
                                @php $va = $a->answers[$item['key']] ?? null; $vb = $b->answers[$item['key']] ?? null; $d = ($va !== null && $vb !== null) ? $vb - $va : null; @endphp
                                <tr>
                                    <td class="px-5 py-2.5">{{ $item['number'] }}. {{ isset($item['group']) ? \Illuminate\Support\Str::before($item['group'], ' (').' — ' : '' }}{{ \Illuminate\Support\Str::limit($item['title'], 60) }}</td>
                                    <td class="px-5 py-2.5 text-right tabular-nums">{{ $va }}</td>
                                    <td class="px-5 py-2.5 text-right tabular-nums">{{ $vb }}</td>
                                    <td class="px-5 py-2.5 text-right tabular-nums {{ $cls($d) }}">{{ $fmt($d) }}</td>
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
