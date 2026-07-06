<?php

namespace Database\Factories;

use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoxesFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => "{$project->code}-BO-{$serialNumber}",
            'name' => fake()->unique()->word().' Box',
            'content_type' => fake()->randomElement(['Animal samples', 'Parasite samples', 'Animal nucleic acids']),
            'content_state' => fake()->randomElement(['Native', 'Glycerol', 'Miscellaneous']),
            'n_rows' => fake()->numberBetween(6, 12),  // Random number of rows
            'n_columns' => fake()->numberBetween(6, 12),  // Random number of columns
            'projects_id' => $project->id,
        ];
    }
}
