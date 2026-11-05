<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects values that look like direct identifiers: emails, phone numbers,
 * national ID numbers (9+ digits in a row, ignoring spaces and dashes).
 */
class NotIdentifying implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (str_contains($value, '@')) {
            $fail('Do not enter email addresses here.');

            return;
        }

        if (preg_match('/\d{9,}/', preg_replace('/[\s\-().+]/', '', $value) ?? '')) {
            $fail('Do not enter phone numbers or ID numbers here.');
        }
    }
}
