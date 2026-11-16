<?php

namespace App\Mail;

use App\Models\Assessment;
use App\Scoring\ScimDefinition;
use App\Support\Mail\EmailTemplates;
use App\Support\Mail\RenderedEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AssessmentResultMail extends Mailable
{
    private ?RenderedEmail $tpl = null;

    public function __construct(public readonly Assessment $assessment) {}

    private function tpl(): RenderedEmail
    {
        return $this->tpl ??= EmailTemplates::render('assessment_result');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tpl()->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.assessment-result', with: [
            'definition' => ScimDefinition::for($this->assessment->scim_version),
            'tpl' => $this->tpl(),
        ]);
    }
}
