<?php

namespace App\Support\Security;

final class Keys
{
    public static function generate(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    public static function decode(string $value): string
    {
        if (str_starts_with($value, 'base64:')) {
            $decoded = base64_decode(substr($value, 7), true);

            return $decoded === false ? '' : $decoded;
        }

        return $value;
    }
}
