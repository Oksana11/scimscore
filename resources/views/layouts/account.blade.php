{{-- Signed-in doctor layout: sidebar navigation + top bar. --}}
@php
    $u = auth()->user();
    $profile = $u->profile;
    $initials = strtoupper(mb_substr($u->email, 0, 1));
    $nav = [
        ['dashboard', 'Dashboard', 'grid', ['dashboard']],
        ['patients.index', 'My Patients', 'users', ['patients.index', 'patients.show', 'patients.create', 'patients.edit', 'patients.compare']],
        ['assessments.new', 'New Assessment', 'plus-circle', ['assessments.new', 'patients.assessments.create']],
        ['assessments.index', 'History', 'history', ['assessments.index', 'assessments.show']],
        ['profile.edit', 'Profile', 'user', ['profile.edit']],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen lg:flex">
    <aside class="hidden w-[250px] shrink-0 flex-col border-r border-line bg-white lg:sticky lg:top-0 lg:flex lg:h-screen print:hidden">
        <div class="px-5 pt-5 pb-6"><x-logo subtitle="Personal research account" :href="route('dashboard')" /></div>
        <nav class="flex-1 space-y-1 px-3.5" aria-label="Main">
            @foreach ($nav as [$route, $label, $icon, $active])
                @php $on = request()->routeIs(...$active); @endphp
                <a href="{{ route($route) }}" @if ($on) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-semibold {{ $on ? 'bg-brand-soft text-brand-dark' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                    <x-icon :name="$icon" class="size-[19px]" /> {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="m-3.5 flex items-center gap-3 rounded-2xl border border-line bg-slate-50 p-3">
            <span class="avatar">{{ $initials }}</span>
            <div class="min-w-0 flex-1 text-xs leading-tight">
                <p class="truncate font-semibold text-ink" title="{{ $u->email }}">{{ $u->email }}</p>
                <p class="truncate text-slate-500">{{ $profile?->profession?->name }} · {{ $profile?->institution?->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="rounded-lg p-1.5 text-slate-400 hover:bg-white hover:text-ink" title="Sign out" aria-label="Sign out"><x-icon name="logout" class="size-[18px]" /></button>
            </form>
        </div>
    </aside>

    <div class="flex min-h-screen min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-10 border-b border-line bg-white/95 backdrop-blur print:hidden">
            <div class="flex items-center justify-between gap-4 px-5 py-3.5 sm:px-9">
                <div class="flex items-center gap-3">
                    {{-- mobile menu (no JS) --}}
                    <details class="relative lg:hidden">
                        <summary class="flex cursor-pointer list-none rounded-lg p-1.5 text-slate-600 hover:bg-slate-100" aria-label="Menu"><x-icon name="menu" /></summary>
                        <nav class="absolute top-10 left-0 z-20 w-60 rounded-2xl border border-line bg-white p-2 shadow-lg">
                            @foreach ($nav as [$route, $label, $icon])
                                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><x-icon :name="$icon" class="size-4" /> {{ $label }}</a>
                            @endforeach
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-1">@csrf
                                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><x-icon name="logout" class="size-4" /> Sign out</button>
                            </form>
                        </nav>
                    </details>
                    <p class="text-[15px] text-slate-600">@yield('breadcrumb')</p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-slate-400 md:inline">{{ now()->format('l, j F Y') }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                        <span class="size-1.5 rounded-full bg-green-600"></span> Only you can see your data
                    </span>
                </div>
            </div>
        </header>

        <main class="flex-1 px-5 py-8 sm:px-9">
            <div class="mx-auto max-w-5xl">
                @if (session('status'))
                    <div class="alert-success mb-6" role="status">{{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
        </main>

        <div class="print:hidden">@include('partials.footer')</div>
    </div>
</body>
</html>
