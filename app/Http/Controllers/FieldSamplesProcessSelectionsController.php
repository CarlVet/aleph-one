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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FieldSamplesProcessSelectionsController extends Controller
{
    use FiltersContentDetails;

    public function human(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = HumanSamples::query()
            ->where('human_samples.projects_id', $projectId)
            ->with(['humans:id,first_name,last_name', 'sample_types:id,name', 'sampling_sites:id,name', 'people:id,title,first_name,last_name'])
            ->orderByDesc('id');

        $this->applyHumanSamplesFilters($query, $filters);

        $sortMap = [
            1 => 'human_samples.code',
            2 => 'humans.last_name',
            3 => 'sample_types.name',
            4 => 'human_samples.date_collected',
            5 => 'sampling_sites.name',
            6 => 'human_samples.latitude',
            7 => 'human_samples.longitude',
            8 => 'people.last_name',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('human_samples.*')
                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
                ->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->leftJoin('people', 'human_samples.people_id', '=', 'people.id')
                ->reorder()
                ->orderBy($sortMap[$sortCol], $sortDir)
                ->orderBy('human_samples.id');
        }

        $human_samples = $query->paginate($perPage, pageName: 'human_samples_page')->withQueryString();

        return view('samples.humans.modals.human_samples_selection', compact('human_samples'));
    }

    public function animal(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = AnimalSamples::query()
            ->where('animal_samples.projects_id', $projectId)
            ->with([
                'animals:id,code,field_label,sex,age,animal_species_id',
                'animals.animal_species:id,name_common',
                'sample_types:id,name',
                'sampling_sites:id,name',
                'people:id,title,first_name,last_name',
                'locations:id,name',
            ])
            ->orderByDesc('id');

        $this->applyAnimalSamplesFilters($query, $filters);

        $sortMap = [
            1 => 'animal_samples.code',
            2 => 'animals.code',
            3 => 'animals.field_label',
            4 => 'animal_species.name_common',
            5 => 'animals.sex',
            6 => 'animals.age',
            7 => 'sample_types.name',
            8 => 'animal_samples.date_collected',
            9 => 'sampling_sites.name',
            10 => 'animal_samples.latitude',
            11 => 'animal_samples.longitude',
            12 => 'people.last_name',
            13 => 'locations.name',
            14 => 'animal_samples.processed',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('animal_samples.*')
                ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
                ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->leftJoin('people', 'animal_samples.people_id', '=', 'people.id')
                ->leftJoin('locations', 'animal_samples.locations_id', '=', 'locations.id')
                ->reorder()
                ->orderBy($sortMap[$sortCol], $sortDir)
                ->orderBy('animal_samples.id');
        }

        $animal_samples = $query->paginate($perPage, pageName: 'animal_samples_page')->withQueryString();

        return view('samples.animals.modals.animal_samples_selection', compact('animal_samples'));
    }

    public function environment(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = EnvironmentSamples::query()
            ->where('environment_samples.projects_id', $projectId)
            ->with(['environment_sample_types:id,name', 'sampling_sites:id,name', 'people:id,title,first_name,last_name'])
            ->orderByDesc('id');

        $this->applyEnvironmentSamplesFilters($query, $filters);

        $sortMap = [
            1 => 'environment_samples.code',
            2 => 'environment_sample_types.name',
            3 => 'environment_samples.date_collected',
            4 => 'sampling_sites.name',
            5 => 'environment_samples.area',
            6 => 'environment_samples.latitude',
            7 => 'environment_samples.longitude',
            8 => 'people.last_name',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('environment_samples.*')
                ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
                ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->leftJoin('people', 'environment_samples.people_id', '=', 'people.id')
                ->reorder()
                ->orderBy($sortMap[$sortCol], $sortDir)
                ->orderBy('environment_samples.id');
        }

        $environment_samples = $query->paginate($perPage, pageName: 'environment_samples_page')->withQueryString();

        return view('samples.environment.modals.environment_samples_selection', compact('environment_samples'));
    }

    public function parasite(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = ParasiteSamples::query()
            ->where('parasite_samples.projects_id', $projectId)
            ->with([
                'parasites:id,parasite_species_id,parasites_origin_type,parasites_origin_id',
                'parasites.parasite_species:id,name_scientific',
                'parasites.parasites_origin',
                'parasites.parasites_origin.sampling_sites',
                'parasite_sample_types:id,name',
                'people:id,title,first_name,last_name',
            ])
            ->orderByDesc('id');

        $this->applyParasiteSamplesFilters($query, $filters);

        $sortMap = [
            1 => 'parasite_samples.code',
            2 => 'parasite_species.name_scientific',
            3 => 'parasite_sample_types.name',
            4 => 'origin_sampling_sites.name',
            5 => 'parasite_samples.date_processed',
            6 => 'people.last_name',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('parasite_samples.*')
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
                ->leftJoin('people', 'parasite_samples.people_id', '=', 'people.id')
                ->reorder()
                ->orderBy($sortMap[$sortCol], $sortDir)
                ->orderBy('parasite_samples.id');
        }

        $parasite_samples = $query->paginate($perPage, pageName: 'parasite_samples_page')->withQueryString();

        return view('samples.parasites.modals.parasite_samples_selection', compact('parasite_samples'));
    }

    public function nucleic(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = NucleicAcids::query()
            ->where('nucleic_acids.projects_id', $projectId)
            ->with(['nucleic_content', 'protocols:id,name', 'people:id,title,first_name,last_name', 'laboratories:id,name'])
            ->orderByDesc('id');

        $this->applyNucleicAcidsFilters($query, $filters);

        $sortMap = [
            1 => 'nucleic_acids.code',
            2 => 'nucleic_acids.nucleic_content_type',
            3 => 'nucleic_content_code',
            4 => 'nucleic_acids.type',
            5 => 'protocols.name',
            6 => 'nucleic_acids.date_extracted',
            7 => 'people.last_name',
            8 => 'laboratories.name',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('nucleic_acids.*')
                ->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id')
                ->leftJoin('people', 'nucleic_acids.people_id', '=', 'people.id')
                ->leftJoin('laboratories', 'nucleic_acids.laboratories_id', '=', 'laboratories.id')
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
                ->reorder()
                ->when($sortCol === 3, function (Builder $q) use ($sortDir): void {
                    $q->orderByRaw(
                        'COALESCE(nucleic_origin_human_samples.code, nucleic_origin_animal_samples.code, nucleic_origin_environment_samples.code, nucleic_origin_parasite_samples.code, nucleic_origin_cultures.code, nucleic_origin_pools.code) '.$sortDir
                    );
                }, function (Builder $q) use ($sortCol, $sortDir, $sortMap): void {
                    $q->orderBy($sortMap[$sortCol], $sortDir);
                })
                ->orderBy('nucleic_acids.id');
        }

        $nucleic_acids = $query->paginate($perPage, pageName: 'nucleic_acids_page')->withQueryString();

        return view('samples.nucleic_acids.modals.nucleic_acids_selection', compact('nucleic_acids'));
    }

    public function culture(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = Cultures::query()
            ->where('cultures.projects_id', $projectId)
            ->with(['cultures_content', 'parent', 'people:id,title,first_name,last_name', 'laboratories:id,name'])
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

        return view('samples.cultures.modals.cultures_selection', compact('cultures'));
    }

    public function pool(Request $request): View
    {
        $projectId = session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = Pools::query()
            ->where('pools.projects_id', $projectId)
            ->with(['pool_contents:id,pools_id,samples_type,samples_id', 'pool_contents.samples', 'laboratories:id,name', 'people:id,title,first_name,last_name'])
            ->orderByDesc('id');

        $this->applyPoolsFilters($query, $filters);

        $sortMap = [
            1 => 'pools.code',
            2 => 'pools.nr_pooled',
            3 => 'pool_contents_agg.samples_type_sort',
            4 => 'pool_contents_agg.min_content_code',
            5 => 'pools.date_pooled',
            6 => 'laboratories.name',
            7 => 'people.last_name',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->select('pools.*')
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
                ->leftJoin('people', 'pools.people_id', '=', 'people.id')
                ->reorder()
                ->orderBy($sortMap[$sortCol], $sortDir)
                ->orderBy('pools.id');
        }

        $pools = $query->paginate($perPage, pageName: 'pools_page')->withQueryString();

        return view('samples.pools.modals.pools_selection', compact('pools'));
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->integer('perPage', 50);

        return in_array($perPage, [10, 50, 100, 200], true) ? $perPage : 50;
    }

    private function resolveSortDir(Request $request): string
    {
        return strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
    }

    public function humanSearch(Request $request): JsonResponse
    {
        return $this->simpleSearch($request, HumanSamples::class);
    }

    public function animalSearch(Request $request): JsonResponse
    {
        return $this->simpleSearch($request, AnimalSamples::class);
    }

    public function environmentSearch(Request $request): JsonResponse
    {
        return $this->simpleSearch($request, EnvironmentSamples::class);
    }

    public function parasiteSearch(Request $request): JsonResponse
    {
        return $this->simpleSearch($request, ParasiteSamples::class);
    }

    public function nucleicSearch(Request $request): JsonResponse
    {
        return $this->simpleSearch($request, NucleicAcids::class);
    }

    public function cultureSearch(Request $request): JsonResponse
    {
        return $this->simpleSearch($request, Cultures::class);
    }

    public function poolSearch(Request $request): JsonResponse
    {
        return $this->simpleSearch($request, Pools::class);
    }

    private function simpleSearch(Request $request, string $modelClass): JsonResponse
    {
        $projectId = session('selected_project_id');
        $q = (string) $request->query('q', '');

        $results = $modelClass::query()
            ->where($modelClass::query()->getModel()->getTable().'.projects_id', $projectId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('code', 'like', '%'.$q.'%');
            })
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code'])
            ->map(fn ($row) => ['value' => $row->id, 'text' => $row->code])
            ->values();

        return response()->json($results);
    }

    private function applyHumanSamplesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (human_samples_selection):
        // 0 checkbox, 1 code, 2 patient, 3 sample type, 4 date collected, 5 sampling site, 6 lat, 7 long, 8 collector
        if (! empty($filters[1])) {
            $query->where('human_samples.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $value = trim((string) $filters[2]);
            $query->whereHas('humans', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%');
            });
        }

        if (! empty($filters[3])) {
            $value = trim((string) $filters[3]);
            $query->whereHas('sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[4])) {
            $query->where('human_samples.date_collected', 'like', '%'.trim((string) $filters[4]).'%');
        }

        if (! empty($filters[5])) {
            $value = trim((string) $filters[5]);
            $query->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[6])) {
            $query->whereRaw('CAST(human_samples.latitude as TEXT) like ?', ['%'.trim((string) $filters[6]).'%']);
        }

        if (! empty($filters[7])) {
            $query->whereRaw('CAST(human_samples.longitude as TEXT) like ?', ['%'.trim((string) $filters[7]).'%']);
        }

        if (! empty($filters[8])) {
            $value = trim((string) $filters[8]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%')
                    ->orWhere('title', 'like', '%'.$value.'%');
            });
        }
    }

    private function applyAnimalSamplesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (animal_samples_selection):
        // 0 checkbox, 1 code, 2 animal code, 3 field id, 4 species, 5 sex, 6 age, 7 sample type, 8 date, 9 park, 10 lat, 11 long, 12 collector, 13 location, 14 processed
        if (! empty($filters[1])) {
            $query->where('animal_samples.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $value = trim((string) $filters[2]);
            $query->whereHas('animals', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[3])) {
            $value = trim((string) $filters[3]);
            $query->whereHas('animals', fn (Builder $q) => $q->where('field_label', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[4])) {
            $value = trim((string) $filters[4]);
            $query->whereHas('animals.animal_species', fn (Builder $q) => $q->where('name_common', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[5])) {
            $value = trim((string) $filters[5]);
            $query->whereHas('animals', fn (Builder $q) => $q->where('sex', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[6])) {
            $value = trim((string) $filters[6]);
            $query->whereHas('animals', fn (Builder $q) => $q->whereRaw('CAST(age as TEXT) like ?', ['%'.$value.'%']));
        }

        if (! empty($filters[7])) {
            $value = trim((string) $filters[7]);
            $query->whereHas('sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[8])) {
            $query->where('animal_samples.date_collected', 'like', '%'.trim((string) $filters[8]).'%');
        }

        if (! empty($filters[9])) {
            $value = trim((string) $filters[9]);
            $query->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[10])) {
            $query->whereRaw('CAST(animal_samples.latitude as TEXT) like ?', ['%'.trim((string) $filters[10]).'%']);
        }

        if (! empty($filters[11])) {
            $query->whereRaw('CAST(animal_samples.longitude as TEXT) like ?', ['%'.trim((string) $filters[11]).'%']);
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
            $query->whereHas('locations', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[14])) {
            $value = strtolower(trim((string) $filters[14]));
            if (in_array($value, ['yes', 'y', '1', 'true'], true)) {
                $query->where('animal_samples.processed', 1);
            } elseif (in_array($value, ['no', 'n', '0', 'false'], true)) {
                $query->where(function (Builder $q): void {
                    $q->where('animal_samples.processed', 0)
                        ->orWhereNull('animal_samples.processed');
                });
            } else {
                $query->whereRaw('CAST(animal_samples.processed as TEXT) like ?', ['%'.trim((string) $filters[14]).'%']);
            }
        }
    }

    private function applyEnvironmentSamplesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (environment_samples_selection):
        // 0 checkbox, 1 code, 2 sample type, 3 date collected, 4 sampling site, 5 area, 6 lat, 7 long, 8 collector
        if (! empty($filters[1])) {
            $query->where('environment_samples.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $value = trim((string) $filters[2]);
            $query->whereHas('environment_sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[3])) {
            $query->where('environment_samples.date_collected', 'like', '%'.trim((string) $filters[3]).'%');
        }

        if (! empty($filters[4])) {
            $value = trim((string) $filters[4]);
            $query->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[5])) {
            $query->where('environment_samples.area', 'like', '%'.trim((string) $filters[5]).'%');
        }

        if (! empty($filters[6])) {
            $query->whereRaw('CAST(environment_samples.latitude as TEXT) like ?', ['%'.trim((string) $filters[6]).'%']);
        }

        if (! empty($filters[7])) {
            $query->whereRaw('CAST(environment_samples.longitude as TEXT) like ?', ['%'.trim((string) $filters[7]).'%']);
        }

        if (! empty($filters[8])) {
            $value = trim((string) $filters[8]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%')
                    ->orWhere('title', 'like', '%'.$value.'%');
            });
        }
    }

    private function applyParasiteSamplesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (parasite_samples_selection):
        // 0 checkbox, 1 code, 2 parasite species, 3 sample type, 4 sampling site, 5 collection date, 6 identified by
        if (! empty($filters[1])) {
            $query->where('parasite_samples.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $value = trim((string) $filters[2]);
            $query->whereHas('parasites.parasite_species', fn (Builder $q) => $q->where('name_scientific', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[3])) {
            $value = trim((string) $filters[3]);
            $query->whereHas('parasite_sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[4])) {
            $value = trim((string) $filters[4]);
            $query->whereHas('parasites.parasites_origin.sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[5])) {
            $query->where('parasite_samples.date_processed', 'like', '%'.trim((string) $filters[5]).'%');
        }

        if (! empty($filters[6])) {
            $value = trim((string) $filters[6]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%')
                    ->orWhere('title', 'like', '%'.$value.'%');
            });
        }
    }

    private function applyNucleicAcidsFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (nucleic_acids_selection):
        // 0 checkbox, 1 code, 2 content type, 3 content code, 4 nucleic type, 5 protocol, 6 extraction date, 7 extracted by, 8 extracted at
        if (! empty($filters[1])) {
            $query->where('nucleic_acids.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('nucleic_acids.nucleic_content_type', 'like', '%'.trim((string) $filters[2]).'%');
        }

        if (! empty($filters[3])) {
            $value = trim((string) $filters[3]);
            $query->whereHasMorph('nucleic_content', [
                HumanSamples::class,
                AnimalSamples::class,
                EnvironmentSamples::class,
                ParasiteSamples::class,
                Cultures::class,
                Pools::class,
            ], fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[4])) {
            $query->where('nucleic_acids.type', 'like', '%'.trim((string) $filters[4]).'%');
        }

        if (! empty($filters[5])) {
            $value = trim((string) $filters[5]);
            $query->whereHas('protocols', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[6])) {
            $query->where('nucleic_acids.date_extracted', 'like', '%'.trim((string) $filters[6]).'%');
        }

        if (! empty($filters[7])) {
            $value = trim((string) $filters[7]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%')
                    ->orWhere('title', 'like', '%'.$value.'%');
            });
        }

        if (! empty($filters[8])) {
            $value = trim((string) $filters[8]);
            $query->whereHas('laboratories', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }
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
            $query->where('cultures.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('cultures.type', 'like', '%'.trim((string) $filters[2]).'%');
        }

        if (! empty($filters[3])) {
            $query->where('cultures.cultures_content_type', 'like', '%'.trim((string) $filters[3]).'%');
        }

        if (! empty($filters[4])) {
            $this->applyMultiWordFilter($query, (string) $filters[4], function (Builder $detailsQuery, string $value): void {
                $detailsQuery
                    ->where('cultures.code', 'like', '%'.$value.'%')
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
            $query->where('cultures.step', 'like', '%'.trim((string) $filters[7]).'%');
        }

        if (! empty($filters[8])) {
            $query->where('cultures.date_cultured', 'like', '%'.trim((string) $filters[8]).'%');
        }

        if (! empty($filters[9])) {
            $query->where('cultures.medium', 'like', '%'.trim((string) $filters[9]).'%');
        }

        if (! empty($filters[10])) {
            $query->whereRaw('CAST(incubation_temp as TEXT) like ?', ['%'.trim((string) $filters[10]).'%']);
        }

        if (! empty($filters[11])) {
            $query->where('cultures.athmosphere', 'like', '%'.trim((string) $filters[11]).'%');
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

    private function applyPoolsFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes (pools_selection):
        // 0 checkbox, 1 code, 2 nr samples, 3 content type, 4 content codes, 5 date pooled, 6 pooled at, 7 created by
        if (! empty($filters[1])) {
            $query->where('pools.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->whereRaw('CAST(pools.nr_pooled as TEXT) like ?', ['%'.trim((string) $filters[2]).'%']);
        }

        if (! empty($filters[3])) {
            $query->whereHas('pool_contents', fn (Builder $q) => $q->where('samples_type', 'like', '%'.trim((string) $filters[3]).'%'));
        }

        if (! empty($filters[4])) {
            $value = trim((string) $filters[4]);
            $query->whereHas('pool_contents.samples', fn (Builder $q) => $q->where('code', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[5])) {
            $query->where('pools.date_pooled', 'like', '%'.trim((string) $filters[5]).'%');
        }

        if (! empty($filters[6])) {
            $value = trim((string) $filters[6]);
            $query->whereHas('laboratories', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[7])) {
            $value = trim((string) $filters[7]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%')
                    ->orWhere('title', 'like', '%'.$value.'%');
            });
        }
    }
}
