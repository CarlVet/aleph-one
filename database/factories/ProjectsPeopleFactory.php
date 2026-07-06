<?php

namespace Database\Factories;

use App\Models\People;
use App\Models\Projects;
use App\Models\ProjectsPeople;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectsPeopleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'projects_id' => Projects::query()->inRandomOrder()->value('id') ?? Projects::factory(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'role' => $this->faker->randomElement(['Principal Investigator', 'Supervisor', 'Co-supervisor', 'Collaborator', 'Postgraduate student', 'Undergraduate student', 'Reponsible technologist']),
            'permission' => $this->faker->randomElement(['viewer', 'editor', 'admin']),
            'date_joined' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function createUnique(array $attributes = [])
    {
        $definition = $this->definition();
        $attributes = array_merge($definition, $attributes);

        return ProjectsPeople::firstOrCreate(
            [
                'projects_id' => $attributes['projects_id'],
                'people_id' => $attributes['people_id'],
            ],
            $attributes
        );
    }
}
