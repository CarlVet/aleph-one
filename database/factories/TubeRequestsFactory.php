<?php

namespace Database\Factories;

use App\Models\People;
use App\Models\Projects;
use App\Models\TubeRequests;
use App\Models\Tubes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TubeRequests>
 */
class TubeRequestsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random tube that is public
        $tube = Tubes::where('is_private', false)->inRandomOrder()->first() ?? Tubes::factory()->create(['is_private' => false]);

        // Get a random person (requester)
        $requester = People::inRandomOrder()->first() ?? People::factory()->create();

        // Get the source project (tube's project)
        $sourceProject = $tube->projects;

        // Get a random target project (different from source)
        $targetProject = Projects::where('id', '!=', $sourceProject->id)->inRandomOrder()->first() ?? Projects::factory()->create();

        return [
            'tubes_id' => $tube->id,
            'requester_id' => $requester->id,
            'source_project_id' => $sourceProject->id,
            'target_project_id' => $targetProject->id,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'request_message' => $this->faker->optional(0.7)->paragraph(),
            'response_message' => $this->faker->optional(0.5)->paragraph(),
            'responded_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
