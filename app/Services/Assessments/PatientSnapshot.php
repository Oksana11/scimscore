<?php

namespace App\Services\Assessments;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;

/**
 * Patient data at the time of an assessment (no identifiers).
 */
final readonly class PatientSnapshot
{
    public function __construct(
        public Gender $gender,
        public int $age,
        public ?int $ams = null,
        public ?NeurologicalLevel $nli = null,
        public ?AisGrade $ais = null,
    ) {}
}
