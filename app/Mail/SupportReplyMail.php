<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportReply;
use App\Support\Mail\EmailTemplates;
use App\Support\Mail\RenderedEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SupportReplyMail extends Mailable
{
    private ?RenderedEmail $tpl = null;

    public function __construct(public readonly SupportMessage $supportMessage, public readonly SupportReply $reply) {}

    private function tpl(): RenderedEmail
    {
        return $this->tpl ??= EmailTemplates::render('support_reply');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tpl()->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.support-reply', with: [
            'tpl' => $this->tpl(),
            'replyLines' => EmailTemplates::paragraphs($this->reply->body),
            'originalLines' => EmailTemplates::paragraphs($this->supportMessage->message),
        ]);
    }
}
