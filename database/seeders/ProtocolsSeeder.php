<?php

namespace Database\Seeders;

use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Techniques;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProtocolsSeeder extends Seeder
{
    public function run(): void
    {
        // Get all projects or create some if none exist
        $projects = Projects::all();
        if ($projects->isEmpty()) {
            // Create some sample projects if none exist
            $projects = collect([
                Projects::factory()->create(['code' => 'PA1', 'name' => 'Project Alpha']),
                Projects::factory()->create(['code' => 'PA2', 'name' => 'Project Beta']),
                Projects::factory()->create(['code' => 'PA3', 'name' => 'Project Gamma']),
                Projects::factory()->create(['code' => 'PA4', 'name' => 'Project Delta']),
                Projects::factory()->create(['code' => 'PA5', 'name' => 'Project Epsilon']),
            ]);
        }

        $protocols = [
            // Nucleic Acids Extraction Protocols
            [
                'name' => 'PureLink™ Genomic DNA Mini Kit',
                'technique' => 'gDNA extraction',
                'description' => 'Thermo Fisher Scientific kit for genomic DNA extraction from various sample types',
            ],
            [
                'name' => 'DNeasy Blood & Tissue Kit',
                'technique' => 'gDNA extraction',
                'description' => 'Qiagen kit for DNA extraction from blood and tissue samples',
            ],
            [
                'name' => 'GeneJET Genomic DNA Purification Kit',
                'technique' => 'gDNA extraction',
                'description' => 'Thermo Fisher Scientific kit for genomic DNA purification',
            ],
            [
                'name' => 'PureLink RNA Mini Kit',
                'technique' => 'RNA extraction',
                'description' => 'Thermo Fisher Scientific kit for total RNA extraction',
            ],
            [
                'name' => 'RNeasy Mini Kit',
                'technique' => 'RNA extraction',
                'description' => 'Qiagen kit for total RNA extraction from various samples',
            ],
            [
                'name' => 'GeneJET Plasmid Miniprep Kit',
                'technique' => 'Plasmid DNA extraction',
                'description' => 'Thermo Fisher Scientific kit for plasmid DNA extraction',
            ],
            [
                'name' => 'QIAprep Spin Miniprep Kit',
                'technique' => 'Plasmid DNA extraction',
                'description' => 'Qiagen kit for plasmid DNA purification',
            ],
            [
                'name' => 'QIAamp Viral RNA Mini Kit',
                'technique' => 'Viral RNA extraction',
                'description' => 'Qiagen kit for viral RNA extraction from plasma, serum, and cell-free body fluids',
            ],
            [
                'name' => 'QIAamp DNA Mini Kit',
                'technique' => 'Bacterial DNA extraction',
                'description' => 'Qiagen kit for DNA extraction from bacteria and other microorganisms',
            ],
            [
                'name' => 'FastDNA SPIN Kit',
                'technique' => 'Fungal DNA extraction',
                'description' => 'MP Biomedicals kit for fungal DNA extraction',
            ],
            [
                'name' => 'QIAamp DNA Stool Mini Kit',
                'technique' => 'Parasite DNA extraction',
                'description' => 'Qiagen kit for parasite DNA extraction from stool samples',
            ],

            // PCR Detection Protocols
            [
                'name' => 'ITS-PCR for fungal identification',
                'technique' => 'Conventional PCR',
                'description' => 'Internal Transcribed Spacer PCR for fungal species identification',
            ],
            [
                'name' => '16S rRNA PCR for bacterial identification',
                'technique' => 'Conventional PCR',
                'description' => '16S ribosomal RNA PCR for bacterial species identification',
            ],
            [
                'name' => 'T. parva p104 cPCR',
                'technique' => 'Conventional PCR',
                'description' => 'Conventional PCR for Theileria parva p104 antigen detection',
            ],
            [
                'name' => 'Brucella IS711 PCR',
                'technique' => 'Conventional PCR',
                'description' => 'Conventional PCR targeting Brucella IS711 insertion sequence',
            ],
            [
                'name' => 'Mycobacterium tuberculosis IS6110 PCR',
                'technique' => 'Conventional PCR',
                'description' => 'Conventional PCR for M. tuberculosis IS6110 insertion sequence',
            ],
            [
                'name' => 'Foot-and-mouth disease virus VP1 PCR',
                'technique' => 'Conventional PCR',
                'description' => 'Conventional PCR for FMDV VP1 gene detection',
            ],
            [
                'name' => 'Avian influenza virus M gene PCR',
                'technique' => 'Conventional PCR',
                'description' => 'Conventional PCR for AIV matrix gene detection',
            ],
            [
                'name' => 'Nested PCR for Trypanosoma spp.',
                'technique' => 'Nested PCR',
                'description' => 'Nested PCR protocol for Trypanosoma species detection',
            ],
            [
                'name' => 'Multiplex PCR for respiratory pathogens',
                'technique' => 'Multiplex PCR',
                'description' => 'Multiplex PCR for simultaneous detection of multiple respiratory pathogens',
            ],
            [
                'name' => 'Real-time PCR for SARS-CoV-2',
                'technique' => 'Real-time PCR',
                'description' => 'Real-time PCR for SARS-CoV-2 RNA detection',
            ],
            [
                'name' => 'LAMP for Mycobacterium tuberculosis',
                'technique' => 'Loop-mediated isothermal amplification (LAMP)',
                'description' => 'LAMP assay for rapid M. tuberculosis detection',
            ],

            // ELISA Protocols
            [
                'name' => 'ID-VET ID Screen® Brucella Indirect ELISA',
                'technique' => 'Indirect ELISA',
                'description' => 'ID-VET indirect ELISA for Brucella antibody detection',
            ],
            [
                'name' => 'ID-VET FMD Multi-species iELISA',
                'technique' => 'Indirect ELISA',
                'description' => 'ID-VET indirect ELISA for foot-and-mouth disease antibody detection',
            ],
            [
                'name' => 'ID-VET Bluetongue Competition ELISA',
                'technique' => 'Competitive ELISA',
                'description' => 'ID-VET competitive ELISA for bluetongue virus antibody detection',
            ],
            [
                'name' => 'ID-VET African Swine Fever Antigen ELISA',
                'technique' => 'Direct ELISA',
                'description' => 'ID-VET direct ELISA for African swine fever virus antigen detection',
            ],
            [
                'name' => 'IDEXX Brucella Ab Test',
                'technique' => 'Indirect ELISA',
                'description' => 'IDEXX indirect ELISA for Brucella antibody detection',
            ],
            [
                'name' => 'IDEXX FMDV Ab Test',
                'technique' => 'Indirect ELISA',
                'description' => 'IDEXX indirect ELISA for foot-and-mouth disease virus antibody detection',
            ],
            [
                'name' => 'IDEXX PRRS X3 Ab Test',
                'technique' => 'Indirect ELISA',
                'description' => 'IDEXX indirect ELISA for porcine reproductive and respiratory syndrome virus',
            ],

            // Other Serological Tests
            [
                'name' => 'Rose Bengal Test (RBT) for Brucella',
                'technique' => 'Rose Bengal test (RBT)',
                'description' => 'Rose Bengal test for Brucella antibody screening',
            ],
            [
                'name' => 'Complement Fixation Test for Brucella',
                'technique' => 'Complement fixation test (CFT)',
                'description' => 'Complement fixation test for Brucella antibody detection',
            ],
            [
                'name' => 'Agar Gel Immunodiffusion for FMD',
                'technique' => 'Agar gel immunodiffusion (AGID)',
                'description' => 'Agar gel immunodiffusion test for foot-and-mouth disease',
            ],
            [
                'name' => 'Western Blot for HIV',
                'technique' => 'Western blot',
                'description' => 'Western blot for HIV antibody confirmation',
            ],
            [
                'name' => 'Immunofluorescence Assay for Toxoplasma',
                'technique' => 'Immunofluorescence assay (IFA)',
                'description' => 'IFA for Toxoplasma gondii antibody detection',
            ],

            // Rapid Tests
            [
                'name' => 'SNAP FIV/FeLV Combo Test',
                'technique' => 'Lateral flow test',
                'description' => 'IDEXX lateral flow test for feline immunodeficiency virus and feline leukemia virus',
            ],
            [
                'name' => 'SNAP Parvo Test',
                'technique' => 'Lateral flow test',
                'description' => 'IDEXX lateral flow test for canine parvovirus',
            ],
            [
                'name' => 'SNAP Giardia Test',
                'technique' => 'Lateral flow test',
                'description' => 'IDEXX lateral flow test for Giardia antigen detection',
            ],
            [
                'name' => 'Rapid Test for Brucella',
                'technique' => 'Immunochromatographic test',
                'description' => 'Rapid immunochromatographic test for Brucella antibody detection',
            ],

            // Culture Protocols
            [
                'name' => 'Blood agar culture for bacteria',
                'technique' => 'Bacterial culture',
                'description' => 'Standard blood agar culture protocol for bacterial isolation',
            ],
            [
                'name' => 'Sabouraud agar culture for fungi',
                'technique' => 'Fungal culture',
                'description' => 'Sabouraud agar culture protocol for fungal isolation',
            ],
            [
                'name' => 'Cell culture for virus isolation',
                'technique' => 'Viral culture',
                'description' => 'Cell culture protocol for virus isolation and propagation',
            ],

            // Microscopy Protocols
            [
                'name' => 'Giemsa staining for blood parasites',
                'technique' => 'Light microscopy',
                'description' => 'Giemsa staining protocol for blood parasite detection',
            ],
            [
                'name' => 'Ziehl-Neelsen staining for acid-fast bacteria',
                'technique' => 'Light microscopy',
                'description' => 'Ziehl-Neelsen staining for acid-fast bacteria detection',
            ],
            [
                'name' => 'Gram staining for bacteria',
                'technique' => 'Light microscopy',
                'description' => 'Gram staining protocol for bacterial classification',
            ],
        ];

        $serialNumber = 1;
        $defaultUserId = User::query()->inRandomOrder()->value('id');

        foreach ($protocols as $index => $protocolData) {
            // Find the corresponding technique
            $technique = Techniques::where('name', $protocolData['technique'])->first();

            if (! $technique) {
                // Create the technique if it doesn't exist
                $technique = Techniques::create([
                    'name' => $protocolData['technique'],
                    'type' => $this->getTechniqueType($protocolData['technique']),
                ]);
            }

            // Distribute protocols across different projects
            $project = $projects[$index % $projects->count()];

            // Generate unique code for this project
            $code = "{$project->code}-PR-".str_pad($serialNumber, 4, '0', STR_PAD_LEFT);

            // Create the protocol
            $protocol = Protocols::firstOrCreate(
                ['name' => $protocolData['name']],
                [
                    'code' => $code,
                    'name' => $protocolData['name'],
                    'techniques_id' => $technique->id,
                    'pdf_path' => null, // Can be updated later if PDFs are available
                    'users_id' => $defaultUserId,
                ]
            );

            if ($protocol->users_id === null && $defaultUserId !== null) {
                $protocol->update(['users_id' => $defaultUserId]);
            }

            $serialNumber++;
        }
    }

    private function getTechniqueType($techniqueName): string
    {
        $typeMap = [
            'Conventional PCR' => 'Nucleic acids detection test',
            'Real-time PCR' => 'Nucleic acids detection test',
            'Nested PCR' => 'Nucleic acids detection test',
            'Multiplex PCR' => 'Nucleic acids detection test',
            'Loop-mediated isothermal amplification (LAMP)' => 'Nucleic acids detection test',
            'Indirect ELISA' => 'Antibody detection test',
            'Competitive ELISA' => 'Antibody detection test',
            'Sandwich ELISA' => 'Antibody detection test',
            'Western blot' => 'Antibody detection test',
            'Immunofluorescence assay (IFA)' => 'Antibody detection test',
            'Complement fixation test (CFT)' => 'Antibody detection test',
            'Agar gel immunodiffusion (AGID)' => 'Antibody detection test',
            'Rose Bengal test (RBT)' => 'Antibody detection test',
            'Lateral flow test' => 'Antigen detection test',
            'Direct ELISA' => 'Antigen detection test',
            'Immunohistochemistry (IHC)' => 'Antigen detection test',
            'Immunochromatographic test' => 'Antigen detection test',
            'gDNA extraction' => 'Nucleic Acids Extraction and Purification',
            'Plasmid DNA extraction' => 'Nucleic Acids Extraction and Purification',
            'RNA extraction' => 'Nucleic Acids Extraction and Purification',
            'Viral RNA extraction' => 'Nucleic Acids Extraction and Purification',
            'Bacterial DNA extraction' => 'Nucleic Acids Extraction and Purification',
            'Fungal DNA extraction' => 'Nucleic Acids Extraction and Purification',
            'Parasite DNA extraction' => 'Nucleic Acids Extraction and Purification',
            'Bacterial culture' => 'Culture and isolation',
            'Viral culture' => 'Culture and isolation',
            'Fungal culture' => 'Culture and isolation',
            'Cell culture' => 'Culture and isolation',
            'Light microscopy' => 'Microscopy',
            'Electron microscopy' => 'Microscopy',
            'Fluorescence microscopy' => 'Microscopy',
            'Confocal microscopy' => 'Microscopy',
        ];

        return $typeMap[$techniqueName] ?? 'Other';
    }
}
