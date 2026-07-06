<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ParasiteSampleTypesFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Salivary glands', 'Midgut', 'Legs', 'Whole parasite',
                'Head', 'Thorax', 'Abdomen', 'Wings', 'Genitalia', 'Hemolymph', 'Muscle tissue', 'Fat body',
                'Malpighian tubules', 'Ovaries', 'Testes', 'Other',
            ]),
        ];
    }
}
