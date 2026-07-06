<?php

namespace App\Livewire\Forms;

use App\Models\Animals;
use App\Models\AnimalSamples;
use App\Models\AnimalSpecies;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class AnimalSamplesForm extends Form
{
    use WithFileUploads;

    public $animal_samples;

    public $photo_path = '';

    public $photo;

    public function mount()
    {
        $this->animal_samples = AnimalSamples::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'sample_types',
            'sampling_sites',
            'people',
        ])->get();
    }

    public function updateField($sampleId, $field, $value)
    {
        $sample = AnimalSamples::find($sampleId);

        if ($sample) {
            switch ($field) {
                case 'animal_id':
                    $animal = Animals::where('code', $value)->first();
                    $sample->update(['animals_id' => $animal->id]);
                    break;
                case 'field_label':
                    $sample->animals()->update(['field_label' => $value]);
                    break;
                case 'species':
                    $species = AnimalSpecies::where('name_common', $value)->first();
                    $sample->animals()->update(['animal_species_id' => $species->id]);
                    break;
                case 'sex':
                    $sample->animals()->update(['sex' => $value]);
                    break;
                case 'age':
                    $sample->animals()->update(['age' => $value]);
                    break;
                case 'sample_type':
                    $type = SampleTypes::where('name', $value)->first();
                    $sample->update(['sample_types_id' => $type->id]);
                    break;
                case 'date_collected':
                    $sample->update(['date_collected' => $value]);
                    break;
                case 'sampling_site':
                    $site = SamplingSites::where('name', $value)->first();
                    $sample->update(['sampling_sites_id' => $site ? $site->id : null]);
                    break;
                case 'latitude':
                    $sample->update(['latitude' => $value]);
                    break;
                case 'longitude':
                    $sample->update(['longitude' => $value]);
                    break;
                case 'photo':
                    // Handle photo upload
                    $this->validate([
                        'photo' => 'image|max:2048', // Ensure it's an image and max 2MB
                    ]);

                    if ($this->photo) {
                        $this->photo_path = $this->photo->storePublicly('animal_samples', ['disk' => 'local']);

                        if ($this->photo_path) {
                            // Update the photo_path field in the database
                            $sample->update(['photo_path' => $this->photo_path]);

                            // Clear the uploaded photo after saving
                            $this->photo = null;

                            session()->flash('success', 'Photo uploaded successfully!');
                        } else {
                            session()->flash('error', 'Failed to upload photo.');
                        }
                    }
                    break;
            }

            session()->flash('success', 'Animal sample edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->animal_samples = AnimalSamples::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'sample_types',
            'sampling_sites',
            'people',
        ])->get();
    }
}
