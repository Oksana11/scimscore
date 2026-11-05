<?php

namespace Tests\Feature\Assessments;

use App\Enums\AssessmentSource;
use App\Mail\AssessmentResultMail;
use App\Models\Assessment;
use App\Models\Institution;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PatientFlowTest extends TestCase
{
    use RefreshDatabase;

    private const E = [3, 3, 1, 3, 0, 3, 10, 11, 5, 0, 4, 1, 0, 2, 2, 2, 0, 0, 0];

    private const H = [3, 3, 3, 4, 4, 3, 10, 15, 10, 5, 6, 2, 2, 8, 8, 8, 3, 2, 1];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
    }

    /** @return array<string, mixed> */
    private function patientForm(array $over = []): array
    {
        return array_replace([
            'research_id' => 'JD1985',
            'alias' => 'J.D.',
            'gender' => 1,
            'birth_year' => 1985,
            'nli' => 'T4',
            'ais' => 'A',
            'ams' => 50,
        ], $over);
    }

    /** @return array<string, mixed> */
    private function assessmentForm(array $vector = self::E, array $over = []): array
    {
        $answers = [];
        foreach ($vector as $i => $v) {
            $answers['v'.($i + 1)] = $v;
        }

        return array_replace([
            'scim_version' => 'III',
            'assessed_on' => '2025-06-01',
            'nli' => 'T4',
            'ais' => 'A',
            'ams' => 50,
            'answers' => $answers,
            'accept_terms' => '1',
        ], $over);
    }

    private function doctor(?Institution $inst = null): User
    {
        return User::factory()->withProfile($inst?->id)->create();
    }

    public function test_create_patient_then_first_assessment(): void
    {
        $user = $this->doctor();

        $res = $this->actingAs($user)->post('/patients', $this->patientForm());
        $patient = Patient::query()->sole();
        $res->assertRedirect("/patients/{$patient->public_id}/assessments/create");
        $this->assertSame($user->institutionId(), $patient->institution_id);

        // prefilled with values "at the time of this assessment"
        $this->get("/patients/{$patient->public_id}/assessments/create")->assertOk()->assertSee('First assessment');

        $this->post("/patients/{$patient->public_id}/assessments", $this->assessmentForm())->assertRedirect();

        $a = Assessment::query()->with('result')->sole();
        $this->assertSame(AssessmentSource::Account, $a->source);
        $this->assertSame($user->id, $a->user_id);
        $this->assertTrue($a->is_first);
        $this->assertSame(40, $a->age); // 2025 − 1985
        $this->assertSame(50, $a->result->total);
        $this->assertSame('E', $a->result->grade);

        $this->get("/assessments/{$a->public_id}")->assertOk()->assertSee('JD1985');
    }

    public function test_follow_up_is_not_first_and_history_shows_delta(): void
    {
        $user = $this->doctor();
        $patient = Patient::factory()->for($user)->create();

        $this->actingAs($user)->post("/patients/{$patient->public_id}/assessments", $this->assessmentForm(self::E, ['assessed_on' => '2025-01-01']));
        $this->post("/patients/{$patient->public_id}/assessments", $this->assessmentForm(self::H, ['assessed_on' => '2025-06-01']));

        $this->assertSame([true, false], Assessment::query()->orderBy('assessed_on')->pluck('is_first')->all());
        $this->get("/patients/{$patient->public_id}")->assertOk()->assertSee('+50');
    }

    public function test_compare_two_assessments(): void
    {
        $user = $this->doctor();
        $patient = Patient::factory()->for($user)->create();
        $this->actingAs($user)->post("/patients/{$patient->public_id}/assessments", $this->assessmentForm(self::E, ['assessed_on' => '2025-01-01']));
        $this->post("/patients/{$patient->public_id}/assessments", $this->assessmentForm(self::H, ['assessed_on' => '2025-06-01']));
        [$a, $b] = Assessment::query()->orderBy('assessed_on')->pluck('public_id')->all();

        $this->get("/patients/{$patient->public_id}/compare?a={$a}&b={$b}")->assertOk()->assertSee('+50');
    }

    public function test_compare_rejects_assessments_of_another_patient(): void
    {
        $user = $this->doctor();
        $p1 = Patient::factory()->for($user)->create();
        $p2 = Patient::factory()->for($user)->create();
        $a = Assessment::factory()->forPatient($p1)->create();
        $b = Assessment::factory()->forPatient($p2)->create();

        $this->actingAs($user)->get("/patients/{$p1->public_id}/compare?a={$a->public_id}&b={$b->public_id}")->assertNotFound();
    }

    public function test_other_users_data_answers_404(): void
    {
        $alice = $this->doctor();
        $bobPatient = Patient::factory()->create();
        $bobAssessment = Assessment::factory()->forPatient($bobPatient)->create();

        $this->actingAs($alice);
        $this->get("/patients/{$bobPatient->public_id}")->assertNotFound();
        $this->get("/patients/{$bobPatient->public_id}/edit")->assertNotFound();
        $this->put("/patients/{$bobPatient->public_id}", $this->patientForm())->assertNotFound();
        $this->get("/patients/{$bobPatient->public_id}/assessments/create")->assertNotFound();
        $this->post("/patients/{$bobPatient->public_id}/assessments", $this->assessmentForm())->assertNotFound();
        $this->get("/assessments/{$bobAssessment->public_id}")->assertNotFound();
        $this->post("/assessments/{$bobAssessment->public_id}/email")->assertNotFound();

        $this->assertSame(1, Assessment::query()->count());
    }

    public function test_patient_list_shows_only_own_and_searches_encrypted_fields(): void
    {
        $user = $this->doctor();
        Patient::factory()->for($user)->create(['research_id' => 'ABC123', 'alias' => 'A.B.']);
        Patient::factory()->for($user)->create(['research_id' => 'XYZ999', 'alias' => 'X.Y.']);
        Patient::factory()->create(['research_id' => 'OTHER1', 'alias' => 'O.T.']);

        $this->actingAs($user);
        $this->get('/patients')->assertOk()->assertSee('ABC123')->assertSee('XYZ999')->assertDontSee('OTHER1');
        $this->get('/patients?research_id=abc123')->assertSee('ABC123')->assertDontSee('XYZ999');
        $this->get('/patients?research_id=OTHER1')->assertSee('No matching patients.')->assertDontSee('O.T.');
    }

    public function test_identifiers_that_look_like_email_or_id_numbers_are_rejected(): void
    {
        $this->actingAs($this->doctor());

        $this->post('/patients', $this->patientForm(['research_id' => 'john@mail.com']))->assertSessionHasErrors('research_id');
        $this->post('/patients', $this->patientForm(['alias' => '054-123-4567']))->assertSessionHasErrors('alias');
        $this->post('/patients', $this->patientForm(['research_id' => '123456789']))->assertSessionHasErrors('research_id');
        $this->assertSame(0, Patient::query()->count());
    }

    // --- Duplicate prevention (Appendix A) ---

    public function test_same_research_id_in_own_list_is_blocked(): void
    {
        $user = $this->doctor();
        Patient::factory()->for($user)->create(['research_id' => 'JD1985']);

        $this->actingAs($user)->post('/patients', $this->patientForm(['research_id' => 'jd1985']))->assertSessionHasErrors('research_id');
        $this->assertSame(1, Patient::query()->count());
    }

    public function test_same_research_id_at_institution_is_blocked_without_revealing_details(): void
    {
        $inst = Institution::factory()->create();
        $colleague = $this->doctor($inst);
        Patient::factory()->for($colleague)->create(['research_id' => 'JD1985', 'alias' => 'Secret']);
        $user = $this->doctor($inst);

        $res = $this->actingAs($user)->from('/patients/create')->post('/patients', $this->patientForm());
        $res->assertSessionHasErrors('research_id');
        $this->assertSame(1, Patient::query()->count());

        $this->followRedirects($res)->assertDontSee('Secret');
    }

    public function test_same_research_id_at_another_institution_is_allowed(): void
    {
        Patient::factory()->create(['research_id' => 'JD1985']);

        $this->actingAs($this->doctor())->post('/patients', $this->patientForm())->assertRedirect();
        $this->assertSame(2, Patient::query()->count());
    }

    public function test_similar_own_patient_shows_warning_and_requires_confirmation(): void
    {
        $user = $this->doctor();
        $existing = Patient::factory()->for($user)->create([
            'research_id' => 'OLD1', 'alias' => 'J.D.', 'gender' => 1, 'birth_year' => 1985,
        ]);

        $this->actingAs($user)->post('/patients', $this->patientForm())->assertRedirect();
        $this->assertSame(1, Patient::query()->count());
        $this->assertSame($existing->public_id, session('duplicate.similar_own.0.public_id'));

        $this->post('/patients', $this->patientForm(['confirm_not_duplicate' => '1']))->assertRedirect();
        $this->assertSame(2, Patient::query()->count());
    }

    public function test_merge_with_similar_patient_adds_assessment_to_existing_record(): void
    {
        $user = $this->doctor();
        $existing = Patient::factory()->for($user)->create([
            'research_id' => 'OLD1', 'alias' => 'J.D.', 'gender' => 1, 'birth_year' => 1985,
        ]);

        $this->actingAs($user)->from('/patients/create')->post('/patients', $this->patientForm(['first_nli' => 'T3', 'first_ais' => 'A']));
        $this->assertSame(1, Patient::query()->count());

        // the warning offers both choices
        $this->get('/patients/create')->assertSee('Merge with this patient')->assertSee('Add as a new patient');

        $this->post("/patients/{$existing->public_id}/merge", ['nli' => 'T4', 'ais' => 'A', 'ams' => 50, 'first_nli' => 'T3', 'first_ais' => 'A'])
            ->assertRedirect("/patients/{$existing->public_id}/assessments/create");

        $this->assertSame(1, Patient::query()->count());                 // no new record
        $this->assertSame('T3', $existing->fresh()->first_nli->value);   // missing data filled in
        $this->assertSame(['nli' => 'T4', 'ais' => 'A', 'ams' => 50], session("assessment.prefill.{$existing->public_id}"));

        $this->post("/patients/{$existing->public_id}/assessments", $this->assessmentForm())->assertRedirect();
        $this->assertSame(1, $existing->assessments()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.duplicate_merged']);
    }

    public function test_merge_does_not_overwrite_existing_first_assessment_data(): void
    {
        $user = $this->doctor();
        $existing = Patient::factory()->for($user)->create(['first_nli' => 'C5', 'first_ais' => 'B']);

        $this->actingAs($user)->post("/patients/{$existing->public_id}/merge", ['nli' => 'T4', 'ais' => 'A', 'first_nli' => 'T3', 'first_ais' => 'A']);

        $this->assertSame('C5', $existing->fresh()->first_nli->value);
        $this->assertSame('B', $existing->fresh()->first_ais->value);
    }

    public function test_cannot_merge_into_another_users_patient(): void
    {
        $theirs = Patient::factory()->create();

        $this->actingAs($this->doctor())->post("/patients/{$theirs->public_id}/merge", ['nli' => 'T4', 'ais' => 'A'])->assertNotFound();
    }

    public function test_similar_patient_at_institution_gives_anonymous_warning(): void
    {
        $inst = Institution::factory()->create();
        $colleague = $this->doctor($inst);
        $theirs = Patient::factory()->for($colleague)->create(['research_id' => 'C-77', 'alias' => 'Z.Z.', 'gender' => 1, 'birth_year' => 1985]);
        Assessment::factory()->forPatient($theirs)->create(['nli' => 'T4', 'ais' => 'A', 'ams' => 50]);
        $user = $this->doctor($inst);

        $res = $this->actingAs($user)->from('/patients/create')->post('/patients', $this->patientForm());
        $this->assertSame(1, Patient::query()->count());
        $this->assertTrue(session('duplicate.institution'));
        $this->assertSame([], session('duplicate.similar_own'));

        $this->followRedirects($res)->assertSee('Possible duplicate patient detected')->assertDontSee('C-77')->assertDontSee('Z.Z.');
    }

    public function test_different_clinical_profile_is_not_a_duplicate(): void
    {
        $user = $this->doctor();
        $p = Patient::factory()->for($user)->create(['alias' => 'Q.Q.', 'gender' => 1, 'birth_year' => 1985]);
        Assessment::factory()->forPatient($p)->create(['nli' => 'C5', 'ais' => 'B', 'ams' => 20]);

        $this->actingAs($user)->post('/patients', $this->patientForm())->assertRedirect();
        $this->assertSame(2, Patient::query()->count());
    }

    public function test_assessment_result_can_be_emailed_to_own_address(): void
    {
        $user = $this->doctor();
        $a = Assessment::factory()->forPatient(Patient::factory()->for($user)->create())->create();
        $a->result()->create(['total' => 10, 'self_care' => 5, 'respiration_sphincter' => 5, 'mobility' => 0]);

        $this->actingAs($user)->post("/assessments/{$a->public_id}/email")->assertRedirect();
        Mail::assertSent(AssessmentResultMail::class, fn ($m) => $m->hasTo($user->email));
    }

    public function test_dashboard_and_history_pages_render(): void
    {
        $user = $this->doctor();
        $patient = Patient::factory()->for($user)->create();
        $this->actingAs($user)->post("/patients/{$patient->public_id}/assessments", $this->assessmentForm());

        $this->get('/dashboard')->assertOk()->assertSee($patient->system_id);
        $this->get('/assessments')->assertOk()->assertSee($patient->system_id);
    }
}
