<?php

namespace App\Models\Concerns;

use App\Support\Security\BlindIndex;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Keeps "<field>_bidx" columns in sync with encrypted fields and allows
 * exact-match queries on them without decrypting anything.
 *
 * The model declares: protected array $blindIndexed = ['email'];
 * Index column name: "{field}_bidx", HMAC context: "{table}.{field}" by default.
 */
trait HasBlindIndexes
{
    public static function bootHasBlindIndexes(): void
    {
        static::saving(function (self $model): void {
            foreach ($model->blindIndexed as $field) {
                if ($model->isDirty($field) || ($model->getAttributes()[$field.'_bidx'] ?? null) === null) {
                    $model->setAttribute($field.'_bidx', $model->blindIndexFor($field, $model->getAttribute($field)));
                }
            }
        });
    }

    public function blindIndexFor(string $field, ?string $value): ?string
    {
        if (! in_array($field, $this->blindIndexed, true)) {
            throw new InvalidArgumentException("Field [{$field}] has no blind index.");
        }

        return app(BlindIndex::class)->compute($value, $this->blindIndexContext($field));
    }

    /**
     * HMAC context for a field. Override to share an index space between
     * tables (e.g. a pending new email must be comparable with users.email).
     */
    protected function blindIndexContext(string $field): string
    {
        return $this->getTable().'.'.$field;
    }

    /**
     * Usage: User::query()->whereBlind('email', $input)->first()
     */
    public function scopeWhereBlind(Builder $query, string $field, ?string $value): Builder
    {
        $index = $this->blindIndexFor($field, $value);

        return $index === null
            ? $query->whereRaw('1 = 0')
            : $query->where($this->qualifyColumn($field.'_bidx'), $index);
    }
}
