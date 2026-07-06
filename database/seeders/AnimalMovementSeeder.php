<?php

namespace Database\Seeders;

use App\Models\AnimalMovement;
use App\Models\Animals;
use App\Models\SamplingSites;
use Illuminate\Database\Seeder;

class AnimalMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all animals
        $animals = Animals::all();
        $sampling_sites = SamplingSites::all();

        if ($sampling_sites->count() < 2) {
            $this->command->warn('Need at least 2 sampling sites to create animal movements. Creating some sampling sites first.');
            SamplingSites::factory()->count(5)->create();
            $sampling_sites = SamplingSites::all();
        }

        foreach ($animals as $animal) {
            // Create 0-3 movement records per animal
            $movementCount = rand(0, 3);

            for ($i = 0; $i < $movementCount; $i++) {
                $sourceSite = $sampling_sites->random();
                $destinationSite = $sampling_sites->where('id', '!=', $sourceSite->id)->random();

                AnimalMovement::factory()->create([
                    'animals_id' => $animal->id,
                    'source_sampling_site_id' => $sourceSite->id,
                    'destination_sampling_site_id' => $destinationSite->id,
                ]);
            }
        }

        $this->command->info('Animal movement records created successfully!');
    }
}
