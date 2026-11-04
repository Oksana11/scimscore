<?php

namespace Tests\Feature\Domain;

use App\Models\Country;
use App\Models\Profession;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProfessionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_load_countries_and_professions_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(249, Country::query()->count());
        $this->assertSame('Israel', Country::query()->find('IL')?->name);
        $this->assertSame(count(ProfessionSeeder::PROFESSIONS), Profession::query()->count());
    }
}
