<?php

namespace App\Services\Auth;

/**
 * 6-digit one-time codes. Only an HMAC of the code is ever stored.
 */
final class OtpCode
{
    public static function generate(): string
    {
        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  string  $scope  Binds the hash to its purpose and recipient (e.g. "login|<email bidx>").
     */
    public static function hash(string $code, string $scope): string
    {
        return hash_hmac('sha256', $scope.'|'.$code, (string) config('app.key'));
    }

    public static function matches(string $code, string $scope, string $hash): bool
    {
        return hash_equals($hash, self::hash(self::normalize($code), $scope));
    }

    public static function normalize(string $code): string
    {
        return preg_replace('/\D+/', '', $code) ?? '';
    }
}
