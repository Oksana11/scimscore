<?php

namespace App\Models;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Models\Concerns\HasBlindIndexes;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Anonymous patient. No names, national IDs, phones, addresses or emails.
 * research_id and alias are encrypted at rest; search uses blind indexes.
 */
#[Fillable(['research_id', 'alias', 'gender', 'birth_year', 'first_nli', 'first_ais', 'first_ams'])]
#[Hidden(['research_id_bidx', 'alias_bidx'])]
#[RouteKey('public_id')]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasBlindIndexes, HasFactory, HasUlids;

    /** @var list<string> */
    protected array $blindIndexed = ['research_id', 'alias'];

    protected function casts(): array
    {
        return [
            'research_id' => 'encrypted',
            'alias' => 'encrypted',
            'gender' => Gender::class,
            'birth_year' => 'integer',
            'first_nli' => NeurologicalLevel::class,
            'first_ais' => AisGrade::class,
            'first_ams' => 'integer',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        // Human-readable system ID, e.g. SCIM-2026-000184
        static::created(function (self $patient): void {
            $patient->system_id = sprintf('SCIM-%s-%06d', $patient->created_at->format('Y'), $patient->id);
            $patient->saveQuietly();
        });
    }

    /** @param  Builder<self>  $query */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where($this->qualifyColumn('user_id'), $user->id);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /** @return HasMany<Assessment, $this> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
