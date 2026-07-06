<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Pools;
use App\Models\Tubes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TubesService
{
    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function isGuestMode()
    {
        return $this->projectId === null;
    }

    public function check_or_create($model, $conditions, $attributes = [])
    {
        $instance = $model::where($conditions)->first();

        if (! $instance) {
            $instance = $model::create(array_merge($conditions, $attributes));
        }

        return $instance;
    }

    public function assign()
    {
        // Get people based on project or guest mode
        if ($this->isGuestMode()) {
            $people = collect(); // Empty collection in guest mode
        } else {
            $people = People::whereHas('projects', function ($query) {
                $query->where('projects.id', $this->projectId);
            })->get();
        }

        // Get tubes with appropriate filtering
        $tubes = Tubes::whereHasMorph(
            'tubes_content',
            [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Cultures::class, Pools::class, NucleicAcids::class, Experiments::class],
        )->with([
            'tubes_content',
            'tubes_content.projects',
            'tubes_content.people',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $human_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [HumanSamples::class],
        )->with([
            'tubes_content',
            'tubes_content.humans',
            'tubes_content.sample_types',
            'tubes_content.sampling_sites',
            'tubes_content.people',
            'tubes_content.projects',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $animal_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [AnimalSamples::class],
        )->with([
            'tubes_content',
            'tubes_content.animals',
            'tubes_content.animals.animal_species',
            'tubes_content.sample_types',
            'tubes_content.sampling_sites',
            'tubes_content.people',
            'tubes_content.projects',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $environment_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [EnvironmentSamples::class],
        )->with([
            'tubes_content',
            'tubes_content.environment_sample_types',
            'tubes_content.sampling_sites',
            'tubes_content.people',
            'tubes_content.projects',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $parasite_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [ParasiteSamples::class],
        )->with([
            'tubes_content',
            'tubes_content.parasites',
            'tubes_content.parasites.parasite_species',
            'tubes_content.parasites.parasites_origin',
            'tubes_content.parasites.parasites_origin.sampling_sites',
            'tubes_content.parasites.parasites_origin.sampling_sites.countries',
            'tubes_content.parasite_sample_types',
            'tubes_content.people',
            'tubes_content.projects',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $culture_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [Cultures::class],
        )->with([
            'tubes_content',
            'tubes_content.parent',
            'tubes_content.cultures_content',
            'tubes_content.laboratories',
            'tubes_content.people',
            'tubes_content.projects',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $pool_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [Pools::class],
        )->with([
            'tubes_content',
            'tubes_content.pool_contents',
            'tubes_content.pool_contents.samples',
            'tubes_content.laboratories',
            'tubes_content.people',
            'tubes_content.projects',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $nucleic_tubes = Tubes::whereHasMorph('tubes_content', [NucleicAcids::class]
        )->with([
            'tubes_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects',
            'tubes_content.nucleic_content',
            'tubes_content.laboratories',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        $experiment_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [Experiments::class],
        )->with([
            'tubes_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects',
            'projects',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->orderBy('created_at', 'desc')
            ->get();

        // Define nucleic states for elution types
        $nucleic_states = [
            'frozen',
            'refrigerated',
            'room temperature',
            'heated',
            'lyophilized',
            'ethanol preserved',
            'glycerol preserved',
            'formalin preserved',
            'other',
        ];

        return [
            'tubes' => $tubes,
            'human_tubes' => $human_tubes,
            'animal_tubes' => $animal_tubes,
            'environment_tubes' => $environment_tubes,
            'parasite_tubes' => $parasite_tubes,
            'culture_tubes' => $culture_tubes,
            'pool_tubes' => $pool_tubes,
            'nucleic_tubes' => $nucleic_tubes,
            'experiment_tubes' => $experiment_tubes,
            'people' => $people,
            'nucleic_states' => $nucleic_states,
        ];
    }

    /**
     * Paginated tube lists for `/experiments/create` selection modals.
     *
     * We still expose the full dataset, but only load one page per tube type
     * to avoid rendering massive HTML tables that can exhaust PHP memory.
     *
     * @return array{
     *   human_tubes:LengthAwarePaginator,
     *   animal_tubes:LengthAwarePaginator,
     *   environment_tubes:LengthAwarePaginator,
     *   parasite_tubes:LengthAwarePaginator,
     *   nucleic_tubes:LengthAwarePaginator,
     *   culture_tubes:LengthAwarePaginator,
     *   pool_tubes:LengthAwarePaginator
     * }
     */
    public function paginateForExperimentsCreate(int $perPage = 10): array
    {
        $human_tubes = Tubes::whereHasMorph('tubes_content', [HumanSamples::class])
            ->with([
                'tubes_content',
                'tubes_content.humans',
                'tubes_content.sample_types',
                'tubes_content.sampling_sites',
                'tubes_content.people',
                'tubes_content.projects',
                'projects',
            ])
            ->where('projects_id', $this->projectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'human_tubes_page');

        $animal_tubes = Tubes::whereHasMorph('tubes_content', [AnimalSamples::class])
            ->with([
                'tubes_content',
                'tubes_content.animals',
                'tubes_content.animals.animal_species',
                'tubes_content.sample_types',
                'tubes_content.sampling_sites',
                'tubes_content.people',
                'tubes_content.projects',
                'projects',
            ])
            ->where('projects_id', $this->projectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'animal_tubes_page');

        $environment_tubes = Tubes::whereHasMorph('tubes_content', [EnvironmentSamples::class])
            ->with([
                'tubes_content',
                'tubes_content.environment_sample_types',
                'tubes_content.sampling_sites',
                'tubes_content.people',
                'tubes_content.projects',
                'projects',
            ])
            ->where('projects_id', $this->projectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'environment_tubes_page');

        $parasite_tubes = Tubes::whereHasMorph('tubes_content', [ParasiteSamples::class])
            ->with([
                'tubes_content',
                'tubes_content.parasites',
                'tubes_content.parasites.parasite_species',
                'tubes_content.parasites.parasites_origin',
                'tubes_content.parasites.parasites_origin.sampling_sites',
                'tubes_content.parasites.parasites_origin.sampling_sites.countries',
                'tubes_content.parasite_sample_types',
                'tubes_content.people',
                'tubes_content.projects',
                'projects',
            ])
            ->where('projects_id', $this->projectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'parasite_tubes_page');

        $nucleic_tubes = Tubes::whereHasMorph('tubes_content', [NucleicAcids::class])
            ->with([
                'tubes_content',
                'tubes_content.protocols',
                'tubes_content.people',
                'tubes_content.projects',
                'tubes_content.nucleic_content',
                'tubes_content.laboratories',
                'projects',
            ])
            ->where('projects_id', $this->projectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'nucleic_tubes_page');

        $culture_tubes = Tubes::whereHasMorph('tubes_content', [Cultures::class])
            ->with([
                'tubes_content',
                'tubes_content.parent',
                'tubes_content.cultures_content',
                'tubes_content.laboratories',
                'tubes_content.people',
                'tubes_content.projects',
                'projects',
            ])
            ->where('projects_id', $this->projectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'culture_tubes_page');

        $pool_tubes = Tubes::whereHasMorph('tubes_content', [Pools::class])
            ->with([
                'tubes_content',
                'tubes_content.pool_contents',
                'tubes_content.pool_contents.samples',
                'tubes_content.laboratories',
                'tubes_content.people',
                'tubes_content.projects',
                'projects',
            ])
            ->where('projects_id', $this->projectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'pool_tubes_page');

        return [
            'human_tubes' => $human_tubes,
            'animal_tubes' => $animal_tubes,
            'environment_tubes' => $environment_tubes,
            'parasite_tubes' => $parasite_tubes,
            'nucleic_tubes' => $nucleic_tubes,
            'culture_tubes' => $culture_tubes,
            'pool_tubes' => $pool_tubes,
        ];
    }
}
