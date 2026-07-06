<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Microplastics;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\SamplingSites;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MicroplasticsDashboardMapPointsController extends Controller
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $recordCache = [];

    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $cursor = (int) $request->query('cursor', 0);
        $limit = max(100, min((int) $request->query('limit', 1000), 2000));

        $rows = $this->filteredQuery($request, $isGuestMode, $projectId)
            ->select([
                'microplastics.id',
                'microplastics.code',
                'microplastics.microplastics_content_type',
                'microplastics.microplastics_content_id',
                'mps_types.name as mps_type',
                'protocols.name as protocol',
                'laboratories.name as laboratory',
                DB::raw($this->peopleNameSql().' as identified_by'),
            ])
            ->where('microplastics.id', '>', $cursor)
            ->orderBy('microplastics.id')
            ->limit($limit)
            ->get();

        $samplingSites = SamplingSites::query()
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->keyBy('id');

        $points = [];

        foreach ($rows as $row) {
            $resolvedPoints = $this->resolveLocations(
                (string) $row->microplastics_content_type,
                (int) $row->microplastics_content_id,
                $samplingSites->all()
            );

            foreach ($resolvedPoints as $point) {
                $points[] = [
                    'code' => $row->code,
                    'source_type' => class_basename((string) $row->microplastics_content_type),
                    'mps_type' => $row->mps_type,
                    'protocol' => $row->protocol,
                    'laboratory' => $row->laboratory,
                    'identified_by' => $row->identified_by,
                    'latitude' => $point['latitude'],
                    'longitude' => $point['longitude'],
                    'sampling_site_id' => $point['sampling_site_id'],
                    'sampling_site_name' => $point['sampling_site_name'],
                    'source_code' => $point['source_code'],
                ];
            }
        }

        $nextCursor = $rows->count() === $limit ? (int) $rows->last()->id : null;

        return response()->json([
            'points' => array_values($points),
            'next_cursor' => $nextCursor,
        ]);
    }

    private function filteredQuery(Request $request, bool $isGuestMode, ?int $projectId): Builder
    {
        $query = Microplastics::query()
            ->leftJoin('mps_types', 'microplastics.mps_types_id', '=', 'mps_types.id')
            ->leftJoin('protocols', 'microplastics.protocols_id', '=', 'protocols.id')
            ->leftJoin('laboratories', 'microplastics.laboratories_id', '=', 'laboratories.id')
            ->leftJoin('people', 'microplastics.people_id', '=', 'people.id');

        $query->whereIn('microplastics.microplastics_content_type', [
            HumanSamples::class,
            AnimalSamples::class,
            EnvironmentSamples::class,
            ParasiteSamples::class,
            Pools::class,
        ]);

        if ($isGuestMode) {
            $query->where('microplastics.is_private', false);
        } else {
            $query->where('microplastics.projects_id', $projectId);
        }

        $mpsTypeFilter = (string) $request->query('mpsTypeFilter', '');
        if ($mpsTypeFilter !== '') {
            $query->where('mps_types.name', $mpsTypeFilter);
        }

        $sourceTypeFilter = (string) $request->query('sourceTypeFilter', 'all');
        if ($sourceTypeFilter !== '' && $sourceTypeFilter !== 'all') {
            $sourceType = match ($sourceTypeFilter) {
                'humansamples', 'human' => HumanSamples::class,
                'animalsamples', 'animal' => AnimalSamples::class,
                'environmentsamples', 'environment' => EnvironmentSamples::class,
                'parasitesamples', 'parasite' => ParasiteSamples::class,
                'pools', 'pool' => Pools::class,
                default => null,
            };

            if ($sourceType) {
                $query->whereIn('microplastics.microplastics_content_type', $this->typeVariants($sourceType));
            }
        }

        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter): void {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'microplastics.id')
                    ->whereIn('sub_project_assignments.assignable_type', $this->typeVariants(Microplastics::class))
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $protocolFilter = (string) $request->query('protocolFilter', '');
        if ($protocolFilter !== '') {
            $query->where('protocols.name', $protocolFilter);
        }

        $laboratoryFilter = (string) $request->query('laboratoryFilter', '');
        if ($laboratoryFilter !== '') {
            $query->where('laboratories.name', $laboratoryFilter);
        }

        $identifiedByFilter = (string) $request->query('identifiedByFilter', '');
        if ($identifiedByFilter !== '') {
            $needle = '%'.$identifiedByFilter.'%';
            $query->where(function (Builder $nestedQuery) use ($needle): void {
                $nestedQuery
                    ->where('people.title', 'like', $needle)
                    ->orWhere('people.first_name', 'like', $needle)
                    ->orWhere('people.last_name', 'like', $needle);
            });
        }

        return $query;
    }

    /**
     * @param  array<int, object>  $samplingSites
     * @param  array<string, bool>  $visited
     * @return array<int, array{latitude: float, longitude: float, sampling_site_id: ?int, sampling_site_name: ?string, source_code: ?string}>
     */
    private function resolveLocations(string $type, int $id, array $samplingSites, int $depth = 0, array $visited = []): array
    {
        if ($depth > 8 || $id <= 0) {
            return [];
        }

        $normalizedType = $this->normalizedType($type);
        $visitKey = $normalizedType.'#'.$id;
        if (isset($visited[$visitKey])) {
            return [];
        }
        $visited[$visitKey] = true;

        return match ($normalizedType) {
            HumanSamples::class => $this->samplePoint('human_samples', $id, $samplingSites),
            AnimalSamples::class => $this->samplePoint('animal_samples', $id, $samplingSites),
            EnvironmentSamples::class => $this->samplePoint('environment_samples', $id, $samplingSites),
            ParasiteSamples::class => $this->resolveParasiteSample($id, $samplingSites, $depth, $visited),
            Pools::class => $this->resolvePool($id, $samplingSites, $depth, $visited),
            default => [],
        };
    }

    /**
     * @param  array<int, object>  $samplingSites
     * @return array<int, array{latitude: float, longitude: float, sampling_site_id: ?int, sampling_site_name: ?string, source_code: ?string}>
     */
    private function samplePoint(string $table, int $id, array $samplingSites): array
    {
        $record = $this->cachedRecord($table, $id, ['code', 'latitude', 'longitude', 'sampling_sites_id']);
        if (! $record) {
            return [];
        }

        $latitude = isset($record['latitude']) ? (float) $record['latitude'] : null;
        $longitude = isset($record['longitude']) ? (float) $record['longitude'] : null;
        $samplingSiteId = isset($record['sampling_sites_id']) ? (int) $record['sampling_sites_id'] : null;

        if ((! $latitude || ! $longitude) && $samplingSiteId && isset($samplingSites[$samplingSiteId])) {
            $site = $samplingSites[$samplingSiteId];
            $latitude = $site->latitude !== null ? (float) $site->latitude : null;
            $longitude = $site->longitude !== null ? (float) $site->longitude : null;
        }

        if (! is_finite((float) $latitude) || ! is_finite((float) $longitude) || $latitude === null || $longitude === null) {
            return [];
        }

        return [[
            'latitude' => $latitude,
            'longitude' => $longitude,
            'sampling_site_id' => $samplingSiteId,
            'sampling_site_name' => $samplingSiteId && isset($samplingSites[$samplingSiteId]) ? (string) $samplingSites[$samplingSiteId]->name : null,
            'source_code' => $record['code'] ?? null,
        ]];
    }

    /**
     * @param  array<int, object>  $samplingSites
     * @param  array<string, bool>  $visited
     * @return array<int, array{latitude: float, longitude: float, sampling_site_id: ?int, sampling_site_name: ?string, source_code: ?string}>
     */
    private function resolveParasiteSample(int $id, array $samplingSites, int $depth, array $visited): array
    {
        $parasiteSample = $this->cachedRecord('parasite_samples', $id, ['parasites_id']);
        if (! $parasiteSample || ! isset($parasiteSample['parasites_id'])) {
            return [];
        }

        $parasite = $this->cachedRecord('parasites', (int) $parasiteSample['parasites_id'], ['parasites_origin_type', 'parasites_origin_id']);
        if (! $parasite || ! isset($parasite['parasites_origin_type'], $parasite['parasites_origin_id'])) {
            return [];
        }

        return $this->resolveLocations((string) $parasite['parasites_origin_type'], (int) $parasite['parasites_origin_id'], $samplingSites, $depth + 1, $visited);
    }

    /**
     * @param  array<int, object>  $samplingSites
     * @param  array<string, bool>  $visited
     * @return array<int, array{latitude: float, longitude: float, sampling_site_id: ?int, sampling_site_name: ?string, source_code: ?string}>
     */
    private function resolveMorphParent(string $table, string $typeColumn, string $idColumn, int $id, array $samplingSites, int $depth, array $visited): array
    {
        $record = $this->cachedRecord($table, $id, [$typeColumn, $idColumn]);
        if (! $record || ! isset($record[$typeColumn], $record[$idColumn])) {
            return [];
        }

        return $this->resolveLocations((string) $record[$typeColumn], (int) $record[$idColumn], $samplingSites, $depth + 1, $visited);
    }

    /**
     * @param  array<int, object>  $samplingSites
     * @param  array<string, bool>  $visited
     * @return array<int, array{latitude: float, longitude: float, sampling_site_id: ?int, sampling_site_name: ?string, source_code: ?string}>
     */
    private function resolvePool(int $id, array $samplingSites, int $depth, array $visited): array
    {
        $contents = DB::table('pool_contents')
            ->where('pools_id', $id)
            ->get(['samples_type', 'samples_id']);

        $resolved = [];

        foreach ($contents as $content) {
            $resolved = array_merge(
                $resolved,
                $this->resolveLocations((string) $content->samples_type, (int) $content->samples_id, $samplingSites, $depth + 1, $visited)
            );
        }

        return collect($resolved)
            ->unique(fn (array $point) => implode('|', [
                $point['source_code'] ?? '',
                number_format((float) $point['latitude'], 6, '.', ''),
                number_format((float) $point['longitude'], 6, '.', ''),
                (string) ($point['sampling_site_id'] ?? ''),
            ]))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, mixed>|null
     */
    private function cachedRecord(string $table, int $id, array $columns): ?array
    {
        $cacheKey = $table.'#'.$id.'#'.implode(',', $columns);

        if (! array_key_exists($cacheKey, $this->recordCache)) {
            $record = DB::table($table)->where('id', $id)->first($columns);
            $this->recordCache[$cacheKey] = $record ? (array) $record : [];
        }

        return $this->recordCache[$cacheKey] !== [] ? $this->recordCache[$cacheKey] : null;
    }

    private function normalizedType(string $type): string
    {
        $normalized = ltrim(trim($type), '\\');
        $base = class_basename($normalized);

        if (str_starts_with($base, 'AppModels')) {
            $base = substr($base, strlen('AppModels'));
        }

        return 'App\\Models\\'.$base;
    }

    /**
     * @return array<int, string>
     */
    private function typeVariants(string $type): array
    {
        $base = class_basename($type);

        return array_values(array_unique([
            $type,
            'App\\Models\\'.$base,
            'AppModels'.$base,
            $base,
        ]));
    }

    private function peopleNameSql(): string
    {
        return "TRIM(COALESCE(people.title, '') || ' ' || COALESCE(people.first_name, '') || ' ' || COALESCE(people.last_name, ''))";
    }
}
