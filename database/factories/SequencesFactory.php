<?php

namespace Database\Factories;

use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class SequencesFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => "{$project->code}-SE-{$serialNumber}",
            'accession_number' => fake()->unique()->regexify('[A-Z]{2}[0-9]{6,9}'),
            'nucleic_acids_id' => NucleicAcids::query()->inRandomOrder()->value('id') ?? NucleicAcids::factory(),
            'length' => fake()->numberBetween(100, 1000000000),
            'method' => $this->faker->randomElement(['Sanger sequencing', 'Next generation sequencing', 'Whole genome sequencing']),
            'instrument' => $this->faker->randomElement([
                'Illumina',
                'PacBio',
                'Oxford Nanopore',
                'ABI 3730',
                'Ion Torrent',
                'Roche 454',
                'ABI 3130',
                'ABI 3100',
                'ABI 3500',
                'Illumina MiSeq',
                'Illumina HiSeq',
                'Illumina NovaSeq',
            ]),
            'date_sequenced' => fake()->date(),
            'people_id' => People::query()->inRandomOrder()->value('id') ?? People::factory(),
            'laboratories_id' => Laboratories::query()->inRandomOrder()->value('id') ?? Laboratories::factory(),
            'projects_id' => $project->id,
            'fasta_path' => null,
        ];
    }
}
