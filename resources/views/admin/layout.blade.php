<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@hasSection('title')@yield('title') · @endif SCIMSCORE Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <header class="bg-slate-900 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('admin.dashboard') }}" class="font-semibold">SCIMSCORE <span class="text-amber-300">Admin</span></a>
            @auth('admin')
                <nav class="flex flex-wrap items-center gap-4 text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="hover:underline">Statistics</a>
                    <a href="{{ route('admin.users') }}" class="hover:underline">Users</a>
                    <a href="{{ route('admin.patients') }}" class="hover:underline">Patients</a>
                    <a href="{{ route('admin.assessments') }}" class="hover:underline">Assessments</a>
                    <a href="{{ route('admin.settings') }}" class="hover:underline">Settings</a>
                    <a href="{{ route('admin.emails') }}" class="hover:underline">Emails</a>
                    <a href="{{ route('admin.audit') }}" class="hover:underline">Audit log</a>
                    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="rounded border border-white/30 px-3 py-1 hover:bg-white/10">Sign out</button></form>
                </nav>
            @endauth
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8">
        @if (session('status'))<div class="alert-success mb-6" role="status">{{ session('status') }}</div>@endif
        @if (session('audit_error'))<div class="alert-error mb-6 whitespace-pre-line" role="alert">{{ session('audit_error') }}</div>@endif
        @error('export')<div class="alert-error mb-6" role="alert">{{ $message }}</div>@enderror
        @yield('content')
    </main>
</body>
</html>
