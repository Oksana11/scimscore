@extends('admin.layout')
@section('title', 'Statistics')
@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-2xl font-semibold">Statistics</h1>
        <div class="flex gap-2">
            @foreach (['pdf' => 'PDF', 'xlsx' => 'Excel', 'csv' => 'CSV'] as $fmt => $label)
                <a href="{{ route('admin.statistics.export', ['format' => $fmt] + request()->query()) }}" class="btn-secondary">Export {{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-4">
        @foreach ($totals as $label => $value)
            <div class="card"><p class="text-xs tracking-wide text-slate-500 uppercase">{{ $label }}</p><p class="mt-1 text-3xl font-semibold tabular-nums">{{ $value }}</p></div>
        @endforeach
    </div>

    <form method="GET" class="card mt-6 flex flex-wrap items-end gap-4">
        <div><label class="label" for="d1">Assessments from</label><input id="d1" type="date" name="date_from" class="input" value="{{ $filters['date_from'] }}"></div>
        <div><label class="label" for="d2">to</label><input id="d2" type="date" name="date_to" class="input" value="{{ $filters['date_to'] }}"></div>
        <div><label class="label" for="per">Period</label>
            <select id="per" name="period" class="input">
                @foreach (['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $k => $l)<option value="{{ $k }}" @selected($filters['period'] === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <button class="btn-primary">Apply</button>
        @error('date_to') <p class="field-error w-full">{{ $message }}</p> @enderror
    </form>

    <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($breakdowns as $title => $rows)
            @php $max = max(1, collect($rows)->max('count')); @endphp
            <section class="card">
                <h2 class="font-semibold">{{ $title }}</h2>
                <table class="mt-3 w-full text-sm">
                    @forelse ($rows as $r)
                        <tr>
                            <td class="py-1 pr-2">{{ $r['label'] }}</td>
                            <td class="w-1/3 py-1"><meter class="h-2 w-full" min="0" max="{{ $max }}" value="{{ $r['count'] }}"></meter></td>
                            <td class="py-1 pl-2 text-right tabular-nums">{{ $r['count'] }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-2 text-slate-500">No data</td></tr>
                    @endforelse
                </table>
            </section>
        @endforeach
    </div>
@endsection
