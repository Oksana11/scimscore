<?php

namespace App\Enums;

enum AssessmentSource: string
{
    /** Quick Assessment without an account. */
    case Quick = 'quick';

    /** Saved to a patient in a user's account. */
    case Account = 'account';
}
