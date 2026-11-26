<?php

namespace App\Scoring;

use InvalidArgumentException;

final class InvalidScimAnswers extends InvalidArgumentException
{
    /**
     * @param  array<string, string>  $errors  Item key => message
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Invalid SCIM answers: '.implode('; ', array_map(
            fn ($k, $v) => "{$k}: {$v}", array_keys($errors), $errors
        )));
    }
}
