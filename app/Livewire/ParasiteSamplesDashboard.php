<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use App\Models\SubProject;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ParasiteSamplesDashboard extends PlainComponent
{
    use WithPagination;

    protected $projectId;

    public string $sampleVisibility = 'all';

    public string $timelineGranularity = 'monthly';

    public $parasiteSpeciesFilter = '';

    public $parasiteGenusFilter = '';

    public $parasiteFamilyFilter = '';

    public $sampleTypeFilter = '';

    public $stageFilter = '';

    public $sexFilter = '';

    public $stateFilter = '';

    public $startDate;

    public $endDate;

    public $originTypeFilter = 'all';

    public string $originAnimalSpeciesFilter = '';

    public string $originAnimalSexFilter = '';

    public string $originAnimalAgeFilter = '';

    public string $originHumanEthnicityFilter = '';

    public string $originHumanOccupationFilter = '';

    public string $originHumanCountryFilter = '';

    public string $subProjectFilter = '';

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

    public function canEdit()
    {
        if ($this->isGuestMode()) {
            return false;
        }

        $user = Auth::user();
        if (! $user || ! $user->people) {
            return false;
        }

        $project = $user->people->projects()
            ->where('projects.id', $this->projectId)
            ->withPivot('role', 'date_joined', 'permission')
            ->first();

        if (! $project || ! $project->pivot) {
            return false;
        }

        return $project->pivot->permission !== 'viewer';
    }

    public function updated($propertyName)
    {
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function resetFilters()
    {
        $this->reset([
            'sampleVisibility',
            'timelineGranularity',
            'parasiteSpeciesFilter',
            'parasiteGenusFilter',
            'parasiteFamilyFilter',
            'sampleTypeFilter',
            'stageFilter',
            'sexFilter',
            'stateFilter',
            'startDate',
            'endDate',
            'originTypeFilter',
            'originAnimalSpeciesFilter',
            'originAnimalSexFilter',
            'originAnimalAgeFilter',
            'originHumanEthnicityFilter',
            'originHumanOccupationFilter',
            'originHumanCountryFilter',
            'subProjectFilter',
        ]);
        $this->resetPage();
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    /**
     * Build the base query with all necessary relationships and apply filters
     */
    private function buildFilteredQuery()
    {
        $query = $this->baseQuery();

        $this->applyFilters($query);

        return $query;
    }

    private function baseFilteredQuery(array $except = [])
    {
        $query = $this->baseQuery();

        $this->applyFilters($query, $except);

        return $query;
    }

    private function baseQuery()
    {
        $query = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->leftJoin('human_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'human_samples.id')
                    ->where('parasites.parasites_origin_type', HumanSamples::class);
            })
            ->leftJoin('animal_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'animal_samples.id')
                    ->where('parasites.parasites_origin_type', AnimalSamples::class);
            })
            ->leftJoin('animals as origin_animals', 'animal_samples.animals_id', '=', 'origin_animals.id')
            ->leftJoin('animal_species as origin_animal_species', 'origin_animals.animal_species_id', '=', 'origin_animal_species.id')
            ->leftJoin('environment_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'environment_samples.id')
                    ->where('parasites.parasites_origin_type', EnvironmentSamples::class);
            })
            ->leftJoin('humans as origin_humans', 'human_samples.humans_id', '=', 'origin_humans.id')
            ->leftJoin('countries as origin_countries', 'origin_humans.countries_id', '=', 'origin_countries.id')
            ->select('parasite_samples.*');

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        return $query;
    }

    /**
     * Apply all active filters to the query
     */
    private function applyFilters($query, array $except = [])
    {
        // Apply parasite species filter
        if (! in_array('parasiteSpeciesFilter', $except, true) && $this->parasiteSpeciesFilter) {
            $query->where('parasite_species.name_scientific', $this->parasiteSpeciesFilter);
        }

        // Apply parasite genus filter
        if (! in_array('parasiteGenusFilter', $except, true) && $this->parasiteGenusFilter) {
            $query->where('parasite_species.genus', $this->parasiteGenusFilter);
        }

        // Apply parasite family filter
        if (! in_array('parasiteFamilyFilter', $except, true) && $this->parasiteFamilyFilter) {
            $query->where('parasite_species.family', $this->parasiteFamilyFilter);
        }

        // Apply sample type filter
        if (! in_array('sampleTypeFilter', $except, true) && $this->sampleTypeFilter) {
            $query->where('parasite_sample_types.name', $this->sampleTypeFilter);
        }

        // Apply stage filter
        if (! in_array('stageFilter', $except, true) && $this->stageFilter) {
            $query->where('parasites.stage', $this->stageFilter);
        }

        // Apply sex filter
        if (! in_array('sexFilter', $except, true) && $this->sexFilter) {
            $query->where('parasites.sex', $this->sexFilter);
        }

        // Apply state filter
        if (! in_array('stateFilter', $except, true) && $this->stateFilter) {
            $query->where('parasites.state', $this->stateFilter);
        }

        // Apply date range filter (collection date from origin samples)
        if (! in_array('startDate', $except, true) && ! in_array('endDate', $except, true) && $this->startDate && $this->endDate) {
            $query->whereBetween(DB::raw('COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)'), [$this->startDate, $this->endDate]);
        } elseif (! in_array('startDate', $except, true) && $this->startDate) {
            $query->where(DB::raw('COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)'), '>=', $this->startDate);
        } elseif (! in_array('endDate', $except, true) && $this->endDate) {
            $query->where(DB::raw('COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)'), '<=', $this->endDate);
        }

        // Apply origin type filter
        if (! in_array('originTypeFilter', $except, true) && $this->originTypeFilter !== 'all') {
            $originType = match ($this->originTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            if ($originType) {
                $query->where('parasites.parasites_origin_type', $originType);
            }
        }

        if (! in_array('subProjectFilter', $except, true) && $this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'parasite_samples.id')
                    ->where('sub_project_assignments.assignable_type', ParasiteSamples::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }

        if (
            ! in_array('originAnimalSpeciesFilter', $except, true)
            && $this->originAnimalSpeciesFilter !== ''
            && (($this->originTypeFilter === 'animal') || in_array('originTypeFilter', $except, true))
        ) {
            $query->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->where('origin_animal_species.name_common', $this->originAnimalSpeciesFilter);
        }

        if (
            ! in_array('originAnimalSexFilter', $except, true)
            && $this->originAnimalSexFilter !== ''
            && (($this->originTypeFilter === 'animal') || in_array('originTypeFilter', $except, true))
        ) {
            $query->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->where('origin_animals.sex', $this->originAnimalSexFilter);
        }

        if (
            ! in_array('originAnimalAgeFilter', $except, true)
            && $this->originAnimalAgeFilter !== ''
            && (($this->originTypeFilter === 'animal') || in_array('originTypeFilter', $except, true))
        ) {
            $query->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->where('origin_animals.age', $this->originAnimalAgeFilter);
        }

        if (
            ! in_array('originHumanEthnicityFilter', $except, true)
            && $this->originHumanEthnicityFilter !== ''
            && (($this->originTypeFilter === 'human') || in_array('originTypeFilter', $except, true))
        ) {
            $query->where('parasites.parasites_origin_type', HumanSamples::class)
                ->where('origin_humans.ethnicity', $this->originHumanEthnicityFilter);
        }

        if (
            ! in_array('originHumanOccupationFilter', $except, true)
            && $this->originHumanOccupationFilter !== ''
            && (($this->originTypeFilter === 'human') || in_array('originTypeFilter', $except, true))
        ) {
            $query->where('parasites.parasites_origin_type', HumanSamples::class)
                ->where('origin_humans.occupation', $this->originHumanOccupationFilter);
        }

        if (
            ! in_array('originHumanCountryFilter', $except, true)
            && $this->originHumanCountryFilter !== ''
            && (($this->originTypeFilter === 'human') || in_array('originTypeFilter', $except, true))
        ) {
            $query->where('parasites.parasites_origin_type', HumanSamples::class)
                ->where('origin_countries.name', $this->originHumanCountryFilter);
        }

        return $query;
    }

    /**
     * Calculate descriptive statistics from filtered parasite samples
     */
    private function calculateStatistics($baseQuery)
    {
        $total = (clone $baseQuery)->count();

        $byOrigin = (clone $baseQuery)
            ->select('parasites.parasites_origin_type as origin', DB::raw('count(*) as total'))
            ->groupBy('parasites.parasites_origin_type')
            ->pluck('total', 'origin')
            ->toArray();

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m');

        $samplesThisYear = (clone $baseQuery)
            ->whereRaw("strftime('%Y', COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)) = ?", [(string) $currentYear])
            ->count();

        $samplesThisMonth = (clone $baseQuery)
            ->whereRaw("strftime('%Y-%m', COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)) = ?", [$currentMonth])
            ->count();

        return [
            'total_samples' => $total,
            'human_samples' => (int) ($byOrigin[HumanSamples::class] ?? 0),
            'animal_samples' => (int) ($byOrigin[AnimalSamples::class] ?? 0),
            'environment_samples' => (int) ($byOrigin[EnvironmentSamples::class] ?? 0),
            'samples_this_year' => $samplesThisYear,
            'samples_this_month' => $samplesThisMonth,
            'collection_timeline' => $this->generateTimelineData($baseQuery),
        ];
    }

    /**
     * Generate timeline data for the last 12 months
     */
    private function generateTimelineData($baseQuery)
    {
        $driver = DB::getDriverName();
        $dateSql = 'COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)';
        $dateExpr = DB::raw($dateSql);

        $start = $this->timelineGranularity === 'yearly'
            ? Carbon::now()->subYears(9)->startOfYear()->toDateString()
            : Carbon::now()->subMonths(11)->startOfMonth()->toDateString();

        $end = $this->timelineGranularity === 'yearly'
            ? Carbon::now()->endOfYear()->toDateString()
            : Carbon::now()->endOfMonth()->toDateString();

        $groupExpr = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT({$dateSql}, '%Y')",
                'pgsql' => "to_char({$dateSql}, 'YYYY')",
                default => "strftime('%Y', {$dateSql})",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT({$dateSql}, '%Y-%m')",
            'pgsql' => "to_char({$dateSql}, 'YYYY-MM')",
            default => "strftime('%Y-%m', {$dateSql})",
        };

        $counts = (clone $baseQuery)
            ->whereBetween($dateExpr, [$start, $end])
            ->select(DB::raw($groupExpr.' as grp'), DB::raw('count(*) as total'))
            ->groupBy('grp')
            ->pluck('total', 'grp')
            ->toArray();

        $timeline = [];
        if ($this->timelineGranularity === 'yearly') {
            for ($i = 9; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->format('Y');
                $timeline[$year] = (int) ($counts[$year] ?? 0);
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $ym = $month->format('Y-m');
                $timeline[$month->format('M Y')] = (int) ($counts[$ym] ?? 0);
            }
        }

        return $timeline;
    }

    /**
     * Get all available parasite species for filter dropdown
     */
    private function getAllParasiteSpecies()
    {
        $query = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->join('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->select('parasite_species.name_scientific')
            ->distinct()
            ->orderBy('parasite_species.name_scientific');

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        return $query->pluck('parasite_species.name_scientific');
    }

    /**
     * Get all available parasite genera for filter dropdown
     */
    private function getAllParasiteGenera()
    {
        $query = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->join('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.genus')
            ->select('parasite_species.genus')
            ->distinct()
            ->orderBy('parasite_species.genus');

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        return $query->pluck('parasite_species.genus');
    }

    /**
     * Get all available parasite families for filter dropdown
     */
    private function getAllParasiteFamilies()
    {
        $query = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->join('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.family')
            ->select('parasite_species.family')
            ->distinct()
            ->orderBy('parasite_species.family');

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        return $query->pluck('parasite_species.family');
    }

    /**
     * Get all available sample types for filter dropdown
     */
    private function getAllSampleTypes()
    {
        $query = ParasiteSamples::query()
            ->join('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->select('parasite_sample_types.name')
            ->distinct()
            ->orderBy('parasite_sample_types.name');

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        return $query->pluck('parasite_sample_types.name');
    }

    private function dashboardPayload(): array
    {
        $base = $this->buildFilteredQuery();

        $statistics = $this->calculateStatistics($base);

        $parasiteSamplesByOriginRaw = (clone $base)
            ->select('parasites.parasites_origin_type as origin', DB::raw('count(*) as total'))
            ->groupBy('parasites.parasites_origin_type')
            ->pluck('total', 'origin')
            ->toArray();

        $parasiteSamplesByOrigin = [
            'Human' => (int) ($parasiteSamplesByOriginRaw[HumanSamples::class] ?? 0),
            'Animal' => (int) ($parasiteSamplesByOriginRaw[AnimalSamples::class] ?? 0),
            'Environment' => (int) ($parasiteSamplesByOriginRaw[EnvironmentSamples::class] ?? 0),
        ];

        $parasiteSamplesByStage = (clone $base)
            ->select('parasites.stage', DB::raw('count(*) as total'))
            ->groupBy('parasites.stage')
            ->orderByDesc('total')
            ->pluck('total', 'parasites.stage')
            ->toArray();

        $parasiteSamplesBySex = (clone $base)
            ->select('parasites.sex', DB::raw('count(*) as total'))
            ->groupBy('parasites.sex')
            ->orderByDesc('total')
            ->pluck('total', 'parasites.sex')
            ->toArray();

        $parasiteSamplesByState = (clone $base)
            ->whereNotNull('parasites.state')
            ->select('parasites.state', DB::raw('count(*) as total'))
            ->groupBy('parasites.state')
            ->orderByDesc('total')
            ->pluck('total', 'parasites.state')
            ->toArray();

        $parasiteSamplesBySpecies = (clone $base)
            ->select('parasite_species.name_scientific', DB::raw('count(*) as total'))
            ->groupBy('parasite_species.name_scientific')
            ->orderByDesc('total')
            ->limit(20)
            ->pluck('total', 'parasite_species.name_scientific')
            ->toArray();

        $parasiteSamplesByGenus = (clone $base)
            ->whereNotNull('parasite_species.genus')
            ->select('parasite_species.genus', DB::raw('count(*) as total'))
            ->groupBy('parasite_species.genus')
            ->orderByDesc('total')
            ->limit(20)
            ->pluck('total', 'parasite_species.genus')
            ->toArray();

        $parasiteSamplesBySampleType = (clone $base)
            ->whereNotNull('parasite_sample_types.name')
            ->select('parasite_sample_types.name', DB::raw('count(*) as total'))
            ->groupBy('parasite_sample_types.name')
            ->orderByDesc('total')
            ->pluck('total', 'parasite_sample_types.name')
            ->toArray();

        $pieChartTabs = [
            [
                'key' => 'origin',
                'label' => 'Origin',
                'data' => $parasiteSamplesByOrigin,
            ],
            [
                'key' => 'stage',
                'label' => 'Parasite Stage',
                'data' => $parasiteSamplesByStage,
            ],
            [
                'key' => 'sex',
                'label' => 'Parasite Sex',
                'data' => $parasiteSamplesBySex,
            ],
            [
                'key' => 'state',
                'label' => 'Parasite State',
                'data' => $parasiteSamplesByState,
            ],
            [
                'key' => 'sample_type',
                'label' => 'Sample Type',
                'data' => $parasiteSamplesBySampleType,
            ],
        ];

        $barChartTabs = [
            [
                'key' => 'species',
                'label' => 'Parasite Species',
                'data' => $parasiteSamplesBySpecies,
            ],
            [
                'key' => 'genus',
                'label' => 'Parasite Genus',
                'data' => $parasiteSamplesByGenus,
            ],
        ];

        if ($this->originTypeFilter === 'human') {
            $humanEthnicity = (clone $base)
                ->where('parasites.parasites_origin_type', HumanSamples::class)
                ->whereNotNull('origin_humans.ethnicity')
                ->select('origin_humans.ethnicity', DB::raw('count(*) as total'))
                ->groupBy('origin_humans.ethnicity')
                ->orderByDesc('total')
                ->pluck('total', 'origin_humans.ethnicity')
                ->toArray();

            $humanOccupation = (clone $base)
                ->where('parasites.parasites_origin_type', HumanSamples::class)
                ->whereNotNull('origin_humans.occupation')
                ->select('origin_humans.occupation', DB::raw('count(*) as total'))
                ->groupBy('origin_humans.occupation')
                ->orderByDesc('total')
                ->pluck('total', 'origin_humans.occupation')
                ->toArray();

            $humanCountry = (clone $base)
                ->where('parasites.parasites_origin_type', HumanSamples::class)
                ->whereNotNull('origin_countries.name')
                ->select('origin_countries.name', DB::raw('count(*) as total'))
                ->groupBy('origin_countries.name')
                ->orderByDesc('total')
                ->pluck('total', 'origin_countries.name')
                ->toArray();

            $pieChartTabs[] = [
                'key' => 'human_ethnicity',
                'label' => 'Human Ethnicity',
                'data' => $humanEthnicity,
            ];
            $pieChartTabs[] = [
                'key' => 'human_occupation',
                'label' => 'Human Occupation',
                'data' => $humanOccupation,
            ];
            $barChartTabs[] = [
                'key' => 'human_country',
                'label' => 'Human Country',
                'data' => $humanCountry,
            ];
        }

        if ($this->originTypeFilter === 'animal') {
            $animalSpecies = (clone $base)
                ->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->whereNotNull('origin_animal_species.name_common')
                ->select('origin_animal_species.name_common', DB::raw('count(*) as total'))
                ->groupBy('origin_animal_species.name_common')
                ->orderByDesc('total')
                ->pluck('total', 'origin_animal_species.name_common')
                ->toArray();

            $animalAge = (clone $base)
                ->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->whereNotNull('origin_animals.age')
                ->select('origin_animals.age', DB::raw('count(*) as total'))
                ->groupBy('origin_animals.age')
                ->orderByDesc('total')
                ->pluck('total', 'origin_animals.age')
                ->toArray();

            $animalSex = (clone $base)
                ->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->whereNotNull('origin_animals.sex')
                ->select('origin_animals.sex', DB::raw('count(*) as total'))
                ->groupBy('origin_animals.sex')
                ->orderByDesc('total')
                ->pluck('total', 'origin_animals.sex')
                ->toArray();

            $barChartTabs[] = [
                'key' => 'animal_species',
                'label' => 'Animal Species',
                'data' => $animalSpecies,
            ];
            $pieChartTabs[] = [
                'key' => 'animal_age',
                'label' => 'Animal Age',
                'data' => $animalAge,
            ];
            $pieChartTabs[] = [
                'key' => 'animal_sex',
                'label' => 'Animal Sex',
                'data' => $animalSex,
            ];
        }

        $mapColorVariableOptions = [
            ['key' => 'origin', 'label' => 'Origin type'],
            ['key' => 'parasite_species', 'label' => 'Parasite species'],
            ['key' => 'parasite_genus', 'label' => 'Parasite genus'],
            ['key' => 'sample_type', 'label' => 'Parasite sample type'],
            ['key' => 'stage', 'label' => 'Parasite stage'],
            ['key' => 'sex', 'label' => 'Parasite sex'],
        ];

        if ($this->originTypeFilter === 'human') {
            $mapColorVariableOptions[] = ['key' => 'human_ethnicity', 'label' => 'Human ethnicity'];
            $mapColorVariableOptions[] = ['key' => 'human_occupation', 'label' => 'Human occupation'];
            $mapColorVariableOptions[] = ['key' => 'human_country', 'label' => 'Human country'];
        }

        if ($this->originTypeFilter === 'animal') {
            $mapColorVariableOptions[] = ['key' => 'animal_species', 'label' => 'Animal species'];
            $mapColorVariableOptions[] = ['key' => 'animal_age', 'label' => 'Animal age'];
            $mapColorVariableOptions[] = ['key' => 'animal_sex', 'label' => 'Animal sex'];
        }

        $activeFilters = [
            'sampleVisibility' => $this->sampleVisibility,
            'parasiteSpeciesFilter' => $this->parasiteSpeciesFilter,
            'parasiteGenusFilter' => $this->parasiteGenusFilter,
            'parasiteFamilyFilter' => $this->parasiteFamilyFilter,
            'sampleTypeFilter' => $this->sampleTypeFilter,
            'stageFilter' => $this->stageFilter,
            'sexFilter' => $this->sexFilter,
            'stateFilter' => $this->stateFilter,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'originTypeFilter' => $this->originTypeFilter,
            'originAnimalSpeciesFilter' => $this->originAnimalSpeciesFilter,
            'originAnimalSexFilter' => $this->originAnimalSexFilter,
            'originAnimalAgeFilter' => $this->originAnimalAgeFilter,
            'originHumanEthnicityFilter' => $this->originHumanEthnicityFilter,
            'originHumanOccupationFilter' => $this->originHumanOccupationFilter,
            'originHumanCountryFilter' => $this->originHumanCountryFilter,
            'subProjectFilter' => $this->subProjectFilter,
        ];

        return [
            'samples' => [],
            'mapPointsUrl' => route('parasites.dashboard.map-points'),
            'activeFilters' => $activeFilters,
            'modalTableUrls' => [
                'samplesModal' => route('parasites.dashboard.modal.samples'),
                'humanSamplesModal' => route('parasites.dashboard.modal.samples.human'),
                'animalSamplesModal' => route('parasites.dashboard.modal.samples.animal'),
                'environmentSamplesModal' => route('parasites.dashboard.modal.samples.environment'),
            ],
            'descriptive_stats' => $statistics,
            'parasiteSamplesByOrigin' => $parasiteSamplesByOrigin,
            'parasiteSamplesByStage' => $parasiteSamplesByStage,
            'parasiteSamplesBySex' => $parasiteSamplesBySex,
            'parasiteSamplesByState' => $parasiteSamplesByState,
            'parasiteSamplesBySpecies' => $parasiteSamplesBySpecies,
            'parasiteSamplesByGenus' => $parasiteSamplesByGenus,
            'parasiteSamplesBySampleType' => $parasiteSamplesBySampleType,
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
        ];
    }

    public function filteredData()
    {
        $payload = $this->dashboardPayload();

        $allParasiteSpecies = $this->getAllParasiteSpecies();
        $allParasiteGenera = $this->getAllParasiteGenera();
        $allParasiteFamilies = $this->getAllParasiteFamilies();
        $allSampleTypes = $this->getAllSampleTypes();
        $allOriginAnimalSpecies = $this->getAllOriginAnimalSpecies();
        $availableOriginTypes = $this->availableOriginTypes();
        $allStages = $this->allStages();
        $allSexes = $this->allSexes();
        $allStates = $this->allStates();
        $originAnimalSexesOptions = $this->originAnimalSexesOptions();
        $originAnimalAgesOptions = $this->originAnimalAgesOptions();
        $originHumanEthnicitiesOptions = $this->originHumanEthnicitiesOptions();
        $originHumanOccupationsOptions = $this->originHumanOccupationsOptions();
        $originHumanCountriesOptions = $this->originHumanCountriesOptions();

        return array_merge($payload, [
            'isGuestMode' => $this->isGuestMode(),
            'allParasiteSpecies' => $allParasiteSpecies,
            'allParasiteGenera' => $allParasiteGenera,
            'allParasiteFamilies' => $allParasiteFamilies,
            'allSampleTypes' => $allSampleTypes,
            'allOriginAnimalSpecies' => $allOriginAnimalSpecies,
            'availableOriginTypes' => $availableOriginTypes,
            'allStages' => $allStages,
            'allSexes' => $allSexes,
            'allStates' => $allStates,
            'originAnimalSexesOptions' => $originAnimalSexesOptions,
            'originAnimalAgesOptions' => $originAnimalAgesOptions,
            'originHumanEthnicitiesOptions' => $originHumanEthnicitiesOptions,
            'originHumanOccupationsOptions' => $originHumanOccupationsOptions,
            'originHumanCountriesOptions' => $originHumanCountriesOptions,
            'allSubProjects' => $this->allSubProjects(),
            'canEdit' => $this->canEdit(),
        ]);
    }

    private function allSubProjects()
    {
        $base = $this->baseFilteredQuery(['subProjectFilter']);

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', ParasiteSamples::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('parasite_samples.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableOriginTypes(): array
    {
        $types = $this->baseFilteredQuery(['originTypeFilter'])
            ->select('parasites.parasites_origin_type')
            ->distinct()
            ->pluck('parasites.parasites_origin_type')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values();

        $map = [
            'HumanSamples' => ['human', 'Human'],
            'AnimalSamples' => ['animal', 'Animal'],
            'EnvironmentSamples' => ['environment', 'Environment'],
        ];

        $options = [];
        foreach ($types->unique() as $rawType) {
            $baseName = class_basename((string) $rawType);
            if (! isset($map[$baseName])) {
                continue;
            }
            [$key, $label] = $map[$baseName];
            $options[$key] = $label;
        }

        $order = ['human', 'animal', 'environment'];
        $sorted = [];
        foreach ($order as $k) {
            if (array_key_exists($k, $options)) {
                $sorted[$k] = $options[$k];
            }
        }

        return $sorted;
    }

    private function allStages(): Collection
    {
        return $this->baseFilteredQuery(['stageFilter'])
            ->whereNotNull('parasites.stage')
            ->select('parasites.stage')
            ->distinct()
            ->orderBy('parasites.stage')
            ->pluck('parasites.stage')
            ->filter()
            ->values();
    }

    private function allSexes(): Collection
    {
        return $this->baseFilteredQuery(['sexFilter'])
            ->whereNotNull('parasites.sex')
            ->select('parasites.sex')
            ->distinct()
            ->orderBy('parasites.sex')
            ->pluck('parasites.sex')
            ->filter()
            ->values();
    }

    private function allStates(): Collection
    {
        return $this->baseFilteredQuery(['stateFilter'])
            ->whereNotNull('parasites.state')
            ->select('parasites.state')
            ->distinct()
            ->orderBy('parasites.state')
            ->pluck('parasites.state')
            ->filter()
            ->values();
    }

    private function originAnimalSexesOptions(): Collection
    {
        if ($this->originTypeFilter !== 'animal') {
            return collect();
        }

        return $this->baseFilteredQuery(['originAnimalSexFilter'])
            ->where('parasites.parasites_origin_type', AnimalSamples::class)
            ->whereNotNull('origin_animals.sex')
            ->select('origin_animals.sex')
            ->distinct()
            ->orderBy('origin_animals.sex')
            ->pluck('origin_animals.sex')
            ->filter()
            ->values();
    }

    private function originAnimalAgesOptions(): Collection
    {
        if ($this->originTypeFilter !== 'animal') {
            return collect();
        }

        return $this->baseFilteredQuery(['originAnimalAgeFilter'])
            ->where('parasites.parasites_origin_type', AnimalSamples::class)
            ->whereNotNull('origin_animals.age')
            ->select('origin_animals.age')
            ->distinct()
            ->orderBy('origin_animals.age')
            ->pluck('origin_animals.age')
            ->filter()
            ->values();
    }

    private function originHumanEthnicitiesOptions(): Collection
    {
        if ($this->originTypeFilter !== 'human') {
            return collect();
        }

        return $this->baseFilteredQuery(['originHumanEthnicityFilter'])
            ->where('parasites.parasites_origin_type', HumanSamples::class)
            ->whereNotNull('origin_humans.ethnicity')
            ->select('origin_humans.ethnicity')
            ->distinct()
            ->orderBy('origin_humans.ethnicity')
            ->pluck('origin_humans.ethnicity')
            ->filter()
            ->values();
    }

    private function originHumanOccupationsOptions(): Collection
    {
        if ($this->originTypeFilter !== 'human') {
            return collect();
        }

        return $this->baseFilteredQuery(['originHumanOccupationFilter'])
            ->where('parasites.parasites_origin_type', HumanSamples::class)
            ->whereNotNull('origin_humans.occupation')
            ->select('origin_humans.occupation')
            ->distinct()
            ->orderBy('origin_humans.occupation')
            ->pluck('origin_humans.occupation')
            ->filter()
            ->values();
    }

    private function originHumanCountriesOptions(): Collection
    {
        if ($this->originTypeFilter !== 'human') {
            return collect();
        }

        return $this->baseFilteredQuery(['originHumanCountryFilter'])
            ->where('parasites.parasites_origin_type', HumanSamples::class)
            ->whereNotNull('origin_countries.name')
            ->select('origin_countries.name')
            ->distinct()
            ->orderBy('origin_countries.name')
            ->pluck('origin_countries.name')
            ->filter()
            ->values();
    }

    private function getAllOriginAnimalSpecies(): Collection
    {
        $query = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('animal_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'animal_samples.id')
                    ->where('parasites.parasites_origin_type', AnimalSamples::class);
            })
            ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->select('animal_species.name_common')
            ->distinct()
            ->orderBy('animal_species.name_common');

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        return $query->pluck('animal_species.name_common')->filter()->values();
    }

    public function render()
    {
        $viewData = $this->filteredData();

        return view('livewire.parasite-samples-dashboard', $viewData);
    }
}
