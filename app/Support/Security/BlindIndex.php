<?php

namespace App\Support\Security;

use Normalizer;
use RuntimeException;

/**
 * Deterministic keyed hash (HMAC-SHA256) of a value, for exact-match search
 * and duplicate detection over encrypted columns. The original value cannot
 * be recovered from the index, and without the key it cannot be brute-forced.
 */
final class BlindIndex
{
    public function __construct(private readonly string $key)
    {
        if (strlen($key) < 32) {
            throw new RuntimeException('BLIND_INDEX_KEY is missing or shorter than 32 bytes. Run: php artisan security:generate-keys');
        }
    }

    public static function fromConfig(): self
    {
        return new self(Keys::decode((string) config('security.blind_index_key')));
    }

    /**
     * @param  string  $context  Field name (e.g. "patients.research_id") so equal values in different fields get different indexes.
     */
    public function compute(?string $value, string $context): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = self::normalize($value);

        if ($normalized === '') {
            return null;
        }

        return hash_hmac('sha256', $context."\x1F".$normalized, $this->key);
    }

    public static function normalize(string $value): string
    {
        $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? $value;

        return mb_strtolower($value, 'UTF-8');
    }
}
