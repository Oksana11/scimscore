<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Security alert for administrators. Contains no personal or medical data.
 */
class SecurityAlertMail extends Mailable
{
    /**
     * @param  array<string, string>  $facts
     */
    public function __construct(public readonly string $title, public readonly array $facts) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[SCIMSCORE security] '.$this->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.security-alert');
    }
}
