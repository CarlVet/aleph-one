<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\SubProject;
use App\Services\CulturesService;
use App\Services\PrimarySampleReachability;
use App\Support\CultureContentDetailsPresenter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class CulturesDashboard extends PlainComponent
{
    use WithPagination;

    protected $projectId;

    public string $sampleVisibility = 'all';

    public string $timelineGranularity = 'monthly';

    public $cultureTypeFilter = '';

    public $sourceTypeFilter = 'all';

    public string $tracePrimaryTypeFilter = 'all';

    public string $tracePrimaryAnimalSpeciesFilter = '';

    public string $tracePrimaryAnimalSexFilter = '';

    public string $tracePrimaryAnimalAgeFilter = '';

    public string $tracePrimaryHumanEthnicityFilter = '';

    public string $tracePrimaryHumanOccupationFilter = '';

    public string $tracePrimaryHumanCountryFilter = '';

    public string $tracePrimaryParasiteSpeciesFilter = '';

    public string $tracePrimaryCultureTypeFilter = '';

    public string $tracePrimaryCultureMediumFilter = '';

    public string $tracePrimaryNucleicTypeFilter = '';

    public ?int $tracePrimaryPoolMinNrPooled = null;

    public ?int $tracePrimaryPoolMaxNrPooled = null;

    public string $traceDeepPrimaryTypeFilter = 'all';

    public string $traceDeepAnimalSpeciesFilter = '';

    public string $traceDeepAnimalSexFilter = '';

    public string $traceDeepAnimalAgeFilter = '';

    public string $traceDeepHumanEthnicityFilter = '';

    public string $traceDeepHumanOccupationFilter = '';

    public string $traceDeepHumanCountryFilter = '';

    public $laboratoryFilter = '';

    public $mediumFilter = '';

    public string $subProjectFilter = '';

    public $pathogenFilter = '';

    public $startDate;

    public $endDate;

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
        $this->dispatch('filtersUpdated', data: $this->filteredData());
    }

    public function updatedSourceTypeFilter(): void
    {
        $this->reset([
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryNucleicTypeFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
        ]);

        $this->dispatch('filtersUpdated', data: $this->filteredData());
    }

    public function resetFilters()
    {
        $this->reset([
            'sampleVisibility',
            'timelineGranularity',
            'cultureTypeFilter',
            'sourceTypeFilter',
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryNucleicTypeFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
            'laboratoryFilter',
            'mediumFilter',
            'subProjectFilter',
            'pathogenFilter',
            'startDate',
            'endDate',
        ]);
        $this->resetPage();
        $this->dispatch('filtersUpdated', data: $this->filteredData());
    }

    /**
     * Build the base query with all necessary relationships and apply filters
     */
    private function buildFilteredQuery()
    {
        $query = Cultures::with([
            'cultures_content',
            'laboratories',
            'people',
            'projects',
            'tubes',
            'observations.photo',
            'observations.people',
            'latestObservation.photo',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'cultures.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(Cultures::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where(function ($w) {
                $w->where('cultures.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'cultures.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(Cultures::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'cultures.id')
                        ->whereIn('tubes.tubes_content_type', $this->typeVariants(Cultures::class));
                });
            }
        }

        // Apply filters
        $this->applyFilters($query);

        return $query;
    }

    /**
     * Apply all active filters to the query
     */
    private function applyFilters($query)
    {
        $normalizedSourceType = $this->normalizeFilterValue((string) $this->sourceTypeFilter);
        if ($this->sourceTypeFilter !== $normalizedSourceType) {
            $this->sourceTypeFilter = $normalizedSourceType;
        }

        $normalizedTracePrimaryType = $this->normalizeFilterValue((string) $this->tracePrimaryTypeFilter);
        if ($this->tracePrimaryTypeFilter !== $normalizedTracePrimaryType) {
            $this->tracePrimaryTypeFilter = $normalizedTracePrimaryType;
        }

        $normalizedTraceDeepType = $this->normalizeFilterValue((string) $this->traceDeepPrimaryTypeFilter);
        if ($this->traceDeepPrimaryTypeFilter !== $normalizedTraceDeepType) {
            $this->traceDeepPrimaryTypeFilter = $normalizedTraceDeepType;
        }

        // Apply culture type filter
        if ($this->cultureTypeFilter) {
            $query->where('type', $this->cultureTypeFilter);
        }
        if ($this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'cultures.id')
                    ->where('sub_project_assignments.assignable_type', Cultures::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }

        // Apply source type filter
        if ($this->sourceTypeFilter !== 'all') {
            $sourceType = match ($this->sourceTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'nucleic' => NucleicAcids::class,
                'pool' => Pools::class,
                default => null
            };

            if ($sourceType) {
                $query->whereIn('cultures_content_type', $this->typeVariants($sourceType));
            }
        }

        $hasDeepTrace = $this->traceDeepPrimaryTypeFilter !== 'all'
            || $this->traceDeepAnimalSpeciesFilter !== ''
            || $this->traceDeepAnimalSexFilter !== ''
            || $this->traceDeepAnimalAgeFilter !== ''
            || $this->traceDeepHumanEthnicityFilter !== ''
            || $this->traceDeepHumanOccupationFilter !== ''
            || $this->traceDeepHumanCountryFilter !== '';

        $hasAnyTrace = $this->tracePrimaryTypeFilter !== 'all'
            || $this->tracePrimaryAnimalSpeciesFilter !== ''
            || $this->tracePrimaryAnimalSexFilter !== ''
            || $this->tracePrimaryAnimalAgeFilter !== ''
            || $this->tracePrimaryHumanEthnicityFilter !== ''
            || $this->tracePrimaryHumanOccupationFilter !== ''
            || $this->tracePrimaryHumanCountryFilter !== ''
            || $this->tracePrimaryParasiteSpeciesFilter !== ''
            || $this->tracePrimaryCultureTypeFilter !== ''
            || $this->tracePrimaryCultureMediumFilter !== ''
            || $this->tracePrimaryNucleicTypeFilter !== ''
            || $this->tracePrimaryPoolMinNrPooled !== null
            || $this->tracePrimaryPoolMaxNrPooled !== null
            || $hasDeepTrace;

        if (
            $this->sourceTypeFilter === 'parasite'
            && in_array($this->tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
        ) {
            $originType = match ($this->tracePrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
            };

            $query->whereExists(function ($sub) use ($originType) {
                $sub->select(DB::raw(1))
                    ->from('parasite_samples')
                    ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                    ->whereColumn('parasite_samples.id', 'cultures.cultures_content_id')
                    ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                    ->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));

                if ($originType === AnimalSamples::class) {
                    $sub->leftJoin('animal_samples as trace_animal_samples', 'trace_animal_samples.id', '=', 'parasites.parasites_origin_id')
                        ->leftJoin('animals as trace_animals', 'trace_animals.id', '=', 'trace_animal_samples.animals_id')
                        ->leftJoin('animal_species as trace_animal_species', 'trace_animal_species.id', '=', 'trace_animals.animal_species_id');

                    if ($this->tracePrimaryAnimalSpeciesFilter !== '') {
                        $sub->where('trace_animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
                    }
                    if ($this->tracePrimaryAnimalSexFilter !== '') {
                        $sub->where('trace_animals.sex', $this->tracePrimaryAnimalSexFilter);
                    }
                    if ($this->tracePrimaryAnimalAgeFilter !== '') {
                        $sub->where('trace_animals.age', $this->tracePrimaryAnimalAgeFilter);
                    }
                }

                if ($originType === HumanSamples::class) {
                    $sub->leftJoin('human_samples as trace_human_samples', 'trace_human_samples.id', '=', 'parasites.parasites_origin_id')
                        ->leftJoin('humans as trace_humans', 'trace_humans.id', '=', 'trace_human_samples.humans_id')
                        ->leftJoin('countries as trace_countries', 'trace_countries.id', '=', 'trace_humans.countries_id');

                    if ($this->tracePrimaryHumanEthnicityFilter !== '') {
                        $sub->where('trace_humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
                    }
                    if ($this->tracePrimaryHumanOccupationFilter !== '') {
                        $sub->where('trace_humans.occupation', $this->tracePrimaryHumanOccupationFilter);
                    }
                    if ($this->tracePrimaryHumanCountryFilter !== '') {
                        $sub->where('trace_countries.name', $this->tracePrimaryHumanCountryFilter);
                    }
                }
            });
        } elseif ($hasAnyTrace) {
            $upstreamType = match ($this->tracePrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'nucleic' => NucleicAcids::class,
                'pool' => Pools::class,
                default => null,
            };

            if ($upstreamType) {
                $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

                $blocked = false;

                if ($upstreamSeedIds === []) {
                    $query->whereRaw('1 = 0');
                    $blocked = true;
                } else {
                    if ($hasDeepTrace && in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
                        $deepPrimaryType = match ($this->traceDeepPrimaryTypeFilter) {
                            'human' => HumanSamples::class,
                            'animal' => AnimalSamples::class,
                            'environment' => EnvironmentSamples::class,
                            default => null,
                        };

                        if ($deepPrimaryType) {
                            $deepPrimaryIds = $this->primarySampleIdsForDeepTracing($deepPrimaryType);
                            $reachability = app(PrimarySampleReachability::class);

                            $reachableUpstream = match ($upstreamType) {
                                ParasiteSamples::class => $reachability->parasiteSampleIdsFromSeed($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                                Cultures::class => $reachability->cultureIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                                NucleicAcids::class => $reachability->nucleicIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                                Pools::class => $reachability->poolIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                                default => [],
                            };

                            if ($reachableUpstream === []) {
                                $query->whereRaw('1 = 0');
                                $blocked = true;
                            } else {
                                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                                $upstreamSeedIds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));

                                if ($upstreamSeedIds === []) {
                                    $query->whereRaw('1 = 0');
                                    $blocked = true;
                                }
                            }
                        }
                    }

                    if (! $blocked) {
                        $reachability = app(PrimarySampleReachability::class);
                        $maxDepth = $upstreamType === Pools::class ? 10 : 6;

                        $ids = in_array($this->tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
                            ? $reachability->cultureIdsFromPrimary($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth)
                            : $reachability->cultureIdsFromSeed($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth);

                        if ($ids === []) {
                            $query->whereRaw('1 = 0');
                        } else {
                            $query->whereIn('cultures.id', $ids);
                        }
                    }
                }
            }
        }

        // Apply laboratory filter
        if ($this->laboratoryFilter) {
            $query->whereHas('laboratories', function ($q) {
                $q->where('name', $this->laboratoryFilter);
            });
        }

        // Apply medium filter
        if ($this->mediumFilter) {
            $query->where('medium', $this->mediumFilter);
        }

        // Apply date range filter
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_cultured', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_cultured', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_cultured', '<=', $this->endDate);
        }

        if ($this->pathogenFilter) {
            $this->applyPathogenTestedFilter($query, $this->pathogenFilter);
        }

        return $query;
    }

    private function applyPathogenTestedFilter($query, string $pathogenSpecies, ?array $positiveOutcomes = null): void
    {
        $cultureVariants = $this->typeVariants(Cultures::class);
        $nucleicVariants = $this->typeVariants(NucleicAcids::class);

        $query->where(function ($q) use ($cultureVariants, $nucleicVariants, $pathogenSpecies, $positiveOutcomes) {
            $q->whereExists(function ($sub) use ($cultureVariants, $pathogenSpecies, $positiveOutcomes) {
                $sub->select(DB::raw(1))
                    ->from('experiments')
                    ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
                    ->whereIn('experiments.experiments_content_type', $cultureVariants)
                    ->whereColumn('experiments.experiments_content_id', 'cultures.id')
                    ->where('pathogens.species', $pathogenSpecies);

                if ($positiveOutcomes !== null) {
                    $sub->whereIn('experiments.outcome_discrete', $positiveOutcomes);
                }
            })->orWhereExists(function ($sub) use ($cultureVariants, $nucleicVariants, $pathogenSpecies, $positiveOutcomes) {
                $sub->select(DB::raw(1))
                    ->from('experiments')
                    ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                    ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
                    ->whereIn('experiments.experiments_content_type', $nucleicVariants)
                    ->whereIn('nucleic_acids.nucleic_content_type', $cultureVariants)
                    ->whereColumn('nucleic_acids.nucleic_content_id', 'cultures.id')
                    ->where('pathogens.species', $pathogenSpecies);

                if ($positiveOutcomes !== null) {
                    $sub->whereIn('experiments.outcome_discrete', $positiveOutcomes);
                }
            });
        });
    }

    /**
     * @return array<int, string>
     */
    private function positiveExperimentOutcomes(): array
    {
        return ['Positive', 'Strong positive'];
    }

    private function averageDaysOnCultureFromQuery($query): ?float
    {
        $driver = DB::getDriverName();
        $today = Carbon::now()->toDateString();

        $endDateExpr = match ($driver) {
            'mysql' => "IF(cultures.is_discarded, cultures.date_discarded, '{$today}')",
            'pgsql' => "CASE WHEN cultures.is_discarded THEN cultures.date_discarded ELSE DATE '{$today}' END",
            default => "CASE WHEN cultures.is_discarded THEN cultures.date_discarded ELSE '{$today}' END",
        };

        $daysExpr = match ($driver) {
            'mysql' => "DATEDIFF({$endDateExpr}, cultures.date_cultured)",
            'pgsql' => "({$endDateExpr} - cultures.date_cultured)",
            default => "julianday({$endDateExpr}) - julianday(cultures.date_cultured)",
        };

        $average = (clone $query)
            ->whereNotNull('date_cultured')
            ->selectRaw("AVG({$daysExpr}) as avg_days")
            ->value('avg_days');

        return $average !== null ? round((float) $average, 1) : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function confirmedPathogenRowsFromQuery($query, int $limit = 500)
    {
        $cultureVariants = $this->typeVariants(Cultures::class);
        $nucleicVariants = $this->typeVariants(NucleicAcids::class);
        $positiveOutcomes = $this->positiveExperimentOutcomes();
        $cultureIds = (clone $query)->select('cultures.id');

        $directRows = DB::table('experiments')
            ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
            ->join('cultures', function ($join) use ($cultureVariants) {
                $join->on('cultures.id', '=', 'experiments.experiments_content_id')
                    ->whereIn('experiments.experiments_content_type', $cultureVariants);
            })
            ->whereIn('cultures.id', $cultureIds)
            ->whereIn('experiments.outcome_discrete', $positiveOutcomes)
            ->select([
                'cultures.code as culture_code',
                'pathogens.species as pathogen',
                'experiments.outcome_discrete as outcome',
                'experiments.date_tested as date_tested',
                DB::raw("'Culture tube' as test_source"),
            ]);

        $nucleicRows = DB::table('experiments')
            ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
            ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
            ->join('cultures', function ($join) use ($cultureVariants) {
                $join->on('cultures.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $cultureVariants);
            })
            ->whereIn('experiments.experiments_content_type', $nucleicVariants)
            ->whereIn('cultures.id', $cultureIds)
            ->whereIn('experiments.outcome_discrete', $positiveOutcomes)
            ->select([
                'cultures.code as culture_code',
                'pathogens.species as pathogen',
                'experiments.outcome_discrete as outcome',
                'experiments.date_tested as date_tested',
                DB::raw("'Nucleic acid' as test_source"),
            ]);

        return $directRows
            ->unionAll($nucleicRows)
            ->orderByDesc('date_tested')
            ->limit($limit)
            ->get();
    }

    private function countConfirmedPathogenResults($query): int
    {
        $cultureVariants = $this->typeVariants(Cultures::class);
        $nucleicVariants = $this->typeVariants(NucleicAcids::class);
        $positiveOutcomes = $this->positiveExperimentOutcomes();
        $cultureIds = (clone $query)->select('cultures.id');

        $directCount = DB::table('experiments')
            ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
            ->whereIn('experiments.experiments_content_type', $cultureVariants)
            ->whereIn('experiments.experiments_content_id', $cultureIds)
            ->whereIn('experiments.outcome_discrete', $positiveOutcomes)
            ->count();

        $nucleicCount = DB::table('experiments')
            ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
            ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
            ->whereIn('experiments.experiments_content_type', $nucleicVariants)
            ->whereIn('nucleic_acids.nucleic_content_type', $cultureVariants)
            ->whereIn('nucleic_acids.nucleic_content_id', $cultureIds)
            ->whereIn('experiments.outcome_discrete', $positiveOutcomes)
            ->count();

        return $directCount + $nucleicCount;
    }

    private function getAllPathogensForFilter()
    {
        $prev = $this->pathogenFilter;
        $this->pathogenFilter = '';

        $cultureIds = $this->buildFilteredQuery()->select('cultures.id');
        $cultureVariants = $this->typeVariants(Cultures::class);
        $nucleicVariants = $this->typeVariants(NucleicAcids::class);

        $directPathogens = DB::table('experiments')
            ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
            ->whereIn('experiments.experiments_content_type', $cultureVariants)
            ->whereIn('experiments.experiments_content_id', $cultureIds)
            ->distinct()
            ->pluck('pathogens.species');

        $nucleicPathogens = DB::table('experiments')
            ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
            ->join('pathogens', 'pathogens.id', '=', 'experiments.pathogens_id')
            ->whereIn('experiments.experiments_content_type', $nucleicVariants)
            ->whereIn('nucleic_acids.nucleic_content_type', $cultureVariants)
            ->whereIn('nucleic_acids.nucleic_content_id', $cultureIds)
            ->distinct()
            ->pluck('pathogens.species');

        $this->pathogenFilter = $prev;

        $pathogens = $directPathogens
            ->merge($nucleicPathogens)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($this->pathogenFilter && ! $pathogens->contains($this->pathogenFilter)) {
            $pathogens->prepend($this->pathogenFilter);
        }

        return $pathogens;
    }

    /**
     * @param  class-string  $upstreamType
     * @return array<int,int>
     */
    private function seedIdsForTraceUpstream(string $upstreamType): array
    {
        if (in_array($upstreamType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
            return $this->primarySampleIdsForTracing($upstreamType);
        }

        if ($upstreamType === ParasiteSamples::class) {
            $q = ParasiteSamples::query()
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                ->select('parasite_samples.id');

            if ($this->tracePrimaryParasiteSpeciesFilter !== '') {
                $q->where('parasite_species.name_scientific', $this->tracePrimaryParasiteSpeciesFilter);
            }

            $this->applyVisibilityToSeedQuery($q, ParasiteSamples::class);

            return $q->distinct()->limit(5000)->pluck('parasite_samples.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Cultures::class) {
            $q = Cultures::query()->select('cultures.id');

            if ($this->tracePrimaryCultureTypeFilter !== '') {
                $q->where('cultures.type', $this->tracePrimaryCultureTypeFilter);
            }
            if ($this->tracePrimaryCultureMediumFilter !== '') {
                $q->where('cultures.medium', $this->tracePrimaryCultureMediumFilter);
            }

            $this->applyVisibilityToSeedQuery($q, Cultures::class);

            return $q->distinct()->limit(5000)->pluck('cultures.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === NucleicAcids::class) {
            $q = NucleicAcids::query()->select('nucleic_acids.id');

            if ($this->tracePrimaryNucleicTypeFilter !== '') {
                $q->where('nucleic_acids.type', $this->tracePrimaryNucleicTypeFilter);
            }

            $this->applyVisibilityToSeedQuery($q, NucleicAcids::class);

            return $q->distinct()->limit(5000)->pluck('nucleic_acids.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Pools::class) {
            $q = Pools::query()->select('pools.id');

            if ($this->tracePrimaryPoolMinNrPooled !== null) {
                $q->where('pools.nr_pooled', '>=', $this->tracePrimaryPoolMinNrPooled);
            }
            if ($this->tracePrimaryPoolMaxNrPooled !== null) {
                $q->where('pools.nr_pooled', '<=', $this->tracePrimaryPoolMaxNrPooled);
            }

            $this->applyVisibilityToSeedQuery($q, Pools::class);

            return $q->distinct()->limit(5000)->pluck('pools.id')->map(fn ($v) => (int) $v)->all();
        }

        return [];
    }

    private function applyVisibilityToSeedQuery($q, string $type): void
    {
        $table = (new $type)->getTable();

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) use ($table, $type) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                    ->where('tubes.is_private', false);
            });

            return;
        }

        if ($this->projectId) {
            $q->where(function ($w) use ($table, $type) {
                $w->where($table.'.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) use ($table, $type) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }
    }

    private function typeVariants(string $type): array
    {
        $base = class_basename($type);

        return array_values(array_unique([
            $type,
            "App\\Models\\{$base}",
            "AppModels{$base}",
            $base,
        ]));
    }

    private function normalizedBasename(string $rawType): string
    {
        $base = class_basename($rawType);

        if (str_starts_with($base, 'AppModels')) {
            return substr($base, strlen('AppModels'));
        }

        return $base;
    }

    private function normalizeFilterValue(string $value): string
    {
        $trimmed = strtolower(trim($value));

        return match ($trimmed) {
            'humans', 'human' => 'human',
            'animals', 'animal' => 'animal',
            'environments', 'environment', 'environmental' => 'environment',
            'parasites', 'parasite' => 'parasite',
            'cultures', 'culture' => 'culture',
            'nucleics', 'nucleic', 'nucleicacids', 'nucleic_acids' => 'nucleic',
            'pools', 'pool' => 'pool',
            default => $trimmed === '' ? 'all' : $trimmed,
        };
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForDeepTracing(string $primaryType): array
    {
        $table = (new $primaryType)->getTable();
        $q = $primaryType::query()->select($table.'.id');

        if ($primaryType === AnimalSamples::class) {
            $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

            if ($this->traceDeepAnimalSpeciesFilter !== '') {
                $q->where('animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
            }
            if ($this->traceDeepAnimalSexFilter !== '') {
                $q->where('animals.sex', $this->traceDeepAnimalSexFilter);
            }
            if ($this->traceDeepAnimalAgeFilter !== '') {
                $q->where('animals.age', $this->traceDeepAnimalAgeFilter);
            }
        }

        if ($primaryType === HumanSamples::class) {
            $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

            if ($this->traceDeepHumanEthnicityFilter !== '') {
                $q->where('humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
            }
            if ($this->traceDeepHumanOccupationFilter !== '') {
                $q->where('humans.occupation', $this->traceDeepHumanOccupationFilter);
            }
            if ($this->traceDeepHumanCountryFilter !== '') {
                $q->where('countries.name', $this->traceDeepHumanCountryFilter);
            }
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) use ($primaryType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) use ($primaryType, $table) {
                $w->where($table.'.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) use ($primaryType, $table) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        return $q->limit(8000)->pluck($table.'.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForTracing(string $primaryType): array
    {
        $table = (new $primaryType)->getTable();
        $q = $primaryType::query()->select($table.'.id');

        if ($primaryType === AnimalSamples::class) {
            $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

            if ($this->tracePrimaryAnimalSpeciesFilter !== '') {
                $q->where('animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
            }

            if ($this->tracePrimaryAnimalSexFilter !== '') {
                $q->where('animals.sex', $this->tracePrimaryAnimalSexFilter);
            }

            if ($this->tracePrimaryAnimalAgeFilter !== '') {
                $q->where('animals.age', $this->tracePrimaryAnimalAgeFilter);
            }
        }

        if ($primaryType === HumanSamples::class) {
            $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

            if ($this->tracePrimaryHumanEthnicityFilter !== '') {
                $q->where('humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
            }

            if ($this->tracePrimaryHumanOccupationFilter !== '') {
                $q->where('humans.occupation', $this->tracePrimaryHumanOccupationFilter);
            }

            if ($this->tracePrimaryHumanCountryFilter !== '') {
                $q->where('countries.name', $this->tracePrimaryHumanCountryFilter);
            }
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) use ($primaryType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) use ($primaryType, $table) {
                $w->where($table.'.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) use ($primaryType, $table) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        return $q->limit(8000)->pluck($table.'.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @return array<int,int> cultures.id
     */
    private function baseCultureIdsForTraceOptions(array $overrides): array
    {
        $original = [];
        foreach ($overrides as $key => $value) {
            $original[$key] = $this->{$key};
            $this->{$key} = $value;
        }

        try {
            return $this->buildFilteredQuery()
                ->select('cultures.id')
                ->distinct()
                ->limit(5000)
                ->pluck('cultures.id')
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all();
        } finally {
            foreach ($original as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return array<int,int> cultures.id
     */
    private function baseCultureIdsForDeepOptions(array $overrides): array
    {
        return $this->baseCultureIdsForTraceOptions($overrides);
    }

    /**
     * @param  class-string  $upstreamType
     * @param  class-string  $deepPrimaryType
     * @param  array<int,int>  $deepPrimaryIds
     * @return array<int,int>
     */
    private function reachableUpstreamIdsFromDeepPrimary(string $upstreamType, string $deepPrimaryType, array $deepPrimaryIds): array
    {
        $reachability = app(PrimarySampleReachability::class);

        return match ($upstreamType) {
            ParasiteSamples::class => $reachability->parasiteSampleIdsFromSeed($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            Cultures::class => $reachability->cultureIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            NucleicAcids::class => $reachability->nucleicIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            Pools::class => $reachability->poolIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            default => [],
        };
    }

    /**
     * @param  class-string  $upstreamType
     * @param  array<int,int>  $upstreamSeedIds
     * @return array<int,int> cultures.id
     */
    private function cultureIdsFromUpstreamSeeds(string $upstreamType, array $upstreamSeedIds): array
    {
        $reachability = app(PrimarySampleReachability::class);
        $maxDepth = $upstreamType === Pools::class ? 10 : 6;

        return $reachability->cultureIdsFromSeed($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth);
    }

    private function traceDeepAnimalSpeciesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForDeepOptions(['traceDeepAnimalSpeciesFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common');

        if ($this->traceDeepAnimalSexFilter !== '') {
            $q->where('animals.sex', $this->traceDeepAnimalSexFilter);
        }
        if ($this->traceDeepAnimalAgeFilter !== '') {
            $q->where('animals.age', $this->traceDeepAnimalAgeFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('animal_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('animal_species.name_common')->pluck('animal_species.name_common')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepAnimalSpeciesFilter;
            $this->traceDeepAnimalSpeciesFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(AnimalSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, AnimalSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->cultureIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepAnimalSpeciesFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepAnimalSpeciesFilter !== '' && ! $col->contains($this->traceDeepAnimalSpeciesFilter)) {
            $col = collect([$this->traceDeepAnimalSpeciesFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepAnimalSexesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForDeepOptions(['traceDeepAnimalSexFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex');

        if ($this->traceDeepAnimalSpeciesFilter !== '') {
            $q->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                ->where('animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
        }
        if ($this->traceDeepAnimalAgeFilter !== '') {
            $q->where('animals.age', $this->traceDeepAnimalAgeFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('animal_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('animals.sex')->pluck('animals.sex')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepAnimalSexFilter;
            $this->traceDeepAnimalSexFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(AnimalSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, AnimalSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->cultureIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepAnimalSexFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepAnimalSexFilter !== '' && ! $col->contains($this->traceDeepAnimalSexFilter)) {
            $col = collect([$this->traceDeepAnimalSexFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepAnimalAgesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForDeepOptions(['traceDeepAnimalAgeFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age');

        if ($this->traceDeepAnimalSpeciesFilter !== '') {
            $q->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                ->where('animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
        }
        if ($this->traceDeepAnimalSexFilter !== '') {
            $q->where('animals.sex', $this->traceDeepAnimalSexFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('animal_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('animals.age')->pluck('animals.age')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepAnimalAgeFilter;
            $this->traceDeepAnimalAgeFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(AnimalSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, AnimalSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->cultureIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepAnimalAgeFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepAnimalAgeFilter !== '' && ! $col->contains($this->traceDeepAnimalAgeFilter)) {
            $col = collect([$this->traceDeepAnimalAgeFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepHumanEthnicitiesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'human' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForDeepOptions(['traceDeepHumanEthnicityFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->select('humans.ethnicity');

        if ($this->traceDeepHumanOccupationFilter !== '') {
            $q->where('humans.occupation', $this->traceDeepHumanOccupationFilter);
        }
        if ($this->traceDeepHumanCountryFilter !== '') {
            $q->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
                ->where('countries.name', $this->traceDeepHumanCountryFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('human_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('humans.ethnicity')->pluck('humans.ethnicity')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepHumanEthnicityFilter;
            $this->traceDeepHumanEthnicityFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(HumanSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, HumanSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->cultureIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepHumanEthnicityFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepHumanEthnicityFilter !== '' && ! $col->contains($this->traceDeepHumanEthnicityFilter)) {
            $col = collect([$this->traceDeepHumanEthnicityFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepHumanOccupationsOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'human' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForDeepOptions(['traceDeepHumanOccupationFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->select('humans.occupation');

        if ($this->traceDeepHumanEthnicityFilter !== '') {
            $q->where('humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
        }
        if ($this->traceDeepHumanCountryFilter !== '') {
            $q->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
                ->where('countries.name', $this->traceDeepHumanCountryFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('human_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('humans.occupation')->pluck('humans.occupation')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepHumanOccupationFilter;
            $this->traceDeepHumanOccupationFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(HumanSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, HumanSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->cultureIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepHumanOccupationFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepHumanOccupationFilter !== '' && ! $col->contains($this->traceDeepHumanOccupationFilter)) {
            $col = collect([$this->traceDeepHumanOccupationFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepHumanCountriesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'human' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForDeepOptions(['traceDeepHumanCountryFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->select('countries.name');

        if ($this->traceDeepHumanEthnicityFilter !== '') {
            $q->where('humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
        }
        if ($this->traceDeepHumanOccupationFilter !== '') {
            $q->where('humans.occupation', $this->traceDeepHumanOccupationFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('human_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('countries.name')->pluck('countries.name')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepHumanCountryFilter;
            $this->traceDeepHumanCountryFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(HumanSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, HumanSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->cultureIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepHumanCountryFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepHumanCountryFilter !== '' && ! $col->contains($this->traceDeepHumanCountryFilter)) {
            $col = collect([$this->traceDeepHumanCountryFilter])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryAnimalSpeciesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForTraceOptions(['tracePrimaryAnimalSpeciesFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $reachability = app(PrimarySampleReachability::class);

        $species = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->distinct()
            ->orderBy('animal_species.name_common')
            ->pluck('animal_species.name_common')
            ->filter()
            ->values();

        $out = [];
        foreach ($species as $name) {
            $prev = $this->tracePrimaryAnimalSpeciesFilter;
            $this->tracePrimaryAnimalSpeciesFilter = (string) $name;

            try {
                $primaryIds = $this->primarySampleIdsForTracing(AnimalSamples::class);
                $ids = $reachability->cultureIdsFromPrimary(AnimalSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $name;
                        break;
                    }
                }
            } finally {
                $this->tracePrimaryAnimalSpeciesFilter = $prev;
            }
        }

        return collect($out)->values();
    }

    private function tracePrimaryAnimalSexesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForTraceOptions(['tracePrimaryAnimalSexFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $reachability = app(PrimarySampleReachability::class);

        $sexes = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->distinct()
            ->orderBy('animals.sex')
            ->pluck('animals.sex')
            ->filter()
            ->values();

        $out = [];
        foreach ($sexes as $sex) {
            $prev = $this->tracePrimaryAnimalSexFilter;
            $this->tracePrimaryAnimalSexFilter = (string) $sex;

            try {
                $primaryIds = $this->primarySampleIdsForTracing(AnimalSamples::class);
                $ids = $reachability->cultureIdsFromPrimary(AnimalSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $sex;
                        break;
                    }
                }
            } finally {
                $this->tracePrimaryAnimalSexFilter = $prev;
            }
        }

        return collect($out)->values();
    }

    private function tracePrimaryAnimalAgesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForTraceOptions(['tracePrimaryAnimalAgeFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $reachability = app(PrimarySampleReachability::class);

        $ages = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->distinct()
            ->orderBy('animals.age')
            ->pluck('animals.age')
            ->filter()
            ->values();

        $out = [];
        foreach ($ages as $age) {
            $prev = $this->tracePrimaryAnimalAgeFilter;
            $this->tracePrimaryAnimalAgeFilter = (string) $age;

            try {
                $primaryIds = $this->primarySampleIdsForTracing(AnimalSamples::class);
                $ids = $reachability->cultureIdsFromPrimary(AnimalSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $age;
                        break;
                    }
                }
            } finally {
                $this->tracePrimaryAnimalAgeFilter = $prev;
            }
        }

        return collect($out)->values();
    }

    private function tracePrimaryHumanEthnicitiesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForTraceOptions(['tracePrimaryHumanEthnicityFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $reachability = app(PrimarySampleReachability::class);

        $ethnicities = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->distinct()
            ->orderBy('humans.ethnicity')
            ->pluck('humans.ethnicity')
            ->filter()
            ->values();

        $out = [];
        foreach ($ethnicities as $ethnicity) {
            $prev = $this->tracePrimaryHumanEthnicityFilter;
            $this->tracePrimaryHumanEthnicityFilter = (string) $ethnicity;

            try {
                $primaryIds = $this->primarySampleIdsForTracing(HumanSamples::class);
                $ids = $reachability->cultureIdsFromPrimary(HumanSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $ethnicity;
                        break;
                    }
                }
            } finally {
                $this->tracePrimaryHumanEthnicityFilter = $prev;
            }
        }

        return collect($out)->values();
    }

    private function tracePrimaryHumanOccupationsOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForTraceOptions(['tracePrimaryHumanOccupationFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $reachability = app(PrimarySampleReachability::class);

        $occupations = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->distinct()
            ->orderBy('humans.occupation')
            ->pluck('humans.occupation')
            ->filter()
            ->values();

        $out = [];
        foreach ($occupations as $occupation) {
            $prev = $this->tracePrimaryHumanOccupationFilter;
            $this->tracePrimaryHumanOccupationFilter = (string) $occupation;

            try {
                $primaryIds = $this->primarySampleIdsForTracing(HumanSamples::class);
                $ids = $reachability->cultureIdsFromPrimary(HumanSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $occupation;
                        break;
                    }
                }
            } finally {
                $this->tracePrimaryHumanOccupationFilter = $prev;
            }
        }

        return collect($out)->values();
    }

    private function tracePrimaryHumanCountriesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $baseIds = $this->baseCultureIdsForTraceOptions(['tracePrimaryHumanCountryFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $reachability = app(PrimarySampleReachability::class);

        $countries = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->distinct()
            ->orderBy('countries.name')
            ->pluck('countries.name')
            ->filter()
            ->values();

        $out = [];
        foreach ($countries as $country) {
            $prev = $this->tracePrimaryHumanCountryFilter;
            $this->tracePrimaryHumanCountryFilter = (string) $country;

            try {
                $primaryIds = $this->primarySampleIdsForTracing(HumanSamples::class);
                $ids = $reachability->cultureIdsFromPrimary(HumanSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $country;
                        break;
                    }
                }
            } finally {
                $this->tracePrimaryHumanCountryFilter = $prev;
            }
        }

        return collect($out)->values();
    }

    /**
     * Calculate descriptive statistics from filtered cultures
     */
    private function calculateStatistics($cultures)
    {
        $stats = [
            'total_samples' => $cultures->count(),
            'human_samples' => $cultures->where('cultures_content_type', HumanSamples::class)->count(),
            'animal_samples' => $cultures->where('cultures_content_type', AnimalSamples::class)->count(),
            'environment_samples' => $cultures->where('cultures_content_type', EnvironmentSamples::class)->count(),
            'parasite_samples' => $cultures->where('cultures_content_type', ParasiteSamples::class)->count(),
            'nucleic_samples' => $cultures->where('cultures_content_type', NucleicAcids::class)->count(),
            'pool_samples' => $cultures->where('cultures_content_type', Pools::class)->count(),
            'samples_this_year' => $cultures->filter(function ($sample) {
                return $sample->date_cultured &&
                    Carbon::parse($sample->date_cultured)->year === Carbon::now()->year;
            })->count(),
            'samples_this_month' => $cultures->filter(function ($sample) {
                return $sample->date_cultured &&
                    Carbon::parse($sample->date_cultured)->isCurrentMonth();
            })->count(),
        ];

        // Generate timeline data
        $stats['culturing_timeline'] = $this->generateTimelineData($cultures);

        return $stats;
    }

    /**
     * Generate timeline data for the last 12 months
     */
    private function generateTimelineData($cultures)
    {
        $timeline = [];
        if ($this->timelineGranularity === 'yearly') {
            for ($i = 9; $i >= 0; $i--) {
                $year = (int) Carbon::now()->subYears($i)->format('Y');
                $count = $cultures->filter(function ($culture) use ($year) {
                    return $culture->date_cultured &&
                        (int) Carbon::parse($culture->date_cultured)->format('Y') === $year;
                })->count();

                $timeline[(string) $year] = $count;
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthLabel = $month->format('M Y');

                $count = $cultures->filter(function ($culture) use ($month) {
                    return $culture->date_cultured &&
                        Carbon::parse($culture->date_cultured)->format('Y-m') === $month->format('Y-m');
                })->count();

                $timeline[$monthLabel] = $count;
            }
        }

        return $timeline;
    }

    /**
     * Get all available culture types for filter dropdown
     */
    private function getAllCultureTypes()
    {
        $query = Cultures::select('type');

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where('projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereHas('tubes');
            }
        }

        return $query->distinct()->pluck('type')->filter()->sort()->values();
    }

    /**
     * Get all available laboratories for filter dropdown
     */
    private function getAllLaboratories()
    {
        $query = Cultures::query()
            ->leftJoin('laboratories', 'cultures.laboratories_id', '=', 'laboratories.id')
            ->whereNotNull('laboratories.name')
            ->select('laboratories.name');

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where('projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereHas('tubes');
            }
        }

        return $query
            ->distinct()
            ->orderBy('laboratories.name')
            ->pluck('laboratories.name')
            ->filter()
            ->values();
    }

    /**
     * Get all available mediums for filter dropdown
     */
    private function getAllMediums()
    {
        $query = Cultures::select('medium');

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where('projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->whereHas('tubes');
            }
        }

        return $query->distinct()->pluck('medium')->filter()->sort()->values();
    }

    /**
     * Extract coordinates from culture based on its content
     */
    private function extractCoordinates($culture)
    {
        if (! $culture->cultures_content) {
            return [null, null];
        }

        $content = $culture->cultures_content;
        $contentType = class_basename($content);

        // Primary samples have direct coordinates
        if (in_array($contentType, ['AnimalSamples', 'HumanSamples', 'EnvironmentSamples'])) {
            if ($content->latitude && $content->longitude) {
                return [$content->latitude, $content->longitude];
            } elseif ($content->sampling_sites) {
                return [$content->sampling_sites->latitude ?? null, $content->sampling_sites->longitude ?? null];
            }
        }

        // Parasite samples get location from parasites_origin
        if ($contentType === 'ParasiteSamples') {
            try {
                if (method_exists($content, 'parasites') && $content->parasites && $content->parasites->parasites_origin) {
                    $origin = $content->parasites->parasites_origin;
                    if ($origin->latitude && $origin->longitude) {
                        return [$origin->latitude, $origin->longitude];
                    } elseif ($origin->sampling_sites) {
                        return [$origin->sampling_sites->latitude ?? null, $origin->sampling_sites->longitude ?? null];
                    }
                }
            } catch (\Exception $e) {
                // Relationship not loaded or doesn't exist
                return [null, null];
            }

            return [null, null];
        }

        // Nucleic acids get location from their content
        if ($contentType === 'NucleicAcids') {
            return $this->extractCoordinatesFromNucleicAcids($content);
        }

        // Pools get location from their content
        if ($contentType === 'Pools') {
            return $this->extractCoordinatesFromPools($content);
        }

        return [null, null];
    }

    /**
     * Extract coordinates from NucleicAcids content
     */
    private function extractCoordinatesFromNucleicAcids($nucleicAcids)
    {
        try {
            if (! method_exists($nucleicAcids, 'nucleic_content') || ! $nucleicAcids->nucleic_content) {
                return [null, null];
            }

            $nucleicContent = $nucleicAcids->nucleic_content;
            $contentType = class_basename($nucleicContent);

            // Primary samples have direct coordinates
            if (in_array($contentType, ['AnimalSamples', 'HumanSamples', 'EnvironmentSamples'])) {
                if ($nucleicContent->latitude && $nucleicContent->longitude) {
                    return [$nucleicContent->latitude, $nucleicContent->longitude];
                } elseif ($nucleicContent->sampling_sites) {
                    return [$nucleicContent->sampling_sites->latitude ?? null, $nucleicContent->sampling_sites->longitude ?? null];
                }
            }

            // Parasite samples get location from parasites_origin
            if ($contentType === 'ParasiteSamples') {
                try {
                    if (method_exists($nucleicContent, 'parasites') && $nucleicContent->parasites && $nucleicContent->parasites->parasites_origin) {
                        $origin = $nucleicContent->parasites->parasites_origin;
                        if ($origin->latitude && $origin->longitude) {
                            return [$origin->latitude, $origin->longitude];
                        } elseif ($origin->sampling_sites) {
                            return [$origin->sampling_sites->latitude ?? null, $origin->sampling_sites->longitude ?? null];
                        }
                    }
                } catch (\Exception $e) {
                    // Relationship not loaded or doesn't exist
                    return [null, null];
                }

                return [null, null];
            }

            return [null, null];
        } catch (\Exception $e) {
            // Lazy loading disabled, skip this relationship
            return [null, null];
        }
    }

    /**
     * Extract coordinates from Pools content
     */
    private function extractCoordinatesFromPools($pools)
    {
        try {
            if (! method_exists($pools, 'pool_contents') || ! $pools->pool_contents) {
                return [null, null];
            }

            // Get the first sample from pool_contents
            $firstPoolContent = $pools->pool_contents->first();
            if (! $firstPoolContent || ! $firstPoolContent->samples) {
                return [null, null];
            }

            $sample = $firstPoolContent->samples;
            $sampleType = class_basename($sample);

            // Primary samples have direct coordinates
            if (in_array($sampleType, ['AnimalSamples', 'HumanSamples', 'EnvironmentSamples'])) {
                if ($sample->latitude && $sample->longitude) {
                    return [$sample->latitude, $sample->longitude];
                } elseif ($sample->sampling_sites) {
                    return [$sample->sampling_sites->latitude ?? null, $sample->sampling_sites->longitude ?? null];
                }
            }

            // Parasite samples get location from parasites_origin
            if ($sampleType === 'ParasiteSamples') {
                try {
                    if (method_exists($sample, 'parasites') && $sample->parasites && $sample->parasites->parasites_origin) {
                        $origin = $sample->parasites->parasites_origin;
                        if ($origin->latitude && $origin->longitude) {
                            return [$origin->latitude, $origin->longitude];
                        } elseif ($origin->sampling_sites) {
                            return [$origin->sampling_sites->latitude ?? null, $origin->sampling_sites->longitude ?? null];
                        }
                    }
                } catch (\Exception $e) {
                    // Relationship not loaded or doesn't exist
                    return [null, null];
                }

                return [null, null];
            }

            return [null, null];
        } catch (\Exception $e) {
            // Lazy loading disabled, skip this relationship
            return [null, null];
        }
    }

    /**
     * Get cultures with properly loaded relationships for coordinate extraction
     */
    private function getCulturesWithCoordinates(int $maxPoints = 2500)
    {
        // Build base query with filters
        $baseQuery = $this->buildFilteredQuery();

        // Get cultures with different relationship loading strategies
        $cultures = collect();
        $perBucket = max(150, (int) ceil($maxPoints / 4));

        // 1. Human primary samples
        $humanCultures = $baseQuery->clone()
            ->where('cultures_content_type', HumanSamples::class)
            ->with(['cultures_content',
                'cultures_content.humans.countries',
                'cultures_content.sampling_sites',
                'laboratories',
                'people'])
            ->orderByDesc('cultures.id')
            ->limit($perBucket)
            ->get();
        $cultures = $cultures->merge($humanCultures);

        // 2. Animal primary samples
        $animalCultures = $baseQuery->clone()
            ->where('cultures_content_type', AnimalSamples::class)
            ->with(['cultures_content',
                'cultures_content.animals.animal_species',
                'cultures_content.sampling_sites',
                'laboratories',
                'people'])
            ->orderByDesc('cultures.id')
            ->limit($perBucket)
            ->get();
        $cultures = $cultures->merge($animalCultures);

        // 3. Environment primary samples
        $environmentCultures = $baseQuery->clone()
            ->where('cultures_content_type', EnvironmentSamples::class)
            ->with(['cultures_content',
                'cultures_content.sampling_sites',
                'laboratories',
                'people'])
            ->orderByDesc('cultures.id')
            ->limit($perBucket)
            ->get();
        $cultures = $cultures->merge($environmentCultures);

        // 4. Parasite samples with parasites relationship
        $parasiteCultures = $baseQuery->clone()
            ->where('cultures_content_type', ParasiteSamples::class)
            ->with([
                'cultures_content',
                'cultures_content.parasites.parasite_species',
                'cultures_content.parasites.parasites_origin',
                'cultures_content.parasites.parasites_origin.sampling_sites',
                'laboratories', 'people',
            ])
            ->orderByDesc('cultures.id')
            ->limit($perBucket)
            ->get();
        $cultures = $cultures->merge($parasiteCultures);

        // 5. Nucleic acids with their content relationships
        $nucleicCultures = $baseQuery->clone()
            ->where('cultures_content_type', NucleicAcids::class)
            ->with([
                'cultures_content',
                'cultures_content.nucleic_content',
                'laboratories', 'people',
            ])
            ->orderByDesc('cultures.id')
            ->limit($perBucket)
            ->get();
        $cultures = $cultures->merge($nucleicCultures);

        // 6. Pools with their content relationships
        $poolCultures = $baseQuery->clone()
            ->where('cultures_content_type', Pools::class)
            ->with([
                'cultures_content',
                'cultures_content.pool_contents.samples',
                'laboratories', 'people',
            ])
            ->orderByDesc('cultures.id')
            ->limit($perBucket)
            ->get();
        $cultures = $cultures->merge($poolCultures);

        // Extract coordinates from all cultures
        return $cultures
            ->map(function ($sample) {
                [$latitude, $longitude] = $this->extractCoordinates($sample);
                $content = $sample->cultures_content;
                $context = $this->extractContextAttributes($sample);

                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'type' => $sample->type,
                    'source_type' => $content ? class_basename($content) : 'Unknown',
                    'code' => $sample->code,
                    'laboratory' => $sample->laboratories->name ?? 'Unknown',
                    'cultured_by' => $sample->people->name ?? 'Unknown',
                    'medium' => $sample->medium,
                    'human_ethnicity' => $context['human_ethnicity'],
                    'human_occupation' => $context['human_occupation'],
                    'human_country' => $context['human_country'],
                    'animal_species' => $context['animal_species'],
                    'animal_sex' => $context['animal_sex'],
                    'animal_age' => $context['animal_age'],
                    'parasite_species' => $context['parasite_species'],
                    'parasite_stage' => $context['parasite_stage'],
                    'parasite_sex' => $context['parasite_sex'],
                    'nucleic_type' => $context['nucleic_type'],
                    'pool_nr_pooled' => $context['pool_nr_pooled'],
                ];
            })
            ->filter(function ($sample) {
                return $sample['latitude'] && $sample['longitude'];
            })
            ->values();
    }

    /**
     * @return array<string, string|null>
     */
    private function extractContextAttributes(Cultures $culture): array
    {
        $attrs = [
            'human_ethnicity' => null,
            'human_occupation' => null,
            'human_country' => null,
            'animal_species' => null,
            'animal_sex' => null,
            'animal_age' => null,
            'parasite_species' => null,
            'parasite_stage' => null,
            'parasite_sex' => null,
            'nucleic_type' => null,
            'pool_nr_pooled' => null,
        ];

        $content = $culture->cultures_content;
        if (! $content) {
            return $attrs;
        }

        if ($content instanceof HumanSamples && $content->relationLoaded('humans') && $content->humans) {
            $attrs['human_ethnicity'] = $content->humans->ethnicity;
            $attrs['human_occupation'] = $content->humans->occupation;
            if ($content->humans->relationLoaded('countries') && $content->humans->countries) {
                $attrs['human_country'] = $content->humans->countries->name;
            }
        }

        if ($content instanceof AnimalSamples && $content->relationLoaded('animals') && $content->animals) {
            $attrs['animal_sex'] = $content->animals->sex;
            $attrs['animal_age'] = $content->animals->age;
            if ($content->animals->relationLoaded('animal_species') && $content->animals->animal_species) {
                $attrs['animal_species'] = $content->animals->animal_species->name_common;
            }
        }

        if ($content instanceof ParasiteSamples && $content->relationLoaded('parasites') && $content->parasites) {
            $attrs['parasite_stage'] = $content->parasites->stage;
            $attrs['parasite_sex'] = $content->parasites->sex;
            if ($content->parasites->relationLoaded('parasite_species') && $content->parasites->parasite_species) {
                $attrs['parasite_species'] = $content->parasites->parasite_species->name_scientific;
            }
        }

        if ($content instanceof NucleicAcids) {
            $attrs['nucleic_type'] = $content->type;
        }

        if ($content instanceof Pools) {
            $attrs['pool_nr_pooled'] = $content->nr_pooled !== null ? (string) $content->nr_pooled : null;
        }

        return $attrs;
    }

    public function filteredData()
    {
        $service = app(CulturesService::class);
        $additionalData = $service->assign();

        // Use aggregate queries for stats/charts to avoid loading all models into memory.
        $agg = $this->buildFilteredQuery()->withoutEagerLoads();

        $total = (clone $agg)->count();
        $humanCount = (clone $agg)->where('cultures_content_type', HumanSamples::class)->count();
        $animalCount = (clone $agg)->where('cultures_content_type', AnimalSamples::class)->count();
        $environmentCount = (clone $agg)->where('cultures_content_type', EnvironmentSamples::class)->count();
        $parasiteCount = (clone $agg)->where('cultures_content_type', ParasiteSamples::class)->count();
        $nucleicCount = (clone $agg)->where('cultures_content_type', NucleicAcids::class)->count();
        $poolCount = (clone $agg)->where('cultures_content_type', Pools::class)->count();
        $activeCulturesCount = (clone $agg)->where(function ($q) {
            $q->where('is_discarded', false)->orWhereNull('is_discarded');
        })->count();
        $distinctMediaCount = (clone $agg)
            ->whereNotNull('medium')
            ->where('medium', '!=', '')
            ->distinct()
            ->count('medium');
        $averageDaysOnCulture = $this->averageDaysOnCultureFromQuery(clone $agg);
        $confirmedPathogenResultsCount = $this->countConfirmedPathogenResults(clone $agg);

        $statistics = [
            'total_samples' => $total,
            'active_cultures' => $activeCulturesCount,
            'distinct_media' => $distinctMediaCount,
            'average_days_on_culture' => $averageDaysOnCulture,
            'confirmed_pathogen_results' => $confirmedPathogenResultsCount,
            'human_samples' => $humanCount,
            'animal_samples' => $animalCount,
            'environment_samples' => $environmentCount,
            'parasite_samples' => $parasiteCount,
            'nucleic_samples' => $nucleicCount,
            'pool_samples' => $poolCount,
            'samples_this_year' => (clone $agg)->whereYear('date_cultured', Carbon::now()->year)->count(),
            'samples_this_month' => (clone $agg)->whereYear('date_cultured', Carbon::now()->year)->whereMonth('date_cultured', Carbon::now()->month)->count(),
            'culturing_timeline' => $this->generateTimelineFromQuery(clone $agg),
        ];

        // Get paginated data for existing functionality
        $paginatedQuery = $this->buildFilteredQuery()->orderBy('created_at', 'desc');
        $cultures = $paginatedQuery->paginate(10, pageName: 'cultures-page');

        // Avoid loading *everything* for modal display (can explode memory on large projects).
        // Keep a reasonable cap for now; we can paginate these modals later.
        $allFilteredCultures = $this->buildFilteredQuery()
            ->with([
                'laboratories',
                'people',
                'parent',
                'cultures_content',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        CultureContentDetailsPresenter::hydrate($allFilteredCultures);

        $activeCultures = $this->buildFilteredQuery()
            ->with([
                'laboratories',
                'people',
                'parent',
                'cultures_content',
            ])
            ->where(function ($q) {
                $q->where('is_discarded', false)->orWhereNull('is_discarded');
            })
            ->orderBy('date_cultured', 'desc')
            ->limit(500)
            ->get();

        CultureContentDetailsPresenter::hydrate($activeCultures);

        $cultureDurationCultures = $this->buildFilteredQuery()
            ->with([
                'laboratories',
                'people',
                'parent',
                'cultures_content',
            ])
            ->whereNotNull('date_cultured')
            ->orderBy('date_cultured', 'desc')
            ->limit(500)
            ->get();

        CultureContentDetailsPresenter::hydrate($cultureDurationCultures);

        $confirmedPathogenRows = $this->confirmedPathogenRowsFromQuery(clone $agg);
        $allPathogens = $this->getAllPathogensForFilter();

        // Get cultures by source type
        $culturesBySource = [
            'Human' => $humanCount,
            'Animal' => $animalCount,
            'Environment' => $environmentCount,
            'Parasite' => $parasiteCount,
            'Nucleic' => $nucleicCount,
            'Pool' => $poolCount,
        ];

        // Get cultures by type
        $culturesByType = (clone $agg)
            ->select('type as k', DB::raw('COUNT(*) as c'))
            ->whereNotNull('type')
            ->groupBy('type')
            ->orderByDesc('c')
            ->limit(30)
            ->pluck('c', 'k')
            ->toArray();

        // Get cultures by laboratory
        $culturesByLaboratory = (clone $agg)
            ->leftJoin('laboratories', 'cultures.laboratories_id', '=', 'laboratories.id')
            ->whereNotNull('laboratories.name')
            ->select('laboratories.name as k', DB::raw('COUNT(*) as c'))
            ->groupBy('laboratories.name')
            ->orderByDesc('c')
            ->limit(30)
            ->pluck('c', 'k')
            ->toArray();

        // Get cultures by medium
        $culturesByMedium = (clone $agg)
            ->whereNotNull('medium')
            ->select('medium as k', DB::raw('COUNT(*) as c'))
            ->groupBy('medium')
            ->orderByDesc('c')
            ->limit(30)
            ->pluck('c', 'k')
            ->toArray();

        $culturesByCulturedBy = (clone $agg)
            ->leftJoin('people', 'cultures.people_id', '=', 'people.id')
            ->whereNotNull('people.id')
            ->selectRaw($this->peopleNameSql().' as k')
            ->selectRaw('COUNT(cultures.id) as c')
            ->groupBy('k')
            ->orderByDesc('c')
            ->limit(30)
            ->pluck('c', 'k')
            ->toArray();

        $sourceType = $this->normalizeFilterValue((string) $this->sourceTypeFilter);
        if ($this->sourceTypeFilter !== $sourceType) {
            $this->sourceTypeFilter = $sourceType;
        }

        $pieChartTabs = [
            ['key' => 'source', 'label' => 'Source', 'data' => $culturesBySource],
            ['key' => 'type', 'label' => 'Culture type', 'data' => $culturesByType],
        ];

        $barChartTabs = [
            ['key' => 'laboratory', 'label' => 'Top Laboratories', 'data' => $culturesByLaboratory],
            ['key' => 'medium', 'label' => 'Top Mediums', 'data' => $culturesByMedium],
            ['key' => 'cultured_by', 'label' => 'Top Cultured By', 'data' => $culturesByCulturedBy],
        ];

        $mapColorVariableOptions = [
            ['key' => 'source', 'label' => 'Source'],
            ['key' => 'type', 'label' => 'Culture type'],
            ['key' => 'laboratory', 'label' => 'Laboratory'],
            ['key' => 'medium', 'label' => 'Medium'],
            ['key' => 'cultured_by', 'label' => 'Cultured by'],
        ];

        if ($sourceType === 'human') {
            $humanEthnicity = (clone $agg)
                ->join('human_samples', 'human_samples.id', '=', 'cultures.cultures_content_id')
                ->leftJoin('humans', 'humans.id', '=', 'human_samples.humans_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('humans.ethnicity')
                ->select('humans.ethnicity as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('humans.ethnicity')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $humanOccupation = (clone $agg)
                ->join('human_samples', 'human_samples.id', '=', 'cultures.cultures_content_id')
                ->leftJoin('humans', 'humans.id', '=', 'human_samples.humans_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('humans.occupation')
                ->select('humans.occupation as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('humans.occupation')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $humanCountry = (clone $agg)
                ->join('human_samples', 'human_samples.id', '=', 'cultures.cultures_content_id')
                ->leftJoin('humans', 'humans.id', '=', 'human_samples.humans_id')
                ->leftJoin('countries', 'countries.id', '=', 'humans.countries_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('countries.name')
                ->select('countries.name as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('countries.name')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $pieChartTabs[] = ['key' => 'human_ethnicity', 'label' => 'Human Ethnicity', 'data' => $humanEthnicity];
            $barChartTabs[] = ['key' => 'human_occupation', 'label' => 'Human Occupation', 'data' => $humanOccupation];
            $barChartTabs[] = ['key' => 'human_country', 'label' => 'Human Country', 'data' => $humanCountry];
            $mapColorVariableOptions[] = ['key' => 'human_ethnicity', 'label' => 'Human ethnicity'];
            $mapColorVariableOptions[] = ['key' => 'human_occupation', 'label' => 'Human occupation'];
            $mapColorVariableOptions[] = ['key' => 'human_country', 'label' => 'Human country'];
        }

        if ($sourceType === 'animal') {
            $animalSpecies = (clone $agg)
                ->join('animal_samples', 'animal_samples.id', '=', 'cultures.cultures_content_id')
                ->join('animals', 'animals.id', '=', 'animal_samples.animals_id')
                ->leftJoin('animal_species', 'animal_species.id', '=', 'animals.animal_species_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('animal_species.name_common')
                ->select('animal_species.name_common as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('animal_species.name_common')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $animalSex = (clone $agg)
                ->join('animal_samples', 'animal_samples.id', '=', 'cultures.cultures_content_id')
                ->join('animals', 'animals.id', '=', 'animal_samples.animals_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('animals.sex')
                ->select('animals.sex as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('animals.sex')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $animalAge = (clone $agg)
                ->join('animal_samples', 'animal_samples.id', '=', 'cultures.cultures_content_id')
                ->join('animals', 'animals.id', '=', 'animal_samples.animals_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('animals.age')
                ->select('animals.age as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('animals.age')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $pieChartTabs[] = ['key' => 'animal_species', 'label' => 'Animal Species', 'data' => $animalSpecies];
            $barChartTabs[] = ['key' => 'animal_sex', 'label' => 'Animal Sex', 'data' => $animalSex];
            $barChartTabs[] = ['key' => 'animal_age', 'label' => 'Animal Age', 'data' => $animalAge];
            $mapColorVariableOptions[] = ['key' => 'animal_species', 'label' => 'Animal species'];
            $mapColorVariableOptions[] = ['key' => 'animal_sex', 'label' => 'Animal sex'];
            $mapColorVariableOptions[] = ['key' => 'animal_age', 'label' => 'Animal age'];
        }

        if ($sourceType === 'parasite') {
            $parasiteSpecies = (clone $agg)
                ->join('parasite_samples', 'parasite_samples.id', '=', 'cultures.cultures_content_id')
                ->join('parasites', 'parasites.id', '=', 'parasite_samples.parasites_id')
                ->leftJoin('parasite_species', 'parasite_species.id', '=', 'parasites.parasite_species_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereNotNull('parasite_species.name_scientific')
                ->select('parasite_species.name_scientific as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('parasite_species.name_scientific')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $parasiteStage = (clone $agg)
                ->join('parasite_samples', 'parasite_samples.id', '=', 'cultures.cultures_content_id')
                ->join('parasites', 'parasites.id', '=', 'parasite_samples.parasites_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereNotNull('parasites.stage')
                ->select('parasites.stage as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('parasites.stage')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $parasiteSex = (clone $agg)
                ->join('parasite_samples', 'parasite_samples.id', '=', 'cultures.cultures_content_id')
                ->join('parasites', 'parasites.id', '=', 'parasite_samples.parasites_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereNotNull('parasites.sex')
                ->select('parasites.sex as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('parasites.sex')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $pieChartTabs[] = ['key' => 'parasite_species', 'label' => 'Parasite Species', 'data' => $parasiteSpecies];
            $barChartTabs[] = ['key' => 'parasite_stage', 'label' => 'Parasite Stage', 'data' => $parasiteStage];
            $barChartTabs[] = ['key' => 'parasite_sex', 'label' => 'Parasite Sex', 'data' => $parasiteSex];
            $mapColorVariableOptions[] = ['key' => 'parasite_species', 'label' => 'Parasite species'];
            $mapColorVariableOptions[] = ['key' => 'parasite_stage', 'label' => 'Parasite stage'];
            $mapColorVariableOptions[] = ['key' => 'parasite_sex', 'label' => 'Parasite sex'];
        }

        if ($sourceType === 'nucleic') {
            $nucleicType = (clone $agg)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'cultures.cultures_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(NucleicAcids::class))
                ->whereNotNull('nucleic_acids.type')
                ->select('nucleic_acids.type as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('nucleic_acids.type')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $pieChartTabs[] = ['key' => 'nucleic_type', 'label' => 'Nucleic Type', 'data' => $nucleicType];
            $barChartTabs[] = ['key' => 'nucleic_type', 'label' => 'Top Nucleic Types', 'data' => $nucleicType];
            $mapColorVariableOptions[] = ['key' => 'nucleic_type', 'label' => 'Nucleic type'];
        }

        if ($sourceType === 'pool') {
            $poolSizes = (clone $agg)
                ->join('pools', 'pools.id', '=', 'cultures.cultures_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(Pools::class))
                ->whereNotNull('pools.nr_pooled')
                ->select('pools.nr_pooled as k', DB::raw('COUNT(cultures.id) as c'))
                ->groupBy('pools.nr_pooled')
                ->orderByDesc('c')
                ->limit(20)
                ->pluck('c', 'k')
                ->toArray();

            $pieChartTabs[] = ['key' => 'pool_nr_pooled', 'label' => 'Pool Size', 'data' => $poolSizes];
            $barChartTabs[] = ['key' => 'pool_nr_pooled', 'label' => 'Top Pool Sizes', 'data' => $poolSizes];
            $mapColorVariableOptions[] = ['key' => 'pool_nr_pooled', 'label' => 'Pool size'];
        }

        // Get samples with locations for the map
        $samples = $this->getCulturesWithCoordinates(2500);

        // Get all available options for filter dropdowns
        $allCultureTypes = $this->getAllCultureTypes();
        $allSubProjects = $this->allSubProjects();
        $allLaboratories = $this->getAllLaboratories();
        $allMediums = $this->getAllMediums();
        $allAnimalSpecies = $this->allAnimalSpecies();
        $availableSourceTypes = $this->availableSourceTypes();
        $availableTracePrimaryTypes = $this->availableTracePrimaryTypes();
        $tracePrimaryAnimalSpeciesOptions = $this->tracePrimaryAnimalSpeciesOptions();
        $tracePrimaryAnimalSexesOptions = $this->tracePrimaryAnimalSexesOptions();
        $tracePrimaryAnimalAgesOptions = $this->tracePrimaryAnimalAgesOptions();
        $tracePrimaryHumanEthnicitiesOptions = $this->tracePrimaryHumanEthnicitiesOptions();
        $tracePrimaryHumanOccupationsOptions = $this->tracePrimaryHumanOccupationsOptions();
        $tracePrimaryHumanCountriesOptions = $this->tracePrimaryHumanCountriesOptions();
        $tracePrimaryParasiteSpeciesOptions = $this->tracePrimaryParasiteSpeciesOptions();
        $tracePrimaryCultureTypesOptions = $this->tracePrimaryCultureTypesOptions();
        $tracePrimaryCultureMediumsOptions = $this->tracePrimaryCultureMediumsOptions();
        $tracePrimaryNucleicTypesOptions = $this->tracePrimaryNucleicTypesOptions();
        $availableTraceDeepPrimaryTypes = $this->availableTraceDeepPrimaryTypes();
        $traceDeepAnimalSpeciesOptions = $this->traceDeepAnimalSpeciesOptions();
        $traceDeepAnimalSexesOptions = $this->traceDeepAnimalSexesOptions();
        $traceDeepAnimalAgesOptions = $this->traceDeepAnimalAgesOptions();
        $traceDeepHumanEthnicitiesOptions = $this->traceDeepHumanEthnicitiesOptions();
        $traceDeepHumanOccupationsOptions = $this->traceDeepHumanOccupationsOptions();
        $traceDeepHumanCountriesOptions = $this->traceDeepHumanCountriesOptions();

        $viewData = array_merge($additionalData, [
            'cultures' => $cultures,
            'all_cultures' => $allFilteredCultures,
            'active_cultures' => $activeCultures,
            'culture_duration_cultures' => $cultureDurationCultures,
            'confirmed_pathogen_rows' => $confirmedPathogenRows,
            'isGuestMode' => $this->isGuestMode(),
            'descriptive_stats' => $statistics,
            'culturesBySource' => $culturesBySource,
            'culturesByType' => $culturesByType,
            'culturesByLaboratory' => $culturesByLaboratory,
            'culturesByMedium' => $culturesByMedium,
            'culturesByCulturedBy' => $culturesByCulturedBy,
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
            'samples' => $samples,
            'allCultureTypes' => $allCultureTypes,
            'allSubProjects' => $allSubProjects,
            'allLaboratories' => $allLaboratories,
            'allMediums' => $allMediums,
            'allPathogens' => $allPathogens,
            'allAnimalSpecies' => $allAnimalSpecies,
            'availableSourceTypes' => $availableSourceTypes,
            'availableTracePrimaryTypes' => $availableTracePrimaryTypes,
            'tracePrimaryAnimalSpeciesOptions' => $tracePrimaryAnimalSpeciesOptions,
            'tracePrimaryAnimalSexesOptions' => $tracePrimaryAnimalSexesOptions,
            'tracePrimaryAnimalAgesOptions' => $tracePrimaryAnimalAgesOptions,
            'tracePrimaryHumanEthnicitiesOptions' => $tracePrimaryHumanEthnicitiesOptions,
            'tracePrimaryHumanOccupationsOptions' => $tracePrimaryHumanOccupationsOptions,
            'tracePrimaryHumanCountriesOptions' => $tracePrimaryHumanCountriesOptions,
            'tracePrimaryParasiteSpeciesOptions' => $tracePrimaryParasiteSpeciesOptions,
            'tracePrimaryCultureTypesOptions' => $tracePrimaryCultureTypesOptions,
            'tracePrimaryCultureMediumsOptions' => $tracePrimaryCultureMediumsOptions,
            'tracePrimaryNucleicTypesOptions' => $tracePrimaryNucleicTypesOptions,
            'availableTraceDeepPrimaryTypes' => $availableTraceDeepPrimaryTypes,
            'traceDeepAnimalSpeciesOptions' => $traceDeepAnimalSpeciesOptions,
            'traceDeepAnimalSexesOptions' => $traceDeepAnimalSexesOptions,
            'traceDeepAnimalAgesOptions' => $traceDeepAnimalAgesOptions,
            'traceDeepHumanEthnicitiesOptions' => $traceDeepHumanEthnicitiesOptions,
            'traceDeepHumanOccupationsOptions' => $traceDeepHumanOccupationsOptions,
            'traceDeepHumanCountriesOptions' => $traceDeepHumanCountriesOptions,
            'activeFilters' => [
                'sampleVisibility' => $this->sampleVisibility,
                'timelineGranularity' => $this->timelineGranularity,
                'cultureTypeFilter' => $this->cultureTypeFilter,
                'sourceTypeFilter' => $this->sourceTypeFilter,
                'tracePrimaryTypeFilter' => $this->tracePrimaryTypeFilter,
                'tracePrimaryAnimalSpeciesFilter' => $this->tracePrimaryAnimalSpeciesFilter,
                'tracePrimaryAnimalSexFilter' => $this->tracePrimaryAnimalSexFilter,
                'tracePrimaryAnimalAgeFilter' => $this->tracePrimaryAnimalAgeFilter,
                'tracePrimaryHumanEthnicityFilter' => $this->tracePrimaryHumanEthnicityFilter,
                'tracePrimaryHumanOccupationFilter' => $this->tracePrimaryHumanOccupationFilter,
                'tracePrimaryHumanCountryFilter' => $this->tracePrimaryHumanCountryFilter,
                'tracePrimaryParasiteSpeciesFilter' => $this->tracePrimaryParasiteSpeciesFilter,
                'tracePrimaryCultureTypeFilter' => $this->tracePrimaryCultureTypeFilter,
                'tracePrimaryCultureMediumFilter' => $this->tracePrimaryCultureMediumFilter,
                'tracePrimaryNucleicTypeFilter' => $this->tracePrimaryNucleicTypeFilter,
                'tracePrimaryPoolMinNrPooled' => $this->tracePrimaryPoolMinNrPooled,
                'tracePrimaryPoolMaxNrPooled' => $this->tracePrimaryPoolMaxNrPooled,
                'traceDeepPrimaryTypeFilter' => $this->traceDeepPrimaryTypeFilter,
                'traceDeepAnimalSpeciesFilter' => $this->traceDeepAnimalSpeciesFilter,
                'traceDeepAnimalSexFilter' => $this->traceDeepAnimalSexFilter,
                'traceDeepAnimalAgeFilter' => $this->traceDeepAnimalAgeFilter,
                'traceDeepHumanEthnicityFilter' => $this->traceDeepHumanEthnicityFilter,
                'traceDeepHumanOccupationFilter' => $this->traceDeepHumanOccupationFilter,
                'traceDeepHumanCountryFilter' => $this->traceDeepHumanCountryFilter,
                'laboratoryFilter' => $this->laboratoryFilter,
                'mediumFilter' => $this->mediumFilter,
                'subProjectFilter' => $this->subProjectFilter,
                'pathogenFilter' => $this->pathogenFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ],
            'canEdit' => $this->canEdit(),
        ]);

        return $viewData;
    }

    private function allSubProjects()
    {
        $base = $this->buildFilteredQuery();

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', Cultures::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('cultures.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableSourceTypes(): array
    {
        $prev = $this->sourceTypeFilter;
        $this->sourceTypeFilter = 'all';

        $types = $this->buildFilteredQuery()
            ->withoutEagerLoads()
            ->select('cultures.cultures_content_type')
            ->distinct()
            ->pluck('cultures.cultures_content_type')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values();

        $this->sourceTypeFilter = $prev;

        $map = [
            'HumanSamples' => ['human', 'Human'],
            'AnimalSamples' => ['animal', 'Animal'],
            'EnvironmentSamples' => ['environment', 'Environment'],
            'ParasiteSamples' => ['parasite', 'Parasite'],
            'NucleicAcids' => ['nucleic', 'Nucleic'],
            'Pools' => ['pool', 'Pool'],
        ];

        $options = [];
        foreach ($types->unique() as $rawType) {
            $baseName = $this->normalizedBasename((string) $rawType);
            if (! isset($map[$baseName])) {
                continue;
            }
            [$key, $label] = $map[$baseName];
            $options[$key] = $label;
        }

        $order = ['human', 'animal', 'environment', 'parasite', 'nucleic', 'pool'];
        $sorted = [];
        foreach ($order as $k) {
            if (array_key_exists($k, $options)) {
                $sorted[$k] = $options[$k];
            }
        }

        return $sorted;
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableTracePrimaryTypes(): array
    {
        $sourceType = $this->normalizeFilterValue((string) $this->sourceTypeFilter);
        if ($this->sourceTypeFilter !== $sourceType) {
            $this->sourceTypeFilter = $sourceType;
        }

        $prev = [
            'tracePrimaryTypeFilter' => $this->tracePrimaryTypeFilter,
            'tracePrimaryAnimalSpeciesFilter' => $this->tracePrimaryAnimalSpeciesFilter,
            'tracePrimaryAnimalSexFilter' => $this->tracePrimaryAnimalSexFilter,
            'tracePrimaryAnimalAgeFilter' => $this->tracePrimaryAnimalAgeFilter,
            'tracePrimaryHumanEthnicityFilter' => $this->tracePrimaryHumanEthnicityFilter,
            'tracePrimaryHumanOccupationFilter' => $this->tracePrimaryHumanOccupationFilter,
            'tracePrimaryHumanCountryFilter' => $this->tracePrimaryHumanCountryFilter,
            'tracePrimaryParasiteSpeciesFilter' => $this->tracePrimaryParasiteSpeciesFilter,
            'tracePrimaryCultureTypeFilter' => $this->tracePrimaryCultureTypeFilter,
            'tracePrimaryCultureMediumFilter' => $this->tracePrimaryCultureMediumFilter,
            'tracePrimaryNucleicTypeFilter' => $this->tracePrimaryNucleicTypeFilter,
            'tracePrimaryPoolMinNrPooled' => $this->tracePrimaryPoolMinNrPooled,
            'tracePrimaryPoolMaxNrPooled' => $this->tracePrimaryPoolMaxNrPooled,
            'traceDeepPrimaryTypeFilter' => $this->traceDeepPrimaryTypeFilter,
            'traceDeepAnimalSpeciesFilter' => $this->traceDeepAnimalSpeciesFilter,
            'traceDeepAnimalSexFilter' => $this->traceDeepAnimalSexFilter,
            'traceDeepAnimalAgeFilter' => $this->traceDeepAnimalAgeFilter,
            'traceDeepHumanEthnicityFilter' => $this->traceDeepHumanEthnicityFilter,
            'traceDeepHumanOccupationFilter' => $this->traceDeepHumanOccupationFilter,
            'traceDeepHumanCountryFilter' => $this->traceDeepHumanCountryFilter,
        ];

        $this->tracePrimaryTypeFilter = 'all';
        $this->tracePrimaryAnimalSpeciesFilter = '';
        $this->tracePrimaryAnimalSexFilter = '';
        $this->tracePrimaryAnimalAgeFilter = '';
        $this->tracePrimaryHumanEthnicityFilter = '';
        $this->tracePrimaryHumanOccupationFilter = '';
        $this->tracePrimaryHumanCountryFilter = '';
        $this->tracePrimaryParasiteSpeciesFilter = '';
        $this->tracePrimaryCultureTypeFilter = '';
        $this->tracePrimaryCultureMediumFilter = '';
        $this->tracePrimaryNucleicTypeFilter = '';
        $this->tracePrimaryPoolMinNrPooled = null;
        $this->tracePrimaryPoolMaxNrPooled = null;
        $this->traceDeepPrimaryTypeFilter = 'all';
        $this->traceDeepAnimalSpeciesFilter = '';
        $this->traceDeepAnimalSexFilter = '';
        $this->traceDeepAnimalAgeFilter = '';
        $this->traceDeepHumanEthnicityFilter = '';
        $this->traceDeepHumanOccupationFilter = '';
        $this->traceDeepHumanCountryFilter = '';

        if ($sourceType === 'parasite') {
            $types = $this->buildFilteredQuery()
                ->withoutEagerLoads()
                ->join('parasite_samples as trace_parasite_samples', 'trace_parasite_samples.id', '=', 'cultures.cultures_content_id')
                ->join('parasites as trace_parasites', 'trace_parasites.id', '=', 'trace_parasite_samples.parasites_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->select('trace_parasites.parasites_origin_type')
                ->distinct()
                ->pluck('trace_parasites.parasites_origin_type')
                ->filter()
                ->map(fn ($v) => (string) $v)
                ->values();

            $out = [];
            foreach ($types as $rawType) {
                $base = $this->normalizedBasename($rawType);
                if ($base === 'HumanSamples') {
                    $out['human'] = 'Human';
                } elseif ($base === 'AnimalSamples') {
                    $out['animal'] = 'Animal';
                } elseif ($base === 'EnvironmentSamples') {
                    $out['environment'] = 'Environment';
                } elseif ($base === 'Pools') {
                    $out['pool'] = 'Pool';
                }
            }

            foreach ($prev as $k => $v) {
                $this->{$k} = $v;
            }

            if ($this->tracePrimaryTypeFilter !== 'all' && ! isset($out[$this->tracePrimaryTypeFilter])) {
                $out = [$this->tracePrimaryTypeFilter => ucfirst($this->tracePrimaryTypeFilter)] + $out;
            }

            return $out;
        }

        $out = [];
        $allCandidates = [
            'human' => 'Human',
            'animal' => 'Animal',
            'environment' => 'Environment',
            'parasite' => 'Parasite',
            'culture' => 'Culture',
            'nucleic' => 'Nucleic acids',
            'pool' => 'Pool',
        ];

        $allowedBySource = match ($sourceType) {
            // Cultures directly from parasites should trace to parasite origins (human/animal/environment),
            // and optionally pool when that origin path exists.
            'parasite' => ['human', 'animal', 'environment', 'pool'],
            // For culture direct, allow tracing across all meaningful upstream types except itself.
            'culture' => ['human', 'animal', 'environment', 'parasite', 'nucleic', 'pool'],
            'nucleic' => ['human', 'animal', 'environment', 'parasite', 'culture', 'nucleic', 'pool'],
            'pool' => ['human', 'animal', 'environment', 'parasite', 'culture', 'nucleic', 'pool'],
            'human', 'animal', 'environment' => [],
            default => array_keys($allCandidates),
        };

        $candidates = [];
        foreach ($allowedBySource as $key) {
            if (isset($allCandidates[$key])) {
                $candidates[$key] = $allCandidates[$key];
            }
        }

        foreach ($candidates as $key => $label) {
            $this->tracePrimaryTypeFilter = $key;
            $has = $this->buildFilteredQuery()
                ->withoutEagerLoads()
                ->select('cultures.id')
                ->limit(1)
                ->exists();

            if ($has) {
                $out[$key] = $label;
            }
        }

        foreach ($prev as $k => $v) {
            $this->{$k} = $v;
        }

        if ($sourceType !== 'all' && $sourceType !== '' && isset($out[$sourceType])) {
            unset($out[$sourceType]);
        }

        if ($this->tracePrimaryTypeFilter !== 'all' && ! isset($out[$this->tracePrimaryTypeFilter])) {
            $out = [$this->tracePrimaryTypeFilter => ucfirst($this->tracePrimaryTypeFilter)] + $out;
        }

        return $out;
    }

    private function allAnimalSpecies()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->select('animal_species.name_common');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->where('tubes.tubes_content_type', AnimalSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('animal_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                        ->where('tubes.tubes_content_type', AnimalSamples::class);
                });
            }
        }

        return $q->distinct()->orderBy('animal_species.name_common')->pluck('animal_species.name_common')->filter()->values();
    }

    private function allAnimalSexesForTrace()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->select('animals.sex');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('animal_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('animals.sex')->pluck('animals.sex')->filter()->values();
    }

    private function allAnimalAgesForTrace()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->select('animals.age');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('animal_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('animals.age')->pluck('animals.age')->filter()->values();
    }

    private function allHumanEthnicitiesForTrace()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->select('humans.ethnicity');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('human_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('humans.ethnicity')->pluck('humans.ethnicity')->filter()->values();
    }

    private function allHumanOccupationsForTrace()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->select('humans.occupation');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('human_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('humans.occupation')->pluck('humans.occupation')->filter()->values();
    }

    private function allHumanCountriesForTrace()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->select('countries.name');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('human_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('countries.name')->pluck('countries.name')->filter()->values();
    }

    private function allParasiteSpeciesForTrace()
    {
        $q = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.name_scientific')
            ->select('parasite_species.name_scientific');

        $this->applyVisibilityToSeedQuery($q, ParasiteSamples::class);

        return $q->distinct()->orderBy('parasite_species.name_scientific')->pluck('parasite_species.name_scientific')->filter()->values();
    }

    private function allNucleicTypesForTrace()
    {
        $q = NucleicAcids::query()->whereNotNull('nucleic_acids.type')->select('nucleic_acids.type');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(NucleicAcids::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('nucleic_acids.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('nucleic_acids.type')->pluck('nucleic_acids.type')->filter()->values();
    }

    private function tracePrimaryParasiteSpeciesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'parasite') {
            return collect();
        }

        $prev = $this->tracePrimaryParasiteSpeciesFilter;
        $this->tracePrimaryParasiteSpeciesFilter = '';
        $candidates = $this->allParasiteSpeciesForTrace();
        $this->tracePrimaryParasiteSpeciesFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryParasiteSpeciesFilter = (string) $candidate;
            $has = $this->buildFilteredQuery()->withoutEagerLoads()->select('cultures.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryParasiteSpeciesFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryCultureTypesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'culture') {
            return collect();
        }

        $prev = $this->tracePrimaryCultureTypeFilter;
        $this->tracePrimaryCultureTypeFilter = '';
        $candidates = $this->getAllCultureTypes();
        $this->tracePrimaryCultureTypeFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryCultureTypeFilter = (string) $candidate;
            $has = $this->buildFilteredQuery()->withoutEagerLoads()->select('cultures.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryCultureTypeFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryCultureMediumsOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'culture') {
            return collect();
        }

        $prev = $this->tracePrimaryCultureMediumFilter;
        $this->tracePrimaryCultureMediumFilter = '';
        $candidates = $this->getAllMediums();
        $this->tracePrimaryCultureMediumFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryCultureMediumFilter = (string) $candidate;
            $has = $this->buildFilteredQuery()->withoutEagerLoads()->select('cultures.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryCultureMediumFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryNucleicTypesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'nucleic') {
            return collect();
        }

        $prev = $this->tracePrimaryNucleicTypeFilter;
        $this->tracePrimaryNucleicTypeFilter = '';
        $candidates = $this->allNucleicTypesForTrace();
        $this->tracePrimaryNucleicTypeFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryNucleicTypeFilter = (string) $candidate;
            $has = $this->buildFilteredQuery()->withoutEagerLoads()->select('cultures.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryNucleicTypeFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableTraceDeepPrimaryTypes(): array
    {
        if (! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return [];
        }

        $prev = $this->traceDeepPrimaryTypeFilter;
        $this->traceDeepPrimaryTypeFilter = 'all';

        $out = [];
        foreach (['human' => 'Human', 'animal' => 'Animal', 'environment' => 'Environment'] as $k => $label) {
            $this->traceDeepPrimaryTypeFilter = $k;
            if ($this->buildFilteredQuery()->withoutEagerLoads()->select('cultures.id')->limit(1)->exists()) {
                $out[$k] = $label;
            }
        }

        $this->traceDeepPrimaryTypeFilter = $prev;

        if ($prev !== 'all' && ! isset($out[$prev])) {
            $out = [$prev => ucfirst($prev)] + $out;
        }

        return $out;
    }

    private function generateTimelineFromQuery($query): array
    {
        $driver = DB::getDriverName();

        if ($this->timelineGranularity === 'yearly') {
            $startYear = (int) Carbon::now()->subYears(9)->format('Y');
            $endYear = (int) Carbon::now()->format('Y');

            $yearExpr = match ($driver) {
                'mysql' => 'YEAR(date_cultured)',
                'pgsql' => 'EXTRACT(YEAR FROM date_cultured)',
                default => "strftime('%Y', date_cultured)",
            };

            $rows = (clone $query)
                ->whereNotNull('date_cultured')
                ->whereBetween('date_cultured', [Carbon::create($startYear, 1, 1)->toDateString(), Carbon::create($endYear, 12, 31)->toDateString()])
                ->select(DB::raw($yearExpr.' as k'), DB::raw('COUNT(*) as c'))
                ->groupBy('k')
                ->pluck('c', 'k')
                ->toArray();

            $out = [];
            for ($y = $startYear; $y <= $endYear; $y++) {
                $key = (string) $y;
                $out[$key] = (int) ($rows[$key] ?? 0);
            }

            return $out;
        }

        $start = Carbon::now()->subMonths(11)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $monthExpr = match ($driver) {
            'mysql' => "DATE_FORMAT(date_cultured, '%Y-%m')",
            'pgsql' => "to_char(date_cultured, 'YYYY-MM')",
            default => "strftime('%Y-%m', date_cultured)",
        };

        $rows = (clone $query)
            ->whereNotNull('date_cultured')
            ->whereBetween('date_cultured', [$start->toDateString(), $end->toDateString()])
            ->select(DB::raw($monthExpr.' as ym'), DB::raw('COUNT(*) as c'))
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->toArray();

        $out = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $ym = $month->format('Y-m');
            $label = $month->format('M Y');
            $out[$label] = (int) ($rows[$ym] ?? 0);
        }

        return $out;
    }

    private function peopleNameSql(): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'mysql' => "TRIM(CONCAT_WS(' ', people.first_name, people.last_name))",
            'pgsql' => "TRIM(CONCAT(people.first_name, ' ', people.last_name))",
            default => "TRIM(COALESCE(people.first_name, '') || ' ' || COALESCE(people.last_name, ''))",
        };
    }

    public function render()
    {
        $viewData = $this->filteredData();

        return view('livewire.cultures-dashboard', $viewData);
    }
}
