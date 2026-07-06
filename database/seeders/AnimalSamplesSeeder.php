<?php

namespace Database\Seeders;

use App\Models\Animals;
use App\Models\AnimalSamples;
use App\Models\AnimalSpecies;
use App\Models\People;
use App\Models\Projects;
use App\Models\ProjectsPeople;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnimalSamplesSeeder extends Seeder
{
    public function run(): void
    {
        AnimalSpecies::factory()->count(7)->create();

        People::factory()->count(5)->create();

        $this->call(FundingSeeder::class);

        Projects::factory()->count(4)->create();

        // Create unique ProjectsPeople associations
        $this->createUniqueProjectsPeople(6);

        Animals::factory()->count(714)->create();

        AnimalSamples::factory(1503)->create();

    }

    private function createUniqueProjectsPeople(int $count): void
    {
        $projects = Projects::all();
        $people = People::all();
        $roles = ['Supervisor', 'Co-supervisor', 'Collaborator', 'Postgraduate student', 'Undergraduate student', 'Reponsible technologist'];
        $permissions = ['viewer', 'editor', 'admin'];
        $date_joined = Carbon::now();

        $created = 0;
        $attempts = 0;
        $maxAttempts = $count * 10; // Prevent infinite loops

        while ($created < $count && $attempts < $maxAttempts) {
            $project = $projects->random();
            $person = $people->random();
            $role = $roles[array_rand($roles)];
            $permission = $permissions[array_rand($permissions)];

            $existing = ProjectsPeople::where('projects_id', $project->id)
                ->where('people_id', $person->id)
                ->exists();

            if (! $existing) {
                ProjectsPeople::create([
                    'projects_id' => $project->id,
                    'people_id' => $person->id,
                    'role' => $role,
                    'permission' => $permission,
                    'date_joined' => $date_joined,
                ]);
                $created++;
            }

            $attempts++;
        }
    }
}
