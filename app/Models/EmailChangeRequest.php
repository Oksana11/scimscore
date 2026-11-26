<?php

namespace App\Models;

use App\Models\Concerns\HasBlindIndexes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'new_email', 'code_hash', 'attempts', 'expires_at', 'confirmed_at'])]
#[Hidden(['new_email_bidx', 'code_hash'])]
class EmailChangeRequest extends Model
{
    use HasBlindIndexes;

    const UPDATED_AT = null;

    /** @var list<string> */
    protected array $blindIndexed = ['new_email'];

    /** Same index space as users.email, so uniqueness can be checked directly. */
    protected function blindIndexContext(string $field): string
    {
        return 'users.email';
    }

    protected function casts(): array
    {
        return [
            'new_email' => 'encrypted',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
