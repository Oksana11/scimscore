<?php

namespace Tests\Feature\Domain;

use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataSegregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_own_patients(): void
    {
        $alice = User::factory()->withProfile()->create();
        $bob = User::factory()->withProfile()->create();
        $bobsPatient = Patient::factory()->for($bob)->create();

        $this->assertFalse($alice->can('view', $bobsPatient));
        $this->assertFalse($alice->can('update', $bobsPatient));
        $this->assertTrue($bob->can('view', $bobsPatient));
        $this->assertSame(0, Patient::query()->ownedBy($alice)->count());
    }

    public function test_nobody_can_delete_patients(): void
    {
        $patient = Patient::factory()->create();

        $this->assertFalse($patient->user->can('delete', $patient));
    }

    public function test_user_sees_only_own_assessments_and_cannot_change_them(): void
    {
        $alice = User::factory()->withProfile()->create();
        $bobsAssessment = Assessment::factory()->forPatient(Patient::factory()->create())->create();
        $bob = $bobsAssessment->user;

        $this->assertFalse($alice->can('view', $bobsAssessment));
        $this->assertTrue($bob->can('view', $bobsAssessment));
        $this->assertFalse($bob->can('update', $bobsAssessment));
        $this->assertFalse($bob->can('delete', $bobsAssessment));
    }

    public function test_user_cannot_add_assessment_to_someone_elses_patient(): void
    {
        $alice = User::factory()->withProfile()->create();
        $bobsPatient = Patient::factory()->create();

        $this->assertFalse($alice->can('create', [Assessment::class, $bobsPatient]));
    }

    public function test_deactivated_user_loses_access_to_own_data(): void
    {
        $patient = Patient::factory()->create();
        $user = $patient->user;
        $user->update(['is_active' => false]);

        $this->assertFalse($user->can('view', $patient));
        $this->assertFalse($user->can('create', Patient::class));
    }

    public function test_user_without_profile_cannot_create_patients(): void
    {
        $this->assertFalse(User::factory()->create()->can('create', Patient::class));
    }
}
