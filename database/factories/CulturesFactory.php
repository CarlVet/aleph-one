<?php

namespace Database\Factories;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class CulturesFactory extends Factory
{
    protected $model = Cultures::class;

    public function definition(): array
    {
        $mediums = [
            'Blood Agar',
            'MacConkey Agar',
            'Sabouraud Dextrose Agar',
            'Chocolate Agar',
            'Mueller Hinton Agar',
            'Thayer Martin Agar',
            'Lowenstein Jensen Medium',
            'Middlebrook 7H10 Agar',
            'Brain Heart Infusion Broth',
            'Tryptic Soy Broth',
        ];

        $cultureTypes = [
            'Solid',
            'Liquid',
            'Semi-solid',
            'Biphasic',
            'Cell culture',
        ];

        $atmospheres = [
            'Aerobic',
            'Anaerobic',
            'Microaerophilic',
            'CO2 Enriched',
            'Capnophilic',
        ];

        // Generate a unique code based on project code and culture number
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();
        $serialNumber = str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT);

        // Randomly select a content type for the culture
        $contentTypes = [
            AnimalSamples::class,
            HumanSamples::class,
            ParasiteSamples::class,
        ];
        $contentType = $this->faker->randomElement($contentTypes);
        $content = $contentType::factory()->create();

        return [
            'code' => "{$project->code}-CU-{$serialNumber}",
            'parent_id' => null, // Will be set in the state if needed
            'step' => 1, // Default to step 1 for primary cultures
            'date_cultured' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'medium' => $this->faker->randomElement($mediums),
            'type' => $this->faker->randomElement($cultureTypes), // Physical state of the culture
            'incubation_temp' => $this->faker->randomElement([25, 30, 35, 37, 42]),
            'athmosphere' => $this->faker->randomElement($atmospheres),
            'people_id' => People::inRandomOrder()->first()->id,
            'laboratories_id' => Laboratories::inRandomOrder()->first()->id,
            'projects_id' => $project->id,
            'cultures_content_type' => $contentType,
            'cultures_content_id' => $content->id,
        ];
    }

    /**
     * Configure the model factory to create a subculture.
     * The step field will be incremented to track the subculture order.
     * Subcultures inherit the content from their parent.
     */
    public function subculture(?Cultures $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            if (! $parent) {
                $parent = Cultures::factory()->create();
            }

            return [
                'parent_id' => $parent->id,
                'step' => $parent->step + 1, // Increment step to track subculture order
                'cultures_content_type' => $parent->cultures_content_type,
                'cultures_content_id' => $parent->cultures_content_id,
                'projects_id' => $parent->projects_id,
            ];
        });
    }

    /**
     * Configure the model factory to create a primary culture.
     * Primary cultures always have step 1.
     */
    public function primary(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => null,
                'step' => 1, // Primary culture is always step 1
            ];
        });
    }

    /**
     * Configure the model factory to create a specific type of culture.
     */
    public function type(string $type): static
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type, // Set specific culture type (Solid, Liquid, etc.)
            ];
        });
    }
}
