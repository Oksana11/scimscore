<?php

namespace App\Mail;

use App\Enums\OtpPurpose;
use App\Support\Mail\EmailTemplates;
use App\Support\Mail\RenderedEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The code is never put in the subject, so it does not show in inbox previews.
 * Texts are editable in the admin panel; the code block is fixed in the layout.
 */
class OtpCodeMail extends Mailable
{
    private ?RenderedEmail $tpl = null;

    public function __construct(public readonly string $code, public readonly OtpPurpose $purpose) {}

    private function tpl(): RenderedEmail
    {
        return $this->tpl ??= EmailTemplates::render(EmailTemplates::keyForOtp($this->purpose));
    }

    public function envelope(): Envelope
    {
        // Defensive: a code must never leak into the subject, even if an admin typed one in.
        return new Envelope(subject: str_replace($this->code, '', $this->tpl()->subject));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: ['tpl' => $this->tpl()]);
    }
}
