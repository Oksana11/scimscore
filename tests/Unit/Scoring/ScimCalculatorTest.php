<?php

namespace Tests\Unit\Scoring;

use App\Enums\Gender;
use App\Enums\ScimVersion;
use App\Scoring\InvalidScimAnswers;
use App\Scoring\ScimCalculator;
use App\Scoring\ScimDefinition;
use App\Scoring\ScimInput;
use Tests\TestCase;

class ScimCalculatorTest extends TestCase
{
    /** Reference profile E from the original site (total 50). */
    private const E = [3, 3, 1, 3, 0, 3, 10, 11, 5, 0, 4, 1, 0, 2, 2, 2, 0, 0, 0];

    private const H = [3, 3, 3, 4, 4, 3, 10, 15, 10, 5, 6, 2, 2, 8, 8, 8, 3, 2, 1];

    /** @param  list<int>  $vector */
    private function answers(array $vector): array
    {
        $out = [];
        foreach ($vector as $i => $v) {
            $out['v'.($i + 1)] = $v;
        }

        return $out;
    }

    private function calc(ScimVersion $version, ScimInput $input)
    {
        return (new ScimCalculator)->calculate($version, $input);
    }

    protected function tearDown(): void
    {
        ScimDefinition::override(ScimVersion::IV, null);
        parent::tearDown();
    }

    public function test_scim_iii_definition_has_19_items_and_section_maximums(): void
    {
        $def = ScimDefinition::for(ScimVersion::III);

        $this->assertCount(19, $def->allowedScores());
        $this->assertSame([0, 3, 6, 9, 11, 13, 15], $def->allowedScores()['v8']); // bladder: all 7 options

        $max = array_map('max', $def->allowedScores());
        $sections = $def->sectionItems();
        $this->assertSame(20, array_sum(array_intersect_key($max, array_flip($sections['self_care']))));
        $this->assertSame(40, array_sum(array_intersect_key($max, array_flip($sections['respiration_sphincter']))));
        $this->assertSame(40, array_sum(array_intersect_key($max, array_flip($sections['mobility']))));
    }

    public function test_scim_iii_sums_and_sci_armi(): void
    {
        $r = $this->calc(ScimVersion::III, new ScimInput($this->answers(self::E), ams: 50));

        $this->assertSame(50, $r->total);
        $this->assertSame(13, $r->selfCare);
        $this->assertSame(26, $r->respirationSphincter);
        $this->assertSame(11, $r->mobility);
        $this->assertSame(71.13, $r->sciarmiTotal);
        $this->assertSame(79.08, $r->sciarmiSelfCare);
        $this->assertSame(73.04, $r->sciarmiRespirationSphincter);
        $this->assertSame(50.1, $r->sciarmiMobility);
        $this->assertSame('iii-2026.1', $r->calcVersion);
    }

    public function test_full_independence_scores_100_and_grade_h(): void
    {
        $r = $this->calc(ScimVersion::III, new ScimInput($this->answers(self::H), ams: 100));

        $this->assertSame(100, $r->total);
        $this->assertSame('H', $r->grade);
        $this->assertSame(100, $r->gradeReferenceTotal);
    }

    public function test_each_reference_profile_gets_its_own_grade(): void
    {
        $refs = ScimDefinition::for(ScimVersion::III)->gradeReferences();

        foreach ($refs as $grade => $vector) {
            $r = $this->calc(ScimVersion::III, new ScimInput($this->answers($vector)));
            $this->assertSame($grade, $r->grade, "Profile {$grade}");
        }

        $this->assertSame(82, array_sum($refs['G'])); // corrected typo from the old database (v3 = 34)
    }

    public function test_sci_armi_is_null_without_ams_or_with_invalid_denominator(): void
    {
        $r = $this->calc(ScimVersion::III, new ScimInput($this->answers(self::E)));
        $this->assertNull($r->sciarmiTotal);

        $r = $this->calc(ScimVersion::III, new ScimInput($this->answers(self::E), ams: 0));
        $this->assertNull($r->sciarmiMobility); // −4.293 at AMS 0
        $this->assertNotNull($r->sciarmiTotal);
    }

    public function test_invalid_answers_are_rejected(): void
    {
        $answers = $this->answers(self::E);
        $answers['v8'] = 7;        // not an allowed bladder score
        unset($answers['v19']);    // missing
        $answers['v99'] = 1;       // unknown

        try {
            $this->calc(ScimVersion::III, new ScimInput($answers));
            $this->fail('Invalid answers were accepted');
        } catch (InvalidScimAnswers $e) {
            $this->assertArrayHasKey('v8', $e->errors);
            $this->assertArrayHasKey('v19', $e->errors);
            $this->assertArrayHasKey('v99', $e->errors);
        }
    }

    public function test_string_digits_are_accepted_other_types_are_not(): void
    {
        $answers = array_map('strval', $this->answers(self::E));
        $this->assertSame(50, $this->calc(ScimVersion::III, new ScimInput($answers))->total);

        $answers['v1'] = '1.5';
        $this->expectException(InvalidScimAnswers::class);
        $this->calc(ScimVersion::III, new ScimInput($answers));
    }

    public function test_scim_iv_sci_armi_uses_age_and_sex(): void
    {
        // Same structure as III until the customer provides the SCIM IV questionnaire.
        $iii = json_decode((string) file_get_contents(resource_path('scim/scim-iii.json')), true);
        unset($iii['grade_reference']);
        ScimDefinition::override(ScimVersion::IV, $iii);

        $male = $this->calc(ScimVersion::IV, new ScimInput($this->answers(self::E), ams: 50, age: 40, gender: Gender::Male));
        $female = $this->calc(ScimVersion::IV, new ScimInput($this->answers(self::E), ams: 50, age: 40, gender: Gender::Female));

        $this->assertSame(66.7, $male->sciarmiTotal);
        $this->assertSame(68.49, $female->sciarmiTotal);
        $this->assertNull($male->sciarmiSelfCare);
        $this->assertNull($male->grade);

        $noAge = $this->calc(ScimVersion::IV, new ScimInput($this->answers(self::E), ams: 50, gender: Gender::Male));
        $this->assertNull($noAge->sciarmiTotal);
    }
}
