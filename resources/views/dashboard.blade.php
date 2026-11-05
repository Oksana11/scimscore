@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $user->profile->profession->name }} · {{ $user->profile->institution->name }}</p>
            <p class="mt-1 text-xs text-slate-500">Only you can see your data.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('patients.index') }}" class="btn-secondary">Follow-up: find patient</a>
            <a href="{{ route('patients.create') }}" class="btn-primary">+ New patient</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('patients.index') }}" class="card hover:border-slate-300">
            <p class="text-xs tracking-wide text-slate-500 uppercase">My patients</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $patientCount }}</p>
        </a>
        <a href="{{ route('assessments.index') }}" class="card hover:border-slate-300">
            <p class="text-xs tracking-wide text-slate-500 uppercase">Assessments</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $assessmentCount }}</p>
        </a>
    </div>

    <div class="card mt-6 overflow-x-auto p-0">
        <h2 class="px-4 pt-4 font-semibold">Recent assessments</h2>
        <table class="mt-2 w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($recent as $a)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><a class="btn-link" href="{{ route('assessments.show', $a) }}">{{ $a->assessed_on->format('d M Y') }}</a></td>
                        <td class="px-4 py-3">{{ $a->patient->system_id }} · {{ $a->patient->research_id }}</td>
                        <td class="px-4 py-3">SCIM {{ $a->scim_version->value }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $a->result->total }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-8 text-center text-slate-500">No assessments yet. Start with <a class="btn-link" href="{{ route('patients.create') }}">a new patient</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
