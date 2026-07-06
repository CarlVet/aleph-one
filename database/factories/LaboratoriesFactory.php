<?php

namespace Database\Factories;

use App\Models\Countries;
use App\Models\Organizations;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaboratoriesFactory extends Factory
{
    public function definition(): array
    {
        $labTypes = ['research', 'diagnostic', 'commercial', 'academic', 'government'];

        $labSuffixes = ['Laboratory', 'Lab', 'Institute', 'Center', 'Facility', 'Research Center', 'Testing Laboratory'];

        return [
            'name' => $this->faker->unique()->company().' '.$this->faker->randomElement($labSuffixes),
            'organizations_id' => Organizations::query()->inRandomOrder()->value('id') ?? Organizations::factory(),
            'countries_id' => Countries::query()->inRandomOrder()->value('id') ?? Countries::factory(),
            'region' => $this->faker->state(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'lab_type' => $this->faker->randomElement($labTypes),
            'description' => $this->faker->paragraph(),
        ];
    }
}
