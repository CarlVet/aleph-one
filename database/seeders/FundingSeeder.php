<?php

namespace Database\Seeders;

use App\Models\Fundings;
use App\Models\Projects;
use App\Models\ProjectsFundings;
use Illuminate\Database\Seeder;

class FundingSeeder extends Seeder
{
    public function run(): void
    {
        Fundings::factory(50)->create();

        $projectIds = Projects::pluck('id')->all();
        $fundingIds = Fundings::pluck('id')->all();

        $combinations = collect($projectIds)
            ->crossJoin($fundingIds)
            ->shuffle()
            ->take(50); // Limit to N pairs

        foreach ($combinations as [$projectId, $fundingId]) {
            ProjectsFundings::firstOrCreate([
                'projects_id' => $projectId,
                'fundings_id' => $fundingId,
            ]);
        }
    }
}
