<?php

namespace App\Support\Security;

/**
 * Time-based one-time passwords (RFC 6238; HMAC-SHA1, 30 s, 6 digits) —
 * compatible with Google Authenticator, Microsoft Authenticator, 1Password, etc.
 */
final class Totp
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function uri(string $secret, string $account, string $issuer = 'SCIMSCORE'): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account)
            .'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits='.self::DIGITS.'&period='.self::PERIOD;
    }

    public static function code(string $secret, ?int $step = null): string
    {
        $step ??= self::currentStep();
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * @return int|null the matching time step (store it to block replay), or null
     */
    public static function verify(string $secret, string $code, ?int $lastUsedStep = null, int $window = 1): ?int
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== self::DIGITS) {
            return null;
        }

        $now = self::currentStep();

        for ($i = -$window; $i <= $window; $i++) {
            $step = $now + $i;

            if ($lastUsedStep !== null && $step <= $lastUsedStep) {
                continue; // a code can be used only once
            }

            if (hash_equals(self::code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public static function currentStep(): int
    {
        return intdiv(now()->getTimestamp(), self::PERIOD);
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');
        $bits = '';
        foreach (str_split($secret) as $c) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int) bindec($byte));
            }
        }

        return $out;
    }
}
