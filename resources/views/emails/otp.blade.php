<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
    <tr><td style="padding:24px">
        <p style="margin:0 0 4px;font-weight:bold">SCIMSCORE</p>
        <h1 style="margin:0 0 16px;font-size:20px">
            @if ($purpose === \App\Enums\OtpPurpose::EmailChange) Confirm your new email address @else Your one-time code @endif
        </h1>
        <p style="margin:0 0 16px;font-size:14px">
            @if ($purpose === \App\Enums\OtpPurpose::EmailChange)
                A request was made to change the sign-in email of a SCIMSCORE account to this address. Enter the code on your Profile page to confirm.
            @else
                Use the code below to sign in to SCIMSCORE. If this email isn't registered yet, entering the code will create your account.
            @endif
        </p>
        <p style="margin:0 0 8px;font-size:32px;font-weight:bold;letter-spacing:8px;font-family:Courier New,monospace">{{ $code }}</p>
        <p style="margin:0 0 16px;font-size:13px;color:#475569">Valid for {{ $ttl }} minutes · single use</p>
        <p style="margin:0;font-size:13px;color:#475569">
            Didn't request this? You can safely ignore this email — nobody can access your account without this code.
            Never share it with anyone, including SCIMSCORE staff.
        </p>
    </td></tr>
</table>
<p style="max-width:480px;margin:12px auto 0;font-size:12px;color:#64748b">This is an automated message — please don't reply.</p>
</body>
</html>
