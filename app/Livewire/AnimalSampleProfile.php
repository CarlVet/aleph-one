<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Services\SampleExperimentsAggregator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AnimalSampleProfile extends PlainComponent
{
    public $sample;

    public $code;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    // Inline editing properties
    public $editingValues = [
        'area' => '',
        'latitude' => '',
        'longitude' => '',
        'storage_state' => '',
        'date_received' => '',
    ];

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
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this animal sample profile.';

            return;
        }

        // Load the sample to check if it belongs to the selected project
        $sample = AnimalSamples::where('code', $this->code)->first();

        if (! $sample) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Animal sample not found.';

            return;
        }

        // Check if the sample belongs to the selected project
        if ($sample->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this animal sample because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($sample->people_id ?? 0), 'animal_samples');

        $this->canView = true;
    }

    private function loadSample()
    {
        $projectId = session('selected_project_id');

        $this->sample = AnimalSamples::with([
            'animals',
            'animals.animal_species',
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
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'pools.people',
            'pools.laboratories',
            'tubes' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'tubes.tube_positions.boxes',
        ])->where('code', $this->code)->firstOrFail();
    }

    public function deleteAnimalSample()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this animal sample.');

            return;
        }

        try {
            // Delete the animal sample
            $this->sample->delete();

            session()->flash('message', 'Animal sample deleted successfully!');

            // Redirect to animal samples list
            return redirect('/samples/animals/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete animal sample: '.$e->getMessage());
        }
    }

    // Helper method to load sample with all relationships
    private function loadSampleWithRelationships()
    {
        $projectId = session('selected_project_id');

        return AnimalSamples::with([
            'animals',
            'animals.animal_species',
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
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'pools.people',
            'pools.laboratories',
            'tubes' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'tubes.tube_positions.boxes',
        ])->where('code', $this->code)->firstOrFail();
    }

    // Inline editing methods
    public function startEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this animal sample profile.');

            return;
        }

        // Ensure the sample is loaded with all relationships before editing
        $this->sample = $this->loadSampleWithRelationships();
        if ($field === 'date_received') {
            $this->editingValues[$field] = $this->sample->date_received ? (string) Carbon::parse($this->sample->date_received)->format('Y-m-d') : '';
        } else {
            $this->editingValues[$field] = $this->sample->$field;
        }
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this animal sample profile.');

            return;
        }

        try {
            $this->validate([
                'editingValues.area' => 'nullable|string|max:255',
                'editingValues.latitude' => 'nullable|numeric|between:-90,90',
                'editingValues.longitude' => 'nullable|numeric|between:-180,180',
                'editingValues.storage_state' => 'nullable|string|max:255',
                'editingValues.date_received' => 'nullable|date',
            ]);

            $this->sample->update([$field => $this->editingValues[$field]]);

            // Re-query the sample with all relationships
            $this->sample = $this->loadSampleWithRelationships();

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
        // Ensure the sample is loaded with all relationships when canceling
        $this->sample = $this->loadSampleWithRelationships();
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.animal-sample-profile', [
                'animalSample' => null,
                'sampleExperiments' => collect(),
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        // Get existing storage states for the datalist
        $existingStorageStates = AnimalSamples::whereNotNull('storage_state')
            ->where('storage_state', '!=', '')
            ->pluck('storage_state')
            ->unique()
            ->values()
            ->toArray();

        return view('livewire.animal-sample-profile', [
            'animalSample' => $this->sample,
            'sampleExperiments' => app(SampleExperimentsAggregator::class)
                ->forSample($this->sample, session('selected_project_id')),
            'existingStorageStates' => $existingStorageStates,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
