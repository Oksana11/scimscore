@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl text-center">
        <p class="text-xs font-bold tracking-[0.18em] text-brand uppercase">SCIMSCORE</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink sm:text-[34px]">Web Applications of the Spinal Cord Independence Measure</h1>
        <p class="mx-auto mt-4 max-w-3xl text-[15px] leading-relaxed text-slate-600">
            The Spinal Cord Independence Measure (SCIM) was developed at the Loewenstein Rehabilitation Medical Center (LRMC).
            Use of any of its versions, in any language, is free for clinical and academic purposes. If used for profit, including
            clinical trials sponsored by a commercial firm, it requires the written agreement of LRMC and it is subject to a fee.
        </p>
    </section>

    <div class="mx-auto mt-8 grid max-w-5xl gap-5 md:grid-cols-2">
        <div class="relative flex flex-col rounded-2xl bg-brand p-7 text-white shadow-[0_18px_40px_-18px_rgba(37,99,235,0.7)]">
            <span class="absolute top-5 right-5 rounded-full bg-white/20 px-2.5 py-1 text-[11px] font-bold tracking-wider uppercase">Recommended</span>
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-white/15"><x-icon name="user" /></span>
                <h2 class="text-xl font-bold">Sign in or create an account</h2>
            </div>
            <p class="mt-4 text-[15px] leading-relaxed text-blue-50">
                One step for new and returning users: enter your email and we'll send a one-time code. Registered users are signed in;
                new emails get an account. No password.
            </p>
            <ul class="mt-4 space-y-1.5 text-[15px]">
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-blue-200" /> Save assessments to anonymous patients</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-blue-200" /> View history and compare over time</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-blue-200" /> Filter your data and export PDF / Excel</li>
            </ul>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn mt-6 w-full bg-white py-3 text-[15px] text-brand-dark hover:bg-blue-50 focus:ring-white/40">Continue with email →</a>
            @guest
                <p class="mt-3 text-center text-sm text-blue-100">Already have an account? <a href="{{ route('login') }}" class="font-bold text-white underline">Sign in</a></p>
            @endguest
        </div>

        <div class="card flex flex-col p-7">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><x-icon name="bolt" /></span>
                <h2 class="text-xl font-bold">Quick Assessment</h2>
            </div>
            <p class="mt-4 text-[15px] text-slate-500">One-time calculation without an account. Nothing is linked to a patient.</p>
            <ul class="mt-4 space-y-1.5 text-[15px] text-slate-700">
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-slate-400" /> SCIM III or SCIM IV, item by item</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-slate-400" /> Instant total and SCI-ARMI</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-slate-400" /> Print, PDF or Excel export</li>
            </ul>
            <a href="{{ route('quick.create') }}" class="btn-secondary mt-auto w-full py-3 text-[15px]">Start quick assessment →</a>
        </div>
    </div>

    <div class="mx-auto mt-8 max-w-5xl space-y-2 text-center text-sm text-slate-500">
        <p>This website offers SCIM applications at no charge. A fee may be charged for certain SCIM applications in the future.</p>
        <p>The application currently available is calculation of the SCIM Grade (A-H). Enter the scores for the various SCIM tasks and click the SCIM Grade button.</p>
        <p>Check back with the SCIMSCORE site from time to time for additional applications and for valuable information for spinal cord injured patients and their caregivers.</p>
    </div>
@endsection
