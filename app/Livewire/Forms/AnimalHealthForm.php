<?php

namespace App\Livewire\Forms;

use App\Models\AnimalHealth;
use App\Models\Animals;
use Livewire\Form;

class AnimalHealthForm extends Form
{
    public $animal_health;

    public function mount()
    {
        $this->animal_health = AnimalHealth::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'clinical_signs',
            'lesions',
        ])->get();
    }

    public function updateField($healthId, $field, $value)
    {
        $health = AnimalHealth::find($healthId);

        if ($health) {
            switch ($field) {
                case 'animal_id':
                    $animal = Animals::where('code', $value)->first();
                    $health->update(['animals_id' => $animal->id]);
                    break;
                case 'health_status':
                    $health->update(['health_status' => $value]);
                    break;
                case 'check_date':
                    $health->update(['check_date' => $value]);
                    break;
                case 'check_type':
                    $health->update(['check_type' => $value]);
                    break;
                case 'clinical_signs_id':
                    $health->update(['clinical_signs_id' => $value]);
                    break;
                case 'lesions_id':
                    $health->update(['lesions_id' => $value]);
                    break;
                case 'alive':
                    $health->update(['alive' => $value]);
                    break;
                case 'notes':
                    $health->update(['notes' => $value]);
                    break;
            }

            session()->flash('success', 'Animal health record edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->animal_health = AnimalHealth::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'clinical_signs',
            'lesions',
        ])->orderBy('created_at', 'desc')->get();
    }
}
