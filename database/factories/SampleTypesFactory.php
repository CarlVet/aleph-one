<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SampleTypesFactory extends Factory
{
    public function definition(): array
    {

        $sample_types = [
            'Liver', 'Spleen', 'Kidney', 'Lymph nodes', 'EDTA blood', 'Serum', 'Parasites', 'Bone marrow',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($sample_types),
        ];
    }
}
