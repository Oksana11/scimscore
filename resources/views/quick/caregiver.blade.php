@extends('layouts.app')

@section('title', 'Quick Assessment')

@section('content')
    <div class="mx-auto max-w-[680px]">
        <a href="{{ route('home') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Back</a>
        <div class="mt-4">@include('quick._steps', ['step' => 1])</div>
        <h1 class="page-title mt-4">Quick Assessment — caregiver details</h1>
        <p class="page-sub">A few details about you, the assessing professional. They're stored anonymously with the result for research and statistics. No account is created.</p>

        <form method="POST" action="{{ route('quick.caregiver') }}" class="card mt-6" novalidate>
            @csrf
            @include('profile._fields')
            <div class="mt-6 flex justify-end border-t border-line pt-5">
                <button type="submit" class="btn-primary">Continue to assessment →</button>
            </div>
        </form>
    </div>
@endsection
