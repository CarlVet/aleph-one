<?php

namespace Database\Factories;

use App\Models\Animals;
use App\Models\Locations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnimalSamplesFactory extends Factory
{
    public function definition(): array
    {

        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        $storageStates = ['Formalin', 'PBS', 'No preservant'];

        // Generate a random date for date_received (within 30 days of date_collected)
        $dateCollected = $this->faker->dateTimeBetween('-1 year', 'now');
        $dateReceived = $this->faker->dateTimeBetween($dateCollected, '+30 days');

        return [
            'code' => "{$project->code}-AS-{$serialNumber}",
            'animals_id' => Animals::query()->inRandomOrder()->value('id') ?? Animals::factory(),
            'sample_types_id' => SampleTypes::query()->inRandomOrder()->value('id') ?? SampleTypes::factory(),
            'date_collected' => fake()->date(),
            'date_received' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'sampling_sites_id' => SamplingSites::query()->inRandomOrder()->value('id') ?? SamplingSites::factory(),
            'area' => fake()->words(3, true),
            'latitude' => fake()->randomFloat(6, -28, -22),
            'longitude' => fake()->randomFloat(6, 22, 32),
            'immobilization_reason' => 'Darting',
            'locations_id' => Locations::query()->inRandomOrder()->value('id') ?? Locations::factory(),
            'storage_state' => fake()->randomElement($storageStates),
            'processed' => false, // Will be updated based on tubes relationship
            'projects_id' => $project->id,
        ];
    }

    /**
     * Configure the factory to set processed to true if tubes exist
     */
    public function configure()
    {
        return $this->afterCreating(function ($animalSample) {
            // Check if the sample has associated tubes
            if ($animalSample->tubes()->count() > 0) {
                $animalSample->update(['processed' => true]);
            }
        });
    }

    /**
     * Indicate that the sample has tubes and should be marked as processed
     */
    public function withTubes()
    {
        return $this->state(function (array $attributes) {
            return [
                'processed' => true,
            ];
        });
    }
}
