<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SCIM assessment</title>
    @include('pdf._style')
</head>
<body>
@php $r = $assessment->result; @endphp
<div class="footer">SCIMSCORE · generated {{ now()->format('d M Y H:i') }} UTC · algorithm {{ $assessment->calc_version }}</div>

<h1>SCIM {{ $assessment->scim_version->value }} assessment</h1>
<p class="muted">
    @if ($assessment->patient)
        Patient {{ $assessment->patient->system_id }} · Research ID {{ $assessment->patient->research_id }} ·
        {{ $assessment->is_first ? 'First assessment' : 'Follow-up assessment' }}
    @else
        Quick assessment (not linked to a patient)
    @endif
</p>

<table>
    <tr><th>Assessment date</th><td>{{ $assessment->assessed_on->format('d M Y') }}</td>
        <th>Gender / age</th><td>{{ $assessment->gender->label() }}, {{ $assessment->age }} y</td></tr>
    <tr><th>Neurological level</th><td>{{ $assessment->nli?->value ?? '—' }}</td>
        <th>AIS / AMS</th><td>{{ $assessment->ais?->value ?? '—' }} / {{ $assessment->ams ?? '—' }}</td></tr>
</table>

<h2>Result</h2>
<table>
    <tr><td>Self-Care</td><td class="num">{{ $r->self_care }} / 20</td></tr>
    <tr><td>Respiration and Sphincter Management</td><td class="num">{{ $r->respiration_sphincter }} / 40</td></tr>
    <tr><td>Mobility</td><td class="num">{{ $r->mobility }} / 40</td></tr>
    <tr><td class="big">SCIM total</td><td class="num big">{{ $r->total }} / 100</td></tr>
    <tr><td class="big">SCI-ARMI</td><td class="num big">{{ $r->sciarmi_total ?? '—' }}</td></tr>
    <tr><td class="big">SCIM grade</td><td class="num big">{{ $r->grade ?? '—' }}</td></tr>
</table>

<h2>Answers</h2>
<table>
    <tr><th>Item</th><th class="num">Score</th><th>Answer</th></tr>
    @foreach ($definition->sections() as $section)
        <tr><td colspan="3"><strong>{{ $section['title'] }}</strong></td></tr>
        @foreach ($section['items'] as $item)
            @php $score = $assessment->answers[$item['key']] ?? null; @endphp
            <tr>
                <td>{{ $item['number'] }}. {{ isset($item['group']) ? \Illuminate\Support\Str::before($item['group'], ' (').' — ' : '' }}{{ \Illuminate\Support\Str::limit($item['title'], 70) }}</td>
                <td class="num">{{ $score }}</td>
                <td>{{ collect($item['options'])->firstWhere('score', $score)['label'] ?? '—' }}</td>
            </tr>
        @endforeach
    @endforeach
</table>
</body>
</html>
