<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Institution;
use App\Models\Profession;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    public function definition(): array
    {
        $country = Country::query()->firstOrCreate(['code' => 'IL'], ['name' => 'Israel']);
        $profession = Profession::query()->firstOrCreate(['name' => 'Physician'], ['sort' => 1]);

        return [
            'profession_id' => $profession->id,
            'country_code' => $country->code,
            'institution_id' => Institution::factory(),
        ];
    }
}
