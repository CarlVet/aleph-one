<?php

namespace App\Livewire\Forms;

use App\Models\AnimalSamples;
use App\Models\AnimalSpecies;
use App\Models\NucleicAcids;
use App\Models\Places;
use App\Models\Protocols;
use App\Models\SampleTypes;
use App\Models\Tubes;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class NucleicAnimalForm extends Form
{
    use WithFileUploads;

    public $animal_nucleic_tubes;

    public $photo;

    public function mount()
    {
        $this->animal_nucleic_tubes = Tubes::whereHasMorph(
            'tubes_content',  // Polymorphic relation
            [NucleicAcids::class],  // Only check tubes containing nucleic acids
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',  // Polymorphic relation on NucleicAcids
                    [AnimalSamples::class]  // Ensure nucleic acid comes from animal samples
                );
            }
        )->with([
            'tubes_content',
            'tubes_content.nucleic_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects',
        ])->get();
    }

    public function updateField($sampleId, $field, $value)
    {
        $sample = Tubes::whereHasMorph(
            'tubes_content',  // Polymorphic relation
            [NucleicAcids::class],  // Only check tubes containing nucleic acids
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',  // Polymorphic relation on NucleicAcids
                    [AnimalSamples::class]  // Ensure nucleic acid comes from animal samples
                );
            }
        )->find($sampleId);

        if ($sample) {
            switch ($field) {
                case 'state':
                    $sample->update(['state' => $value]);
                    break;
                case 'nucleic_id':
                    $sample->update(['tubes_content_id' => $value]);
                    break;
                case 'nucleic_type':
                    $sample->tubes_content()->update(['type' => $value]);
                    break;
                case 'protocol':
                    $protocol = Protocols::where('name', $value)->first();
                    $sample->tubes_content()->update(['protocols_id' => $protocol->id]);
                    break;
                case 'date_extracted':
                    $sample->tubes_content()->update(['date_extracted' => $value]);
                    break;
                case 'volume':
                    $sample->tubes_content()->update(['volume' => $value]);
                    break;
                case 'animal_id':
                    $sample->tubes_content->nucleic_content()->update(['animals_id' => $value]);
                    break;
                case 'species':
                    $animal_species = AnimalSpecies::where('name_common', $value)->first();
                    $sample->tubes_content->nucleic_content->animals()->update(['animal_species_id' => $animal_species->id]);
                    break;
                case 'sampling_site':
                    $place = Places::where('name', $value)->first();
                    $sample->tubes_content->nucleic_content()->update(['places_id' => $place->id]);
                    break;
                case 'sample_type':
                    $sample_type = SampleTypes::where('name', $value)->first();
                    $sample->tubes_content->nucleic_content()->update(['sample_types_id' => $sample_type->id]);
                    break;
            }

            session()->flash('success', 'Animal Nucleic Acid edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->animal_nucleic_tubes = Tubes::whereHasMorph(
            'tubes_content',  // Polymorphic relation
            [NucleicAcids::class],  // Only check tubes containing nucleic acids
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',  // Polymorphic relation on NucleicAcids
                    [AnimalSamples::class]  // Ensure nucleic acid comes from animal samples
                );
            }
        )->with([
            'tubes_content',
            'tubes_content.nucleic_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects',
        ])->get();
    }
}
