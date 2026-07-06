<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersContentDetails;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Tubes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CulturesCreateSelectionsController extends Controller
{
    use FiltersContentDetails;

    public function humanTubes(Request $request): View
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
        $paginationPath = route('cultures.create.tubes.human');

        return view('samples.humans.modals.human_tubes_selection', compact('human_tubes', 'paginationPath'));
    }

    public function animalTubes(Request $request): View
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
        $paginationPath = route('cultures.create.tubes.animal');

        return view('samples.animals.modals.animal_tubes_selection', compact('animal_tubes', 'paginationPath'));
    }

    public function environmentTubes(Request $request): View
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
        $paginationPath = route('cultures.create.tubes.environment');

        return view('samples.environment.modals.environment_tubes_selection', compact('environment_tubes', 'paginationPath'));
    }

    public function parasiteTubes(Request $request): View
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
        $paginationPath = route('cultures.create.tubes.parasite');

        return view('samples.parasites.modals.parasite_tubes_selection', compact('parasite_tubes', 'paginationPath'));
    }

    public function poolTubes(Request $request): View
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
        $paginationPath = route('cultures.create.tubes.pool');

        return view('samples.pools.modals.pool_tubes_selection', compact('pool_tubes', 'paginationPath'));
    }

    public function cultures(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = Cultures::query()
            ->where('projects_id', $projectId)
            ->with([
                'cultures_content',
                'parent',
                'people',
                'laboratories',
            ])
            ->orderByDesc('id');

        $this->applyCulturesFilters($query, $filters);

        $sortMap = [
            1 => 'cultures.code',
            2 => 'cultures.type',
            3 => 'cultures.cultures_content_type',
            5 => 'cultures_content_code',
            6 => 'parent_cultures.code',
            7 => 'cultures.step',
            8 => 'cultures.date_cultured',
            9 => 'cultures.medium',
            10 => 'cultures.incubation_temp',
            11 => 'cultures.athmosphere',
            12 => 'people.last_name',
            13 => 'laboratories.name',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('cultures.*')
                ->leftJoin('cultures as parent_cultures', 'cultures.parent_id', '=', 'parent_cultures.id')
                ->leftJoin('human_samples as culture_origin_human_samples', function ($join): void {
                    $join->on('cultures.cultures_content_id', '=', 'culture_origin_human_samples.id')
                        ->where('cultures.cultures_content_type', '=', HumanSamples::class);
                })
                ->leftJoin('animal_samples as culture_origin_animal_samples', function ($join): void {
                    $join->on('cultures.cultures_content_id', '=', 'culture_origin_animal_samples.id')
                        ->where('cultures.cultures_content_type', '=', AnimalSamples::class);
                })
                ->leftJoin('environment_samples as culture_origin_environment_samples', function ($join): void {
                    $join->on('cultures.cultures_content_id', '=', 'culture_origin_environment_samples.id')
                        ->where('cultures.cultures_content_type', '=', EnvironmentSamples::class);
                })
                ->leftJoin('parasite_samples as culture_origin_parasite_samples', function ($join): void {
                    $join->on('cultures.cultures_content_id', '=', 'culture_origin_parasite_samples.id')
                        ->where('cultures.cultures_content_type', '=', ParasiteSamples::class);
                })
                ->leftJoin('people', 'cultures.people_id', '=', 'people.id')
                ->leftJoin('laboratories', 'cultures.laboratories_id', '=', 'laboratories.id')
                ->reorder()
                ->when($sortCol === 5, function (Builder $q) use ($sortDir): void {
                    $q->orderByRaw(
                        'COALESCE(culture_origin_human_samples.code, culture_origin_animal_samples.code, culture_origin_environment_samples.code, culture_origin_parasite_samples.code) '.$sortDir
                    );
                }, function (Builder $q) use ($sortCol, $sortDir, $sortMap): void {
                    $q->orderBy($sortMap[$sortCol], $sortDir);
                })
                ->orderBy('cultures.id');
        }

        $cultures = $query->paginate($perPage, pageName: 'cultures_page')->withQueryString();
        $this->hydrateCultureTracebackDetails($cultures);
        $paginationPath = route('cultures.create.cultures');

        return view('samples.cultures.modals.cultures_selection', compact('cultures', 'paginationPath'));
    }

    public function humanTubesSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, HumanSamples::class);
    }

    public function animalTubesSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, AnimalSamples::class);
    }

    public function environmentTubesSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, EnvironmentSamples::class);
    }

    public function parasiteTubesSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, ParasiteSamples::class);
    }

    public function poolTubesSearch(Request $request): JsonResponse
    {
        return $this->tubeSearch($request, Pools::class);
    }

    public function culturesSearch(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $q = (string) $request->query('q', '');

        $results = Cultures::query()
            ->where('projects_id', $projectId)
            ->when($q !== '', fn (Builder $query) => $query->where('code', 'like', '%'.$q.'%'))
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code', 'alias_code'])
            ->map(fn ($row) => [
                'value' => $row->id,
                'text' => $row->code,
                'code' => $row->code,
                'alias_code' => $row->alias_code,
            ])
            ->values();

        return response()->json($results);
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
            Pools::class => [
                1 => 'tubes.code',
                2 => 'tubes.preservant',
                3 => 'pools.nr_pooled',
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
                Pools::class => $query
                    ->leftJoin('pools', 'tubes.tubes_content_id', '=', 'pools.id')
                    ->leftJoin('laboratories', 'pools.laboratories_id', '=', 'laboratories.id')
                    ->leftJoin('people', 'pools.people_id', '=', 'people.id'),
                default => null,
            };
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
            Pools::class => [
                'tubes_content',
                'tubes_content.pool_contents',
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

        $this->applyTubeFilters($query, $filters, $contentType);

        $this->applyTubeSelectionSorting($query, $contentType, $sortCol, $sortDir);

        return $query->paginate($perPage, pageName: $pageName)->withQueryString();
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
            $query->where('code', 'like', '%'.trim((string) $filters[1]).'%');
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
                $query->where('alias_code', 'like', '%'.trim((string) $filters[$idx]).'%');
            }
        }

        // Preservant column index differs per table.
        $preservantIndexes = match ($contentType) {
            HumanSamples::class => [3],
            AnimalSamples::class => [3],
            ParasiteSamples::class => [3],
            Pools::class => [2],
            default => [],
        };
        foreach ($preservantIndexes as $idx) {
            if (! empty($filters[$idx])) {
                $query->where('preservant', 'like', '%'.trim((string) $filters[$idx]).'%');
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
                $query->where('purpose', 'like', '%'.trim((string) $filters[$idx]).'%');
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

            if ($contentType === Pools::class) {
                if (! empty($filters[3])) {
                    $contentQuery->whereRaw('CAST(nr_pooled as TEXT) like ?', ['%'.trim((string) $filters[3]).'%']);
                }

                if (! empty($filters[6])) {
                    $contentQuery->where('date_pooled', 'like', '%'.trim((string) $filters[6]).'%');
                }

                if (! empty($filters[7])) {
                    $value = trim((string) $filters[7]);
                    $contentQuery->whereHas('laboratories', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
                }

                if (! empty($filters[8])) {
                    $value = trim((string) $filters[8]);
                    $contentQuery->whereHas('people', function (Builder $q) use ($value) {
                        $q->where('first_name', 'like', '%'.$value.'%')
                            ->orWhere('last_name', 'like', '%'.$value.'%')
                            ->orWhere('title', 'like', '%'.$value.'%');
                    });
                }
            }
        });
    }

    private function applyCulturesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes:
        // 0 checkbox, 1 code, 2 type, 3 content type, 4 content details, 5 content code, 6 parent code, 7 step, 8 date, 9 medium, 10 incubation temp, 11 atmosphere, 12 cultured by, 13 cultured at
        if (! empty($filters[1])) {
            $query->where('code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('type', 'like', '%'.trim((string) $filters[2]).'%');
        }

        if (! empty($filters[3])) {
            $query->where('cultures_content_type', 'like', '%'.trim((string) $filters[3]).'%');
        }

        if (! empty($filters[4])) {
            $this->applyMultiWordFilter($query, (string) $filters[4], function (Builder $detailsQuery, string $value): void {
                $detailsQuery
                    ->where('code', 'like', '%'.$value.'%')
                    ->orWhereHas('parent', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'))
                    ->orWhereHas('cultures_content', function (Builder $q) use ($value): void {
                        $q->where('code', 'like', '%'.$value.'%')
                            ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                    });
            });
        }

        if (! empty($filters[5])) {
            $value = trim((string) $filters[5]);
            $query->whereHas('cultures_content', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[6])) {
            $value = trim((string) $filters[6]);
            $query->whereHas('parent', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[7])) {
            $query->where('step', 'like', '%'.trim((string) $filters[7]).'%');
        }

        if (! empty($filters[8])) {
            $query->where('date_cultured', 'like', '%'.trim((string) $filters[8]).'%');
        }

        if (! empty($filters[9])) {
            $query->where('medium', 'like', '%'.trim((string) $filters[9]).'%');
        }

        if (! empty($filters[10])) {
            $query->whereRaw('CAST(incubation_temp as TEXT) like ?', ['%'.trim((string) $filters[10]).'%']);
        }

        if (! empty($filters[11])) {
            $query->where('athmosphere', 'like', '%'.trim((string) $filters[11]).'%');
        }

        if (! empty($filters[12])) {
            $value = trim((string) $filters[12]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%')
                    ->orWhere('title', 'like', '%'.$value.'%');
            });
        }

        if (! empty($filters[13])) {
            $value = trim((string) $filters[13]);
            $query->whereHas('laboratories', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }
    }

    private function hydrateCultureTracebackDetails(LengthAwarePaginator $paginator): void
    {
        $rows = $paginator->getCollection();
        if ($rows->isEmpty()) {
            return;
        }

        $rows->loadMorph('cultures_content', [
            HumanSamples::class => ['tubes'],
            AnimalSamples::class => ['tubes'],
            EnvironmentSamples::class => ['tubes'],
            ParasiteSamples::class => ['tubes'],
            NucleicAcids::class => ['tubes'],
            Cultures::class => ['tubes'],
            Pools::class => ['tubes'],
        ]);
    }

    private function tubeSearch(Request $request, string $contentType): JsonResponse
    {
        $projectId = session('selected_project_id');
        $q = (string) $request->query('q', '');

        $results = Tubes::query()
            ->where('projects_id', $projectId)
            ->where('tubes_content_type', $contentType)
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $q2) use ($q) {
                    $q2->where('code', 'like', '%'.$q.'%')
                        ->orWhere('alias_code', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code', 'alias_code'])
            ->map(fn ($row) => [
                'value' => $row->id,
                'text' => $row->code,
                'code' => $row->code,
                'alias_code' => $row->alias_code,
            ])
            ->values();

        return response()->json($results);
    }
}
