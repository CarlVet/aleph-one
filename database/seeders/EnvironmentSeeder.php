<?php

namespace Database\Seeders;

use App\Models\EnvironmentSamples;
use App\Models\EnvironmentSampleTypes;
use Illuminate\Database\Seeder;

class EnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        // Create 50 environment sample types
        EnvironmentSampleTypes::factory()->count(50)->create();

        EnvironmentSamples::factory()->count(50)->create();
    }
}
