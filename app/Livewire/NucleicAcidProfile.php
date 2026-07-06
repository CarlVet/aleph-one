<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

class NucleicAcidProfile extends PlainComponent
{
    public $nucleicAcid;

    public $code;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    // Inline editing properties
    public $editingValues = [
        'concentration' => '',
        'volume' => '',
        'notes' => '',
    ];

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->loadNucleicAcid();
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this nucleic acid profile.';

            return;
        }

        $nucleicAcid = NucleicAcids::where('code', $this->code)
            ->where('projects_id', $selectedProjectId)
            ->first();

        if (! $nucleicAcid) {
            $existsInAnotherProject = NucleicAcids::where('code', $this->code)->exists();
            $this->canView = false;
            $this->unauthorizedMessage = $existsInAnotherProject
                ? 'You are not authorized to view the profile of this nucleic acid because it does not belong to your selected project.'
                : 'Nucleic acid not found.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($nucleicAcid->people_id ?? 0), 'nucleic_acids');

        $this->canView = true;
    }

    private function loadNucleicAcid()
    {
        $projectId = session('selected_project_id');

        $this->nucleicAcid = NucleicAcids::whereHasMorph(
            'nucleic_content', [AnimalSamples::class, HumanSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Experiments::class, Cultures::class, Pools::class]
        )->with([
            'nucleic_content' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Experiments::class => ['experiments_content'],
                ]);
            },
            'people',
            'laboratories',
            'projects',
            'protocols',
            'experiments.protocols.techniques',
            'experiments.pathogens',
            'experiments.people',
            'experiments.laboratories',
            'tubes' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'tubes.tube_positions.boxes',
        ])->where('code', $this->code)
            ->where('projects_id', $projectId)
            ->firstOrFail();
    }

    public function deleteNucleicAcid()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this nucleic acid.');

            return;
        }

        try {
            // Delete the nucleic acid
            $this->nucleicAcid->delete();

            session()->flash('message', 'Nucleic acid deleted successfully!');

            // Redirect to nucleic acids list
            return redirect('/samples/nucleic/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete nucleic acid: '.$e->getMessage());
        }
    }

    // Helper method to load sample with all relationships
    private function loadSampleWithRelationships()
    {
        $projectId = session('selected_project_id');

        return NucleicAcids::whereHasMorph(
            'nucleic_content', [AnimalSamples::class, HumanSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Experiments::class, Cultures::class, Pools::class]
        )->with([
            'nucleic_content' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Experiments::class => ['experiments_content'],
                ]);
            },
            'people',
            'laboratories',
            'projects',
            'protocols',
            'experiments.protocols.techniques',
            'experiments.pathogens',
            'experiments.people',
            'experiments.laboratories',
            'tubes' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'tubes.tube_positions.boxes',
        ])->where('code', $this->code)
            ->where('projects_id', $projectId)
            ->firstOrFail();

    }

    // Inline editing methods
    public function startEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this nucleic acid profile.');

            return;
        }

        $this->nucleicAcid = $this->loadSampleWithRelationships();
        $this->editingValues[$field] = $this->nucleicAcid->$field;
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this nucleic acid profile.');

            return;
        }

        try {
            $this->validate([
                'editingValues.concentration' => 'nullable|numeric|min:0',
                'editingValues.volume' => 'nullable|numeric|min:0',
                'editingValues.notes' => 'nullable|string|max:1000',
            ]);

            $this->nucleicAcid->update([$field => $this->editingValues[$field]]);

            // Re-query the nucleic acid with all relationships
            $projectId = session('selected_project_id');
            $this->nucleicAcid = $this->loadSampleWithRelationships();

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
        $this->nucleicAcid = $this->loadSampleWithRelationships();
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.nucleic-acid-profile', [
                'nucleicAcid' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.nucleic-acid-profile', [
            'nucleicAcid' => $this->nucleicAcid,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
