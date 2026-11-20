{{-- Result summary (mockup "SCIM III result"). Expects: $assessment (with result). --}}
@php
    $r = $assessment->result;
    $sections = [['Self-Care', $r->self_care, 20], ['Respiration & Sphincter', $r->respiration_sphincter, 40], ['Mobility', $r->mobility, 40]];
@endphp
<div class="grid gap-5 lg:grid-cols-2">
    <div class="rounded-2xl bg-blue-900 p-7 text-white">
        <p class="text-xs font-bold tracking-[0.14em] text-blue-200 uppercase">SCIM total</p>
        <p class="mt-2"><span class="text-6xl font-bold tabular-nums">{{ $r->total }}</span><span class="ml-1 text-2xl text-blue-200">/ 100</span></p>
        <div class="mt-5 grid grid-cols-2 gap-4 border-t border-white/20 pt-4">
            <div>
                <p class="text-sm text-blue-100">SCI-ARMI</p>
                <p class="text-xs text-blue-300">Adapted for SCIM {{ $assessment->scim_version->value }}</p>
            </div>
            <p class="text-right text-3xl font-bold tabular-nums">{{ $r->sciarmi_total !== null ? ((float) $r->sciarmi_total).'%' : '—' }}</p>
            <div>
                <p class="text-sm text-blue-100">SCIM grade</p>
                <p class="text-xs text-blue-300">Nearest reference profile</p>
            </div>
            <p class="text-right text-3xl font-bold">{{ $r->grade ?? '—' }}</p>
        </div>
        @if ($r->sciarmi_total === null)
            <p class="mt-3 text-xs text-blue-200">SCI-ARMI requires AMS, age and gender.</p>
        @endif
    </div>

    <div class="card">
        <div class="space-y-4">
            @foreach ($sections as [$label, $score, $max])
                <div>
                    <div class="flex justify-between text-[15px]"><span class="font-semibold">{{ $label }}</span><span><strong class="tabular-nums">{{ $score }}</strong><span class="text-slate-400"> / {{ $max }}</span></span></div>
                    <meter class="mt-1.5 h-2 w-full" min="0" max="{{ $max }}" value="{{ $score }}"></meter>
                </div>
            @endforeach
        </div>
        <p class="mt-5 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-500">
            <span>Level <strong class="text-ink">{{ $assessment->nli?->value ?? '—' }}</strong></span>
            <span>AIS <strong class="text-ink">{{ $assessment->ais?->value ?? '—' }}</strong></span>
            <span>AMS <strong class="text-ink">{{ $assessment->ams ?? '—' }}</strong></span>
            <span>{{ $assessment->gender->label() }}, {{ $assessment->age }} y</span>
            @if ($assessment->etiology)<span>{{ $assessment->etiology->label() }}</span>@endif
        </p>
    </div>
</div>
