{{-- Result table. Expects: $assessment (with result). --}}
@php $r = $assessment->result; @endphp
<dl class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-lg bg-slate-50 p-4">
        <dt class="text-xs tracking-wide text-slate-500 uppercase">SCIM total</dt>
        <dd class="mt-1 text-3xl font-semibold tabular-nums">{{ $r->total }} <span class="text-base font-normal text-slate-500">/ 100</span></dd>
    </div>
    <div class="rounded-lg bg-slate-50 p-4">
        <dt class="text-xs tracking-wide text-slate-500 uppercase">SCI-ARMI</dt>
        <dd class="mt-1 text-3xl font-semibold tabular-nums">{{ $r->sciarmi_total ?? '—' }}</dd>
        @if ($r->sciarmi_total === null)
            <p class="mt-1 text-xs text-slate-500">Requires AMS, age and gender.</p>
        @endif
    </div>
    <div class="rounded-lg bg-slate-50 p-4">
        <dt class="text-xs tracking-wide text-slate-500 uppercase">SCIM grade</dt>
        <dd class="mt-1 text-3xl font-semibold">{{ $r->grade ?? '—' }}</dd>
    </div>
</dl>

<table class="mt-6 w-full text-sm">
    <thead>
        <tr class="border-b border-slate-200 text-left text-slate-500">
            <th class="py-2 font-medium">Section</th>
            <th class="py-2 text-right font-medium">Score</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        <tr><td class="py-2">Self-Care</td><td class="py-2 text-right tabular-nums">{{ $r->self_care }} / 20</td></tr>
        <tr><td class="py-2">Respiration and Sphincter Management</td><td class="py-2 text-right tabular-nums">{{ $r->respiration_sphincter }} / 40</td></tr>
        <tr><td class="py-2">Mobility</td><td class="py-2 text-right tabular-nums">{{ $r->mobility }} / 40</td></tr>
    </tbody>
</table>

<p class="mt-4 text-xs text-slate-500">
    SCIM {{ $assessment->scim_version->value }} · {{ $assessment->assessed_on->format('d M Y') }} ·
    {{ $assessment->gender->label() }}, {{ $assessment->age }} y · {{ $assessment->nli?->value }} · AIS {{ $assessment->ais?->value }} · AMS {{ $assessment->ams ?? '—' }}
</p>
