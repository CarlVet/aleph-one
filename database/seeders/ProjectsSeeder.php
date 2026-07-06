<?php

namespace Database\Seeders;

use App\Models\People;
use App\Models\Projects;
use App\Models\ProjectsPeople;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    public function run(): void
    {
        // Single demo administrator. Everything is fictional — change these
        // credentials right after installing.
        $admin = People::factory()->create([
            'title' => 'Dr.',
            'first_name' => 'Demo',
            'last_name' => 'Administrator',
            'job' => 'Administrator',
            'orcid' => null,
            'email' => 'admin@example.org',
        ]);

        User::factory()->create([
            'people_id' => $admin->id,
            'email' => 'admin@example.org',
            'password' => bcrypt('password'),
            'permission' => 'Admin',
        ]);

        // A few more fictional people so samples, experiments and project
        // memberships have members to attach to.
        People::factory()->count(4)->create();

        // Example projects (generic topics, no real research data).
        $projectsData = [
            [
                'code' => 'DEMO1',
                'type' => 'PhD project',
                'title' => 'Pathogen surveillance in wildlife reserves',
            ],
            [
                'code' => 'DEMO2',
                'type' => 'MSc project',
                'title' => 'Vector-borne disease prevalence in African wildlife',
            ],
        ];

        foreach ($projectsData as $data) {
            Projects::factory()->create($data);
        }

        // Link the administrator to the first project as principal investigator.
        ProjectsPeople::factory()->create([
            'projects_id' => Projects::first()->id,
            'people_id' => $admin->id,
            'role' => 'Principal Investigator',
            'permission' => 'admin',
            'date_joined' => '2024-01-01',
        ]);

        $this->call(FundingSeeder::class);
    }
}
