@extends('layouts.account')

@section('title', 'New patient')
@section('breadcrumb')<a href="{{ route('patients.index') }}" class="hover:underline">My Patients</a> / New Patient @endsection

@section('content')
    <div class="mx-auto max-w-[800px]">
        <a href="{{ route('dashboard') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Dashboard</a>
        <h1 class="page-title mt-3">New Patient — First Assessment</h1>
        <p class="page-sub">No names, ID numbers, phone numbers, addresses or emails are stored.</p>

        <div class="mt-5 rounded-2xl border border-blue-200 bg-brand-soft px-5 py-4 text-xs font-bold tracking-wider text-brand uppercase">
            System Patient ID · auto-generated after saving
        </div>

        <div class="alert-warn mt-4 flex gap-3">
            <span class="font-bold">!</span>
            <div class="space-y-1">
                <p>Please save this <strong>Patient Research ID / Local Patient Code</strong> in your local records and use exactly the same code for all future assessments of this patient.</p>
                <p>Do not use email addresses, phone numbers or real passport / ID details of the patient in these fields.</p>
            </div>
        </div>

        @php
            $dup = session('duplicate');
            $warn = $dup && ! $dup['blocked'];
            // values entered in the form, carried into the "merge" request
            $carry = collect(['nli', 'ais', 'ams', 'first_nli', 'first_ais', 'first_ams'])->mapWithKeys(fn ($k) => [$k => old($k)]);
        @endphp

        @if ($warn)
            <section class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-[15px] text-amber-950" role="alert">
                <h2 class="flex items-center gap-2 font-bold"><x-icon name="alert" class="size-5" /> Possible duplicate — please check before saving</h2>

                @if (! empty($dup['similar_own']))
                    <p class="mt-2">A similar patient already exists in your list. If it is the same person, merge: no new record is created and this assessment is added to the existing patient's history.</p>
                    <div class="mt-4 space-y-3">
                        @foreach ($dup['similar_own'] as $p)
                            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-amber-200 bg-white p-4 text-ink">
                                <div>
                                    <p><strong>{{ $p['alias'] }}</strong> <span class="font-mono text-sm text-slate-500">{{ $p['research_id'] }}</span></p>
                                    <p class="text-sm text-slate-500">
                                        <span class="font-mono">{{ $p['system_id'] }}</span> · {{ $p['gender'] }} · born {{ $p['birth_year'] }}
                                        · {{ $p['assessments'] }} {{ \Illuminate\Support\Str::plural('assessment', $p['assessments']) }}
                                        @if ($p['last']) · last: {{ $p['last'] }} @endif
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('patients.show', $p['public_id']) }}" class="btn-secondary py-2" target="_blank" rel="noopener">View</a>
                                    <form method="POST" action="{{ route('patients.merge', $p['public_id']) }}">
                                        @csrf
                                        @foreach ($carry as $k => $val)<input type="hidden" name="{{ $k }}" value="{{ $val }}">@endforeach
                                        <button type="submit" class="btn-primary py-2">Merge with this patient</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($dup['institution'])
                    <div class="{{ empty($dup['similar_own']) ? 'mt-2' : 'mt-5 border-t border-amber-200 pt-4' }}">
                        <p class="font-semibold">Possible duplicate patient detected at your institution.</p>
                        <p class="mt-1">
                            A patient with similar identifying and clinical characteristics may already exist in the system under the same institution.
                            To protect patient privacy, no patient details can be displayed. Please verify internally whether this patient has already
                            been registered by another authorized user from your institution before creating a new patient record.
                        </p>
                    </div>
                @endif

                <p class="mt-4">Not the same person? Use <strong>Add as a new patient</strong> at the bottom of the form.</p>
            </section>
        @elseif ($dup && $dup['blocked'] && $dup['own'])
            <div class="alert-error mt-4" role="alert">
                This Research ID already exists in your list.
                <a class="font-semibold underline" href="{{ route('patients.assessments.create', $dup['own']) }}">Add a follow-up assessment to that patient</a>.
            </div>
        @endif

        <form method="POST" action="{{ route('patients.store') }}" class="card mt-4" novalidate>
            @csrf
            @include('patients._form', ['patient' => null, 'isCreate' => true])

            <div class="mt-6 flex justify-end gap-3 border-t border-line pt-5">
                <a href="{{ route('patients.index') }}" class="btn-secondary">Cancel</a>
                @if ($warn)
                    <button type="submit" name="confirm_not_duplicate" value="1" class="btn-primary">Add as a new patient →</button>
                @else
                    <button type="submit" class="btn-primary">Save &amp; start assessment →</button>
                @endif
            </div>
        </form>
    </div>
@endsection
