<?php

namespace Database\Factories;

use App\Models\AnimalSpecies;
use App\Models\Humans;
use App\Models\Organizations;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnimalsFactory extends Factory
{
    public function definition(): array
    {

        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        // Determine the owner type and get the corresponding sample
        $type = $this->faker->randomElement(['human', 'company']);
        $ownerId = null;
        $ownerType = null;

        switch ($type) {
            case 'human':
                $owner = Humans::inRandomOrder()->first() ?? Humans::factory()->create();
                $ownerId = $owner->id;
                $ownerType = Humans::class;
                break;

            case 'company':
                $owner = Organizations::inRandomOrder()->first() ?? Organizations::factory()->create();
                $ownerId = $owner->id;
                $ownerType = Organizations::class;
                break;
        }

        return [
            'code' => "{$project->code}-AN-{$serialNumber}",
            'animal_species_id' => AnimalSpecies::query()->inRandomOrder()->value('id') ?? AnimalSpecies::factory(),
            'field_label' => 'KNP_'.fake()->unique()->randomNumber(3, true),
            'sex' => $this->faker->randomElement(['Male', 'Female']),
            'age' => $this->faker->randomElement(['Juvenile', 'Sub-adult', 'Adult']),
            'owner_id' => $ownerId,
            'owner_type' => $ownerType,
            'projects_id' => $project->id,
        ];
    }
}
