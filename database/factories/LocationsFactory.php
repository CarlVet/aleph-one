<?php

namespace Database\Factories;

use App\Models\Laboratories;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationsFactory extends Factory
{
    public function definition(): array
    {

        $locations = [
            [
                'name' => 'HEN 1',
                'type' => 'Stand-up freezer',
                'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
                'room' => '2-43',
            ],
            [
                'name' => 'HEN 2',
                'type' => 'Stand-up freezer',
                'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
                'room' => '2-59',
            ],
            [
                'name' => 'HEN 3',
                'type' => 'Stand-up freezer',
                'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
                'room' => '2-32',
            ],
            [
                'name' => 'HEN 4',
                'type' => 'Stand-up freezer',
                'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
                'room' => 'Microlab',
            ],
            [
                'name' => 'HEN 5',
                'type' => 'Stand-up freezer',
                'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
                'room' => 'General Lab',
            ],
        ];

        $randomLocations = $this->faker->unique()->randomElement($locations);

        return [
            'name' => $randomLocations['name'],
            'type' => $randomLocations['type'],
            'laboratories_id' => $randomLocations['laboratories_id'],
            'room' => $randomLocations['room'],
        ];
    }
}
