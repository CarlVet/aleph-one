<?php

namespace Database\Seeders;

use App\Models\AnimalMedication;
use App\Models\Animals;
use Illuminate\Database\Seeder;

class AnimalMedicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all animals
        $animals = Animals::all();

        foreach ($animals as $animal) {
            // Create 0-2 medication records per animal (not all animals need medication)
            $medicationCount = rand(0, 2);

            for ($i = 0; $i < $medicationCount; $i++) {
                AnimalMedication::factory()->create([
                    'animals_id' => $animal->id,
                ]);
            }
        }

        $this->command->info('Animal medication records created successfully!');
    }
}
