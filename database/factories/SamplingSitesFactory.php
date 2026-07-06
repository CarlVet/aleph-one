<?php

namespace Database\Factories;

use App\Models\Countries;
use App\Models\Organizations;
use Illuminate\Database\Eloquent\Factories\Factory;

class SamplingSitesFactory extends Factory
{
    public function definition(): array
    {
        $siteTypes = ['Hospital', 'Clinic', 'Natural Park', 'Farm', 'Zoo', 'Sanctuary'];

        $siteNames = [
            'Pilanesberg Game Reserve',
            'Madikwe Game Reserve',
            'iSimangaliso Wetland Park',
            'Garden Route National Park',
            'Camdeboo National Park',
            'Agulhas National Park',
            'Bontebok National Park',
            'Richtersveld National Park',
            'Tankwa Karoo National Park',
            'West Coast National Park',
            'Mokala National Park',
            'Mapungubwe National Park',
            'Marakele National Park',
            'Augrabies Falls National Park',
            'Tsitsikamma National Park',
            'Wilderness National Park',
            'Knysna National Lake Area',
            'Robberg Nature Reserve',
            'De Hoop Nature Reserve',
            'Cape Point Nature Reserve',
            'Boulders Penguin Colony',
            'Betty\'s Bay Nature Reserve',
            'Strandfontein Bird Sanctuary',
            'Rietvlei Nature Reserve',
            'Suikerbosrand Nature Reserve',
            'Krugersdorp Game Reserve',
            'Lion Park',
            'Rhino and Lion Nature Reserve',
            'Cradle of Humankind',
            'Sterkfontein Caves',
            'Wonder Cave',
            'Maropeng Visitor Centre',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($siteNames),
            'organizations_id' => Organizations::query()->inRandomOrder()->value('id') ?? Organizations::factory(),
            'countries_id' => Countries::query()->inRandomOrder()->value('id') ?? Countries::factory(),
            'region' => $this->faker->state(),
            'latitude' => $this->faker->randomFloat(6, -35, 35),
            'longitude' => $this->faker->randomFloat(6, -180, 180),
            'site_type' => $this->faker->randomElement($siteTypes),
            'description' => $this->faker->paragraph(),
        ];
    }
}
