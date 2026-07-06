<?php

namespace Database\Factories;

use App\Models\MpsTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MpsTypes>
 */
class MpsTypesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Polyamide',
                'Polycarbonate',
                'Polyester',
                'Polyethylene',
                'Polypropylene',
                'Polystyrene',
                'Polyurethane',
                'PVC',
            ]),
        ];
    }
}
