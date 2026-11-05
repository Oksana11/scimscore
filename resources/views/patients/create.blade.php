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

        @php $dup = session('duplicate'); @endphp
        @if ($dup && ! $dup['blocked'])
            <div class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-[15px] text-amber-950" role="alert">
                @if (! empty($dup['similar_own']))
                    <p class="font-bold">A similar patient already exists in your list.</p>
                    <p class="mt-1">Please verify whether this is the same patient before creating a new record.</p>
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($dup['similar_own'] as $p)
                            <li>
                                <a class="font-semibold underline" href="{{ route('patients.show', $p['public_id']) }}">{{ $p['system_id'] }} · {{ $p['research_id'] }} · {{ $p['alias'] }}</a>
                                — <a class="btn-link" href="{{ route('patients.assessments.create', $p['public_id']) }}">add assessment to this patient</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($dup['institution'])
                    <p class="font-bold {{ empty($dup['similar_own']) ? '' : 'mt-4' }}">Possible duplicate patient detected.</p>
                    <p class="mt-1">
                        A patient with similar identifying and clinical characteristics may already exist in the system under the same institution.
                        To protect patient privacy, no patient details can be displayed. Please verify internally whether this patient has already
                        been registered by another authorized user from your institution before creating a new patient record.
                    </p>
                @endif
                <p class="mt-3">If you have verified that this is a different patient, confirm below and save again.</p>
            </div>
        @elseif ($dup && $dup['blocked'] && $dup['own'])
            <div class="alert-error mt-4" role="alert">
                This Research ID already exists in your list.
                <a class="font-semibold underline" href="{{ route('patients.assessments.create', $dup['own']) }}">Add a follow-up assessment to that patient</a>.
            </div>
        @endif

        <form method="POST" action="{{ route('patients.store') }}" class="card mt-4" novalidate>
            @csrf
            @include('patients._form', ['patient' => null, 'isCreate' => true])

            @if ($dup && ! $dup['blocked'])
                <label class="mt-6 flex items-start gap-2.5 rounded-xl bg-amber-50 p-3 text-sm font-semibold text-amber-950">
                    <input type="checkbox" name="confirm_not_duplicate" value="1" class="mt-0.5 size-4 accent-brand">
                    I have verified that this is a different patient — create a new record.
                </label>
            @endif

            <div class="mt-6 flex justify-end gap-3 border-t border-line pt-5">
                <a href="{{ route('patients.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save &amp; start assessment →</button>
            </div>
        </form>
    </div>
@endsection
