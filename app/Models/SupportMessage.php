<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Message sent through the contact form. Visible to administrators only.
 */
#[Fillable(['user_id', 'email', 'subject', 'message', 'status'])]
#[RouteKey('public_id')]
class SupportMessage extends Model
{
    use HasUlids;

    public const STATUSES = ['new', 'answered', 'closed'];

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'subject' => 'encrypted',
            'message' => 'encrypted',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SupportReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class)->orderBy('id');
    }
}
