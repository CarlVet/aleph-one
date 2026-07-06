<?php

namespace Database\Seeders;

use App\Models\Countries;
use App\Models\Organizations;
use App\Models\SamplingSites;
use Illuminate\Database\Seeder;

class SamplingSitesSeeder extends Seeder
{
    public function run(): void
    {
        $parks = Organizations::where('name', 'National Parks Authority')->first();
        $nzg = Organizations::where('name', 'National Zoological Institute')->first();

        SamplingSites::create([
            'name' => 'Northern Wildlife Reserve',
            'organizations_id' => $parks->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Northern Region',
            'latitude' => -24.0000,
            'longitude' => 31.0000,
            'site_type' => 'National Park',
            'description' => 'Large game reserve',
        ]);

        SamplingSites::create([
            'name' => 'Riverside Nature Reserve',
            'organizations_id' => null,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Northern Region',
            'latitude' => -24.2000,
            'longitude' => 28.3000,
            'site_type' => 'Private reserve',
            'description' => 'Private wilderness reserve',
        ]);

        SamplingSites::create([
            'name' => 'Highland National Park',
            'organizations_id' => $parks->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Western Region',
            'latitude' => -32.4000,
            'longitude' => 22.6000,
            'site_type' => 'National Park',
            'description' => 'Semi-desert national park',
        ]);

        SamplingSites::create([
            'name' => 'Mountain View National Park',
            'organizations_id' => $parks->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Central Region',
            'latitude' => -28.5000,
            'longitude' => 28.6000,
            'site_type' => 'National Park',
            'description' => 'Mountainous national park',
        ]);

        SamplingSites::create([
            'name' => 'Savanna Game Reserve',
            'organizations_id' => null,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Northern Region',
            'latitude' => -24.8000,
            'longitude' => 31.5000,
            'site_type' => 'Private reserve',
            'description' => 'Private game reserve',
        ]);

        SamplingSites::create([
            'name' => 'Coastal National Park',
            'organizations_id' => $parks->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Eastern Region',
            'latitude' => -33.4000,
            'longitude' => 25.7000,
            'site_type' => 'National Park',
            'description' => 'Elephant conservation park',
        ]);

        SamplingSites::create([
            'name' => 'Desert National Park',
            'organizations_id' => $parks->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Western Region',
            'latitude' => -30.5000,
            'longitude' => 17.9000,
            'site_type' => 'National Park',
            'description' => 'Desert flora park',
        ]);

        SamplingSites::create([
            'name' => 'Grassland National Park',
            'organizations_id' => $parks->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Eastern Region',
            'latitude' => -32.2000,
            'longitude' => 25.6000,
            'site_type' => 'National Park',
            'description' => 'Zebra conservation area',
        ]);

        SamplingSites::create([
            'name' => 'Transfrontier Conservation Park',
            'organizations_id' => $parks->id,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Western Region',
            'latitude' => -25.5000,
            'longitude' => 20.6000,
            'site_type' => 'National Park',
            'description' => 'Transfrontier conservation area',
        ]);

        SamplingSites::create([
            'name' => 'Wetlands Game Reserve',
            'organizations_id' => null,
            'countries_id' => Countries::where('name', 'South Africa')->first()->id,
            'region' => 'Eastern Region',
            'latitude' => -28.2000,
            'longitude' => 31.9000,
            'site_type' => 'reserve',
            'description' => 'Big game reserve',
        ]);

        // Create additional random sampling sites
        SamplingSites::factory()->count(10)->create();
    }
}
