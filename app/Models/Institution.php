<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Institution from the Research Organization Registry (ror_id set)
 * or entered manually by a user (is_custom = true).
 */
#[Fillable(['ror_id', 'name', 'country_code', 'is_custom', 'created_by_user_id', 'merged_into_id'])]
class Institution extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_custom' => 'boolean'];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return BelongsTo<self, $this> */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    /** @return HasMany<UserProfile, $this> */
    public function profiles(): HasMany
    {
        return $this->hasMany(UserProfile::class);
    }
}
