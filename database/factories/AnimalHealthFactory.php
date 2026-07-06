<?php

namespace Database\Factories;

use App\Models\AnimalHealth;
use App\Models\Animals;
use App\Models\ClinicalSigns;
use App\Models\Lesions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalHealth>
 */
class AnimalHealthFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $healthStatuses = ['Healthy', 'Sick', 'Recovering', 'Under Treatment'];
        $checkTypes = ['Routine', 'Follow-up', 'Emergency', 'Treatment', 'Pre-release'];

        return [
            'animals_id' => Animals::factory(),
            'health_status' => $this->faker->randomElement($healthStatuses),
            'check_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'check_type' => $this->faker->randomElement($checkTypes),
            'clinical_signs_id' => ClinicalSigns::factory(),
            'lesions_id' => Lesions::factory(),
            'alive' => $this->faker->boolean(90), // 90% chance of being alive
        ];
    }
}
