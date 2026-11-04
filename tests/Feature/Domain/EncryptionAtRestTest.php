<?php

namespace Tests\Feature\Domain;

use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptionAtRestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_email_is_encrypted_and_found_by_blind_index(): void
    {
        $user = User::factory()->create(['email' => 'Doctor@Clinic.org']);

        $raw = DB::table('users')->where('id', $user->id)->first();
        $this->assertStringNotContainsStringIgnoringCase('clinic.org', $raw->email);
        $this->assertStringNotContainsStringIgnoringCase('clinic.org', $raw->email_bidx);

        $found = User::query()->whereBlind('email', '  doctor@clinic.ORG ')->first();
        $this->assertTrue($found?->is($user));
        $this->assertSame('Doctor@Clinic.org', $found->email);
    }

    public function test_same_email_cannot_belong_to_two_accounts(): void
    {
        User::factory()->create(['email' => 'a@clinic.org']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'A@clinic.org']);
    }

    public function test_patient_codes_are_encrypted(): void
    {
        $patient = Patient::factory()->create(['research_id' => 'JD1985', 'alias' => 'J.D.']);

        $raw = DB::table('patients')->where('id', $patient->id)->first();
        $this->assertStringNotContainsString('JD1985', $raw->research_id);
        $this->assertStringNotContainsString('J.D.', $raw->alias);

        $this->assertTrue(Patient::query()->whereBlind('research_id', 'jd1985')->first()?->is($patient));
    }

    public function test_assessment_answers_are_encrypted(): void
    {
        $assessment = Assessment::factory()->forPatient(Patient::factory()->create())->create([
            'answers' => ['v1' => 3, 'v2' => 1],
        ]);

        $raw = DB::table('assessments')->where('id', $assessment->id)->value('answers');
        $this->assertStringNotContainsString('v1', $raw);
        $this->assertSame(['v1' => 3, 'v2' => 1], $assessment->fresh()->answers);
    }
}
