<?php

namespace App\Scoring;

use App\Enums\Gender;
use App\Enums\ScimVersion;

/**
 * SCIM scoring. Pure calculation, no I/O besides reading the questionnaire definition.
 *
 * SCIM III (formulas of the original scimscore.com, 2012):
 *   SCI-ARMI per section = 100 · score / f(AMS), f = a + b·AMS + c·AMS²
 *   Grade A–H = nearest reference profile (sum of absolute item differences).
 *
 * SCIM IV (customer specification "SCIM", Google Doc):
 *   SCI-ARMI = 100 · SCIM / (30.6 + 1.225·AMS − 0.00474·AMS² − 0.126·Age − 1.96·Sex), Sex: male 0, female 1.
 *   Sections: plain sums only.
 *
 * Bump CALC_VERSION whenever a formula changes: it is stored with every assessment.
 */
final class ScimCalculator
{
    public const CALC_VERSION = [
        'III' => 'iii-2026.1',
        'IV' => 'iv-2026.1',
    ];

    /** SCIM III SCI-ARMI denominators: [a, b (AMS), c (AMS²)]. */
    private const ARMI_III = [
        'total' => [7.045, 1.615, -0.007],
        'self_care' => [8.139, 0.216, -0.001],
        'respiration_sphincter' => [14.097, 0.58, -0.003],
        'mobility' => [-4.293, 0.625, -0.002],
    ];

    public function calculate(ScimVersion $version, ScimInput $input): ScimResult
    {
        $definition = ScimDefinition::for($version);
        $answers = $definition->validate($input->answers);

        $sums = [];
        foreach ($definition->sectionItems() as $section => $keys) {
            $sums[$section] = array_sum(array_map(fn ($k) => $answers[$k], $keys));
        }
        $total = array_sum($sums);

        $armi = match ($version) {
            ScimVersion::III => $this->armiIII($sums, $total, $input->ams),
            ScimVersion::IV => $this->armiIV($total, $input),
        };

        [$grade, $gradeTotal] = $this->grade($definition, $answers);

        return new ScimResult(
            version: $version,
            calcVersion: self::CALC_VERSION[$version->value],
            total: $total,
            selfCare: $sums['self_care'],
            respirationSphincter: $sums['respiration_sphincter'],
            mobility: $sums['mobility'],
            sciarmiTotal: $armi['total'],
            sciarmiSelfCare: $armi['self_care'],
            sciarmiRespirationSphincter: $armi['respiration_sphincter'],
            sciarmiMobility: $armi['mobility'],
            grade: $grade,
            gradeReferenceTotal: $gradeTotal,
        );
    }

    /**
     * @param  array<string, int>  $sums
     * @return array<string, float|null>
     */
    private function armiIII(array $sums, int $total, ?int $ams): array
    {
        $scores = ['total' => $total] + $sums;
        $out = [];

        foreach (self::ARMI_III as $key => [$a, $b, $c]) {
            $out[$key] = $ams === null ? null : $this->ratio($scores[$key], $a + $b * $ams + $c * $ams ** 2);
        }

        return $out;
    }

    /**
     * @return array<string, float|null>
     */
    private function armiIV(int $total, ScimInput $input): array
    {
        $value = null;

        if ($input->ams !== null && $input->age !== null && $input->gender !== null) {
            $sex = $input->gender === Gender::Female ? 1 : 0;
            $value = $this->ratio($total, 30.6 + 1.225 * $input->ams - 0.00474 * $input->ams ** 2 - 0.126 * $input->age - 1.96 * $sex);
        }

        return ['total' => $value, 'self_care' => null, 'respiration_sphincter' => null, 'mobility' => null];
    }

    /**
     * Expected-score denominators can be zero or negative at very low AMS
     * (mobility in SCIM III when AMS < 7): the index is undefined there.
     */
    private function ratio(int $score, float $denominator): ?float
    {
        return $denominator > 0 ? round(100 * $score / $denominator, 2) : null;
    }

    /**
     * @param  array<string, int>  $answers
     * @return array{0: string|null, 1: int|null}
     */
    private function grade(ScimDefinition $definition, array $answers): array
    {
        $references = $definition->gradeReferences();

        if ($references === null) {
            return [null, null];
        }

        $vector = array_values($answers);
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($references as $grade => $reference) {
            $distance = 0;
            foreach ($reference as $i => $value) {
                $distance += abs($vector[$i] - $value);
            }

            // Strictly smaller: on a tie the lower grade wins (same as the original site).
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $grade;
            }
        }

        return [$best, $best === null ? null : array_sum($references[$best])];
    }
}
