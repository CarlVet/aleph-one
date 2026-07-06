<?php

namespace Database\Factories;

use App\Models\Projects;
use App\Models\Techniques;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProtocolsFactory extends Factory
{
    public function definition(): array
    {
        // Get a random project
        $project = Projects::inRandomOrder()->first() ?? Projects::factory()->create();

        // Get a random serial number (zero-padded)
        $serialNumber = fake()->unique()->numberBetween(1, 9999);

        // Get a random technique (prefer existing ones from seeder)
        $technique = Techniques::inRandomOrder()->first() ?? Techniques::factory()->create();

        $protocols = [
            [
                'name' => 'PureLink™ Genomic DNA Mini Kit',
                'technique' => 'gDNA extraction',
            ],
            [
                'name' => 'DNeasy Blood & Tissue Kits',
                'technique' => 'gDNA extraction',
            ],
            [
                'name' => 'GeneJET Plasmid Miniprep Kit',
                'technique' => 'Plasmid DNA extraction',
            ],
            [
                'name' => 'PureLink RNA isolation mini kits',
                'technique' => 'RNA extraction',
            ],
            [
                'name' => 'T. parva p104 cPCR',
                'technique' => 'Conventional PCR',
            ],
            [
                'name' => 'ID-VET ID Screen® Brucella Indirect ELISA',
                'technique' => 'Indirect ELISA',
            ],
            [
                'name' => 'ITS-PCR for fungal identification',
                'technique' => 'Conventional PCR',
            ],
            [
                'name' => '16S rRNA PCR for bacterial identification',
                'technique' => 'Conventional PCR',
            ],
            [
                'name' => 'Real-time PCR for SARS-CoV-2',
                'technique' => 'Real-time PCR',
            ],
            [
                'name' => 'ID-VET FMD Multi-species iELISA',
                'technique' => 'Indirect ELISA',
            ],
            [
                'name' => 'SNAP FIV/FeLV Combo Test',
                'technique' => 'Lateral flow test',
            ],
            [
                'name' => 'Giemsa staining for blood parasites',
                'technique' => 'Light microscopy',
            ],
        ];

        $randomProtocol = $this->faker->unique()->randomElement($protocols);

        // Try to find the specific technique, fallback to random technique
        $specificTechnique = Techniques::where('name', $randomProtocol['technique'])->first() ?? $technique;

        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;

        return [
            'code' => "{$project->code}-PR-{$serialNumber}",
            'name' => $randomProtocol['name'],
            'techniques_id' => $specificTechnique->id,
            'users_id' => $userId,
        ];
    }
}
