@extends('layouts.account')

@section('title', 'Edit patient')
@section('breadcrumb')<a href="{{ route('patients.index') }}" class="hover:underline">My Patients</a> / Edit @endsection

@section('content')
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('patients.show', $patient) }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> {{ $patient->system_id }}</a>
        <h1 class="page-title mt-3">Edit patient details</h1>

        <form method="POST" action="{{ route('patients.update', $patient) }}" class="card mt-6" novalidate>
            @csrf
            @method('PUT')
            @include('patients._form', ['isCreate' => false])
            <div class="mt-6 flex justify-end gap-3 border-t border-line pt-5">
                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>

        <div class="card mt-6 border-red-200">
            <h2 class="text-lg font-semibold text-red-800">Delete patient</h2>
            <p class="mt-1 text-sm text-slate-600">The patient and <strong>all their assessments</strong> are deleted permanently. This cannot be undone.</p>
            <form method="POST" action="{{ route('patients.destroy', $patient) }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf @method('DELETE')
                <div>
                    <label for="confirm-patient" class="label">Type DELETE to confirm</label>
                    <input id="confirm-patient" name="confirm" class="input" autocomplete="off" required pattern="DELETE">
                    @error('confirm') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-secondary text-red-700">Delete patient</button>
            </form>
        </div>
    </div>
@endsection
