<?php

namespace Database\Factories;

use App\Enums\AisGrade;
use App\Enums\AssessmentSource;
use App\Enums\NeurologicalLevel;
use App\Enums\ScimVersion;
use App\Models\Assessment;
use App\Models\Patient;
use App\Models\UserConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => AssessmentSource::Account,
            'scim_version' => ScimVersion::III,
            'assessed_on' => now()->toDateString(),
            'is_first' => false,
            'age' => fake()->numberBetween(18, 85),
            'nli' => fake()->randomElement(NeurologicalLevel::cases()),
            'ais' => fake()->randomElement(AisGrade::cases()),
            'ams' => fake()->numberBetween(0, 100),
            'answers' => ['v1' => 2, 'v2' => 1],
            'calc_version' => 'test',
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn () => [
            'patient_id' => $patient->id,
            'user_id' => $patient->user_id,
            'gender' => $patient->gender,
        ])->afterMaking(function (Assessment $a) use ($patient): void {
            $a->consent_id ??= UserConsent::query()->create([
                'user_id' => $patient->user_id, 'terms_version' => 'test', 'accepted_at' => now(),
            ])->id;
        });
    }
}
