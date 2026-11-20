@extends('layouts.app')

@section('title', 'Complete your profile')

@section('content')
    <div class="mx-auto max-w-[760px]">
        <h1 class="page-title">Complete your profile</h1>
        <p class="page-sub">A few details about you, the assessing professional. Used for statistics only and never shown to other users.</p>
        <form method="POST" action="{{ route('profile.update') }}" class="card mt-6" novalidate>
            @csrf
            @method('PUT')
            @include('profile._fields')
            <div class="mt-6 flex justify-end border-t border-line pt-5">
                <button type="submit" class="btn-primary">Continue to dashboard →</button>
            </div>
        </form>
    </div>
@endsection
