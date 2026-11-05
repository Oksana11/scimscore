<?php

namespace App\Mail;

use App\Enums\OtpPurpose;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The code is never put in the subject, so it does not show in inbox previews.
 */
class OtpCodeMail extends Mailable
{
    public function __construct(public readonly string $code, public readonly OtpPurpose $purpose) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->purpose) {
            OtpPurpose::Login => 'Your SCIMSCORE sign-in code',
            OtpPurpose::EmailChange => 'Confirm your new SCIMSCORE email address',
            OtpPurpose::QuickResultEmail => 'Your SCIMSCORE verification code',
            OtpPurpose::AdminLogin => 'Your SCIMSCORE administrator sign-in code',
        });
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: [
            'ttl' => (int) config('scim.otp.ttl_minutes'),
        ]);
    }
}
