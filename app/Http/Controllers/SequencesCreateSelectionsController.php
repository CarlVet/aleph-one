<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersContentDetails;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\Tubes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SequencesCreateSelectionsController extends Controller
{
    use FiltersContentDetails;

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->integer('perPage', 50);
        if (! in_array($perPage, [10, 50, 100, 200], true)) {
            $perPage = 50;
        }

        return $perPage;
    }

    private function resolveSortDir(Request $request): string
    {
        return strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
    }

    public function nucleicTubes(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = Tubes::query()
            ->where('projects_id', $projectId)
            ->where('tubes_content_type', NucleicAcids::class)
            ->whereHasMorph('tubes_content', [NucleicAcids::class])
            ->whereHasMorph('tubes_content', [NucleicAcids::class], function (Builder $naQuery): void {
                // Only nucleic acids extracted from experiments (so NA.nucleic_content is Experiments)
                $naQuery->whereHasMorph('nucleic_content', [Experiments::class], function (Builder $expQuery): void {
                    // Experiment must be performed on an original nucleic acid
                    $expQuery->whereHasMorph('experiments_content', [NucleicAcids::class]);
                });
            })
            ->with([
                'tubes_content',
                'tubes_content.tubes',
                'tubes_content.protocols',
                'tubes_content.nucleic_content',
                'tubes_content.nucleic_content.protocols',
                'tubes_content.nucleic_content.pathogens',
                'tubes_content.nucleic_content.experiments_content',
                'tubes_content.nucleic_content.experiments_content.nucleic_content',
            ])
            ->orderByDesc('id');

        $this->applyNucleicTubeFilters($query, $filters);

        $sortMap = [
            1 => 'tubes.code',
            2 => 'tubes.alias_code',
            3 => 'derived_nucleic_acids.type',
            4 => 'protocols.name',
            5 => 'derived_nucleic_acids.date_extracted',
            6 => 'derived_nucleic_acids.volume',
            7 => 'original_nucleic_acids.nucleic_content_type',
            9 => 'original_content_code',
            10 => 'experiment_protocols.name',
            11 => 'experiment_pathogens.species',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('tubes.*')
                ->leftJoin('nucleic_acids as derived_nucleic_acids', 'tubes.tubes_content_id', '=', 'derived_nucleic_acids.id')
                ->leftJoin('protocols', 'derived_nucleic_acids.protocols_id', '=', 'protocols.id')
                ->leftJoin('experiments as derived_experiments', function ($join): void {
                    $join->on('derived_nucleic_acids.nucleic_content_id', '=', 'derived_experiments.id')
                        ->where('derived_nucleic_acids.nucleic_content_type', '=', Experiments::class);
                })
                ->leftJoin('protocols as experiment_protocols', 'derived_experiments.protocols_id', '=', 'experiment_protocols.id')
                ->leftJoin('pathogens as experiment_pathogens', 'derived_experiments.pathogens_id', '=', 'experiment_pathogens.id')
                ->leftJoin('nucleic_acids as original_nucleic_acids', function ($join): void {
                    $join->on('derived_experiments.experiments_content_id', '=', 'original_nucleic_acids.id')
                        ->where('derived_experiments.experiments_content_type', '=', NucleicAcids::class);
                })
                ->leftJoin('human_samples as original_origin_human_samples', function ($join): void {
                    $join->on('original_nucleic_acids.nucleic_content_id', '=', 'original_origin_human_samples.id')
                        ->where('original_nucleic_acids.nucleic_content_type', '=', HumanSamples::class);
                })
                ->leftJoin('animal_samples as original_origin_animal_samples', function ($join): void {
                    $join->on('original_nucleic_acids.nucleic_content_id', '=', 'original_origin_animal_samples.id')
                        ->where('original_nucleic_acids.nucleic_content_type', '=', AnimalSamples::class);
                })
                ->leftJoin('environment_samples as original_origin_environment_samples', function ($join): void {
                    $join->on('original_nucleic_acids.nucleic_content_id', '=', 'original_origin_environment_samples.id')
                        ->where('original_nucleic_acids.nucleic_content_type', '=', EnvironmentSamples::class);
                })
                ->leftJoin('parasite_samples as original_origin_parasite_samples', function ($join): void {
                    $join->on('original_nucleic_acids.nucleic_content_id', '=', 'original_origin_parasite_samples.id')
                        ->where('original_nucleic_acids.nucleic_content_type', '=', ParasiteSamples::class);
                })
                ->leftJoin('cultures as original_origin_cultures', function ($join): void {
                    $join->on('original_nucleic_acids.nucleic_content_id', '=', 'original_origin_cultures.id')
                        ->where('original_nucleic_acids.nucleic_content_type', '=', Cultures::class);
                })
                ->leftJoin('pools as original_origin_pools', function ($join): void {
                    $join->on('original_nucleic_acids.nucleic_content_id', '=', 'original_origin_pools.id')
                        ->where('original_nucleic_acids.nucleic_content_type', '=', Pools::class);
                })
                ->reorder()
                ->when($sortCol === 8, function (Builder $q) use ($sortDir): void {
                    $q->orderByRaw(
                        'COALESCE(original_origin_human_samples.code, original_origin_animal_samples.code, original_origin_environment_samples.code, original_origin_parasite_samples.code, original_origin_cultures.code, original_origin_pools.code) '.$sortDir
                    );
                }, function (Builder $q) use ($sortCol, $sortDir, $sortMap): void {
                    $q->orderBy($sortMap[$sortCol], $sortDir);
                })
                ->orderBy('tubes.id');
        }

        $nucleic_experiment_tubes = $query->paginate($perPage, pageName: 'nucleic_tubes_page')->withQueryString();
        $this->hydrateNucleicTubeContentDetails($nucleic_experiment_tubes);
        $paginationPath = route('sequences.create.nucleic_tubes');

        return view('samples.nucleic_acids.sequences.modals.experiment_nucleic_selection', compact('nucleic_experiment_tubes', 'paginationPath'));
    }

    public function nucleicTubesSearch(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $q = (string) $request->query('q', '');

        $results = Tubes::query()
            ->where('projects_id', $projectId)
            ->where('tubes_content_type', NucleicAcids::class)
            ->whereHasMorph('tubes_content', [NucleicAcids::class], function (Builder $naQuery): void {
                $naQuery->whereHasMorph('nucleic_content', [Experiments::class], function (Builder $expQuery): void {
                    $expQuery->whereHasMorph('experiments_content', [NucleicAcids::class]);
                });
            })
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $q2) use ($q) {
                    $q2->where('code', 'like', '%'.$q.'%')
                        ->orWhere('alias_code', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code'])
            ->map(fn ($row) => ['value' => $row->id, 'text' => $row->code])
            ->values();

        return response()->json($results);
    }

    private function applyNucleicTubeFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes:
        // 0 checkbox, 1 tube code, 2 alias code, 3 type, 4 extraction protocol,
        // 5 date extracted, 6 volume, 7 content type, 8 content details, 9 content code,
        // 10 experiment protocol, 11 target pathogen

        if (! empty($filters[1])) {
            $query->where('code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('alias_code', 'like', '%'.trim((string) $filters[2]).'%');
        }

        $query->whereHasMorph('tubes_content', [NucleicAcids::class], function (Builder $naQuery) use ($filters) {
            if (! empty($filters[3])) {
                $naQuery->where('type', 'like', '%'.trim((string) $filters[3]).'%');
            }

            if (! empty($filters[4])) {
                $value = trim((string) $filters[4]);
                $naQuery->whereHas('protocols', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
            }

            if (! empty($filters[5])) {
                $naQuery->where('date_extracted', 'like', '%'.trim((string) $filters[5]).'%');
            }

            if (! empty($filters[6])) {
                $naQuery->whereRaw('CAST(volume as TEXT) like ?', ['%'.trim((string) $filters[6]).'%']);
            }

            if (! empty($filters[7])) {
                $value = strtolower(trim((string) $filters[7]));
                $typeMap = [
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'env' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'culture' => Cultures::class,
                    'pool' => Pools::class,
                ];

                $targetClass = $typeMap[$value] ?? null;

                $naQuery->whereHasMorph('nucleic_content', [Experiments::class], function (Builder $expQuery) use ($targetClass, $value): void {
                    $expQuery->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $originalNaQuery) use ($targetClass, $value): void {
                        if ($targetClass) {
                            $originalNaQuery->where('nucleic_content_type', $targetClass);

                            return;
                        }

                        $originalNaQuery->where('nucleic_content_type', 'like', '%'.$value.'%');
                    });
                });
            }

            if (! empty($filters[8])) {
                $this->applyMultiWordFilter($naQuery, (string) $filters[8], function (Builder $naQuery, string $value): void {
                    $naQuery->whereHasMorph('nucleic_content', [Experiments::class], function (Builder $expQuery) use ($value): void {
                        $expQuery->where(function (Builder $detailsQuery) use ($value): void {
                            $detailsQuery
                                ->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $originalNaQuery) use ($value): void {
                                    $originalNaQuery
                                        ->where('code', 'like', '%'.$value.'%')
                                        ->orWhere('type', 'like', '%'.$value.'%')
                                        ->orWhere('nucleic_content_type', 'like', '%'.$value.'%')
                                        ->orWhereHas('tubes', function (Builder $tubeQuery) use ($value): void {
                                            $tubeQuery
                                                ->where('code', 'like', '%'.$value.'%')
                                                ->orWhere('alias_code', 'like', '%'.$value.'%');
                                        })
                                        ->orWhereHasMorph('nucleic_content', [
                                            HumanSamples::class,
                                        ], function (Builder $sourceQuery) use ($value): void {
                                            $sourceQuery
                                                ->where('code', 'like', '%'.$value.'%')
                                                ->orWhereHas('sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                                ->orWhereHas('sampling_sites', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                                ->orWhereHas('tubes', function (Builder $tubeQuery) use ($value): void {
                                                    $tubeQuery
                                                        ->where('code', 'like', '%'.$value.'%')
                                                        ->orWhere('alias_code', 'like', '%'.$value.'%');
                                                });
                                        })
                                        ->orWhereHasMorph('nucleic_content', [
                                            AnimalSamples::class,
                                        ], function (Builder $sourceQuery) use ($value): void {
                                            $sourceQuery
                                                ->where('code', 'like', '%'.$value.'%')
                                                ->orWhereHas('animals.animal_species', fn (Builder $sq) => $sq->where('name_common', 'like', '%'.$value.'%')->orWhere('name_scientific', 'like', '%'.$value.'%'))
                                                ->orWhereHas('sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                                ->orWhereHas('sampling_sites', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                                ->orWhereHas('tubes', function (Builder $tubeQuery) use ($value): void {
                                                    $tubeQuery
                                                        ->where('code', 'like', '%'.$value.'%')
                                                        ->orWhere('alias_code', 'like', '%'.$value.'%');
                                                });
                                        })
                                        ->orWhereHasMorph('nucleic_content', [
                                            EnvironmentSamples::class,
                                        ], function (Builder $sourceQuery) use ($value): void {
                                            $sourceQuery
                                                ->where('code', 'like', '%'.$value.'%')
                                                ->orWhere('area', 'like', '%'.$value.'%')
                                                ->orWhereHas('environment_sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                                ->orWhereHas('sampling_sites', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                                ->orWhereHas('tubes', function (Builder $tubeQuery) use ($value): void {
                                                    $tubeQuery
                                                        ->where('code', 'like', '%'.$value.'%')
                                                        ->orWhere('alias_code', 'like', '%'.$value.'%');
                                                });
                                        })
                                        ->orWhereHasMorph('nucleic_content', [
                                            ParasiteSamples::class,
                                        ], function (Builder $sourceQuery) use ($value): void {
                                            $sourceQuery
                                                ->where('code', 'like', '%'.$value.'%')
                                                ->orWhereHas('parasites.parasite_species', fn (Builder $sq) => $sq->where('name_scientific', 'like', '%'.$value.'%'))
                                                ->orWhereHas('parasite_sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                                ->orWhereHas('tubes', function (Builder $tubeQuery) use ($value): void {
                                                    $tubeQuery
                                                        ->where('code', 'like', '%'.$value.'%')
                                                        ->orWhere('alias_code', 'like', '%'.$value.'%');
                                                });
                                        })
                                        ->orWhereHasMorph('nucleic_content', [
                                            Pools::class,
                                        ], function (Builder $sourceQuery) use ($value): void {
                                            $sourceQuery
                                                ->where('code', 'like', '%'.$value.'%')
                                                ->orWhereHas('pool_contents.samples', fn (Builder $sq) => $sq->where('code', 'like', '%'.$value.'%'))
                                                ->orWhereHas('tubes', function (Builder $tubeQuery) use ($value): void {
                                                    $tubeQuery
                                                        ->where('code', 'like', '%'.$value.'%')
                                                        ->orWhere('alias_code', 'like', '%'.$value.'%');
                                                });
                                        })
                                        ->orWhereHasMorph('nucleic_content', [
                                            Cultures::class,
                                        ], function (Builder $sourceQuery) use ($value): void {
                                            $sourceQuery
                                                ->where('code', 'like', '%'.$value.'%')
                                                ->orWhere('medium', 'like', '%'.$value.'%')
                                                ->orWhere('type', 'like', '%'.$value.'%')
                                                ->orWhereHas('cultures_content', fn (Builder $cq) => $cq->where('code', 'like', '%'.$value.'%'))
                                                ->orWhereHas('tubes', function (Builder $tubeQuery) use ($value): void {
                                                    $tubeQuery
                                                        ->where('code', 'like', '%'.$value.'%')
                                                        ->orWhere('alias_code', 'like', '%'.$value.'%');
                                                });
                                        });
                                })
                                ->orWhereHas('protocols', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'))
                                ->orWhereHas('pathogens', fn (Builder $q) => $q->where('species', 'like', '%'.$value.'%'));
                        });
                    });
                });
            }

            if (! empty($filters[9])) {
                $value = trim((string) $filters[9]);

                $naQuery->whereHasMorph('nucleic_content', [Experiments::class], function (Builder $expQuery) use ($value): void {
                    $expQuery->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $originalNaQuery) use ($value): void {
                        $originalNaQuery->whereHasMorph('nucleic_content', [
                            HumanSamples::class,
                            AnimalSamples::class,
                            EnvironmentSamples::class,
                            ParasiteSamples::class,
                            Cultures::class,
                            Pools::class,
                        ], function (Builder $sourceQuery) use ($value): void {
                            $sourceQuery->where('code', 'like', '%'.$value.'%');
                        });
                    });
                });
            }

            if (! empty($filters[10])) {
                $value = trim((string) $filters[10]);
                $naQuery->whereHasMorph('nucleic_content', [Experiments::class], function (Builder $expQuery) use ($value): void {
                    $expQuery->whereHas('protocols', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                });
            }

            if (! empty($filters[11])) {
                $value = trim((string) $filters[11]);
                $naQuery->whereHasMorph('nucleic_content', [Experiments::class], function (Builder $expQuery) use ($value): void {
                    $expQuery->whereHas('pathogens', fn (Builder $q) => $q->where('species', 'like', '%'.$value.'%'));
                });
            }
        });
    }

    private function hydrateNucleicTubeContentDetails(LengthAwarePaginator $paginator): void
    {
        $rows = $paginator->getCollection();
        if ($rows->isEmpty()) {
            return;
        }

        $rows->loadMorph('tubes_content', [
            NucleicAcids::class => ['nucleic_content'],
        ]);

        $derivedNucleics = $rows
            ->pluck('tubes_content')
            ->filter(fn ($item) => $item instanceof NucleicAcids)
            ->unique('id')
            ->values();

        if ($derivedNucleics->isEmpty()) {
            return;
        }

        $derivedCollection = new EloquentCollection($derivedNucleics->all());
        $derivedCollection->loadMorph('nucleic_content', [
            Experiments::class => ['protocols', 'pathogens', 'experiments_content', 'experiments_content.nucleic_content'],
        ]);

        $originalNucleics = $derivedCollection
            ->pluck('nucleic_content')
            ->filter(fn ($item) => $item instanceof Experiments)
            ->pluck('experiments_content')
            ->filter(fn ($item) => $item instanceof NucleicAcids)
            ->unique('id')
            ->values();

        if ($originalNucleics->isEmpty()) {
            return;
        }

        $originalCollection = new EloquentCollection($originalNucleics->all());
        $originalCollection->load('tubes');
        $originalCollection->loadMorph('nucleic_content', [
            HumanSamples::class => ['humans.countries', 'sample_types', 'sampling_sites', 'tubes'],
            AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites', 'tubes'],
            EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites', 'tubes'],
            ParasiteSamples::class => ['parasites.parasite_species', 'parasite_sample_types', 'tubes'],
            Cultures::class => ['laboratories', 'tubes'],
            Pools::class => ['pool_contents.samples', 'tubes'],
        ]);

        $poolContents = $originalCollection
            ->pluck('nucleic_content')
            ->filter(fn ($item) => $item instanceof Pools)
            ->flatMap(fn (Pools $pool) => $pool->pool_contents)
            ->filter(fn ($item) => $item instanceof PoolContents)
            ->values();

        if ($poolContents->isNotEmpty()) {
            (new EloquentCollection($poolContents->all()))->loadMorph('samples', [
                HumanSamples::class => ['tubes'],
                AnimalSamples::class => ['tubes'],
                EnvironmentSamples::class => ['tubes'],
                ParasiteSamples::class => ['tubes'],
                NucleicAcids::class => ['tubes'],
                Cultures::class => ['tubes'],
                Pools::class => ['tubes'],
            ]);
        }
    }
}
