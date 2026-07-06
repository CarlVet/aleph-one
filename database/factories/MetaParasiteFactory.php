<?php

namespace Database\Factories;

use App\Models\Countries;
use App\Models\MetaParasite;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Projects;
use App\Models\RiskFactors;
use App\Models\Studies;
use App\Models\Techniques;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetaParasite>
 */
class MetaParasiteFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (MetaParasite $metaParasite): void {
            $riskFactorIds = RiskFactors::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->pluck('id');
            if ($riskFactorIds->isEmpty()) {
                $riskFactorIds = collect([RiskFactors::factory()->create()->id]);
            }
            $metaParasite->risk_factors()->sync($riskFactorIds->all());
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
            'parasite_species_id' => ParasiteSpecies::query()->inRandomOrder()->value('id') ?? ParasiteSpecies::factory(),
            'sex' => $this->faker->optional(0.7)->randomElement(['Male', 'Female', 'Cannot differentiate', 'Unknown']),
            'stage' => $this->faker->optional(0.8)->randomElement(['Egg', 'Larva', 'Nymph', 'Pupa', 'Adult', 'Unknown']),
            'location' => $this->faker->optional(0.8)->city(),
            'countries_id' => Countries::query()->inRandomOrder()->value('id') ?? Countries::factory(),
            'date_sampling' => $this->faker->optional(0.9)->date(),
            'parasite_sample_types_id' => ParasiteSampleTypes::query()->inRandomOrder()->value('id') ?? ParasiteSampleTypes::factory(),
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
