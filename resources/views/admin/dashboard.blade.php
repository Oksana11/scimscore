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

    @php
        $period = $breakdowns[\App\Services\Admin\AdminStatistics::PERIOD] ?? [];
        $lists = collect($breakdowns)->except(\App\Services\Admin\AdminStatistics::PERIOD);
        $visible = 8;
    @endphp

    {{-- Assessments over time: column chart (SVG attributes only — CSP forbids inline styles) --}}
    <section class="card mt-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-semibold">{{ \App\Services\Admin\AdminStatistics::PERIOD }} ({{ $filters['period'] }})</h2>
            <p class="text-sm text-slate-500">{{ count($period) }} periods · {{ collect($period)->sum('count') }} assessments</p>
        </div>
        @if (count($period))
            @php
                $pmax = max(1, collect($period)->max('count'));
                $n = count($period);
                $bw = 10; $gap = 2; $h = 120;
                $step = max(1, (int) ceil($n / 12)); // at most ~12 axis labels
            @endphp
            <svg class="mt-4 h-48 w-full" viewBox="0 0 {{ $n * ($bw + $gap) }} {{ $h + 16 }}" preserveAspectRatio="none" role="img" aria-label="Assessments per period">
                @foreach ($period as $i => $p)
                    @php $bh = max(1, round($p['count'] / $pmax * $h, 1)); @endphp
                    <rect x="{{ $i * ($bw + $gap) }}" y="{{ $h - $bh }}" width="{{ $bw }}" height="{{ $bh }}" class="fill-blue-600"><title>{{ $p['label'] }}: {{ $p['count'] }}</title></rect>
                @endforeach
            </svg>
            <div class="mt-1 flex justify-between text-xs text-slate-500">
                @foreach ($period as $i => $p)
                    @if ($i % $step === 0 || $i === $n - 1)<span>{{ $p['label'] }}</span>@endif
                @endforeach
            </div>
            <details class="mt-3 text-sm">
                <summary class="cursor-pointer text-blue-700">Show table</summary>
                <div class="mt-2 grid max-h-72 gap-x-6 overflow-auto sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($period as $p)
                        <div class="flex justify-between border-b border-slate-100 py-1"><span>{{ $p['label'] }}</span><span class="tabular-nums">{{ $p['count'] }}</span></div>
                    @endforeach
                </div>
            </details>
        @else
            <p class="mt-3 text-sm text-slate-500">No data</p>
        @endif
    </section>

    {{-- Ranked lists: top rows visible, the rest folded --}}
    <div class="mt-6 grid items-start gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($lists as $title => $rows)
            @php $max = max(1, collect($rows)->max('count')); $total = collect($rows)->sum('count'); @endphp
            <section class="card">
                <div class="flex items-baseline justify-between gap-2">
                    <h2 class="font-semibold">{{ $title }}</h2>
                    <span class="text-xs text-slate-500">{{ $total }}</span>
                </div>
                @if (count($rows))
                    @foreach ([array_slice($rows, 0, $visible), array_slice($rows, $visible)] as $part => $chunk)
                        @if ($part === 1 && count($chunk))
                            <details class="mt-1"><summary class="cursor-pointer text-sm text-blue-700">Show {{ count($chunk) }} more</summary>
                        @endif
                        <table class="mt-2 w-full table-fixed text-sm">
                            @foreach ($chunk as $r)
                                <tr>
                                    <td class="w-1/2 truncate py-1 pr-2" title="{{ $r['label'] }}">{{ $r['label'] }}</td>
                                    <td class="py-1"><meter class="h-2 w-full" min="0" max="{{ $max }}" value="{{ $r['count'] }}"></meter></td>
                                    <td class="w-14 py-1 pl-2 text-right tabular-nums">{{ $r['count'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                        @if ($part === 1 && count($chunk))
                            </details>
                        @endif
                    @endforeach
                @else
                    <p class="mt-3 text-sm text-slate-500">No data</p>
                @endif
            </section>
        @endforeach
    </div>
@endsection
