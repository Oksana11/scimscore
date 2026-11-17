@extends('layouts.app')

@section('title', $page->title)

@section('content')
    <article class="card mx-auto max-w-3xl p-9">
        <h1 class="text-2xl font-bold">{{ $page->title }}</h1>
        @if ($page->slug === \App\Models\Page::TERMS)
            <p class="mt-1 text-sm text-slate-500">Version {{ $page->version }} · updated {{ $page->updated_at->format('j M Y') }}</p>
        @endif
        <div class="page-content mt-5">{{ $page->html() }}</div>
    </article>
@endsection
