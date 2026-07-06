<?php

namespace App\Livewire\Forms;

use App\Models\Animals;
use App\Models\AnimalVaccination;
use Livewire\Form;

class AnimalVaccinationForm extends Form
{
    public $animal_vaccinations;

    public function mount()
    {
        $this->animal_vaccinations = AnimalVaccination::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ])->get();
    }

    public function updateField($vaccinationId, $field, $value)
    {
        $vaccination = AnimalVaccination::find($vaccinationId);

        if ($vaccination) {
            switch ($field) {
                case 'animal_id':
                    $animal = Animals::find($value);
                    if ($animal) {
                        $vaccination->update(['animals_id' => $animal->id]);
                    }
                    break;
                case 'vaccine_name':
                    $vaccination->update(['vaccine_name' => $value]);
                    break;
                case 'vaccine_type':
                    $vaccination->update(['vaccine_type' => $value]);
                    break;
                case 'date_administered':
                    $vaccination->update(['date_administered' => $value]);
                    break;
                case 'next_due_date':
                    $vaccination->update(['next_due_date' => $value]);
                    break;
                case 'administered_by':
                    $vaccination->update(['administered_by' => $value]);
                    break;
                case 'notes':
                    $vaccination->update(['notes' => $value]);
                    break;
            }

            session()->flash('success', 'Animal vaccination record edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->animal_vaccinations = AnimalVaccination::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ])->get();
    }
}
