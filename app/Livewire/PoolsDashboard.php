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
use App\Services\PrimarySampleReachability;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoolsDashboard extends PlainComponent
{
    protected $projectId;

    public string $contentTypeFilter = 'all';

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

    public ?int $minNrPooled = null;

    public ?int $maxNrPooled = null;

    public string $laboratoryFilter = '';

    public string $subProjectFilter = '';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $timelineGranularity = 'monthly';

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

    public function updatedContentTypeFilter(): void
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
    }

    public function resetFilters(): void
    {
        $this->reset([
            'contentTypeFilter',
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
            'minNrPooled',
            'maxNrPooled',
            'laboratoryFilter',
            'subProjectFilter',
            'startDate',
            'endDate',
            'timelineGranularity',
        ]);
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableTracePrimaryTypes(): array
    {
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

        $baseIds = $this->baseQuery()
            ->select('pools.id')
            ->distinct()
            ->limit(2000)
            ->pluck('pools.id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        foreach ($prev as $k => $v) {
            $this->{$k} = $v;
        }

        if ($baseIds === []) {
            return [];
        }

        $baseSet = array_fill_keys($baseIds, true);
        $reachability = app(PrimarySampleReachability::class);

        $out = [];
        foreach ([
            'human' => HumanSamples::class,
            'animal' => AnimalSamples::class,
            'environment' => EnvironmentSamples::class,
        ] as $key => $primaryType) {
            $primaryIds = $this->primarySampleIdsForTracing($primaryType);
            $ids = $reachability->poolIdsFromPrimary($primaryType, $primaryIds, $this->projectId, $this->isGuestMode());

            foreach ($ids as $id) {
                if (isset($baseSet[$id])) {
                    $out[$key] = ucfirst($key);
                    break;
                }
            }
        }

        $types = DB::table('pool_contents')
            ->whereIn('pool_contents.pools_id', $baseIds)
            ->select('pool_contents.samples_type')
            ->distinct()
            ->pluck('samples_type')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values();

        $map = [
            ParasiteSamples::class => ['key' => 'parasite', 'label' => 'Parasite'],
            Cultures::class => ['key' => 'culture', 'label' => 'Culture'],
            NucleicAcids::class => ['key' => 'nucleic', 'label' => 'Nucleic acids'],
            Pools::class => ['key' => 'pool', 'label' => 'Pool'],
        ];

        foreach ($types as $t) {
            $base = $this->normalizedBasename((string) $t);
            foreach ($map as $class => $entry) {
                if (class_basename($class) === $base) {
                    $out[$entry['key']] = $entry['label'];
                    break;
                }
            }
        }

        if ($this->contentTypeFilter !== 'all' && $this->contentTypeFilter !== '' && isset($out[$this->contentTypeFilter])) {
            unset($out[$this->contentTypeFilter]);
        }

        if ($this->tracePrimaryTypeFilter !== 'all' && ! isset($out[$this->tracePrimaryTypeFilter])) {
            $out = [$this->tracePrimaryTypeFilter => ucfirst($this->tracePrimaryTypeFilter)] + $out;
        }

        return $out;
    }

    private function baseQuery()
    {
        $query = Pools::query()->leftJoin('laboratories', 'pools.laboratories_id', '=', 'laboratories.id');

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'pools.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(Pools::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where(function ($w) {
                $w->where('pools.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'pools.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(Pools::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        if ($this->laboratoryFilter !== '') {
            $query->where('laboratories.name', $this->laboratoryFilter);
        }
        if ($this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'pools.id')
                    ->where('sub_project_assignments.assignable_type', Pools::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }

        if ($this->contentTypeFilter !== '' && $this->contentTypeFilter !== 'all') {
            $contentType = match ($this->contentTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'nucleic' => NucleicAcids::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                default => null,
            };

            if ($contentType) {
                $query->whereExists(function ($sub) use ($contentType) {
                    $sub->select(DB::raw(1))
                        ->from('pool_contents')
                        ->whereColumn('pool_contents.pools_id', 'pools.id')
                        ->whereIn('pool_contents.samples_type', $this->typeVariants($contentType));
                });
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

        if ($hasAnyTrace) {
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

                if ($upstreamSeedIds === []) {
                    $query->whereRaw('1 = 0');
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
                            } else {
                                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                                $upstreamSeedIds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));

                                if ($upstreamSeedIds === []) {
                                    $query->whereRaw('1 = 0');
                                }
                            }
                        }
                    }

                    $reachability = app(PrimarySampleReachability::class);
                    $maxDepth = $upstreamType === Pools::class ? 10 : 6;

                    $ids = in_array($this->tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
                        ? $reachability->poolIdsFromPrimary($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth)
                        : $reachability->poolIdsFromSeed($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth);

                    if ($ids === []) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('pools.id', $ids);
                    }
                }
            }
        }

        if ($this->minNrPooled !== null) {
            $query->where('pools.nr_pooled', '>=', $this->minNrPooled);
        }

        if ($this->maxNrPooled !== null) {
            $query->where('pools.nr_pooled', '<=', $this->maxNrPooled);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('pools.date_pooled', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('pools.date_pooled', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('pools.date_pooled', '<=', $this->endDate);
        }

        return $query;
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableContentTypes(): array
    {
        $query = Pools::query();

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'pools.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(Pools::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where(function ($w) {
                $w->where('pools.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'pools.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(Pools::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        if ($this->laboratoryFilter !== '') {
            $query->leftJoin('laboratories', 'pools.laboratories_id', '=', 'laboratories.id')
                ->where('laboratories.name', $this->laboratoryFilter);
        }

        if ($this->minNrPooled !== null) {
            $query->where('pools.nr_pooled', '>=', $this->minNrPooled);
        }

        if ($this->maxNrPooled !== null) {
            $query->where('pools.nr_pooled', '<=', $this->maxNrPooled);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('pools.date_pooled', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('pools.date_pooled', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('pools.date_pooled', '<=', $this->endDate);
        }

        $poolIds = $query->limit(5000)->pluck('pools.id')->map(fn ($v) => (int) $v)->all();
        if ($poolIds === []) {
            return [];
        }

        $types = DB::table('pool_contents')
            ->whereIn('pool_contents.pools_id', $poolIds)
            ->select('pool_contents.samples_type')
            ->distinct()
            ->pluck('samples_type')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values();

        $map = [
            'HumanSamples' => ['key' => 'human', 'label' => 'Human samples'],
            'AnimalSamples' => ['key' => 'animal', 'label' => 'Animal samples'],
            'EnvironmentSamples' => ['key' => 'environment', 'label' => 'Environment samples'],
            'ParasiteSamples' => ['key' => 'parasite', 'label' => 'Parasite samples'],
            'NucleicAcids' => ['key' => 'nucleic', 'label' => 'Nucleic acids'],
            'Cultures' => ['key' => 'culture', 'label' => 'Cultures'],
            'Pools' => ['key' => 'pool', 'label' => 'Pools (nested)'],
        ];

        $out = [];
        foreach ($types as $t) {
            $base = $this->normalizedBasename((string) $t);
            if (! isset($map[$base])) {
                continue;
            }
            $out[$map[$base]['key']] = $map[$base]['label'];
        }

        if ($this->contentTypeFilter !== 'all' && $this->contentTypeFilter !== '' && ! isset($out[$this->contentTypeFilter])) {
            $out = [$this->contentTypeFilter => $this->contentTypeFilter] + $out;
        }

        return $out;
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

            $this->applyVisibilityToSeedQuery($q, 'parasite_samples', ParasiteSamples::class);

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

            $this->applyVisibilityToSeedQuery($q, 'cultures', Cultures::class);

            return $q->distinct()->limit(5000)->pluck('cultures.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === NucleicAcids::class) {
            $q = NucleicAcids::query()->select('nucleic_acids.id');

            if ($this->tracePrimaryNucleicTypeFilter !== '') {
                $q->where('nucleic_acids.type', $this->tracePrimaryNucleicTypeFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'nucleic_acids', NucleicAcids::class);

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

            $this->applyVisibilityToSeedQuery($q, 'pools', Pools::class);

            return $q->distinct()->limit(5000)->pluck('pools.id')->map(fn ($v) => (int) $v)->all();
        }

        return [];
    }

    private function applyVisibilityToSeedQuery($q, string $table, string $type): void
    {
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

    /**
     * @return array<int,int> pools.id
     */
    private function basePoolIdsForTraceOptions(array $overrides): array
    {
        $original = [];
        foreach ($overrides as $key => $value) {
            $original[$key] = $this->{$key};
            $this->{$key} = $value;
        }

        try {
            return $this->baseQuery()
                ->select('pools.id')
                ->distinct()
                ->limit(5000)
                ->pluck('pools.id')
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
     * @return array<int,int> pools.id
     */
    private function basePoolIdsForDeepOptions(array $overrides): array
    {
        return $this->basePoolIdsForTraceOptions($overrides);
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
     * @return array<int,int> pools.id
     */
    private function poolIdsFromUpstreamSeeds(string $upstreamType, array $upstreamSeedIds): array
    {
        $reachability = app(PrimarySampleReachability::class);
        $maxDepth = $upstreamType === Pools::class ? 10 : 6;

        return $reachability->poolIdsFromSeed($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth);
    }

    private function traceDeepAnimalSpeciesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->basePoolIdsForDeepOptions(['traceDeepAnimalSpeciesFilter' => '']);
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

                $ids = $this->poolIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
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

        $baseIds = $this->basePoolIdsForDeepOptions(['traceDeepAnimalSexFilter' => '']);
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

                $ids = $this->poolIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
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

        $baseIds = $this->basePoolIdsForDeepOptions(['traceDeepAnimalAgeFilter' => '']);
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

                $ids = $this->poolIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
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

        $baseIds = $this->basePoolIdsForDeepOptions(['traceDeepHumanEthnicityFilter' => '']);
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

                $ids = $this->poolIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
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

        $baseIds = $this->basePoolIdsForDeepOptions(['traceDeepHumanOccupationFilter' => '']);
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

                $ids = $this->poolIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
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

        $baseIds = $this->basePoolIdsForDeepOptions(['traceDeepHumanCountryFilter' => '']);
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

                $ids = $this->poolIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
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

        $baseIds = $this->basePoolIdsForTraceOptions(['tracePrimaryAnimalSpeciesFilter' => '']);
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
                $ids = $reachability->poolIdsFromPrimary(AnimalSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

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

        $baseIds = $this->basePoolIdsForTraceOptions(['tracePrimaryAnimalSexFilter' => '']);
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
                $ids = $reachability->poolIdsFromPrimary(AnimalSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

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

        $baseIds = $this->basePoolIdsForTraceOptions(['tracePrimaryAnimalAgeFilter' => '']);
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
                $ids = $reachability->poolIdsFromPrimary(AnimalSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

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

        $baseIds = $this->basePoolIdsForTraceOptions(['tracePrimaryHumanEthnicityFilter' => '']);
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
                $ids = $reachability->poolIdsFromPrimary(HumanSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

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

        $baseIds = $this->basePoolIdsForTraceOptions(['tracePrimaryHumanOccupationFilter' => '']);
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
                $ids = $reachability->poolIdsFromPrimary(HumanSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

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

        $baseIds = $this->basePoolIdsForTraceOptions(['tracePrimaryHumanCountryFilter' => '']);
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
                $ids = $reachability->poolIdsFromPrimary(HumanSamples::class, $primaryIds, $this->projectId, $this->isGuestMode());

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

    private function timelineCounts($base): array
    {
        $driver = DB::getDriverName();
        $expr = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT(pools.date_pooled, '%Y')",
                'pgsql' => "to_char(pools.date_pooled, 'YYYY')",
                default => "strftime('%Y', pools.date_pooled)",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT(pools.date_pooled, '%Y-%m')",
            'pgsql' => "to_char(pools.date_pooled, 'YYYY-MM')",
            default => "strftime('%Y-%m', pools.date_pooled)",
        };

        $rows = $base
            ->select(DB::raw($expr.' as ym'), DB::raw('COUNT(pools.id) as c'))
            ->whereNotNull('pools.date_pooled')
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

    private function allLaboratories()
    {
        $base = Pools::query()->leftJoin('laboratories', 'pools.laboratories_id', '=', 'laboratories.id');

        if ($this->isGuestMode()) {
            $base->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'pools.id')
                    ->where('tubes.tubes_content_type', Pools::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $base->where('pools.projects_id', $this->projectId);
        }

        return $base->distinct()->orderBy('laboratories.name')->pluck('laboratories.name')->filter()->values();
    }

    private function allSubProjects()
    {
        $base = $this->baseQuery();

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', Pools::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('pools.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    private function dashboardPayload(): array
    {
        $base = $this->baseQuery();

        $now = Carbon::now();
        $startOfYear = $now->copy()->startOfYear()->toDateString();
        $endOfYear = $now->copy()->endOfYear()->toDateString();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $totalPools = (clone $base)->count('pools.id');

        $poolsThisYear = (clone $base)->whereBetween('pools.date_pooled', [$startOfYear, $endOfYear])->count('pools.id');
        $poolsThisMonth = (clone $base)->whereBetween('pools.date_pooled', [$startOfMonth, $endOfMonth])->count('pools.id');

        $poolsWithTubes = (clone $base)->whereExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('tubes')
                ->whereColumn('tubes.tubes_content_id', 'pools.id')
                ->where('tubes.tubes_content_type', Pools::class);
        })->count('pools.id');

        $poolsByLaboratory = (clone $base)
            ->select('laboratories.name as k', DB::raw('COUNT(pools.id) as c'))
            ->whereNotNull('laboratories.name')
            ->groupBy('laboratories.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $descriptive_stats = [
            'total_pools' => $totalPools,
            'pools_with_tubes' => $poolsWithTubes,
            'pools_this_year' => $poolsThisYear,
            'pools_this_month' => $poolsThisMonth,
            'pooling_timeline' => $this->timelineCounts(clone $base),
        ];

        return [
            'descriptive_stats' => $descriptive_stats,
            'poolsByLaboratory' => $poolsByLaboratory,
            'mapPointsUrl' => route('pools.dashboard.map-points'),
            'activeFilters' => [
                'contentTypeFilter' => $this->contentTypeFilter,
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
                'minNrPooled' => $this->minNrPooled,
                'maxNrPooled' => $this->maxNrPooled,
                'laboratoryFilter' => $this->laboratoryFilter,
                'subProjectFilter' => $this->subProjectFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ],
            'availableContentTypes' => $this->availableContentTypes(),
            'availableTracePrimaryTypes' => $this->availableTracePrimaryTypes(),
            'allLaboratories' => $this->allLaboratories(),
            'allSubProjects' => $this->allSubProjects(),
            'allAnimalSpecies' => $this->allAnimalSpecies(),
            'tracePrimaryAnimalSpeciesOptions' => $this->tracePrimaryAnimalSpeciesOptions(),
            'tracePrimaryAnimalSexesOptions' => $this->tracePrimaryAnimalSexesOptions(),
            'tracePrimaryAnimalAgesOptions' => $this->tracePrimaryAnimalAgesOptions(),
            'tracePrimaryHumanEthnicitiesOptions' => $this->tracePrimaryHumanEthnicitiesOptions(),
            'tracePrimaryHumanOccupationsOptions' => $this->tracePrimaryHumanOccupationsOptions(),
            'tracePrimaryHumanCountriesOptions' => $this->tracePrimaryHumanCountriesOptions(),
            'tracePrimaryParasiteSpeciesOptions' => $this->tracePrimaryParasiteSpeciesOptions(),
            'tracePrimaryCultureTypesOptions' => $this->tracePrimaryCultureTypesOptions(),
            'tracePrimaryCultureMediumsOptions' => $this->tracePrimaryCultureMediumsOptions(),
            'tracePrimaryNucleicTypesOptions' => $this->tracePrimaryNucleicTypesOptions(),
            'availableTraceDeepPrimaryTypes' => $this->availableTraceDeepPrimaryTypes(),
            'traceDeepAnimalSpeciesOptions' => $this->traceDeepAnimalSpeciesOptions(),
            'traceDeepAnimalSexesOptions' => $this->traceDeepAnimalSexesOptions(),
            'traceDeepAnimalAgesOptions' => $this->traceDeepAnimalAgesOptions(),
            'traceDeepHumanEthnicitiesOptions' => $this->traceDeepHumanEthnicitiesOptions(),
            'traceDeepHumanOccupationsOptions' => $this->traceDeepHumanOccupationsOptions(),
            'traceDeepHumanCountriesOptions' => $this->traceDeepHumanCountriesOptions(),
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $this->canEdit(),
        ];
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

        $this->applyVisibilityToSeedQuery($q, 'parasite_samples', ParasiteSamples::class);

        return $q->distinct()->orderBy('parasite_species.name_scientific')->pluck('parasite_species.name_scientific')->filter()->values();
    }

    private function allCultureTypesForTrace()
    {
        $q = Cultures::query()->whereNotNull('cultures.type')->select('cultures.type');
        $this->applyVisibilityToSeedQuery($q, 'cultures', Cultures::class);

        return $q->distinct()->orderBy('cultures.type')->pluck('cultures.type')->filter()->values();
    }

    private function allCultureMediumsForTrace()
    {
        $q = Cultures::query()->whereNotNull('cultures.medium')->select('cultures.medium');
        $this->applyVisibilityToSeedQuery($q, 'cultures', Cultures::class);

        return $q->distinct()->orderBy('cultures.medium')->pluck('cultures.medium')->filter()->values();
    }

    private function allNucleicTypesForTrace()
    {
        $q = NucleicAcids::query()->whereNotNull('nucleic_acids.type')->select('nucleic_acids.type');
        $this->applyVisibilityToSeedQuery($q, 'nucleic_acids', NucleicAcids::class);

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
            if ((clone $this->baseQuery())->select('pools.id')->limit(1)->exists()) {
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
        $candidates = $this->allCultureTypesForTrace();
        $this->tracePrimaryCultureTypeFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryCultureTypeFilter = (string) $candidate;
            if ((clone $this->baseQuery())->select('pools.id')->limit(1)->exists()) {
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
        $candidates = $this->allCultureMediumsForTrace();
        $this->tracePrimaryCultureMediumFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryCultureMediumFilter = (string) $candidate;
            if ((clone $this->baseQuery())->select('pools.id')->limit(1)->exists()) {
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
            if ((clone $this->baseQuery())->select('pools.id')->limit(1)->exists()) {
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
            if ((clone $this->baseQuery())->select('pools.id')->limit(1)->exists()) {
                $out[$k] = $label;
            }
        }

        $this->traceDeepPrimaryTypeFilter = $prev;

        if ($prev !== 'all' && ! isset($out[$prev])) {
            $out = [$prev => ucfirst($prev)] + $out;
        }

        return $out;
    }

    public function render()
    {
        return view('livewire.pools-dashboard', $this->dashboardPayload());
    }
}
