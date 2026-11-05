<?php

namespace App\Scoring;

use App\Enums\Gender;
use App\Enums\ScimVersion;

/**
 * SCIM scoring. Pure calculation, no I/O besides reading the questionnaire definition.
 *
 * SCI-ARMI (customer specification "SCIM", Google Doc — the only formula in use, for SCIM III and IV):
 *   SCI-ARMI = 100 · SCIM / (30.6 + 1.225·AMS − 0.00474·AMS² − 0.126·Age − 1.96·Sex), Sex: male 0, female 1.
 *   Sections (self-care, respiration/sphincter, mobility): plain sums only.
 *
 * Grade A–H (SCIM III only): nearest of 8 reference profiles of the original
 * scimscore.com (sum of absolute item differences).
 *
 * Bump CALC_VERSION whenever a formula changes: it is stored with every assessment.
 */
final class ScimCalculator
{
    public const CALC_VERSION = [
        'III' => 'iii-2026.1',
        'IV' => 'iv-2026.1',
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

        $sciarmi = $this->sciarmi($total, $input);

        [$grade, $gradeTotal] = $this->grade($definition, $answers);

        return new ScimResult(
            version: $version,
            calcVersion: self::CALC_VERSION[$version->value],
            total: $total,
            selfCare: $sums['self_care'],
            respirationSphincter: $sums['respiration_sphincter'],
            mobility: $sums['mobility'],
            sciarmiTotal: $sciarmi,
            sciarmiSelfCare: null,
            sciarmiRespirationSphincter: null,
            sciarmiMobility: null,
            grade: $grade,
            gradeReferenceTotal: $gradeTotal,
        );
    }

    private function sciarmi(int $total, ScimInput $input): ?float
    {
        if ($input->ams === null || $input->age === null || $input->gender === null) {
            return null;
        }

        $sex = $input->gender === Gender::Female ? 1 : 0;

        return $this->ratio($total, 30.6 + 1.225 * $input->ams - 0.00474 * $input->ams ** 2 - 0.126 * $input->age - 1.96 * $sex);
    }

    /**
     * The expected score can be zero or negative for implausible inputs
     * (e.g. very old age with AMS 0): the index is undefined there.
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
