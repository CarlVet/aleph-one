<?php

namespace App\Livewire\Forms;

use App\Models\AnimalMedication;
use App\Models\Animals;
use Livewire\Form;

class AnimalMedicationForm extends Form
{
    public $animal_medications;

    public function mount()
    {
        $this->animal_medications = AnimalMedication::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ])->get();
    }

    public function updateField($medicationId, $field, $value)
    {
        $medication = AnimalMedication::find($medicationId);

        if ($medication) {
            switch ($field) {
                case 'animal_id':
                    $animal = Animals::find($value);
                    if ($animal) {
                        $medication->update(['animals_id' => $animal->id]);
                    }
                    break;
                case 'medication_name':
                    $medication->update(['medication_name' => $value]);
                    break;
                case 'dosage':
                    $medication->update(['dosage' => $value]);
                    break;
                case 'start_date':
                    $medication->update(['start_date' => $value]);
                    break;
                case 'end_date':
                    $medication->update(['end_date' => $value]);
                    break;
                case 'prescribed_by':
                    $medication->update(['prescribed_by' => $value]);
                    break;
                case 'notes':
                    $medication->update(['notes' => $value]);
                    break;
            }

            session()->flash('success', 'Animal medication record edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->animal_medications = AnimalMedication::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ])->get();
    }
}
