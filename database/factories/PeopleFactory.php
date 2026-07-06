<?php

namespace Database\Factories;

use App\Models\Departments;
use App\Models\Organizations;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeopleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Dr.',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'departments_id' => Departments::query()->inRandomOrder()->value('id') ?? Departments::factory()->create()->id,
            'organizations_id' => Organizations::query()->inRandomOrder()->value('id') ?? Organizations::factory()->create()->id,
            'pic_path' => fake()->imageUrl(),
            'orcid' => null,
            'job' => null,
            'email' => null,
        ];
    }
}
