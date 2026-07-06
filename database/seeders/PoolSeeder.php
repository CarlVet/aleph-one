<?php

namespace Database\Seeders;

use App\Models\PoolContents;
use App\Models\Pools;
use Illuminate\Database\Seeder;

class PoolSeeder extends Seeder
{
    private $sampleTypes = [
        'App\\Models\\HumanSamples',
        'App\\Models\\AnimalSamples',
        'App\\Models\\EnvironmentSamples',
        'App\\Models\\ParasiteSamples',
        'App\\Models\\NucleicAcids',
    ];

    public function run(): void
    {
        // Create 10 pools
        for ($i = 0; $i < 10; $i++) {
            // Select a random sample type for this pool
            $sampleType = fake()->randomElement($this->sampleTypes);

            // Create a pool
            $pool = Pools::factory()->create();

            // Create between 2 and 8 pool contents for each pool with the same sample type
            $numberOfContents = rand(2, 8);
            for ($j = 0; $j < $numberOfContents; $j++) {
                PoolContents::factory()->create([
                    'pools_id' => $pool->id,
                    'samples_type' => $sampleType,
                ]);
            }

            // Update the pool with the actual number of pooled samples
            $pool->update([
                'nr_pooled' => $numberOfContents,
            ]);
        }
    }
}
