@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl text-center">
        <h1 class="text-3xl font-semibold tracking-tight">SCIMSCORE</h1>
        <p class="mt-2 text-slate-600">Web applications of the Spinal Cord Independence Measure</p>
    </section>

    <div class="mx-auto mt-10 grid max-w-3xl gap-6 md:grid-cols-2">
        <div class="card flex flex-col">
            <span class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Recommended</span>
            <h2 class="mt-1 text-lg font-semibold">Sign in or create an account</h2>
            <p class="mt-1 text-sm text-slate-600">Enter your email and we'll send a one-time code. No password.</p>
            <ul class="mt-4 space-y-1 text-sm text-slate-700">
                <li>✓ Save assessments to anonymous patients</li>
                <li>✓ View history and compare over time</li>
                <li>✓ Filter your data and export PDF / Excel</li>
            </ul>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary mt-6 self-start">Continue with email →</a>
        </div>

        <div class="card flex flex-col">
            <h2 class="text-lg font-semibold">Quick Assessment</h2>
            <p class="mt-1 text-sm text-slate-600">One-time calculation without an account. Nothing is linked to a patient.</p>
            <ul class="mt-4 space-y-1 text-sm text-slate-700">
                <li>✓ SCIM item by item</li>
                <li>✓ Instant total and SCI-ARMI</li>
                <li>✓ Print, PDF or Excel export</li>
            </ul>
            <span class="btn-secondary mt-6 self-start opacity-60" aria-disabled="true">Coming soon</span>
        </div>
    </div>

    <p class="mx-auto mt-10 max-w-3xl text-xs text-slate-500">
        The Spinal Cord Independence Measure (SCIM) was developed at the Loewenstein Rehabilitation Medical Center (LRMC).
        Use of any of its versions, in any language, is free for clinical and academic purposes. If used for profit, including
        clinical trials sponsored by a commercial firm, it requires the written agreement of LRMC and it is subject to a fee.
    </p>
@endsection
