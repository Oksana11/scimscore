{{-- Expects: $p (Patient) --}}
<div class="flex items-center gap-3">
    <span class="avatar">{{ $p->initials() }}</span>
    <div class="leading-tight">
        <p><strong class="text-ink">{{ $p->alias }}</strong> <span class="font-mono text-xs text-slate-500">{{ $p->research_id }}</span></p>
        <p class="text-xs text-slate-400">{{ $p->gender->label() }} · {{ $p->birth_year }}</p>
    </div>
</div>
