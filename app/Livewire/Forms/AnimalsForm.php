<?php

namespace App\Livewire\Forms;

use App\Models\Animals;
use App\Models\AnimalSpecies;
use Livewire\Form;

class AnimalsForm extends Form
{
    public function updateField(int $animalId, string $field, mixed $value): void
    {
        $animal = Animals::query()->find($animalId);

        if (! $animal) {
            return;
        }

        switch ($field) {
            case 'field_label':
                $animal->update(['field_label' => $value]);
                break;

            case 'species':
                $species = AnimalSpecies::query()->where('name_common', $value)->first();
                if ($species) {
                    $animal->update(['animal_species_id' => $species->id]);
                }
                break;

            case 'sex':
                $animal->update(['sex' => $value]);
                break;

            case 'age':
                $animal->update(['age' => $value]);
                break;
        }
    }

    public function refreshData(): void
    {
        // The Animals index queries directly, so nothing needed here.
    }
}
