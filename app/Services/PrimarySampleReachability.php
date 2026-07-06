<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrimarySampleReachability
{
    /**
     * Some polymorphic type columns in this app can contain different representations:
     * - Fully qualified class: App\Models\Foo
     * - "Compressed" class: AppModelsFoo
     * - Sometimes basename: Foo
     *
     * @return array<int,string>
     */
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
     * Trace from primary samples to nucleic acids through:
     * primary -> parasite_samples -> cultures -> pools -> nucleic_acids (and recursive nucleic_acids).
     *
     * This is used for conditional dashboard filters that must "trace back to primary samples".
     *
     * @param  class-string  $primaryType
     * @param  array<int,int>  $primaryIds
     * @return array<int,int> nucleic_acids.id
     */
    public function nucleicIdsFromPrimary(string $primaryType, array $primaryIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($primaryType, $primaryIds, $projectId, $isGuestMode, $maxDepth)->get(NucleicAcids::class, []);
    }

    /**
     * @param  class-string  $primaryType
     * @param  array<int,int>  $primaryIds
     * @return array<int,int> cultures.id
     */
    public function cultureIdsFromPrimary(string $primaryType, array $primaryIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($primaryType, $primaryIds, $projectId, $isGuestMode, $maxDepth)->get(Cultures::class, []);
    }

    /**
     * @param  class-string  $primaryType
     * @param  array<int,int>  $primaryIds
     * @return array<int,int> pools.id
     */
    public function poolIdsFromPrimary(string $primaryType, array $primaryIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($primaryType, $primaryIds, $projectId, $isGuestMode, $maxDepth)->get(Pools::class, []);
    }

    /**
     * @param  class-string  $primaryType
     * @param  array<int,int>  $primaryIds
     * @return array<int,int> experiments.id
     */
    public function experimentIdsFromPrimary(string $primaryType, array $primaryIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($primaryType, $primaryIds, $projectId, $isGuestMode, $maxDepth)->get(Experiments::class, []);
    }

    /**
     * Trace from any seed node type to experiments through the same graph.
     *
     * @param  class-string  $seedType
     * @param  array<int,int>  $seedIds
     * @return array<int,int> experiments.id
     */
    public function experimentIdsFromSeed(string $seedType, array $seedIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($seedType, $seedIds, $projectId, $isGuestMode, $maxDepth)->get(Experiments::class, []);
    }

    /**
     * Trace from any seed node type to nucleic acids through the same graph.
     *
     * @param  class-string  $seedType
     * @param  array<int,int>  $seedIds
     * @return array<int,int> nucleic_acids.id
     */
    public function nucleicIdsFromSeed(string $seedType, array $seedIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($seedType, $seedIds, $projectId, $isGuestMode, $maxDepth)->get(NucleicAcids::class, []);
    }

    /**
     * Trace from any seed node type to cultures through the same graph.
     *
     * @param  class-string  $seedType
     * @param  array<int,int>  $seedIds
     * @return array<int,int> cultures.id
     */
    public function cultureIdsFromSeed(string $seedType, array $seedIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($seedType, $seedIds, $projectId, $isGuestMode, $maxDepth)->get(Cultures::class, []);
    }

    /**
     * Trace from any seed node type to pools through the same graph.
     *
     * @param  class-string  $seedType
     * @param  array<int,int>  $seedIds
     * @return array<int,int> pools.id
     */
    public function poolIdsFromSeed(string $seedType, array $seedIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($seedType, $seedIds, $projectId, $isGuestMode, $maxDepth)->get(Pools::class, []);
    }

    /**
     * @param  class-string  $seedType
     * @param  array<int,int>  $seedIds
     * @return array<int,int> parasite_samples.id
     */
    public function parasiteSampleIdsFromSeed(string $seedType, array $seedIds, ?int $projectId, bool $isGuestMode, int $maxDepth = 6): array
    {
        return $this->graphFromPrimary($seedType, $seedIds, $projectId, $isGuestMode, $maxDepth)->get(ParasiteSamples::class, []);
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    public function primarySampleIdsAtSamplingSiteName(
        string $primaryType,
        string $siteName,
        ?int $projectId,
        bool $isGuestMode
    ): array {
        if ($siteName === '') {
            return [];
        }

        $table = (new $primaryType)->getTable();

        $q = DB::table($table)
            ->join('sampling_sites', "{$table}.sampling_sites_id", '=', 'sampling_sites.id')
            ->where('sampling_sites.name', $siteName)
            ->select("{$table}.id");

        if ($isGuestMode) {
            $q->whereExists(function ($sub) use ($primaryType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', "{$table}.id")
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                    ->where('tubes.is_private', false);
            });
        } elseif ($projectId) {
            $q->where(function ($w) use ($projectId, $primaryType, $table) {
                $w->where("{$table}.projects_id", $projectId)
                    ->orWhereExists(function ($sub) use ($projectId, $primaryType, $table) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', "{$table}.id")
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                            ->where('tubes.projects_id', $projectId);
                    });
            });
        }

        return $q->pluck("{$table}.id")->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param  class-string|null  $scopedPrimaryType
     * @return array<int,int>
     */
    public function experimentIdsFromSamplingSiteName(
        string $siteName,
        ?int $projectId,
        bool $isGuestMode,
        ?string $scopedPrimaryType = null
    ): array {
        if ($siteName === '') {
            return [];
        }

        $primaryTypes = $scopedPrimaryType !== null
            ? [$scopedPrimaryType]
            : [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class];

        $experimentIds = [];

        foreach ($primaryTypes as $primaryType) {
            $primaryIds = $this->primarySampleIdsAtSamplingSiteName($primaryType, $siteName, $projectId, $isGuestMode);
            if ($primaryIds === []) {
                continue;
            }

            $experimentIds = array_merge(
                $experimentIds,
                $this->experimentIdsFromPrimary($primaryType, $primaryIds, $projectId, $isGuestMode)
            );
        }

        return array_values(array_unique(array_map('intval', $experimentIds)));
    }

    /**
     * @param  class-string  $primaryType
     * @param  array<int,int>  $primaryIds
     * @return Collection<class-string,array<int,int>>
     */
    private function graphFromPrimary(string $primaryType, array $primaryIds, ?int $projectId, bool $isGuestMode, int $maxDepth): Collection
    {
        $primaryIds = array_values(array_unique(array_map('intval', $primaryIds)));
        if ($primaryIds === []) {
            return collect([
                HumanSamples::class => [],
                AnimalSamples::class => [],
                EnvironmentSamples::class => [],
                ParasiteSamples::class => [],
                Cultures::class => [],
                Pools::class => [],
                NucleicAcids::class => [],
                Experiments::class => [],
            ]);
        }

        $nodes = collect([
            HumanSamples::class => [],
            AnimalSamples::class => [],
            EnvironmentSamples::class => [],
            ParasiteSamples::class => [],
            Cultures::class => [],
            Pools::class => [],
            NucleicAcids::class => [],
            Experiments::class => [],
        ]);

        $nodes[$primaryType] = $primaryIds;
        $frontier = collect([$primaryType => $primaryIds]);

        $depth = 0;
        while ($frontier->isNotEmpty() && $depth < $maxDepth) {
            $depth++;

            // 1) parasite_samples from primary origins
            $parasiteIds = [];
            foreach ([HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class] as $t) {
                $ids = (array) ($frontier->get($t) ?? []);
                if ($ids === []) {
                    continue;
                }

                $parasiteIds = array_merge($parasiteIds, $this->chunked(function (array $chunk) use ($t, $projectId, $isGuestMode) {
                    $q = ParasiteSamples::query()
                        ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                        ->whereIn('parasites.parasites_origin_type', $this->typeVariants($t))
                        ->whereIn('parasites.parasites_origin_id', $chunk);

                    if ($isGuestMode) {
                        $q->whereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('tubes')
                                ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                                ->where('tubes.tubes_content_type', ParasiteSamples::class)
                                ->where('tubes.is_private', false);
                        });
                    } elseif ($projectId) {
                        $q->where(function ($w) use ($projectId) {
                            $w->where('parasite_samples.projects_id', $projectId)
                                ->orWhereExists(function ($sub) use ($projectId) {
                                    $sub->select(DB::raw(1))
                                        ->from('tubes')
                                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                                        ->whereIn('tubes.tubes_content_type', $this->typeVariants(ParasiteSamples::class))
                                        ->where('tubes.projects_id', $projectId);
                                });
                        });
                    }

                    return $q->pluck('parasite_samples.id')->map(fn ($v) => (int) $v)->all();
                }, $ids));
            }

            $parasiteIds = array_values(array_unique($parasiteIds));
            $newParasites = array_values(array_diff($parasiteIds, (array) $nodes[ParasiteSamples::class]));
            if ($newParasites !== []) {
                $nodes[ParasiteSamples::class] = array_values(array_unique(array_merge((array) $nodes[ParasiteSamples::class], $newParasites)));
            }

            // 2) cultures from anything in frontier
            $newCultures = $this->culturesFromFrontier($frontier, $nodes, $projectId, $isGuestMode);
            if ($newCultures !== []) {
                $nodes[Cultures::class] = array_values(array_unique(array_merge((array) $nodes[Cultures::class], $newCultures)));
            }

            // 3) pools from anything in frontier
            $newPools = $this->poolsFromFrontier($frontier, $nodes, $projectId, $isGuestMode);
            if ($newPools !== []) {
                $nodes[Pools::class] = array_values(array_unique(array_merge((array) $nodes[Pools::class], $newPools)));
            }

            // 4) nucleic acids from anything in frontier
            $newNucleics = $this->nucleicsFromFrontier($frontier, $nodes, $projectId, $isGuestMode);
            if ($newNucleics !== []) {
                $nodes[NucleicAcids::class] = array_values(array_unique(array_merge((array) $nodes[NucleicAcids::class], $newNucleics)));
            }

            // 5) experiments from anything in frontier
            $newExperiments = $this->experimentsFromFrontier($frontier, $nodes, $projectId, $isGuestMode);
            if ($newExperiments !== []) {
                $nodes[Experiments::class] = array_values(array_unique(array_merge((array) $nodes[Experiments::class], $newExperiments)));
            }

            $next = collect();
            if ($newParasites !== []) {
                $next[ParasiteSamples::class] = $newParasites;
            }
            if ($newCultures !== []) {
                $next[Cultures::class] = $newCultures;
            }
            if ($newPools !== []) {
                $next[Pools::class] = $newPools;
            }
            if ($newNucleics !== []) {
                $next[NucleicAcids::class] = $newNucleics;
            }
            if ($newExperiments !== []) {
                $next[Experiments::class] = $newExperiments;
            }

            $frontier = $next;
        }

        return $nodes->map(fn ($ids) => array_values(array_unique(array_map('intval', (array) $ids))));
    }

    /**
     * @param  Collection<string,array<int,int>>  $frontier
     * @param  Collection<string,array<int,int>>  $nodes
     * @return array<int,int>
     */
    private function culturesFromFrontier(Collection $frontier, Collection $nodes, ?int $projectId, bool $isGuestMode): array
    {
        $new = [];

        foreach ($frontier as $type => $ids) {
            $ids = array_values(array_unique(array_map('intval', (array) $ids)));
            if ($ids === []) {
                continue;
            }

            $found = $this->chunked(function (array $chunk) use ($type, $projectId, $isGuestMode) {
                $q = Cultures::query()
                    ->whereIn('cultures_content_type', $this->typeVariants((string) $type))
                    ->whereIn('cultures_content_id', $chunk);

                if ($isGuestMode) {
                    $q->whereHas('tubes', fn ($t) => $t->where('is_private', false));
                } elseif ($projectId) {
                    $q->where(function ($w) use ($projectId) {
                        $w->where('cultures.projects_id', $projectId)
                            ->orWhereExists(function ($sub) use ($projectId) {
                                $sub->select(DB::raw(1))
                                    ->from('tubes')
                                    ->whereColumn('tubes.tubes_content_id', 'cultures.id')
                                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(Cultures::class))
                                    ->where('tubes.projects_id', $projectId);
                            });
                    });
                }

                return $q->pluck('id')->map(fn ($v) => (int) $v)->all();
            }, $ids);

            $new = array_merge($new, $found);
        }

        $new = array_values(array_unique($new));

        return array_values(array_diff($new, (array) $nodes[Cultures::class]));
    }

    /**
     * @param  Collection<string,array<int,int>>  $frontier
     * @param  Collection<string,array<int,int>>  $nodes
     * @return array<int,int>
     */
    private function poolsFromFrontier(Collection $frontier, Collection $nodes, ?int $projectId, bool $isGuestMode): array
    {
        $new = [];

        foreach ($frontier as $type => $ids) {
            $ids = array_values(array_unique(array_map('intval', (array) $ids)));
            if ($ids === []) {
                continue;
            }

            $poolIds = $this->chunked(function (array $chunk) use ($type, $projectId, $isGuestMode) {
                $q = DB::table('pool_contents')
                    ->select('pool_contents.pools_id')
                    ->whereIn('pool_contents.samples_type', $this->typeVariants((string) $type))
                    ->whereIn('pool_contents.samples_id', $chunk);

                $ids = $q->pluck('pool_contents.pools_id')->map(fn ($v) => (int) $v)->all();
                if ($ids === []) {
                    return [];
                }

                $poolQuery = Pools::query()->whereIn('id', $ids);
                if ($isGuestMode) {
                    $poolQuery->whereHas('tubes', fn ($t) => $t->where('is_private', false));
                } elseif ($projectId) {
                    $poolQuery->where(function ($w) use ($projectId) {
                        $w->where('pools.projects_id', $projectId)
                            ->orWhereExists(function ($sub) use ($projectId) {
                                $sub->select(DB::raw(1))
                                    ->from('tubes')
                                    ->whereColumn('tubes.tubes_content_id', 'pools.id')
                                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(Pools::class))
                                    ->where('tubes.projects_id', $projectId);
                            });
                    });
                }

                return $poolQuery->pluck('id')->map(fn ($v) => (int) $v)->all();
            }, $ids);

            $new = array_merge($new, $poolIds);
        }

        $new = array_values(array_unique($new));

        return array_values(array_diff($new, (array) $nodes[Pools::class]));
    }

    /**
     * @param  Collection<string,array<int,int>>  $frontier
     * @param  Collection<string,array<int,int>>  $nodes
     * @return array<int,int>
     */
    private function nucleicsFromFrontier(Collection $frontier, Collection $nodes, ?int $projectId, bool $isGuestMode): array
    {
        $new = [];

        foreach ($frontier as $type => $ids) {
            $ids = array_values(array_unique(array_map('intval', (array) $ids)));
            if ($ids === []) {
                continue;
            }

            $found = $this->chunked(function (array $chunk) use ($type, $projectId, $isGuestMode) {
                $q = NucleicAcids::query()
                    ->whereIn('nucleic_content_type', $this->typeVariants((string) $type))
                    ->whereIn('nucleic_content_id', $chunk);

                if ($isGuestMode) {
                    $q->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                            ->where('tubes.tubes_content_type', NucleicAcids::class)
                            ->where('tubes.is_private', false);
                    });
                } elseif ($projectId) {
                    $q->where(function ($w) use ($projectId) {
                        $w->where('nucleic_acids.projects_id', $projectId)
                            ->orWhereExists(function ($sub) use ($projectId) {
                                $sub->select(DB::raw(1))
                                    ->from('tubes')
                                    ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(NucleicAcids::class))
                                    ->where('tubes.projects_id', $projectId);
                            });
                    });
                }

                return $q->pluck('id')->map(fn ($v) => (int) $v)->all();
            }, $ids);

            $new = array_merge($new, $found);
        }

        $new = array_values(array_unique($new));

        return array_values(array_diff($new, (array) $nodes[NucleicAcids::class]));
    }

    /**
     * @param  Collection<string,array<int,int>>  $frontier
     * @param  Collection<string,array<int,int>>  $nodes
     * @return array<int,int>
     */
    private function experimentsFromFrontier(Collection $frontier, Collection $nodes, ?int $projectId, bool $isGuestMode): array
    {
        $new = [];

        foreach ($frontier as $type => $ids) {
            $ids = array_values(array_unique(array_map('intval', (array) $ids)));
            if ($ids === []) {
                continue;
            }

            $found = $this->chunked(function (array $chunk) use ($type, $projectId, $isGuestMode) {
                $q = Experiments::query()
                    ->whereIn('experiments_content_type', $this->typeVariants((string) $type))
                    ->whereIn('experiments_content_id', $chunk);

                if ($isGuestMode) {
                    $q->where('is_private', false);
                } elseif ($projectId) {
                    $q->where('projects_id', $projectId);
                }

                return $q->pluck('id')->map(fn ($v) => (int) $v)->all();
            }, $ids);

            $new = array_merge($new, $found);
        }

        $new = array_values(array_unique($new));

        return array_values(array_diff($new, (array) $nodes[Experiments::class]));
    }

    /**
     * @template T
     *
     * @param  array<int,int>  $ids
     * @return array<int,T>
     */
    private function chunked(callable $callback, array $ids, int $chunkSize = 800): array
    {
        $out = [];
        foreach (array_chunk($ids, $chunkSize) as $chunk) {
            $out = array_merge($out, $callback($chunk));
        }

        return $out;
    }
}
