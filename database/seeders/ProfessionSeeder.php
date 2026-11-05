<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionSeeder extends Seeder
{
    public const PROFESSIONS = [
        'Physician',
        'Nurse',
        'Physiotherapist',
        'Occupational therapist',
        'Researcher',
        'Other',
    ];

    public function run(): void
    {
        foreach (self::PROFESSIONS as $i => $name) {
            Profession::query()->firstOrCreate(['name' => $name], ['sort' => ($i + 1) * 10]);
        }
    }
}
