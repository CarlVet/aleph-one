<?php

namespace Database\Seeders;

use App\Models\Experiments;
use App\Models\Pathogens;
use App\Models\Protocols;
use App\Models\Studies;
use Illuminate\Database\Seeder;

class ExperimentsSeeder extends Seeder
{
    public function run(): void
    {
        $pathogens = Pathogens::factory()->count(63)->create();
        $studies = Studies::factory()->count(3)->create();

        // Get existing protocols (seeded by ProtocolsSeeder)
        $protocols = Protocols::all();

        // Attach pathogens to each protocol
        foreach ($protocols as $protocol) {
            $protocol->pathogens()->attach($pathogens->random(2)->pluck('id'));
            $protocol->studies()->attach($studies->random(2)->pluck('id'));
        }

        Experiments::factory(1792)->create();
    }
}
