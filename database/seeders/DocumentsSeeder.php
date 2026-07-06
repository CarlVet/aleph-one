<?php

namespace Database\Seeders;

use App\Models\Documents;
use App\Models\Projects;
use Illuminate\Database\Seeder;

class DocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, create regular documents (non-amendments)
        $projects = Projects::all();

        if ($projects->isEmpty()) {
            $this->command->warn('No projects found. Creating documents without projects.');

            return;
        }

        // Create parent documents first
        $parentDocuments = [];
        foreach ($projects as $project) {
            // Create 3-5 parent documents per project
            $numDocuments = rand(3, 5);

            for ($i = 0; $i < $numDocuments; $i++) {
                $document = Documents::factory()->create([
                    'projects_id' => $project->id,
                ]);
                $parentDocuments[] = $document;
            }
        }

        // Now create amendments for some of the parent documents
        foreach ($parentDocuments as $parentDoc) {
            // 30% chance to create an amendment for each parent document
            if (rand(1, 100) <= 30) {
                Documents::factory()->amendment($parentDoc)->create([
                    'projects_id' => $parentDoc->projects_id,
                ]);
            }
        }

        $this->command->info('Documents and amendments seeded successfully!');
    }
}
