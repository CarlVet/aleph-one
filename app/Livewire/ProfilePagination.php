<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\BoxPositions;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Pools;
use App\Models\TubePositions;
use App\Models\Tubes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProfilePagination extends Component
{
    public int $currentHumanPage = 1;

    public int $currentAnimalPage = 1;

    public int $currentEnvironmentPage = 1;

    public int $currentParasitePage = 1;

    public int $currentNucleicPage = 1;

    public int $currentCulturePage = 1;

    public int $currentPoolPage = 1;

    public int $currentExpPage = 1;

    public int $currentMetaPage = 1;

    public int $currentTubePage = 1;

    public int $currentTubePositionPage = 1;

    public int $currentBoxPage = 1;

    public int $samplesPerPage = 8;

    public int $experimentsPerPage = 10;

    public int $metaPerPage = 10;

    public int $tubesPerPage = 10;

    public int $tubePositionsPerPage = 10;

    public int $boxesPerPage = 10;

    public $personId;

    public array $stats = [];

    public string $humanCodeFilter = '';

    public string $humanTypeFilter = '';

    public string $humanDateFilter = '';

    public string $animalCodeFilter = '';

    public string $animalTypeFilter = '';

    public string $animalDateFilter = '';

    public string $environmentCodeFilter = '';

    public string $environmentTypeFilter = '';

    public string $environmentDateFilter = '';

    public string $parasiteCodeFilter = '';

    public string $parasiteTypeFilter = '';

    public string $parasiteDateFilter = '';

    public string $nucleicCodeFilter = '';

    public string $nucleicTypeFilter = '';

    public string $nucleicDateFilter = '';

    public string $cultureCodeFilter = '';

    public string $cultureTypeFilter = '';

    public string $cultureDateFilter = '';

    public string $poolCodeFilter = '';

    public string $poolCountFilter = '';

    public string $poolDateFilter = '';

    public string $expCodeFilter = '';

    public string $expContentTypeFilter = '';

    public string $expProtocolFilter = '';

    public string $expOutcomeFilter = '';

    public string $expDateFilter = '';

    public string $metaTypeFilter = '';

    public string $metaReferenceFilter = '';

    public string $metaPathogenFilter = '';

    public string $metaYearFilter = '';

    public string $tubeCodeFilter = '';

    public string $tubeAliasFilter = '';

    public string $tubeTypeFilter = '';

    public string $tubeProjectFilter = '';

    public string $tubePositionTubeCodeFilter = '';

    public string $tubePositionBoxCodeFilter = '';

    public string $tubePositionXFilter = '';

    public string $tubePositionYFilter = '';

    public string $tubePositionDateFilter = '';

    public string $boxCodeFilter = '';

    public string $boxContentTypeFilter = '';

    public string $boxDateFilter = '';

    public string $boxLocationFilter = '';

    public function mount($person, $stats): void
    {
        $this->personId = $person->id;
        $this->stats = $stats;
        $this->currentHumanPage = (int) request()->get('human_page', 1);
        $this->currentAnimalPage = (int) request()->get('animal_page', 1);
        $this->currentEnvironmentPage = (int) request()->get('environment_page', 1);
        $this->currentParasitePage = (int) request()->get('parasite_page', 1);
        $this->currentNucleicPage = (int) request()->get('nucleic_page', 1);
        $this->currentCulturePage = (int) request()->get('culture_page', 1);
        $this->currentPoolPage = (int) request()->get('pool_page', 1);
        $this->currentExpPage = (int) request()->get('exp_page', 1);
        $this->currentMetaPage = (int) request()->get('meta_page', 1);
        $this->currentTubePage = (int) request()->get('tube_page', 1);
        $this->currentTubePositionPage = (int) request()->get('tube_position_page', 1);
        $this->currentBoxPage = (int) request()->get('box_page', 1);
    }

    public function updating(string $name): void
    {
        if (str_starts_with((string) $name, 'human')) {
            $this->currentHumanPage = 1;
        }
        if (str_starts_with((string) $name, 'animal')) {
            $this->currentAnimalPage = 1;
        }
        if (str_starts_with((string) $name, 'environment')) {
            $this->currentEnvironmentPage = 1;
        }
        if (str_starts_with((string) $name, 'parasite')) {
            $this->currentParasitePage = 1;
        }
        if (str_starts_with((string) $name, 'nucleic')) {
            $this->currentNucleicPage = 1;
        }
        if (str_starts_with((string) $name, 'culture')) {
            $this->currentCulturePage = 1;
        }
        if (str_starts_with((string) $name, 'pool')) {
            $this->currentPoolPage = 1;
        }
        if (str_starts_with((string) $name, 'exp')) {
            $this->currentExpPage = 1;
        }
        if (str_starts_with((string) $name, 'meta')) {
            $this->currentMetaPage = 1;
        }
        if (str_starts_with((string) $name, 'tubePosition')) {
            $this->currentTubePositionPage = 1;
        }
        if (str_starts_with((string) $name, 'tube') && ! str_starts_with((string) $name, 'tubePosition')) {
            $this->currentTubePage = 1;
        }
        if (str_starts_with((string) $name, 'box')) {
            $this->currentBoxPage = 1;
        }
    }

    public function setHumanPage(int $page): void
    {
        $this->currentHumanPage = max(1, $page);
    }

    public function setAnimalPage(int $page): void
    {
        $this->currentAnimalPage = max(1, $page);
    }

    public function setEnvironmentPage(int $page): void
    {
        $this->currentEnvironmentPage = max(1, $page);
    }

    public function setParasitePage(int $page): void
    {
        $this->currentParasitePage = max(1, $page);
    }

    public function setNucleicPage(int $page): void
    {
        $this->currentNucleicPage = max(1, $page);
    }

    public function setCulturePage(int $page): void
    {
        $this->currentCulturePage = max(1, $page);
    }

    public function setPoolPage(int $page): void
    {
        $this->currentPoolPage = max(1, $page);
    }

    public function setExpPage(int $page): void
    {
        $this->currentExpPage = max(1, $page);
    }

    public function setMetaPage(int $page): void
    {
        $this->currentMetaPage = max(1, $page);
    }

    public function setTubePage(int $page): void
    {
        $this->currentTubePage = max(1, $page);
    }

    public function setTubePositionPage(int $page): void
    {
        $this->currentTubePositionPage = max(1, $page);
    }

    public function setBoxPage(int $page): void
    {
        $this->currentBoxPage = max(1, $page);
    }

    public function getPersonProperty(): People
    {
        return People::query()->findOrFail($this->personId);
    }

    private function humanSamplesQuery()
    {
        return DB::table('human_samples')
            ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
            ->where('human_samples.people_id', $this->personId)
            ->selectRaw('human_samples.code as code, COALESCE(sample_types.name, "N/A") as sample_type, human_samples.date_collected as event_date');
    }

    private function animalSamplesQuery()
    {
        return DB::table('animal_samples')
            ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
            ->where('animal_samples.people_id', $this->personId)
            ->selectRaw('animal_samples.code as code, COALESCE(sample_types.name, "N/A") as sample_type, animal_samples.date_collected as event_date');
    }

    private function environmentSamplesQuery()
    {
        return DB::table('environment_samples')
            ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
            ->where('environment_samples.people_id', $this->personId)
            ->selectRaw('environment_samples.code as code, COALESCE(environment_sample_types.name, "N/A") as sample_type, environment_samples.date_collected as event_date');
    }

    private function parasiteSamplesQuery()
    {
        return DB::table('parasite_samples')
            ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->where('parasite_samples.people_id', $this->personId)
            ->selectRaw('parasite_samples.code as code, COALESCE(parasite_sample_types.name, "N/A") as sample_type, parasite_samples.date_processed as event_date');
    }

    private function nucleicSamplesQuery()
    {
        return DB::table('nucleic_acids')
            ->where('nucleic_acids.people_id', $this->personId)
            ->selectRaw('nucleic_acids.code as code, COALESCE(nucleic_acids.type, "N/A") as sample_type, nucleic_acids.date_extracted as event_date');
    }

    private function cultureSamplesQuery()
    {
        return DB::table('cultures')
            ->where('cultures.people_id', $this->personId)
            ->selectRaw('cultures.code as code, COALESCE(cultures.type, "N/A") as sample_type, cultures.date_cultured as event_date');
    }

    private function poolSamplesQuery()
    {
        return DB::table('pools')
            ->where('pools.people_id', $this->personId)
            ->selectRaw('pools.code as code, CAST(pools.nr_pooled as TEXT) as sample_type, pools.date_pooled as event_date');
    }

    private function experimentsBaseQuery()
    {
        return DB::table('experiments')
            ->leftJoin('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->where('experiments.people_id', $this->personId)
            ->selectRaw('experiments.code as code, experiments.experiments_content_type as content_type, protocols.name as protocol_name, experiments.outcome_discrete as outcome, experiments.date_tested as date_tested');
    }

    private function metaBaseQuery()
    {
        $metaAnimal = DB::table('meta_animals')
            ->leftJoin('studies', 'meta_animals.studies_id', '=', 'studies.id')
            ->leftJoin('pathogens', 'meta_animals.pathogens_id', '=', 'pathogens.id')
            ->where('meta_animals.people_id', $this->personId)
            ->selectRaw("meta_animals.id as id, 'Animal' as study_type, studies.ref_key as reference, pathogens.species as pathogen, studies.publication_year as publication_year");

        $metaHuman = DB::table('meta_humans')
            ->leftJoin('studies', 'meta_humans.studies_id', '=', 'studies.id')
            ->leftJoin('pathogens', 'meta_humans.pathogens_id', '=', 'pathogens.id')
            ->where('meta_humans.people_id', $this->personId)
            ->selectRaw("meta_humans.id as id, 'Human' as study_type, studies.ref_key as reference, pathogens.species as pathogen, studies.publication_year as publication_year");

        $metaEnvironment = DB::table('meta_environments')
            ->leftJoin('studies', 'meta_environments.studies_id', '=', 'studies.id')
            ->leftJoin('pathogens', 'meta_environments.pathogens_id', '=', 'pathogens.id')
            ->where('meta_environments.people_id', $this->personId)
            ->selectRaw("meta_environments.id as id, 'Environment' as study_type, studies.ref_key as reference, pathogens.species as pathogen, studies.publication_year as publication_year");

        $metaParasite = DB::table('meta_parasites')
            ->leftJoin('studies', 'meta_parasites.studies_id', '=', 'studies.id')
            ->leftJoin('pathogens', 'meta_parasites.pathogens_id', '=', 'pathogens.id')
            ->where('meta_parasites.people_id', $this->personId)
            ->selectRaw("meta_parasites.id as id, 'Parasite' as study_type, studies.ref_key as reference, pathogens.species as pathogen, studies.publication_year as publication_year");

        return DB::query()->fromSub(
            $metaAnimal->unionAll($metaHuman)->unionAll($metaEnvironment)->unionAll($metaParasite),
            'meta_rows'
        );
    }

    private function storageTubesBaseQuery()
    {
        return Tubes::query()
            ->leftJoin('projects', 'tubes.projects_id', '=', 'projects.id')
            ->where(function ($outer) {
                $outer->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('human_samples')
                        ->whereColumn('human_samples.id', 'tubes.tubes_content_id')
                        ->where('tubes.tubes_content_type', HumanSamples::class)
                        ->where('human_samples.people_id', $this->personId);
                })
                    ->orWhereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('animal_samples')
                            ->whereColumn('animal_samples.id', 'tubes.tubes_content_id')
                            ->where('tubes.tubes_content_type', AnimalSamples::class)
                            ->where('animal_samples.people_id', $this->personId);
                    })
                    ->orWhereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('environment_samples')
                            ->whereColumn('environment_samples.id', 'tubes.tubes_content_id')
                            ->where('tubes.tubes_content_type', EnvironmentSamples::class)
                            ->where('environment_samples.people_id', $this->personId);
                    })
                    ->orWhereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('parasite_samples')
                            ->whereColumn('parasite_samples.id', 'tubes.tubes_content_id')
                            ->where('tubes.tubes_content_type', ParasiteSamples::class)
                            ->where('parasite_samples.people_id', $this->personId);
                    })
                    ->orWhereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('nucleic_acids')
                            ->whereColumn('nucleic_acids.id', 'tubes.tubes_content_id')
                            ->where('tubes.tubes_content_type', NucleicAcids::class)
                            ->where('nucleic_acids.people_id', $this->personId);
                    })
                    ->orWhereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('cultures')
                            ->whereColumn('cultures.id', 'tubes.tubes_content_id')
                            ->where('tubes.tubes_content_type', Cultures::class)
                            ->where('cultures.people_id', $this->personId);
                    })
                    ->orWhereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('pools')
                            ->whereColumn('pools.id', 'tubes.tubes_content_id')
                            ->where('tubes.tubes_content_type', Pools::class)
                            ->where('pools.people_id', $this->personId);
                    });
            })
            ->selectRaw('tubes.code as code, tubes.alias_code as alias_code, tubes.tube_type as tube_type, tubes.purpose as purpose, tubes.date_processed as date_processed, projects.code as project_code');
    }

    private function storageBoxesBaseQuery()
    {
        return BoxPositions::query()
            ->leftJoin('boxes', 'box_positions.boxes_id', '=', 'boxes.id')
            ->leftJoin('locations', 'box_positions.locations_id', '=', 'locations.id')
            ->where('box_positions.people_id', $this->personId)
            ->selectRaw('boxes.code as box_code, boxes.content_type as content_type, box_positions.date_moved as date_moved, locations.name as location, box_positions.reason as reason');
    }

    private function storageTubePositionsBaseQuery()
    {
        return TubePositions::query()
            ->leftJoin('tubes', 'tube_positions.tubes_id', '=', 'tubes.id')
            ->leftJoin('boxes', 'tube_positions.boxes_id', '=', 'boxes.id')
            ->where('tube_positions.people_id', $this->personId)
            ->selectRaw('tubes.code as tube_code, boxes.code as box_code, tube_positions.position_x as position_x, tube_positions.position_y as position_y, tube_positions.date_moved as date_moved, tube_positions.reason as reason');
    }

    /**
     * @return array{items: Collection<int, mixed>, total:int, currentPage:int, totalPages:int, from:int, to:int}
     */
    private function paginateQuery($query, int $perPage, int $currentPage, string $orderColumn = 'event_date'): array
    {
        $total = (clone $query)->count();
        $totalPages = max(1, (int) ceil(max($total, 1) / max($perPage, 1)));
        $current = max(1, min($currentPage, $totalPages));
        $from = $total === 0 ? 0 : (($current - 1) * $perPage) + 1;
        $to = min($current * $perPage, $total);
        $items = (clone $query)
            ->orderByDesc($orderColumn)
            ->offset(($current - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $current,
            'totalPages' => $totalPages,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function render()
    {
        $humanQuery = $this->humanSamplesQuery();
        if ($this->humanCodeFilter !== '') {
            $humanQuery->whereRaw('LOWER(human_samples.code) like ?', ['%'.mb_strtolower($this->humanCodeFilter).'%']);
        }
        if ($this->humanTypeFilter !== '') {
            $humanQuery->whereRaw('LOWER(COALESCE(sample_types.name, "")) like ?', ['%'.mb_strtolower($this->humanTypeFilter).'%']);
        }
        if ($this->humanDateFilter !== '') {
            $humanQuery->whereRaw('CAST(human_samples.date_collected as TEXT) like ?', ['%'.$this->humanDateFilter.'%']);
        }

        $animalQuery = $this->animalSamplesQuery();
        if ($this->animalCodeFilter !== '') {
            $animalQuery->whereRaw('LOWER(animal_samples.code) like ?', ['%'.mb_strtolower($this->animalCodeFilter).'%']);
        }
        if ($this->animalTypeFilter !== '') {
            $animalQuery->whereRaw('LOWER(COALESCE(sample_types.name, "")) like ?', ['%'.mb_strtolower($this->animalTypeFilter).'%']);
        }
        if ($this->animalDateFilter !== '') {
            $animalQuery->whereRaw('CAST(animal_samples.date_collected as TEXT) like ?', ['%'.$this->animalDateFilter.'%']);
        }

        $environmentQuery = $this->environmentSamplesQuery();
        if ($this->environmentCodeFilter !== '') {
            $environmentQuery->whereRaw('LOWER(environment_samples.code) like ?', ['%'.mb_strtolower($this->environmentCodeFilter).'%']);
        }
        if ($this->environmentTypeFilter !== '') {
            $environmentQuery->whereRaw('LOWER(COALESCE(environment_sample_types.name, "")) like ?', ['%'.mb_strtolower($this->environmentTypeFilter).'%']);
        }
        if ($this->environmentDateFilter !== '') {
            $environmentQuery->whereRaw('CAST(environment_samples.date_collected as TEXT) like ?', ['%'.$this->environmentDateFilter.'%']);
        }

        $parasiteQuery = $this->parasiteSamplesQuery();
        if ($this->parasiteCodeFilter !== '') {
            $parasiteQuery->whereRaw('LOWER(parasite_samples.code) like ?', ['%'.mb_strtolower($this->parasiteCodeFilter).'%']);
        }
        if ($this->parasiteTypeFilter !== '') {
            $parasiteQuery->whereRaw('LOWER(COALESCE(parasite_sample_types.name, "")) like ?', ['%'.mb_strtolower($this->parasiteTypeFilter).'%']);
        }
        if ($this->parasiteDateFilter !== '') {
            $parasiteQuery->whereRaw('CAST(parasite_samples.date_processed as TEXT) like ?', ['%'.$this->parasiteDateFilter.'%']);
        }

        $nucleicQuery = $this->nucleicSamplesQuery();
        if ($this->nucleicCodeFilter !== '') {
            $nucleicQuery->whereRaw('LOWER(nucleic_acids.code) like ?', ['%'.mb_strtolower($this->nucleicCodeFilter).'%']);
        }
        if ($this->nucleicTypeFilter !== '') {
            $nucleicQuery->whereRaw('LOWER(COALESCE(nucleic_acids.type, "")) like ?', ['%'.mb_strtolower($this->nucleicTypeFilter).'%']);
        }
        if ($this->nucleicDateFilter !== '') {
            $nucleicQuery->whereRaw('CAST(nucleic_acids.date_extracted as TEXT) like ?', ['%'.$this->nucleicDateFilter.'%']);
        }

        $cultureQuery = $this->cultureSamplesQuery();
        if ($this->cultureCodeFilter !== '') {
            $cultureQuery->whereRaw('LOWER(cultures.code) like ?', ['%'.mb_strtolower($this->cultureCodeFilter).'%']);
        }
        if ($this->cultureTypeFilter !== '') {
            $cultureQuery->whereRaw('LOWER(COALESCE(cultures.type, "")) like ?', ['%'.mb_strtolower($this->cultureTypeFilter).'%']);
        }
        if ($this->cultureDateFilter !== '') {
            $cultureQuery->whereRaw('CAST(cultures.date_cultured as TEXT) like ?', ['%'.$this->cultureDateFilter.'%']);
        }

        $poolQuery = $this->poolSamplesQuery();
        if ($this->poolCodeFilter !== '') {
            $poolQuery->whereRaw('LOWER(pools.code) like ?', ['%'.mb_strtolower($this->poolCodeFilter).'%']);
        }
        if ($this->poolCountFilter !== '') {
            $poolQuery->whereRaw('CAST(pools.nr_pooled as TEXT) like ?', ['%'.$this->poolCountFilter.'%']);
        }
        if ($this->poolDateFilter !== '') {
            $poolQuery->whereRaw('CAST(pools.date_pooled as TEXT) like ?', ['%'.$this->poolDateFilter.'%']);
        }

        $experimentsQuery = $this->experimentsBaseQuery();
        if ($this->expCodeFilter !== '') {
            $experimentsQuery->whereRaw('LOWER(experiments.code) like ?', ['%'.mb_strtolower($this->expCodeFilter).'%']);
        }
        if ($this->expContentTypeFilter !== '') {
            $typeFilter = mb_strtolower(trim($this->expContentTypeFilter));
            $experimentsQuery->whereRaw('LOWER(experiments.experiments_content_type) like ?', ['%'.$typeFilter.'%']);
        }
        if ($this->expProtocolFilter !== '') {
            $experimentsQuery->whereRaw('LOWER(COALESCE(protocols.name, "")) like ?', ['%'.mb_strtolower($this->expProtocolFilter).'%']);
        }
        if ($this->expOutcomeFilter !== '') {
            $outcomeFilter = mb_strtolower(trim($this->expOutcomeFilter));
            $experimentsQuery->whereRaw('LOWER(COALESCE(experiments.outcome_discrete, "")) like ?', ['%'.$outcomeFilter.'%']);
        }
        if ($this->expDateFilter !== '') {
            $experimentsQuery->whereRaw('CAST(experiments.date_tested as TEXT) like ?', ['%'.$this->expDateFilter.'%']);
        }

        $metaQuery = $this->metaBaseQuery();
        if ($this->metaReferenceFilter !== '') {
            $metaQuery->whereRaw('LOWER(COALESCE(reference, "")) like ?', ['%'.mb_strtolower($this->metaReferenceFilter).'%']);
        }
        if ($this->metaPathogenFilter !== '') {
            $metaQuery->whereRaw('LOWER(COALESCE(pathogen, "")) like ?', ['%'.mb_strtolower($this->metaPathogenFilter).'%']);
        }
        if ($this->metaTypeFilter !== '') {
            $typeFilter = mb_strtolower(trim($this->metaTypeFilter));
            $metaQuery->whereRaw('LOWER(COALESCE(study_type, "")) like ?', ['%'.$typeFilter.'%']);
        }
        if ($this->metaYearFilter !== '') {
            $metaQuery->whereRaw('CAST(publication_year as TEXT) like ?', ['%'.$this->metaYearFilter.'%']);
        }

        $tubesQuery = $this->storageTubesBaseQuery();
        if ($this->tubeCodeFilter !== '') {
            $tubesQuery->whereRaw('LOWER(COALESCE(tubes.code, "")) like ?', ['%'.mb_strtolower($this->tubeCodeFilter).'%']);
        }
        if ($this->tubeAliasFilter !== '') {
            $tubesQuery->whereRaw('LOWER(COALESCE(tubes.alias_code, "")) like ?', ['%'.mb_strtolower($this->tubeAliasFilter).'%']);
        }
        if ($this->tubeTypeFilter !== '') {
            $typeFilter = mb_strtolower(trim($this->tubeTypeFilter));
            $tubesQuery->whereRaw('LOWER(COALESCE(tubes.tube_type, "")) like ?', ['%'.$typeFilter.'%']);
        }
        if ($this->tubeProjectFilter !== '') {
            $tubesQuery->whereRaw('LOWER(COALESCE(projects.code, "")) like ?', ['%'.mb_strtolower($this->tubeProjectFilter).'%']);
        }

        $tubePositionsQuery = $this->storageTubePositionsBaseQuery();
        if ($this->tubePositionTubeCodeFilter !== '') {
            $tubePositionsQuery->whereRaw('LOWER(COALESCE(tubes.code, "")) like ?', ['%'.mb_strtolower($this->tubePositionTubeCodeFilter).'%']);
        }
        if ($this->tubePositionBoxCodeFilter !== '') {
            $tubePositionsQuery->whereRaw('LOWER(COALESCE(boxes.code, "")) like ?', ['%'.mb_strtolower($this->tubePositionBoxCodeFilter).'%']);
        }
        if ($this->tubePositionXFilter !== '') {
            $tubePositionsQuery->whereRaw('CAST(tube_positions.position_x as TEXT) like ?', ['%'.$this->tubePositionXFilter.'%']);
        }
        if ($this->tubePositionYFilter !== '') {
            $tubePositionsQuery->whereRaw('CAST(tube_positions.position_y as TEXT) like ?', ['%'.$this->tubePositionYFilter.'%']);
        }
        if ($this->tubePositionDateFilter !== '') {
            $tubePositionsQuery->whereRaw('CAST(tube_positions.date_moved as TEXT) like ?', ['%'.$this->tubePositionDateFilter.'%']);
        }

        $boxesQuery = $this->storageBoxesBaseQuery();
        if ($this->boxCodeFilter !== '') {
            $boxesQuery->whereRaw('LOWER(COALESCE(boxes.code, "")) like ?', ['%'.mb_strtolower($this->boxCodeFilter).'%']);
        }
        if ($this->boxContentTypeFilter !== '') {
            $boxesQuery->whereRaw('LOWER(COALESCE(boxes.content_type, "")) like ?', ['%'.mb_strtolower($this->boxContentTypeFilter).'%']);
        }
        if ($this->boxDateFilter !== '') {
            $boxesQuery->whereRaw('CAST(box_positions.date_moved as TEXT) like ?', ['%'.$this->boxDateFilter.'%']);
        }
        if ($this->boxLocationFilter !== '') {
            $boxesQuery->whereRaw('LOWER(COALESCE(locations.name, "")) like ?', ['%'.mb_strtolower($this->boxLocationFilter).'%']);
        }

        $humanPagination = $this->paginateQuery($humanQuery, $this->samplesPerPage, $this->currentHumanPage, 'event_date');
        $animalPagination = $this->paginateQuery($animalQuery, $this->samplesPerPage, $this->currentAnimalPage, 'event_date');
        $environmentPagination = $this->paginateQuery($environmentQuery, $this->samplesPerPage, $this->currentEnvironmentPage, 'event_date');
        $parasitePagination = $this->paginateQuery($parasiteQuery, $this->samplesPerPage, $this->currentParasitePage, 'event_date');
        $nucleicPagination = $this->paginateQuery($nucleicQuery, $this->samplesPerPage, $this->currentNucleicPage, 'event_date');
        $culturePagination = $this->paginateQuery($cultureQuery, $this->samplesPerPage, $this->currentCulturePage, 'event_date');
        $poolPagination = $this->paginateQuery($poolQuery, $this->samplesPerPage, $this->currentPoolPage, 'event_date');
        $experimentsPagination = $this->paginateQuery($experimentsQuery, $this->experimentsPerPage, $this->currentExpPage, 'date_tested');
        $metaPagination = $this->paginateQuery($metaQuery, $this->metaPerPage, $this->currentMetaPage, 'publication_year');
        $tubesPagination = $this->paginateQuery($tubesQuery, $this->tubesPerPage, $this->currentTubePage, 'date_processed');
        $tubePositionsPagination = $this->paginateQuery($tubePositionsQuery, $this->tubePositionsPerPage, $this->currentTubePositionPage, 'date_moved');
        $boxesPagination = $this->paginateQuery($boxesQuery, $this->boxesPerPage, $this->currentBoxPage, 'date_moved');

        return view('livewire.profile-pagination', [
            'person' => $this->person,
            'humanPagination' => $humanPagination,
            'animalPagination' => $animalPagination,
            'environmentPagination' => $environmentPagination,
            'parasitePagination' => $parasitePagination,
            'nucleicPagination' => $nucleicPagination,
            'culturePagination' => $culturePagination,
            'poolPagination' => $poolPagination,
            'experimentsPagination' => $experimentsPagination,
            'metaPagination' => $metaPagination,
            'tubesPagination' => $tubesPagination,
            'tubePositionsPagination' => $tubePositionsPagination,
            'boxesPagination' => $boxesPagination,
        ]);
    }
}
