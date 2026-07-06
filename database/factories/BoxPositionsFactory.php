<?php

namespace Database\Factories;

use App\Models\Boxes;
use App\Models\Locations;
use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoxPositionsFactory extends Factory
{
    public function definition(): array
    {
        // Get an existing box or create a new one
        $box = Boxes::inRandomOrder()->first() ?? Boxes::factory()->create();

        return [
            'boxes_id' => $box->id,
            'locations_id' => Locations::inRandomOrder()->first()->id,
            'sublocation' => fake()->optional()->word(),
            'date_moved' => fake()->date(),
            'people_id' => People::inRandomOrder()->first()->id,
            'reason' => fake()->optional()->sentence(6, true),
        ];
    }
}
