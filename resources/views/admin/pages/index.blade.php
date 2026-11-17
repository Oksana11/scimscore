@extends('admin.layout')
@section('title', 'Pages')
@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">Pages</h1>
        <a href="{{ route('admin.pages.create') }}" class="btn-primary">+ New page</a>
    </div>

    <section class="card mt-4 overflow-x-auto p-0">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase text-slate-500">
                    <th class="px-4 py-3">Title</th><th class="px-4 py-3">Address</th><th class="px-4 py-3">Menu</th><th class="px-4 py-3">Order</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pages as $p)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 font-medium">{{ $p->title }}</td>
                        <td class="px-4 py-3"><a href="{{ $p->url() }}" class="text-brand hover:underline" target="_blank" rel="noopener">/{{ $p->slug }}</a></td>
                        <td class="px-4 py-3">{{ collect(['Header' => $p->show_in_header, 'Footer' => $p->show_in_footer])->filter()->keys()->join(', ') ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $p->sort }}</td>
                        <td class="px-4 py-3 {{ $p->is_published ? 'text-green-700' : 'text-slate-400' }}">{{ $p->is_published ? 'Published' : 'Hidden' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.pages.edit', $p) }}" class="btn-primary px-3 py-1.5 text-xs">Edit</a>
                            @unless ($p->is_system)
                                <form method="POST" action="{{ route('admin.pages.destroy', $p) }}" class="ml-1 inline" data-confirm="Delete the page “{{ $p->title }}”?">@csrf @method('DELETE')
                                    <button class="btn-secondary px-3 py-1.5 text-xs text-red-700">Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
