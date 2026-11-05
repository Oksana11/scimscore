@props(['name', 'class' => 'size-5'])
{{-- Minimal inline icon set (stroke icons, 24px grid). No external icon fonts or requests. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('pulse')<path d="M3 12h4l2-5 4 10 2-5h6"/>@break
        @case('grid')<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>@break
        @case('users')<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.5 3.4-5.5 6.5-5.5s5.7 2 6.5 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.7 3.1 2.5 3.5 5.2"/>@break
        @case('plus-circle')<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>@break
        @case('user-plus')<circle cx="10" cy="8" r="4"/><path d="M2 21c1-4 4-6 8-6 1.5 0 2.9.3 4 .9M19 14v6M16 17h6"/>@break
        @case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>@break
        @case('bolt')<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>@break
        @case('check')<path d="m5 12 4.5 4.5L19 7"/>@break
        @case('chevron-right')<path d="m9 6 6 6-6 6"/>@break
        @case('chevron-left')<path d="m15 6-6 6 6 6"/>@break
        @case('logout')<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M10 16l-4-4 4-4M6 12h10"/>@break
        @case('print')<path d="M7 9V4h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><rect x="7" y="14" width="10" height="6" rx="1"/>@break
        @case('file')<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/>@break
        @case('sheet')<rect x="4" y="4" width="16" height="16" rx="2"/><path d="m9 9 6 6M15 9l-6 6"/>@break
        @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>@break
        @case('history')<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>@break
        @case('alert')<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
    @endswitch
</svg>
