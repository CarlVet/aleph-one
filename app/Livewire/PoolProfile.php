<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PoolProfile extends PlainComponent
{
    public $pool;

    public $code;

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

        $this->loadPool();
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this pool profile.';

            return;
        }

        // Load the pool to check if it belongs to the selected project
        $pool = Pools::where('code', $this->code)->first();

        if (! $pool) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Pool not found.';

            return;
        }

        // Check if the pool belongs to the selected project
        if ($pool->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this pool because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($pool->people_id ?? 0), 'pools');

        $this->canView = true;
    }

    private function loadPool()
    {
        $projectId = $this->selectedProjectId();

        $this->pool = Pools::with([
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'pool_contents',
            'pool_contents.samples' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    HumanSamples::class => ['humans.countries'],
                    AnimalSamples::class => ['animals.animal_species'],
                    EnvironmentSamples::class => ['environment_sample_types'],
                    ParasiteSamples::class => ['parasites.parasite_species'],
                    NucleicAcids::class => [],
                    Cultures::class => [],
                ]);
            },
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
            'tubes' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'tubes.tube_positions.boxes',
        ])->where('code', $this->code)->firstOrFail();
    }

    public function deletePool()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this pool.');

            return;
        }

        try {
            $this->pool->delete();
            session()->flash('message', 'Pool deleted successfully!');

            return redirect('/samples/pools/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete pool: '.$e->getMessage());
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.pool-profile', [
                'pool' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.pool-profile', [
            'pool' => $this->pool,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
