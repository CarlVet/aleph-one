<?php

namespace Database\Factories;

use App\Models\AnimalMedication;
use App\Models\Animals;
use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalMedication>
 */
class AnimalMedicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $medications = [
            'Amoxicillin',
            'Cephalexin',
            'Doxycycline',
            'Metronidazole',
            'Prednisone',
            'Rimadyl',
            'Tramadol',
            'Gabapentin',
            'Omeprazole',
            'Famotidine',
            'Ivermectin',
            'Fenbendazole',
            'Praziquantel',
            'Meloxicam',
            'Buprenorphine',
        ];

        $dosages = [
            '10mg/kg twice daily',
            '5mg/kg once daily',
            '2mg/kg every 8 hours',
            '1mg/kg every 12 hours',
            '0.5mg/kg once daily',
            '15mg/kg once daily',
            '2mg/kg every 6 hours',
        ];

        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $endDate = $this->faker->optional(0.8)->dateTimeBetween($startDate, '+3 months');

        return [
            'animals_id' => Animals::factory(),
            'medication_name' => $this->faker->randomElement($medications),
            'dosage' => $this->faker->randomElement($dosages),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'prescribed_by' => People::inRandomOrder()->first()?->id ?? People::factory(),
            'notes' => $this->faker->optional(0.6)->sentence(),
        ];
    }
}
