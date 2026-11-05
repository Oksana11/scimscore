<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data only. Safe to run repeatedly, also in production.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            ProfessionSeeder::class,
        ]);
    }
}
