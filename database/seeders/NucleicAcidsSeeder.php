<?php

namespace Database\Seeders;

use App\Models\NucleicAcids;
use App\Models\Sequences;
use Illuminate\Database\Seeder;

class NucleicAcidsSeeder extends Seeder
{
    public function run(): void
    {
        NucleicAcids::factory(1000)->create();

        Sequences::factory(300)->create();
    }
}
