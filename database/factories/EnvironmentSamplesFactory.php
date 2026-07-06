<?php

namespace Database\Factories;

use App\Models\EnvironmentSampleTypes;
use App\Models\Locations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SamplingSites;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnvironmentSamplesFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => "{$project->code}-ES-{$serialNumber}",
            'environment_sample_types_id' => EnvironmentSampleTypes::query()->inRandomOrder()->value('id') ?? EnvironmentSampleTypes::factory(),
            'date_collected' => fake()->date(),
            'sampling_sites_id' => SamplingSites::query()->inRandomOrder()->value('id') ?? SamplingSites::factory(),
            'area' => fake()->words(3, true),
            'latitude' => fake()->randomFloat(6, -28, -22),
            'longitude' => fake()->randomFloat(6, 22, 32),
            'locations_id' => Locations::query()->inRandomOrder()->value('id') ?? Locations::factory(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'projects_id' => $project->id,
            'processed' => false,
        ];
    }
}
