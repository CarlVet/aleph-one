<?php

namespace Tests\Feature;

use App\Models\Animals;
use App\Models\AnimalSamples;
use App\Models\Humans;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function userInProject(Projects $project): User
    {
        $user = User::factory()->create();

        DB::table('projects_people')->insert([
            'people_id' => $user->people_id,
            'projects_id' => $project->id,
            'role' => 'member',
            'permission' => 'read',
        ]);

        return $user;
    }

    /**
     * Base attributes for a sample, built from single shared reference rows.
     * Created directly (not via the sample factory) to avoid its SamplingSites
     * cascade, whose LocationsFactory has only a few unique values.
     *
     * @return array<string, mixed>
     */
    /** @var array<string, int> */
    private array $deps = [];

    private function sampleBase(Projects $project): array
    {
        if ($this->deps === []) {
            $this->deps = [
                // Created directly: LocationsFactory draws from a tiny unique pool that exhausts.
                'location' => Locations::create([
                    'name' => 'API Test Location',
                    'laboratories_id' => Laboratories::factory()->create()->id,
                ])->id,
                'sample_type' => SampleTypes::factory()->create()->id,
                'person' => People::factory()->create()->id,
            ];
        }

        return [
            'code' => 'API-'.uniqid(),
            'sample_types_id' => $this->deps['sample_type'],
            'people_id' => $this->deps['person'],
            'locations_id' => $this->deps['location'],
            'date_collected' => now()->toDateString(),
            'projects_id' => $project->id,
        ];
    }

    public function test_token_endpoint_issues_a_bearer_token_for_valid_credentials(): void
    {
        User::factory()->create(['email' => 'api@example.test', 'password' => Hash::make('secret-pass')]);

        $this->postJson('/api/v1/auth/token', [
            'email' => 'api@example.test',
            'password' => 'secret-pass',
            'device_name' => 'integration-test',
        ])->assertOk()->assertJsonStructure(['token', 'token_type']);
    }

    public function test_token_endpoint_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'api@example.test', 'password' => Hash::make('secret-pass')]);

        $this->postJson('/api/v1/auth/token', [
            'email' => 'api@example.test',
            'password' => 'wrong',
            'device_name' => 'integration-test',
        ])->assertStatus(422);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/projects')->assertUnauthorized();
        $this->getJson('/api/v1/animal-samples')->assertUnauthorized();
    }

    public function test_resources_are_scoped_to_the_users_projects(): void
    {
        $mine = Projects::factory()->create();
        $other = Projects::factory()->create();
        $user = $this->userInProject($mine);
        $animal = Animals::factory()->create()->id;

        $visible = AnimalSamples::create($this->sampleBase($mine) + ['animals_id' => $animal, 'immobilization_reason' => 'n/a']);
        $hidden = AnimalSamples::create($this->sampleBase($other) + ['animals_id' => $animal, 'immobilization_reason' => 'n/a']);

        Sanctum::actingAs($user);

        $ids = collect($this->getJson('/api/v1/animal-samples')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($visible->id));
        $this->assertFalse($ids->contains($hidden->id));
    }

    public function test_human_sample_resource_omits_patient_pii_and_precise_location(): void
    {
        $project = Projects::factory()->create();
        $user = $this->userInProject($project);

        HumanSamples::create($this->sampleBase($project) + ['humans_id' => Humans::factory()->create()->id]);

        Sanctum::actingAs($user);

        $row = $this->getJson('/api/v1/human-samples')->assertOk()->json('data.0');

        foreach (['latitude', 'longitude', 'national_id', 'first_name', 'last_name'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $row);
        }
    }
}
