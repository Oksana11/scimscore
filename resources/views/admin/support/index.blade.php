@extends('admin.layout')
@section('title', 'Support')
@section('content')
    <h1 class="text-2xl font-semibold">Support messages</h1>

    <nav class="mt-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.support') }}" class="{{ $status === null ? 'btn-primary' : 'btn-secondary' }}">All</a>
        @foreach (\App\Models\SupportMessage::STATUSES as $s)
            <a href="{{ route('admin.support', ['status' => $s]) }}" class="{{ $status === $s ? 'btn-primary' : 'btn-secondary' }}">{{ ucfirst($s) }}</a>
        @endforeach
    </nav>

    <section class="card mt-4 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase text-slate-500">
                    <th class="px-4 py-3">Received</th><th class="px-4 py-3">From</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Replies</th><th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($messages as $m)
                    <tr class="border-b border-slate-100 {{ $m->status === 'new' ? 'font-semibold' : '' }}">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $m->email }} @if ($m->user_id) <span class="text-xs font-normal text-slate-400">(user)</span> @endif</td>
                        <td class="px-4 py-3"><a href="{{ route('admin.support.show', $m) }}" class="text-brand hover:underline">{{ \Illuminate\Support\Str::limit($m->subject, 80) }}</a></td>
                        <td class="px-4 py-3">{{ $m->replies_count }}</td>
                        <td class="px-4 py-3 {{ ['new' => 'text-amber-700', 'answered' => 'text-green-700', 'closed' => 'text-slate-400'][$m->status] }}">{{ ucfirst($m->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No messages.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
