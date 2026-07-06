<?php

namespace Database\Factories;

use App\Models\Fundings;
use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundingsFactory extends Factory
{
    protected $model = Fundings::class;

    public function definition(): array
    {
        return [
            'source' => $this->faker->company,
            'recipient_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'amount' => $this->faker->randomFloat(2, 1000, 1000000),
            'currency' => 'ZAR',
            'reference' => $this->faker->bothify('FUND-####-????'),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
        ];
    }
}
