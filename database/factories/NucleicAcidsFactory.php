<?php

namespace Database\Factories;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use Illuminate\Database\Eloquent\Factories\Factory;

class NucleicAcidsFactory extends Factory
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
            'App\Models\Pools' => Pools::class,
            'App\Models\Experiments' => Experiments::class,
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

        // Special handling for experiments: ensure they reference nucleic acids
        if ($contentType === 'App\Models\Experiments') {
            // Find an experiment that references a nucleic acid, or create one
            $experiment = Experiments::where('experiments_content_type', 'App\Models\NucleicAcids')->inRandomOrder()->first();
            if (! $experiment) {
                // Create a nucleic acid first, then an experiment that references it
                $nucleicAcid = NucleicAcids::factory()->create();
                $experiment = Experiments::factory()->create([
                    'experiments_content_id' => $nucleicAcid->id,
                    'experiments_content_type' => 'App\Models\NucleicAcids',
                ]);
            }
            $contentId = $experiment->id;
        }

        return [
            'code' => "{$project->code}-NA-{$serialNumber}",
            'type' => $this->faker->randomElement([
                'genomic DNA',
                'complementary DNA',
                'plasmid DNA',
                'mitochondrial DNA',
                'RNA',
                'Purified PCR product',
            ]),
            'nucleic_content_id' => $contentId,
            'nucleic_content_type' => $contentType,
            'protocols_id' => Protocols::query()->whereHas(
                'techniques',
                function ($q) {
                    $q->where('type', 'Nucleic Acids Extraction and Purification');
                })->inRandomOrder()->value('id') ?? Protocols::factory(),
            'volume' => fake()->numberBetween(1, 100),
            'concentration' => fake()->randomFloat(2, 1, 200),
            'A260/A280' => fake()->randomFloat(2, 1, 3),
            'A260/A230' => fake()->randomFloat(2, 1, 3),
            'date_extracted' => fake()->date(),
            'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'projects_id' => $project->id,
        ];
    }
}
