<?php

namespace Database\Seeders;

use App\Models\Boxes;
use App\Models\BoxPositions;
use App\Models\Countries;
use App\Models\Cultures;
use App\Models\Documents;
use App\Models\Locations;
use App\Models\TubePositions;
use App\Models\Tubes;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Countries::create([
            'name' => 'Italy',
        ]);

        Countries::create([
            'name' => 'South Africa',
        ]);

        Countries::create([
            'name' => 'United States',
        ]);

        Countries::factory()->count(100)->create();
        // Seed new organization structure first
        $this->call(OrganizationsSeeder::class);
        $this->call(SamplingSitesSeeder::class);
        $this->call(LaboratoriesSeeder::class);
        $this->call(DepartmentsSeeder::class);

        Locations::factory()->count(5)->create();

        $this->call(ProjectsSeeder::class);

        $this->call(HumanSamplesSeeder::class);

        $this->call(AnimalSamplesSeeder::class);

        // Seed animal-related data
        $this->call(AnimalHealthSeeder::class);
        $this->call(AnimalVaccinationSeeder::class);
        $this->call(AnimalMedicationSeeder::class);
        $this->call(AnimalMovementSeeder::class);

        $this->call(EnvironmentSeeder::class);

        $this->call(ParasiteSamplesSeeder::class);

        // Seed techniques and protocols before experiments
        $this->call(TechniquesSeeder::class);
        $this->call(ProtocolsSeeder::class);
        $this->call(ProtocolCommentsSeeder::class);

        $this->call(ExperimentsSeeder::class);

        $this->call(NucleicAcidsSeeder::class);

        // Create primary cultures
        $primaryCultures = Cultures::factory()->count(20)->create();

        // Create subcultures for each primary culture
        foreach ($primaryCultures as $primaryCulture) {
            // Create 1-3 subcultures for each primary culture
            Cultures::factory()
                ->count(rand(1, 3))
                ->subculture($primaryCulture)
                ->create();
        }

        $this->call(PoolSeeder::class);

        $this->call(MetaSeeder::class);

        Tubes::factory(200)->create();
        Boxes::factory(50)->create();

        TubePositions::factory(100)->create();
        BoxPositions::factory(100)->create();

        $this->call(FundingSeeder::class);

        // Create documents with proper parent-child relationships
        $this->call(DocumentsSeeder::class);
    }
}
