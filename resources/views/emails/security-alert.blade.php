<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;margin:0 auto;background:#ffffff;border:1px solid #fecaca;border-radius:12px">
    <tr><td style="padding:24px">
        <p style="margin:0 0 4px;font-weight:bold;color:#b91c1c">SCIMSCORE · Security alert</p>
        <h1 style="margin:0 0 16px;font-size:19px">{{ $title }}</h1>
        <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px">
            @foreach ($facts as $label => $value)
                <tr><td style="padding:3px 16px 3px 0;color:#64748b">{{ $label }}</td><td style="padding:3px 0">{{ $value }}</td></tr>
            @endforeach
        </table>
        <p style="margin:20px 0 0;font-size:13px">Review it in the admin panel: <a href="{{ url('/admin/audit?category=security') }}" style="color:#2563eb">Audit log → Security</a>.
            If this was not expected, deactivate the account concerned and contact the server administrator.</p>
    </td></tr>
</table>
<p style="max-width:520px;margin:12px auto 0;font-size:12px;color:#64748b">You receive this because you are a SCIMSCORE administrator. The same alert is sent at most once per hour.</p>
</body>
</html>
