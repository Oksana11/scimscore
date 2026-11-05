<?php

namespace App\Mail;

use App\Models\Assessment;
use App\Scoring\ScimDefinition;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AssessmentResultMail extends Mailable
{
    public function __construct(public readonly Assessment $assessment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'SCIM assessment result');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.assessment-result', with: [
            'definition' => ScimDefinition::for($this->assessment->scim_version),
        ]);
    }
}
