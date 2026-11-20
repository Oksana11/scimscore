<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
    <tr><td style="padding:24px">
        <p style="margin:0 0 4px;font-weight:bold">SCIMSCORE</p>
        <h1 style="margin:0 0 16px;font-size:20px">{{ $tpl->heading }}</h1>
        @foreach ($tpl->paragraphs as $p)
            <p style="margin:0 0 12px;font-size:14px">{{ $p }}</p>
        @endforeach
    </td></tr>
</table>
@include('emails._footer')
</body>
</html>
