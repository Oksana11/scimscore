<?php

namespace App\Scoring;

use App\Enums\Gender;

/**
 * Patient data used by the formulas (everything else is for statistics only).
 */
final readonly class ScimInput
{
    /**
     * @param  array<string, int>  $answers  Item key (v1..v19) => score
     * @param  int|null  $ams  ASIA motor score 0–100; SCI-ARMI is not calculated without it
     */
    public function __construct(
        public array $answers,
        public ?int $ams = null,
        public ?int $age = null,
        public ?Gender $gender = null,
    ) {}
}
