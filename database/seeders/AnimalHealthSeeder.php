<?php

namespace Database\Seeders;

use App\Models\AnimalHealth;
use App\Models\Animals;
use App\Models\ClinicalSigns;
use App\Models\Lesions;
use Illuminate\Database\Seeder;

class AnimalHealthSeeder extends Seeder
{
    public function run(): void
    {
        // Get all animals that don't have health records
        $animalsWithoutHealth = Animals::whereDoesntHave('animal_health')->get();

        foreach ($animalsWithoutHealth as $animal) {
            // Create health record for each animal
            AnimalHealth::factory()->create([
                'animals_id' => $animal->id,
                'clinical_signs_id' => ClinicalSigns::inRandomOrder()->first()?->id ?? ClinicalSigns::factory(),
                'lesions_id' => Lesions::inRandomOrder()->first()?->id ?? Lesions::factory(),
            ]);
        }

        $this->command->info('Animal health records created successfully!');
    }
}
