<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->withProfile(),
            'research_id' => strtoupper(fake()->unique()->bothify('??####')),
            'alias' => strtoupper(fake()->lexify('?.?.')),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_year' => fake()->numberBetween(1940, 2008),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Patient $patient): void {
            $patient->user_id ??= User::factory()->withProfile()->create()->id;
            $patient->institution_id ??= User::query()->find($patient->user_id)?->institutionId();
        });
    }
}
