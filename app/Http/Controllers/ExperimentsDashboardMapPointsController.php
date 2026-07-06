<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\Parasites;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\SamplingSites;
use App\Models\Tubes;
use App\Services\PrimarySampleReachability;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExperimentsDashboardMapPointsController extends Controller
{
    /**
     * @var array<int, array{tubes_content_type: string, tubes_content_id: int}>
     */
    private array $tubesCache = [];

    /**
     * @var array<int, array{parasites_id: int}>
     */
    private array $parasiteSamplesCache = [];

    /**
     * @var array<int, array{parasites_origin_type: string, parasites_origin_id: int}>
     */
    private array $parasitesCache = [];

    /**
     * @var array<int, array{code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int}>
     */
    private array $humanSamplesCache = [];

    /**
     * @var array<int, array{code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int}>
     */
    private array $animalSamplesCache = [];

    /**
     * @var array<int, array{code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int}>
     */
    private array $environmentSamplesCache = [];

    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $samplingSites = SamplingSites::query()
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->keyBy('id');

        if ($request->boolean('chunked')) {
            return $this->chunkedMapPointsResponse($request, $isGuestMode, $projectId, $samplingSites);
        }

        $groupedPoints = [];
        $samplingSitesSummary = [];

        $this->filteredExperimentsQuery($request, $isGuestMode, $projectId)
            ->select($this->mapExperimentColumns())
            ->orderBy('id')
            ->chunkById(1000, function (Collection $experiments) use ($samplingSites, &$groupedPoints, &$samplingSitesSummary): void {
                $this->aggregateExperimentsIntoMap(
                    $experiments,
                    $samplingSites,
                    $groupedPoints,
                    $samplingSitesSummary
                );
            }, 'id');

        return response()->json([
            'grouped_points' => array_values($groupedPoints),
            'sampling_sites_summary' => array_values($samplingSitesSummary),
        ]);
    }

    /**
     * @param  Collection<int, SamplingSites>  $samplingSites
     */
    private function chunkedMapPointsResponse(
        Request $request,
        bool $isGuestMode,
        ?int $projectId,
        Collection $samplingSites
    ): JsonResponse {
        $afterId = max(0, (int) $request->query('after_id', 0));
        $chunkSize = min(1000, max(100, (int) $request->query('chunk_size', 500)));
        $includeTotal = $request->boolean('include_total', $afterId === 0);

        $baseQuery = $this->filteredExperimentsQuery($request, $isGuestMode, $projectId);
        $total = $includeTotal ? (clone $baseQuery)->count('experiments.id') : null;

        $query = (clone $baseQuery)
            ->select($this->mapExperimentColumns())
            ->orderBy('experiments.id');

        if ($afterId > 0) {
            $query->where('experiments.id', '>', $afterId);
        }

        $experiments = $query->limit($chunkSize)->get();

        $groupedPoints = [];
        $samplingSitesSummary = [];

        if ($experiments->isNotEmpty()) {
            $this->aggregateExperimentsIntoMap(
                $experiments,
                $samplingSites,
                $groupedPoints,
                $samplingSitesSummary
            );
        }

        $chunkCount = $experiments->count();
        $lastId = $experiments->last()?->id ?? $afterId;
        $complete = $chunkCount < $chunkSize;

        return response()->json([
            'grouped_points' => array_values($groupedPoints),
            'sampling_sites_summary' => array_values($samplingSitesSummary),
            'meta' => [
                'total' => $total,
                'chunk_count' => $chunkCount,
                'last_id' => $lastId,
                'complete' => $complete,
                'next_after_id' => $complete ? null : $lastId,
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function mapExperimentColumns(): array
    {
        return [
            'id',
            'code',
            'experiments_content_type',
            'experiments_content_id',
            'outcome_discrete',
            'date_tested',
            'protocols_id',
            'pathogens_id',
            'laboratories_id',
        ];
    }

    /**
     * @param  Collection<int, Experiments>  $experiments
     * @param  Collection<int, SamplingSites>  $samplingSites
     * @param  array<string, array<string, mixed>>  $groupedPoints
     * @param  array<string, array{id:string, name:string, count:int}>  $samplingSitesSummary
     */
    private function aggregateExperimentsIntoMap(
        Collection $experiments,
        Collection $samplingSites,
        array &$groupedPoints,
        array &$samplingSitesSummary
    ): void {
        $protocolMeta = DB::table('protocols')
            ->leftJoin('techniques', 'protocols.techniques_id', '=', 'techniques.id')
            ->whereIn('protocols.id', $experiments->pluck('protocols_id')->filter()->unique()->values())
            ->select('protocols.id', 'protocols.name', 'techniques.type as technique_type')
            ->get()
            ->keyBy('id');

        $pathogenMeta = DB::table('pathogens')
            ->whereIn('id', $experiments->pluck('pathogens_id')->filter()->unique()->values())
            ->pluck('species', 'id');

        $laboratoryMeta = DB::table('laboratories')
            ->whereIn('id', $experiments->pluck('laboratories_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $points = $this->experimentsToPoints($experiments, $samplingSites, $protocolMeta, $pathogenMeta, $laboratoryMeta);

        foreach ($points as $point) {
            $this->aggregatePoint($groupedPoints, $samplingSitesSummary, $point);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $groupedPoints
     * @param  array<string, array{id:string, name:string, count:int}>  $samplingSitesSummary
     * @param  array{latitude: float, longitude: float, outcome_discrete: ?string, type: string, code: ?string, sampling_site_id: ?int, sampling_site_name: ?string, protocol:?string, pathogen:?string, laboratory:?string, technique_type:?string, experiment_code:?string, sample_title:string, sample_details:array<int,string>}  $point
     */
    private function aggregatePoint(array &$groupedPoints, array &$samplingSitesSummary, array $point): void
    {
        $latitude = (float) $point['latitude'];
        $longitude = (float) $point['longitude'];
        $samplingSiteId = $point['sampling_site_id'] ? (string) $point['sampling_site_id'] : null;

        if ($samplingSiteId) {
            $summary = $samplingSitesSummary[$samplingSiteId] ?? [
                'id' => $samplingSiteId,
                'name' => (string) ($point['sampling_site_name'] ?: "Site #{$samplingSiteId}"),
                'count' => 0,
            ];
            $summary['count']++;
            $samplingSitesSummary[$samplingSiteId] = $summary;
        }

        $groupKey = 'coord:'.number_format($latitude, 6, '.', '').'|'.number_format($longitude, 6, '.', '').'|site:'.($samplingSiteId ?? 'none');

        $row = $groupedPoints[$groupKey] ?? [
            'latitude' => round($latitude, 6),
            'longitude' => round($longitude, 6),
            'sampling_site_id' => $point['sampling_site_id'],
            'sampling_site_name' => $point['sampling_site_name'],
            'weight' => 0,
            'aggregateDistributions' => $this->emptyAggregateDistributions(),
            'entries' => [],
        ];

        $row['weight']++;
        $this->incrementDistribution($row['aggregateDistributions']['outcome'], $point['outcome_discrete']);
        $this->incrementDistribution($row['aggregateDistributions']['type'], $point['type']);
        $this->incrementDistribution($row['aggregateDistributions']['protocol'], $point['protocol']);
        $this->incrementDistribution($row['aggregateDistributions']['pathogen'], $point['pathogen']);
        $this->incrementDistribution($row['aggregateDistributions']['technique_type'], $point['technique_type']);
        $this->incrementDistribution($row['aggregateDistributions']['laboratory'], $point['laboratory']);
        foreach (($point['sample_variables'] ?? []) as $variable => $value) {
            if (isset($row['aggregateDistributions'][$variable])) {
                $this->incrementDistribution($row['aggregateDistributions'][$variable], $value);
            }
        }
        $row['entries'][] = [
            'experiment_code' => $point['experiment_code'],
            'sample_code' => $point['code'],
            'sample_type' => $point['type'],
            'sample_title' => $point['sample_title'],
            'sample_details' => $point['sample_details'],
            'sample_detail_groups' => $point['sample_detail_groups'] ?? [],
            'outcome_discrete' => $point['outcome_discrete'],
            'protocol' => $point['protocol'],
            'pathogen' => $point['pathogen'],
            'technique_type' => $point['technique_type'],
            'laboratory' => $point['laboratory'],
            'date_tested' => $point['date_tested'],
            'sampling_site_name' => $point['sampling_site_name'],
        ];

        $groupedPoints[$groupKey] = $row;
    }

    /**
     * @param  array<string, int>  $distribution
     */
    private function incrementDistribution(array &$distribution, ?string $value): void
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return;
        }

        $distribution[$normalized] = ($distribution[$normalized] ?? 0) + 1;
    }

    private function filteredExperimentsQuery(Request $request, bool $isGuestMode, ?int $projectId): Builder
    {
        $query = Experiments::query();

        if ($isGuestMode) {
            $query->where('is_private', false);
        } else {
            $query->where('projects_id', $projectId);
        }

        $experimentType = (string) $request->query('experimentTypeFilter', 'all');
        if ($experimentType !== '' && $experimentType !== 'all') {
            $query->whereIn('experiments_content_type', $this->typeVariants($this->normalizeType($experimentType)));
        }

        $animalSpeciesFilter = (string) $request->query('animalSpeciesFilter', '');
        $animalSexFilter = (string) $request->query('animalSexFilter', '');
        $parasiteSpeciesFilter = (string) $request->query('parasiteSpeciesFilter', '');
        $parasiteStageFilter = (string) $request->query('parasiteStageFilter', '');
        $parasiteSexFilter = (string) $request->query('parasiteSexFilter', '');
        $parasiteStateFilter = (string) $request->query('parasiteStateFilter', '');
        $parasiteSampleTypeFilter = (string) $request->query('parasiteSampleTypeFilter', '');
        $cultureTypeFilter = (string) $request->query('cultureTypeFilter', '');
        $cultureMediumFilter = (string) $request->query('cultureMediumFilter', '');
        $nucleicTypeFilter = (string) $request->query('nucleicTypeFilter', '');
        $poolContentTypeFilter = (string) $request->query('poolContentTypeFilter', 'all');
        $poolMinNrPooledFilter = $request->query('poolMinNrPooledFilter') !== null
            ? (int) $request->query('poolMinNrPooledFilter')
            : null;
        $poolMaxNrPooledFilter = $request->query('poolMaxNrPooledFilter') !== null
            ? (int) $request->query('poolMaxNrPooledFilter')
            : null;

        $tracePrimaryTypeFilter = (string) $request->query('tracePrimaryTypeFilter', 'all');
        $tracePrimaryAnimalSpeciesFilter = (string) $request->query('tracePrimaryAnimalSpeciesFilter', '');
        $tracePrimaryAnimalSexFilter = (string) $request->query('tracePrimaryAnimalSexFilter', '');
        $tracePrimaryAnimalAgeFilter = (string) $request->query('tracePrimaryAnimalAgeFilter', '');
        $tracePrimaryHumanEthnicityFilter = (string) $request->query('tracePrimaryHumanEthnicityFilter', '');
        $tracePrimaryHumanOccupationFilter = (string) $request->query('tracePrimaryHumanOccupationFilter', '');
        $tracePrimaryHumanCountryFilter = (string) $request->query('tracePrimaryHumanCountryFilter', '');
        $tracePrimaryParasiteSpeciesFilter = (string) $request->query('tracePrimaryParasiteSpeciesFilter', '');
        $tracePrimaryParasiteStageFilter = (string) $request->query('tracePrimaryParasiteStageFilter', '');
        $tracePrimaryParasiteSexFilter = (string) $request->query('tracePrimaryParasiteSexFilter', '');
        $tracePrimaryParasiteStateFilter = (string) $request->query('tracePrimaryParasiteStateFilter', '');
        $tracePrimaryParasiteSampleTypeFilter = (string) $request->query('tracePrimaryParasiteSampleTypeFilter', '');
        $tracePrimaryCultureTypeFilter = (string) $request->query('tracePrimaryCultureTypeFilter', '');
        $tracePrimaryCultureMediumFilter = (string) $request->query('tracePrimaryCultureMediumFilter', '');
        $tracePrimaryPoolMinNrPooled = $request->query('tracePrimaryPoolMinNrPooled') !== null
            ? (int) $request->query('tracePrimaryPoolMinNrPooled')
            : null;
        $tracePrimaryPoolMaxNrPooled = $request->query('tracePrimaryPoolMaxNrPooled') !== null
            ? (int) $request->query('tracePrimaryPoolMaxNrPooled')
            : null;

        $traceDeepPrimaryTypeFilter = (string) $request->query('traceDeepPrimaryTypeFilter', 'all');
        $traceDeepAnimalSpeciesFilter = (string) $request->query('traceDeepAnimalSpeciesFilter', '');
        $traceDeepAnimalSexFilter = (string) $request->query('traceDeepAnimalSexFilter', '');
        $traceDeepAnimalAgeFilter = (string) $request->query('traceDeepAnimalAgeFilter', '');
        $traceDeepHumanEthnicityFilter = (string) $request->query('traceDeepHumanEthnicityFilter', '');
        $traceDeepHumanOccupationFilter = (string) $request->query('traceDeepHumanOccupationFilter', '');
        $traceDeepHumanCountryFilter = (string) $request->query('traceDeepHumanCountryFilter', '');
        $samplingSiteFilter = (string) $request->query('samplingSiteFilter', '');

        $normalizedExperimentType = $this->normalizeType($experimentType);
        $hasTraceFilters = $tracePrimaryTypeFilter !== 'all'
            || $tracePrimaryAnimalSpeciesFilter !== ''
            || $tracePrimaryAnimalSexFilter !== ''
            || $tracePrimaryAnimalAgeFilter !== ''
            || $tracePrimaryHumanEthnicityFilter !== ''
            || $tracePrimaryHumanOccupationFilter !== ''
            || $tracePrimaryHumanCountryFilter !== ''
            || $tracePrimaryParasiteSpeciesFilter !== ''
            || $tracePrimaryParasiteStageFilter !== ''
            || $tracePrimaryParasiteSexFilter !== ''
            || $tracePrimaryParasiteStateFilter !== ''
            || $tracePrimaryParasiteSampleTypeFilter !== ''
            || $tracePrimaryCultureTypeFilter !== ''
            || $tracePrimaryCultureMediumFilter !== ''
            || $tracePrimaryPoolMinNrPooled !== null
            || $tracePrimaryPoolMaxNrPooled !== null
            || $traceDeepPrimaryTypeFilter !== 'all'
            || $traceDeepAnimalSpeciesFilter !== ''
            || $traceDeepAnimalSexFilter !== ''
            || $traceDeepAnimalAgeFilter !== ''
            || $traceDeepHumanEthnicityFilter !== ''
            || $traceDeepHumanOccupationFilter !== ''
            || $traceDeepHumanCountryFilter !== '';

        if (in_array(class_basename($normalizedExperimentType), ['ParasiteSamples', 'NucleicAcids', 'Cultures', 'Pools'], true) && $hasTraceFilters) {
            $target = class_basename($normalizedExperimentType);

            if ($target === 'NucleicAcids') {
                $upstreamType = match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'culture' => Cultures::class,
                    'pool' => Pools::class,
                    default => null,
                };

                if ($upstreamType) {
                    $query->whereExists(function ($sub) use (
                        $upstreamType,
                        $tracePrimaryAnimalSpeciesFilter,
                        $tracePrimaryAnimalSexFilter,
                        $tracePrimaryAnimalAgeFilter,
                        $tracePrimaryHumanEthnicityFilter,
                        $tracePrimaryHumanOccupationFilter,
                        $tracePrimaryHumanCountryFilter,
                        $tracePrimaryParasiteSpeciesFilter,
                        $tracePrimaryParasiteStageFilter,
                        $tracePrimaryParasiteSexFilter,
                        $tracePrimaryParasiteStateFilter,
                        $tracePrimaryParasiteSampleTypeFilter,
                        $tracePrimaryCultureTypeFilter,
                        $tracePrimaryCultureMediumFilter,
                        $tracePrimaryPoolMinNrPooled,
                        $tracePrimaryPoolMaxNrPooled,
                        $traceDeepPrimaryTypeFilter,
                        $traceDeepAnimalSpeciesFilter,
                        $traceDeepAnimalSexFilter,
                        $traceDeepAnimalAgeFilter,
                        $traceDeepHumanEthnicityFilter,
                        $traceDeepHumanOccupationFilter,
                        $traceDeepHumanCountryFilter
                    ) {
                        $sub->select(DB::raw(1))
                            ->from('nucleic_acids')
                            ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                            ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants($upstreamType));

                        if ($upstreamType === AnimalSamples::class) {
                            $sub->join('animal_samples', 'nucleic_acids.nucleic_content_id', '=', 'animal_samples.id')
                                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

                            if ($tracePrimaryAnimalSpeciesFilter !== '') {
                                $sub->where('animal_species.name_common', $tracePrimaryAnimalSpeciesFilter);
                            }
                            if ($tracePrimaryAnimalSexFilter !== '') {
                                $sub->where('animals.sex', $tracePrimaryAnimalSexFilter);
                            }
                            if ($tracePrimaryAnimalAgeFilter !== '') {
                                $sub->where('animals.age', $tracePrimaryAnimalAgeFilter);
                            }
                        }

                        if ($upstreamType === HumanSamples::class) {
                            $sub->join('human_samples', 'nucleic_acids.nucleic_content_id', '=', 'human_samples.id')
                                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

                            if ($tracePrimaryHumanEthnicityFilter !== '') {
                                $sub->where('humans.ethnicity', $tracePrimaryHumanEthnicityFilter);
                            }
                            if ($tracePrimaryHumanOccupationFilter !== '') {
                                $sub->where('humans.occupation', $tracePrimaryHumanOccupationFilter);
                            }
                            if ($tracePrimaryHumanCountryFilter !== '') {
                                $sub->where('countries.name', $tracePrimaryHumanCountryFilter);
                            }
                        }

                        if ($upstreamType === ParasiteSamples::class) {
                            $sub->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                                ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id');

                            if ($tracePrimaryParasiteSpeciesFilter !== '') {
                                $sub->where('parasite_species.name_scientific', $tracePrimaryParasiteSpeciesFilter);
                            }
                            if ($tracePrimaryParasiteStageFilter !== '') {
                                $sub->where('parasites.stage', $tracePrimaryParasiteStageFilter);
                            }
                            if ($tracePrimaryParasiteSexFilter !== '') {
                                $sub->where('parasites.sex', $tracePrimaryParasiteSexFilter);
                            }
                            if ($tracePrimaryParasiteStateFilter !== '') {
                                $sub->where('parasites.state', $tracePrimaryParasiteStateFilter);
                            }
                            if ($tracePrimaryParasiteSampleTypeFilter !== '') {
                                $sub->where('parasite_sample_types.name', $tracePrimaryParasiteSampleTypeFilter);
                            }

                            // Deep trace: parasite -> primary (human/animal/environment)
                            if (
                                $traceDeepPrimaryTypeFilter !== 'all'
                                || $traceDeepAnimalSpeciesFilter !== ''
                                || $traceDeepAnimalSexFilter !== ''
                                || $traceDeepAnimalAgeFilter !== ''
                                || $traceDeepHumanEthnicityFilter !== ''
                                || $traceDeepHumanOccupationFilter !== ''
                                || $traceDeepHumanCountryFilter !== ''
                            ) {
                                $primaryType = match ($traceDeepPrimaryTypeFilter) {
                                    'human' => HumanSamples::class,
                                    'animal' => AnimalSamples::class,
                                    'environment' => EnvironmentSamples::class,
                                    default => null,
                                };

                                if ($primaryType) {
                                    $sub->whereIn('parasites.parasites_origin_type', $this->typeVariants($primaryType));

                                    if ($primaryType === AnimalSamples::class) {
                                        $sub->join('animal_samples as deep_animal_samples', 'parasites.parasites_origin_id', '=', 'deep_animal_samples.id')
                                            ->join('animals as deep_animals', 'deep_animal_samples.animals_id', '=', 'deep_animals.id')
                                            ->leftJoin('animal_species as deep_animal_species', 'deep_animals.animal_species_id', '=', 'deep_animal_species.id');

                                        if ($traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $traceDeepAnimalSexFilter);
                                        }
                                        if ($traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                                        }
                                        if ($traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $traceDeepHumanOccupationFilter);
                                        }
                                        if ($traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $traceDeepHumanCountryFilter);
                                        }
                                    }
                                }
                            }
                        }

                        if ($upstreamType === Cultures::class) {
                            $sub->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id');

                            if ($tracePrimaryCultureTypeFilter !== '') {
                                $sub->where('cultures.type', $tracePrimaryCultureTypeFilter);
                            }
                            if ($tracePrimaryCultureMediumFilter !== '') {
                                $sub->where('cultures.medium', $tracePrimaryCultureMediumFilter);
                            }
                        }

                        if ($upstreamType === Pools::class) {
                            $sub->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id');

                            if ($tracePrimaryPoolMinNrPooled !== null) {
                                $sub->where('pools.nr_pooled', '>=', $tracePrimaryPoolMinNrPooled);
                            }
                            if ($tracePrimaryPoolMaxNrPooled !== null) {
                                $sub->where('pools.nr_pooled', '<=', $tracePrimaryPoolMaxNrPooled);
                            }
                        }
                    });
                }

                // Deep trace for derived upstreams (culture/pool) - add as extra EXISTS constraints
                if (
                    in_array($tracePrimaryTypeFilter, ['culture', 'pool'], true)
                    && ($traceDeepPrimaryTypeFilter !== 'all'
                        || $traceDeepAnimalSpeciesFilter !== ''
                        || $traceDeepAnimalSexFilter !== ''
                        || $traceDeepAnimalAgeFilter !== ''
                        || $traceDeepHumanEthnicityFilter !== ''
                        || $traceDeepHumanOccupationFilter !== ''
                        || $traceDeepHumanCountryFilter !== '')
                ) {
                    $primaryType = match ($traceDeepPrimaryTypeFilter) {
                        'human' => HumanSamples::class,
                        'animal' => AnimalSamples::class,
                        'environment' => EnvironmentSamples::class,
                        default => null,
                    };

                    if ($primaryType) {
                        if ($tracePrimaryTypeFilter === 'culture') {
                            $query->where(function ($w) use (
                                $primaryType,
                                $traceDeepAnimalSpeciesFilter,
                                $traceDeepAnimalSexFilter,
                                $traceDeepAnimalAgeFilter,
                                $traceDeepHumanEthnicityFilter,
                                $traceDeepHumanOccupationFilter,
                                $traceDeepHumanCountryFilter
                            ) {
                                $w->whereExists(function ($sub) use (
                                    $primaryType,
                                    $traceDeepAnimalSpeciesFilter,
                                    $traceDeepAnimalSexFilter,
                                    $traceDeepAnimalAgeFilter,
                                    $traceDeepHumanEthnicityFilter,
                                    $traceDeepHumanOccupationFilter,
                                    $traceDeepHumanCountryFilter
                                ) {
                                    $sub->select(DB::raw(1))
                                        ->from('nucleic_acids')
                                        ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                                        ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                                        ->whereIn('cultures.cultures_content_type', $this->typeVariants($primaryType));

                                    if ($primaryType === AnimalSamples::class) {
                                        $sub->join('animal_samples as deep_animal_samples', 'cultures.cultures_content_id', '=', 'deep_animal_samples.id')
                                            ->join('animals as deep_animals', 'deep_animal_samples.animals_id', '=', 'deep_animals.id')
                                            ->leftJoin('animal_species as deep_animal_species', 'deep_animals.animal_species_id', '=', 'deep_animal_species.id');

                                        if ($traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $traceDeepAnimalSexFilter);
                                        }
                                        if ($traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'cultures.cultures_content_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                                        }
                                        if ($traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $traceDeepHumanOccupationFilter);
                                        }
                                        if ($traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $traceDeepHumanCountryFilter);
                                        }
                                    }
                                })->orWhereExists(function ($sub) use (
                                    $primaryType,
                                    $traceDeepAnimalSpeciesFilter,
                                    $traceDeepAnimalSexFilter,
                                    $traceDeepAnimalAgeFilter,
                                    $traceDeepHumanEthnicityFilter,
                                    $traceDeepHumanOccupationFilter,
                                    $traceDeepHumanCountryFilter
                                ) {
                                    $sub->select(DB::raw(1))
                                        ->from('nucleic_acids')
                                        ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                                        ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                                        ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                                        ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                                        ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                                        ->whereIn('parasites.parasites_origin_type', $this->typeVariants($primaryType));

                                    if ($primaryType === AnimalSamples::class) {
                                        $sub->join('animal_samples as deep_animal_samples', 'parasites.parasites_origin_id', '=', 'deep_animal_samples.id')
                                            ->join('animals as deep_animals', 'deep_animal_samples.animals_id', '=', 'deep_animals.id')
                                            ->leftJoin('animal_species as deep_animal_species', 'deep_animals.animal_species_id', '=', 'deep_animal_species.id');

                                        if ($traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $traceDeepAnimalSexFilter);
                                        }
                                        if ($traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                                        }
                                        if ($traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $traceDeepHumanOccupationFilter);
                                        }
                                        if ($traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $traceDeepHumanCountryFilter);
                                        }
                                    }
                                });
                            });
                        }

                        if ($tracePrimaryTypeFilter === 'pool') {
                            $query->where(function ($w) use (
                                $primaryType,
                                $traceDeepAnimalSpeciesFilter,
                                $traceDeepAnimalSexFilter,
                                $traceDeepAnimalAgeFilter,
                                $traceDeepHumanEthnicityFilter,
                                $traceDeepHumanOccupationFilter,
                                $traceDeepHumanCountryFilter
                            ) {
                                $w->whereExists(function ($sub) use (
                                    $primaryType,
                                    $traceDeepAnimalSpeciesFilter,
                                    $traceDeepAnimalSexFilter,
                                    $traceDeepAnimalAgeFilter,
                                    $traceDeepHumanEthnicityFilter,
                                    $traceDeepHumanOccupationFilter,
                                    $traceDeepHumanCountryFilter
                                ) {
                                    $sub->select(DB::raw(1))
                                        ->from('nucleic_acids')
                                        ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                                        ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                                        ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                                        ->whereIn('pool_contents.samples_type', $this->typeVariants($primaryType));

                                    if ($primaryType === AnimalSamples::class) {
                                        $sub->join('animal_samples as deep_animal_samples', 'pool_contents.samples_id', '=', 'deep_animal_samples.id')
                                            ->join('animals as deep_animals', 'deep_animal_samples.animals_id', '=', 'deep_animals.id')
                                            ->leftJoin('animal_species as deep_animal_species', 'deep_animals.animal_species_id', '=', 'deep_animal_species.id');

                                        if ($traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $traceDeepAnimalSexFilter);
                                        }
                                        if ($traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'pool_contents.samples_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                                        }
                                        if ($traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $traceDeepHumanOccupationFilter);
                                        }
                                        if ($traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $traceDeepHumanCountryFilter);
                                        }
                                    }
                                })->orWhereExists(function ($sub) use (
                                    $primaryType,
                                    $traceDeepAnimalSpeciesFilter,
                                    $traceDeepAnimalSexFilter,
                                    $traceDeepAnimalAgeFilter,
                                    $traceDeepHumanEthnicityFilter,
                                    $traceDeepHumanOccupationFilter,
                                    $traceDeepHumanCountryFilter
                                ) {
                                    $sub->select(DB::raw(1))
                                        ->from('nucleic_acids')
                                        ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                                        ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                                        ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                                        ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                                        ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                                        ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                                        ->whereIn('parasites.parasites_origin_type', $this->typeVariants($primaryType));

                                    if ($primaryType === AnimalSamples::class) {
                                        $sub->join('animal_samples as deep_animal_samples', 'parasites.parasites_origin_id', '=', 'deep_animal_samples.id')
                                            ->join('animals as deep_animals', 'deep_animal_samples.animals_id', '=', 'deep_animals.id')
                                            ->leftJoin('animal_species as deep_animal_species', 'deep_animals.animal_species_id', '=', 'deep_animal_species.id');

                                        if ($traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $traceDeepAnimalSexFilter);
                                        }
                                        if ($traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                                        }
                                        if ($traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $traceDeepHumanOccupationFilter);
                                        }
                                        if ($traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $traceDeepHumanCountryFilter);
                                        }
                                    }
                                });
                            });
                        }
                    }
                }
            }

            if ($target === 'Cultures') {
                $upstreamType = match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'nucleic' => NucleicAcids::class,
                    'pool' => Pools::class,
                    default => null,
                };

                if ($upstreamType) {
                    $query->whereExists(function ($sub) use (
                        $upstreamType,
                        $tracePrimaryAnimalSpeciesFilter,
                        $tracePrimaryAnimalSexFilter,
                        $tracePrimaryAnimalAgeFilter,
                        $tracePrimaryHumanEthnicityFilter,
                        $tracePrimaryHumanOccupationFilter,
                        $tracePrimaryHumanCountryFilter,
                        $tracePrimaryParasiteSpeciesFilter,
                        $tracePrimaryParasiteStageFilter,
                        $tracePrimaryParasiteSexFilter,
                        $tracePrimaryParasiteStateFilter,
                        $tracePrimaryParasiteSampleTypeFilter,
                        $traceDeepPrimaryTypeFilter,
                        $traceDeepAnimalSpeciesFilter,
                        $traceDeepAnimalSexFilter,
                        $traceDeepAnimalAgeFilter,
                        $traceDeepHumanEthnicityFilter,
                        $traceDeepHumanOccupationFilter,
                        $traceDeepHumanCountryFilter
                    ) {
                        $sub->select(DB::raw(1))
                            ->from('cultures')
                            ->whereColumn('cultures.id', 'experiments.experiments_content_id')
                            ->whereIn('cultures.cultures_content_type', $this->typeVariants($upstreamType));

                        if ($upstreamType === AnimalSamples::class) {
                            $sub->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id')
                                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

                            if ($tracePrimaryAnimalSpeciesFilter !== '') {
                                $sub->where('animal_species.name_common', $tracePrimaryAnimalSpeciesFilter);
                            }
                            if ($tracePrimaryAnimalSexFilter !== '') {
                                $sub->where('animals.sex', $tracePrimaryAnimalSexFilter);
                            }
                            if ($tracePrimaryAnimalAgeFilter !== '') {
                                $sub->where('animals.age', $tracePrimaryAnimalAgeFilter);
                            }
                        }

                        if ($upstreamType === HumanSamples::class) {
                            $sub->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id')
                                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

                            if ($tracePrimaryHumanEthnicityFilter !== '') {
                                $sub->where('humans.ethnicity', $tracePrimaryHumanEthnicityFilter);
                            }
                            if ($tracePrimaryHumanOccupationFilter !== '') {
                                $sub->where('humans.occupation', $tracePrimaryHumanOccupationFilter);
                            }
                            if ($tracePrimaryHumanCountryFilter !== '') {
                                $sub->where('countries.name', $tracePrimaryHumanCountryFilter);
                            }
                        }

                        if ($upstreamType === ParasiteSamples::class) {
                            $sub->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                                ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id');

                            if ($tracePrimaryParasiteSpeciesFilter !== '') {
                                $sub->where('parasite_species.name_scientific', $tracePrimaryParasiteSpeciesFilter);
                            }
                            if ($tracePrimaryParasiteStageFilter !== '') {
                                $sub->where('parasites.stage', $tracePrimaryParasiteStageFilter);
                            }
                            if ($tracePrimaryParasiteSexFilter !== '') {
                                $sub->where('parasites.sex', $tracePrimaryParasiteSexFilter);
                            }
                            if ($tracePrimaryParasiteStateFilter !== '') {
                                $sub->where('parasites.state', $tracePrimaryParasiteStateFilter);
                            }
                            if ($tracePrimaryParasiteSampleTypeFilter !== '') {
                                $sub->where('parasite_sample_types.name', $tracePrimaryParasiteSampleTypeFilter);
                            }

                            // Deep trace: parasite -> primary (human/animal/environment)
                            if (
                                $traceDeepPrimaryTypeFilter !== 'all'
                                || $traceDeepAnimalSpeciesFilter !== ''
                                || $traceDeepAnimalSexFilter !== ''
                                || $traceDeepAnimalAgeFilter !== ''
                                || $traceDeepHumanEthnicityFilter !== ''
                                || $traceDeepHumanOccupationFilter !== ''
                                || $traceDeepHumanCountryFilter !== ''
                            ) {
                                $primaryType = match ($traceDeepPrimaryTypeFilter) {
                                    'human' => HumanSamples::class,
                                    'animal' => AnimalSamples::class,
                                    'environment' => EnvironmentSamples::class,
                                    default => null,
                                };

                                if ($primaryType) {
                                    $sub->whereIn('parasites.parasites_origin_type', $this->typeVariants($primaryType));

                                    if ($primaryType === AnimalSamples::class) {
                                        $sub->join('animal_samples as deep_animal_samples', 'parasites.parasites_origin_id', '=', 'deep_animal_samples.id')
                                            ->join('animals as deep_animals', 'deep_animal_samples.animals_id', '=', 'deep_animals.id')
                                            ->leftJoin('animal_species as deep_animal_species', 'deep_animals.animal_species_id', '=', 'deep_animal_species.id');

                                        if ($traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $traceDeepAnimalSexFilter);
                                        }
                                        if ($traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                                        }
                                        if ($traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $traceDeepHumanOccupationFilter);
                                        }
                                        if ($traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $traceDeepHumanCountryFilter);
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            if ($target === 'Pools') {
                $primaryType = match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                };

                if ($primaryType) {
                    $seedIds = $this->seedIdsForTracing(
                        $primaryType,
                        $tracePrimaryAnimalSpeciesFilter,
                        $tracePrimaryAnimalSexFilter,
                        $tracePrimaryAnimalAgeFilter,
                        $tracePrimaryHumanEthnicityFilter,
                        $tracePrimaryHumanOccupationFilter,
                        $tracePrimaryHumanCountryFilter,
                        $projectId,
                        $isGuestMode
                    );

                    $reachability = new PrimarySampleReachability;
                    $poolIds = $reachability->poolIdsFromPrimary($primaryType, $seedIds, $projectId, $isGuestMode, maxDepth: 10);

                    $query->whereIn('experiments.experiments_content_id', $poolIds);
                } else {
                    $upstreamType = match ($tracePrimaryTypeFilter) {
                        'parasite' => ParasiteSamples::class,
                        'nucleic' => NucleicAcids::class,
                        'culture' => Cultures::class,
                        'pool' => Pools::class,
                        default => null,
                    };

                    if ($upstreamType) {
                        $query->whereExists(function ($sub) use ($upstreamType) {
                            $sub->select(DB::raw(1))
                                ->from('pool_contents')
                                ->whereColumn('pool_contents.pools_id', 'experiments.experiments_content_id')
                                ->whereIn('pool_contents.samples_type', $this->typeVariants($upstreamType));
                        });
                    }
                }

                if ($tracePrimaryPoolMinNrPooled !== null || $tracePrimaryPoolMaxNrPooled !== null) {
                    $query->whereExists(function ($sub) use ($tracePrimaryPoolMinNrPooled, $tracePrimaryPoolMaxNrPooled) {
                        $sub->select(DB::raw(1))
                            ->from('pools')
                            ->whereColumn('pools.id', 'experiments.experiments_content_id');

                        if ($tracePrimaryPoolMinNrPooled !== null) {
                            $sub->where('pools.nr_pooled', '>=', $tracePrimaryPoolMinNrPooled);
                        }
                        if ($tracePrimaryPoolMaxNrPooled !== null) {
                            $sub->where('pools.nr_pooled', '<=', $tracePrimaryPoolMaxNrPooled);
                        }
                    });
                }
            }

            if ($target === 'ParasiteSamples') {
                $originType = match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                };

                if ($originType) {
                    $query->whereExists(function ($sub) use (
                        $originType,
                        $tracePrimaryAnimalSpeciesFilter,
                        $tracePrimaryAnimalSexFilter,
                        $tracePrimaryAnimalAgeFilter,
                        $tracePrimaryHumanEthnicityFilter,
                        $tracePrimaryHumanOccupationFilter,
                        $tracePrimaryHumanCountryFilter
                    ) {
                        $sub->select(DB::raw(1))
                            ->from('parasite_samples')
                            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                            ->whereColumn('parasite_samples.id', 'experiments.experiments_content_id')
                            ->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));

                        if ($originType === AnimalSamples::class) {
                            $sub->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id')
                                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

                            if ($tracePrimaryAnimalSpeciesFilter !== '') {
                                $sub->where('animal_species.name_common', $tracePrimaryAnimalSpeciesFilter);
                            }
                            if ($tracePrimaryAnimalSexFilter !== '') {
                                $sub->where('animals.sex', $tracePrimaryAnimalSexFilter);
                            }
                            if ($tracePrimaryAnimalAgeFilter !== '') {
                                $sub->where('animals.age', $tracePrimaryAnimalAgeFilter);
                            }
                        }

                        if ($originType === HumanSamples::class) {
                            $sub->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id')
                                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

                            if ($tracePrimaryHumanEthnicityFilter !== '') {
                                $sub->where('humans.ethnicity', $tracePrimaryHumanEthnicityFilter);
                            }
                            if ($tracePrimaryHumanOccupationFilter !== '') {
                                $sub->where('humans.occupation', $tracePrimaryHumanOccupationFilter);
                            }
                            if ($tracePrimaryHumanCountryFilter !== '') {
                                $sub->where('countries.name', $tracePrimaryHumanCountryFilter);
                            }
                        }
                    });
                }
            }
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('date_tested', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('date_tested', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('date_tested', '<=', $endDate);
        }

        $protocol = (string) $request->query('protocolFilter', '');
        if ($protocol !== '') {
            $query->whereHas('protocols', function ($q) use ($protocol) {
                $q->where('name', $protocol);
            });
        }

        $techniqueType = (string) $request->query('techniqueTypeFilter', '');
        if ($techniqueType !== '') {
            $query->whereHas('protocols.techniques', function ($q) use ($techniqueType) {
                $q->where('name', $techniqueType);
            });
        }

        $techniqueCategory = (string) $request->query('techniqueCategoryFilter', '');
        if ($techniqueCategory !== '') {
            $query->whereHas('protocols.techniques', function ($q) use ($techniqueCategory) {
                $q->where('type', $techniqueCategory);
            });
        }

        $pathogen = (string) $request->query('pathogenFilter', '');
        if ($pathogen !== '') {
            $query->whereHas('pathogens', function ($q) use ($pathogen) {
                $q->where('species', $pathogen);
            });
        }

        $outcome = (string) $request->query('outcomeFilter', '');
        if ($outcome !== '') {
            $query->where('outcome_discrete', $outcome);
        }

        $purpose = (string) $request->query('purposeFilter', '');
        if ($purpose !== '') {
            $this->applyPurposeFilter($query, $purpose);
        }

        // Content-specific filters for experiment content itself.
        if (class_basename($normalizedExperimentType) === 'AnimalSamples') {
            if ($animalSpeciesFilter !== '') {
                $query->whereExists(function ($sub) use ($animalSpeciesFilter) {
                    $sub->select(DB::raw(1))
                        ->from('animal_samples')
                        ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                        ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                        ->whereColumn('animal_samples.id', 'experiments.experiments_content_id')
                        ->where('animal_species.name_common', $animalSpeciesFilter);
                });
            }

            if ($animalSexFilter !== '') {
                $query->whereExists(function ($sub) use ($animalSexFilter) {
                    $sub->select(DB::raw(1))
                        ->from('animal_samples')
                        ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                        ->whereColumn('animal_samples.id', 'experiments.experiments_content_id')
                        ->where('animals.sex', $animalSexFilter);
                });
            }
        }

        if (class_basename($normalizedExperimentType) === 'ParasiteSamples') {
            if (
                $parasiteSpeciesFilter !== ''
                || $parasiteStageFilter !== ''
                || $parasiteSexFilter !== ''
                || $parasiteStateFilter !== ''
                || $parasiteSampleTypeFilter !== ''
            ) {
                $query->whereExists(function ($sub) use (
                    $parasiteSpeciesFilter,
                    $parasiteStageFilter,
                    $parasiteSexFilter,
                    $parasiteStateFilter,
                    $parasiteSampleTypeFilter
                ) {
                    $sub->select(DB::raw(1))
                        ->from('parasite_samples')
                        ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                        ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                        ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
                        ->whereColumn('parasite_samples.id', 'experiments.experiments_content_id');

                    if ($parasiteSpeciesFilter !== '') {
                        $sub->where('parasite_species.name_scientific', $parasiteSpeciesFilter);
                    }
                    if ($parasiteStageFilter !== '') {
                        $sub->where('parasites.stage', $parasiteStageFilter);
                    }
                    if ($parasiteSexFilter !== '') {
                        $sub->where('parasites.sex', $parasiteSexFilter);
                    }
                    if ($parasiteStateFilter !== '') {
                        $sub->where('parasites.state', $parasiteStateFilter);
                    }
                    if ($parasiteSampleTypeFilter !== '') {
                        $sub->where('parasite_sample_types.name', $parasiteSampleTypeFilter);
                    }
                });
            }
        }

        if (class_basename($normalizedExperimentType) === 'Cultures') {
            if ($cultureTypeFilter !== '' || $cultureMediumFilter !== '') {
                $query->whereExists(function ($sub) use ($cultureTypeFilter, $cultureMediumFilter) {
                    $sub->select(DB::raw(1))
                        ->from('cultures')
                        ->whereColumn('cultures.id', 'experiments.experiments_content_id');

                    if ($cultureTypeFilter !== '') {
                        $sub->where('cultures.type', $cultureTypeFilter);
                    }
                    if ($cultureMediumFilter !== '') {
                        $sub->where('cultures.medium', $cultureMediumFilter);
                    }
                });
            }
        }

        if (class_basename($normalizedExperimentType) === 'NucleicAcids') {
            if ($nucleicTypeFilter !== '') {
                $query->whereExists(function ($sub) use ($nucleicTypeFilter) {
                    $sub->select(DB::raw(1))
                        ->from('nucleic_acids')
                        ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                        ->where('nucleic_acids.type', $nucleicTypeFilter);
                });
            }
        }

        if (class_basename($normalizedExperimentType) === 'Pools') {
            if (
                $poolMinNrPooledFilter !== null
                || $poolMaxNrPooledFilter !== null
                || ($poolContentTypeFilter !== '' && $poolContentTypeFilter !== 'all')
            ) {
                $query->whereExists(function ($sub) use ($poolMinNrPooledFilter, $poolMaxNrPooledFilter, $poolContentTypeFilter) {
                    $sub->select(DB::raw(1))
                        ->from('pools')
                        ->whereColumn('pools.id', 'experiments.experiments_content_id');

                    if ($poolMinNrPooledFilter !== null) {
                        $sub->where('pools.nr_pooled', '>=', $poolMinNrPooledFilter);
                    }
                    if ($poolMaxNrPooledFilter !== null) {
                        $sub->where('pools.nr_pooled', '<=', $poolMaxNrPooledFilter);
                    }

                    if ($poolContentTypeFilter !== '' && $poolContentTypeFilter !== 'all') {
                        $contentType = match ($poolContentTypeFilter) {
                            'human' => HumanSamples::class,
                            'animal' => AnimalSamples::class,
                            'environment' => EnvironmentSamples::class,
                            'parasite' => ParasiteSamples::class,
                            'culture' => Cultures::class,
                            'nucleic' => NucleicAcids::class,
                            'pool' => Pools::class,
                            default => null,
                        };

                        if ($contentType) {
                            $sub->whereExists(function ($sub2) use ($contentType) {
                                $sub2->select(DB::raw(1))
                                    ->from('pool_contents')
                                    ->whereColumn('pool_contents.pools_id', 'pools.id')
                                    ->whereIn('pool_contents.samples_type', $this->typeVariants($contentType));
                            });
                        }
                    }
                });
            }
        }

        if ($samplingSiteFilter !== '') {
            $scopedPrimaryType = $this->scopedPrimaryTypeForSamplingSiteFromRequest(
                $normalizedExperimentType,
                $tracePrimaryTypeFilter,
                $traceDeepPrimaryTypeFilter
            );

            $experimentIds = app(PrimarySampleReachability::class)->experimentIdsFromSamplingSiteName(
                $samplingSiteFilter,
                $projectId,
                $isGuestMode,
                $scopedPrimaryType
            );

            if ($experimentIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($w) use ($experimentIds) {
                    foreach (array_chunk($experimentIds, 800) as $chunk) {
                        $w->orWhereIn('experiments.id', $chunk);
                    }
                });
            }
        }

        return $query;
    }

    /**
     * @return class-string|null
     */
    private function scopedPrimaryTypeForSamplingSiteFromRequest(
        string $normalizedExperimentType,
        string $tracePrimaryTypeFilter,
        string $traceDeepPrimaryTypeFilter
    ): ?string {
        return match (class_basename($normalizedExperimentType)) {
            'HumanSamples' => HumanSamples::class,
            'AnimalSamples' => AnimalSamples::class,
            'EnvironmentSamples' => EnvironmentSamples::class,
            default => match ($traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                },
            },
        };
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function seedIdsForTracing(
        string $seedType,
        string $animalSpecies,
        string $animalSex,
        string $animalAge,
        string $humanEthnicity,
        string $humanOccupation,
        string $humanCountry,
        ?int $projectId,
        bool $isGuestMode
    ): array {
        $table = (new $seedType)->getTable();
        $q = $seedType::query()->select($table.'.id');

        if ($seedType === AnimalSamples::class) {
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

        if ($seedType === HumanSamples::class) {
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
            $q->whereExists(function ($sub) use ($seedType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($seedType))
                    ->where('tubes.is_private', false);
            });
        } elseif ($projectId) {
            $q->where(function ($w) use ($projectId, $seedType, $table) {
                $w->where($table.'.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($projectId, $seedType, $table) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($seedType))
                            ->where('tubes.projects_id', $projectId);
                    });
            });
        }

        return $q->pluck($table.'.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @return array<int,string>
     */
    private function typeVariants(string $type): array
    {
        $type = $this->normalizeType($type);
        $base = class_basename($type);

        return array_values(array_unique([
            $type,
            "App\\Models\\{$base}",
            "AppModels{$base}",
            $base,
        ]));
    }

    /**
     * @param  Collection<int, Experiments>  $experiments
     * @param  Collection<int, SamplingSites>  $samplingSites
     * @return array<int, array{latitude: float, longitude: float, outcome_discrete: ?string, type: string, code: ?string, sampling_site_id: ?int, sampling_site_name: ?string, protocol:?string, pathogen:?string, laboratory:?string, technique_type:?string, experiment_code:?string, sample_title:string, sample_details:array<int,string>}>
     */
    private function experimentsToPoints(Collection $experiments, Collection $samplingSites, Collection $protocolMeta, Collection $pathogenMeta, Collection $laboratoryMeta): array
    {
        $byType = $experiments->groupBy(fn ($e) => $this->normalizeType((string) $e->experiments_content_type));

        $human = $this->loadPrimarySamples($byType->get(HumanSamples::class, collect()), HumanSamples::class);
        $animal = $this->loadPrimarySamples($byType->get(AnimalSamples::class, collect()), AnimalSamples::class);
        $environment = $this->loadPrimarySamples($byType->get(EnvironmentSamples::class, collect()), EnvironmentSamples::class);

        $parasiteSamples = $this->loadParasiteSamples($byType->get(ParasiteSamples::class, collect()));

        $nucleic = $this->loadNucleicAcids($byType->get(NucleicAcids::class, collect()));
        $cultures = $this->loadCultures($byType->get(Cultures::class, collect()));
        $pools = $this->loadPools($byType->get(Pools::class, collect()));

        $primarySamples = $human
            ->merge($animal)
            ->merge($environment);

        $points = [];

        foreach ($experiments as $experiment) {
            $contentType = $this->normalizeType((string) $experiment->experiments_content_type);
            $contentId = $experiment->experiments_content_id;

            [$lat, $lng, $code, $typeLabel, $samplingSiteId] = $this->resolveExperimentLocation(
                $contentType,
                $contentId,
                $primarySamples,
                $parasiteSamples,
                $nucleic,
                $cultures,
                $pools,
                $samplingSites
            );

            if (! $lat || ! $lng) {
                continue;
            }

            $samplingSiteName = null;
            if ($samplingSiteId) {
                $samplingSiteName = $samplingSites->get($samplingSiteId)?->name;
            }

            $sampleDescriptor = $this->describeExperimentContent(
                $contentType,
                (int) $contentId,
                $primarySamples,
                $parasiteSamples,
                $nucleic,
                $cultures,
                $pools
            );

            $points[] = [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'outcome_discrete' => $experiment->outcome_discrete,
                'type' => $typeLabel,
                'code' => $code,
                'sampling_site_id' => $samplingSiteId,
                'sampling_site_name' => $samplingSiteName,
                'protocol' => data_get($protocolMeta, $experiment->protocols_id.'.name'),
                'technique_type' => data_get($protocolMeta, $experiment->protocols_id.'.technique_type'),
                'pathogen' => $pathogenMeta->get($experiment->pathogens_id),
                'laboratory' => $laboratoryMeta->get($experiment->laboratories_id),
                'experiment_code' => $experiment->code,
                'date_tested' => $experiment->date_tested
                    ? Carbon::parse($experiment->date_tested)->format('Y-m-d')
                    : null,
                'sample_title' => $sampleDescriptor['title'],
                'sample_details' => $sampleDescriptor['details'],
                'sample_detail_groups' => $sampleDescriptor['detail_groups'],
                'sample_variables' => $sampleDescriptor['variables'],
            ];
        }

        return $points;
    }

    /**
     * @return Collection<int, array{id:int, code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int, model:string, details:array<int,string>}>
     */
    private function loadPrimarySamples(Collection $experiments, string $modelClass): Collection
    {
        $ids = $experiments->pluck('experiments_content_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        if ($modelClass === HumanSamples::class) {
            $rows = HumanSamples::query()
                ->whereIn('human_samples.id', $ids)
                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
                ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
                ->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->get([
                    'human_samples.id',
                    'human_samples.code',
                    'human_samples.latitude',
                    'human_samples.longitude',
                    'human_samples.sampling_sites_id',
                    'human_samples.date_collected',
                    'humans.ethnicity',
                    'humans.occupation',
                    'countries.name as country_name',
                    'sample_types.name as sample_type_name',
                    'sampling_sites.name as sampling_site_name',
                ]);
        } elseif ($modelClass === AnimalSamples::class) {
            $rows = AnimalSamples::query()
                ->whereIn('animal_samples.id', $ids)
                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
                ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->get([
                    'animal_samples.id',
                    'animal_samples.code',
                    'animal_samples.latitude',
                    'animal_samples.longitude',
                    'animal_samples.sampling_sites_id',
                    'animal_samples.date_collected',
                    'animals.sex',
                    'animals.age',
                    'animal_species.name_common as species_name',
                    'sample_types.name as sample_type_name',
                    'sampling_sites.name as sampling_site_name',
                ]);
        } else {
            $rows = EnvironmentSamples::query()
                ->whereIn('environment_samples.id', $ids)
                ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
                ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->get([
                    'environment_samples.id',
                    'environment_samples.code',
                    'environment_samples.latitude',
                    'environment_samples.longitude',
                    'environment_samples.sampling_sites_id',
                    'environment_samples.date_collected',
                    'environment_sample_types.name as sample_type_name',
                    'sampling_sites.name as sampling_site_name',
                ]);
        }

        return $rows->mapWithKeys(function ($row) use ($modelClass) {
            $descriptor = match ($modelClass) {
                HumanSamples::class => $this->buildHumanSampleDescriptor($row),
                AnimalSamples::class => $this->buildAnimalSampleDescriptor($row),
                default => $this->buildEnvironmentSampleDescriptor($row),
            };

            return [
                $row->id => [
                    'id' => $row->id,
                    'code' => $row->code ?? null,
                    'latitude' => $row->latitude ? (float) $row->latitude : null,
                    'longitude' => $row->longitude ? (float) $row->longitude : null,
                    'sampling_sites_id' => $row->sampling_sites_id,
                    'model' => $modelClass,
                    'details' => $descriptor['details'],
                    'variables' => $descriptor['variables'],
                ],
            ];
        });
    }

    /**
     * @return Collection<int, array{id:int, code:?string, parasites_id:int, details:array<int,string>}>
     */
    private function loadParasiteSamples(Collection $experiments): Collection
    {
        $ids = $experiments->pluck('experiments_content_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = ParasiteSamples::query()
            ->whereIn('parasite_samples.id', $ids)
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->get([
                'parasite_samples.id',
                'parasite_samples.code',
                'parasite_samples.parasites_id',
                'parasite_samples.date_processed',
                'parasites.stage',
                'parasites.sex',
                'parasites.state',
                'parasites.parasites_origin_type',
                'parasites.parasites_origin_id',
                'parasite_species.name_scientific as parasite_species_name',
                'parasite_sample_types.name as parasite_sample_type_name',
            ]);

        return $rows->mapWithKeys(function ($row) {
            $descriptor = $this->buildParasiteSampleDescriptor($row);

            return [
                $row->id => [
                    'id' => $row->id,
                    'code' => $row->code ?? null,
                    'parasites_id' => (int) $row->parasites_id,
                    'parasites_origin_type' => $row->parasites_origin_type ? (string) $row->parasites_origin_type : null,
                    'parasites_origin_id' => $row->parasites_origin_id ? (int) $row->parasites_origin_id : null,
                    'details' => $descriptor['details'],
                    'variables' => $descriptor['variables'],
                ],
            ];
        });
    }

    /**
     * @return Collection<int, array{id:int, code:?string, nucleic_content_type:string, nucleic_content_id:int, details:array<int,string>}>
     */
    private function loadNucleicAcids(Collection $experiments): Collection
    {
        $ids = $experiments->pluck('experiments_content_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = NucleicAcids::query()
            ->whereIn('nucleic_acids.id', $ids)
            ->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id')
            ->get([
                'nucleic_acids.id',
                'nucleic_acids.code',
                'nucleic_acids.type',
                'nucleic_acids.date_extracted',
                'nucleic_acids.nucleic_content_type',
                'nucleic_acids.nucleic_content_id',
                'protocols.name as extraction_protocol_name',
            ]);

        return $rows->mapWithKeys(function ($row) {
            $descriptor = $this->buildNucleicAcidDescriptor($row);

            return [
                $row->id => [
                    'id' => $row->id,
                    'code' => $row->code ?? null,
                    'nucleic_content_type' => (string) $row->nucleic_content_type,
                    'nucleic_content_id' => (int) $row->nucleic_content_id,
                    'details' => $descriptor['details'],
                    'variables' => $descriptor['variables'],
                ],
            ];
        });
    }

    /**
     * @return Collection<int, array{id:int, code:?string, cultures_content_type:string, cultures_content_id:int, details:array<int,string>}>
     */
    private function loadCultures(Collection $experiments): Collection
    {
        $ids = $experiments->pluck('experiments_content_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = Cultures::query()
            ->whereIn('id', $ids)
            ->get(['id', 'code', 'type', 'medium', 'date_created', 'cultures_content_type', 'cultures_content_id']);

        return $rows->mapWithKeys(function ($row) {
            $descriptor = $this->buildCultureDescriptor($row);

            return [
                $row->id => [
                    'id' => $row->id,
                    'code' => $row->code ?? null,
                    'cultures_content_type' => (string) $row->cultures_content_type,
                    'cultures_content_id' => (int) $row->cultures_content_id,
                    'details' => $descriptor['details'],
                    'variables' => $descriptor['variables'],
                ],
            ];
        });
    }

    /**
     * @return Collection<int, array{id:int, code:?string, pool_samples: array<int, array{type: string, id: int}>, details:array<int,string>}>
     */
    private function loadPools(Collection $experiments): Collection
    {
        $ids = $experiments->pluck('experiments_content_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $pools = Pools::query()->whereIn('id', $ids)->get(['id', 'code', 'nr_pooled', 'type', 'date_created']);

        $poolContents = PoolContents::query()
            ->whereIn('pools_id', $ids)
            ->orderBy('id')
            ->get(['id', 'pools_id', 'samples_type', 'samples_id'])
            ->groupBy('pools_id')
            ->map(function ($group) {
                return $group
                    ->filter(fn ($row) => ! empty($row->samples_type) && ! empty($row->samples_id))
                    ->map(fn ($row) => ['type' => (string) $row->samples_type, 'id' => (int) $row->samples_id])
                    ->values()
                    ->all();
            });

        return $pools->mapWithKeys(function ($pool) use ($poolContents) {
            $members = $poolContents->get($pool->id, []);
            $descriptor = $this->buildPoolDescriptor($pool, $members);

            return [
                $pool->id => [
                    'id' => $pool->id,
                    'code' => $pool->code ?? null,
                    'pool_samples' => $members,
                    'details' => $descriptor['details'],
                    'variables' => $descriptor['variables'],
                ],
            ];
        });
    }

    /**
     * @return array{0:?float,1:?float,2:?string,3:string,4:?int}
     */
    private function resolveExperimentLocation(
        string $contentType,
        int $contentId,
        Collection $primarySamples,
        Collection $parasiteSamples,
        Collection $nucleic,
        Collection $cultures,
        Collection $pools,
        Collection $samplingSites
    ): array {
        $contentType = $this->normalizeType($contentType);
        $typeLabel = class_basename($contentType);

        if (in_array($contentType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
            $sample = $this->getPrimarySample($contentType, $contentId, $primarySamples);
            if (! $sample) {
                return [null, null, null, $typeLabel, null];
            }

            return [
                $this->lat($sample, $samplingSites),
                $this->lng($sample, $samplingSites),
                $sample['code'],
                $typeLabel,
                $sample['sampling_sites_id'] ?? null,
            ];
        }

        if ($contentType === ParasiteSamples::class) {
            $ps = $parasiteSamples->get($contentId);
            if (! $ps) {
                return [null, null, null, $typeLabel, null];
            }

            $parasite = $this->getParasite($ps['parasites_id']);
            if (! $parasite) {
                return [null, null, $ps['code'], $typeLabel, null];
            }

            $originType = $this->normalizeType((string) $parasite['parasites_origin_type']);
            $originId = (int) $parasite['parasites_origin_id'];

            if (in_array($originType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
                $origin = $this->getPrimarySample($originType, $originId, $primarySamples);
                if (! $origin) {
                    return [null, null, $ps['code'], $typeLabel, null];
                }

                return [
                    $this->lat($origin, $samplingSites),
                    $this->lng($origin, $samplingSites),
                    $ps['code'],
                    $typeLabel,
                    $origin['sampling_sites_id'] ?? null,
                ];
            }

            return [null, null, $ps['code'], $typeLabel, null];
        }

        if ($contentType === NucleicAcids::class) {
            $na = $nucleic->get($contentId);
            if (! $na) {
                return [null, null, null, $typeLabel, null];
            }

            return $this->resolveDerivedLocation(
                $na['nucleic_content_type'],
                $na['nucleic_content_id'],
                $primarySamples,
                $cultures,
                $pools,
                $samplingSites,
                $na['code'],
                3
            );
        }

        if ($contentType === Cultures::class) {
            $c = $cultures->get($contentId);
            if (! $c) {
                return [null, null, null, $typeLabel, null];
            }

            return $this->resolveDerivedLocation(
                $c['cultures_content_type'],
                $c['cultures_content_id'],
                $primarySamples,
                $cultures,
                $pools,
                $samplingSites,
                $c['code'],
                3
            );
        }

        if ($contentType === Pools::class) {
            $p = $pools->get($contentId);
            if (! $p) {
                return [null, null, null, $typeLabel, null];
            }

            foreach (($p['pool_samples'] ?? []) as $member) {
                $result = $this->resolveDerivedLocation(
                    (string) $member['type'],
                    (int) $member['id'],
                    $primarySamples,
                    $cultures,
                    $pools,
                    $samplingSites,
                    $p['code'],
                    5
                );

                if ($result[0] && $result[1]) {
                    return $result;
                }
            }

            return [null, null, $p['code'] ?? null, $typeLabel, null];
        }

        return [null, null, null, $typeLabel, null];
    }

    /**
     * Resolve derived models (NucleicAcids/Cultures/Pools) down to primary samples or parasites origins.
     *
     * @return array{0:?float,1:?float,2:?string,3:string,4:?int}
     */
    private function resolveDerivedLocation(
        string $type,
        int $id,
        Collection $primarySamples,
        Collection $cultures,
        Collection $pools,
        Collection $samplingSites,
        ?string $codeForPoint,
        int $depth
    ): array {
        if ($depth <= 0) {
            return [null, null, $codeForPoint, class_basename($type), null];
        }

        $type = $this->normalizeType($type);
        $typeLabel = class_basename($type);

        if (in_array($type, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
            $sample = $this->getPrimarySample($type, $id, $primarySamples);
            if (! $sample) {
                return [null, null, $codeForPoint, $typeLabel, null];
            }

            return [
                $this->lat($sample, $samplingSites),
                $this->lng($sample, $samplingSites),
                $codeForPoint,
                $typeLabel,
                $sample['sampling_sites_id'] ?? null,
            ];
        }

        if ($type === Tubes::class) {
            $tube = $this->getTube($id);
            if (! $tube) {
                return [null, null, $codeForPoint, $typeLabel, null];
            }

            return $this->resolveDerivedLocation(
                $tube['tubes_content_type'],
                (int) $tube['tubes_content_id'],
                $primarySamples,
                $cultures,
                $pools,
                $samplingSites,
                $codeForPoint,
                $depth - 1
            );
        }

        if ($type === ParasiteSamples::class) {
            $ps = $this->getParasiteSample($id);
            if (! $ps) {
                return [null, null, $codeForPoint, $typeLabel, null];
            }

            $parasite = $this->getParasite($ps['parasites_id']);
            if (! $parasite) {
                return [null, null, $codeForPoint, $typeLabel, null];
            }

            return $this->resolveDerivedLocation(
                $parasite['parasites_origin_type'],
                (int) $parasite['parasites_origin_id'],
                $primarySamples,
                $cultures,
                $pools,
                $samplingSites,
                $codeForPoint,
                $depth - 1
            );
        }

        if ($type === Parasites::class) {
            $parasite = $this->getParasite($id);
            if (! $parasite) {
                return [null, null, $codeForPoint, $typeLabel, null];
            }

            $originType = $this->normalizeType((string) $parasite['parasites_origin_type']);
            $originId = (int) $parasite['parasites_origin_id'];

            return $this->resolveDerivedLocation(
                $originType,
                $originId,
                $primarySamples,
                $cultures,
                $pools,
                $samplingSites,
                $codeForPoint,
                $depth - 1
            );
        }

        if ($type === Cultures::class) {
            $c = $cultures->get($id);
            if (! $c) {
                $cRow = Cultures::query()->find($id, ['id', 'cultures_content_type', 'cultures_content_id']);
                if (! $cRow) {
                    return [null, null, $codeForPoint, $typeLabel, null];
                }
                $c = [
                    'cultures_content_type' => (string) $cRow->cultures_content_type,
                    'cultures_content_id' => (int) $cRow->cultures_content_id,
                ];
            }

            return $this->resolveDerivedLocation(
                $c['cultures_content_type'],
                (int) $c['cultures_content_id'],
                $primarySamples,
                $cultures,
                $pools,
                $samplingSites,
                $codeForPoint,
                $depth - 1
            );
        }

        if ($type === Pools::class) {
            $p = $pools->get($id);
            if (! $p) {
                $pRow = Pools::query()->find($id, ['id', 'code']);
                if (! $pRow) {
                    return [null, null, $codeForPoint, $typeLabel, null];
                }

                $pcs = PoolContents::query()
                    ->where('pools_id', $id)
                    ->orderBy('id')
                    ->get(['samples_type', 'samples_id']);

                foreach ($pcs as $pc) {
                    if (! $pc->samples_type || ! $pc->samples_id) {
                        continue;
                    }

                    $result = $this->resolveDerivedLocation(
                        (string) $pc->samples_type,
                        (int) $pc->samples_id,
                        $primarySamples,
                        $cultures,
                        $pools,
                        $samplingSites,
                        $codeForPoint,
                        $depth - 1
                    );

                    if ($result[0] && $result[1]) {
                        return $result;
                    }
                }

                return [null, null, $codeForPoint, $typeLabel, null];
            }

            foreach (($p['pool_samples'] ?? []) as $member) {
                $result = $this->resolveDerivedLocation(
                    (string) $member['type'],
                    (int) $member['id'],
                    $primarySamples,
                    $cultures,
                    $pools,
                    $samplingSites,
                    $codeForPoint,
                    $depth - 1
                );

                if ($result[0] && $result[1]) {
                    return $result;
                }
            }

            return [null, null, $codeForPoint, $typeLabel, null];
        }

        if ($type === NucleicAcids::class) {
            $naRow = NucleicAcids::query()->find($id, ['id', 'nucleic_content_type', 'nucleic_content_id']);
            if (! $naRow) {
                return [null, null, $codeForPoint, $typeLabel, null];
            }

            return $this->resolveDerivedLocation(
                (string) $naRow->nucleic_content_type,
                (int) $naRow->nucleic_content_id,
                $primarySamples,
                $cultures,
                $pools,
                $samplingSites,
                $codeForPoint,
                $depth - 1
            );
        }

        return [null, null, $codeForPoint, $typeLabel, null];
    }

    /**
     * @return array{title:string, details:array<int,string>, detail_groups:array<int,array{title:string,details:array<int,string>}>, variables:array<string,string>}
     */
    private function describeExperimentContent(
        string $contentType,
        int $contentId,
        Collection $primarySamples,
        Collection $parasiteSamples,
        Collection $nucleic,
        Collection $cultures,
        Collection $pools
    ): array {
        $detailGroups = $this->resolveContentDetailGroups(
            $this->normalizeType($contentType),
            $contentId,
            $primarySamples,
            $parasiteSamples,
            $nucleic,
            $cultures,
            $pools,
            6
        );

        $variables = [];
        $details = [];
        foreach ($detailGroups as $group) {
            foreach (($group['variables'] ?? []) as $key => $value) {
                if ($value !== null && trim((string) $value) !== '') {
                    $variables[$key] = (string) $value;
                }
            }
            foreach (($group['details'] ?? []) as $line) {
                $details[] = $line;
            }
        }

        $title = $detailGroups[0]['title'] ?? class_basename($this->normalizeType($contentType));

        return [
            'title' => $title,
            'details' => $this->filterDetails($details),
            'detail_groups' => array_map(
                fn (array $group) => [
                    'title' => $group['title'],
                    'details' => $this->filterDetails($group['details'] ?? []),
                ],
                $detailGroups
            ),
            'variables' => $variables,
        ];
    }

    /**
     * @return array<int, array{title:string, details:array<int,string>, variables:array<string,string>}>
     */
    private function resolveContentDetailGroups(
        string $contentType,
        int $contentId,
        Collection $primarySamples,
        Collection $parasiteSamples,
        Collection $nucleic,
        Collection $cultures,
        Collection $pools,
        int $depth
    ): array {
        if ($depth <= 0) {
            return [];
        }

        $contentType = $this->normalizeType($contentType);
        $typeLabel = class_basename($contentType);
        $groups = [];

        if (in_array($contentType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
            $sample = $this->getPrimarySample($contentType, $contentId, $primarySamples);
            if (! $sample) {
                return [];
            }

            $groups[] = [
                'title' => $typeLabel.' '.($sample['code'] ?? 'N/A'),
                'details' => $sample['details'] ?? [],
                'variables' => $sample['variables'] ?? [],
            ];

            return $groups;
        }

        if ($contentType === ParasiteSamples::class) {
            $sample = $parasiteSamples->get($contentId) ?? $this->getParasiteSampleRecord($contentId);
            if (! $sample) {
                return [];
            }

            $groups[] = [
                'title' => 'Parasite sample '.($sample['code'] ?? 'N/A'),
                'details' => $sample['details'] ?? [],
                'variables' => $sample['variables'] ?? [],
            ];

            if (! empty($sample['parasites_origin_type']) && ! empty($sample['parasites_origin_id'])) {
                $originGroups = $this->resolveContentDetailGroups(
                    (string) $sample['parasites_origin_type'],
                    (int) $sample['parasites_origin_id'],
                    $primarySamples,
                    $parasiteSamples,
                    $nucleic,
                    $cultures,
                    $pools,
                    $depth - 1
                );

                foreach ($originGroups as $originGroup) {
                    $groups[] = [
                        'title' => 'Collected from — '.$originGroup['title'],
                        'details' => $originGroup['details'] ?? [],
                        'variables' => $originGroup['variables'] ?? [],
                    ];
                }
            }

            return $groups;
        }

        if ($contentType === NucleicAcids::class) {
            $sample = $nucleic->get($contentId) ?? $this->getNucleicAcidRecord($contentId);
            if (! $sample) {
                return [];
            }

            $groups[] = [
                'title' => 'Nucleic acid '.($sample['code'] ?? 'N/A'),
                'details' => $sample['details'] ?? [],
                'variables' => $sample['variables'] ?? [],
            ];

            $upstreamGroups = $this->resolveContentDetailGroups(
                (string) $sample['nucleic_content_type'],
                (int) $sample['nucleic_content_id'],
                $primarySamples,
                $parasiteSamples,
                $nucleic,
                $cultures,
                $pools,
                $depth - 1
            );

            foreach ($upstreamGroups as $upstreamGroup) {
                $groups[] = [
                    'title' => 'Extracted from — '.$upstreamGroup['title'],
                    'details' => $upstreamGroup['details'] ?? [],
                    'variables' => $upstreamGroup['variables'] ?? [],
                ];
            }

            return $groups;
        }

        if ($contentType === Cultures::class) {
            $sample = $cultures->get($contentId) ?? $this->getCultureRecord($contentId);
            if (! $sample) {
                return [];
            }

            $groups[] = [
                'title' => 'Culture '.($sample['code'] ?? 'N/A'),
                'details' => $sample['details'] ?? [],
                'variables' => $sample['variables'] ?? [],
            ];

            $upstreamGroups = $this->resolveContentDetailGroups(
                (string) $sample['cultures_content_type'],
                (int) $sample['cultures_content_id'],
                $primarySamples,
                $parasiteSamples,
                $nucleic,
                $cultures,
                $pools,
                $depth - 1
            );

            foreach ($upstreamGroups as $upstreamGroup) {
                $groups[] = [
                    'title' => 'Derived from — '.$upstreamGroup['title'],
                    'details' => $upstreamGroup['details'] ?? [],
                    'variables' => $upstreamGroup['variables'] ?? [],
                ];
            }

            return $groups;
        }

        if ($contentType === Pools::class) {
            $sample = $pools->get($contentId) ?? $this->getPoolRecord($contentId);
            if (! $sample) {
                return [];
            }

            $groups[] = [
                'title' => 'Pool '.($sample['code'] ?? 'N/A'),
                'details' => $sample['details'] ?? [],
                'variables' => $sample['variables'] ?? [],
            ];

            foreach (($sample['pool_samples'] ?? []) as $member) {
                $memberGroups = $this->resolveContentDetailGroups(
                    (string) $member['type'],
                    (int) $member['id'],
                    $primarySamples,
                    $parasiteSamples,
                    $nucleic,
                    $cultures,
                    $pools,
                    $depth - 1
                );

                foreach ($memberGroups as $memberGroup) {
                    $groups[] = [
                        'title' => 'Pool member — '.$memberGroup['title'],
                        'details' => $memberGroup['details'] ?? [],
                        'variables' => $memberGroup['variables'] ?? [],
                    ];
                }
            }

            return $groups;
        }

        if ($contentType === Tubes::class) {
            $tube = $this->getTube($contentId);
            if (! $tube) {
                return [];
            }

            return $this->resolveContentDetailGroups(
                $tube['tubes_content_type'],
                (int) $tube['tubes_content_id'],
                $primarySamples,
                $parasiteSamples,
                $nucleic,
                $cultures,
                $pools,
                $depth - 1
            );
        }

        if ($contentType === Parasites::class) {
            $parasite = $this->getParasite($contentId);
            if (! $parasite) {
                return [];
            }

            return $this->resolveContentDetailGroups(
                $parasite['parasites_origin_type'],
                (int) $parasite['parasites_origin_id'],
                $primarySamples,
                $parasiteSamples,
                $nucleic,
                $cultures,
                $pools,
                $depth - 1
            );
        }

        return [
            [
                'title' => $typeLabel,
                'details' => [],
                'variables' => [],
            ],
        ];
    }

    private function normalizeType(string $type): string
    {
        // Normalize loose polymorphic strings like "Pools" to an actual FQCN.
        if (! str_contains($type, '\\') && ! str_starts_with($type, 'App\\Models\\')) {
            return "App\\Models\\{$type}";
        }

        if (str_starts_with($type, 'AppModels')) {
            return 'App\\Models\\'.substr($type, strlen('AppModels'));
        }

        return $type;
    }

    /**
     * Restrict the query by experiment test purpose. Mirrors ExperimentsDashboard::applyPurposeFilter.
     *
     * @param  Builder  $query
     */
    private function applyPurposeFilter($query, string $mode): void
    {
        if ($mode === 'screening' || $mode === 'confirmation') {
            $query->where('purpose', $mode);

            return;
        }

        if ($mode === 'either') {
            $query->whereIn('purpose', ['screening', 'confirmation']);

            return;
        }

        if ($mode === 'screening_with_confirmation') {
            $query->whereIn('experiments.purpose', ['screening', 'confirmation'])
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('experiments as purpose_screening')
                        ->whereColumn('purpose_screening.experiments_content_id', 'experiments.experiments_content_id')
                        ->whereColumn('purpose_screening.experiments_content_type', 'experiments.experiments_content_type')
                        ->where('purpose_screening.purpose', 'screening');
                })->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('experiments as purpose_confirmation')
                        ->whereColumn('purpose_confirmation.experiments_content_id', 'experiments.experiments_content_id')
                        ->whereColumn('purpose_confirmation.experiments_content_type', 'experiments.experiments_content_type')
                        ->where('purpose_confirmation.purpose', 'confirmation');
                })
                // Collapse to one representative experiment per origin sample so the
                // map, sampling-site value box and table modal count distinct samples
                // (not the screening + confirmation rows of each sample).
                ->whereRaw(
                    'experiments.id = (select min(rep.id) from experiments as rep'
                    .' where rep.experiments_content_type = experiments.experiments_content_type'
                    .' and rep.experiments_content_id = experiments.experiments_content_id'
                    .' and rep.purpose in (?, ?))',
                    ['screening', 'confirmation']
                );
        }
    }

    /**
     * @param  array<int, ?string>  $details
     * @return array<int, string>
     */
    private function filterDetails(array $details): array
    {
        return array_values(array_filter($details, fn (?string $value) => $value !== null && trim($value) !== ''));
    }

    /**
     * @return array{tubes_content_type: string, tubes_content_id: int}|null
     */
    private function getTube(int $id): ?array
    {
        if (isset($this->tubesCache[$id])) {
            return $this->tubesCache[$id];
        }

        $tube = Tubes::query()->find($id, ['id', 'tubes_content_type', 'tubes_content_id']);
        if (! $tube || ! $tube->tubes_content_type || ! $tube->tubes_content_id) {
            $this->tubesCache[$id] = null;

            return null;
        }

        return $this->tubesCache[$id] = [
            'tubes_content_type' => $this->normalizeType((string) $tube->tubes_content_type),
            'tubes_content_id' => (int) $tube->tubes_content_id,
        ];
    }

    /**
     * @return array{parasites_id: int}|null
     */
    private function getParasiteSample(int $id): ?array
    {
        if (isset($this->parasiteSamplesCache[$id])) {
            return $this->parasiteSamplesCache[$id];
        }

        $ps = ParasiteSamples::query()->find($id, ['id', 'parasites_id']);
        if (! $ps || ! $ps->parasites_id) {
            $this->parasiteSamplesCache[$id] = null;

            return null;
        }

        return $this->parasiteSamplesCache[$id] = [
            'parasites_id' => (int) $ps->parasites_id,
        ];
    }

    /**
     * @return array{parasites_origin_type: string, parasites_origin_id: int}|null
     */
    private function getParasite(int $id): ?array
    {
        if (isset($this->parasitesCache[$id])) {
            return $this->parasitesCache[$id];
        }

        $p = Parasites::query()->find($id, ['id', 'parasites_origin_type', 'parasites_origin_id']);
        if (! $p || ! $p->parasites_origin_type || ! $p->parasites_origin_id) {
            $this->parasitesCache[$id] = null;

            return null;
        }

        return $this->parasitesCache[$id] = [
            'parasites_origin_type' => $this->normalizeType((string) $p->parasites_origin_type),
            'parasites_origin_id' => (int) $p->parasites_origin_id,
        ];
    }

    private function getPrimarySample(string $type, int $id, Collection $primarySamples): ?array
    {
        $type = $this->normalizeType($type);

        $fromPreload = $primarySamples->get($id);
        if ($fromPreload) {
            return $fromPreload;
        }

        if ($type === HumanSamples::class) {
            return $this->getHumanSample($id);
        }

        if ($type === AnimalSamples::class) {
            return $this->getAnimalSample($id);
        }

        if ($type === EnvironmentSamples::class) {
            return $this->getEnvironmentSample($id);
        }

        return null;
    }

    /**
     * @return array{code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int}|null
     */
    private function getHumanSample(int $id): ?array
    {
        if (array_key_exists($id, $this->humanSamplesCache)) {
            return $this->humanSamplesCache[$id];
        }

        $row = HumanSamples::query()
            ->where('human_samples.id', $id)
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
            ->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id')
            ->first([
                'human_samples.id',
                'human_samples.code',
                'human_samples.latitude',
                'human_samples.longitude',
                'human_samples.sampling_sites_id',
                'human_samples.date_collected',
                'humans.ethnicity',
                'humans.occupation',
                'countries.name as country_name',
                'sample_types.name as sample_type_name',
                'sampling_sites.name as sampling_site_name',
            ]);

        if (! $row) {
            $this->humanSamplesCache[$id] = null;

            return null;
        }

        $descriptor = $this->buildHumanSampleDescriptor($row);

        return $this->humanSamplesCache[$id] = [
            'id' => $row->id,
            'code' => $row->code ?? null,
            'latitude' => $row->latitude ? (float) $row->latitude : null,
            'longitude' => $row->longitude ? (float) $row->longitude : null,
            'sampling_sites_id' => $row->sampling_sites_id,
            'model' => HumanSamples::class,
            'details' => $descriptor['details'],
            'variables' => $descriptor['variables'],
        ];
    }

    /**
     * @return array{code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int}|null
     */
    private function getAnimalSample(int $id): ?array
    {
        if (array_key_exists($id, $this->animalSamplesCache)) {
            return $this->animalSamplesCache[$id];
        }

        $row = AnimalSamples::query()
            ->where('animal_samples.id', $id)
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
            ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id')
            ->first([
                'animal_samples.id',
                'animal_samples.code',
                'animal_samples.latitude',
                'animal_samples.longitude',
                'animal_samples.sampling_sites_id',
                'animal_samples.date_collected',
                'animals.sex',
                'animals.age',
                'animal_species.name_common as species_name',
                'sample_types.name as sample_type_name',
                'sampling_sites.name as sampling_site_name',
            ]);

        if (! $row) {
            $this->animalSamplesCache[$id] = null;

            return null;
        }

        $descriptor = $this->buildAnimalSampleDescriptor($row);

        return $this->animalSamplesCache[$id] = [
            'id' => $row->id,
            'code' => $row->code ?? null,
            'latitude' => $row->latitude ? (float) $row->latitude : null,
            'longitude' => $row->longitude ? (float) $row->longitude : null,
            'sampling_sites_id' => $row->sampling_sites_id,
            'model' => AnimalSamples::class,
            'details' => $descriptor['details'],
            'variables' => $descriptor['variables'],
        ];
    }

    /**
     * @return array{code:?string, latitude:?float, longitude:?float, sampling_sites_id:?int}|null
     */
    private function getEnvironmentSample(int $id): ?array
    {
        if (array_key_exists($id, $this->environmentSamplesCache)) {
            return $this->environmentSamplesCache[$id];
        }

        $row = EnvironmentSamples::query()
            ->where('environment_samples.id', $id)
            ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
            ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id')
            ->first([
                'environment_samples.id',
                'environment_samples.code',
                'environment_samples.latitude',
                'environment_samples.longitude',
                'environment_samples.sampling_sites_id',
                'environment_samples.date_collected',
                'environment_sample_types.name as sample_type_name',
                'sampling_sites.name as sampling_site_name',
            ]);

        if (! $row) {
            $this->environmentSamplesCache[$id] = null;

            return null;
        }

        $descriptor = $this->buildEnvironmentSampleDescriptor($row);

        return $this->environmentSamplesCache[$id] = [
            'id' => $row->id,
            'code' => $row->code ?? null,
            'latitude' => $row->latitude ? (float) $row->latitude : null,
            'longitude' => $row->longitude ? (float) $row->longitude : null,
            'sampling_sites_id' => $row->sampling_sites_id,
            'model' => EnvironmentSamples::class,
            'details' => $descriptor['details'],
            'variables' => $descriptor['variables'],
        ];
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function emptyAggregateDistributions(): array
    {
        return [
            'outcome' => [],
            'type' => [],
            'protocol' => [],
            'pathogen' => [],
            'technique_type' => [],
            'laboratory' => [],
            'human_ethnicity' => [],
            'human_occupation' => [],
            'human_country' => [],
            'animal_species' => [],
            'animal_sex' => [],
            'animal_age' => [],
            'environment_sample_type' => [],
            'parasite_species' => [],
            'parasite_stage' => [],
            'parasite_sex' => [],
            'parasite_state' => [],
            'culture_type' => [],
            'culture_medium' => [],
            'nucleic_type' => [],
            'nucleic_extraction_protocol' => [],
            'pool_nr_pooled' => [],
            'sampling_site' => [],
        ];
    }

    /**
     * @return array{details: array<int, string>, variables: array<string, string>}
     */
    private function buildHumanSampleDescriptor(object $row): array
    {
        return [
            'details' => $this->filterDetails([
                $row->sample_type_name ? 'Sample type: '.$row->sample_type_name : null,
                $row->ethnicity ? 'Ethnicity: '.$row->ethnicity : null,
                $row->occupation ? 'Occupation: '.$row->occupation : null,
                $row->country_name ? 'Country: '.$row->country_name : null,
                $row->sampling_site_name ? 'Sampling site: '.$row->sampling_site_name : null,
                $row->date_collected ? 'Date collected: '.Carbon::parse($row->date_collected)->format('Y-m-d') : null,
            ]),
            'variables' => array_filter([
                'human_ethnicity' => $row->ethnicity ?? null,
                'human_occupation' => $row->occupation ?? null,
                'human_country' => $row->country_name ?? null,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @return array{details: array<int, string>, variables: array<string, string>}
     */
    private function buildAnimalSampleDescriptor(object $row): array
    {
        return [
            'details' => $this->filterDetails([
                $row->species_name ? 'Species: '.$row->species_name : null,
                $row->sex ? 'Sex: '.$row->sex : null,
                $row->age !== null && $row->age !== '' ? 'Age: '.$row->age : null,
                $row->sample_type_name ? 'Sample type: '.$row->sample_type_name : null,
                $row->sampling_site_name ? 'Sampling site: '.$row->sampling_site_name : null,
                $row->date_collected ? 'Date collected: '.Carbon::parse($row->date_collected)->format('Y-m-d') : null,
            ]),
            'variables' => array_filter([
                'animal_species' => $row->species_name ?? null,
                'animal_sex' => $row->sex ?? null,
                'animal_age' => $row->age !== null && $row->age !== '' ? (string) $row->age : null,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @return array{details: array<int, string>, variables: array<string, string>}
     */
    private function buildEnvironmentSampleDescriptor(object $row): array
    {
        return [
            'details' => $this->filterDetails([
                $row->sample_type_name ? 'Sample type: '.$row->sample_type_name : null,
                $row->sampling_site_name ? 'Sampling site: '.$row->sampling_site_name : null,
                $row->date_collected ? 'Date collected: '.Carbon::parse($row->date_collected)->format('Y-m-d') : null,
            ]),
            'variables' => array_filter([
                'environment_sample_type' => $row->sample_type_name ?? null,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @return array{details: array<int, string>, variables: array<string, string>}
     */
    private function buildParasiteSampleDescriptor(object $row): array
    {
        return [
            'details' => $this->filterDetails([
                $row->parasite_species_name ? 'Species: '.$row->parasite_species_name : null,
                $row->stage ? 'Stage: '.$row->stage : null,
                $row->sex ? 'Sex: '.$row->sex : null,
                $row->state ? 'State: '.$row->state : null,
                $row->parasite_sample_type_name ? 'Sample type: '.$row->parasite_sample_type_name : null,
                $row->date_processed ? 'Date processed: '.Carbon::parse($row->date_processed)->format('Y-m-d') : null,
            ]),
            'variables' => array_filter([
                'parasite_species' => $row->parasite_species_name ?? null,
                'parasite_stage' => $row->stage ?? null,
                'parasite_sex' => $row->sex ?? null,
                'parasite_state' => $row->state ?? null,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @return array{details: array<int, string>, variables: array<string, string>}
     */
    private function buildNucleicAcidDescriptor(object $row): array
    {
        return [
            'details' => $this->filterDetails([
                $row->type ? 'Type: '.$row->type : null,
                $row->extraction_protocol_name ? 'Extraction protocol: '.$row->extraction_protocol_name : null,
                $row->date_extracted ? 'Date extracted: '.Carbon::parse($row->date_extracted)->format('Y-m-d') : null,
            ]),
            'variables' => array_filter([
                'nucleic_type' => $row->type ?? null,
                'nucleic_extraction_protocol' => $row->extraction_protocol_name ?? null,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @return array{details: array<int, string>, variables: array<string, string>}
     */
    private function buildCultureDescriptor(object $row): array
    {
        return [
            'details' => $this->filterDetails([
                $row->type ? 'Culture type: '.$row->type : null,
                $row->medium ? 'Medium: '.$row->medium : null,
                $row->date_created ? 'Date created: '.Carbon::parse($row->date_created)->format('Y-m-d') : null,
            ]),
            'variables' => array_filter([
                'culture_type' => $row->type ?? null,
                'culture_medium' => $row->medium ?? null,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @param  array<int, array{type: string, id: int}>  $members
     * @return array{details: array<int, string>, variables: array<string, string>}
     */
    private function buildPoolDescriptor(object $pool, array $members): array
    {
        return [
            'details' => $this->filterDetails([
                $pool->type ? 'Pool type: '.$pool->type : null,
                $pool->nr_pooled !== null ? 'Nr pooled: '.$pool->nr_pooled : null,
                $pool->date_created ? 'Date created: '.Carbon::parse($pool->date_created)->format('Y-m-d') : null,
                ! empty($members) ? 'Members: '.collect($members)->map(fn ($member) => class_basename((string) $member['type']))->unique()->implode(', ') : null,
            ]),
            'variables' => array_filter([
                'pool_nr_pooled' => $pool->nr_pooled !== null ? (string) $pool->nr_pooled : null,
            ], fn ($value) => $value !== null && trim((string) $value) !== ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getParasiteSampleRecord(int $id): ?array
    {
        $row = ParasiteSamples::query()
            ->where('parasite_samples.id', $id)
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->first([
                'parasite_samples.id',
                'parasite_samples.code',
                'parasite_samples.parasites_id',
                'parasite_samples.date_processed',
                'parasites.stage',
                'parasites.sex',
                'parasites.state',
                'parasites.parasites_origin_type',
                'parasites.parasites_origin_id',
                'parasite_species.name_scientific as parasite_species_name',
                'parasite_sample_types.name as parasite_sample_type_name',
            ]);

        if (! $row) {
            return null;
        }

        $descriptor = $this->buildParasiteSampleDescriptor($row);

        return [
            'id' => $row->id,
            'code' => $row->code ?? null,
            'parasites_id' => (int) $row->parasites_id,
            'parasites_origin_type' => $row->parasites_origin_type ? (string) $row->parasites_origin_type : null,
            'parasites_origin_id' => $row->parasites_origin_id ? (int) $row->parasites_origin_id : null,
            'details' => $descriptor['details'],
            'variables' => $descriptor['variables'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getNucleicAcidRecord(int $id): ?array
    {
        $row = NucleicAcids::query()
            ->where('nucleic_acids.id', $id)
            ->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id')
            ->first([
                'nucleic_acids.id',
                'nucleic_acids.code',
                'nucleic_acids.type',
                'nucleic_acids.date_extracted',
                'nucleic_acids.nucleic_content_type',
                'nucleic_acids.nucleic_content_id',
                'protocols.name as extraction_protocol_name',
            ]);
        if (! $row) {
            return null;
        }

        $descriptor = $this->buildNucleicAcidDescriptor($row);

        return [
            'id' => $row->id,
            'code' => $row->code ?? null,
            'nucleic_content_type' => (string) $row->nucleic_content_type,
            'nucleic_content_id' => (int) $row->nucleic_content_id,
            'details' => $descriptor['details'],
            'variables' => $descriptor['variables'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCultureRecord(int $id): ?array
    {
        $row = Cultures::query()->find($id, ['id', 'code', 'type', 'medium', 'date_created', 'cultures_content_type', 'cultures_content_id']);
        if (! $row) {
            return null;
        }

        $descriptor = $this->buildCultureDescriptor($row);

        return [
            'id' => $row->id,
            'code' => $row->code ?? null,
            'cultures_content_type' => (string) $row->cultures_content_type,
            'cultures_content_id' => (int) $row->cultures_content_id,
            'details' => $descriptor['details'],
            'variables' => $descriptor['variables'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getPoolRecord(int $id): ?array
    {
        $pool = Pools::query()->find($id, ['id', 'code', 'nr_pooled', 'type', 'date_created']);
        if (! $pool) {
            return null;
        }

        $members = PoolContents::query()
            ->where('pools_id', $id)
            ->orderBy('id')
            ->get(['samples_type', 'samples_id'])
            ->filter(fn ($row) => ! empty($row->samples_type) && ! empty($row->samples_id))
            ->map(fn ($row) => ['type' => (string) $row->samples_type, 'id' => (int) $row->samples_id])
            ->values()
            ->all();

        $descriptor = $this->buildPoolDescriptor($pool, $members);

        return [
            'id' => $pool->id,
            'code' => $pool->code ?? null,
            'pool_samples' => $members,
            'details' => $descriptor['details'],
            'variables' => $descriptor['variables'],
        ];
    }

    private function lat(array $sample, Collection $samplingSites): ?float
    {
        if (! empty($sample['latitude'])) {
            return (float) $sample['latitude'];
        }

        $siteId = $sample['sampling_sites_id'] ?? null;
        if (! $siteId) {
            return null;
        }

        $site = $samplingSites->get($siteId);

        return $site && $site->latitude ? (float) $site->latitude : null;
    }

    private function lng(array $sample, Collection $samplingSites): ?float
    {
        if (! empty($sample['longitude'])) {
            return (float) $sample['longitude'];
        }

        $siteId = $sample['sampling_sites_id'] ?? null;
        if (! $siteId) {
            return null;
        }

        $site = $samplingSites->get($siteId);

        return $site && $site->longitude ? (float) $site->longitude : null;
    }
}
