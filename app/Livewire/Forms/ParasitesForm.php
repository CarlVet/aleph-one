<?php

namespace App\Livewire\Forms;

use App\Models\Parasites;
use App\Models\ParasiteSpecies;
use Livewire\Form;

class ParasitesForm extends Form
{
    public $parasites;

    public $photo_path = '';

    public function mount()
    {
        $this->parasites = Parasites::with([
            'parasite_species',
            'animal_samples',
            'animal_samples.animals',
            'animal_samples.animals.animal_species',
            'animal_samples.places',
            'people',
            'projects',
        ])->get();
    }

    public function updateField($parasiteId, $field, $value)
    {
        $parasite = Parasites::find($parasiteId);

        if ($parasite) {
            switch ($field) {
                case 'parasite_id':
                    $parasite->update(['parasites_id' => $value]);
                    break;
                case 'code':
                    $parasite->update(['code' => $value]);
                    break;
                case 'species':
                    $species = ParasiteSpecies::where('name_scientific', $value)->first();
                    $parasite->update(['parasite_species_id' => $species->id]);
                    break;
                case 'stage':
                    $parasite->update(['stage' => $value]);
                    break;
                case 'sex':
                    $parasite->update(['sex' => $value]);
                    break;
                case 'state':
                    $parasite->update(['state' => $value]);
                    break;
                case 'date_identified':
                    $parasite->update(['date_identified' => $value]);
                    break;
                case 'sample_id':
                    $parasite->update(['animal_samples_id' => $value]);
                    break;
            }

            session()->flash('success', 'Parasite edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->parasites = Parasites::with([
            'parasite_species',
            'animal_samples',
            'animal_samples.animals',
            'animal_samples.animals.animal_species',
            'animal_samples.places',
            'people',
            'projects',
        ])->get();
    }
}
