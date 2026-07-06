<?php

namespace Database\Factories;

use App\Models\Fundings;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectsFundingsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fundings_id' => Fundings::query()->inRandomOrder()->value('id') ?? Fundings::factory(),
            'projects_id' => Projects::query()->inRandomOrder()->value('id') ?? Projects::factory(),
        ];
    }
}
