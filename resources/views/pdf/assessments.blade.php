<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SCIM assessments</title>
    @include('pdf._style')
</head>
<body>
<div class="footer">SCIMSCORE · generated {{ now()->format('d M Y H:i') }} UTC</div>

<h1>SCIM assessments</h1>
<p class="muted">
    @php $active = array_filter($filters, fn ($v) => $v !== null && $v !== ''); @endphp
    Filters: @forelse ($active as $k => $v){{ str_replace('_', ' ', $k) }} = {{ $v }}@if (! $loop->last); @endif @empty none @endforelse
    · {{ count($assessments) }} assessments
</p>

<table>
    <tr>
        <th>Date</th><th>System ID</th><th>Research ID</th><th>Version</th><th>Type</th><th>Gender</th><th class="num">Born</th>
        <th>Level</th><th>AIS</th><th class="num">AMS</th><th class="num">SC</th><th class="num">RSM</th><th class="num">MOB</th>
        <th class="num">Total</th><th class="num">SCI-ARMI</th><th>Grade</th>
    </tr>
    @foreach ($assessments as $a)
        <tr>
            <td>{{ $a->assessed_on->format('Y-m-d') }}</td>
            <td>{{ $a->patient?->system_id }}</td>
            <td>{{ $a->patient?->research_id }}</td>
            <td>{{ $a->scim_version->value }}</td>
            <td>{{ $a->is_first ? 'First' : 'Follow-up' }}</td>
            <td>{{ $a->gender->label() }}</td>
            <td class="num">{{ $a->patient?->birth_year }}</td>
            <td>{{ $a->nli?->value }}</td>
            <td>{{ $a->ais?->value }}</td>
            <td class="num">{{ $a->ams ?? '—' }}</td>
            <td class="num">{{ $a->result->self_care }}</td>
            <td class="num">{{ $a->result->respiration_sphincter }}</td>
            <td class="num">{{ $a->result->mobility }}</td>
            <td class="num"><strong>{{ $a->result->total }}</strong></td>
            <td class="num">{{ $a->result->sciarmi_total ?? '—' }}</td>
            <td>{{ $a->result->grade ?? '—' }}</td>
        </tr>
    @endforeach
</table>
</body>
</html>
