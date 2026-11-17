@extends('admin.layout')
@section('title', 'Support message')
@section('content')
    <a href="{{ route('admin.support') }}" class="back-link"><x-icon name="chevron-left" class="size-4" /> Support messages</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $message->subject }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $message->email }} · {{ $message->created_at->format('Y-m-d H:i') }} @if ($message->user_id) · registered user @endif</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="#reply" class="btn-primary">Reply</a>
            <form method="POST" action="{{ route('admin.support.destroy', $message) }}" data-confirm="Delete this message and its replies?">@csrf @method('DELETE')
                <button class="btn-secondary text-red-700">Delete</button>
            </form>
        </div>
    </div>

    <div class="mt-4">
        <form method="POST" action="{{ route('admin.support.status', $message) }}" class="flex items-center gap-2">@csrf
            <label for="status" class="text-sm text-slate-500">Status</label>
            <select id="status" name="status" class="input w-auto">
                @foreach (\App\Models\SupportMessage::STATUSES as $s)
                    <option value="{{ $s }}" @selected($message->status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Save</button>
        </form>
    </div>

    <div class="mt-6 max-w-3xl space-y-4">
        <article class="card">
            <p class="text-xs font-semibold uppercase text-slate-500">Message</p>
            <p class="mt-2 text-sm whitespace-pre-line">{{ $message->message }}</p>
        </article>

        @foreach ($message->replies as $r)
            <article class="card border-l-4 border-l-brand">
                <p class="text-xs font-semibold uppercase text-slate-500">Reply · {{ $r->admin?->name ?? 'Administrator' }} · {{ $r->created_at->format('Y-m-d H:i') }}</p>
                <p class="mt-2 text-sm whitespace-pre-line">{{ $r->body }}</p>
            </article>
        @endforeach

        <section id="reply" class="card scroll-mt-4">
            <h2 class="mb-3 font-semibold">Reply</h2>
            <form method="POST" action="{{ route('admin.support.reply', $message) }}" class="space-y-3">@csrf
                <label for="body" class="label">Reply by email to {{ $message->email }}</label>
                <textarea id="body" name="body" rows="7" class="input" maxlength="10000" required>{{ old('body') }}</textarea>
                @error('body') <p class="field-error">{{ $message }}</p> @enderror
                <button type="submit" class="btn-primary">Send reply</button>
                <p class="hint">The email includes your reply, their original message and the text set in Emails → Support reply.</p>
            </form>
        </section>
    </div>
@endsection
