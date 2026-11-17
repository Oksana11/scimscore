<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@hasSection('title')@yield('title') · @endif SCIMSCORE Admin</title>
    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
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
                    @php($newSupport = \App\Models\SupportMessage::query()->where('status', 'new')->count())
                    <a href="{{ route('admin.support') }}" class="hover:underline">Support @if ($newSupport)<span class="ml-0.5 rounded-full bg-amber-400 px-1.5 text-xs font-bold text-slate-900">{{ $newSupport }}</span>@endif</a>
                    <a href="{{ route('admin.pages') }}" class="hover:underline">Pages</a>
                    <a href="{{ route('admin.emails') }}" class="hover:underline">Emails</a>
                    <a href="{{ route('admin.admins') }}" class="hover:underline">Admins</a>
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
