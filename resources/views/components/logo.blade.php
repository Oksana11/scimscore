@props(['subtitle' => 'Spinal Cord Independence Measure', 'href' => null])
<a href="{{ $href ?? route('home') }}" class="flex items-center gap-3">
    <span class="flex size-10 items-center justify-center rounded-xl bg-brand text-white shadow-sm"><x-icon name="pulse" class="size-5" /></span>
    <span class="leading-tight">
        <span class="block text-[17px] font-bold tracking-tight text-ink">SCIMSCORE</span>
        <span class="block text-xs font-medium text-slate-500">{{ $subtitle }}</span>
    </span>
</a>
