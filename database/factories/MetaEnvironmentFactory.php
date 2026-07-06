<?php

namespace Database\Factories;

use App\Models\Countries;
use App\Models\EnvironmentSampleTypes;
use App\Models\MetaEnvironment;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Projects;
use App\Models\RiskFactors;
use App\Models\Studies;
use App\Models\Techniques;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetaEnvironment>
 */
class MetaEnvironmentFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (MetaEnvironment $metaEnvironment): void {
            $riskFactorIds = RiskFactors::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->pluck('id');
            if ($riskFactorIds->isEmpty()) {
                $riskFactorIds = collect([RiskFactors::factory()->create()->id]);
            }
            $metaEnvironment->risk_factors()->sync($riskFactorIds->all());
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'studies_id' => Studies::query()->inRandomOrder()->value('id') ?? Studies::factory(),
            'environment_sample_types_id' => EnvironmentSampleTypes::query()->inRandomOrder()->value('id') ?? EnvironmentSampleTypes::factory(),
            'location' => $this->faker->optional(0.8)->city(),
            'countries_id' => Countries::query()->inRandomOrder()->value('id') ?? Countries::factory(),
            'date_sampling' => $this->faker->optional(0.9)->date(),
            'pathogens_id' => Pathogens::query()->inRandomOrder()->value('id') ?? Pathogens::factory(),
            'techniques_id' => Techniques::query()->inRandomOrder()->value('id') ?? Techniques::factory(),
            'tested_n' => $this->faker->numberBetween(1, 1000),
            'pos_n' => function (array $attributes) {
                return $this->faker->numberBetween(0, $attributes['tested_n']);
            },
            'projects_id' => Projects::query()->inRandomOrder()->value('id') ?? Projects::factory(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
        ];
    }
}
