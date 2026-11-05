{{-- Public layout: landing, sign-in, quick assessment, terms. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-screen flex-col">
    <header class="border-b border-line bg-white print:hidden">
        <div class="flex items-center justify-between gap-4 px-6 py-3 sm:px-9">
            <x-logo />
            @auth
                <a href="{{ auth()->user()->profile ? route('dashboard') : route('profile.complete') }}" class="btn-secondary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
            @endauth
        </div>
    </header>

    <main class="w-full flex-1 px-4 py-8 sm:px-6">
        @if (session('status'))
            <div class="alert-success mx-auto mb-6 max-w-3xl" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <div class="print:hidden">@include('partials.footer')</div>
</body>
</html>
