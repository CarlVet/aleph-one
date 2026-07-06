<?php

namespace Database\Seeders;

use App\Models\ClinicalSigns;
use App\Models\Countries;
use App\Models\Lesions;
use App\Models\MetaAnimal;
use App\Models\MetaEnvironment;
use App\Models\MetaHuman;
use App\Models\MetaParasite;
use App\Models\RiskFactors;
use Illuminate\Database\Seeder;

class MetaSeeder extends Seeder
{
    public function run(): void
    {
        // Create lesions if none exist
        if (Lesions::count() === 0) {
            Lesions::factory()->count(20)->create();
        }

        // Create clinical signs if none exist
        if (ClinicalSigns::count() === 0) {
            ClinicalSigns::factory()->count(20)->create();
        }

        // Create countries if none exist
        if (Countries::count() === 0) {
            Countries::factory()->count(20)->create();
        }

        // Create risk factors if none exist
        if (RiskFactors::count() === 0) {
            RiskFactors::factory()->count(20)->create();
        }

        // Create meta data entries
        MetaAnimal::factory()->count(50)->create();
        MetaHuman::factory()->count(30)->create();
        MetaParasite::factory()->count(40)->create();
        MetaEnvironment::factory()->count(25)->create();
    }
}
