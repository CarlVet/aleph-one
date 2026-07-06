<?php

namespace Database\Factories;

use App\Models\Pools;
use Illuminate\Database\Eloquent\Factories\Factory;

class PoolContentsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'samples_type' => 'App\\Models\\HumanSamples', // Default value, will be overridden
            'samples_id' => $this->faker->numberBetween(1, 50),
            'pools_id' => Pools::query()->inRandomOrder()->value('id') ?? Pools::factory(),
        ];
    }
}
