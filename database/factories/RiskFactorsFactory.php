<?php

namespace Database\Factories;

use App\Models\RiskFactors;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiskFactors>
 */
class RiskFactorsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $riskFactors = [
            'Age',
            'Sex',
            'Breed',
            'Season',
            'Geographic Location',
            'Housing Conditions',
            'Population Density',
            'Vaccination Status',
            'Previous Disease History',
            'Contact with Wildlife',
            'Water Source',
            'Feed Source',
            'Management Practices',
            'Climate Conditions',
            'Vector Presence',
            'Sanitation Level',
            'Biosecurity Measures',
            'Animal Movement',
            'Human Contact',
            'Environmental Contamination',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($riskFactors),
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
