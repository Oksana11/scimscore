<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'sort', 'is_active'])]
#[WithoutTimestamps]
class Profession extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort');
    }
}
