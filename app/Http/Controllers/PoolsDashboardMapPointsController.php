<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Services\PrimarySampleReachability;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoolsDashboardMapPointsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $cursor = (int) $request->query('cursor', 0);
        $limit = (int) $request->query('limit', 1500);
        $limit = max(100, min($limit, 2000));

        $laboratoryFilter = (string) $request->query('laboratoryFilter', '');
        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        $contentTypeFilter = (string) $request->query('contentTypeFilter', 'all');
        $tracePrimaryTypeFilter = (string) $request->query('tracePrimaryTypeFilter', 'all');
        $tracePrimaryAnimalSpeciesFilter = (string) $request->query('tracePrimaryAnimalSpeciesFilter', '');
        $tracePrimaryAnimalSexFilter = (string) $request->query('tracePrimaryAnimalSexFilter', '');
        $tracePrimaryAnimalAgeFilter = (string) $request->query('tracePrimaryAnimalAgeFilter', '');
        $tracePrimaryHumanEthnicityFilter = (string) $request->query('tracePrimaryHumanEthnicityFilter', '');
        $tracePrimaryHumanOccupationFilter = (string) $request->query('tracePrimaryHumanOccupationFilter', '');
        $tracePrimaryHumanCountryFilter = (string) $request->query('tracePrimaryHumanCountryFilter', '');
        $tracePrimaryParasiteSpeciesFilter = (string) $request->query('tracePrimaryParasiteSpeciesFilter', '');
        $tracePrimaryCultureTypeFilter = (string) $request->query('tracePrimaryCultureTypeFilter', '');
        $tracePrimaryCultureMediumFilter = (string) $request->query('tracePrimaryCultureMediumFilter', '');
        $tracePrimaryNucleicTypeFilter = (string) $request->query('tracePrimaryNucleicTypeFilter', '');
        $tracePrimaryPoolMinNrPooled = $request->query('tracePrimaryPoolMinNrPooled');
        $tracePrimaryPoolMaxNrPooled = $request->query('tracePrimaryPoolMaxNrPooled');
        $traceDeepPrimaryTypeFilter = (string) $request->query('traceDeepPrimaryTypeFilter', 'all');
        $traceDeepAnimalSpeciesFilter = (string) $request->query('traceDeepAnimalSpeciesFilter', '');
        $traceDeepAnimalSexFilter = (string) $request->query('traceDeepAnimalSexFilter', '');
        $traceDeepAnimalAgeFilter = (string) $request->query('traceDeepAnimalAgeFilter', '');
        $traceDeepHumanEthnicityFilter = (string) $request->query('traceDeepHumanEthnicityFilter', '');
        $traceDeepHumanOccupationFilter = (string) $request->query('traceDeepHumanOccupationFilter', '');
        $traceDeepHumanCountryFilter = (string) $request->query('traceDeepHumanCountryFilter', '');
        $minNrPooled = $request->query('minNrPooled');
        $maxNrPooled = $request->query('maxNrPooled');

        $query = Pools::query()
            ->leftJoin('laboratories', 'pools.laboratories_id', '=', 'laboratories.id');

        if ($isGuestMode) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'pools.id')
                    ->where('tubes.tubes_content_type', Pools::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('pools.projects_id', $projectId);
        }

        if ($laboratoryFilter !== '') {
            $query->where('laboratories.name', $laboratoryFilter);
        }

        if ($contentTypeFilter !== '' && $contentTypeFilter !== 'all') {
            $contentType = match ($contentTypeFilter) {
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
                        ->where('pool_contents.samples_type', $contentType);
                });
            }
        }

        $hasDeepTrace = $traceDeepPrimaryTypeFilter !== 'all'
            || $traceDeepAnimalSpeciesFilter !== ''
            || $traceDeepAnimalSexFilter !== ''
            || $traceDeepAnimalAgeFilter !== ''
            || $traceDeepHumanEthnicityFilter !== ''
            || $traceDeepHumanOccupationFilter !== ''
            || $traceDeepHumanCountryFilter !== '';

        $hasAnyTrace = $tracePrimaryTypeFilter !== 'all'
            || $tracePrimaryAnimalSpeciesFilter !== ''
            || $tracePrimaryAnimalSexFilter !== ''
            || $tracePrimaryAnimalAgeFilter !== ''
            || $tracePrimaryHumanEthnicityFilter !== ''
            || $tracePrimaryHumanOccupationFilter !== ''
            || $tracePrimaryHumanCountryFilter !== ''
            || $tracePrimaryParasiteSpeciesFilter !== ''
            || $tracePrimaryCultureTypeFilter !== ''
            || $tracePrimaryCultureMediumFilter !== ''
            || $tracePrimaryNucleicTypeFilter !== ''
            || $tracePrimaryPoolMinNrPooled !== null
            || $tracePrimaryPoolMaxNrPooled !== null
            || $hasDeepTrace;

        if ($hasAnyTrace) {
            $upstreamType = match ($tracePrimaryTypeFilter) {
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
                $upstreamSeedIds = $this->seedIdsForTraceUpstream(
                    $upstreamType,
                    projectId: $projectId,
                    isGuestMode: $isGuestMode,
                    tracePrimaryAnimalSpeciesFilter: $tracePrimaryAnimalSpeciesFilter,
                    tracePrimaryAnimalSexFilter: $tracePrimaryAnimalSexFilter,
                    tracePrimaryAnimalAgeFilter: $tracePrimaryAnimalAgeFilter,
                    tracePrimaryHumanEthnicityFilter: $tracePrimaryHumanEthnicityFilter,
                    tracePrimaryHumanOccupationFilter: $tracePrimaryHumanOccupationFilter,
                    tracePrimaryHumanCountryFilter: $tracePrimaryHumanCountryFilter,
                    tracePrimaryParasiteSpeciesFilter: $tracePrimaryParasiteSpeciesFilter,
                    tracePrimaryCultureTypeFilter: $tracePrimaryCultureTypeFilter,
                    tracePrimaryCultureMediumFilter: $tracePrimaryCultureMediumFilter,
                    tracePrimaryNucleicTypeFilter: $tracePrimaryNucleicTypeFilter,
                    tracePrimaryPoolMinNrPooled: $tracePrimaryPoolMinNrPooled,
                    tracePrimaryPoolMaxNrPooled: $tracePrimaryPoolMaxNrPooled,
                );

                if ($upstreamSeedIds === []) {
                    $query->whereRaw('1 = 0');
                } else {
                    if ($hasDeepTrace && in_array($tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
                        $deepPrimaryType = match ($traceDeepPrimaryTypeFilter) {
                            'human' => HumanSamples::class,
                            'animal' => AnimalSamples::class,
                            'environment' => EnvironmentSamples::class,
                            default => null,
                        };

                        if ($deepPrimaryType) {
                            $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(
                                $deepPrimaryType,
                                projectId: $projectId,
                                isGuestMode: $isGuestMode,
                                animalSpecies: $traceDeepAnimalSpeciesFilter,
                                animalSex: $traceDeepAnimalSexFilter,
                                animalAge: $traceDeepAnimalAgeFilter,
                                humanEthnicity: $traceDeepHumanEthnicityFilter,
                                humanOccupation: $traceDeepHumanOccupationFilter,
                                humanCountry: $traceDeepHumanCountryFilter,
                            );

                            $reachability = app(PrimarySampleReachability::class);
                            $reachableUpstream = match ($upstreamType) {
                                ParasiteSamples::class => $reachability->parasiteSampleIdsFromSeed($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
                                Cultures::class => $reachability->cultureIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
                                NucleicAcids::class => $reachability->nucleicIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
                                Pools::class => $reachability->poolIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
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

                    $ids = in_array($tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
                        ? $reachability->poolIdsFromPrimary($upstreamType, $upstreamSeedIds, $projectId, $isGuestMode, $maxDepth)
                        : $reachability->poolIdsFromSeed($upstreamType, $upstreamSeedIds, $projectId, $isGuestMode, $maxDepth);

                    if ($ids === []) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('pools.id', $ids);
                    }
                }
            }
        }

        if ($minNrPooled !== null && $minNrPooled !== '' && is_numeric($minNrPooled)) {
            $query->where('pools.nr_pooled', '>=', (int) $minNrPooled);
        }

        if ($maxNrPooled !== null && $maxNrPooled !== '' && is_numeric($maxNrPooled)) {
            $query->where('pools.nr_pooled', '<=', (int) $maxNrPooled);
        }

        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('pools.date_pooled', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('pools.date_pooled', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('pools.date_pooled', '<=', $endDate);
        }

        $pools = $query
            ->select([
                'pools.id',
                'pools.code',
                'pools.nr_pooled',
                'pools.date_pooled',
                'laboratories.name as laboratory_name',
            ])
            ->where('pools.id', '>', $cursor)
            ->orderBy('pools.id')
            ->limit($limit)
            ->get();

        $poolModels = Pools::query()
            ->whereIn('id', $pools->pluck('id'))
            ->with([
                'pool_contents.samples' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        AnimalSamples::class => ['sampling_sites:id,latitude,longitude,name'],
                        HumanSamples::class => ['sampling_sites:id,latitude,longitude,name'],
                        EnvironmentSamples::class => ['sampling_sites:id,latitude,longitude,name'],
                    ]);
                },
            ])
            ->get()
            ->keyBy('id');

        $points = [];
        foreach ($pools as $row) {
            $pool = $poolModels->get($row->id);
            if (! $pool) {
                continue;
            }

            [$lat, $lng] = $this->extractCoordinatesFromPool($pool);
            if (! $lat || ! $lng) {
                continue;
            }

            $points[] = [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'code' => $row->code,
                'nr_pooled' => (int) $row->nr_pooled,
                'date_pooled' => $row->date_pooled,
                'laboratory' => $row->laboratory_name ?? 'Unknown',
            ];
        }

        $nextCursor = null;
        if ($pools->count() === $limit) {
            $nextCursor = (int) $pools->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }

    /**
     * @param  class-string  $upstreamType
     * @return array<int,int>
     */
    private function seedIdsForTraceUpstream(
        string $upstreamType,
        ?int $projectId,
        bool $isGuestMode,
        string $tracePrimaryAnimalSpeciesFilter,
        string $tracePrimaryAnimalSexFilter,
        string $tracePrimaryAnimalAgeFilter,
        string $tracePrimaryHumanEthnicityFilter,
        string $tracePrimaryHumanOccupationFilter,
        string $tracePrimaryHumanCountryFilter,
        string $tracePrimaryParasiteSpeciesFilter,
        string $tracePrimaryCultureTypeFilter,
        string $tracePrimaryCultureMediumFilter,
        string $tracePrimaryNucleicTypeFilter,
        $tracePrimaryPoolMinNrPooled,
        $tracePrimaryPoolMaxNrPooled,
    ): array {
        if (in_array($upstreamType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
            return $this->primarySampleIdsForTracing(
                $upstreamType,
                projectId: $projectId,
                isGuestMode: $isGuestMode,
                animalSpecies: $tracePrimaryAnimalSpeciesFilter,
                animalSex: $tracePrimaryAnimalSexFilter,
                animalAge: $tracePrimaryAnimalAgeFilter,
                humanEthnicity: $tracePrimaryHumanEthnicityFilter,
                humanOccupation: $tracePrimaryHumanOccupationFilter,
                humanCountry: $tracePrimaryHumanCountryFilter,
            );
        }

        if ($upstreamType === ParasiteSamples::class) {
            $q = ParasiteSamples::query()
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                ->select('parasite_samples.id');

            if ($tracePrimaryParasiteSpeciesFilter !== '') {
                $q->where('parasite_species.name_scientific', $tracePrimaryParasiteSpeciesFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'parasite_samples', ParasiteSamples::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('parasite_samples.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Cultures::class) {
            $q = Cultures::query()->select('cultures.id');

            if ($tracePrimaryCultureTypeFilter !== '') {
                $q->where('cultures.type', $tracePrimaryCultureTypeFilter);
            }
            if ($tracePrimaryCultureMediumFilter !== '') {
                $q->where('cultures.medium', $tracePrimaryCultureMediumFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'cultures', Cultures::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('cultures.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === NucleicAcids::class) {
            $q = NucleicAcids::query()->select('nucleic_acids.id');

            if ($tracePrimaryNucleicTypeFilter !== '') {
                $q->where('nucleic_acids.type', $tracePrimaryNucleicTypeFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'nucleic_acids', NucleicAcids::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('nucleic_acids.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Pools::class) {
            $q = Pools::query()->select('pools.id');

            if ($tracePrimaryPoolMinNrPooled !== null && $tracePrimaryPoolMinNrPooled !== '') {
                $q->where('pools.nr_pooled', '>=', (int) $tracePrimaryPoolMinNrPooled);
            }
            if ($tracePrimaryPoolMaxNrPooled !== null && $tracePrimaryPoolMaxNrPooled !== '') {
                $q->where('pools.nr_pooled', '<=', (int) $tracePrimaryPoolMaxNrPooled);
            }

            $this->applyVisibilityToSeedQuery($q, 'pools', Pools::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('pools.id')->map(fn ($v) => (int) $v)->all();
        }

        return [];
    }

    private function applyVisibilityToSeedQuery($q, string $table, string $type, ?int $projectId, bool $isGuestMode): void
    {
        if ($isGuestMode) {
            $q->whereExists(function ($sub) use ($table, $type) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                    ->where('tubes.is_private', false);
            });

            return;
        }

        if ($projectId) {
            $q->where(function ($w) use ($table, $type, $projectId) {
                $w->where($table.'.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($table, $type, $projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                            ->where('tubes.projects_id', $projectId);
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

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForTracing(
        string $primaryType,
        ?int $projectId,
        bool $isGuestMode,
        string $animalSpecies,
        string $animalSex,
        string $animalAge,
        string $humanEthnicity,
        string $humanOccupation,
        string $humanCountry,
    ): array {
        $table = (new $primaryType)->getTable();
        $q = $primaryType::query()->select($table.'.id');

        if ($primaryType === AnimalSamples::class) {
            $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

            if ($animalSpecies !== '') {
                $q->where('animal_species.name_common', $animalSpecies);
            }
            if ($animalSex !== '') {
                $q->where('animals.sex', $animalSex);
            }
            if ($animalAge !== '') {
                $q->where('animals.age', $animalAge);
            }
        }

        if ($primaryType === HumanSamples::class) {
            $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

            if ($humanEthnicity !== '') {
                $q->where('humans.ethnicity', $humanEthnicity);
            }
            if ($humanOccupation !== '') {
                $q->where('humans.occupation', $humanOccupation);
            }
            if ($humanCountry !== '') {
                $q->where('countries.name', $humanCountry);
            }
        }

        if ($isGuestMode) {
            $q->whereExists(function ($sub) use ($primaryType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                    ->where('tubes.is_private', false);
            });
        } elseif ($projectId) {
            $q->where(function ($w) use ($primaryType, $table, $projectId) {
                $w->where($table.'.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($primaryType, $table, $projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                            ->where('tubes.projects_id', $projectId);
                    });
            });
        }

        return $q->limit(8000)->pluck($table.'.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForDeepTracing(
        string $primaryType,
        ?int $projectId,
        bool $isGuestMode,
        string $animalSpecies,
        string $animalSex,
        string $animalAge,
        string $humanEthnicity,
        string $humanOccupation,
        string $humanCountry,
    ): array {
        return $this->primarySampleIdsForTracing(
            $primaryType,
            projectId: $projectId,
            isGuestMode: $isGuestMode,
            animalSpecies: $animalSpecies,
            animalSex: $animalSex,
            animalAge: $animalAge,
            humanEthnicity: $humanEthnicity,
            humanOccupation: $humanOccupation,
            humanCountry: $humanCountry,
        );
    }

    /**
     * @return array{0:?float,1:?float}
     */
    private function extractCoordinatesFromPool(Pools $pool): array
    {
        $first = $pool->pool_contents->first();
        if (! $first || ! $first->samples) {
            return [null, null];
        }

        $sample = $first->samples;

        if (isset($sample->latitude, $sample->longitude) && $sample->latitude && $sample->longitude) {
            return [(float) $sample->latitude, (float) $sample->longitude];
        }

        if (method_exists($sample, 'sampling_sites') && $sample->sampling_sites) {
            $site = $sample->sampling_sites;

            if ($site->latitude && $site->longitude) {
                return [(float) $site->latitude, (float) $site->longitude];
            }
        }

        return [null, null];
    }
}
