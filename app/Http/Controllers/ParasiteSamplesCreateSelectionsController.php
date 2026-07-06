<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParasiteSamplesCreateSelectionsController extends Controller
{
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

    public function humanSamples(Request $request): View
    {
        $projectId = (int) session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = HumanSamples::query()
            ->where('human_samples.projects_id', $projectId)
            ->with([
                'humans',
                'sample_types',
                'sampling_sites',
                'people',
            ])
            ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'))
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
        $paginationPath = route('parasites.create.samples.human');

        return view('samples.humans.modals.human_samples_selection', compact('human_samples', 'paginationPath'));
    }

    public function animalSamples(Request $request): View
    {
        $projectId = (int) session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = AnimalSamples::query()
            ->where('animal_samples.projects_id', $projectId)
            ->with([
                'animals',
                'animals.animal_species',
                'sample_types',
                'sampling_sites',
                'people',
                'locations',
            ])
            ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'))
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
        $paginationPath = route('parasites.create.samples.animal');

        return view('samples.animals.modals.animal_samples_selection', compact('animal_samples', 'paginationPath'));
    }

    public function environmentSamples(Request $request): View
    {
        $projectId = (int) session('selected_project_id');
        $filters = (array) $request->query('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = $this->resolveSortDir($request);

        $query = EnvironmentSamples::query()
            ->where('environment_samples.projects_id', $projectId)
            ->with([
                'environment_sample_types',
                'sampling_sites',
                'people',
            ])
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
        $paginationPath = route('parasites.create.samples.environment');

        return view('samples.environment.modals.environment_samples_selection', compact('environment_samples', 'paginationPath'));
    }

    public function humanSamplesSearch(Request $request): JsonResponse
    {
        return $this->sampleSearch($request, HumanSamples::class);
    }

    public function animalSamplesSearch(Request $request): JsonResponse
    {
        return $this->sampleSearch($request, AnimalSamples::class);
    }

    public function environmentSamplesSearch(Request $request): JsonResponse
    {
        return $this->sampleSearch($request, EnvironmentSamples::class);
    }

    private function sampleSearch(Request $request, string $modelClass): JsonResponse
    {
        $projectId = (int) session('selected_project_id');
        $q = (string) $request->query('q', '');

        $query = $modelClass::query()
            ->where($modelClass::query()->getModel()->getTable().'.projects_id', $projectId)
            ->select(['id', 'code'])
            ->when($q !== '', fn (Builder $builder) => $builder->where('code', 'like', '%'.$q.'%'))
            ->orderBy('code')
            ->limit(20);

        if ($modelClass === HumanSamples::class || $modelClass === AnimalSamples::class) {
            $query->whereHas('sample_types', fn (Builder $typeQuery) => $typeQuery->where('category', 'non_host_derived'));
        }

        if ($modelClass === EnvironmentSamples::class) {
            // Parasites can be identified from any environmental sample type.
        }

        /** @var array<int, array{value:int, text:string, code:string}> $results */
        $results = $query
            ->get()
            ->map(fn ($row) => [
                'value' => $row->id,
                'text' => $row->code,
                'code' => $row->code,
            ])
            ->all();

        return response()->json($results);
    }

    private function applyHumanSamplesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter($filters, fn ($value) => is_string($value) ? trim($value) !== '' : false);

        if (! $filters) {
            return;
        }

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
            $value = trim((string) $filters[4]);
            $query->where('date_collected', 'like', '%'.$value.'%');
        }

        if (! empty($filters[5])) {
            $value = trim((string) $filters[5]);
            $query->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[6])) {
            $query->where('latitude', 'like', '%'.trim((string) $filters[6]).'%');
        }

        if (! empty($filters[7])) {
            $query->where('longitude', 'like', '%'.trim((string) $filters[7]).'%');
        }

        if (! empty($filters[8])) {
            $value = trim((string) $filters[8]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%');
            });
        }
    }

    private function applyAnimalSamplesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter($filters, fn ($value) => is_string($value) ? trim($value) !== '' : false);

        if (! $filters) {
            return;
        }

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
            $query->whereHas('animals', fn (Builder $q) => $q->where('age', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[7])) {
            $value = trim((string) $filters[7]);
            $query->whereHas('sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[8])) {
            $value = trim((string) $filters[8]);
            $query->where('animal_samples.date_collected', 'like', '%'.$value.'%');
        }

        if (! empty($filters[9])) {
            $value = trim((string) $filters[9]);
            $query->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[10])) {
            $query->where('latitude', 'like', '%'.trim((string) $filters[10]).'%');
        }

        if (! empty($filters[11])) {
            $query->where('longitude', 'like', '%'.trim((string) $filters[11]).'%');
        }

        if (! empty($filters[12])) {
            $value = trim((string) $filters[12]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%');
            });
        }

        if (! empty($filters[13])) {
            $value = trim((string) $filters[13]);
            $query->whereHas('locations', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[14])) {
            $value = trim((string) $filters[14]);
            if ($value === '1' || strcasecmp($value, 'yes') === 0) {
                $query->where('processed', 1);
            } elseif ($value === '0' || strcasecmp($value, 'no') === 0) {
                $query->where('processed', 0);
            }
        }
    }

    private function applyEnvironmentSamplesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter($filters, fn ($value) => is_string($value) ? trim($value) !== '' : false);

        if (! $filters) {
            return;
        }

        if (! empty($filters[1])) {
            $query->where('environment_samples.code', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $value = trim((string) $filters[2]);
            $query->whereHas('environment_sample_types', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[3])) {
            $value = trim((string) $filters[3]);
            $query->where('date_collected', 'like', '%'.$value.'%');
        }

        if (! empty($filters[4])) {
            $value = trim((string) $filters[4]);
            $query->whereHas('sampling_sites', fn (Builder $q) => $q->where('name', 'like', '%'.$value.'%'));
        }

        if (! empty($filters[5])) {
            $query->where('area', 'like', '%'.trim((string) $filters[5]).'%');
        }

        if (! empty($filters[6])) {
            $query->where('latitude', 'like', '%'.trim((string) $filters[6]).'%');
        }

        if (! empty($filters[7])) {
            $query->where('longitude', 'like', '%'.trim((string) $filters[7]).'%');
        }

        if (! empty($filters[8])) {
            $value = trim((string) $filters[8]);
            $query->whereHas('people', function (Builder $q) use ($value) {
                $q->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%');
            });
        }
    }
}
