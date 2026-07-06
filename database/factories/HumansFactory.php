<?php

namespace Database\Factories;

use App\Models\Countries;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class HumansFactory extends Factory
{
    public function definition(): array
    {

        $sex = $this->faker->randomElement(['Male', 'Female']);
        $firstName = $sex === 'Male' ? $this->faker->firstNameMale() : $this->faker->firstNameFemale();

        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'projects_id' => $project->id,
            'code' => "{$project->code}-HU-{$serialNumber}",
            'first_name' => $firstName,
            'last_name' => $this->faker->lastName(),
            'sex' => $sex,
            'date_of_birth' => $this->faker->date(),
            'ethnicity' => $this->faker->randomElement(['African', 'Asian', 'Caucasian', 'Hispanic', 'Mixed']),
            'occupation' => $this->faker->jobTitle(),
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'province' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'countries_id' => Countries::query()->inRandomOrder()->value('id') ?? Countries::factory(),
            'preferred_contact_method' => $this->faker->randomElement(['phone', 'email', 'sms']),
            'phone' => $this->faker->phoneNumber(),
            'alternate_phone' => $this->faker->optional(0.3)->phoneNumber(),
            'email' => $this->faker->email(),
            'alternate_email' => $this->faker->optional(0.3)->email(),
            'national_id' => $this->faker->optional(0.8)->numerify('##########'),
            'marital_status' => $this->faker->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'insurance_provider' => $this->faker->optional(0.7)->company(),
            'insurance_id' => $this->faker->optional(0.7)->numerify('INS-####-####'),
            'photo_path' => null,
            'projects_id' => $project->id,
        ];
    }
}
