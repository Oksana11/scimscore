@extends('admin.layout')
@section('title', 'Audit log')
@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-2xl font-semibold">Audit log</h1>
        <form method="POST" action="{{ route('admin.audit.verify') }}">@csrf<button class="btn-secondary">Verify integrity</button></form>
    </div>
    <nav class="mt-6 flex flex-wrap gap-2 text-sm">
        @foreach (\App\Support\Security\AuditCategories::LABELS as $key => $label)
            <a href="{{ route('admin.audit', array_filter(['category' => $key] + \Illuminate\Support\Arr::except($filters, 'category'))) }}"
               class="{{ $category === $key ? 'btn-primary' : 'btn-secondary' }} px-3 py-1.5">{{ $label }}</a>
        @endforeach
    </nav>
    <p class="mt-2 text-xs text-slate-500">“Activity” shows everything except page views. Page views are under “Views”.</p>
    <form method="GET" class="card mt-4 flex flex-wrap items-end gap-4">
        <input type="hidden" name="category" value="{{ $category }}">
        <div><label class="label" for="ac">Action starts with</label><input id="ac" name="action" class="input" value="{{ $filters['action'] ?? '' }}" placeholder="e.g. admin."></div>
        <div><label class="label" for="at">Actor type</label><select id="at" name="actor_type" class="input"><option value="">Any</option>@foreach (['guest', 'user', 'admin', 'system'] as $t)<option @selected(($filters['actor_type'] ?? '') === $t)>{{ $t }}</option>@endforeach</select></div>
        <div><label class="label" for="ai">Actor ID</label><input id="ai" name="actor_id" type="number" class="input" value="{{ $filters['actor_id'] ?? '' }}"></div>
        <button class="btn-primary">Filter</button>
    </form>
    <div class="card mt-6 overflow-x-auto p-0">
        <table class="w-full text-xs">
            <thead class="border-b border-slate-200 text-left text-slate-500"><tr>
                <th class="px-3 py-2">#</th><th class="px-3 py-2">Time (UTC)</th><th class="px-3 py-2">Actor</th><th class="px-3 py-2">Action</th><th class="px-3 py-2">Subject</th><th class="px-3 py-2">IP</th><th class="px-3 py-2">Context</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100 font-mono">
                @foreach ($logs as $l)
                    <tr class="{{ $l->action === 'access.denied' || str_ends_with($l->action, '_failed') ? 'bg-red-50' : '' }}">
                        <td class="px-3 py-2">{{ $l->id }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ \Illuminate\Support\Str::before($l->occurred_at, '.') }}</td>
                        <td class="px-3 py-2">{{ $l->actor_type }}{{ $l->actor_id ? '#'.$l->actor_id : '' }}</td>
                        @php($cat = \App\Support\Security\AuditCategories::of($l->action))
                        <td class="px-3 py-2 whitespace-nowrap"><span class="mr-1 rounded px-1.5 py-0.5 font-sans text-[10px] font-semibold uppercase {{ ['security' => 'bg-red-100 text-red-800', 'data' => 'bg-green-100 text-green-800', 'exports' => 'bg-amber-100 text-amber-800', 'admin' => 'bg-blue-100 text-blue-800', 'views' => 'bg-slate-100 text-slate-500'][$cat] }}">{{ $cat }}</span>{{ $l->action }}</td>
                        <td class="px-3 py-2">{{ $l->subject_type }}{{ $l->subject_id ? '#'.$l->subject_id : '' }}</td>
                        <td class="px-3 py-2">{{ $l->ip }}</td>
                        <td class="px-3 py-2 break-all">{{ $l->context }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
