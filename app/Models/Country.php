<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'is_active'])]
#[WithoutIncrementing]
#[WithoutTimestamps]
class Country extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
