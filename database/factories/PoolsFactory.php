<?php

namespace Database\Factories;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class PoolsFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        // Define content types and their corresponding models
        $contentTypes = [
            'App\Models\HumanSamples' => HumanSamples::class,
            'App\Models\AnimalSamples' => AnimalSamples::class,
            'App\Models\EnvironmentSamples' => EnvironmentSamples::class,
            'App\Models\ParasiteSamples' => ParasiteSamples::class,
            'App\Models\Cultures' => Cultures::class,
            'App\Models\NucleicAcids' => NucleicAcids::class,
        ];

        // Select a random content type
        $contentType = $this->faker->randomElement(array_keys($contentTypes));
        $modelClass = $contentTypes[$contentType];

        // Get a valid ID from the selected model
        $contentId = $modelClass::inRandomOrder()->value('id');

        // If no records exist for this model, create one
        if (! $contentId) {
            $contentId = $modelClass::factory()->create()->id;
        }

        return [
            'code' => "{$project->code}-PO-{$serialNumber}",
            'nr_pooled' => $this->faker->numberBetween(2, 10), // Number of samples pooled
            'date_pooled' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
            'projects_id' => $project->id,
        ];
    }
}
