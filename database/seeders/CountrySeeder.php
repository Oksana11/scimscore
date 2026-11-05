<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, string> $countries */
        $countries = require database_path('data/countries.php');

        $rows = [];
        foreach ($countries as $code => $name) {
            $rows[] = ['code' => $code, 'name' => $name, 'is_active' => true];
        }

        // Updates names, keeps is_active chosen by the administrator.
        Country::query()->upsert($rows, ['code'], ['name']);
    }
}
