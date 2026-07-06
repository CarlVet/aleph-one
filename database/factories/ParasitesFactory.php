<?php

namespace Database\Factories;

use App\Enums\ParasiteStatus;
use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\ParasiteSpecies;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParasitesFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        // Determine the origin type and get the corresponding sample
        $sampleType = $this->faker->randomElement(['animal', 'human', 'environmental']);
        $originId = null;
        $originType = null;

        switch ($sampleType) {
            case 'animal':
                $sample = AnimalSamples::whereHas('sample_types', function ($q) {
                    $q->where('name', 'Parasites');
                })->inRandomOrder()->first() ?? AnimalSamples::factory()->create();
                $originId = $sample->id;
                $originType = AnimalSamples::class;
                break;

            case 'human':
                $sample = HumanSamples::whereHas('sample_types', function ($q) {
                    $q->where('name', 'Parasites');
                })->inRandomOrder()->first() ?? HumanSamples::factory()->create();
                $originId = $sample->id;
                $originType = HumanSamples::class;
                break;

            case 'environmental':
                $sample = EnvironmentSamples::whereHas('environment_sample_types', function ($q) {
                    $q->where('name', 'Parasites');
                })->inRandomOrder()->first() ?? EnvironmentSamples::factory()->create();
                $originId = $sample->id;
                $originType = EnvironmentSamples::class;
                break;
        }

        return [
            'code' => "{$project->code}-PA-{$serialNumber}",
            'parasite_species_id' => ParasiteSpecies::query()->inRandomOrder()->value('id') ?? ParasiteSpecies::factory(),
            'stage' => $this->faker->randomElement(['Egg', 'Larva', 'Nymph', 'Pupa', 'Adult']),
            'sex' => $this->faker->randomElement(['Male', 'Female', 'Cannot differentiate']),
            'state' => $this->faker->randomElement(['Engorged', 'Not engorged', 'Partially engorged']),
            'status' => ParasiteStatus::Intact->value,
            'date_identified' => fake()->date(),
            'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'projects_id' => $project->id,
            'parasites_origin_id' => $originId,
            'parasites_origin_type' => $originType,
        ];
    }
}
