<?php

namespace Database\Seeders;

use App\Models\Techniques;
use Illuminate\Database\Seeder;

class TechniquesSeeder extends Seeder
{
    public function run(): void
    {
        $techniques = [
            // Nucleic Acids Detection Tests (Screening)
            [
                'name' => 'Conventional PCR',
                'type' => 'Nucleic acids detection test',
            ],
            [
                'name' => 'Real-time PCR',
                'type' => 'Nucleic acids detection test',
            ],
            [
                'name' => 'Nested PCR',
                'type' => 'Nucleic acids detection test',
            ],
            [
                'name' => 'Multiplex PCR',
                'type' => 'Nucleic acids detection test',
            ],
            [
                'name' => 'Loop-mediated isothermal amplification (LAMP)',
                'type' => 'Nucleic acids detection test',
            ],

            // Antibody Detection Tests (Screening)
            [
                'name' => 'Indirect ELISA',
                'type' => 'Antibody detection test',
            ],
            [
                'name' => 'Competitive ELISA',
                'type' => 'Antibody detection test',
            ],
            [
                'name' => 'Sandwich ELISA',
                'type' => 'Antibody detection test',
            ],
            [
                'name' => 'Western blot',
                'type' => 'Antibody detection test',
            ],
            [
                'name' => 'Immunofluorescence assay (IFA)',
                'type' => 'Antibody detection test',
            ],
            [
                'name' => 'Complement fixation test (CFT)',
                'type' => 'Antibody detection test',
            ],
            [
                'name' => 'Agar gel immunodiffusion (AGID)',
                'type' => 'Antibody detection test',
            ],
            [
                'name' => 'Rose Bengal test (RBT)',
                'type' => 'Antibody detection test',
            ],

            // Antigen Detection Tests (Screening)
            [
                'name' => 'Lateral flow test',
                'type' => 'Antigen detection test',
            ],
            [
                'name' => 'Direct ELISA',
                'type' => 'Antigen detection test',
            ],
            [
                'name' => 'Immunohistochemistry (IHC)',
                'type' => 'Antigen detection test',
            ],
            [
                'name' => 'Immunochromatographic test',
                'type' => 'Antigen detection test',
            ],

            // Nucleic Acids Extraction and Purification
            [
                'name' => 'gDNA extraction',
                'type' => 'Nucleic Acids Extraction and Purification',
            ],
            [
                'name' => 'Plasmid DNA extraction',
                'type' => 'Nucleic Acids Extraction and Purification',
            ],
            [
                'name' => 'RNA extraction',
                'type' => 'Nucleic Acids Extraction and Purification',
            ],
            [
                'name' => 'Viral RNA extraction',
                'type' => 'Nucleic Acids Extraction and Purification',
            ],
            [
                'name' => 'Bacterial DNA extraction',
                'type' => 'Nucleic Acids Extraction and Purification',
            ],
            [
                'name' => 'Fungal DNA extraction',
                'type' => 'Nucleic Acids Extraction and Purification',
            ],
            [
                'name' => 'Parasite DNA extraction',
                'type' => 'Nucleic Acids Extraction and Purification',
            ],

            // Culture and Isolation
            [
                'name' => 'Bacterial culture',
                'type' => 'Culture and isolation',
            ],
            [
                'name' => 'Viral culture',
                'type' => 'Culture and isolation',
            ],
            [
                'name' => 'Fungal culture',
                'type' => 'Culture and isolation',
            ],
            [
                'name' => 'Cell culture',
                'type' => 'Culture and isolation',
            ],

            // Microscopy
            [
                'name' => 'Light microscopy',
                'type' => 'Microscopy',
            ],
            [
                'name' => 'Electron microscopy',
                'type' => 'Microscopy',
            ],
            [
                'name' => 'Fluorescence microscopy',
                'type' => 'Microscopy',
            ],
            [
                'name' => 'Confocal microscopy',
                'type' => 'Microscopy',
            ],
        ];

        foreach ($techniques as $technique) {
            Techniques::firstOrCreate(
                ['name' => $technique['name']],
                $technique
            );
        }
    }
}
