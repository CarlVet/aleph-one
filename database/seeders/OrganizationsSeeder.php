<?php

namespace Database\Seeders;

use App\Models\Countries;
use App\Models\Organizations;
use Illuminate\Database\Seeder;

class OrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        Organizations::create([
            'name' => 'Example University',
            'type' => 'University',
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => '1 University Avenue, Example City',
            'website' => 'https://example.edu',
            'description' => 'Public research university',
        ]);

        Organizations::create([
            'name' => 'National Veterinary Research Institute',
            'type' => 'Research institute',
            'countries_id' => Countries::where('name', 'Italy')->first()->id,
            'region' => 'Northern Region',
            'city' => 'Example Town',
            'address' => 'Via Esempio 10, Example Town',
            'website' => 'https://vetinstitute.example.org',
            'description' => 'Veterinary research institute',
        ]);

        Organizations::create([
            'name' => 'National Parks Authority',
            'type' => 'Government',
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => '10 Government Road, Example City',
            'website' => 'https://parks.example.org',
            'description' => 'Agency managing national parks and protected areas',
        ]);

        Organizations::create([
            'name' => 'Agricultural Research Institute',
            'type' => 'Research institute',
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => '20 Research Park, Example City',
            'website' => 'https://agri.example.org',
            'description' => 'Agricultural research organization',
        ]);

        Organizations::create([
            'name' => 'National Zoological Institute',
            'type' => 'Government',
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'city' => 'Example City',
            'address' => '30 Zoo Street, Example City',
            'website' => 'https://zoo.example.org',
            'description' => 'National zoo and research facility',
        ]);

        Organizations::create([
            'name' => 'Example Analytics',
            'type' => 'Company',
            'countries_id' => Countries::where('name', 'Italy')->first()->id,
            'region' => 'Western Region',
            'city' => 'Example Town',
            'website' => 'https://analytics.example.com',
        ]);

        Organizations::create([
            'name' => 'Example Health Corp',
            'type' => 'Company',
            'countries_id' => Countries::where('name', 'United States')->first()->id,
            'region' => 'Eastern Region',
            'city' => 'Example City',
            'website' => 'https://health.example.com',
        ]);

        Organizations::factory()->count(7)->create();
    }
}
