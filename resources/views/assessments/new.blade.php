@extends('layouts.account')

@section('title', 'New assessment')
@section('breadcrumb', 'New Assessment')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="page-title">New assessment</h1>
        <p class="page-sub">Is this patient already in your list? Always search first to avoid duplicates.</p>

        <div class="mt-6 space-y-3">
            <a href="{{ route('patients.index') }}" class="card flex items-center gap-4 px-5 py-4 hover:border-slate-300">
                <span class="flex size-11 items-center justify-center rounded-xl bg-brand text-white"><x-icon name="search" /></span>
                <span class="flex-1"><span class="block font-bold">Existing patient — follow-up</span><span class="text-[15px] text-slate-500">Search by Research ID, alias or year of birth.</span></span>
                <x-icon name="chevron-right" class="size-5 text-slate-400" />
            </a>
            <a href="{{ route('patients.create') }}" class="card flex items-center gap-4 px-5 py-4 hover:border-slate-300">
                <span class="flex size-11 items-center justify-center rounded-xl bg-brand-soft text-brand"><x-icon name="plus" /></span>
                <span class="flex-1"><span class="block font-bold">New patient — first assessment</span><span class="text-[15px] text-slate-500">Creates a System Patient ID and runs duplicate checks.</span></span>
                <x-icon name="chevron-right" class="size-5 text-slate-400" />
            </a>
        </div>
    </div>
@endsection
