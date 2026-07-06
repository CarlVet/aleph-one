<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\SubProject;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnimalSamplesDashboard extends PlainComponent
{
    protected $projectId;

    public string $visualize_by = 'samples';

    public string $sampleVisibility = 'all';

    public string $timelineGranularity = 'monthly';

    public string $animal_species_filter = 'All';

    public string $sample_type_filter = 'All';

    public string $sampling_site_filter = 'All';

    public string $subProjectFilter = '';

    public ?string $startDate = null;

    public ?string $endDate = null;

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

    public function updated($propertyName): void
    {
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function resetFilters(): void
    {
        $this->reset([
            'visualize_by',
            'sampleVisibility',
            'timelineGranularity',
            'animal_species_filter',
            'sample_type_filter',
            'sampling_site_filter',
            'subProjectFilter',
            'startDate',
            'endDate',
        ]);

        // Restore defaults (reset() sets typed properties back to initial values defined on the class).
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function render()
    {
        return view('livewire.animal-samples-dashboard', array_merge(
            $this->dashboardPayload(),
            [
                'isGuestMode' => $this->isGuestMode(),
                'canEdit' => $this->canEdit(),
                'allAnimalSpecies' => $this->allAnimalSpecies(),
                'allSampleTypes' => $this->allSampleTypes(),
                'allSamplingSites' => $this->allSamplingSites(),
                'allSubProjects' => $this->allSubProjects(),
            ]
        ));
    }

    private function filteredQuery()
    {
        $query = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
            ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id');

        if ($this->isGuestMode()) {
            $query->where('animal_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->where('tubes.tubes_content_type', AnimalSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $projectId = $this->projectId;
            $query->where(function ($q) use ($projectId) {
                $q->where('animal_samples.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->where('tubes.tubes_content_type', AnimalSamples::class)
                            ->where('tubes.projects_id', $projectId);
                    });
            });

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->where('animal_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                        ->where('tubes.tubes_content_type', AnimalSamples::class);
                });
            }
        }

        if ($this->animal_species_filter !== '' && $this->animal_species_filter !== 'All') {
            $query->where('animal_species.name_common', $this->animal_species_filter);
        }
        if ($this->sample_type_filter !== '' && $this->sample_type_filter !== 'All') {
            $query->where('sample_types.name', $this->sample_type_filter);
        }
        if ($this->sampling_site_filter !== '' && $this->sampling_site_filter !== 'All') {
            $query->where('sampling_sites.name', $this->sampling_site_filter);
        }
        if ($this->subProjectFilter !== '' && $this->subProjectFilter !== 'All') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'animal_samples.id')
                    ->where('sub_project_assignments.assignable_type', AnimalSamples::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('animal_samples.date_collected', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('animal_samples.date_collected', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('animal_samples.date_collected', '<=', $this->endDate);
        }

        return $query;
    }

    private function dashboardPayload(): array
    {
        $base = $this->filteredQuery();

        $now = Carbon::now();
        $startOfYear = $now->copy()->startOfYear()->toDateString();
        $endOfYear = $now->copy()->endOfYear()->toDateString();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $total = (clone $base)->count('animal_samples.id');
        $processed = (clone $base)->where('animal_samples.processed', true)->count('animal_samples.id');
        $pending = (clone $base)->where('animal_samples.processed', false)->count('animal_samples.id');
        $uniqueAnimals = (clone $base)->distinct()->count('animals.id');
        $uniqueSpecies = (clone $base)->distinct()->count('animal_species.id');
        $uniqueSites = (clone $base)->distinct()->count('sampling_sites.id');
        $uniqueTypes = (clone $base)->distinct()->count('sample_types.id');
        $samplesThisYear = (clone $base)->whereBetween('animal_samples.date_collected', [$startOfYear, $endOfYear])->count('animal_samples.id');
        $samplesThisMonth = (clone $base)->whereBetween('animal_samples.date_collected', [$startOfMonth, $endOfMonth])->count('animal_samples.id');

        $processingRate = $total > 0 ? round(($processed / $total) * 100, 1) : 0;

        $animalSamplesBySpeciesCountExpr = $this->visualize_by === 'animals'
            ? 'COUNT(DISTINCT animals.id)'
            : 'COUNT(animal_samples.id)';

        $animalSamplesBySpecies = (clone $base)
            ->select('animal_species.name_common as k', DB::raw($animalSamplesBySpeciesCountExpr.' as c'))
            ->whereNotNull('animal_species.name_common')
            ->groupBy('animal_species.name_common')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $animalSamplesByTypeCountExpr = $this->visualize_by === 'animals'
            ? 'COUNT(DISTINCT animals.id)'
            : 'COUNT(animal_samples.id)';

        $animalSamplesByType = (clone $base)
            ->select('sample_types.name as k', DB::raw($animalSamplesByTypeCountExpr.' as c'))
            ->whereNotNull('sample_types.name')
            ->groupBy('sample_types.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $animalSamplesBySite = (clone $base)
            ->select('sampling_sites.name as k', DB::raw($animalSamplesByTypeCountExpr.' as c'))
            ->whereNotNull('sampling_sites.name')
            ->groupBy('sampling_sites.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $animalSamplesBySex = (clone $base)
            ->select('animals.sex as k', DB::raw($animalSamplesByTypeCountExpr.' as c'))
            ->whereNotNull('animals.sex')
            ->groupBy('animals.sex')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $descriptive_stats = [
            'total_samples' => $total,
            'processed_samples' => $processed,
            'pending_samples' => $pending,
            'unique_animals' => $uniqueAnimals,
            'unique_species' => $uniqueSpecies,
            'unique_sampling_sites' => $uniqueSites,
            'unique_sample_types' => $uniqueTypes,
            'samples_this_year' => $samplesThisYear,
            'samples_this_month' => $samplesThisMonth,
            'processing_rate' => $processingRate,
            'collection_timeline' => $this->timelineCounts(clone $base),
        ];

        $pieChartTabs = [
            ['key' => 'sample_type', 'label' => 'Sample Type', 'data' => $animalSamplesByType],
            ['key' => 'sex', 'label' => 'Sex', 'data' => $animalSamplesBySex],
        ];

        $barChartTabs = [
            ['key' => 'species', 'label' => 'Species', 'data' => $animalSamplesBySpecies],
            ['key' => 'sampling_site', 'label' => 'Sampling Site', 'data' => $animalSamplesBySite],
        ];

        $mapColorVariableOptions = [
            ['key' => 'species', 'label' => 'Species'],
            ['key' => 'type', 'label' => 'Sample Type'],
            ['key' => 'sex', 'label' => 'Sex'],
            ['key' => 'sampling_site', 'label' => 'Sampling Site'],
        ];

        return [
            'descriptive_stats' => $descriptive_stats,
            'animalSamplesBySpecies' => $animalSamplesBySpecies,
            'animalSamplesByType' => $animalSamplesByType,
            'animalSamplesBySite' => $animalSamplesBySite,
            'animalSamplesBySex' => $animalSamplesBySex,
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
            'mapPointsUrl' => route('animals.dashboard.map-points'),
            'modalTableUrls' => [
                'samplesModal' => route('animals.dashboard.modal.samples'),
                'animalsModal' => route('animals.dashboard.modal.animals'),
                'speciesModal' => route('animals.dashboard.modal.species'),
                'sitesModal' => route('animals.dashboard.modal.sites'),
                'typesModal' => route('animals.dashboard.modal.types'),
            ],
            'activeFilters' => [
                'visualize_by' => $this->visualize_by,
                'sampleVisibility' => $this->sampleVisibility,
                'animal_species_filter' => $this->animal_species_filter,
                'sample_type_filter' => $this->sample_type_filter,
                'sampling_site_filter' => $this->sampling_site_filter,
                'subProjectFilter' => $this->subProjectFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ],
        ];
    }

    private function allAnimalSpecies()
    {
        $base = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('animal_species.name_common')->pluck('animal_species.name_common')->filter()->values();
    }

    private function allSampleTypes()
    {
        $base = AnimalSamples::query()->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('sample_types.name')->pluck('sample_types.name')->filter()->values();
    }

    private function allSamplingSites()
    {
        $base = AnimalSamples::query()->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('sampling_sites.name')->pluck('sampling_sites.name')->filter()->values();
    }

    private function allSubProjects()
    {
        $base = AnimalSamples::query();
        $this->applyVisibilityScope($base);

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', AnimalSamples::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('animal_samples.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    private function applyVisibilityScope($query): void
    {
        if ($this->isGuestMode()) {
            $query->where('animal_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->where('tubes.tubes_content_type', AnimalSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $projectId = $this->projectId;
            $query->where(function ($q) use ($projectId) {
                $q->where('animal_samples.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->where('tubes.tubes_content_type', AnimalSamples::class)
                            ->where('tubes.projects_id', $projectId);
                    });
            });

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->where('animal_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                        ->where('tubes.tubes_content_type', AnimalSamples::class);
                });
            }
        }
    }

    private function timelineCounts($base): array
    {
        $driver = DB::getDriverName();
        $expr = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT(animal_samples.date_collected, '%Y')",
                'pgsql' => "to_char(animal_samples.date_collected, 'YYYY')",
                default => "strftime('%Y', animal_samples.date_collected)",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT(animal_samples.date_collected, '%Y-%m')",
            'pgsql' => "to_char(animal_samples.date_collected, 'YYYY-MM')",
            default => "strftime('%Y-%m', animal_samples.date_collected)",
        };

        $rows = $base
            ->select(DB::raw($expr.' as ym'), DB::raw('COUNT(animal_samples.id) as c'))
            ->whereNotNull('animal_samples.date_collected')
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->toArray();

        $timeline = [];
        if ($this->timelineGranularity === 'yearly') {
            for ($i = 9; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->format('Y');
                $timeline[$year] = (int) ($rows[$year] ?? 0);
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $key = $month->format('Y-m');
                $label = $month->format('M Y');
                $timeline[$label] = (int) ($rows[$key] ?? 0);
            }
        }

        return $timeline;
    }
}
