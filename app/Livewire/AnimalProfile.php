<?php

namespace App\Livewire;

use App\Models\Animals;
use App\Services\SampleExperimentsAggregator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class AnimalProfile extends PlainComponent
{
    use WithFileUploads;

    public $animal;

    public $code;

    public $photo;

    public $uploadingPhoto = false;

    public $uploadError = null;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    // Inline editing properties
    public $editingValues = [
        'field_label' => '',
        'sex' => '',
        'age' => '',
    ];

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->loadAnimal();
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this animal profile.';

            return;
        }

        // Load the animal to check if it belongs to the selected project
        $animal = Animals::where('code', $this->code)->first();

        if (! $animal) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Animal not found.';

            return;
        }

        // Check if the animal belongs to the selected project
        if ($animal->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this animal because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($animal->people_id ?? 0), 'animal_samples');

        $this->canView = true;
    }

    private function loadAnimal()
    {
        $this->animal = Animals::with([
            'animal_species',
            'animal_health',
            'animal_health.clinical_signs',
            'animal_health.lesions',
            'animal_vaccinations',
            'animal_vaccinations.people',
            'animal_medications',
            'animal_medications.people',
            'animal_movements',
            'animal_movements.source_sampling_site',
            'animal_movements.destination_sampling_site',
            'owner',
            'animal_samples',
            'animal_samples.sample_types',
        ])->where('code', $this->code)->firstOrFail();
    }

    public function uploadPhoto()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this animal profile.');

            return;
        }

        if (! $this->photo) {
            $this->uploadError = 'Please select a photo first.';

            return;
        }

        // Check file size (50MB = 52428800 bytes)
        if ($this->photo->getSize() > 52428800) {
            $this->uploadError = 'File size exceeds 50MB limit.';
            $this->photo = null;

            return;
        }

        $this->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200', // 50MB max
        ], [
            'photo.mimes' => 'Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.',
        ]);

        $this->uploadingPhoto = true;
        $this->uploadError = null;

        try {
            // Delete old photo if exists
            if ($this->animal->pic_path) {
                Storage::disk('local')->delete($this->animal->pic_path);
            }

            // Store new photo
            $photoPath = $this->photo->store('animal-photos', 'local');

            // Update animal record
            $this->animal->update(['pic_path' => $photoPath]);

            // Force a fresh load of the animal with all relationships
            $this->animal = $this->animal->fresh([
                'animal_species',
                'animal_health',
                'animal_health.clinical_signs',
                'animal_health.lesions',
                'animal_vaccinations',
                'animal_vaccinations.people',
                'animal_medications',
                'animal_medications.people',
                'animal_movements',
                'animal_movements.source_sampling_site',
                'animal_movements.destination_sampling_site',
                'owner',
                'animal_samples',
                'animal_samples.sample_types',
            ]);

            $this->photo = null;
            $this->uploadingPhoto = false;

            session()->flash('message', 'Photo uploaded successfully!');
            $this->dispatch('show-success', message: 'Photo uploaded successfully!');

            // Dispatch event to clear file input
            $this->dispatch('photo-uploaded');

        } catch (\Exception $e) {
            $this->uploadingPhoto = false;
            $this->uploadError = 'Failed to upload photo: '.$e->getMessage();
            $this->dispatch('show-error', message: $this->uploadError);
        }
    }

    public function deletePhoto()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this animal profile.');

            return;
        }

        try {
            if ($this->animal->pic_path) {
                Storage::disk('local')->delete($this->animal->pic_path);
                $this->animal->update(['pic_path' => null]);
                // Force a fresh load of the animal with all relationships
                $this->animal = $this->animal->fresh([
                    'animal_species',
                    'animal_health',
                    'animal_health.clinical_signs',
                    'animal_health.lesions',
                    'animal_vaccinations',
                    'animal_vaccinations.people',
                    'animal_medications',
                    'animal_medications.people',
                    'animal_movements',
                    'animal_movements.source_sampling_site',
                    'animal_movements.destination_sampling_site',
                    'owner',
                    'animal_samples',
                    'animal_samples.sample_types',
                ]);

                session()->flash('message', 'Photo deleted successfully!');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete photo: '.$e->getMessage());
        }
    }

    public function cancelPhotoSelection()
    {
        $this->photo = null;
        $this->uploadError = null;
        $this->dispatch('photo-cancelled');
    }

    // Inline editing methods
    public function startEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this animal profile.');

            return;
        }

        $this->editingValues[$field] = $this->animal->$field;
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this animal profile.');

            return;
        }

        try {
            $this->validate([
                'editingValues.field_label' => 'nullable|string|max:255',
                'editingValues.sex' => 'nullable|in:Male,Female,NA',
                'editingValues.age' => 'nullable|in:Juvenile,Sub-adult,Adult,NA',
            ]);

            $this->animal->update([$field => $this->editingValues[$field]]);

            // Re-query the animal with all relationships
            $this->loadAnimal();

            $this->editingValues[$field] = '';
            $this->dispatch('save-edit', field: $field);
            $this->dispatch('show-success', message: ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
            session()->flash('message', ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->toArray();
            $errorMessage = implode("\n", $messages);
            $this->dispatch('show-error', message: $errorMessage);
            session()->flash('error', $errorMessage);
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
            session()->flash('error', 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
        }
    }

    public function cancelEdit($field)
    {
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.animal-profile', [
                'animal' => null,
                'sampleExperiments' => collect(),
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.animal-profile', [
            'animal' => $this->animal,
            'sampleExperiments' => app(SampleExperimentsAggregator::class)
                ->forNodes($this->animal->animal_samples, session('selected_project_id')),
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
