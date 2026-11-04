@extends('layouts.app')

@section('title', 'Terms of Use')

@section('content')
    <article class="card">
        <h1 class="text-xl font-semibold">Terms of Use</h1>
        <p class="mt-2 text-sm text-slate-500">Version {{ config('scim.terms_version') }}</p>
        {{-- TODO: final text from the customer --}}
        <p class="mt-4 text-sm text-slate-700">
            The data entered into the system is stored anonymously and used for research, statistics and system improvement.
            Do not enter any identifying details of patients (names, ID numbers, phone numbers, addresses or emails).
        </p>
    </article>
@endsection
