<?php

namespace Database\Factories;

use App\Models\Pathogens;
use App\Models\Protocols;
use Illuminate\Database\Eloquent\Factories\Factory;

class PathogensProtocolsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pathogens_id' => Pathogens::query()->inRandomOrder()->value('id') ?? Pathogens::factory(),
            'protocols_id' => Protocols::query()->inRandomOrder()->value('id') ?? Protocols::factory(),
        ];
    }
}
