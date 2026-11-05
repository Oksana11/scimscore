<?php

namespace App\Scoring;

use App\Enums\ScimVersion;

final readonly class ScimResult
{
    public function __construct(
        public ScimVersion $version,
        public string $calcVersion,
        public int $total,
        public int $selfCare,
        public int $respirationSphincter,
        public int $mobility,
        public ?float $sciarmiTotal,
        public ?float $sciarmiSelfCare,
        public ?float $sciarmiRespirationSphincter,
        public ?float $sciarmiMobility,
        public ?string $grade,
        /** Total score of the grade's reference profile (the "median" shown by the old site). */
        public ?int $gradeReferenceTotal,
    ) {}

    /**
     * Attributes for App\Models\AssessmentResult.
     *
     * @return array<string, int|float|string|null>
     */
    public function toResultAttributes(): array
    {
        return [
            'total' => $this->total,
            'self_care' => $this->selfCare,
            'respiration_sphincter' => $this->respirationSphincter,
            'mobility' => $this->mobility,
            'sciarmi_total' => $this->sciarmiTotal,
            'sciarmi_self_care' => $this->sciarmiSelfCare,
            'sciarmi_respiration_sphincter' => $this->sciarmiRespirationSphincter,
            'sciarmi_mobility' => $this->sciarmiMobility,
            'grade' => $this->grade,
        ];
    }
}
