@extends('layouts.app')

@section('title', 'Edit patient')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('patients.show', $patient) }}" class="btn-link">‹ {{ $patient->system_id }}</a>
        <h1 class="mt-2 text-2xl font-semibold">Edit patient details</h1>

        <form method="POST" action="{{ route('patients.update', $patient) }}" class="card mt-6" novalidate>
            @csrf
            @method('PUT')
            @include('patients._form', ['isCreate' => false])
            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn-primary">Save</button>
                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
