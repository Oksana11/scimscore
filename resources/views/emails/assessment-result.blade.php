@php $r = $assessment->result; @endphp
<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
    <tr><td style="padding:24px">
        <p style="margin:0 0 4px;font-weight:bold">SCIMSCORE</p>
        <h1 style="margin:0 0 16px;font-size:20px">{{ $tpl->heading }} · SCIM {{ $assessment->scim_version->value }}</h1>
        @if ($assessment->patient_code)
            <p style="margin:0 0 12px;font-size:14px">Patient code: <strong>{{ $assessment->patient_code }}</strong></p>
        @endif
        @if ($assessment->patient)
            <p style="margin:0 0 12px;font-size:14px">
                Patient: <strong>{{ $assessment->patient->system_id }}</strong> · Research ID <strong>{{ $assessment->patient->research_id }}</strong>
            </p>
        @endif
        <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="font-size:14px;border-collapse:collapse">
            <tr><td>Assessment date</td><td align="right">{{ $assessment->assessed_on->format('d M Y') }}</td></tr>
            <tr><td>Patient</td><td align="right">{{ $assessment->gender->label() }}, {{ $assessment->age }} y · {{ $assessment->nli?->value }} · AIS {{ $assessment->ais?->value }} · AMS {{ $assessment->ams ?? '—' }}</td></tr>
            <tr style="border-top:1px solid #e2e8f0"><td>Self-Care</td><td align="right">{{ $r->self_care }} / 20</td></tr>
            <tr><td>Respiration and Sphincter Management</td><td align="right">{{ $r->respiration_sphincter }} / 40</td></tr>
            <tr><td>Mobility</td><td align="right">{{ $r->mobility }} / 40</td></tr>
            <tr style="border-top:1px solid #e2e8f0"><td><strong>SCIM total</strong></td><td align="right"><strong>{{ $r->total }} / 100</strong></td></tr>
            <tr><td><strong>SCI-ARMI</strong></td><td align="right"><strong>{{ $r->sciarmi_total ?? '—' }}</strong></td></tr>
            <tr><td><strong>SCIM grade</strong></td><td align="right"><strong>{{ $r->grade ?? '—' }}</strong></td></tr>
        </table>
        <p style="margin:16px 0 0;font-size:12px;color:#64748b">Item answers:
            @foreach ($assessment->answers as $k => $v){{ strtoupper($k) }}={{ $v }}@if (! $loop->last), @endif @endforeach
        </p>
    </td></tr>
</table>
@foreach ($tpl->paragraphs as $p)
    <p style="max-width:520px;margin:12px auto 0;font-size:13px;color:#334155">{{ $p }}</p>
@endforeach
@include('emails._footer', ['width' => 520])
</body>
</html>
