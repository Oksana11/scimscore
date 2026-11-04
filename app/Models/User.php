<?php

namespace App\Models;

use App\Models\Concerns\HasBlindIndexes;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Healthcare professional. Passwordless: signs in with email + OTP.
 * The email is stored encrypted; lookups go through the blind index:
 *   User::query()->whereBlind('email', $email)->first()
 */
#[Fillable(['email', 'email_verified_at', 'is_active', 'deactivated_at', 'last_login_at'])]
#[Hidden(['email_bidx'])]
#[RouteKey('public_id')]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasBlindIndexes, HasFactory, HasUlids, Notifiable;

    /** @var list<string> */
    protected array $blindIndexed = ['email'];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    // --- No passwords / "remember me" in this system ---

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    // --- Relations ---

    /** @return HasOne<UserProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /** @return HasMany<Patient, $this> */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /** @return HasMany<Assessment, $this> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function institutionId(): ?int
    {
        return $this->profile?->institution_id;
    }
}
