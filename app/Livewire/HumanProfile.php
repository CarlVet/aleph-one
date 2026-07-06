<?php

namespace App\Livewire;

use App\Models\Humans;
use App\Services\SampleExperimentsAggregator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class HumanProfile extends PlainComponent
{
    use WithFileUploads;

    public $human;

    public $code;

    public $photo;

    public $uploadingPhoto = false;

    public $uploadError = null;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    // Inline editing properties
    public $editingValues = [
        'first_name' => '',
        'last_name' => '',
        'sex' => '',
        'date_of_birth' => '',
        'age' => '',
        'marital_status' => '',
        'email' => '',
        'alternate_email' => '',
        'phone' => '',
        'alternate_phone' => '',
        'preferred_contact_method' => '',
    ];

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->loadHuman();
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this human profile.';

            return;
        }

        // Load the human to check if it belongs to the selected project
        $human = Humans::where('code', $this->code)->first();

        if (! $human) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Human not found.';

            return;
        }

        // Check if the human belongs to the selected project
        if ($human->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this human because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($human->people_id ?? 0), 'human_samples');

        $this->canView = true;
    }

    private function loadHuman()
    {
        $this->human = Humans::with([
            'human_samples',
            'human_samples.sample_types',
            'human_samples.people',
            'human_samples.sampling_sites',
            'human_samples.locations',
            'human_samples.projects',
        ])->where('code', $this->code)->firstOrFail();
    }

    public function uploadPhoto()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to upload photos to this human profile.');

            return;
        }

        if (! $this->photo) {
            $this->uploadError = 'Please select a file first.';

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
            if ($this->human->photo_path) {
                Storage::disk('local')->delete($this->human->photo_path);
            }

            // Store new photo
            $photoPath = $this->photo->store('human-photos', 'local');

            // Update human record
            $this->human->update(['photo_path' => $photoPath]);

            // Force a fresh load of the human with all relationships
            $this->human = Humans::with([
                'human_samples',
                'human_samples.sample_types',
                'human_samples.people',
                'human_samples.sampling_sites',
                'human_samples.locations',
                'human_samples.projects',
            ])->where('code', $this->code)->firstOrFail();

            $this->photo = null;
            $this->uploadingPhoto = false;

            session()->flash('message', 'Photo uploaded successfully!');

            // Dispatch event to clear file input
            $this->dispatch('photo-uploaded');

        } catch (\Exception $e) {
            $this->uploadingPhoto = false;
            $this->uploadError = 'Failed to upload photo: '.$e->getMessage();
        }
    }

    public function deletePhoto()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete photos from this human profile.');

            return;
        }

        try {
            if ($this->human->photo_path) {
                Storage::disk('local')->delete($this->human->photo_path);
                $this->human->update(['photo_path' => null]);
                // Force a fresh load of the human with all relationships
                $this->human = Humans::with([
                    'human_samples',
                    'human_samples.sample_types',
                    'human_samples.people',
                    'human_samples.sampling_sites',
                    'human_samples.locations',
                    'human_samples.projects',
                ])->where('code', $this->code)->firstOrFail();

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
            session()->flash('error', 'You do not have permission to edit this human profile.');

            return;
        }

        $this->editingValues[$field] = $this->human->$field;
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this human profile.');

            return;
        }

        try {
            $this->validate([
                'editingValues.first_name' => 'nullable|string|max:255',
                'editingValues.last_name' => 'nullable|string|max:255',
                'editingValues.sex' => 'nullable|in:male,female,other',
                'editingValues.date_of_birth' => 'nullable|date',
                'editingValues.age' => 'nullable|integer|min:0|max:150',
                'editingValues.email' => 'nullable|email|max:255',
                'editingValues.alternate_email' => 'nullable|email|max:255',
                'editingValues.phone' => 'nullable|string|max:20',
                'editingValues.alternate_phone' => 'nullable|string|max:20',
                'editingValues.marital_status' => 'nullable|in:single,married,divorced,widowed,separated',
                'editingValues.preferred_contact_method' => 'nullable|in:email,phone,sms,mail',
            ]);

            $this->human->update([$field => $this->editingValues[$field]]);

            // Re-query the human with all relationships
            $this->human = Humans::with([
                'human_samples',
                'human_samples.sample_types',
                'human_samples.people',
                'human_samples.sampling_sites',
                'human_samples.locations',
                'human_samples.projects',
            ])->where('code', $this->code)->firstOrFail();

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
            return view('livewire.human-profile', [
                'human' => null,
                'sampleExperiments' => collect(),
                'photo' => null,
                'uploadingPhoto' => false,
                'uploadError' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.human-profile', [
            'human' => $this->human,
            'sampleExperiments' => app(SampleExperimentsAggregator::class)
                ->forNodes($this->human->human_samples, $this->selectedProjectId()),
            'photo' => $this->photo,
            'uploadingPhoto' => $this->uploadingPhoto,
            'uploadError' => $this->uploadError,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
