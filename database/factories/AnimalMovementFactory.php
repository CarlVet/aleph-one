<?php

namespace Database\Factories;

use App\Models\Animals;
use App\Models\SamplingSites;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnimalMovementFactory extends Factory
{
    public function definition(): array
    {
        $movementReasons = [
            'Relocation',
            'Treatment',
            'Breeding',
            'Research',
            'Conservation',
            'Rehabilitation',
            'Release',
            'Quarantine',
            'Medical Check-up',
            'Habitat Management',
        ];

        // Generate coordinates within South Africa bounds
        $startLat = $this->faker->randomFloat(8, -35, -22);
        $startLng = $this->faker->randomFloat(8, 16, 33);
        $destLat = $this->faker->randomFloat(8, -35, -22);
        $destLng = $this->faker->randomFloat(8, 16, 33);

        return [
            'animals_id' => Animals::factory(),
            'source_sampling_site_id' => SamplingSites::inRandomOrder()->first() ?? SamplingSites::factory(),
            'destination_sampling_site_id' => SamplingSites::inRandomOrder()->first() ?? SamplingSites::factory(),
            'date_moved' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'coordinates_start_lat' => $startLat,
            'coordinates_start_lng' => $startLng,
            'coordinates_destination_lat' => $destLat,
            'coordinates_destination_lng' => $destLng,
            'movement_reason' => $this->faker->randomElement($movementReasons),
            'notes' => $this->faker->optional(0.7)->sentence(),
        ];
    }
}
