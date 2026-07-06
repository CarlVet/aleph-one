<?php

namespace Database\Seeders;

use App\Models\Humans;
use App\Models\HumanSamples;
use App\Models\SampleTypes;
use Illuminate\Database\Seeder;

class HumanSamplesSeeder extends Seeder
{
    public function run(): void
    {
        SampleTypes::factory()->count(8)->create();

        Humans::factory()->count(50)->create();

        HumanSamples::factory(200)->create();

    }
}
