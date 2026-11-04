<?php

namespace Tests\Feature\Domain;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_id_and_public_id_are_generated(): void
    {
        $patient = Patient::factory()->create();

        $this->assertMatchesRegularExpression('/^SCIM-\d{4}-\d{6}$/', $patient->system_id);
        $this->assertSame(26, strlen($patient->public_id));
        $this->assertSame($patient->public_id, $patient->getRouteKey());
    }

    public function test_patient_takes_owner_institution(): void
    {
        $user = User::factory()->withProfile()->create();
        $patient = Patient::factory()->for($user)->create();

        $this->assertSame($user->institutionId(), $patient->institution_id);
    }

    public function test_research_id_is_unique_per_user(): void
    {
        $user = User::factory()->withProfile()->create();
        Patient::factory()->for($user)->create(['research_id' => 'P001']);

        $this->expectException(QueryException::class);
        Patient::factory()->for($user)->create(['research_id' => 'p001']);
    }

    public function test_different_users_may_use_the_same_research_id(): void
    {
        Patient::factory()->create(['research_id' => 'P001']);
        Patient::factory()->create(['research_id' => 'P001']);

        $this->assertSame(2, Patient::query()->whereBlind('research_id', 'P001')->count());
    }
}
