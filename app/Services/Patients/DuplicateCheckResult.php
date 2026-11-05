<?php

namespace App\Services\Patients;

use App\Models\Patient;
use Illuminate\Support\Collection;

final readonly class DuplicateCheckResult
{
    /**
     * @param  bool  $researchIdTakenInAccount  Same Research ID already in this user's list (blocks creation).
     * @param  bool  $researchIdTakenInInstitution  Same Research ID used by another user of the same institution (blocks creation).
     * @param  Collection<int, Patient>  $similarOwn  Similar patients in this user's own list (may be shown).
     * @param  bool  $similarInInstitution  Similar patient exists under another user of the institution (details never shown).
     */
    public function __construct(
        public bool $researchIdTakenInAccount,
        public bool $researchIdTakenInInstitution,
        public Collection $similarOwn,
        public bool $similarInInstitution,
        public ?Patient $sameResearchIdOwn = null,
    ) {}

    public function blocked(): bool
    {
        return $this->researchIdTakenInAccount || $this->researchIdTakenInInstitution;
    }

    public function hasWarnings(): bool
    {
        return $this->similarOwn->isNotEmpty() || $this->similarInInstitution;
    }
}
