<?php

namespace Database\Seeders;

use App\Models\Countries;
use App\Models\Laboratories;
use App\Models\Organizations;
use Illuminate\Database\Seeder;

class LaboratoriesSeeder extends Seeder
{
    public function run(): void
    {
        $up = Organizations::where('name', 'Example University')->first();
        $izs = Organizations::where('name', 'National Veterinary Research Institute')->first();
        $arc = Organizations::where('name', 'Agricultural Research Institute')->first();
        $nzg = Organizations::where('name', 'National Zoological Institute')->first();

        Laboratories::create([
            'name' => 'Department of Veterinary Tropical Diseases',
            'organizations_id' => $up->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => 'Main Campus, Example University',
            'lab_type' => 'academic',
            'description' => 'Veterinary research laboratory',
        ]);

        Laboratories::create([
            'name' => 'Wildlife Research Station',
            'organizations_id' => $up->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Northern Region',
            'city' => 'Example Town',
            'address' => 'Wildlife Reserve, Example Town',
            'lab_type' => 'research',
            'description' => 'Wildlife research facility',
        ]);

        Laboratories::create([
            'name' => 'Regional Veterinary Laboratory',
            'organizations_id' => $izs->id,
            'countries_id' => Countries::where('name', 'Italy')->first()->id,
            'region' => 'Southern Region',
            'city' => 'Example Town',
            'address' => 'Via Esempio 20, Example Town',
            'lab_type' => 'research',
            'description' => 'Veterinary research laboratory',
        ]);

        Laboratories::create([
            'name' => 'Veterinary Academic Hospital',
            'organizations_id' => $up->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => 'Main Campus, Example University',
            'lab_type' => 'diagnostic',
            'description' => 'Veterinary hospital and diagnostic laboratory',
        ]);

        Laboratories::create([
            'name' => 'Veterinary Research Institute Laboratory',
            'organizations_id' => $arc->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => '20 Research Park, Example City',
            'lab_type' => 'research',
            'description' => 'Veterinary research institute',
        ]);

        Laboratories::create([
            'name' => 'Zoo Research Laboratory',
            'organizations_id' => $nzg->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => '30 Zoo Street, Example City',
            'lab_type' => 'research',
            'description' => 'Zoo research laboratory',
        ]);

        Laboratories::create([
            'name' => 'Regional Diagnostic Laboratory',
            'organizations_id' => $izs->id,
            'countries_id' => Countries::where('name', 'Italy')->first()->id,
            'region' => 'Western Region',
            'city' => 'Example Town',
            'address' => 'Via Esempio 30, Example Town',
            'lab_type' => 'research',
            'description' => 'Veterinary research laboratory',
        ]);

        Laboratories::factory()->count(6)->create();
    }
}
