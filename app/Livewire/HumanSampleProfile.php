<?php

namespace App\Livewire;

use App\Models\HumanSamples;
use App\Services\SampleExperimentsAggregator;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class HumanSampleProfile extends PlainComponent
{
    use WithFileUploads;

    public $sample;

    public $code;

    public $photo;

    public $uploadingPhoto = false;

    public $uploadError = null;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->loadSample();
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = session('selected_project_id');

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this human sample profile.';

            return;
        }

        // Load the sample to check if it belongs to the selected project
        $sample = HumanSamples::where('code', $this->code)->first();

        if (! $sample) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Human sample not found.';

            return;
        }

        // Check if the sample belongs to the selected project
        if ($sample->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this human sample because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($sample->people_id ?? 0), 'human_samples');

        $this->canView = true;
    }

    private function loadSample()
    {
        $projectId = $this->selectedProjectId();

        $this->sample = HumanSamples::with([
            'humans',
            'sample_types',
            'people',
            'sampling_sites',
            'locations',
            'projects',
            'subProjectAssignment.subProject',
            'nucleic_acids' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'nucleic_acids.people',
            'nucleic_acids.laboratories',
            'nucleic_acids.sequences',
            'experiments' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'experiments.protocols.techniques',
            'experiments.pathogens',
            'experiments.people',
            'experiments.laboratories',
            'microplastics' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'microplastics.mps_types',
            'microplastics.protocols',
            'microplastics.laboratories',
            'microplastics.people',
            'cultures' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'cultures.people',
            'cultures.laboratories',
            'pools' => function ($query) use ($projectId) {
                $query->with(['pools.people', 'pools.laboratories', 'pools.projects']);

                if ($projectId) {
                    $query->whereHas('pools', fn ($q) => $q->where('projects_id', $projectId));
                }
            },
            'tubes' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'tubes.tube_positions.boxes',
        ])->where('code', $this->code)->firstOrFail();
    }

    public function uploadPhoto()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to upload photos to this human sample profile.');

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
            if ($this->sample->humans->photo_path) {
                Storage::disk('local')->delete($this->sample->humans->photo_path);
            }

            // Store new photo
            $photoPath = $this->photo->store('human-sample-photos', 'local');

            // Update sample record
            $this->sample->humans->update(['photo_path' => $photoPath]);

            $this->loadSample();

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
            session()->flash('error', 'You do not have permission to delete photos from this human sample profile.');

            return;
        }

        try {
            if ($this->sample->humans->photo_path) {
                Storage::disk('local')->delete($this->sample->humans->photo_path);
                $this->sample->humans->update(['photo_path' => null]);
                $this->loadSample();

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

    public function deleteHumanSample()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this human sample.');

            return;
        }

        try {
            // Delete the human sample
            $this->sample->delete();

            session()->flash('message', 'Human sample deleted successfully!');

            // Redirect to human samples list
            return redirect('/samples/humans/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete human sample: '.$e->getMessage());
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.human-sample-profile', [
                'humanSample' => null,
                'sampleExperiments' => collect(),
                'photo' => null,
                'uploadingPhoto' => false,
                'uploadError' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.human-sample-profile', [
            'humanSample' => $this->sample,
            'sampleExperiments' => app(SampleExperimentsAggregator::class)
                ->forSample($this->sample, $this->selectedProjectId()),
            'photo' => $this->photo,
            'uploadingPhoto' => $this->uploadingPhoto,
            'uploadError' => $this->uploadError,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
