<?php

namespace Database\Factories;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\MpsTypes;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use Illuminate\Database\Eloquent\Factories\Factory;

class MicroplasticsFactory extends Factory
{
    public function definition(): array
    {
        $project = Projects::query()->inRandomOrder()->first() ?? Projects::factory()->create();
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        $contentTypes = [
            HumanSamples::class,
            AnimalSamples::class,
            EnvironmentSamples::class,
            ParasiteSamples::class,
            Pools::class,
        ];

        $contentType = fake()->randomElement($contentTypes);
        $contentId = $contentType::query()->inRandomOrder()->value('id') ?? $contentType::factory()->create()->id;

        return [
            'code' => "{$project->code}-MP-{$serialNumber}",
            'microplastics_content_type' => $contentType,
            'microplastics_content_id' => $contentId,
            'sample_weight' => fake()->randomFloat(3, 0.001, 500),
            'r_coeff' => fake()->randomFloat(4, -1, 1),
            'mps_types_id' => MpsTypes::query()->inRandomOrder()->value('id') ?? MpsTypes::factory(),
            'm_feret' => fake()->randomFloat(3, 1, 5000),
            'identification_date' => fake()->date(),
            'is_private' => true,
            'protocols_id' => Protocols::query()->inRandomOrder()->value('id') ?? Protocols::factory(),
            'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'projects_id' => $project->id,
        ];
    }
}
