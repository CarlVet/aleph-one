<?php

namespace Database\Factories;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Illuminate\Database\Eloquent\Factories\Factory;

class TubesFactory extends Factory
{
    protected static array $usedCodes = [];

    public function definition(): array
    {
        // Define possible related models (including Experiments)
        $sampleModels = [
            HumanSamples::class,
            AnimalSamples::class,
            EnvironmentSamples::class,
            ParasiteSamples::class,
            NucleicAcids::class,
            Cultures::class,
            Pools::class,
            Experiments::class,
        ];

        // Pick one randomly
        $sampleModel = $this->faker->randomElement($sampleModels);

        // Get a random existing sample or create one
        $sample = $sampleModel::inRandomOrder()->first() ?? $sampleModel::factory()->create();

        $project = $sample->projects;

        // Generate a unique code combining sample code and a random 3-digit number
        do {
            $random = (string) $this->faker->numberBetween(1, 9);
            $code = "{$sample->code}-{$random}";
        } while (in_array($code, static::$usedCodes));

        static::$usedCodes[] = $code;

        // Define realistic values for the new fields
        $tubeTypes = ['1.5ml/2ml tube', '200ul tube', '0.5ml tube', '15ml tube', '50ml tube'];
        $preservants = ['Glycerol', 'Elution buffer', 'Formaline', 'Water', 'Ethanol', 'PBS', 'RNAlater'];
        $purposes = [
            'for DNA extraction',
            'for culture',
            'for direct testing',
            'for long-term storage',
            'for RNA extraction',
            'for protein analysis',
            'for microscopy',
            'for sequencing',
        ];
        $amountUnits = ['mg', 'ml', 'ul', 'g', 'mg/ml'];

        return [
            'code' => $code,
            'alias_code' => $this->faker->optional(0.3)->regexify('[A-Z]{2}[0-9]{4}'), // 30% chance of having legacy code
            'tubes_content_id' => $sample->id,
            'tubes_content_type' => $sampleModel,
            'tube_type' => $this->faker->randomElement($tubeTypes),
            'preservant' => $this->faker->randomElement($preservants),
            'purpose' => $this->faker->randomElement($purposes),
            'amount' => $this->faker->optional()->randomFloat(3, 0.1, 10.0),
            'amount_unit' => $this->faker->optional()->randomElement($amountUnits),
            'date_processed' => fake()->date(),
            'projects_id' => $project->id,
            'is_private' => $this->faker->boolean(80), // 80% chance of being private
        ];
    }
}
