<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@hasSection('title')@yield('title') · @endif SCIMSCORE</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="font-semibold tracking-tight">
                SCIMSCORE <span class="hidden text-sm font-normal text-slate-500 sm:inline">Spinal Cord Independence Measure</span>
            </a>
            <nav class="flex items-center gap-4 text-sm">
                @auth
                    @if (auth()->user()->profile)
                        <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
                        <a href="{{ route('patients.index') }}" class="hover:underline">My Patients</a>
                        <a href="{{ route('assessments.index') }}" class="hover:underline">History</a>
                        <a href="{{ route('profile.edit') }}" class="hover:underline">Profile</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary py-1.5">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-primary py-1.5">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10">
        @if (session('status'))
            <div class="alert-success mb-6" role="status">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="mx-auto max-w-5xl px-4 pb-10 text-xs text-slate-500">
        © {{ date('Y') }} SCIMSCORE · Loewenstein Rehabilitation Medical Center ·
        <a href="{{ route('terms') }}" class="underline">Terms of Use</a>
    </footer>
</body>
</html>
