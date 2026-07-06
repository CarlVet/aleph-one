<?php

namespace Database\Factories;

use App\Models\Humans;
use App\Models\Locations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use Illuminate\Database\Eloquent\Factories\Factory;

class HumanSamplesFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => "{$project->code}-HS-{$serialNumber}",
            'humans_id' => Humans::query()->inRandomOrder()->value('id') ?? Humans::factory(),
            'sample_types_id' => SampleTypes::query()->inRandomOrder()->value('id') ?? SampleTypes::factory(),
            'date_collected' => $this->faker->date(),
            'sampling_sites_id' => SamplingSites::query()->inRandomOrder()->value('id') ?? SamplingSites::factory(),
            'area' => $this->faker->optional(0.7)->city(),
            'latitude' => $this->faker->optional(0.8)->latitude(),
            'longitude' => $this->faker->optional(0.8)->longitude(),
            'sample_purpose' => $this->faker->randomElement(['diagnostic', 'research', 'surveillance']),
            'locations_id' => Locations::query()->inRandomOrder()->value('id') ?? Locations::factory(),
            'storage_state' => $this->faker->randomElement(['formalin', 'RNAlater', 'No preservative']),
            'processed' => $this->faker->boolean(30),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'projects_id' => $project->id,
        ];
    }
}
