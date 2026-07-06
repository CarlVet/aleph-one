<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\SamplingSites;
use App\Models\Sequences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SequencesDashboardMapPointsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $cursor = (int) $request->query('cursor', 0);
        $limit = (int) $request->query('limit', 1500);
        $limit = max(100, min($limit, 2000));

        $rows = $this->filteredQuery($request, $isGuestMode, $projectId)
            ->select([
                'sequences.id',
                'sequences.code',
                'sequences.method',
                'sequences.instrument',
                'sequences.date_sequenced',
                'exp_na.code as nucleic_code',
                'orig_na.nucleic_content_type as nucleic_content_type',
                'orig_na.nucleic_content_id as nucleic_content_id',
                'laboratories.name as laboratory',
                DB::raw($this->peopleNameSql().' as sequenced_by'),
            ])
            ->where('sequences.id', '>', $cursor)
            ->orderBy('sequences.id')
            ->limit($limit)
            ->get();

        $samplingSites = SamplingSites::query()
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->keyBy('id');

        $points = $this->rowsToPoints($rows, $samplingSites);

        $nextCursor = null;
        if ($rows->count() === $limit) {
            $nextCursor = (int) $rows->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }

    private function filteredQuery(Request $request, bool $isGuestMode, ?int $projectId)
    {
        $query = Sequences::query()
            ->leftJoin('nucleic_acids as exp_na', 'sequences.nucleic_acids_id', '=', 'exp_na.id')
            ->leftJoin('experiments', function ($join) {
                $join->on('exp_na.nucleic_content_id', '=', 'experiments.id')
                    ->where('exp_na.nucleic_content_type', Experiments::class)
                    ->where('experiments.experiments_content_type', NucleicAcids::class);
            })
            ->leftJoin('nucleic_acids as orig_na', 'experiments.experiments_content_id', '=', 'orig_na.id')
            ->leftJoin('laboratories', 'sequences.laboratories_id', '=', 'laboratories.id')
            ->leftJoin('people', 'sequences.people_id', '=', 'people.id');

        if ($isGuestMode) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'exp_na.id')
                    ->where('tubes.tubes_content_type', NucleicAcids::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('sequences.projects_id', $projectId);
        }

        $sourceTypeFilter = (string) $request->query('sourceTypeFilter', 'all');
        if ($sourceTypeFilter !== '' && $sourceTypeFilter !== 'all') {
            $sourceType = match ($sourceTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                default => null,
            };

            if ($sourceType) {
                $query->where('orig_na.nucleic_content_type', $sourceType);
            }
        }

        $methodFilter = (string) $request->query('methodFilter', '');
        if ($methodFilter !== '') {
            $query->where('sequences.method', $methodFilter);
        }

        $instrumentFilter = (string) $request->query('instrumentFilter', '');
        if ($instrumentFilter !== '') {
            $query->where('sequences.instrument', $instrumentFilter);
        }

        $laboratoryFilter = (string) $request->query('laboratoryFilter', '');
        if ($laboratoryFilter !== '') {
            $query->where('laboratories.name', $laboratoryFilter);
        }

        $sequencedByFilter = (string) $request->query('sequencedByFilter', '');
        if ($sequencedByFilter !== '') {
            $query->whereRaw($this->peopleNameSql().' = ?', [$sequencedByFilter]);
        }
        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'sequences.id')
                    ->where('sub_project_assignments.assignable_type', Sequences::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $startLength = (string) $request->query('startLength', '');
        $endLength = (string) $request->query('endLength', '');
        if ($startLength !== '' && $endLength !== '') {
            $query->whereBetween('sequences.length', [(int) $startLength, (int) $endLength]);
        } elseif ($startLength !== '') {
            $query->where('sequences.length', '>=', (int) $startLength);
        } elseif ($endLength !== '') {
            $query->where('sequences.length', '<=', (int) $endLength);
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('sequences.date_sequenced', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('sequences.date_sequenced', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('sequences.date_sequenced', '<=', $endDate);
        }

        return $query;
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

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, SamplingSites>  $samplingSites
     * @return array<int, array{latitude: float, longitude: float, code: ?string, nucleic_code: ?string, source_type: string, method: ?string, instrument: ?string, sequenced_by: ?string, laboratory: ?string, date_sequenced: ?string}>
     */
    private function rowsToPoints(Collection $rows, Collection $samplingSites): array
    {
        $byType = $rows->groupBy(fn ($r) => (string) $r->nucleic_content_type);

        $primaryHuman = $this->loadPrimarySamples($byType->get(HumanSamples::class, collect()), HumanSamples::class);
        $primaryAnimal = $this->loadPrimarySamples($byType->get(AnimalSamples::class, collect()), AnimalSamples::class);
        $primaryEnvironment = $this->loadPrimarySamples($byType->get(EnvironmentSamples::class, collect()), EnvironmentSamples::class);

        $parasiteOrigins = $this->loadParasiteOrigins($byType->get(ParasiteSamples::class, collect()));
        $cultureOrigins = $this->loadCultureOrigins($byType->get(Cultures::class, collect()));
        $poolOrigins = $this->loadPoolOrigins($byType->get(Pools::class, collect()));

        $primary = $primaryHuman
            ->merge($primaryAnimal)
            ->merge($primaryEnvironment)
            ->merge($parasiteOrigins)
            ->merge($cultureOrigins)
            ->merge($poolOrigins);

        $points = [];

        foreach ($rows as $row) {
            $originType = (string) $row->nucleic_content_type;
            $originId = (int) $row->nucleic_content_id;

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

            $points[] = [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'code' => $row->code,
                'nucleic_code' => $row->nucleic_code !== null ? (string) $row->nucleic_code : null,
                'source_type' => class_basename($originType),
                'method' => $row->method !== null ? (string) $row->method : null,
                'instrument' => $row->instrument !== null ? (string) $row->instrument : null,
                'sequenced_by' => $row->sequenced_by !== null ? (string) $row->sequenced_by : null,
                'laboratory' => $row->laboratory !== null ? (string) $row->laboratory : null,
                'date_sequenced' => $row->date_sequenced !== null ? (string) $row->date_sequenced : null,
            ];
        }

        return $points;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadPrimarySamples(Collection $rows, string $modelClass): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $table = (new $modelClass)->getTable();

        $samples = $modelClass::query()
            ->whereIn($table.'.id', $ids)
            ->get([$table.'.id', $table.'.latitude', $table.'.longitude', $table.'.sampling_sites_id']);

        return $samples->mapWithKeys(function ($s) use ($modelClass) {
            return [
                $modelClass.'#'.$s->id => [
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'sampling_sites_id' => $s->sampling_sites_id,
                ],
            ];
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadParasiteOrigins(Collection $rows): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $parasites = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereIn('parasite_samples.id', $ids)
            ->get(['parasite_samples.id', 'parasites.parasites_origin_type', 'parasites.parasites_origin_id']);

        $byOriginType = $parasites->groupBy(fn ($r) => (string) $r->parasites_origin_type);
        $human = $this->loadPrimarySamplesFromIds($byOriginType->get(HumanSamples::class, collect())->pluck('parasites_origin_id'), HumanSamples::class);
        $animal = $this->loadPrimarySamplesFromIds($byOriginType->get(AnimalSamples::class, collect())->pluck('parasites_origin_id'), AnimalSamples::class);
        $environment = $this->loadPrimarySamplesFromIds($byOriginType->get(EnvironmentSamples::class, collect())->pluck('parasites_origin_id'), EnvironmentSamples::class);

        $origins = $human->merge($animal)->merge($environment);

        return $parasites->mapWithKeys(function ($p) use ($origins) {
            $originType = (string) $p->parasites_origin_type;
            $originId = (int) $p->parasites_origin_id;

            $origin = $origins->get($originType.'#'.$originId);

            return [
                ParasiteSamples::class.'#'.$p->id => $origin ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null],
            ];
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadCultureOrigins(Collection $rows): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return $this->loadCultureOriginsFromIds($ids);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadPoolOrigins(Collection $rows): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $poolContents = PoolContents::query()
            ->whereIn('pools_id', $ids)
            ->orderBy('id')
            ->get(['id', 'pools_id', 'samples_type', 'samples_id']);

        $byPool = $poolContents->groupBy('pools_id');

        $human = $this->loadPrimarySamplesFromIds(
            $poolContents->where('samples_type', HumanSamples::class)->pluck('samples_id'),
            HumanSamples::class
        );
        $animal = $this->loadPrimarySamplesFromIds(
            $poolContents->where('samples_type', AnimalSamples::class)->pluck('samples_id'),
            AnimalSamples::class
        );
        $environment = $this->loadPrimarySamplesFromIds(
            $poolContents->where('samples_type', EnvironmentSamples::class)->pluck('samples_id'),
            EnvironmentSamples::class
        );
        $parasite = $this->loadParasiteOriginsFromIds(
            $poolContents->where('samples_type', ParasiteSamples::class)->pluck('samples_id')
        );
        $culture = $this->loadCultureOriginsFromIds(
            $poolContents->where('samples_type', Cultures::class)->pluck('samples_id')
        );

        $points = collect();

        foreach ($byPool as $poolId => $contents) {
            $resolved = null;

            foreach ($contents as $pc) {
                $type = (string) $pc->samples_type;
                $id = (int) $pc->samples_id;

                $resolved = match ($type) {
                    HumanSamples::class => $human->get(HumanSamples::class.'#'.$id),
                    AnimalSamples::class => $animal->get(AnimalSamples::class.'#'.$id),
                    EnvironmentSamples::class => $environment->get(EnvironmentSamples::class.'#'.$id),
                    ParasiteSamples::class => $parasite->get(ParasiteSamples::class.'#'.$id),
                    Cultures::class => $culture->get(Cultures::class.'#'.$id),
                    default => null,
                };

                if ($resolved) {
                    break;
                }
            }

            $points->put(
                Pools::class.'#'.(int) $poolId,
                $resolved ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null]
            );
        }

        return $points;
    }

    private function loadPrimarySamplesFromIds($ids, string $modelClass): Collection
    {
        $ids = collect($ids)->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $table = (new $modelClass)->getTable();

        $samples = $modelClass::query()
            ->whereIn($table.'.id', $ids)
            ->get([$table.'.id', $table.'.latitude', $table.'.longitude', $table.'.sampling_sites_id']);

        return $samples->mapWithKeys(function ($s) use ($modelClass) {
            return [
                $modelClass.'#'.$s->id => [
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'sampling_sites_id' => $s->sampling_sites_id,
                ],
            ];
        });
    }

    private function loadParasiteOriginsFromIds($ids): Collection
    {
        $ids = collect($ids)->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $parasites = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereIn('parasite_samples.id', $ids)
            ->get(['parasite_samples.id', 'parasites.parasites_origin_type', 'parasites.parasites_origin_id']);

        $byOriginType = $parasites->groupBy(fn ($r) => (string) $r->parasites_origin_type);
        $human = $this->loadPrimarySamplesFromIds($byOriginType->get(HumanSamples::class, collect())->pluck('parasites_origin_id'), HumanSamples::class);
        $animal = $this->loadPrimarySamplesFromIds($byOriginType->get(AnimalSamples::class, collect())->pluck('parasites_origin_id'), AnimalSamples::class);
        $environment = $this->loadPrimarySamplesFromIds($byOriginType->get(EnvironmentSamples::class, collect())->pluck('parasites_origin_id'), EnvironmentSamples::class);

        $origins = $human->merge($animal)->merge($environment);

        return $parasites->mapWithKeys(function ($p) use ($origins) {
            $originType = (string) $p->parasites_origin_type;
            $originId = (int) $p->parasites_origin_id;
            $origin = $origins->get($originType.'#'.$originId);

            return [
                ParasiteSamples::class.'#'.$p->id => $origin ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null],
            ];
        });
    }

    private function loadCultureOriginsFromIds($ids): Collection
    {
        $ids = collect($ids)->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $cultures = Cultures::query()
            ->whereIn('id', $ids)
            ->get(['id', 'cultures_content_type', 'cultures_content_id']);

        $byType = $cultures->groupBy(fn ($c) => (string) $c->cultures_content_type);

        $human = $this->loadPrimarySamplesFromIds($byType->get(HumanSamples::class, collect())->pluck('cultures_content_id'), HumanSamples::class);
        $animal = $this->loadPrimarySamplesFromIds($byType->get(AnimalSamples::class, collect())->pluck('cultures_content_id'), AnimalSamples::class);
        $environment = $this->loadPrimarySamplesFromIds($byType->get(EnvironmentSamples::class, collect())->pluck('cultures_content_id'), EnvironmentSamples::class);

        $parasite = $this->loadParasiteOriginsFromIds($byType->get(ParasiteSamples::class, collect())->pluck('cultures_content_id'));

        $origins = $human->merge($animal)->merge($environment)->merge($parasite);

        return $cultures->mapWithKeys(function ($c) use ($origins) {
            $originType = (string) $c->cultures_content_type;
            $originId = (int) $c->cultures_content_id;
            $origin = $origins->get($originType.'#'.$originId);

            return [
                Cultures::class.'#'.$c->id => $origin ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null],
            ];
        });
    }
}
