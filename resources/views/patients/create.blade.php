@extends('layouts.app')

@section('title', 'New patient')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('patients.index') }}" class="btn-link">‹ My Patients</a>
        <h1 class="mt-2 text-2xl font-semibold">New patient — first assessment</h1>
        <p class="mt-1 text-sm text-slate-600">
            Please save the Patient Research ID in your local records and use exactly the same code for all future assessments of this patient.
        </p>

        @php $dup = session('duplicate'); @endphp
        @if ($dup && ! $dup['blocked'])
            <div class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-950" role="alert">
                @if (! empty($dup['similar_own']))
                    <p class="font-semibold">A similar patient already exists in your list.</p>
                    <p class="mt-1">Please verify whether this is the same patient before creating a new record.</p>
                    <ul class="mt-3 space-y-1">
                        @foreach ($dup['similar_own'] as $p)
                            <li>
                                <a class="btn-link" href="{{ route('patients.show', $p['public_id']) }}">{{ $p['system_id'] }} · {{ $p['research_id'] }} · {{ $p['alias'] }}</a>
                                — <a class="btn-link" href="{{ route('patients.assessments.create', $p['public_id']) }}">add assessment to this patient</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($dup['institution'])
                    <p class="font-semibold {{ empty($dup['similar_own']) ? '' : 'mt-4' }}">Possible duplicate patient detected.</p>
                    <p class="mt-1">
                        A patient with similar identifying and clinical characteristics may already exist in the system under the same institution.
                        To protect patient privacy, no patient details can be displayed. Please verify internally whether this patient has already
                        been registered by another authorized user from your institution before creating a new patient record.
                    </p>
                @endif
                <p class="mt-3">If you have verified that this is a different patient, confirm below and save again.</p>
            </div>
        @elseif ($dup && $dup['blocked'] && $dup['own'])
            <div class="alert-error mt-6" role="alert">
                This Research ID already exists in your list.
                <a class="btn-link" href="{{ route('patients.assessments.create', $dup['own']) }}">Add a follow-up assessment to that patient</a>.
            </div>
        @endif

        <form method="POST" action="{{ route('patients.store') }}" class="card mt-6" novalidate>
            @csrf
            @include('patients._form', ['patient' => null, 'isCreate' => true])

            @if ($dup && ! $dup['blocked'])
                <label class="mt-6 flex items-start gap-2 text-sm font-medium">
                    <input type="checkbox" name="confirm_not_duplicate" value="1" class="mt-0.5">
                    I have verified that this is a different patient — create a new record.
                </label>
            @endif

            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn-primary">Save &amp; start assessment →</button>
                <a href="{{ route('patients.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
