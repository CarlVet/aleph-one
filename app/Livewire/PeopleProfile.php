<?php

namespace App\Livewire;

use App\Models\People;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PeopleProfile extends Component
{
    use WithPagination;

    public $person;

    public $personId;

    public $experimentsPerPage = 10;

    public $metaPerPage = 10;

    public $currentExpPage = 1;

    public $currentMetaPage = 1;

    protected $paginationTheme = 'bootstrap';

    public function mount($id = null)
    {
        $this->personId = $id;

        if ($id) {
            $this->person = People::with([
                'organizations',
                'organizations.countries',
                'departments',
                'users',
                'projects',
                'human_samples.sample_types',
                'animal_samples.sample_types',
                'environment_samples.environment_sample_types',
                'parasite_samples.parasite_sample_types',
                'nucleic_acids',
                'cultures',
                'pools',
                'experiments.protocols.techniques',
                'experiments.pathogens',
                'experiments.people',
                'experiments.laboratories',
                'experiments.experiments_content',
                'sequences',
                'parasites',
                'meta_animals.studies',
                'meta_animals.animal_species',
                'meta_animals.sample_types',
                'meta_animals.pathogens',
                'meta_humans.studies',
                'meta_humans.sample_types',
                'meta_humans.pathogens',
                'meta_environments.studies',
                'meta_environments.environment_sample_types',
                'meta_environments.pathogens',
                'meta_parasites.studies',
                'meta_parasites.parasite_sample_types',
                'meta_parasites.pathogens',
                'fundings',
            ])->findOrFail($id);
        } else {
            $this->person = Auth::user()->people;

            // Load relationships for the current user's person
            $this->person->load([
                'organizations',
                'departments',
                'users',
                'projects',
                'human_samples.sample_types',
                'animal_samples.sample_types',
                'environment_samples.environment_sample_types',
                'parasite_samples.parasite_sample_types',
                'nucleic_acids',
                'cultures',
                'pools',
                'experiments.protocols.techniques',
                'experiments.pathogens',
                'experiments.people',
                'experiments.laboratories',
                'experiments.experiments_content',
                'sequences',
                'parasites',
                'meta_animals.studies',
                'meta_animals.animal_species',
                'meta_animals.sample_types',
                'meta_animals.pathogens',
                'meta_humans.studies',
                'meta_humans.sample_types',
                'meta_humans.pathogens',
                'meta_environments.studies',
                'meta_environments.environment_sample_types',
                'meta_environments.pathogens',
                'meta_parasites.studies',
                'meta_parasites.parasite_sample_types',
                'meta_parasites.pathogens',
                'fundings',
            ]);
        }
    }

    public function getExperimentsProperty()
    {
        $startIndex = ($this->currentExpPage - 1) * $this->experimentsPerPage;

        return $this->person->experiments->slice($startIndex, $this->experimentsPerPage);
    }

    public function getMetaStudiesProperty()
    {
        $allMeta = collect();

        // Combine all meta studies
        $allMeta = $allMeta->merge($this->person->meta_animals);
        $allMeta = $allMeta->merge($this->person->meta_humans);
        $allMeta = $allMeta->merge($this->person->meta_environments);
        $allMeta = $allMeta->merge($this->person->meta_parasites);

        $startIndex = ($this->currentMetaPage - 1) * $this->metaPerPage;

        return $allMeta->slice($startIndex, $this->metaPerPage);
    }

    public function getTotalExperimentsPagesProperty()
    {
        return ceil($this->person->experiments->count() / $this->experimentsPerPage);
    }

    public function getTotalMetaPagesProperty()
    {
        $totalMeta = $this->person->meta_animals->count() +
                    $this->person->meta_humans->count() +
                    $this->person->meta_environments->count() +
                    $this->person->meta_parasites->count();

        return ceil($totalMeta / $this->metaPerPage);
    }

    public function setExpPage($page)
    {
        $this->currentExpPage = $page;
    }

    public function setMetaPage($page)
    {
        $this->currentMetaPage = $page;
    }

    public function render()
    {
        // Calculate comprehensive stats
        $stats = [
            'total_projects' => $this->person->projects()->count(),
            'total_samples' => $this->person->human_samples()->count() +
                              $this->person->animal_samples()->count() +
                              $this->person->environment_samples()->count() +
                              $this->person->parasite_samples()->count() +
                              $this->person->nucleic_acids()->count() +
                              $this->person->cultures()->count() +
                              $this->person->pools()->count(),
            'total_experiments' => $this->person->experiments()->count(),
            'total_nucleic_acids' => $this->person->nucleic_acids()->count(),
            'total_cultures' => $this->person->cultures()->count(),
            'total_pools' => $this->person->pools()->count(),
            'total_sequences' => $this->person->sequences()->count(),
            'total_parasites' => $this->person->parasites()->count(),
            'total_meta_studies' => $this->person->meta_animals()->count() +
                                   $this->person->meta_humans()->count() +
                                   $this->person->meta_environments()->count() +
                                   $this->person->meta_parasites()->count(),
            'total_fundings' => $this->person->fundings()->count(),
        ];

        return view('livewire.people-profile', [
            'person' => $this->person,
            'stats' => $stats,
            'experiments' => $this->experiments,
            'metaStudies' => $this->metaStudies,
            'totalExpPages' => $this->totalExperimentsPages,
            'totalMetaPages' => $this->totalMetaPages,
        ]);
    }
}
