<?php

namespace Database\Factories;

use App\Models\Fundings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->regexify('[A-Z][1-9][A-Z][1-9]'),
            'type' => $this->faker->randomElement(['PhD project', 'MSc project', 'Research assignment', 'Publication-related project']),
            'title' => $this->faker->sentence(10),
            'description' => $this->faker->paragraph,
            'ethics_ref' => $this->faker->optional()->bothify('ETH-####-????'),
            'date_started' => $startDate = $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'date_end_intended' => $this->faker->dateTimeBetween(
                Carbon::parse($startDate)->addMonths(6),
                Carbon::parse($startDate)->addYears(2)
            )->format('Y-m-d'),
            'date_end' => $this->faker->dateTimeBetween(
                Carbon::parse($startDate)->addMonths(6),
                Carbon::parse($startDate)->addYears(2)
            )->format('Y-m-d'),
            'status' => $this->faker->randomElement(['active', 'completed', 'on_hold', 'cancelled']),
            'objectives' => $this->faker->optional()->paragraphs(3, true),
            'methodology' => $this->faker->optional()->paragraphs(2, true),
            'expected_outcomes' => $this->faker->optional()->paragraphs(2, true),
            'notes' => $this->faker->optional()->paragraph,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($project) {
            // Attach 1-3 random fundings to each project
            $fundings = Fundings::inRandomOrder()->take(rand(1, 3))->get();
            $project->fundings()->attach($fundings);
        });
    }
}
