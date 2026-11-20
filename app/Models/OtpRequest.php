<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One-time code. Stores only a blind index of the email and an HMAC of the code.
 */
#[Fillable(['purpose', 'email_bidx', 'code_hash', 'attempts', 'expires_at', 'consumed_at'])]
class OtpRequest extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
