<?php

namespace Database\Factories;

use App\Models\Boxes;
use App\Models\People;
use App\Models\Tubes;
use Illuminate\Database\Eloquent\Factories\Factory;

class TubePositionsFactory extends Factory
{
    public function definition(): array
    {
        // Get an existing box or create a new one
        $box = Boxes::inRandomOrder()->first() ?? Boxes::factory()->create();

        // Get an existing tube or create a new one
        $tube = Tubes::inRandomOrder()->first() ?? Tubes::factory()->create();

        return [
            'tubes_id' => $tube->id,
            'boxes_id' => $box->id,
            'position_x' => fake()->numberBetween(1, $box->n_columns),
            'position_y' => fake()->numberBetween(1, $box->n_rows),
            'date_moved' => fake()->date(),
            'people_id' => People::inRandomOrder()->first()->id,
            'reason' => fake()->optional()->sentence(6, true),
        ];
    }
}
