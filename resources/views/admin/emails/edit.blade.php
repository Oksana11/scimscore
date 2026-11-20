@extends('admin.layout')
@section('title', $definition['label'])
@section('content')
    <a href="{{ route('admin.emails') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Emails</a>
    <h1 class="mt-3 text-2xl font-semibold">{{ $definition['label'] }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $definition['description'] }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card">
            <form method="POST" action="{{ route('admin.emails.update', $key) }}" class="space-y-4">@csrf @method('PUT')
                <div>
                    <label for="subject" class="label">Subject</label>
                    <input id="subject" name="subject" class="input" maxlength="150" required value="{{ old('subject', $current['subject']) }}">
                    @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="heading" class="label">Heading</label>
                    <input id="heading" name="heading" class="input" maxlength="150" required value="{{ old('heading', $current['heading']) }}">
                    @error('heading') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="body" class="label">Text</label>
                    <textarea id="body" name="body" rows="10" class="input" maxlength="5000" required>{{ old('body', $current['body']) }}</textarea>
                    <p class="hint">One paragraph per line. Placeholders: {{ implode(', ', $definition['placeholders']) }}</p>
                    @if (str_ends_with($key, '_code'))
                        <p class="hint">The first line is shown above the code, the rest below it. The code itself is always included.</p>
                    @endif
                    @error('body') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
            <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                <form method="POST" action="{{ route('admin.emails.test', $key) }}">@csrf
                    <button type="submit" class="btn-secondary">Send test to me</button>
                </form>
                @if ($current['customized'])
                    <form method="POST" action="{{ route('admin.emails.reset', $key) }}">@csrf
                        <button type="submit" class="btn-secondary text-red-700">Restore default</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="card p-0">
            <p class="px-5 pt-4 text-sm font-semibold">Preview <span class="font-normal text-slate-500">(saved version, sample data)</span></p>
            <iframe src="{{ route('admin.emails.preview', $key) }}" sandbox="" title="Email preview" class="mt-2 h-[560px] w-full rounded-b-2xl border-t border-line bg-slate-50"></iframe>
        </section>
    </div>
@endsection
