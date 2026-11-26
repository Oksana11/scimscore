<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>SCIMSCORE statistics</title>@include('pdf._style')</head>
<body>
<div class="footer">SCIMSCORE · generated {{ now()->format('d M Y H:i') }} UTC</div>
<h1>SCIMSCORE statistics</h1>
<p class="muted">Assessments {{ $filters['date_from'] ?? 'from start' }} – {{ $filters['date_to'] ?? 'today' }} · period: {{ $filters['period'] }}</p>
@foreach ($breakdowns as $title => $rows)
    <h2>{{ $title }}</h2>
    <table>
        <tr><th>Value</th><th class="num">Count</th></tr>
        @forelse ($rows as $r)<tr><td>{{ $r['label'] }}</td><td class="num">{{ $r['count'] }}</td></tr>@empty<tr><td colspan="2" class="muted">No data</td></tr>@endforelse
    </table>
@endforeach
</body>
</html>
