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
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExperimentsCreateTubesController extends Controller
{
    use FiltersContentDetails;

    public function human(Request $request): View
    {
        $filters = (array) $request->query('filters', []);
        $human_tubes = $this->tubesPaginator(
            HumanSamples::class,
            'human_tubes_page',
            $filters,
            $this->resolvePerPage($request),
            (int) $request->integer('sort_col', 0),
            $this->resolveSortDir($request)
        );
        $paginationPath = route('experiments.create.tubes.human');

        return view('samples.humans.modals.human_tubes_selection', compact('human_tubes', 'paginationPath'));
    }

    public function animal(Request $request): View
    {
        $filters = (array) $request->query('filters', []);
        $animal_tubes = $this->tubesPaginator(
            AnimalSamples::class,
            'animal_tubes_page',
            $filters,
            $this->resolvePerPage($request),
            (int) $request->integer('sort_col', 0),
            $this->resolveSortDir($request)
        );
        $paginationPath = route('experiments.create.tubes.animal');

        return view('samples.animals.modals.animal_tubes_selection', compact('animal_tubes', 'paginationPath'));
    }

    public function environment(Request $request): View
    {
        $filters = (array) $request->query('filters', []);
        $environment_tubes = $this->tubesPaginator(
            EnvironmentSamples::class,
            'environment_tubes_page',
            $filters,
            $this->resolvePerPage($request),
            (int) $request->integer('sort_col', 0),
            $this->resolveSortDir($request)
        );
        $paginationPath = route('experiments.create.tubes.environment');

        return view('samples.environment.modals.environment_tubes_selection', compact('environment_tubes', 'paginationPath'));
    }

    public function parasite(Request $request): View
    {
        $filters = (array) $request->query('filters', []);
        $parasite_tubes = $this->tubesPaginator(
            ParasiteSamples::class,
            'parasite_tubes_page',
            $filters,
            $this->resolvePerPage($request),
            (int) $request->integer('sort_col', 0),
            $this->resolveSortDir($request)
        );
        $paginationPath = route('experiments.create.tubes.parasite');

        return view('samples.parasites.modals.parasite_tubes_selection', compact('parasite_tubes', 'paginationPath'));
    }

    public function nucleic(Request $request): View
    {
        $filters = (array) $request->query('filters', []);
        $nucleic_tubes = $this->tubesPaginator(
            NucleicAcids::class,
            'nucleic_tubes_page',
            $filters,
            $this->resolvePerPage($request),
            (int) $request->integer('sort_col', 0),
            $this->resolveSortDir($request)
        );
        $paginationPath = route('experiments.create.tubes.nucleic');

        return view('samples.nucleic_acids.modals.nucleic_tubes_table', compact('nucleic_tubes', 'paginationPath'));
    }

    public function culture(Request $request): View
    {
        $filters = (array) $request->query('filters', []);
        $culture_tubes = $this->tubesPaginator(
            Cultures::class,
            'culture_tubes_page',
            $filters,
            $this->resolvePerPage($request),
            (int) $request->integer('sort_col', 0),
            $this->resolveSortDir($request)
        );
        $paginationPath = route('experiments.create.tubes.culture');

        return view('samples.cultures.modals.culture_tubes_selection', compact('culture_tubes', 'paginationPath'));
    }

    public function pool(Request $request): View
    {
        $filters = (array) $request->query('filters', []);
        $pool_tubes = $this->tubesPaginator(
            Pools::class,
            'pool_tubes_page',
            $filters,
            $this->resolvePerPage($request),
            (int) $request->integer('sort_col', 0),
            $this->resolveSortDir($request)
        );
        $paginationPath = route('experiments.create.tubes.pool');

        return view('samples.pools.modals.pool_tubes_selection', compact('pool_tubes', 'paginationPath'));
    }

    public function humanSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, HumanSamples::class);
    }

    public function animalSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, AnimalSamples::class);
    }

    public function environmentSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, EnvironmentSamples::class);
    }

    public function parasiteSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, ParasiteSamples::class);
    }

    public function nucleicSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, NucleicAcids::class);
    }

    public function cultureSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, Cultures::class);
    }

    public function poolSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, Pools::class);
    }

    private function tubeSearch(Request $request, string $contentClass): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $projectId = session('selected_project_id');

        $tubes = Tubes::query()
            ->whereHasMorph('tubes_content', [$contentClass])
            ->where('tubes.projects_id', $projectId)
            ->where(function ($query) use ($q) {
                $query
                    ->where('tubes.code', 'like', "%{$q}%")
                    ->orWhere('tubes.alias_code', 'like', "%{$q}%");
            })
            ->orderBy('tubes.code')
            ->limit(20)
            ->get(['tubes.id', 'tubes.code', 'tubes.alias_code']);

        return response()->json(
            $tubes->map(fn ($tube) => [
                'value' => $tube->id,
                'text' => $tube->code,
                'code' => $tube->code,
                'alias_code' => $tube->alias_code,
            ])->all()
        );
    }

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

    private function applyTubeSelectionSorting(Builder $query, string $contentType, int $sortCol, string $sortDir): void
    {
        $sortMap = match ($contentType) {
            HumanSamples::class => [
                1 => 'tubes.code',
                2 => 'tubes.alias_code',
                3 => 'tubes.preservant',
                4 => 'human_samples.code',
                5 => 'humans.last_name',
                6 => 'sample_types.name',
                7 => 'human_samples.date_collected',
                8 => 'sampling_sites.name',
                9 => 'human_samples.latitude',
                10 => 'human_samples.longitude',
                11 => 'people.last_name',
            ],
            AnimalSamples::class => [
                1 => 'tubes.code',
                2 => 'tubes.alias_code',
                3 => 'tubes.preservant',
                4 => 'tubes.purpose',
                5 => 'animal_samples.code',
                6 => 'animals.code',
                7 => 'animals.field_label',
                8 => 'animal_species.name_common',
                9 => 'animals.sex',
                10 => 'animals.age',
                11 => 'sample_types.name',
                12 => 'animal_samples.date_collected',
                13 => 'sampling_sites.name',
                14 => 'animal_samples.latitude',
                15 => 'animal_samples.longitude',
                16 => 'people.last_name',
            ],
            EnvironmentSamples::class => [
                1 => 'tubes.code',
                2 => 'tubes.alias_code',
                3 => 'environment_sample_types.name',
                4 => 'environment_samples.date_collected',
                5 => 'sampling_sites.name',
                6 => 'environment_samples.area',
                7 => 'environment_samples.latitude',
                8 => 'environment_samples.longitude',
                9 => 'people.last_name',
            ],
            ParasiteSamples::class => [
                1 => 'tubes.code',
                2 => 'tubes.alias_code',
                3 => 'tubes.preservant',
                4 => 'parasite_samples.code',
                5 => 'parasite_species.name_scientific',
                6 => 'parasite_sample_types.name',
                7 => 'origin_sampling_sites.name',
                8 => 'parasite_samples.date_processed',
                9 => 'people.last_name',
            ],
            NucleicAcids::class => [
                1 => 'tubes.code',
                2 => 'tubes.alias_code',
                3 => 'tubes.preservant',
                4 => 'nucleic_acids.type',
                5 => 'protocols.name',
                6 => 'nucleic_acids.date_extracted',
                7 => 'nucleic_acids.volume',
                8 => 'nucleic_acids.nucleic_content_type',
                10 => 'nucleic_content_code',
            ],
            Cultures::class => [
                1 => 'tubes.code',
                2 => 'tubes.preservant',
                3 => 'parent_cultures.code',
                4 => 'cultures.step',
                5 => 'cultures.date_cultured',
                6 => 'cultures.medium',
                7 => 'cultures.type',
                8 => 'cultures.incubation_temp',
                9 => 'cultures.athmosphere',
                12 => 'laboratories.name',
                13 => 'people.last_name',
            ],
            Pools::class => [
                1 => 'tubes.code',
                2 => 'tubes.preservant',
                3 => 'pools.nr_pooled',
                4 => 'pool_contents_agg.samples_type_sort',
                5 => 'pool_contents_agg.min_content_code',
                6 => 'pools.date_pooled',
                7 => 'laboratories.name',
                8 => 'people.last_name',
            ],
            default => [],
        };

        if (! isset($sortMap[$sortCol])) {
            return;
        }

        $query->select('tubes.*');

        $sortColumn = $sortMap[$sortCol];
        if (! str_starts_with($sortColumn, 'tubes.')) {
            match ($contentType) {
                HumanSamples::class => $query
                    ->leftJoin('human_samples', 'tubes.tubes_content_id', '=', 'human_samples.id')
                    ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                    ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
                    ->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id')
                    ->leftJoin('people', 'human_samples.people_id', '=', 'people.id'),
                AnimalSamples::class => $query
                    ->leftJoin('animal_samples', 'tubes.tubes_content_id', '=', 'animal_samples.id')
                    ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
                    ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                    ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
                    ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id')
                    ->leftJoin('people', 'animal_samples.people_id', '=', 'people.id'),
                EnvironmentSamples::class => $query
                    ->leftJoin('environment_samples', 'tubes.tubes_content_id', '=', 'environment_samples.id')
                    ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
                    ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id')
                    ->leftJoin('people', 'environment_samples.people_id', '=', 'people.id'),
                ParasiteSamples::class => $query
                    ->leftJoin('parasite_samples', 'tubes.tubes_content_id', '=', 'parasite_samples.id')
                    ->leftJoin('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                    ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                    ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
                    ->leftJoin('human_samples as origin_human_samples', function ($join): void {
                        $join->on('parasites.parasites_origin_id', '=', 'origin_human_samples.id')
                            ->where('parasites.parasites_origin_type', '=', HumanSamples::class);
                    })
                    ->leftJoin('animal_samples as origin_animal_samples', function ($join): void {
                        $join->on('parasites.parasites_origin_id', '=', 'origin_animal_samples.id')
                            ->where('parasites.parasites_origin_type', '=', AnimalSamples::class);
                    })
                    ->leftJoin('environment_samples as origin_environment_samples', function ($join): void {
                        $join->on('parasites.parasites_origin_id', '=', 'origin_environment_samples.id')
                            ->where('parasites.parasites_origin_type', '=', EnvironmentSamples::class);
                    })
                    ->leftJoin('sampling_sites as origin_sampling_sites', function ($join): void {
                        $join->on('origin_sampling_sites.id', '=', DB::raw('COALESCE(origin_human_samples.sampling_sites_id, origin_animal_samples.sampling_sites_id, origin_environment_samples.sampling_sites_id)'));
                    })
                    ->leftJoin('people', 'parasite_samples.people_id', '=', 'people.id'),
                NucleicAcids::class => $query
                    ->leftJoin('nucleic_acids', 'tubes.tubes_content_id', '=', 'nucleic_acids.id')
                    ->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id')
                    ->leftJoin('human_samples as nucleic_origin_human_samples', function ($join): void {
                        $join->on('nucleic_acids.nucleic_content_id', '=', 'nucleic_origin_human_samples.id')
                            ->where('nucleic_acids.nucleic_content_type', '=', HumanSamples::class);
                    })
                    ->leftJoin('animal_samples as nucleic_origin_animal_samples', function ($join): void {
                        $join->on('nucleic_acids.nucleic_content_id', '=', 'nucleic_origin_animal_samples.id')
                            ->where('nucleic_acids.nucleic_content_type', '=', AnimalSamples::class);
                    })
                    ->leftJoin('environment_samples as nucleic_origin_environment_samples', function ($join): void {
                        $join->on('nucleic_acids.nucleic_content_id', '=', 'nucleic_origin_environment_samples.id')
                            ->where('nucleic_acids.nucleic_content_type', '=', EnvironmentSamples::class);
                    })
                    ->leftJoin('parasite_samples as nucleic_origin_parasite_samples', function ($join): void {
                        $join->on('nucleic_acids.nucleic_content_id', '=', 'nucleic_origin_parasite_samples.id')
                            ->where('nucleic_acids.nucleic_content_type', '=', ParasiteSamples::class);
                    })
                    ->leftJoin('cultures as nucleic_origin_cultures', function ($join): void {
                        $join->on('nucleic_acids.nucleic_content_id', '=', 'nucleic_origin_cultures.id')
                            ->where('nucleic_acids.nucleic_content_type', '=', Cultures::class);
                    })
                    ->leftJoin('pools as nucleic_origin_pools', function ($join): void {
                        $join->on('nucleic_acids.nucleic_content_id', '=', 'nucleic_origin_pools.id')
                            ->where('nucleic_acids.nucleic_content_type', '=', Pools::class);
                    })
                    ->leftJoin('experiments as nucleic_origin_experiments', function ($join): void {
                        $join->on('nucleic_acids.nucleic_content_id', '=', 'nucleic_origin_experiments.id')
                            ->where('nucleic_acids.nucleic_content_type', '=', Experiments::class);
                    }),
                Cultures::class => $query
                    ->leftJoin('cultures', 'tubes.tubes_content_id', '=', 'cultures.id')
                    ->leftJoin('cultures as parent_cultures', 'cultures.parent_id', '=', 'parent_cultures.id')
                    ->leftJoin('laboratories', 'cultures.laboratories_id', '=', 'laboratories.id')
                    ->leftJoin('people', 'cultures.people_id', '=', 'people.id'),
                Pools::class => $query
                    ->leftJoin('pools', 'tubes.tubes_content_id', '=', 'pools.id')
                    ->leftJoinSub(
                        DB::table('pool_contents')
                            ->leftJoin('human_samples as pool_human_samples', function ($join): void {
                                $join->on('pool_contents.samples_id', '=', 'pool_human_samples.id')
                                    ->where('pool_contents.samples_type', '=', HumanSamples::class);
                            })
                            ->leftJoin('animal_samples as pool_animal_samples', function ($join): void {
                                $join->on('pool_contents.samples_id', '=', 'pool_animal_samples.id')
                                    ->where('pool_contents.samples_type', '=', AnimalSamples::class);
                            })
                            ->leftJoin('environment_samples as pool_environment_samples', function ($join): void {
                                $join->on('pool_contents.samples_id', '=', 'pool_environment_samples.id')
                                    ->where('pool_contents.samples_type', '=', EnvironmentSamples::class);
                            })
                            ->leftJoin('parasite_samples as pool_parasite_samples', function ($join): void {
                                $join->on('pool_contents.samples_id', '=', 'pool_parasite_samples.id')
                                    ->where('pool_contents.samples_type', '=', ParasiteSamples::class);
                            })
                            ->leftJoin('nucleic_acids as pool_nucleic_acids', function ($join): void {
                                $join->on('pool_contents.samples_id', '=', 'pool_nucleic_acids.id')
                                    ->where('pool_contents.samples_type', '=', NucleicAcids::class);
                            })
                            ->leftJoin('cultures as pool_cultures', function ($join): void {
                                $join->on('pool_contents.samples_id', '=', 'pool_cultures.id')
                                    ->where('pool_contents.samples_type', '=', Cultures::class);
                            })
                            ->select([
                                'pool_contents.pools_id',
                                DB::raw('MIN(pool_contents.samples_type) as samples_type_sort'),
                                DB::raw('MIN(COALESCE(pool_human_samples.code, pool_animal_samples.code, pool_environment_samples.code, pool_parasite_samples.code, pool_nucleic_acids.code, pool_cultures.code)) as min_content_code'),
                            ])
                            ->groupBy('pool_contents.pools_id'),
                        'pool_contents_agg',
                        'pool_contents_agg.pools_id',
                        '=',
                        'pools.id'
                    )
                    ->leftJoin('laboratories', 'pools.laboratories_id', '=', 'laboratories.id')
                    ->leftJoin('people', 'pools.people_id', '=', 'people.id'),
                default => null,
            };
        }

        if ($contentType === NucleicAcids::class && $sortCol === 9) {
            $query->reorder()
                ->orderByRaw(
                    'COALESCE(nucleic_origin_human_samples.code, nucleic_origin_animal_samples.code, nucleic_origin_environment_samples.code, nucleic_origin_parasite_samples.code, nucleic_origin_cultures.code, nucleic_origin_pools.code, nucleic_origin_experiments.code) '.$sortDir
                )
                ->orderBy('tubes.id');

            return;
        }

        $query->reorder()->orderBy($sortColumn, $sortDir)->orderBy('tubes.id');
    }

    private function tubesPaginator(string $contentType, string $pageName, array $filters, int $perPage, int $sortCol, string $sortDir)
    {
        $projectId = session('selected_project_id');

        $with = match ($contentType) {
            HumanSamples::class => [
                'tubes_content',
                'tubes_content.humans',
                'tubes_content.sample_types',
                'tubes_content.people',
                'tubes_content.sampling_sites',
            ],
            AnimalSamples::class => [
                'tubes_content',
                'tubes_content.animals.animal_species',
                'tubes_content.sample_types',
                'tubes_content.people',
                'tubes_content.sampling_sites',
            ],
            EnvironmentSamples::class => [
                'tubes_content',
                'tubes_content.environment_sample_types',
                'tubes_content.people',
                'tubes_content.sampling_sites',
            ],
            ParasiteSamples::class => [
                'tubes_content',
                'tubes_content.parasites.parasite_species',
                'tubes_content.parasites.parasites_origin.sampling_sites',
                'tubes_content.parasite_sample_types',
                'tubes_content.people',
            ],
            NucleicAcids::class => [
                'tubes_content',
                'tubes_content.protocols',
                'tubes_content.nucleic_content',
                'tubes_content.people',
                'tubes_content.laboratories',
            ],
            Cultures::class => [
                'tubes_content',
                'tubes_content.parent',
                'tubes_content.cultures_content',
                'tubes_content.cultures_content.tubes',
                'tubes_content.laboratories',
                'tubes_content.people',
            ],
            Pools::class => [
                'tubes_content',
                'tubes_content.pool_contents.samples',
                'tubes_content.laboratories',
                'tubes_content.people',
            ],
            default => [
                'tubes_content',
            ],
        };

        $query = Tubes::query()
            ->where('tubes.projects_id', $projectId)
            ->where('tubes_content_type', $contentType)
            ->whereHasMorph('tubes_content', [$contentType])
            ->with($with)
            ->orderByDesc('id');

        if ($contentType === NucleicAcids::class) {
            $this->applyNucleicTubeFilters($query, $filters);
        } elseif ($contentType === Cultures::class) {
            $this->applyCultureTubeFilters($query, $filters);
        } elseif ($contentType === Pools::class) {
            $this->applyPoolTubeFilters($query, $filters);
        } else {
            $this->applyTubeFilters($query, $filters, $contentType);
        }

        $this->applyTubeSelectionSorting($query, $contentType, $sortCol, $sortDir);

        $paginator = $query->paginate($perPage, pageName: $pageName)->withQueryString();
        if ($contentType === NucleicAcids::class) {
            $this->hydrateNucleicTubeTracebackDetails($paginator);
        } elseif ($contentType === Cultures::class) {
            $this->hydrateCultureTubeSelectionDetails($paginator);
        }

        return $paginator;
    }

    private function hydrateCultureTubeSelectionDetails(LengthAwarePaginator $paginator): void
    {
        $rows = $paginator->getCollection();
        if ($rows->isEmpty()) {
            return;
        }

        $rows->loadMorph('tubes_content', [
            Cultures::class => ['cultures_content'],
        ]);

        $cultures = $rows
            ->pluck('tubes_content')
            ->filter(fn ($item) => $item instanceof Cultures)
            ->unique('id')
            ->values();

        if ($cultures->isEmpty()) {
            return;
        }

        (new EloquentCollection($cultures->all()))->loadMorph('cultures_content', [
            HumanSamples::class => ['tubes'],
            AnimalSamples::class => ['tubes'],
            EnvironmentSamples::class => ['tubes'],
            ParasiteSamples::class => ['tubes'],
            NucleicAcids::class => ['tubes'],
            Cultures::class => ['tubes'],
            Pools::class => ['tubes'],
            Experiments::class => ['tubes'],
        ]);
    }

    private function hydrateNucleicTubeTracebackDetails(LengthAwarePaginator $paginator): void
    {
        $rows = $paginator->getCollection();
        if ($rows->isEmpty()) {
            return;
        }

        $rows->loadMorph('tubes_content', [
            NucleicAcids::class => ['nucleic_content', 'tubes', 'protocols'],
        ]);

        $nucleics = $rows
            ->pluck('tubes_content')
            ->filter(fn ($item) => $item instanceof NucleicAcids)
            ->unique('id')
            ->values();

        if ($nucleics->isEmpty()) {
            return;
        }

        $nucleicCollection = new EloquentCollection($nucleics->all());
        $nucleicCollection->loadMorph('nucleic_content', [
            HumanSamples::class => ['humans.countries', 'sample_types', 'sampling_sites', 'tubes'],
            AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites', 'tubes'],
            EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites', 'tubes'],
            ParasiteSamples::class => ['parasites.parasite_species', 'parasite_sample_types', 'tubes'],
            Cultures::class => ['laboratories', 'tubes', 'cultures_content', 'parent'],
            Pools::class => ['pool_contents.samples', 'tubes'],
            Experiments::class => ['protocols', 'pathogens', 'experiments_content', 'experiments_content.nucleic_content', 'experiments_content.tubes'],
        ]);

        $experimentSourceNucleics = $nucleicCollection
            ->pluck('nucleic_content')
            ->filter(fn ($item) => $item instanceof Experiments)
            ->pluck('experiments_content')
            ->filter(fn ($item) => $item instanceof NucleicAcids)
            ->unique('id')
            ->values();

        if ($experimentSourceNucleics->isNotEmpty()) {
            $sourceCollection = new EloquentCollection($experimentSourceNucleics->all());
            $sourceCollection->load('tubes');
            $sourceCollection->loadMorph('nucleic_content', [
                HumanSamples::class => ['humans.countries', 'sample_types', 'sampling_sites', 'tubes'],
                AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites', 'tubes'],
                EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites', 'tubes'],
                ParasiteSamples::class => ['parasites.parasite_species', 'parasite_sample_types', 'tubes'],
                Cultures::class => ['laboratories', 'tubes', 'cultures_content', 'parent'],
                Pools::class => ['pool_contents.samples', 'tubes'],
            ]);
        }

        $poolContents = $nucleicCollection
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

    private function applyTubeFilters(Builder $query, array $filters, string $contentType): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column 1 is always tube code.
        if (! empty($filters[1])) {
            $query->where('tubes.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        // Alias code column index differs per table.
        $aliasIndexes = match ($contentType) {
            HumanSamples::class => [2],
            AnimalSamples::class => [2],
            EnvironmentSamples::class => [2],
            ParasiteSamples::class => [2],
            default => [],
        };
        foreach ($aliasIndexes as $idx) {
            if (! empty($filters[$idx])) {
                $query->where('tubes.alias_code', 'like', '%'.trim((string) $filters[$idx]).'%');
            }
        }

        // Preservant column index differs per table.
        $preservantIndexes = match ($contentType) {
            HumanSamples::class => [3],
            AnimalSamples::class => [3],
            ParasiteSamples::class => [3],
            default => [],
        };
        foreach ($preservantIndexes as $idx) {
            if (! empty($filters[$idx])) {
                $query->where('tubes.preservant', 'like', '%'.trim((string) $filters[$idx]).'%');
            }
        }

        $purposeIndexes = match ($contentType) {
            HumanSamples::class => [12],
            AnimalSamples::class => [4],
            EnvironmentSamples::class => [10],
            ParasiteSamples::class => [10],
            NucleicAcids::class => [11],
            Cultures::class => [14],
            Pools::class => [9],
            default => [],
        };
        foreach ($purposeIndexes as $idx) {
            if (! empty($filters[$idx])) {
                $query->where('tubes.purpose', 'like', '%'.trim((string) $filters[$idx]).'%');
            }
        }

        $query->whereHasMorph('tubes_content', [$contentType], function (Builder $contentQuery) use ($filters, $contentType) {
            if ($contentType === HumanSamples::class) {
                if (! empty($filters[4])) {
                    $contentQuery->where('code', 'like', '%'.trim((string) $filters[4]).'%');
                }

                if (! empty($filters[5])) {
                    $value = trim((string) $filters[5]);
                    $contentQuery->whereHas('humans', function (Builder $q) use ($value) {
                        $q->where('first_name', 'like', '%'.$value.'%')
                            ->orWhere('last_name', 'like', '%'.$value.'%');
                    });
                }

                if (! empty($filters[6])) {
                    $value = trim((string) $filters[6]);
                    $contentQuery->whereHas('sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[7])) {
                    $contentQuery->where('date_collected', 'like', '%'.trim((string) $filters[7]).'%');
                }

                if (! empty($filters[8])) {
                    $value = trim((string) $filters[8]);
                    $contentQuery->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[9])) {
                    $contentQuery->whereRaw('CAST(latitude as TEXT) like ?', ['%'.trim((string) $filters[9]).'%']);
                }

                if (! empty($filters[10])) {
                    $contentQuery->whereRaw('CAST(longitude as TEXT) like ?', ['%'.trim((string) $filters[10]).'%']);
                }

                if (! empty($filters[11])) {
                    $value = trim((string) $filters[11]);
                    $contentQuery->whereHas('people', function (Builder $q) use ($value) {
                        $q->where('first_name', 'like', '%'.$value.'%')
                            ->orWhere('last_name', 'like', '%'.$value.'%')
                            ->orWhere('title', 'like', '%'.$value.'%');
                    });
                }
            }

            if ($contentType === AnimalSamples::class) {
                if (! empty($filters[5])) {
                    $contentQuery->where('code', 'like', '%'.trim((string) $filters[5]).'%');
                }

                if (! empty($filters[6])) {
                    $value = trim((string) $filters[6]);
                    $contentQuery->whereHas('animals', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[7])) {
                    $value = trim((string) $filters[7]);
                    $contentQuery->whereHas('animals', fn (Builder $q) => $q->where('field_label', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[8])) {
                    $value = trim((string) $filters[8]);
                    $contentQuery->whereHas('animals.animal_species', fn (Builder $q) => $q->where('name_common', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[9])) {
                    $value = trim((string) $filters[9]);
                    $contentQuery->whereHas('animals', fn (Builder $q) => $q->where('sex', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[10])) {
                    $value = trim((string) $filters[10]);
                    $contentQuery->whereHas('animals', fn (Builder $q) => $q->whereRaw('CAST(age as TEXT) like ?', ['%'.$value.'%']));
                }

                if (! empty($filters[11])) {
                    $value = trim((string) $filters[11]);
                    $contentQuery->whereHas('sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[12])) {
                    $contentQuery->where('date_collected', 'like', '%'.trim((string) $filters[12]).'%');
                }

                if (! empty($filters[13])) {
                    $value = trim((string) $filters[13]);
                    $contentQuery->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[14])) {
                    $contentQuery->whereRaw('CAST(latitude as TEXT) like ?', ['%'.trim((string) $filters[14]).'%']);
                }

                if (! empty($filters[15])) {
                    $contentQuery->whereRaw('CAST(longitude as TEXT) like ?', ['%'.trim((string) $filters[15]).'%']);
                }

                if (! empty($filters[16])) {
                    $value = trim((string) $filters[16]);
                    $contentQuery->whereHas('people', function (Builder $q) use ($value) {
                        $q->where('first_name', 'like', '%'.$value.'%')
                            ->orWhere('last_name', 'like', '%'.$value.'%')
                            ->orWhere('title', 'like', '%'.$value.'%');
                    });
                }
            }

            if ($contentType === EnvironmentSamples::class) {
                if (! empty($filters[3])) {
                    $value = trim((string) $filters[3]);
                    $contentQuery->whereHas('environment_sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[4])) {
                    $contentQuery->where('date_collected', 'like', '%'.trim((string) $filters[4]).'%');
                }

                if (! empty($filters[5])) {
                    $value = trim((string) $filters[5]);
                    $contentQuery->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[6])) {
                    $contentQuery->where('area', 'like', '%'.trim((string) $filters[6]).'%');
                }

                if (! empty($filters[7])) {
                    $contentQuery->whereRaw('CAST(latitude as TEXT) like ?', ['%'.trim((string) $filters[7]).'%']);
                }

                if (! empty($filters[8])) {
                    $contentQuery->whereRaw('CAST(longitude as TEXT) like ?', ['%'.trim((string) $filters[8]).'%']);
                }

                if (! empty($filters[9])) {
                    $value = trim((string) $filters[9]);
                    $contentQuery->whereHas('people', function (Builder $q) use ($value) {
                        $q->where('first_name', 'like', '%'.$value.'%')
                            ->orWhere('last_name', 'like', '%'.$value.'%')
                            ->orWhere('title', 'like', '%'.$value.'%');
                    });
                }
            }

            if ($contentType === ParasiteSamples::class) {
                if (! empty($filters[4])) {
                    $contentQuery->where('code', 'like', '%'.trim((string) $filters[4]).'%');
                }

                if (! empty($filters[5])) {
                    $value = trim((string) $filters[5]);
                    $contentQuery->whereHas('parasites.parasite_species', fn (Builder $q) => $q->where('name_scientific', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[6])) {
                    $value = trim((string) $filters[6]);
                    $contentQuery->whereHas('parasite_sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[7])) {
                    $value = trim((string) $filters[7]);
                    $contentQuery->whereHas('parasites.parasites_origin.sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[8])) {
                    $contentQuery->where('date_processed', 'like', '%'.trim((string) $filters[8]).'%');
                }

                if (! empty($filters[9])) {
                    $value = trim((string) $filters[9]);
                    $contentQuery->whereHas('people', function (Builder $q) use ($value) {
                        $q->where('first_name', 'like', '%'.$value.'%')
                            ->orWhere('last_name', 'like', '%'.$value.'%')
                            ->orWhere('title', 'like', '%'.$value.'%');
                    });
                }
            }
        });
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

        // Column indexes (nucleic_tubes_table):
        // 0 checkbox, 1 tube code, 2 alias, 3 preservant, 4 type, 5 extraction protocol, 6 date extracted, 7 volume, 8 content type, 9 content details, 10 content code
        if (! empty($filters[1])) {
            $query->where('tubes.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('tubes.alias_code', 'like', '%'.trim((string) $filters[2]).'%');
        }

        if (! empty($filters[3])) {
            $query->where('tubes.preservant', 'like', '%'.trim((string) $filters[3]).'%');
        }

        $query->whereHasMorph('tubes_content', [NucleicAcids::class], function (Builder $naQuery) use ($filters) {
            if (! empty($filters[4])) {
                $naQuery->where('type', 'like', '%'.trim((string) $filters[4]).'%');
            }

            if (! empty($filters[5])) {
                $value = trim((string) $filters[5]);
                $naQuery->whereHas('protocols', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
            }

            if (! empty($filters[6])) {
                $naQuery->where('date_extracted', 'like', '%'.trim((string) $filters[6]).'%');
            }

            if (! empty($filters[7])) {
                $naQuery->whereRaw('CAST(volume as TEXT) like ?', ['%'.trim((string) $filters[7]).'%']);
            }

            if (! empty($filters[8])) {
                $value = strtolower(trim((string) $filters[8]));
                $map = [
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'env' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'culture' => Cultures::class,
                    'pool' => Pools::class,
                    'experiment' => Experiments::class,
                ];

                $matched = null;
                foreach ($map as $needle => $class) {
                    if (str_contains($value, $needle)) {
                        $matched = $class;
                        break;
                    }
                }

                if ($matched) {
                    $naQuery->where('nucleic_content_type', $matched);
                } else {
                    $naQuery->where('nucleic_content_type', 'like', '%'.trim((string) $filters[8]).'%');
                }
            }

            if (! empty($filters[9])) {
                $detailTokens = preg_split('/\s+/', trim((string) $filters[9]), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($detailTokens as $value) {
                    $naQuery->where(function (Builder $detailsQuery) use ($value): void {
                        $detailsQuery
                            ->where('code', 'like', '%'.$value.'%')
                            ->orWhere('type', 'like', '%'.$value.'%')
                            ->orWhereHas('protocols', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'))
                            ->orWhereHas('tubes', fn (Builder $q) => $q->where('alias_code', 'like', '%'.$value.'%'))
                            ->orWhereHasMorph('nucleic_content', [
                                HumanSamples::class,
                            ], function (Builder $q) use ($value): void {
                                $q->where('code', 'like', '%'.$value.'%')
                                    ->orWhereHas('sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                            })
                            ->orWhereHasMorph('nucleic_content', [
                                AnimalSamples::class,
                            ], function (Builder $q) use ($value): void {
                                $q->where('code', 'like', '%'.$value.'%')
                                    ->orWhereHas('animals.animal_species', fn (Builder $sq) => $sq->where('name_common', 'like', '%'.$value.'%')->orWhere('name_scientific', 'like', '%'.$value.'%'))
                                    ->orWhereHas('sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                            })
                            ->orWhereHasMorph('nucleic_content', [
                                EnvironmentSamples::class,
                            ], function (Builder $q) use ($value): void {
                                $q->where('code', 'like', '%'.$value.'%')
                                    ->orWhere('area', 'like', '%'.$value.'%')
                                    ->orWhereHas('environment_sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                            })
                            ->orWhereHasMorph('nucleic_content', [
                                ParasiteSamples::class,
                            ], function (Builder $q) use ($value): void {
                                $q->where('code', 'like', '%'.$value.'%')
                                    ->orWhereHas('parasites.parasite_species', fn (Builder $sq) => $sq->where('name_scientific', 'like', '%'.$value.'%'))
                                    ->orWhereHas('parasite_sample_types', fn (Builder $sq) => $sq->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                            })
                            ->orWhereHasMorph('nucleic_content', [
                                Cultures::class,
                            ], function (Builder $q) use ($value): void {
                                $q->where('code', 'like', '%'.$value.'%')
                                    ->orWhere('medium', 'like', '%'.$value.'%')
                                    ->orWhere('type', 'like', '%'.$value.'%')
                                    ->orWhereHas('cultures_content', fn (Builder $cq) => $cq->where('code', 'like', '%'.$value.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                            })
                            ->orWhereHasMorph('nucleic_content', [
                                Pools::class,
                            ], function (Builder $q) use ($value): void {
                                $q->where('code', 'like', '%'.$value.'%')
                                    ->orWhereHas('pool_contents.samples', fn (Builder $sq) => $sq->where('code', 'like', '%'.$value.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                            })
                            ->orWhereHasMorph('nucleic_content', [
                                Experiments::class,
                            ], function (Builder $q) use ($value): void {
                                $q->where('code', 'like', '%'.$value.'%')
                                    ->orWhereHas('protocols', fn (Builder $protocolQuery) => $protocolQuery->where('name', 'like', '%'.$value.'%'))
                                    ->orWhereHas('pathogens', fn (Builder $pathogenQuery) => $pathogenQuery->where('species', 'like', '%'.$value.'%'));
                            });
                    });
                }
            }

            if (! empty($filters[10])) {
                $value = trim((string) $filters[10]);
                $naQuery->whereHasMorph('nucleic_content', [
                    HumanSamples::class,
                    AnimalSamples::class,
                    EnvironmentSamples::class,
                    ParasiteSamples::class,
                    Cultures::class,
                    Pools::class,
                    Experiments::class,
                ], fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
            }
        });
    }

    private function applyCultureTubeFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (culture_tubes_selection):
        // 0 checkbox, 1 tube code, 2 preservant, 3 parent code, 4 step, 5 date cultured, 6 medium, 7 type, 8 incubation temp, 9 atmosphere, 10 content details, 11 content code, 12 cultured at, 13 cultured by
        if (! empty($filters[1])) {
            $query->where('tubes.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('tubes.preservant', 'like', '%'.trim((string) $filters[2]).'%');
        }

        $query->whereHasMorph('tubes_content', [Cultures::class], function (Builder $cQuery) use ($filters) {
            if (! empty($filters[3])) {
                $value = trim((string) $filters[3]);
                $cQuery->whereHas('parent', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
            }

            if (! empty($filters[4])) {
                $cQuery->where('step', 'like', '%'.trim((string) $filters[4]).'%');
            }

            if (! empty($filters[5])) {
                $cQuery->where('date_cultured', 'like', '%'.trim((string) $filters[5]).'%');
            }

            if (! empty($filters[6])) {
                $cQuery->where('medium', 'like', '%'.trim((string) $filters[6]).'%');
            }

            if (! empty($filters[7])) {
                $cQuery->where('type', 'like', '%'.trim((string) $filters[7]).'%');
            }

            if (! empty($filters[8])) {
                $cQuery->whereRaw('CAST(incubation_temp as TEXT) like ?', ['%'.trim((string) $filters[8]).'%']);
            }

            if (! empty($filters[9])) {
                $cQuery->where('athmosphere', 'like', '%'.trim((string) $filters[9]).'%');
            }

            if (! empty($filters[10])) {
                $this->applyMultiWordFilter($cQuery, (string) $filters[10], function (Builder $detailsQuery, string $value): void {
                    $detailsQuery
                        ->where('code', 'like', '%'.$value.'%')
                        ->orWhereHas('parent', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'))
                        ->orWhereHas('cultures_content', function (Builder $q) use ($value): void {
                            $q->where('code', 'like', '%'.$value.'%')
                                ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                        });
                });
            }

            if (! empty($filters[11])) {
                $value = trim((string) $filters[11]);
                $cQuery->whereHas('cultures_content', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
            }

            if (! empty($filters[12])) {
                $value = trim((string) $filters[12]);
                $cQuery->whereHas('laboratories', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
            }

            if (! empty($filters[13])) {
                $value = trim((string) $filters[13]);
                $cQuery->whereHas('people', function (Builder $q) use ($value) {
                    $q->where('first_name', 'like', '%'.$value.'%')
                        ->orWhere('last_name', 'like', '%'.$value.'%')
                        ->orWhere('title', 'like', '%'.$value.'%');
                });
            }
        });
    }

    private function applyPoolTubeFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (pool_tubes_selection):
        // 0 checkbox, 1 tube code, 2 preservant, 3 nr samples, 4 content type, 5 content codes, 6 date pooled, 7 location, 8 created by
        if (! empty($filters[1])) {
            $query->where('tubes.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('tubes.preservant', 'like', '%'.trim((string) $filters[2]).'%');
        }

        $query->whereHasMorph('tubes_content', [Pools::class], function (Builder $poolQuery) use ($filters) {
            if (! empty($filters[3])) {
                $poolQuery->whereRaw('CAST(nr_pooled as TEXT) like ?', ['%'.trim((string) $filters[3]).'%']);
            }

            if (! empty($filters[4])) {
                $value = strtolower(trim((string) $filters[4]));
                $map = [
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'env' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'nucleic' => NucleicAcids::class,
                    'culture' => Cultures::class,
                ];

                $matched = null;
                foreach ($map as $needle => $class) {
                    if (str_contains($value, $needle)) {
                        $matched = $class;
                        break;
                    }
                }

                $poolQuery->whereHas('pool_contents', function (Builder $q) use ($matched, $filters) {
                    if ($matched) {
                        $q->where('samples_type', $matched);
                    } else {
                        $q->where('samples_type', 'like', '%'.trim((string) $filters[4]).'%');
                    }
                });
            }

            if (! empty($filters[5])) {
                $value = trim((string) $filters[5]);
                $poolQuery->whereHas('pool_contents.samples', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
            }

            if (! empty($filters[6])) {
                $poolQuery->where('date_pooled', 'like', '%'.trim((string) $filters[6]).'%');
            }

            if (! empty($filters[7])) {
                $value = trim((string) $filters[7]);
                $poolQuery->whereHas('laboratories', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
            }

            if (! empty($filters[8])) {
                $value = trim((string) $filters[8]);
                $poolQuery->whereHas('people', function (Builder $q) use ($value) {
                    $q->where('first_name', 'like', '%'.$value.'%')
                        ->orWhere('last_name', 'like', '%'.$value.'%')
                        ->orWhere('title', 'like', '%'.$value.'%');
                });
            }
        });
    }
}
