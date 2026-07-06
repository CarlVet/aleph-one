<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use App\Models\SamplingSites;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ParasiteSamplesDashboardMapPointsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $cursor = (int) $request->query('cursor', 0);
        $limit = (int) $request->query('limit', 1500);
        $limit = max(100, min($limit, 2000));

        $query = $this->filteredParasiteSamplesQuery($request, $isGuestMode, $projectId)
            ->select([
                'parasite_samples.id',
                'parasite_samples.code',
                'parasites.stage',
                'parasites.sex',
                'parasites.state',
                'parasites.parasites_origin_type',
                'parasites.parasites_origin_id',
                'parasite_species.name_scientific as parasite_species',
                'parasite_species.genus as parasite_genus',
                'parasite_sample_types.name as sample_type',
                'origin_humans.ethnicity as human_ethnicity',
                'origin_humans.occupation as human_occupation',
                'origin_countries.name as human_country',
                'origin_animal_species.name_common as animal_species',
                'origin_animals.age as animal_age',
                'origin_animals.sex as animal_sex',
            ])
            ->where('parasite_samples.id', '>', $cursor)
            ->orderBy('parasite_samples.id')
            ->limit($limit);

        $rows = $query->get();

        $samplingSites = SamplingSites::query()
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->keyBy('id');

        $points = $this->rowsToPoints($rows, $samplingSites);

        $nextCursor = null;
        if ($rows->count() === $limit) {
            $nextCursor = $rows->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }

    private function filteredParasiteSamplesQuery(Request $request, bool $isGuestMode, ?int $projectId): Builder
    {
        $query = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->leftJoin('human_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'human_samples.id')
                    ->where('parasites.parasites_origin_type', HumanSamples::class);
            })
            ->leftJoin('animal_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'animal_samples.id')
                    ->where('parasites.parasites_origin_type', AnimalSamples::class);
            })
            ->leftJoin('animals as origin_animals', 'animal_samples.animals_id', '=', 'origin_animals.id')
            ->leftJoin('animal_species as origin_animal_species', 'origin_animals.animal_species_id', '=', 'origin_animal_species.id')
            ->leftJoin('environment_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'environment_samples.id')
                    ->where('parasites.parasites_origin_type', EnvironmentSamples::class);
            })
            ->leftJoin('humans as origin_humans', 'human_samples.humans_id', '=', 'origin_humans.id')
            ->leftJoin('countries as origin_countries', 'origin_humans.countries_id', '=', 'origin_countries.id');

        $sampleVisibility = (string) $request->query('sampleVisibility', 'all');

        if ($isGuestMode) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $projectId);

            if ($sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        $parasiteSpeciesFilter = (string) $request->query('parasiteSpeciesFilter', '');
        if ($parasiteSpeciesFilter !== '') {
            $query->where('parasite_species.name_scientific', $parasiteSpeciesFilter);
        }

        $parasiteGenusFilter = (string) $request->query('parasiteGenusFilter', '');
        if ($parasiteGenusFilter !== '') {
            $query->where('parasite_species.genus', $parasiteGenusFilter);
        }

        $parasiteFamilyFilter = (string) $request->query('parasiteFamilyFilter', '');
        if ($parasiteFamilyFilter !== '') {
            $query->where('parasite_species.family', $parasiteFamilyFilter);
        }

        $sampleTypeFilter = (string) $request->query('sampleTypeFilter', '');
        if ($sampleTypeFilter !== '') {
            $query->where('parasite_sample_types.name', $sampleTypeFilter);
        }

        $stageFilter = (string) $request->query('stageFilter', '');
        if ($stageFilter !== '') {
            $query->where('parasites.stage', $stageFilter);
        }

        $sexFilter = (string) $request->query('sexFilter', '');
        if ($sexFilter !== '') {
            $query->where('parasites.sex', $sexFilter);
        }

        $stateFilter = (string) $request->query('stateFilter', '');
        if ($stateFilter !== '') {
            $query->where('parasites.state', $stateFilter);
        }

        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'parasite_samples.id')
                    ->where('sub_project_assignments.assignable_type', ParasiteSamples::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $originTypeFilter = (string) $request->query('originTypeFilter', 'all');
        if ($originTypeFilter !== '' && $originTypeFilter !== 'all') {
            $originType = match ($originTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            if ($originType) {
                $query->where('parasites.parasites_origin_type', $originType);
            }
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        $originDateExpr = DB::raw('COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)');

        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween($originDateExpr, [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where($originDateExpr, '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where($originDateExpr, '<=', $endDate);
        }

        $originAnimalSpeciesFilter = (string) $request->query('originAnimalSpeciesFilter', '');
        if ($originAnimalSpeciesFilter !== '' && $originTypeFilter === 'animal') {
            $query->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->where('origin_animal_species.name_common', $originAnimalSpeciesFilter);
        }

        $originAnimalSexFilter = (string) $request->query('originAnimalSexFilter', '');
        if ($originAnimalSexFilter !== '' && $originTypeFilter === 'animal') {
            $query->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->where('origin_animals.sex', $originAnimalSexFilter);
        }

        $originAnimalAgeFilter = (string) $request->query('originAnimalAgeFilter', '');
        if ($originAnimalAgeFilter !== '' && $originTypeFilter === 'animal') {
            $query->where('parasites.parasites_origin_type', AnimalSamples::class)
                ->where('origin_animals.age', $originAnimalAgeFilter);
        }

        $originHumanEthnicityFilter = (string) $request->query('originHumanEthnicityFilter', '');
        if ($originHumanEthnicityFilter !== '' && $originTypeFilter === 'human') {
            $query->where('parasites.parasites_origin_type', HumanSamples::class)
                ->where('origin_humans.ethnicity', $originHumanEthnicityFilter);
        }

        $originHumanOccupationFilter = (string) $request->query('originHumanOccupationFilter', '');
        if ($originHumanOccupationFilter !== '' && $originTypeFilter === 'human') {
            $query->where('parasites.parasites_origin_type', HumanSamples::class)
                ->where('origin_humans.occupation', $originHumanOccupationFilter);
        }

        $originHumanCountryFilter = (string) $request->query('originHumanCountryFilter', '');
        if ($originHumanCountryFilter !== '' && $originTypeFilter === 'human') {
            $query->where('parasites.parasites_origin_type', HumanSamples::class)
                ->where('origin_countries.name', $originHumanCountryFilter);
        }

        return $query;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, SamplingSites>  $samplingSites
     * @return array<int, array{latitude: float, longitude: float, stage: string, sex: string, state: ?string, type: string, code: ?string, parasite_species: ?string, parasite_genus: ?string, sample_type: ?string, human_ethnicity: ?string, human_occupation: ?string, human_country: ?string, animal_species: ?string, animal_age: ?string, animal_sex: ?string, sampling_site_id: ?int, sampling_site_name: ?string}>
     */
    private function rowsToPoints(Collection $rows, Collection $samplingSites): array
    {
        $byType = $rows->groupBy(fn ($r) => (string) $r->parasites_origin_type);

        $human = $this->loadPrimarySamples($byType->get(HumanSamples::class, collect()), HumanSamples::class);
        $animal = $this->loadPrimarySamples($byType->get(AnimalSamples::class, collect()), AnimalSamples::class);
        $environment = $this->loadPrimarySamples($byType->get(EnvironmentSamples::class, collect()), EnvironmentSamples::class);

        $primary = $human->merge($animal)->merge($environment);

        $points = [];

        foreach ($rows as $row) {
            $originType = (string) $row->parasites_origin_type;
            $originId = (int) $row->parasites_origin_id;

            $origin = $primary->get($originType.'#'.$originId);
            if (! $origin) {
                continue;
            }

            $lat = $origin['latitude'];
            $lng = $origin['longitude'];
            $samplingSiteId = $origin['sampling_sites_id'];

            if ((! $lat || ! $lng) && $samplingSiteId) {
                $site = $samplingSites->get($samplingSiteId);
                $lat = $site?->latitude;
                $lng = $site?->longitude;
            }

            if (! $lat || ! $lng) {
                continue;
            }

            $samplingSiteName = null;
            if ($samplingSiteId) {
                $samplingSiteName = $samplingSites->get($samplingSiteId)?->name;
            }

            $points[] = [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'stage' => (string) $row->stage,
                'sex' => (string) $row->sex,
                'state' => $row->state !== null ? (string) $row->state : null,
                'type' => match ($originType) {
                    HumanSamples::class => 'Human samples',
                    AnimalSamples::class => 'Animal samples',
                    EnvironmentSamples::class => 'Environmental samples',
                    default => 'Unknown',
                },
                'code' => $row->code,
                'parasite_species' => $row->parasite_species,
                'parasite_genus' => $row->parasite_genus,
                'sample_type' => $row->sample_type,
                'human_ethnicity' => $row->human_ethnicity,
                'human_occupation' => $row->human_occupation,
                'human_country' => $row->human_country,
                'animal_species' => $row->animal_species,
                'animal_age' => $row->animal_age !== null ? (string) $row->animal_age : null,
                'animal_sex' => $row->animal_sex,
                'sampling_site_id' => $samplingSiteId,
                'sampling_site_name' => $samplingSiteName,
            ];
        }

        return $points;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{id:int, code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int, model:string}>
     */
    private function loadPrimarySamples(Collection $rows, string $modelClass): Collection
    {
        $ids = $rows->pluck('parasites_origin_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $models = $modelClass::query()
            ->whereIn('id', $ids)
            ->get(['id', 'code', 'latitude', 'longitude', 'sampling_sites_id']);

        return $models->mapWithKeys(function ($row) use ($modelClass) {
            return [
                $modelClass.'#'.$row->id => [
                    'id' => $row->id,
                    'code' => $row->code ?? null,
                    'latitude' => $row->latitude ? (float) $row->latitude : null,
                    'longitude' => $row->longitude ? (float) $row->longitude : null,
                    'sampling_sites_id' => $row->sampling_sites_id,
                    'model' => $modelClass,
                ],
            ];
        });
    }
}
