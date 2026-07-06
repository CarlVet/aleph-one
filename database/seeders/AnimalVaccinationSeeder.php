<?php

namespace Database\Seeders;

use App\Models\Animals;
use App\Models\AnimalVaccination;
use Illuminate\Database\Seeder;

class AnimalVaccinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all animals
        $animals = Animals::all();

        foreach ($animals as $animal) {
            // Create 1-3 vaccination records per animal
            $vaccinationCount = rand(1, 3);

            for ($i = 0; $i < $vaccinationCount; $i++) {
                AnimalVaccination::factory()->create([
                    'animals_id' => $animal->id,
                ]);
            }
        }

        $this->command->info('Animal vaccination records created successfully!');
    }
}
