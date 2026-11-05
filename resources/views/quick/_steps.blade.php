{{-- Expects: $step (1..3) --}}
<ol class="flex flex-wrap items-center gap-3 text-sm font-semibold" aria-label="Progress">
    @foreach (['Caregiver details', 'SCIM assessment', 'Result'] as $i => $label)
        @php $n = $i + 1; $on = $n === $step; $done = $n < $step; @endphp
        @if ($i > 0)<li aria-hidden="true" class="h-px w-6 bg-slate-300"></li>@endif
        <li class="flex items-center gap-2 {{ $on ? 'rounded-full bg-brand-soft px-2.5 py-1 text-brand' : ($done ? 'text-slate-600' : 'text-slate-400') }}" @if ($on) aria-current="step" @endif>
            <span class="flex size-5 items-center justify-center rounded-full text-[11px] {{ $on ? 'bg-brand text-white' : 'bg-slate-200 text-slate-600' }}">{{ $done ? '✓' : $n }}</span>
            {{ $label }}
        </li>
    @endforeach
</ol>
