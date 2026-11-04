<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to the OLD address after a confirmed change. Does not reveal the new address.
 */
class EmailChangedMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SCIMSCORE sign-in email was changed');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-changed');
    }
}
