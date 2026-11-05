<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false, 'deactivated_at' => now()]);
    }

    /** User with a completed profile (profession, country, institution). */
    public function withProfile(?int $institutionId = null): static
    {
        return $this->afterCreating(function (User $user) use ($institutionId): void {
            UserProfileFactory::new()->for($user)->create(
                $institutionId ? ['institution_id' => $institutionId] : []
            );
        });
    }
}
