<?php

namespace App\Services\Patients;

use App\Enums\AisGrade;
use App\Enums\Gender;
use App\Enums\NeurologicalLevel;

/**
 * Identification + clinical fields used to create a patient and to look for duplicates.
 */
final readonly class PatientData
{
    public function __construct(
        public string $researchId,
        public string $alias,
        public Gender $gender,
        public int $birthYear,
        public ?NeurologicalLevel $currentNli = null,
        public ?AisGrade $currentAis = null,
        public ?int $currentAms = null,
        public ?NeurologicalLevel $firstNli = null,
        public ?AisGrade $firstAis = null,
        public ?int $firstAms = null,
    ) {}
}
