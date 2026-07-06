<?php

namespace Database\Factories;

use App\Enums\ExperimentPurpose;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperimentsFactory extends Factory
{
    public function definition(): array
    {
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        $serialNumber = fake()->unique()->numberBetween(1, 99999);

        // Define content types and their corresponding models
        $contentTypes = [
            'App\Models\HumanSamples' => HumanSamples::class,
            'App\Models\AnimalSamples' => AnimalSamples::class,
            'App\Models\EnvironmentSamples' => EnvironmentSamples::class,
            'App\Models\ParasiteSamples' => ParasiteSamples::class,
            'App\Models\NucleicAcids' => NucleicAcids::class,
            'App\Models\Cultures' => Cultures::class,
            'App\Models\Pools' => Pools::class,
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
            'code' => "{$project->code}-EX-{$serialNumber}",
            'experiments_content_id' => $contentId,
            'experiments_content_type' => $contentType,
            'protocols_id' => Protocols::query()->with([
                'techniques',
                'studies',
                'pathogens',
            ])->whereDoesntHave('techniques', function ($query) {
                $query->where('type', 'Nucleic Acids Extraction and Purification');
            })->inRandomOrder()->value('id') ?? Protocols::factory(),
            'pathogens_id' => Pathogens::query()->inRandomOrder()->value('id') ?? Pathogens::factory(),
            'outcome_discrete' => $this->faker->randomElement(['Positive', 'Negative', 'Suspect']),
            'outcome_binary' => $this->faker->randomElement([0, 1]),
            'purpose' => $this->faker->randomElement(ExperimentPurpose::cases()),
            'date_tested' => fake()->date(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
            'projects_id' => $project->id,
            'is_private' => $this->faker->boolean(70), // 70% chance of being private
        ];
    }
}
