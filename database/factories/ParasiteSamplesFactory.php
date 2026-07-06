<?php

namespace Database\Factories;

use App\Models\Parasites;
use App\Models\ParasiteSampleTypes;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParasiteSamplesFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => "{$project->code}-PS-{$serialNumber}",
            'parasites_id' => Parasites::query()->inRandomOrder()->value('id') ?? Parasites::factory(),
            'parasite_sample_types_id' => ParasiteSampleTypes::query()->inRandomOrder()->value('id') ?? ParasiteSampleTypes::factory(),
            'date_processed' => fake()->date(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'projects_id' => $project->id,
        ];
    }
}
