<?php

namespace Database\Factories;

use App\Models\Animals;
use App\Models\AnimalVaccination;
use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalVaccination>
 */
class AnimalVaccinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vaccines = [
            'Rabies Vaccine',
            'Distemper Vaccine',
            'Parvovirus Vaccine',
            'Bordetella Vaccine',
            'Leptospirosis Vaccine',
            'Lyme Disease Vaccine',
            'Canine Influenza Vaccine',
            'Feline Leukemia Vaccine',
            'FVRCP Vaccine',
            'Feline Immunodeficiency Virus Vaccine',
        ];

        $vaccineTypes = ['Core', 'Non-core', 'Optional', 'Required'];

        $dateAdministered = $this->faker->dateTimeBetween('-2 years', 'now');
        $nextDueDate = $this->faker->dateTimeBetween($dateAdministered, '+2 years');

        return [
            'animals_id' => Animals::factory(),
            'vaccine_name' => $this->faker->randomElement($vaccines),
            'vaccine_type' => $this->faker->randomElement($vaccineTypes),
            'date_administered' => $dateAdministered,
            'next_due_date' => $nextDueDate,
            'administered_by' => People::inRandomOrder()->first()?->id ?? People::factory(),
            'notes' => $this->faker->optional(0.7)->sentence(),
        ];
    }
}
