<?php

namespace Database\Factories;

use App\Models\ProtocolComments;
use App\Models\Protocols;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProtocolComments>
 */
class ProtocolCommentsFactory extends Factory
{
    protected $model = ProtocolComments::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $protocolId = Protocols::query()->inRandomOrder()->value('id') ?? Protocols::factory()->create()->id;
        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;

        return [
            'protocols_id' => $protocolId,
            'users_id' => $userId,
            'parent_id' => null,
            'body' => fake()->paragraph(),
        ];
    }

    public function replyTo(ProtocolComments $parent): self
    {
        return $this->state(fn () => [
            'protocols_id' => $parent->protocols_id,
            'parent_id' => $parent->id,
        ]);
    }
}
