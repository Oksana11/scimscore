<?php

namespace App\Services\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Duplicate prevention (spec, Appendix A). Not a 100% guarantee — the system holds
 * no identifying data — but it substantially reduces duplicate records:
 *
 *  1. Same Research ID in the user's list or at the user's institution → creation is blocked.
 *  2. "Similar" patient (same gender and year of birth, plus the same alias, or the same
 *     level + AIS at the current or first assessment, or the same AMS) →
 *       - in the user's own list: the matches are shown;
 *       - under another user of the same institution: a warning only, no details,
 *         no owner, no access.
 *
 * All comparisons of encrypted fields use blind indexes; nothing is decrypted.
 */
final class DuplicateDetector
{
    public function check(User $user, PatientData $data, ?Patient $except = null): DuplicateCheckResult
    {
        $institutionId = $user->institutionId();
        $probe = new Patient;
        $researchIdx = $probe->blindIndexFor('research_id', $data->researchId);
        $aliasIdx = $probe->blindIndexFor('alias', $data->alias);

        $base = fn () => Patient::query()->when($except, fn ($q) => $q->whereKeyNot($except->id));

        $sameOwn = $base()->where('user_id', $user->id)->where('research_id_bidx', $researchIdx)->first();

        $sameInstitution = $institutionId !== null && $base()
            ->where('institution_id', $institutionId)
            ->where('user_id', '!=', $user->id)
            ->where('research_id_bidx', $researchIdx)
            ->exists();

        $candidates = $base()
            ->with(['assessments' => fn ($q) => $q->select(['id', 'patient_id', 'nli', 'ais', 'ams', 'assessed_on'])])
            ->where('gender', $data->gender->value)
            ->where('birth_year', $data->birthYear)
            ->where(function ($q) use ($user, $institutionId) {
                $q->where('user_id', $user->id);
                if ($institutionId !== null) {
                    $q->orWhere('institution_id', $institutionId);
                }
            })
            ->get()
            ->filter(fn (Patient $p) => $this->isSimilar($p, $data, $aliasIdx));

        /** @var Collection<int, Patient> $similarOwn */
        $similarOwn = $candidates->where('user_id', $user->id)->values();

        return new DuplicateCheckResult(
            researchIdTakenInAccount: $sameOwn !== null,
            researchIdTakenInInstitution: $sameInstitution,
            similarOwn: $similarOwn,
            similarInInstitution: $candidates->contains(fn (Patient $p) => $p->user_id !== $user->id),
            sameResearchIdOwn: $sameOwn,
        );
    }

    private function isSimilar(Patient $p, PatientData $d, ?string $aliasIdx): bool
    {
        if ($aliasIdx !== null && hash_equals($p->alias_bidx, $aliasIdx)) {
            return true;
        }

        // Clinical pairs known for the existing patient: first assessment + every saved assessment.
        $known = collect([[$p->first_nli, $p->first_ais, $p->first_ams]])
            ->merge($p->assessments->map(fn ($a) => [$a->nli, $a->ais, $a->ams]));

        // Clinical pairs of the new patient.
        $new = array_filter([
            [$d->currentNli, $d->currentAis, $d->currentAms],
            [$d->firstNli, $d->firstAis, $d->firstAms],
        ], fn ($t) => $t[0] !== null && $t[1] !== null);

        foreach ($new as [$nli, $ais, $ams]) {
            foreach ($known as [$kNli, $kAis, $kAms]) {
                if ($kNli === $nli && $kAis === $ais && ($ams === null || $kAms === null || $kAms === $ams)) {
                    return true;
                }
            }
        }

        return false;
    }
}
