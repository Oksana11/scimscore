{{-- Progress + running total for the SCIM form (updated by resources/js/scim-form.js). Expects: $definition --}}
@php $count = count($definition->allowedScores()); @endphp
<div class="card sticky top-[var(--sticky-top,0px)] z-[5] flex flex-wrap items-center gap-4 rounded-t-none border-t-0 px-5 py-3.5 shadow-[0_6px_16px_-8px_rgba(15,23,42,0.18)]">
    <span class="text-[15px] text-slate-600"><strong class="text-ink" data-scim-answered>0</strong> / {{ $count }} items</span>
    <span class="h-1.5 min-w-24 flex-1 overflow-hidden rounded-full bg-slate-100"><progress class="block h-1.5 w-full [&::-webkit-progress-bar]:bg-slate-100 [&::-webkit-progress-value]:bg-brand [&::-moz-progress-bar]:bg-brand" max="{{ $count }}" value="0" data-scim-progress></progress></span>
    <span class="text-[15px] text-slate-600">Running total <strong class="text-lg text-ink tabular-nums" data-scim-total>0</strong></span>
    @if (app()->isLocal())
        <button type="button" class="text-xs font-semibold text-slate-400 hover:text-slate-600" data-scim-demo>Demo fill</button>
    @endif
</div>
