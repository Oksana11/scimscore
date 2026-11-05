<?php

namespace App\Models;

use App\Models\Concerns\HasBlindIndexes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * System administrator. Not a doctor: separate table and guard.
 * Created only from the console: php artisan admin:create
 */
#[Fillable(['email', 'name', 'is_active'])]
#[Hidden(['email_bidx', 'totp_secret', 'totp_last_step'])]
class Admin extends Authenticatable
{
    use HasBlindIndexes;

    /** @var list<string> */
    protected array $blindIndexed = ['email'];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'totp_secret' => 'encrypted',
            'totp_confirmed_at' => 'datetime',
            'totp_last_step' => 'integer',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function hasMfa(): bool
    {
        return $this->totp_secret !== null && $this->totp_confirmed_at !== null;
    }
}
