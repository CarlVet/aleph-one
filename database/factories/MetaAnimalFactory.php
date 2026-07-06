<?php

namespace Database\Factories;

use App\Models\AnimalSpecies;
use App\Models\ClinicalSigns;
use App\Models\Countries;
use App\Models\Lesions;
use App\Models\MetaAnimal;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Projects;
use App\Models\RiskFactors;
use App\Models\SampleTypes;
use App\Models\Studies;
use App\Models\Techniques;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetaAnimalFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (MetaAnimal $metaAnimal): void {
            $riskFactorIds = RiskFactors::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->pluck('id');
            if ($riskFactorIds->isEmpty()) {
                $riskFactorIds = collect([RiskFactors::factory()->create()->id]);
            }
            $metaAnimal->risk_factors()->sync($riskFactorIds->all());

            $clinicalSignIds = ClinicalSigns::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->pluck('id');
            if ($clinicalSignIds->isEmpty()) {
                $clinicalSignIds = collect([ClinicalSigns::factory()->create()->id]);
            }
            $metaAnimal->clinical_signs()->sync($clinicalSignIds->all());

            $lesionIds = Lesions::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->pluck('id');
            if ($lesionIds->isEmpty()) {
                $lesionIds = collect([Lesions::factory()->create()->id]);
            }
            $metaAnimal->lesions()->sync($lesionIds->all());
        });
    }

    public function definition(): array
    {
        return [
            'studies_id' => Studies::query()->inRandomOrder()->value('id') ?? Studies::factory(),
            'animal_species_id' => AnimalSpecies::query()->inRandomOrder()->value('id') ?? AnimalSpecies::factory(),
            'sex' => $this->faker->optional(0.8)->randomElement(['Male', 'Female', 'Unknown']),
            'age_group' => $this->faker->optional(0.7)->randomElement(['Juvenile', 'Sub-adult', 'Adult', 'Elderly', 'Unknown']),
            'habitat' => $this->faker->optional(0.6)->randomElement(['Savanna', 'Forest', 'Desert', 'Wetland', 'Mountain', 'Coastal', 'Urban']),
            'location' => $this->faker->optional(0.8)->city(),
            'countries_id' => Countries::query()->inRandomOrder()->value('id') ?? Countries::factory(),
            'date_sampling' => $this->faker->optional(0.9)->date(),
            'sample_types_id' => SampleTypes::query()->inRandomOrder()->value('id') ?? SampleTypes::factory(),
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
