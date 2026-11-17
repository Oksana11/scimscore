@extends('admin.layout')
@section('title', $page->exists ? $page->title : 'New page')
@section('content')
    <a href="{{ route('admin.pages') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Pages</a>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">{{ $page->exists ? 'Edit page' : 'New page' }}</h1>
        @if ($page->exists && $page->is_published)
            <a href="{{ $page->url() }}" target="_blank" rel="noopener" class="btn-secondary">View on site ↗</a>
        @endif
    </div>

    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="mt-6 grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        <section class="card space-y-4 lg:col-span-2">
            <div>
                <label for="title" class="label">Title</label>
                <input id="title" name="title" class="input" maxlength="150" required value="{{ old('title', $page->title) }}">
                @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="body" class="label">Content</label>
                <textarea id="body" name="body" rows="20" class="input font-mono text-sm" maxlength="100000" required>{{ old('body', $page->body) }}</textarea>
                <p class="hint">Markdown: empty line = new paragraph · <code>## Heading</code> · <code>**bold**</code> · <code>- list item</code> · <code>[link text](https://…)</code>. HTML is not allowed.</p>
                @error('body') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="card space-y-4 self-start">
            <div>
                <label for="slug" class="label">Address</label>
                @if ($page->is_system)
                    <p class="text-sm">/{{ $page->slug }} <span class="text-xs text-slate-400">(fixed)</span></p>
                @else
                    <div class="flex items-center gap-1"><span class="text-sm text-slate-500">/</span>
                        <input id="slug" name="slug" class="input" maxlength="64" required pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $page->slug) }}" placeholder="about-us">
                    </div>
                    <p class="hint">Lowercase letters, numbers and hyphens.</p>
                    @error('slug') <p class="field-error">{{ $message }}</p> @enderror
                @endif
            </div>

            @if ($page->slug === \App\Models\Page::TERMS)
                <div>
                    <label for="version" class="label">Terms version</label>
                    <input id="version" name="version" class="input" maxlength="32" required value="{{ old('version', $page->version) }}">
                    <p class="hint">Change it only for substantive changes: every user will be asked to accept the new terms at their next sign-in.</p>
                    @error('version') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <fieldset class="space-y-2">
                <legend class="label">Show in menu</legend>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_in_header" value="1" @checked(old('show_in_header', $page->show_in_header))> Top menu</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_in_footer" value="1" @checked(old('show_in_footer', $page->show_in_footer))> Footer</label>
            </fieldset>

            <div>
                <label for="sort" class="label">Menu order</label>
                <input id="sort" name="sort" type="number" min="0" max="9999" class="input" value="{{ old('sort', $page->sort) }}">
                <p class="hint">Smaller numbers come first.</p>
            </div>

            @unless ($page->is_system)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> Published</label>
            @endunless

            <button type="submit" class="btn-primary w-full">Save</button>
        </section>
    </form>
@endsection
