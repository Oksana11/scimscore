<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ror_id' => strtolower(fake()->unique()->bothify('0?????##')),
            'name' => fake()->company().' Medical Center',
            'is_custom' => false,
        ];
    }
}
