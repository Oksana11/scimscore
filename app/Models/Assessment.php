<?php

namespace App\Models;

use App\Enums\AisGrade;
use App\Enums\AssessmentSource;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One SCIM assessment. Item answers are encrypted at rest; calculated
 * results live in assessment_results (plain numbers, used for statistics).
 */
#[Fillable([
    'source', 'user_id', 'patient_id', 'consent_id', 'scim_version', 'assessed_on', 'is_first',
    'gender', 'age', 'nli', 'ais', 'ams', 'answers', 'calc_version',
    'access_token_hash', 'access_expires_at', 'legacy_ref',
])]
#[Hidden(['access_token_hash', 'legacy_ref'])]
#[RouteKey('public_id')]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'source' => AssessmentSource::class,
            'scim_version' => ScimVersion::class,
            'assessed_on' => 'date',
            'is_first' => 'boolean',
            'gender' => Gender::class,
            'age' => 'integer',
            'nli' => NeurologicalLevel::class,
            'ais' => AisGrade::class,
            'ams' => 'integer',
            'answers' => 'encrypted:array',
            'access_expires_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
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

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<UserConsent, $this> */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(UserConsent::class);
    }

    /** @return HasOne<AssessmentResult, $this> */
    public function result(): HasOne
    {
        return $this->hasOne(AssessmentResult::class);
    }
}
