<?php

namespace Tests\Feature\Assessments;

use App\Enums\AssessmentSource;
use App\Mail\AssessmentResultMail;
use App\Mail\OtpCodeMail;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\Institution;
use App\Models\Patient;
use App\Models\Profession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuickAssessmentTest extends TestCase
{
    use RefreshDatabase;

    /** Reference profile E (total 50). */
    private const E = [3, 3, 1, 3, 0, 3, 10, 11, 5, 0, 4, 1, 0, 2, 2, 2, 0, 0, 0];

    private array $caregiver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();

        Country::query()->create(['code' => 'IL', 'name' => 'Israel']);
        $profession = Profession::query()->create(['name' => 'Physician', 'sort' => 1]);
        $institution = Institution::factory()->create(['name' => 'Rehab Center']);

        $this->caregiver = [
            'profession_id' => $profession->id,
            'country_code' => 'IL',
            'institution_id' => $institution->id,
            'institution' => 'local:'.$institution->id,
            'institution_display' => $institution->name,
            'professional_title' => 'Senior physician',
            'organization' => null,
            'department' => null,
        ];
        $this->withSession(['quick.caregiver' => $this->caregiver]);
    }

    /** @return array<string, mixed> */
    private function payload(array $over = []): array
    {
        $answers = [];
        foreach (self::E as $i => $v) {
            $answers['v'.($i + 1)] = $v;
        }

        return array_replace([
            'scim_version' => 'III',
            'assessed_on' => now()->toDateString(),
            'patient_code' => 'P-15355',
            'etiology' => 'traumatic',
            'gender' => 1,
            'age' => 40,
            'ams' => 50,
            'nli' => 'T4',
            'ais' => 'A',
            'answers' => $answers,
            'accept_terms' => '1',
        ], $over);
    }

    public function test_form_shows_all_19_items(): void
    {
        $html = $this->get('/quick/assessment')->assertOk()->getContent();

        for ($i = 1; $i <= 19; $i++) {
            $this->assertStringContainsString("name=\"answers[v{$i}]\"", $html);
        }
    }

    public function test_calculation_is_stored_anonymously_and_shown(): void
    {
        $res = $this->post('/quick', $this->payload());

        $assessment = Assessment::query()->with('result')->sole();
        $res->assertRedirect('/quick/'.$assessment->public_id);

        $this->assertSame(AssessmentSource::Quick, $assessment->source);
        $this->assertNull($assessment->user_id);
        $this->assertNull($assessment->patient_id);
        $this->assertSame(50, $assessment->result->total);
        $this->assertSame('66.70', (string) $assessment->result->sciarmi_total);
        $this->assertSame('E', $assessment->result->grade);
        $this->assertNotNull($assessment->consent->accepted_at);
        $this->assertNull($assessment->consent->user_id);

        $raw = DB::table('assessments')->where('id', $assessment->id)->value('answers');
        $this->assertStringNotContainsString('v1', $raw);

        $this->get('/quick/'.$assessment->public_id)->assertOk()->assertSee('66.7')->assertSee('P-15355');

        // caregiver details from step 1 and patient code (encrypted) are stored with the result
        $this->assertSame($this->caregiver['profession_id'], $assessment->caregiver_profession_id);
        $this->assertSame('IL', $assessment->caregiver_country_code);
        $this->assertSame($this->caregiver['institution_id'], $assessment->caregiver_institution_id);
        $this->assertSame('P-15355', $assessment->patient_code);
        $this->assertStringNotContainsString('P-15355', (string) DB::table('assessments')->where('id', $assessment->id)->value('patient_code'));
        $this->assertSame('traumatic', $assessment->etiology->value);
    }

    public function test_caregiver_step_is_required_first(): void
    {
        $this->flushSession();
        $this->get('/quick/assessment')->assertRedirect('/quick');
        $this->post('/quick', $this->payload())->assertRedirect('/quick');
        $this->assertSame(0, Assessment::query()->count());
    }

    public function test_caregiver_step_validates_and_stores_details_in_session(): void
    {
        $this->flushSession();
        $this->get('/quick')->assertOk()->assertSee('caregiver details');

        $this->post('/quick/caregiver', ['country_code' => 'IL'])->assertSessionHasErrors(['profession_id', 'institution']);

        $this->post('/quick/caregiver', [
            'profession_id' => $this->caregiver['profession_id'],
            'country_code' => 'IL',
            'institution' => 'custom',
            'institution_custom_name' => 'Small Rehab Clinic',
        ])->assertRedirect('/quick/assessment');

        $this->assertSame('Small Rehab Clinic', session('quick.caregiver.institution_display'));
        $this->get('/quick/assessment')->assertOk()->assertSee('Small Rehab Clinic');
    }

    public function test_patient_code_must_not_look_like_an_identifier_and_ams_is_required(): void
    {
        $this->post('/quick', $this->payload(['patient_code' => 'john@mail.com', 'ams' => null]))
            ->assertSessionHasErrors(['patient_code', 'ams']);
    }

    public function test_quick_assessment_is_anonymous_even_for_signed_in_users(): void
    {
        $user = User::factory()->withProfile()->create();

        $this->actingAs($user)->post('/quick', $this->payload())->assertRedirect();

        $assessment = Assessment::query()->sole();
        $this->assertNull($assessment->user_id);

        $log = AuditLog::query()->where('action', 'assessment.quick_created')->sole();
        $this->assertNull($log->actor_id);
        $this->assertNull($log->ip);
    }

    public function test_result_is_visible_only_in_the_creating_session(): void
    {
        $this->post('/quick', $this->payload());
        $id = Assessment::query()->sole()->public_id;

        $this->flushSession();
        $this->get('/quick/'.$id)->assertNotFound();
    }

    public function test_account_assessments_are_not_reachable_via_quick_routes(): void
    {
        $a = Assessment::factory()->forPatient(Patient::factory()->create())->create();

        $this->withSession(['quick.results' => [$a->public_id]])->get('/quick/'.$a->public_id)->assertNotFound();
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post('/quick', $this->payload(['accept_terms' => null]))->assertSessionHasErrors('accept_terms');
        $this->assertSame(0, Assessment::query()->count());
    }

    public function test_invalid_and_missing_answers_are_rejected(): void
    {
        $p = $this->payload();
        $p['answers']['v8'] = 7;
        unset($p['answers']['v19']);

        $this->post('/quick', $p)->assertSessionHasErrors(['answers.v8', 'answers.v19']);
        $this->assertSame(0, Assessment::query()->count());
    }

    public function test_unavailable_version_is_rejected(): void
    {
        $this->post('/quick', $this->payload(['scim_version' => 'IV']))->assertSessionHasErrors('scim_version');
    }

    public function test_patient_fields_are_validated(): void
    {
        $this->post('/quick', $this->payload(['age' => 200, 'ams' => 101, 'nli' => 'X9', 'ais' => 'Z', 'gender' => 5]))
            ->assertSessionHasErrors(['age', 'ams', 'nli', 'ais', 'gender']);
    }

    public function test_result_can_be_emailed_after_otp(): void
    {
        $this->post('/quick', $this->payload());
        $id = Assessment::query()->sole()->public_id;

        $this->post("/quick/{$id}/email", ['email' => 'me@clinic.org'])->assertRedirect("/quick/{$id}");

        $code = null;
        Mail::assertSent(OtpCodeMail::class, function ($m) use (&$code) {
            $code = $m->code;

            return $m->hasTo('me@clinic.org');
        });
        Mail::assertNotSent(AssessmentResultMail::class);

        $this->post("/quick/{$id}/email/send", ['code' => $code])->assertRedirect("/quick/{$id}");
        Mail::assertSent(AssessmentResultMail::class, fn ($m) => $m->hasTo('me@clinic.org'));
    }

    public function test_wrong_code_does_not_send_result(): void
    {
        $this->post('/quick', $this->payload());
        $id = Assessment::query()->sole()->public_id;
        $this->post("/quick/{$id}/email", ['email' => 'me@clinic.org']);

        $this->post("/quick/{$id}/email/send", ['code' => '000000x'])->assertSessionHasErrors('code');
        Mail::assertNotSent(AssessmentResultMail::class);
    }
}
