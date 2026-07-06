<?php

namespace App\Livewire\Forms;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class ParasiteSamplesForm extends Form
{
    use WithFileUploads;

    public $parasite_samples;

    public $photo;

    public $projectId = 1;

    public function mount()
    {
        $this->parasite_samples = ParasiteSamples::whereHas(
            'parasites',
            function ($query) {
                $query->whereHasMorph(
                    'parasites_origin',
                    [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class]
                );
            }
        )->with([
            'parasites',
            'parasites.parasite_species',
            'parasites.parasites_origin',
            'parasite_sample_types',
            'parasites.people',
            'projects',
        ])->where('projects_id', $this->projectId)
            ->get();
    }

    public function updateField($sampleId, $field, $value)
    {
        $sample = ParasiteSamples::find($sampleId);

        if ($sample) {
            switch ($field) {
                case 'parasite_id':
                    $sample->update(['parasites_id' => $value]);
                    break;
                case 'code':
                    $sample->parasites()->update(['code' => $value]);
                    break;
                case 'species':
                    $species = ParasiteSpecies::where('name_scientific', $value)->first();
                    $sample->parasites()->update(['parasite_species_id' => $species->id]);
                    break;
                case 'sample_type':
                    $type = ParasiteSampleTypes::where('name', $value)->first();
                    $sample->update(['parasite_sample_types_id' => $type->id]);
                    break;
                case 'stage':
                    $sample->parasites()->update(['stage' => $value]);
                    break;
                case 'sex':
                    $sample->parasites()->update(['sex' => $value]);
                    break;
                case 'state':
                    $sample->parasites()->update(['state' => $value]);
                    break;
                case 'date_identified':
                    $sample->parasites()->update(['date_identified' => $value]);
                    break;
                case 'date_processed':
                    $sample->update(['date_processed' => $value]);
                    break;
                case 'animal_id':
                    $sample->update(['animals_id' => $value]);
                    break;
            }

            session()->flash('success', 'Parasite sample edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->parasite_samples = ParasiteSamples::whereHas(
            'parasites',
            function ($query) {
                $query->whereHasMorph(
                    'parasites_origin',
                    [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class]
                );
            }
        )->with([
            'parasites',
            'parasites.parasite_species',
            'parasites.parasites_origin',
            'parasite_sample_types',
            'parasites.people',
            'projects',
        ])->where('projects_id', $this->projectId)
            ->get();
    }
}
