<?php

namespace Database\Seeders;

use App\Models\Parasites;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use Illuminate\Database\Seeder;

class ParasiteSamplesSeeder extends Seeder
{
    public function run(): void
    {
        ParasiteSpecies::factory()->count(100)->create();
        ParasiteSampleTypes::factory()->count(15)->create();

        Parasites::factory()->count(50)->create();

        ParasiteSamples::factory(200)->create();
    }
}
