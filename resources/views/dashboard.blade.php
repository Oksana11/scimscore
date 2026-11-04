@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="mt-1 text-sm text-slate-600">
        {{ $user->profile->profession->name }} · {{ $user->profile->institution->name }}
    </p>
    <p class="mt-1 text-xs text-slate-500">Only you can see your data.</p>

    <div class="card mt-6 text-sm text-slate-600">
        Patients and assessments will appear here (next stage).
    </div>
@endsection
