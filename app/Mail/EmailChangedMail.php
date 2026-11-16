<?php

namespace App\Mail;

use App\Support\Mail\EmailTemplates;
use App\Support\Mail\RenderedEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to the OLD address after a confirmed change. Does not reveal the new address.
 */
class EmailChangedMail extends Mailable
{
    private ?RenderedEmail $tpl = null;

    private function tpl(): RenderedEmail
    {
        return $this->tpl ??= EmailTemplates::render('email_changed');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tpl()->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-changed', with: ['tpl' => $this->tpl()]);
    }
}
