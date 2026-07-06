<?php

namespace App\Livewire;

use App\Models\EnvironmentSamples;
use Illuminate\Validation\ValidationException;

class EnvironmentSampleProfile extends PlainComponent
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
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this environment sample profile.';

            return;
        }

        // Load the sample to check if it belongs to the selected project
        $sample = EnvironmentSamples::where('code', $this->code)->first();

        if (! $sample) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Environment sample not found.';

            return;
        }

        // Check if the sample belongs to the selected project
        if ($sample->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this environment sample because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($sample->people_id ?? 0), 'environment_samples');

        $this->canView = true;
    }

    private function loadSample()
    {
        $projectId = session('selected_project_id');

        $this->sample = EnvironmentSamples::with([
            'environment_sample_types',
            'sampling_sites',
            'people',
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

    public function deleteEnvironmentSample()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this environment sample.');

            return;
        }

        try {
            // Delete the environment sample
            $this->sample->delete();

            session()->flash('message', 'Environment sample deleted successfully!');

            // Redirect to environment samples list
            return redirect('/samples/environment/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete environment sample: '.$e->getMessage());
        }
    }

    // Inline editing methods
    public function startEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this environment sample profile.');

            return;
        }

        $this->editingValues[$field] = $this->sample->$field;
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this environment sample profile.');

            return;
        }

        try {
            $this->validate([
                'editingValues.area' => 'nullable|string|max:255',
                'editingValues.latitude' => 'nullable|numeric|between:-90,90',
                'editingValues.longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $this->sample->update([$field => $this->editingValues[$field]]);

            // Re-query the sample with all relationships
            $projectId = session('selected_project_id');
            $this->sample = EnvironmentSamples::with([
                'environment_sample_types',
                'sampling_sites',
                'people',
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
            return view('livewire.environment-sample-profile', [
                'environmentSample' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.environment-sample-profile', [
            'environmentSample' => $this->sample,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
