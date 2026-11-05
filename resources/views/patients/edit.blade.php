@extends('layouts.account')

@section('title', 'Edit patient')
@section('breadcrumb')<a href="{{ route('patients.index') }}" class="hover:underline">My Patients</a> / Edit @endsection

@section('content')
    <div class="mx-auto max-w-[800px]">
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
    </div>
@endsection
