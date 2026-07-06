<?php

namespace Database\Factories;

use App\Models\Countries;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationsFactory extends Factory
{
    public function definition(): array
    {
        $organizationTypes = ['Company', 'University', 'NGO', 'Research institute'];

        return [
            'name' => $this->faker->unique()->company(),
            'type' => $this->faker->randomElement($organizationTypes),
            'countries_id' => Countries::query()->inRandomOrder()->value('id') ?? Countries::factory(),
            'region' => $this->faker->state(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'website' => $this->faker->url(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
