<?php

namespace App\Livewire\Forms;

use App\Models\EnvironmentSamples;
use App\Models\EnvironmentSampleTypes;
use App\Models\SamplingSites;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class EnvironmentSamplesForm extends Form
{
    use WithFileUploads;

    public $environment_samples;

    public $photo_path = '';

    public $photo;

    public function mount()
    {
        $this->environment_samples = EnvironmentSamples::with([
            'environment_sample_types',
            'sampling_sites',
            'people',
        ])->get();
    }

    public function updateField($sampleId, $field, $value)
    {
        $sample = EnvironmentSamples::find($sampleId);

        if ($sample) {
            switch ($field) {
                case 'sample_type':
                    $type = EnvironmentSampleTypes::where('name', $value)->first();
                    $sample->update(['environment_sample_types_id' => $type->id]);
                    break;
                case 'date_collected':
                    $sample->update(['date_collected' => $value]);
                    break;
                case 'sampling_site':
                    $site = SamplingSites::where('name', $value)->first();
                    $sample->update(['sampling_sites_id' => $site->id]);
                    break;
                case 'area':
                    $sample->update(['area' => $value]);
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
                        $this->photo_path = $this->photo->storePublicly('environment_samples', ['disk' => 'local']);

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

            session()->flash('success', 'Environment sample edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->environment_samples = EnvironmentSamples::with([
            'environment_sample_types',
            'sampling_sites',
            'people',
        ])->get();
    }
}
