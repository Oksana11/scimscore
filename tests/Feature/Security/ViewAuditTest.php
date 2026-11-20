<?php

namespace Tests\Feature\Security;

use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function logged(string $action): ?AuditLog
    {
        return AuditLog::query()->where('action', $action)->latest('id')->first();
    }

    public function test_viewing_patients_and_assessments_is_logged(): void
    {
        $user = User::factory()->withProfile()->create();
        $patient = Patient::factory()->for($user)->create(['research_id' => 'SECRET-R1']);
        $a = Assessment::factory()->forPatient($patient)->create();
        $a->result()->create(['total' => 40, 'self_care' => 10, 'respiration_sphincter' => 20, 'mobility' => 10, 'grade' => 'D']);
        $this->actingAs($user);

        $this->get('/patients?research_id=SECRET-R1')->assertOk();
        $list = $this->logged('patient.list_viewed');
        $this->assertNotNull($list);
        $this->assertSame((string) $user->id, (string) $list->actor_id);
        $this->assertStringNotContainsString('SECRET-R1', json_encode($list->context));

        $this->get("/patients/{$patient->public_id}")->assertOk();
        $this->assertSame((string) $patient->id, $this->logged('patient.viewed')?->subject_id);

        $this->get("/assessments/{$a->public_id}")->assertOk();
        $this->assertSame((string) $a->id, $this->logged('assessment.viewed')?->subject_id);

        $this->get('/assessments')->assertOk();
        $this->assertNotNull($this->logged('assessment.list_viewed'));
    }

    public function test_attempt_to_open_another_doctors_patient_is_logged(): void
    {
        $owner = User::factory()->withProfile()->create();
        $intruder = User::factory()->withProfile()->create();
        $patient = Patient::factory()->for($owner)->create();

        $this->actingAs($intruder)->get("/patients/{$patient->public_id}")->assertNotFound();

        $denied = $this->logged('access.denied');
        $this->assertNotNull($denied);
        $this->assertSame((string) $intruder->id, (string) $denied->actor_id);
        $this->assertSame((string) $patient->id, $denied->subject_id);
        $this->assertNull($this->logged('patient.viewed'));
    }
}
