<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\SubProject;
use App\Services\PrimarySampleReachability;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ExperimentsDashboard extends PlainComponent
{
    use WithPagination;

    public $experimentTypeFilter = 'all';

    public string $tracePrimaryTypeFilter = 'all';

    public string $tracePrimaryAnimalSpeciesFilter = '';

    public string $tracePrimaryAnimalSexFilter = '';

    public string $tracePrimaryAnimalAgeFilter = '';

    public string $tracePrimaryHumanEthnicityFilter = '';

    public string $tracePrimaryHumanOccupationFilter = '';

    public string $tracePrimaryHumanCountryFilter = '';

    public string $tracePrimaryParasiteSpeciesFilter = '';

    public string $tracePrimaryParasiteStageFilter = '';

    public string $tracePrimaryParasiteSexFilter = '';

    public string $tracePrimaryParasiteStateFilter = '';

    public string $tracePrimaryParasiteSampleTypeFilter = '';

    public string $tracePrimaryCultureTypeFilter = '';

    public string $tracePrimaryCultureMediumFilter = '';

    public ?int $tracePrimaryPoolMinNrPooled = null;

    public ?int $tracePrimaryPoolMaxNrPooled = null;

    public string $traceDeepPrimaryTypeFilter = 'all';

    public string $traceDeepAnimalSpeciesFilter = '';

    public string $traceDeepAnimalSexFilter = '';

    public string $traceDeepAnimalAgeFilter = '';

    public string $traceDeepHumanEthnicityFilter = '';

    public string $traceDeepHumanOccupationFilter = '';

    public string $traceDeepHumanCountryFilter = '';

    public string $animalSpeciesFilter = '';

    public string $animalSexFilter = '';

    public string $parasiteSpeciesFilter = '';

    public string $parasiteStageFilter = '';

    public string $parasiteSexFilter = '';

    public string $parasiteStateFilter = '';

    public string $parasiteSampleTypeFilter = '';

    public string $cultureTypeFilter = '';

    public string $cultureMediumFilter = '';

    public string $nucleicTypeFilter = '';

    public string $poolContentTypeFilter = 'all';

    public ?int $poolMinNrPooledFilter = null;

    public ?int $poolMaxNrPooledFilter = null;

    public string $timelineGranularity = 'monthly';

    public $startDate;

    public $endDate;

    public $protocolFilter;

    public string $techniqueTypeFilter = '';

    public string $techniqueCategoryFilter = '';

    public $pathogenFilter;

    public $outcomeFilter;

    public string $purposeFilter = '';

    public string $subProjectFilter = '';

    public string $samplingSiteFilter = '';

    public function getProjectId()
    {
        return session('selected_project_id');
    }

    public function isGuestMode(): bool
    {
        return session('selected_project_id') === null;
    }

    public function canEdit()
    {
        if ($this->isGuestMode()) {
            return false;
        }

        $user = Auth::user();
        if (! $user || ! $user->people) {
            return false;
        }

        $project = $user->people->projects()
            ->where('projects.id', session('selected_project_id'))
            ->withPivot('role', 'date_joined', 'permission')
            ->first();

        if (! $project || ! $project->pivot) {
            return false;
        }

        return $project->pivot->permission !== 'viewer';
    }

    public function updated($propertyName)
    {
        // Avoid re-rendering / re-charting when only paginating tables.
        if (str_starts_with((string) $propertyName, 'paginators.')) {
            return;
        }

        $this->dispatch('filtersUpdated', data: $this->filteredData(realtimeOnly: true));
    }

    public function updatedExperimentTypeFilter(): void
    {
        // Avoid stale trace filters pinning dataset to 0 when switching experiment content types.
        $this->reset([
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryParasiteStageFilter',
            'tracePrimaryParasiteSexFilter',
            'tracePrimaryParasiteStateFilter',
            'tracePrimaryParasiteSampleTypeFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
            'animalSpeciesFilter',
            'animalSexFilter',
            'parasiteSpeciesFilter',
            'parasiteStageFilter',
            'parasiteSexFilter',
            'parasiteStateFilter',
            'parasiteSampleTypeFilter',
            'cultureTypeFilter',
            'cultureMediumFilter',
            'nucleicTypeFilter',
            'poolContentTypeFilter',
            'poolMinNrPooledFilter',
            'poolMaxNrPooledFilter',
            'samplingSiteFilter',
        ]);
    }

    public function resetFilters()
    {
        $this->reset([
            'experimentTypeFilter',
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryParasiteStageFilter',
            'tracePrimaryParasiteSexFilter',
            'tracePrimaryParasiteStateFilter',
            'tracePrimaryParasiteSampleTypeFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
            'animalSpeciesFilter',
            'animalSexFilter',
            'parasiteSpeciesFilter',
            'parasiteStageFilter',
            'parasiteSexFilter',
            'parasiteStateFilter',
            'parasiteSampleTypeFilter',
            'cultureTypeFilter',
            'cultureMediumFilter',
            'nucleicTypeFilter',
            'poolContentTypeFilter',
            'poolMinNrPooledFilter',
            'poolMaxNrPooledFilter',
            'timelineGranularity',
            'startDate',
            'endDate',
            'protocolFilter',
            'techniqueTypeFilter',
            'techniqueCategoryFilter',
            'pathogenFilter',
            'outcomeFilter',
            'purposeFilter',
            'subProjectFilter',
            'samplingSiteFilter',
        ]);
        $this->resetPage();
        $this->resetPage('all_experiments_page');

        $this->dispatch('filtersUpdated', data: $this->filteredData(realtimeOnly: true));
    }

    /**
     * Build the base filtered query (no eager loading by default).
     */
    /**
     * Map of experiment content morph classes to their underlying table.
     *
     * @return array<class-string, string>
     */
    private function sampleCodeTableMap(): array
    {
        return [
            HumanSamples::class => 'human_samples',
            AnimalSamples::class => 'animal_samples',
            EnvironmentSamples::class => 'environment_samples',
            ParasiteSamples::class => 'parasite_samples',
            NucleicAcids::class => 'nucleic_acids',
            Cultures::class => 'cultures',
            Pools::class => 'pools',
        ];
    }

    /**
     * Build the correlated SQL expression resolving an experiment's sample code
     * across all polymorphic content types.
     *
     * @return array{0: string, 1: array<int, string>} The SQL and its bindings.
     */
    private function sampleCodeExpression(): array
    {
        $cases = [];
        $bindings = [];

        foreach ($this->sampleCodeTableMap() as $class => $table) {
            foreach ($this->typeVariants($class) as $variant) {
                $cases[] = "WHEN ? THEN (SELECT {$table}.code FROM {$table} WHERE {$table}.id = experiments.experiments_content_id)";
                $bindings[] = $variant;
            }
        }

        $sql = 'CASE experiments.experiments_content_type '.implode(' ', $cases).' ELSE NULL END';

        return [$sql, $bindings];
    }

    /**
     * Correlated SQL expression resolving the alias code from any tube linked to
     * the experiment's sample (alias lives on tubes, not on samples).
     */
    private function sampleAliasExpression(): string
    {
        return "(SELECT t.alias_code FROM tubes t WHERE t.tubes_content_id = experiments.experiments_content_id AND t.tubes_content_type = experiments.experiments_content_type AND t.alias_code IS NOT NULL AND t.alias_code <> '' LIMIT 1)";
    }

    /**
     * Add resolved sample code / alias columns to the given experiments query so
     * the dataset modal can display (and client-side filter/sort) them. The alias
     * is sourced from any tube linked to the sample.
     *
     * @param  Builder  $query
     * @return Builder
     */
    private function applyDatasetSampleColumns($query)
    {
        [$codeSql, $codeBindings] = $this->sampleCodeExpression();
        $aliasSql = $this->sampleAliasExpression();

        return $query->select('experiments.*')
            ->selectRaw("({$codeSql}) as sample_code", $codeBindings)
            ->selectRaw("({$aliasSql}) as sample_alias")
            ->orderBy('experiments.created_at', 'desc');
    }

    /**
     * Compute prevalence using the "screening with confirmation" interpretation.
     *
     * Only samples (per pathogen) that have at least one screening AND at least
     * one confirmation outcome are considered. A sample is a "confirmed positive"
     * when it has at least one positive screening AND at least one positive
     * confirmation result; otherwise it is a "confirmed negative". Prevalence is
     * reported separately for each screening test (protocol), alongside the
     * confirmation test(s) used.
     *
     * @param  Builder  $base
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    private function screeningConfirmationPrevalence($base): array
    {
        $positiveOutcomes = ['Positive', 'Strong positive'];

        $rows = (clone $base)
            ->leftJoin('pathogens', 'experiments.pathogens_id', '=', 'pathogens.id')
            ->leftJoin('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->whereIn('experiments.purpose', ['screening', 'confirmation'])
            ->get([
                'experiments.id as id',
                'experiments.experiments_content_id as content_id',
                'experiments.experiments_content_type as content_type',
                DB::raw("COALESCE(pathogens.species, 'Unknown') as pathogen"),
                DB::raw("COALESCE(protocols.name, 'Unknown') as protocol"),
                'experiments.purpose as purpose',
                'experiments.outcome_discrete as outcome',
            ])
            ->unique('id');

        // Aggregate per sample + pathogen.
        $samples = [];
        foreach ($rows as $row) {
            $key = $row->content_type.'#'.$row->content_id.'|'.$row->pathogen;
            if (! isset($samples[$key])) {
                $samples[$key] = [
                    'pathogen' => (string) $row->pathogen,
                    'screening_protocols' => [],
                    'confirmation_protocols' => [],
                    'screening_positive' => false,
                    'confirmation_positive' => false,
                ];
            }

            $purpose = $row->purpose instanceof \BackedEnum ? $row->purpose->value : (string) $row->purpose;
            $isPositive = in_array($row->outcome, $positiveOutcomes, true);

            if ($purpose === 'screening') {
                $samples[$key]['screening_protocols'][(string) $row->protocol] = true;
                $samples[$key]['screening_positive'] = $samples[$key]['screening_positive'] || $isPositive;
            } elseif ($purpose === 'confirmation') {
                $samples[$key]['confirmation_protocols'][(string) $row->protocol] = true;
                $samples[$key]['confirmation_positive'] = $samples[$key]['confirmation_positive'] || $isPositive;
            }
        }

        $aggregated = [];
        $summaryPositive = 0;
        $summaryTotal = 0;

        foreach ($samples as $sample) {
            if (empty($sample['screening_protocols']) || empty($sample['confirmation_protocols'])) {
                continue;
            }

            $confirmedPositive = $sample['screening_positive'] && $sample['confirmation_positive'];

            $summaryTotal++;
            if ($confirmedPositive) {
                $summaryPositive++;
            }

            foreach (array_keys($sample['screening_protocols']) as $screeningTest) {
                $groupKey = $sample['pathogen'].'|'.$screeningTest;
                if (! isset($aggregated[$groupKey])) {
                    $aggregated[$groupKey] = [
                        'pathogen' => $sample['pathogen'],
                        'screening_test' => $screeningTest,
                        'confirmation_tests' => [],
                        'positive_count' => 0,
                        'total_count' => 0,
                    ];
                }

                $aggregated[$groupKey]['total_count']++;
                if ($confirmedPositive) {
                    $aggregated[$groupKey]['positive_count']++;
                }
                foreach (array_keys($sample['confirmation_protocols']) as $confirmationTest) {
                    $aggregated[$groupKey]['confirmation_tests'][$confirmationTest] = true;
                }
            }
        }

        $resultRows = collect($aggregated)
            ->map(function (array $group): array {
                $total = $group['total_count'];

                return [
                    'pathogen' => $group['pathogen'],
                    'screening_test' => $group['screening_test'],
                    'confirmation_tests' => implode(', ', array_keys($group['confirmation_tests'])),
                    'positive_count' => $group['positive_count'],
                    'total_count' => $total,
                    'prevalence' => $total > 0 ? round(($group['positive_count'] / $total) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('total_count')
            ->values()
            ->all();

        return [
            'rows' => $resultRows,
            'summary' => [
                'positive' => $summaryPositive,
                'negative' => $summaryTotal - $summaryPositive,
                'total' => $summaryTotal,
                'percentage' => $summaryTotal > 0 ? round(($summaryPositive / $summaryTotal) * 100, 1) : 0.0,
            ],
        ];
    }

    private function baseFilteredQuery(array $except = [])
    {
        $query = Experiments::query();

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->where('is_private', false);
        } else {
            $query->where('experiments.projects_id', session('selected_project_id'));
        }

        // Apply filters
        $this->applyFilters($query, $except);

        return $query;
    }

    /**
     * Apply all active filters to the query
     */
    private function applyFilters($query, array $except = [])
    {
        // Apply experiment type filter
        if (! in_array('experimentTypeFilter', $except, true) && $this->experimentTypeFilter !== '' && $this->experimentTypeFilter !== 'all') {
            $query->whereIn('experiments_content_type', $this->typeVariants($this->normalizeType((string) $this->experimentTypeFilter)));
        }
        if (! in_array('subProjectFilter', $except, true) && $this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'experiments.id')
                    ->where('sub_project_assignments.assignable_type', Experiments::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }

        $effectiveTracePrimaryType = in_array('tracePrimaryTypeFilter', $except, true) ? '' : $this->tracePrimaryTypeFilter;
        $effectiveDeepPrimaryType = in_array('traceDeepPrimaryTypeFilter', $except, true) ? '' : $this->traceDeepPrimaryTypeFilter;

        if (
            in_array($this->normalizedBasename((string) $this->experimentTypeFilter), ['ParasiteSamples', 'NucleicAcids', 'Cultures', 'Pools'], true)
            && (($effectiveTracePrimaryType !== '' && $effectiveTracePrimaryType !== 'all')
                || $this->tracePrimaryAnimalSpeciesFilter !== ''
                || $this->tracePrimaryAnimalSexFilter !== ''
                || $this->tracePrimaryAnimalAgeFilter !== ''
                || $this->tracePrimaryHumanEthnicityFilter !== ''
                || $this->tracePrimaryHumanOccupationFilter !== ''
                || $this->tracePrimaryHumanCountryFilter !== ''
                || $this->tracePrimaryParasiteSpeciesFilter !== ''
                || $this->tracePrimaryParasiteStageFilter !== ''
                || $this->tracePrimaryParasiteSexFilter !== ''
                || $this->tracePrimaryParasiteStateFilter !== ''
                || $this->tracePrimaryParasiteSampleTypeFilter !== ''
                || $this->tracePrimaryCultureTypeFilter !== ''
                || $this->tracePrimaryCultureMediumFilter !== ''
                || $this->tracePrimaryPoolMinNrPooled !== null
                || $this->tracePrimaryPoolMaxNrPooled !== null
                || ($effectiveDeepPrimaryType !== '' && $effectiveDeepPrimaryType !== 'all')
                || $this->traceDeepAnimalSpeciesFilter !== ''
                || $this->traceDeepAnimalSexFilter !== ''
                || $this->traceDeepAnimalAgeFilter !== ''
                || $this->traceDeepHumanEthnicityFilter !== ''
                || $this->traceDeepHumanOccupationFilter !== ''
                || $this->traceDeepHumanCountryFilter !== '')
        ) {
            $target = $this->normalizedBasename((string) $this->experimentTypeFilter);

            $hasDeepTrace = in_array($effectiveTracePrimaryType, ['parasite', 'culture', 'nucleic', 'pool'], true)
                && (($effectiveDeepPrimaryType !== '' && $effectiveDeepPrimaryType !== 'all')
                    || $this->traceDeepAnimalSpeciesFilter !== ''
                    || $this->traceDeepAnimalSexFilter !== ''
                    || $this->traceDeepAnimalAgeFilter !== ''
                    || $this->traceDeepHumanEthnicityFilter !== ''
                    || $this->traceDeepHumanOccupationFilter !== ''
                    || $this->traceDeepHumanCountryFilter !== '');

            // For NucleicAcids experiments we can filter trace "upstream type" directly on
            // nucleic_acids.nucleic_content_type/nucleic_content_id. This avoids mismatches
            // caused by cross-project references and does not depend on precomputing seeds.
            if ($target === 'NucleicAcids') {
                $upstreamType = match ($effectiveTracePrimaryType) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'culture' => Cultures::class,
                    'pool' => Pools::class,
                    default => null,
                };

                if ($upstreamType) {
                    $query->whereExists(function ($sub) use ($upstreamType, $except) {
                        $sub->select(DB::raw(1))
                            ->from('nucleic_acids')
                            ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                            ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants($upstreamType));

                        if ($upstreamType === AnimalSamples::class) {
                            $sub->join('animal_samples', 'nucleic_acids.nucleic_content_id', '=', 'animal_samples.id')
                                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

                            if (! in_array('tracePrimaryAnimalSpeciesFilter', $except, true) && $this->tracePrimaryAnimalSpeciesFilter !== '') {
                                $sub->where('animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
                            }
                            if (! in_array('tracePrimaryAnimalSexFilter', $except, true) && $this->tracePrimaryAnimalSexFilter !== '') {
                                $sub->where('animals.sex', $this->tracePrimaryAnimalSexFilter);
                            }
                            if (! in_array('tracePrimaryAnimalAgeFilter', $except, true) && $this->tracePrimaryAnimalAgeFilter !== '') {
                                $sub->where('animals.age', $this->tracePrimaryAnimalAgeFilter);
                            }
                        }

                        if ($upstreamType === HumanSamples::class) {
                            $sub->join('human_samples', 'nucleic_acids.nucleic_content_id', '=', 'human_samples.id')
                                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

                            if (! in_array('tracePrimaryHumanEthnicityFilter', $except, true) && $this->tracePrimaryHumanEthnicityFilter !== '') {
                                $sub->where('humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
                            }
                            if (! in_array('tracePrimaryHumanOccupationFilter', $except, true) && $this->tracePrimaryHumanOccupationFilter !== '') {
                                $sub->where('humans.occupation', $this->tracePrimaryHumanOccupationFilter);
                            }
                            if (! in_array('tracePrimaryHumanCountryFilter', $except, true) && $this->tracePrimaryHumanCountryFilter !== '') {
                                $sub->where('countries.name', $this->tracePrimaryHumanCountryFilter);
                            }
                        }

                        if ($upstreamType === ParasiteSamples::class) {
                            $sub->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                                ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id');

                            if ($this->tracePrimaryParasiteSpeciesFilter !== '') {
                                $sub->where('parasite_species.name_scientific', $this->tracePrimaryParasiteSpeciesFilter);
                            }
                            if ($this->tracePrimaryParasiteStageFilter !== '') {
                                $sub->where('parasites.stage', $this->tracePrimaryParasiteStageFilter);
                            }
                            if ($this->tracePrimaryParasiteSexFilter !== '') {
                                $sub->where('parasites.sex', $this->tracePrimaryParasiteSexFilter);
                            }
                            if ($this->tracePrimaryParasiteStateFilter !== '') {
                                $sub->where('parasites.state', $this->tracePrimaryParasiteStateFilter);
                            }
                            if ($this->tracePrimaryParasiteSampleTypeFilter !== '') {
                                $sub->where('parasite_sample_types.name', $this->tracePrimaryParasiteSampleTypeFilter);
                            }

                            // Deep trace: parasite -> primary (human/animal/environment)
                            if (
                                ($this->traceDeepPrimaryTypeFilter !== '' && $this->traceDeepPrimaryTypeFilter !== 'all')
                                || $this->traceDeepAnimalSpeciesFilter !== ''
                                || $this->traceDeepAnimalSexFilter !== ''
                                || $this->traceDeepAnimalAgeFilter !== ''
                                || $this->traceDeepHumanEthnicityFilter !== ''
                                || $this->traceDeepHumanOccupationFilter !== ''
                                || $this->traceDeepHumanCountryFilter !== ''
                            ) {
                                $primaryType = match ($this->traceDeepPrimaryTypeFilter) {
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

                                        if ($this->traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($this->traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $this->traceDeepAnimalSexFilter);
                                        }
                                        if ($this->traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $this->traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($this->traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                                        }
                                        if ($this->traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $this->traceDeepHumanOccupationFilter);
                                        }
                                        if ($this->traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $this->traceDeepHumanCountryFilter);
                                        }
                                    }
                                }
                            }
                        }

                        if ($upstreamType === Cultures::class) {
                            $sub->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id');

                            if ($this->tracePrimaryCultureTypeFilter !== '') {
                                $sub->where('cultures.type', $this->tracePrimaryCultureTypeFilter);
                            }
                            if ($this->tracePrimaryCultureMediumFilter !== '') {
                                $sub->where('cultures.medium', $this->tracePrimaryCultureMediumFilter);
                            }
                        }

                        if ($upstreamType === Pools::class) {
                            $sub->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id');

                            if ($this->tracePrimaryPoolMinNrPooled !== null) {
                                $sub->where('pools.nr_pooled', '>=', $this->tracePrimaryPoolMinNrPooled);
                            }
                            if ($this->tracePrimaryPoolMaxNrPooled !== null) {
                                $sub->where('pools.nr_pooled', '<=', $this->tracePrimaryPoolMaxNrPooled);
                            }
                        }
                    });
                }

                // Deep trace for derived upstreams (culture/pool): apply as an additional
                // constraint using separate EXISTS subqueries to keep joins clean.
                if ($hasDeepTrace && in_array($this->tracePrimaryTypeFilter, ['culture', 'pool'], true)) {
                    $primaryType = match ($this->traceDeepPrimaryTypeFilter) {
                        'human' => HumanSamples::class,
                        'animal' => AnimalSamples::class,
                        'environment' => EnvironmentSamples::class,
                        default => null,
                    };

                    if ($primaryType) {
                        if ($this->tracePrimaryTypeFilter === 'culture') {
                            $query->where(function ($w) use ($primaryType) {
                                // Direct: cultures -> primary
                                $w->whereExists(function ($sub) use ($primaryType) {
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

                                        if ($this->traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($this->traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $this->traceDeepAnimalSexFilter);
                                        }
                                        if ($this->traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $this->traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'cultures.cultures_content_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($this->traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                                        }
                                        if ($this->traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $this->traceDeepHumanOccupationFilter);
                                        }
                                        if ($this->traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $this->traceDeepHumanCountryFilter);
                                        }
                                    }
                                })
                                    // Via parasite: cultures -> parasite_samples -> origin primary
                                    ->orWhereExists(function ($sub) use ($primaryType) {
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

                                            if ($this->traceDeepAnimalSpeciesFilter !== '') {
                                                $sub->where('deep_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                                            }
                                            if ($this->traceDeepAnimalSexFilter !== '') {
                                                $sub->where('deep_animals.sex', $this->traceDeepAnimalSexFilter);
                                            }
                                            if ($this->traceDeepAnimalAgeFilter !== '') {
                                                $sub->where('deep_animals.age', $this->traceDeepAnimalAgeFilter);
                                            }
                                        }

                                        if ($primaryType === HumanSamples::class) {
                                            $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                                ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                                ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                            if ($this->traceDeepHumanEthnicityFilter !== '') {
                                                $sub->where('deep_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                                            }
                                            if ($this->traceDeepHumanOccupationFilter !== '') {
                                                $sub->where('deep_humans.occupation', $this->traceDeepHumanOccupationFilter);
                                            }
                                            if ($this->traceDeepHumanCountryFilter !== '') {
                                                $sub->where('deep_countries.name', $this->traceDeepHumanCountryFilter);
                                            }
                                        }
                                    });
                            });
                        }

                        if ($this->tracePrimaryTypeFilter === 'pool') {
                            $query->where(function ($w) use ($primaryType) {
                                // Direct: pools -> pool_contents -> primary
                                $w->whereExists(function ($sub) use ($primaryType) {
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

                                        if ($this->traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($this->traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $this->traceDeepAnimalSexFilter);
                                        }
                                        if ($this->traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $this->traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'pool_contents.samples_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($this->traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                                        }
                                        if ($this->traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $this->traceDeepHumanOccupationFilter);
                                        }
                                        if ($this->traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $this->traceDeepHumanCountryFilter);
                                        }
                                    }
                                })
                                    // Via parasite: pools -> pool_contents (parasite_samples) -> origin primary
                                    ->orWhereExists(function ($sub) use ($primaryType) {
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

                                            if ($this->traceDeepAnimalSpeciesFilter !== '') {
                                                $sub->where('deep_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                                            }
                                            if ($this->traceDeepAnimalSexFilter !== '') {
                                                $sub->where('deep_animals.sex', $this->traceDeepAnimalSexFilter);
                                            }
                                            if ($this->traceDeepAnimalAgeFilter !== '') {
                                                $sub->where('deep_animals.age', $this->traceDeepAnimalAgeFilter);
                                            }
                                        }

                                        if ($primaryType === HumanSamples::class) {
                                            $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                                ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                                ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                            if ($this->traceDeepHumanEthnicityFilter !== '') {
                                                $sub->where('deep_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                                            }
                                            if ($this->traceDeepHumanOccupationFilter !== '') {
                                                $sub->where('deep_humans.occupation', $this->traceDeepHumanOccupationFilter);
                                            }
                                            if ($this->traceDeepHumanCountryFilter !== '') {
                                                $sub->where('deep_countries.name', $this->traceDeepHumanCountryFilter);
                                            }
                                        }
                                    });
                            });
                        }
                    }
                }
            }

            if ($target === 'Cultures') {
                $upstreamType = match ($this->tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'nucleic' => NucleicAcids::class,
                    'pool' => Pools::class,
                    default => null,
                };

                if ($upstreamType) {
                    $query->whereExists(function ($sub) use ($upstreamType, $hasDeepTrace) {
                        $sub->select(DB::raw(1))
                            ->from('cultures')
                            ->whereColumn('cultures.id', 'experiments.experiments_content_id')
                            ->whereIn('cultures.cultures_content_type', $this->typeVariants($upstreamType));

                        if ($upstreamType === AnimalSamples::class) {
                            $sub->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id')
                                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

                            if ($this->tracePrimaryAnimalSpeciesFilter !== '') {
                                $sub->where('animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
                            }
                            if ($this->tracePrimaryAnimalSexFilter !== '') {
                                $sub->where('animals.sex', $this->tracePrimaryAnimalSexFilter);
                            }
                            if ($this->tracePrimaryAnimalAgeFilter !== '') {
                                $sub->where('animals.age', $this->tracePrimaryAnimalAgeFilter);
                            }
                        }

                        if ($upstreamType === HumanSamples::class) {
                            $sub->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id')
                                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

                            if ($this->tracePrimaryHumanEthnicityFilter !== '') {
                                $sub->where('humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
                            }
                            if ($this->tracePrimaryHumanOccupationFilter !== '') {
                                $sub->where('humans.occupation', $this->tracePrimaryHumanOccupationFilter);
                            }
                            if ($this->tracePrimaryHumanCountryFilter !== '') {
                                $sub->where('countries.name', $this->tracePrimaryHumanCountryFilter);
                            }
                        }

                        if ($upstreamType === ParasiteSamples::class) {
                            $sub->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                                ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id');

                            if ($this->tracePrimaryParasiteSpeciesFilter !== '') {
                                $sub->where('parasite_species.name_scientific', $this->tracePrimaryParasiteSpeciesFilter);
                            }
                            if ($this->tracePrimaryParasiteStageFilter !== '') {
                                $sub->where('parasites.stage', $this->tracePrimaryParasiteStageFilter);
                            }
                            if ($this->tracePrimaryParasiteSexFilter !== '') {
                                $sub->where('parasites.sex', $this->tracePrimaryParasiteSexFilter);
                            }
                            if ($this->tracePrimaryParasiteStateFilter !== '') {
                                $sub->where('parasites.state', $this->tracePrimaryParasiteStateFilter);
                            }
                            if ($this->tracePrimaryParasiteSampleTypeFilter !== '') {
                                $sub->where('parasite_sample_types.name', $this->tracePrimaryParasiteSampleTypeFilter);
                            }

                            // Deep trace: parasite -> primary (human/animal/environment)
                            if ($hasDeepTrace) {
                                $primaryType = match ($this->traceDeepPrimaryTypeFilter) {
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

                                        if ($this->traceDeepAnimalSpeciesFilter !== '') {
                                            $sub->where('deep_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                                        }
                                        if ($this->traceDeepAnimalSexFilter !== '') {
                                            $sub->where('deep_animals.sex', $this->traceDeepAnimalSexFilter);
                                        }
                                        if ($this->traceDeepAnimalAgeFilter !== '') {
                                            $sub->where('deep_animals.age', $this->traceDeepAnimalAgeFilter);
                                        }
                                    }

                                    if ($primaryType === HumanSamples::class) {
                                        $sub->join('human_samples as deep_human_samples', 'parasites.parasites_origin_id', '=', 'deep_human_samples.id')
                                            ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                            ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                        if ($this->traceDeepHumanEthnicityFilter !== '') {
                                            $sub->where('deep_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                                        }
                                        if ($this->traceDeepHumanOccupationFilter !== '') {
                                            $sub->where('deep_humans.occupation', $this->traceDeepHumanOccupationFilter);
                                        }
                                        if ($this->traceDeepHumanCountryFilter !== '') {
                                            $sub->where('deep_countries.name', $this->traceDeepHumanCountryFilter);
                                        }
                                    }
                                }
                            }
                        }

                        if ($upstreamType === NucleicAcids::class && $hasDeepTrace) {
                            $primaryType = match ($this->traceDeepPrimaryTypeFilter) {
                                'human' => HumanSamples::class,
                                'animal' => AnimalSamples::class,
                                'environment' => EnvironmentSamples::class,
                                default => null,
                            };

                            if ($primaryType) {
                                $sub->join('nucleic_acids', 'cultures.cultures_content_id', '=', 'nucleic_acids.id')
                                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants($primaryType));

                                if ($primaryType === AnimalSamples::class) {
                                    $sub->join('animal_samples as deep_animal_samples', 'nucleic_acids.nucleic_content_id', '=', 'deep_animal_samples.id')
                                        ->join('animals as deep_animals', 'deep_animal_samples.animals_id', '=', 'deep_animals.id')
                                        ->leftJoin('animal_species as deep_animal_species', 'deep_animals.animal_species_id', '=', 'deep_animal_species.id');

                                    if ($this->traceDeepAnimalSpeciesFilter !== '') {
                                        $sub->where('deep_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                                    }
                                    if ($this->traceDeepAnimalSexFilter !== '') {
                                        $sub->where('deep_animals.sex', $this->traceDeepAnimalSexFilter);
                                    }
                                    if ($this->traceDeepAnimalAgeFilter !== '') {
                                        $sub->where('deep_animals.age', $this->traceDeepAnimalAgeFilter);
                                    }
                                }

                                if ($primaryType === HumanSamples::class) {
                                    $sub->join('human_samples as deep_human_samples', 'nucleic_acids.nucleic_content_id', '=', 'deep_human_samples.id')
                                        ->leftJoin('humans as deep_humans', 'deep_human_samples.humans_id', '=', 'deep_humans.id')
                                        ->leftJoin('countries as deep_countries', 'deep_humans.countries_id', '=', 'deep_countries.id');

                                    if ($this->traceDeepHumanEthnicityFilter !== '') {
                                        $sub->where('deep_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                                    }
                                    if ($this->traceDeepHumanOccupationFilter !== '') {
                                        $sub->where('deep_humans.occupation', $this->traceDeepHumanOccupationFilter);
                                    }
                                    if ($this->traceDeepHumanCountryFilter !== '') {
                                        $sub->where('deep_countries.name', $this->traceDeepHumanCountryFilter);
                                    }
                                }
                            }
                        }
                    });
                }
            }

            if ($target === 'Pools') {
                $primaryType = match ($this->tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                };

                // For Pools experiments, trace-to-primary must follow the pool_contents graph
                // (pools can contain derived types, not just primary samples). Use the shared
                // reachability service so filters actually work.
                if ($primaryType) {
                    $seedIds = $this->seedIdsForTracing($primaryType, $except);
                    $reachability = new PrimarySampleReachability;
                    $poolIds = $reachability->poolIdsFromPrimary($primaryType, $seedIds, $this->getProjectId(), $this->isGuestMode(), maxDepth: 10);

                    $query->whereIn('experiments.experiments_content_id', $poolIds);
                } else {
                    $upstreamType = match ($this->tracePrimaryTypeFilter) {
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

                if (! in_array('tracePrimaryPoolMinNrPooled', $except, true) && $this->tracePrimaryPoolMinNrPooled !== null) {
                    $query->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pools')
                            ->whereColumn('pools.id', 'experiments.experiments_content_id')
                            ->where('pools.nr_pooled', '>=', $this->tracePrimaryPoolMinNrPooled);
                    });
                }

                if (! in_array('tracePrimaryPoolMaxNrPooled', $except, true) && $this->tracePrimaryPoolMaxNrPooled !== null) {
                    $query->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pools')
                            ->whereColumn('pools.id', 'experiments.experiments_content_id')
                            ->where('pools.nr_pooled', '<=', $this->tracePrimaryPoolMaxNrPooled);
                    });
                }
            }

            if ($target === 'ParasiteSamples') {
                $originType = match ($this->tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                };

                if ($originType) {
                    $query->whereExists(function ($sub) use ($originType) {
                        $sub->select(DB::raw(1))
                            ->from('parasite_samples')
                            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                            ->whereColumn('parasite_samples.id', 'experiments.experiments_content_id')
                            ->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));

                        if ($originType === AnimalSamples::class) {
                            $sub->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id')
                                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

                            if ($this->tracePrimaryAnimalSpeciesFilter !== '') {
                                $sub->where('animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
                            }
                            if ($this->tracePrimaryAnimalSexFilter !== '') {
                                $sub->where('animals.sex', $this->tracePrimaryAnimalSexFilter);
                            }
                            if ($this->tracePrimaryAnimalAgeFilter !== '') {
                                $sub->where('animals.age', $this->tracePrimaryAnimalAgeFilter);
                            }
                        }

                        if ($originType === HumanSamples::class) {
                            $sub->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id')
                                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

                            if ($this->tracePrimaryHumanEthnicityFilter !== '') {
                                $sub->where('humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
                            }
                            if ($this->tracePrimaryHumanOccupationFilter !== '') {
                                $sub->where('humans.occupation', $this->tracePrimaryHumanOccupationFilter);
                            }
                            if ($this->tracePrimaryHumanCountryFilter !== '') {
                                $sub->where('countries.name', $this->tracePrimaryHumanCountryFilter);
                            }
                        }
                    });
                }
            }
        }

        $this->applySamplingSiteFilter($query, $except);

        // Apply date range filter
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_tested', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_tested', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_tested', '<=', $this->endDate);
        }

        // Apply protocol filter
        if (! in_array('protocolFilter', $except, true) && $this->protocolFilter) {
            $query->whereHas('protocols', function ($q) {
                $q->where('name', $this->protocolFilter);
            });
        }

        if (! in_array('techniqueTypeFilter', $except, true) && $this->techniqueTypeFilter !== '') {
            $query->whereHas('protocols.techniques', function ($q) {
                $q->where('name', $this->techniqueTypeFilter);
            });
        }

        if (! in_array('techniqueCategoryFilter', $except, true) && $this->techniqueCategoryFilter !== '') {
            $query->whereHas('protocols.techniques', function ($q) {
                $q->where('type', $this->techniqueCategoryFilter);
            });
        }

        // Apply pathogen filter
        if (! in_array('pathogenFilter', $except, true) && $this->pathogenFilter) {
            $query->whereHas('pathogens', function ($q) {
                $q->where('species', $this->pathogenFilter);
            });
        }

        // Apply outcome filter
        if (! in_array('outcomeFilter', $except, true) && $this->outcomeFilter) {
            $query->where('outcome_discrete', $this->outcomeFilter);
        }

        // Apply test purpose filter
        if (! in_array('purposeFilter', $except, true) && $this->purposeFilter !== '') {
            $this->applyPurposeFilter($query, $this->purposeFilter);
        }

        // Content-specific filters (primary samples)
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) === 'AnimalSamples') {
            if (! in_array('animalSpeciesFilter', $except, true) && $this->animalSpeciesFilter !== '') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('animal_samples')
                        ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                        ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                        ->whereColumn('animal_samples.id', 'experiments.experiments_content_id')
                        ->where('animal_species.name_common', $this->animalSpeciesFilter);
                });
            }

            if (! in_array('animalSexFilter', $except, true) && $this->animalSexFilter !== '') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('animal_samples')
                        ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                        ->whereColumn('animal_samples.id', 'experiments.experiments_content_id')
                        ->where('animals.sex', $this->animalSexFilter);
                });
            }
        }

        if ($this->normalizedBasename((string) $this->experimentTypeFilter) === 'ParasiteSamples') {
            if (
                (! in_array('parasiteSpeciesFilter', $except, true) && $this->parasiteSpeciesFilter !== '')
                || (! in_array('parasiteStageFilter', $except, true) && $this->parasiteStageFilter !== '')
                || (! in_array('parasiteSexFilter', $except, true) && $this->parasiteSexFilter !== '')
                || (! in_array('parasiteStateFilter', $except, true) && $this->parasiteStateFilter !== '')
                || (! in_array('parasiteSampleTypeFilter', $except, true) && $this->parasiteSampleTypeFilter !== '')
            ) {
                $query->whereExists(function ($sub) use ($except) {
                    $sub->select(DB::raw(1))
                        ->from('parasite_samples')
                        ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                        ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                        ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
                        ->whereColumn('parasite_samples.id', 'experiments.experiments_content_id');

                    if (! in_array('parasiteSpeciesFilter', $except, true) && $this->parasiteSpeciesFilter !== '') {
                        $sub->where('parasite_species.name_scientific', $this->parasiteSpeciesFilter);
                    }
                    if (! in_array('parasiteStageFilter', $except, true) && $this->parasiteStageFilter !== '') {
                        $sub->where('parasites.stage', $this->parasiteStageFilter);
                    }
                    if (! in_array('parasiteSexFilter', $except, true) && $this->parasiteSexFilter !== '') {
                        $sub->where('parasites.sex', $this->parasiteSexFilter);
                    }
                    if (! in_array('parasiteStateFilter', $except, true) && $this->parasiteStateFilter !== '') {
                        $sub->where('parasites.state', $this->parasiteStateFilter);
                    }
                    if (! in_array('parasiteSampleTypeFilter', $except, true) && $this->parasiteSampleTypeFilter !== '') {
                        $sub->where('parasite_sample_types.name', $this->parasiteSampleTypeFilter);
                    }
                });
            }
        }

        if ($this->normalizedBasename((string) $this->experimentTypeFilter) === 'Cultures') {
            if (
                (! in_array('cultureTypeFilter', $except, true) && $this->cultureTypeFilter !== '')
                || (! in_array('cultureMediumFilter', $except, true) && $this->cultureMediumFilter !== '')
            ) {
                $query->whereExists(function ($sub) use ($except) {
                    $sub->select(DB::raw(1))
                        ->from('cultures')
                        ->whereColumn('cultures.id', 'experiments.experiments_content_id');

                    if (! in_array('cultureTypeFilter', $except, true) && $this->cultureTypeFilter !== '') {
                        $sub->where('cultures.type', $this->cultureTypeFilter);
                    }
                    if (! in_array('cultureMediumFilter', $except, true) && $this->cultureMediumFilter !== '') {
                        $sub->where('cultures.medium', $this->cultureMediumFilter);
                    }
                });
            }
        }

        if ($this->normalizedBasename((string) $this->experimentTypeFilter) === 'NucleicAcids') {
            if (! in_array('nucleicTypeFilter', $except, true) && $this->nucleicTypeFilter !== '') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('nucleic_acids')
                        ->whereColumn('nucleic_acids.id', 'experiments.experiments_content_id')
                        ->where('nucleic_acids.type', $this->nucleicTypeFilter);
                });
            }
        }

        if ($this->normalizedBasename((string) $this->experimentTypeFilter) === 'Pools') {
            if (
                (! in_array('poolMinNrPooledFilter', $except, true) && $this->poolMinNrPooledFilter !== null)
                || (! in_array('poolMaxNrPooledFilter', $except, true) && $this->poolMaxNrPooledFilter !== null)
                || (! in_array('poolContentTypeFilter', $except, true) && $this->poolContentTypeFilter !== '' && $this->poolContentTypeFilter !== 'all')
            ) {
                $query->whereExists(function ($sub) use ($except) {
                    $sub->select(DB::raw(1))
                        ->from('pools')
                        ->whereColumn('pools.id', 'experiments.experiments_content_id');

                    if (! in_array('poolMinNrPooledFilter', $except, true) && $this->poolMinNrPooledFilter !== null) {
                        $sub->where('pools.nr_pooled', '>=', $this->poolMinNrPooledFilter);
                    }
                    if (! in_array('poolMaxNrPooledFilter', $except, true) && $this->poolMaxNrPooledFilter !== null) {
                        $sub->where('pools.nr_pooled', '<=', $this->poolMaxNrPooledFilter);
                    }

                    if (! in_array('poolContentTypeFilter', $except, true) && $this->poolContentTypeFilter !== '' && $this->poolContentTypeFilter !== 'all') {
                        $contentType = match ($this->poolContentTypeFilter) {
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

        return $query;
    }

    private function normalizeType(string $type): string
    {
        if (str_starts_with($type, 'AppModels')) {
            return 'App\\Models\\'.substr($type, strlen('AppModels'));
        }

        return $type;
    }

    /**
     * Restrict the query by the experiment test purpose.
     *
     * Modes:
     *  - "screening": only screening experiments
     *  - "confirmation": only confirmation experiments
     *  - "either": experiments tagged as screening or confirmation
     *  - "screening_with_confirmation": only experiments whose origin sample was
     *    tested with both a screening tool and a confirmation tool
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
            // Restrict to screening/confirmation rows only so this is always a
            // strict subset of "either" (otherwise empty-purpose experiments of
            // qualifying samples would be counted too).
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
                });
        }
    }

    /**
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

    private function normalizedBasename(string $type): string
    {
        return class_basename($this->normalizeType($type));
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function seedIdsForTracing(string $seedType, array $except = []): array
    {
        $table = (new $seedType)->getTable();
        $q = $seedType::query()->select($table.'.id');

        if ($seedType === AnimalSamples::class) {
            $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

            if (! in_array('tracePrimaryAnimalSpeciesFilter', $except, true) && $this->tracePrimaryAnimalSpeciesFilter !== '') {
                $q->where('animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
            }

            if (! in_array('tracePrimaryAnimalSexFilter', $except, true) && $this->tracePrimaryAnimalSexFilter !== '') {
                $q->where('animals.sex', $this->tracePrimaryAnimalSexFilter);
            }

            if (! in_array('tracePrimaryAnimalAgeFilter', $except, true) && $this->tracePrimaryAnimalAgeFilter !== '') {
                $q->where('animals.age', $this->tracePrimaryAnimalAgeFilter);
            }
        }

        if ($seedType === HumanSamples::class) {
            $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

            if (! in_array('tracePrimaryHumanEthnicityFilter', $except, true) && $this->tracePrimaryHumanEthnicityFilter !== '') {
                $q->where('humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
            }

            if (! in_array('tracePrimaryHumanOccupationFilter', $except, true) && $this->tracePrimaryHumanOccupationFilter !== '') {
                $q->where('humans.occupation', $this->tracePrimaryHumanOccupationFilter);
            }

            if (! in_array('tracePrimaryHumanCountryFilter', $except, true) && $this->tracePrimaryHumanCountryFilter !== '') {
                $q->where('countries.name', $this->tracePrimaryHumanCountryFilter);
            }
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) use ($seedType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($seedType))
                    ->where('tubes.is_private', false);
            });
        } else {
            $projectId = $this->getProjectId();

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

    private function allAnimalSpecies()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->select('animal_species.name_common');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->where('tubes.tubes_content_type', AnimalSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('animal_samples.projects_id', $this->getProjectId());
        }

        return $q->distinct()->orderBy('animal_species.name_common')->pluck('animal_species.name_common')->filter()->values();
    }

    private function allSubProjects()
    {
        $base = $this->baseFilteredQuery(['subProjectFilter']);

        $query = SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', Experiments::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('experiments.id'))
            ->distinct()
            ->orderBy('sub_projects.code');

        return $query->pluck('sub_projects.code')->filter()->values();
    }

    /**
     * Calculate descriptive statistics from filtered experiments
     */
    private function calculateStatistics($experiments)
    {
        $stats = [
            'total_experiments' => $experiments->count(),
            'positive_experiments' => $experiments->where('outcome_discrete', 'Positive')->count(),
            'strong_positive_experiments' => $experiments->where('outcome_discrete', 'Strong positive')->count(),
            'suspect_experiments' => $experiments->where('outcome_discrete', 'Suspect')->count(),
            'negative_experiments' => $experiments->where('outcome_discrete', 'Negative')->count(),
            'experiments_this_year' => $experiments->filter(function ($experiment) {
                return $experiment->date_tested &&
                    Carbon::parse($experiment->date_tested)->year === Carbon::now()->year;
            })->count(),
            'experiments_this_month' => $experiments->filter(function ($experiment) {
                return $experiment->date_tested &&
                    Carbon::parse($experiment->date_tested)->isCurrentMonth();
            })->count(),
        ];

        // Calculate success rate
        $totalPositive = $stats['positive_experiments'] + $stats['strong_positive_experiments'];
        $stats['success_rate'] = $stats['total_experiments'] > 0
            ? round(($totalPositive / $stats['total_experiments']) * 100, 1)
            : 0;

        // Generate timeline data
        $stats['testing_timeline'] = $this->generateTimelineData($experiments);

        return $stats;
    }

    /**
     * Generate timeline data for the last 12 months
     */
    private function generateTimelineData($experiments)
    {
        $timeline = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabel = $month->format('M Y');

            $count = $experiments->filter(function ($experiment) use ($month) {
                return $experiment->date_tested &&
                    Carbon::parse($experiment->date_tested)->format('Y-m') === $month->format('Y-m');
            })->count();

            $timeline[$monthLabel] = $count;
        }

        return $timeline;
    }

    /**
     * Get all available experiment types for filter dropdown
     */
    private function getAllExperimentTypes()
    {
        $types = $this->baseFilteredQuery(['experimentTypeFilter', 'tracePrimaryTypeFilter', 'tracePrimaryAnimalSpeciesFilter'])
            ->select('experiments_content_type')
            ->distinct()
            ->pluck('experiments_content_type')
            ->filter()
            ->sort()
            ->values();

        if ($this->experimentTypeFilter !== '' && $this->experimentTypeFilter !== 'all' && ! $types->contains($this->experimentTypeFilter)) {
            $types->prepend($this->experimentTypeFilter);
        }

        return $types;
    }

    /**
     * Get all available protocols for filter dropdown
     */
    private function getAllProtocols()
    {
        $query = $this->baseFilteredQuery(['protocolFilter', 'tracePrimaryTypeFilter', 'tracePrimaryAnimalSpeciesFilter'])
            ->join('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->select('protocols.name')
            ->distinct()
            ->orderBy('protocols.name');

        $protocols = $query->pluck('protocols.name')->filter()->values();

        if ($this->protocolFilter && ! $protocols->contains($this->protocolFilter)) {
            $protocols->prepend($this->protocolFilter);
        }

        return $protocols;
    }

    private function getAllTechniqueTypes()
    {
        $techniqueTypes = $this->baseFilteredQuery(['techniqueTypeFilter', 'tracePrimaryTypeFilter', 'tracePrimaryAnimalSpeciesFilter'])
            ->join('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->join('techniques', 'protocols.techniques_id', '=', 'techniques.id')
            ->whereNotNull('techniques.name')
            ->select('techniques.name')
            ->distinct()
            ->orderBy('techniques.name')
            ->pluck('techniques.name')
            ->filter()
            ->values();

        if ($this->techniqueTypeFilter !== '' && ! $techniqueTypes->contains($this->techniqueTypeFilter)) {
            $techniqueTypes->prepend($this->techniqueTypeFilter);
        }

        return $techniqueTypes;
    }

    private function getAllTechniqueCategories()
    {
        $techniqueCategories = $this->baseFilteredQuery(['techniqueCategoryFilter', 'tracePrimaryTypeFilter', 'tracePrimaryAnimalSpeciesFilter'])
            ->join('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->join('techniques', 'protocols.techniques_id', '=', 'techniques.id')
            ->whereNotNull('techniques.type')
            ->select('techniques.type')
            ->distinct()
            ->orderBy('techniques.type')
            ->pluck('techniques.type')
            ->filter()
            ->values();

        if ($this->techniqueCategoryFilter !== '' && ! $techniqueCategories->contains($this->techniqueCategoryFilter)) {
            $techniqueCategories->prepend($this->techniqueCategoryFilter);
        }

        return $techniqueCategories;
    }

    /**
     * Get all available pathogens for filter dropdown
     */
    private function getAllPathogens()
    {
        $query = $this->baseFilteredQuery(['pathogenFilter', 'tracePrimaryTypeFilter', 'tracePrimaryAnimalSpeciesFilter'])
            ->join('pathogens', 'experiments.pathogens_id', '=', 'pathogens.id')
            ->select('pathogens.species')
            ->distinct()
            ->orderBy('pathogens.species');

        $pathogens = $query->pluck('pathogens.species')->filter()->values();

        if ($this->pathogenFilter && ! $pathogens->contains($this->pathogenFilter)) {
            $pathogens->prepend($this->pathogenFilter);
        }

        return $pathogens;
    }

    private function getAllOutcomes()
    {
        $outcomes = $this->baseFilteredQuery(['outcomeFilter', 'tracePrimaryTypeFilter', 'tracePrimaryAnimalSpeciesFilter'])
            ->whereNotNull('outcome_discrete')
            ->select('outcome_discrete')
            ->distinct()
            ->orderBy('outcome_discrete')
            ->pluck('outcome_discrete')
            ->filter()
            ->values();

        if ($this->outcomeFilter && ! $outcomes->contains($this->outcomeFilter)) {
            $outcomes->prepend($this->outcomeFilter);
        }

        return $outcomes;
    }

    private function getAllAnimalSpeciesForExperiments()
    {
        $variants = $this->typeVariants(AnimalSamples::class);

        $q = $this->baseFilteredQuery(['animalSpeciesFilter'])
            ->join('animal_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'animal_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->select('animal_species.name_common')
            ->distinct()
            ->orderBy('animal_species.name_common');

        return $q->pluck('animal_species.name_common')->filter()->values();
    }

    private function getAllAnimalSexesForExperiments()
    {
        $variants = $this->typeVariants(AnimalSamples::class);

        $q = $this->baseFilteredQuery(['animalSexFilter'])
            ->join('animal_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'animal_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->select('animals.sex')
            ->distinct()
            ->orderBy('animals.sex');

        return $q->pluck('animals.sex')->filter()->values();
    }

    private function getAllParasiteSpeciesForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return collect();
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return $this->baseFilteredQuery(['parasiteSpeciesFilter'])
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.name_scientific')
            ->distinct()
            ->orderBy('parasite_species.name_scientific')
            ->pluck('parasite_species.name_scientific')
            ->filter()
            ->values();
    }

    private function getAllParasiteStagesForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return collect();
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return $this->baseFilteredQuery(['parasiteStageFilter'])
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.stage')
            ->distinct()
            ->orderBy('parasites.stage')
            ->pluck('parasites.stage')
            ->filter()
            ->values();
    }

    private function getAllParasiteSexesForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return collect();
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return $this->baseFilteredQuery(['parasiteSexFilter'])
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.sex')
            ->distinct()
            ->orderBy('parasites.sex')
            ->pluck('parasites.sex')
            ->filter()
            ->values();
    }

    private function getAllParasiteStatesForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return collect();
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return $this->baseFilteredQuery(['parasiteStateFilter'])
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.state')
            ->distinct()
            ->orderBy('parasites.state')
            ->pluck('parasites.state')
            ->filter()
            ->values();
    }

    private function getAllParasiteSampleTypesForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return collect();
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return $this->baseFilteredQuery(['parasiteSampleTypeFilter'])
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->whereNotNull('parasite_sample_types.name')
            ->distinct()
            ->orderBy('parasite_sample_types.name')
            ->pluck('parasite_sample_types.name')
            ->filter()
            ->values();
    }

    private function getAllCultureTypesForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'Cultures') {
            return collect();
        }

        $variants = $this->typeVariants(Cultures::class);

        return $this->baseFilteredQuery(['cultureTypeFilter'])
            ->join('cultures', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'cultures.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->whereNotNull('cultures.type')
            ->distinct()
            ->orderBy('cultures.type')
            ->pluck('cultures.type')
            ->filter()
            ->values();
    }

    private function getAllCultureMediumsForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'Cultures') {
            return collect();
        }

        $variants = $this->typeVariants(Cultures::class);

        return $this->baseFilteredQuery(['cultureMediumFilter'])
            ->join('cultures', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'cultures.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->whereNotNull('cultures.medium')
            ->distinct()
            ->orderBy('cultures.medium')
            ->pluck('cultures.medium')
            ->filter()
            ->values();
    }

    private function getAllNucleicTypesForExperiments()
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'NucleicAcids') {
            return collect();
        }

        $variants = $this->typeVariants(NucleicAcids::class);

        return $this->baseFilteredQuery(['nucleicTypeFilter'])
            ->join('nucleic_acids', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'nucleic_acids.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->whereNotNull('nucleic_acids.type')
            ->distinct()
            ->orderBy('nucleic_acids.type')
            ->pluck('nucleic_acids.type')
            ->filter()
            ->values();
    }

    /**
     * @return array<string,string> key => label
     */
    private function availablePoolContentTypesForExperiments(): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'Pools') {
            return [];
        }

        $types = $this->baseFilteredQuery(['poolContentTypeFilter'])
            ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
            ->whereNotNull('pool_contents.samples_type')
            ->distinct()
            ->pluck('pool_contents.samples_type')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values();

        $map = [
            'HumanSamples' => ['human', 'Human samples'],
            'AnimalSamples' => ['animal', 'Animal samples'],
            'EnvironmentSamples' => ['environment', 'Environment samples'],
            'ParasiteSamples' => ['parasite', 'Parasite samples'],
            'Cultures' => ['culture', 'Cultures'],
            'NucleicAcids' => ['nucleic', 'Nucleic acids'],
            'Pools' => ['pool', 'Pools (nested)'],
        ];

        $options = [];
        foreach ($types->unique() as $rawType) {
            $baseName = $this->normalizedBasename((string) $rawType);
            if (! isset($map[$baseName])) {
                continue;
            }
            [$key, $label] = $map[$baseName];
            $options[$key] = $label;
        }

        $order = ['human', 'animal', 'environment', 'parasite', 'culture', 'nucleic', 'pool'];
        $sorted = [];
        foreach ($order as $k) {
            if (array_key_exists($k, $options)) {
                $sorted[$k] = $options[$k];
            }
        }

        return $sorted;
    }

    /**
     * Extract coordinates from experiment content based on its type
     */
    private function extractCoordinates($experiment)
    {
        $content = $experiment->experiments_content;
        if (! $content) {
            return [null, null];
        }

        $contentType = class_basename($content);

        // Primary samples have direct coordinates
        if (in_array($contentType, ['AnimalSamples', 'HumanSamples', 'EnvironmentSamples'])) {
            if ($content->latitude && $content->longitude) {
                return [$content->latitude, $content->longitude];
            } else {
                return [$content->sampling_sites->latitude, $content->sampling_sites->longitude];
            }
        }

        // Parasite samples get location from parasites_origin
        if ($contentType === 'ParasiteSamples') {
            try {
                if (method_exists($content, 'parasites') && $content->parasites && $content->parasites->parasites_origin) {
                    return [$content->parasites->parasites_origin->latitude, $content->parasites->parasites_origin->longitude];
                }
            } catch (\Exception $e) {
                // Relationship not loaded or doesn't exist
                return [null, null];
            }

            return [null, null];
        }

        // Nucleic acids get location from their content
        if ($contentType === 'NucleicAcids') {
            return $this->extractCoordinatesFromNucleicAcids($content);
        }

        // Cultures get location from their content
        if ($contentType === 'Cultures') {
            return $this->extractCoordinatesFromCultures($content);
        }

        // Pools get location from their content
        if ($contentType === 'Pools') {
            return $this->extractCoordinatesFromPools($content);
        }

        return [null, null];
    }

    /**
     * Extract coordinates from NucleicAcids content
     */
    private function extractCoordinatesFromNucleicAcids($nucleicAcids)
    {
        try {
            if (! method_exists($nucleicAcids, 'nucleic_content') || ! $nucleicAcids->nucleic_content) {
                return [null, null];
            }

            $nucleicContent = $nucleicAcids->nucleic_content;
            $contentType = class_basename($nucleicContent);

            // Primary samples have direct coordinates
            if (in_array($contentType, ['AnimalSamples', 'HumanSamples', 'EnvironmentSamples'])) {
                return [$nucleicContent->latitude, $nucleicContent->longitude];
            }

            // Parasite samples get location from parasites_origin
            if ($contentType === 'ParasiteSamples') {
                try {
                    if (method_exists($nucleicContent, 'parasites') && $nucleicContent->parasites && $nucleicContent->parasites->parasites_origin) {
                        return [$nucleicContent->parasites->parasites_origin->latitude, $nucleicContent->parasites->parasites_origin->longitude];
                    }
                } catch (\Exception $e) {
                    // Relationship not loaded or doesn't exist
                    return [null, null];
                }

                return [null, null];
            }

            // Cultures get location from their content
            if ($contentType === 'Cultures') {
                return $this->extractCoordinatesFromCultures($nucleicContent);
            }

            return [null, null];
        } catch (\Exception $e) {
            // Lazy loading disabled, skip this relationship
            return [null, null];
        }
    }

    /**
     * Extract coordinates from Cultures content
     */
    private function extractCoordinatesFromCultures($cultures)
    {
        try {
            if (! method_exists($cultures, 'cultures_content') || ! $cultures->cultures_content) {
                return [null, null];
            }

            $culturesContent = $cultures->cultures_content;
            $contentType = class_basename($culturesContent);

            // Primary samples have direct coordinates
            if (in_array($contentType, ['AnimalSamples', 'HumanSamples', 'EnvironmentSamples'])) {
                return [$culturesContent->latitude, $culturesContent->longitude];
            }

            // Parasite samples get location from parasites_origin
            if ($contentType === 'ParasiteSamples') {
                try {
                    if (method_exists($culturesContent, 'parasites') && $culturesContent->parasites && $culturesContent->parasites->parasites_origin) {
                        return [$culturesContent->parasites->parasites_origin->latitude, $culturesContent->parasites->parasites_origin->longitude];
                    }
                } catch (\Exception $e) {
                    // Relationship not loaded or doesn't exist
                    return [null, null];
                }

                return [null, null];
            }

            return [null, null];
        } catch (\Exception $e) {
            // Lazy loading disabled, skip this relationship
            return [null, null];
        }
    }

    /**
     * Extract coordinates from Pools content
     */
    private function extractCoordinatesFromPools($pools)
    {
        try {
            if (! method_exists($pools, 'pool_contents') || ! $pools->pool_contents) {
                return [null, null];
            }

            // Get the first sample from pool_contents
            $firstPoolContent = $pools->pool_contents->first();
            if (! $firstPoolContent || ! $firstPoolContent->samples) {
                return [null, null];
            }

            $sample = $firstPoolContent->samples;
            $sampleType = class_basename($sample);

            // Primary samples have direct coordinates
            if (in_array($sampleType, ['AnimalSamples', 'HumanSamples', 'EnvironmentSamples'])) {
                return [$sample->latitude, $sample->longitude];
            }

            // Parasite samples get location from parasites_origin
            if ($sampleType === 'ParasiteSamples') {
                try {
                    if (method_exists($sample, 'parasites') && $sample->parasites && $sample->parasites->parasites_origin) {
                        return [$sample->parasites->parasites_origin->latitude, $sample->parasites->parasites_origin->longitude];
                    }
                } catch (\Exception $e) {
                    // Relationship not loaded or doesn't exist
                    return [null, null];
                }

                return [null, null];
            }

            return [null, null];
        } catch (\Exception $e) {
            // Lazy loading disabled, skip this relationship
            return [null, null];
        }
    }

    /**
     * Get experiments with properly loaded relationships for coordinate extraction
     */
    private function getExperimentsWithCoordinates()
    {
        // Build base query with filters
        $baseQuery = $this->baseFilteredQuery();

        // Get experiments with different relationship loading strategies
        $experiments = collect();
        $limitPerType = 150;

        // 1. Primary samples (AnimalSamples, HumanSamples, EnvironmentSamples)
        $primaryExperiments = $baseQuery->clone()
            ->whereHasMorph('experiments_content', [
                AnimalSamples::class,
                HumanSamples::class,
                EnvironmentSamples::class,
            ])
            ->with(['experiments_content',
                'experiments_content.sampling_sites',
                'protocols',
                'pathogens',
                'laboratories'])
            ->orderBy('created_at', 'desc')
            ->limit($limitPerType)
            ->get();
        $experiments = $experiments->merge($primaryExperiments);

        // 2. Parasite samples with parasites relationship
        $parasiteExperiments = $baseQuery->clone()
            ->whereHasMorph('experiments_content', [ParasiteSamples::class])
            ->with([
                'experiments_content',
                'experiments_content.parasites.parasites_origin',
                'protocols', 'pathogens', 'laboratories',
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limitPerType)
            ->get();
        $experiments = $experiments->merge($parasiteExperiments);

        // 3. Nucleic acids with their content relationships - load based on content type
        $nucleicExperiments = $baseQuery->clone()
            ->whereHasMorph('experiments_content', [NucleicAcids::class])
            ->with([
                'experiments_content',
                'experiments_content.nucleic_content',
                'protocols', 'pathogens', 'laboratories',
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limitPerType)
            ->get();
        $experiments = $experiments->merge($nucleicExperiments);

        // 4. Cultures with their content relationships - load based on content type
        $cultureExperiments = $baseQuery->clone()
            ->whereHasMorph('experiments_content', [Cultures::class])
            ->with([
                'experiments_content',
                'experiments_content.cultures_content',
                'protocols', 'pathogens', 'laboratories',
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limitPerType)
            ->get();
        $experiments = $experiments->merge($cultureExperiments);

        // 5. Pools with their content relationships
        $poolExperiments = $baseQuery->clone()
            ->whereHasMorph('experiments_content', [Pools::class])
            ->with([
                'experiments_content',
                'experiments_content.pool_contents.samples',
                'protocols', 'pathogens', 'laboratories',
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limitPerType)
            ->get();
        $experiments = $experiments->merge($poolExperiments);

        // Extract coordinates from all experiments
        return $experiments
            ->map(function ($experiment) {
                [$latitude, $longitude] = $this->extractCoordinates($experiment);
                $content = $experiment->experiments_content;

                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'outcome_discrete' => $experiment->outcome_discrete,
                    'type' => $content ? class_basename($content) : 'Unknown',
                    'code' => $content ? $content->code : null,
                ];
            })
            ->filter(function ($sample) {
                return $sample['latitude'] && $sample['longitude'];
            })
            ->values();
    }

    public function filteredData(bool $realtimeOnly = false)
    {
        $base = $this->baseFilteredQuery();

        // Descriptive stats (aggregate queries; avoid loading full datasets)
        $total = (clone $base)->count();
        $positive = (clone $base)->where('outcome_discrete', 'Positive')->count();
        $strongPositive = (clone $base)->where('outcome_discrete', 'Strong positive')->count();
        $suspect = (clone $base)->where('outcome_discrete', 'Suspect')->count();
        $negative = (clone $base)->where('outcome_discrete', 'Negative')->count();

        $experimentsThisYear = (clone $base)->whereYear('date_tested', Carbon::now()->year)->count();
        $experimentsThisMonth = (clone $base)
            ->whereBetween('date_tested', [
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString(),
            ])
            ->count();

        $totalPositive = $positive + $strongPositive;
        $successRate = $total > 0 ? round(($totalPositive / $total) * 100, 1) : 0;

        $testingTimeline = [];
        if ($this->timelineGranularity === 'yearly') {
            for ($i = 9; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i);
                $label = $year->format('Y');

                $testingTimeline[$label] = (clone $base)
                    ->whereBetween('date_tested', [
                        $year->copy()->startOfYear()->toDateString(),
                        $year->copy()->endOfYear()->toDateString(),
                    ])
                    ->count();
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $label = $month->format('M Y');

                $testingTimeline[$label] = (clone $base)
                    ->whereBetween('date_tested', [
                        $month->copy()->startOfMonth()->toDateString(),
                        $month->copy()->endOfMonth()->toDateString(),
                    ])
                    ->count();
            }
        }

        $statistics = [
            'total_experiments' => $total,
            'positive_experiments' => $positive,
            'strong_positive_experiments' => $strongPositive,
            'suspect_experiments' => $suspect,
            'negative_experiments' => $negative,
            'experiments_this_year' => $experimentsThisYear,
            'experiments_this_month' => $experimentsThisMonth,
            'success_rate' => $successRate,
            'testing_timeline' => $testingTimeline,
        ];

        // Table data (paginated)
        $experiments = (clone $base)
            ->with(['protocols:id,name', 'pathogens:id,species', 'laboratories:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'experiments-page');

        // Modal data (paginated, not a full collection)
        $allFilteredExperiments = $this->applyDatasetSampleColumns(
            (clone $base)
                ->with(['protocols:id,name', 'pathogens:id,species', 'laboratories:id,name'])
        )->paginate(50, pageName: 'all_experiments_page');

        $experimentsByOutcome = (clone $base)
            ->select('outcome_discrete', DB::raw('count(*) as total'))
            ->whereNotNull('outcome_discrete')
            ->groupBy('outcome_discrete')
            ->pluck('total', 'outcome_discrete')
            ->toArray();

        $topProtocols = (clone $base)
            ->join('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->select('protocols.name', DB::raw('count(*) as total'))
            ->groupBy('protocols.name')
            ->orderByDesc('total')
            ->pluck('total', 'protocols.name')
            ->toArray();

        $topPathogens = (clone $base)
            ->join('pathogens', 'experiments.pathogens_id', '=', 'pathogens.id')
            ->select('pathogens.species', DB::raw('count(*) as total'))
            ->groupBy('pathogens.species')
            ->orderByDesc('total')
            ->pluck('total', 'pathogens.species')
            ->toArray();

        $experimentsByLab = (clone $base)
            ->join('laboratories', 'experiments.laboratories_id', '=', 'laboratories.id')
            ->select('laboratories.name', DB::raw('count(*) as total'))
            ->groupBy('laboratories.name')
            ->orderByDesc('total')
            ->pluck('total', 'laboratories.name');

        $experimentsByTechnique = (clone $base)
            ->leftJoin('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->leftJoin('techniques', 'protocols.techniques_id', '=', 'techniques.id')
            ->select(DB::raw("COALESCE(techniques.type, 'Unknown') as technique_type"), DB::raw('count(*) as total'))
            ->groupBy('technique_type')
            ->orderByDesc('total')
            ->pluck('total', 'technique_type')
            ->toArray();

        $tracePrimaryAnimalSpeciesOptions = $this->tracePrimaryAnimalSpeciesOptions();
        $tracePrimaryAnimalSexesOptions = $this->tracePrimaryAnimalSexesOptions();
        $tracePrimaryAnimalAgesOptions = $this->tracePrimaryAnimalAgesOptions();
        $tracePrimaryHumanEthnicitiesOptions = $this->tracePrimaryHumanEthnicitiesOptions();
        $tracePrimaryHumanOccupationsOptions = $this->tracePrimaryHumanOccupationsOptions();
        $tracePrimaryHumanCountriesOptions = $this->tracePrimaryHumanCountriesOptions();
        $traceDeepAnimalSpeciesOptions = $this->traceDeepAnimalSpeciesOptions();
        $traceDeepAnimalSexesOptions = $this->traceDeepAnimalSexesOptions();
        $traceDeepAnimalAgesOptions = $this->traceDeepAnimalAgesOptions();
        $traceDeepHumanEthnicitiesOptions = $this->traceDeepHumanEthnicitiesOptions();
        $traceDeepHumanOccupationsOptions = $this->traceDeepHumanOccupationsOptions();
        $traceDeepHumanCountriesOptions = $this->traceDeepHumanCountriesOptions();
        $allTraceParasiteSpecies = $this->getAllTraceParasiteSpecies();
        $allTraceParasiteStages = $this->getAllTraceParasiteStages();
        $allTraceParasiteSexes = $this->getAllTraceParasiteSexes();
        $allTraceCultureTypes = $this->getAllTraceCultureTypes();
        $allTraceCultureMediums = $this->getAllTraceCultureMediums();

        $totalPositiveForPrevalence = $positive + $strongPositive;
        $totalNegativeForPrevalence = $negative;

        $prevalenceBreakdown = (clone $base)
            ->leftJoin('pathogens', 'experiments.pathogens_id', '=', 'pathogens.id')
            ->leftJoin('protocols', 'experiments.protocols_id', '=', 'protocols.id')
            ->selectRaw("COALESCE(pathogens.species, 'Unknown') as pathogen")
            ->selectRaw("COALESCE(protocols.name, 'Unknown') as protocol")
            ->selectRaw("SUM(CASE WHEN experiments.outcome_discrete IN ('Positive', 'Strong positive') THEN 1 ELSE 0 END) as positive_count")
            ->selectRaw('COUNT(*) as total_count')
            ->groupBy('pathogen', 'protocol')
            ->orderByDesc('total_count')
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_count;
                $positiveCount = (int) $row->positive_count;

                return [
                    'pathogen' => (string) $row->pathogen,
                    'protocol' => (string) $row->protocol,
                    'positive_count' => $positiveCount,
                    'total_count' => $total,
                    'prevalence' => $total > 0 ? round(($positiveCount / $total) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        $screeningConfirmationMode = $this->purposeFilter === 'screening_with_confirmation';
        $screeningConfirmation = $screeningConfirmationMode
            ? $this->screeningConfirmationPrevalence($base)
            : ['rows' => [], 'summary' => null];

        $pieChartTabs = [
            ['key' => 'outcome', 'label' => 'Outcome', 'data' => $experimentsByOutcome],
            ['key' => 'sample_type', 'label' => 'Sample Type', 'data' => $this->contentTypeDistribution($base)],
            ['key' => 'pathogen', 'label' => 'Pathogen', 'data' => $topPathogens],
        ];

        $barChartTabs = [
            ['key' => 'protocol', 'label' => 'Protocols', 'data' => $topProtocols],
            ['key' => 'laboratory', 'label' => 'Laboratories', 'data' => $experimentsByLab->toArray()],
            ['key' => 'technique_type', 'label' => 'Technique Type', 'data' => $experimentsByTechnique],
        ];

        $mapColorVariableOptions = [
            ['key' => 'outcome', 'label' => 'Outcome'],
            ['key' => 'type', 'label' => 'Sample type'],
            ['key' => 'protocol', 'label' => 'Protocol'],
            ['key' => 'pathogen', 'label' => 'Pathogen'],
            ['key' => 'technique_type', 'label' => 'Technique type'],
            ['key' => 'laboratory', 'label' => 'Laboratory'],
        ];

        $this->appendExperimentTypeSpecificChartTabs(
            $pieChartTabs,
            $barChartTabs,
            $mapColorVariableOptions,
            $base,
            $tracePrimaryAnimalSpeciesOptions,
            $tracePrimaryAnimalSexesOptions,
            $tracePrimaryAnimalAgesOptions,
            $tracePrimaryHumanEthnicitiesOptions,
            $tracePrimaryHumanOccupationsOptions,
            $tracePrimaryHumanCountriesOptions,
            $traceDeepAnimalSpeciesOptions,
            $traceDeepAnimalSexesOptions,
            $traceDeepAnimalAgesOptions,
            $traceDeepHumanEthnicitiesOptions,
            $traceDeepHumanOccupationsOptions,
            $traceDeepHumanCountriesOptions,
            $allTraceParasiteSpecies,
            $allTraceParasiteStages,
            $allTraceParasiteSexes,
            $allTraceCultureTypes,
            $allTraceCultureMediums
        );

        $activeFilters = [
            'experimentTypeFilter' => $this->experimentTypeFilter,
            'tracePrimaryTypeFilter' => $this->tracePrimaryTypeFilter,
            'tracePrimaryAnimalSpeciesFilter' => $this->tracePrimaryAnimalSpeciesFilter,
            'tracePrimaryAnimalSexFilter' => $this->tracePrimaryAnimalSexFilter,
            'tracePrimaryAnimalAgeFilter' => $this->tracePrimaryAnimalAgeFilter,
            'tracePrimaryHumanEthnicityFilter' => $this->tracePrimaryHumanEthnicityFilter,
            'tracePrimaryHumanOccupationFilter' => $this->tracePrimaryHumanOccupationFilter,
            'tracePrimaryHumanCountryFilter' => $this->tracePrimaryHumanCountryFilter,
            'tracePrimaryParasiteSpeciesFilter' => $this->tracePrimaryParasiteSpeciesFilter,
            'tracePrimaryParasiteStageFilter' => $this->tracePrimaryParasiteStageFilter,
            'tracePrimaryParasiteSexFilter' => $this->tracePrimaryParasiteSexFilter,
            'tracePrimaryParasiteStateFilter' => $this->tracePrimaryParasiteStateFilter,
            'tracePrimaryParasiteSampleTypeFilter' => $this->tracePrimaryParasiteSampleTypeFilter,
            'tracePrimaryCultureTypeFilter' => $this->tracePrimaryCultureTypeFilter,
            'tracePrimaryCultureMediumFilter' => $this->tracePrimaryCultureMediumFilter,
            'tracePrimaryPoolMinNrPooled' => $this->tracePrimaryPoolMinNrPooled,
            'tracePrimaryPoolMaxNrPooled' => $this->tracePrimaryPoolMaxNrPooled,
            'traceDeepPrimaryTypeFilter' => $this->traceDeepPrimaryTypeFilter,
            'traceDeepAnimalSpeciesFilter' => $this->traceDeepAnimalSpeciesFilter,
            'traceDeepAnimalSexFilter' => $this->traceDeepAnimalSexFilter,
            'traceDeepAnimalAgeFilter' => $this->traceDeepAnimalAgeFilter,
            'traceDeepHumanEthnicityFilter' => $this->traceDeepHumanEthnicityFilter,
            'traceDeepHumanOccupationFilter' => $this->traceDeepHumanOccupationFilter,
            'traceDeepHumanCountryFilter' => $this->traceDeepHumanCountryFilter,
            'animalSpeciesFilter' => $this->animalSpeciesFilter,
            'animalSexFilter' => $this->animalSexFilter,
            'parasiteSpeciesFilter' => $this->parasiteSpeciesFilter,
            'parasiteStageFilter' => $this->parasiteStageFilter,
            'parasiteSexFilter' => $this->parasiteSexFilter,
            'parasiteStateFilter' => $this->parasiteStateFilter,
            'parasiteSampleTypeFilter' => $this->parasiteSampleTypeFilter,
            'cultureTypeFilter' => $this->cultureTypeFilter,
            'cultureMediumFilter' => $this->cultureMediumFilter,
            'nucleicTypeFilter' => $this->nucleicTypeFilter,
            'poolContentTypeFilter' => $this->poolContentTypeFilter,
            'poolMinNrPooledFilter' => $this->poolMinNrPooledFilter,
            'poolMaxNrPooledFilter' => $this->poolMaxNrPooledFilter,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'protocolFilter' => $this->protocolFilter,
            'techniqueTypeFilter' => $this->techniqueTypeFilter,
            'techniqueCategoryFilter' => $this->techniqueCategoryFilter,
            'pathogenFilter' => $this->pathogenFilter,
            'outcomeFilter' => $this->outcomeFilter,
            'purposeFilter' => $this->purposeFilter,
            'subProjectFilter' => $this->subProjectFilter,
            'samplingSiteFilter' => $this->samplingSiteFilter,
        ];

        if ($realtimeOnly) {
            return [
                'descriptive_stats' => $statistics,
                'pieChartTabs' => $pieChartTabs,
                'barChartTabs' => $barChartTabs,
                'mapColorVariableOptions' => $mapColorVariableOptions,
                'mapPointsUrl' => route('experiments.dashboard.map-points'),
                'activeFilters' => $activeFilters,
            ];
        }

        // Filter dropdown options
        $allExperimentTypes = $this->getAllExperimentTypes();
        $allProtocols = $this->getAllProtocols();
        $allTechniqueTypes = $this->getAllTechniqueTypes();
        $allTechniqueCategories = $this->getAllTechniqueCategories();
        $allPathogens = $this->getAllPathogens();
        $allOutcomes = $this->getAllOutcomes();
        $allSubProjects = $this->allSubProjects();
        $normalizedType = $this->normalizedBasename((string) $this->experimentTypeFilter);

        $allAnimalSexes = collect();
        $allAnimalSpeciesForExperiments = collect();
        $allParasiteSpeciesForExperiments = collect();
        $allParasiteStagesForExperiments = collect();
        $allParasiteSexesForExperiments = collect();
        $allParasiteStatesForExperiments = collect();
        $allParasiteSampleTypesForExperiments = collect();
        $allCultureTypesForExperiments = collect();
        $allCultureMediumsForExperiments = collect();
        $allNucleicTypesForExperiments = collect();
        $availablePoolContentTypesForExperiments = [];
        $tracePrimaryAnimalSpeciesOptions = collect();
        $tracePrimaryAnimalSexesOptions = collect();
        $tracePrimaryAnimalAgesOptions = collect();
        $tracePrimaryHumanEthnicitiesOptions = collect();
        $tracePrimaryHumanOccupationsOptions = collect();
        $tracePrimaryHumanCountriesOptions = collect();
        $availableTraceTypes = [];
        $allTraceParasiteSpecies = collect();
        $allTraceParasiteStages = collect();
        $allTraceParasiteSexes = collect();
        $allTraceParasiteStates = collect();
        $allTraceParasiteSampleTypes = collect();
        $allTraceCultureTypes = collect();
        $allTraceCultureMediums = collect();
        $availableDeepPrimaryTypes = [];
        $traceDeepAnimalSpeciesOptions = collect();
        $traceDeepAnimalSexesOptions = collect();
        $traceDeepAnimalAgesOptions = collect();
        $traceDeepHumanEthnicitiesOptions = collect();
        $traceDeepHumanOccupationsOptions = collect();
        $traceDeepHumanCountriesOptions = collect();
        $poolsWithMissingPoolContents = [];
        $allSamplingSitesForExperiments = $this->samplingSitesForExperimentsOptions();

        if ($normalizedType === 'AnimalSamples') {
            $allAnimalSexes = $this->getAllAnimalSexesForExperiments();
            $allAnimalSpeciesForExperiments = $this->getAllAnimalSpeciesForExperiments();
        }
        if ($normalizedType === 'ParasiteSamples') {
            $allParasiteSpeciesForExperiments = $this->getAllParasiteSpeciesForExperiments();
            $allParasiteStagesForExperiments = $this->getAllParasiteStagesForExperiments();
            $allParasiteSexesForExperiments = $this->getAllParasiteSexesForExperiments();
            $allParasiteStatesForExperiments = $this->getAllParasiteStatesForExperiments();
            $allParasiteSampleTypesForExperiments = $this->getAllParasiteSampleTypesForExperiments();
        }
        if ($normalizedType === 'Cultures') {
            $allCultureTypesForExperiments = $this->getAllCultureTypesForExperiments();
            $allCultureMediumsForExperiments = $this->getAllCultureMediumsForExperiments();
        }
        if ($normalizedType === 'NucleicAcids') {
            $allNucleicTypesForExperiments = $this->getAllNucleicTypesForExperiments();
        }
        if ($normalizedType === 'Pools') {
            $availablePoolContentTypesForExperiments = $this->availablePoolContentTypesForExperiments();
            $poolsWithMissingPoolContents = $this->poolsWithMissingPoolContentsForFilteredExperiments();
        }

        $hasTraceFiltersSection = in_array($normalizedType, ['ParasiteSamples', 'NucleicAcids', 'Cultures', 'Pools'], true);
        if ($hasTraceFiltersSection) {
            $availableTraceTypes = $this->availableTraceTypes();
        }
        if ($this->tracePrimaryTypeFilter === 'animal') {
            $tracePrimaryAnimalSpeciesOptions = $this->tracePrimaryAnimalSpeciesOptions();
            $tracePrimaryAnimalSexesOptions = $this->tracePrimaryAnimalSexesOptions();
            $tracePrimaryAnimalAgesOptions = $this->tracePrimaryAnimalAgesOptions();
        }
        if ($this->tracePrimaryTypeFilter === 'human') {
            $tracePrimaryHumanEthnicitiesOptions = $this->tracePrimaryHumanEthnicitiesOptions();
            $tracePrimaryHumanOccupationsOptions = $this->tracePrimaryHumanOccupationsOptions();
            $tracePrimaryHumanCountriesOptions = $this->tracePrimaryHumanCountriesOptions();
        }
        if ($this->tracePrimaryTypeFilter === 'parasite') {
            $allTraceParasiteSpecies = $this->getAllTraceParasiteSpecies();
            $allTraceParasiteStages = $this->getAllTraceParasiteStages();
            $allTraceParasiteSexes = $this->getAllTraceParasiteSexes();
            $allTraceParasiteStates = $this->getAllTraceParasiteStates();
            $allTraceParasiteSampleTypes = $this->getAllTraceParasiteSampleTypes();
        }
        if ($this->tracePrimaryTypeFilter === 'culture') {
            $allTraceCultureTypes = $this->getAllTraceCultureTypes();
            $allTraceCultureMediums = $this->getAllTraceCultureMediums();
        }
        if ($hasTraceFiltersSection && in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            $availableDeepPrimaryTypes = $this->availableDeepPrimaryTypes();
            if ($this->traceDeepPrimaryTypeFilter === 'animal') {
                $traceDeepAnimalSpeciesOptions = $this->traceDeepAnimalSpeciesOptions();
                $traceDeepAnimalSexesOptions = $this->traceDeepAnimalSexesOptions();
                $traceDeepAnimalAgesOptions = $this->traceDeepAnimalAgesOptions();
            }
            if ($this->traceDeepPrimaryTypeFilter === 'human') {
                $traceDeepHumanEthnicitiesOptions = $this->traceDeepHumanEthnicitiesOptions();
                $traceDeepHumanOccupationsOptions = $this->traceDeepHumanOccupationsOptions();
                $traceDeepHumanCountriesOptions = $this->traceDeepHumanCountriesOptions();
            }
        }

        return [
            'experiments' => $experiments,
            'all_experiments' => $allFilteredExperiments,
            'isGuestMode' => $this->isGuestMode(),
            'descriptive_stats' => $statistics,
            'experimentsByOutcome' => $experimentsByOutcome,
            'topProtocols' => $topProtocols,
            'topPathogens' => $topPathogens,
            'experimentsByLab' => $experimentsByLab,
            'experimentsByTechnique' => $experimentsByTechnique,
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
            'prevalenceSummary' => ($screeningConfirmationMode && $screeningConfirmation['summary'])
                ? $screeningConfirmation['summary']
                : [
                    'positive' => $totalPositiveForPrevalence,
                    'negative' => $totalNegativeForPrevalence,
                    'total' => $total,
                    'percentage' => $total > 0 ? round(($totalPositiveForPrevalence / $total) * 100, 1) : 0.0,
                ],
            'prevalenceBreakdown' => $prevalenceBreakdown,
            'screeningConfirmationMode' => $screeningConfirmationMode,
            'screeningConfirmationBreakdown' => $screeningConfirmation['rows'],
            'samples' => [],
            'mapPointsUrl' => route('experiments.dashboard.map-points'),
            'activeFilters' => $activeFilters,
            'allExperimentTypes' => $allExperimentTypes,
            'allProtocols' => $allProtocols,
            'allTechniqueTypes' => $allTechniqueTypes,
            'allTechniqueCategories' => $allTechniqueCategories,
            'allPathogens' => $allPathogens,
            'allOutcomes' => $allOutcomes,
            'allSubProjects' => $allSubProjects,
            'allAnimalSexes' => $allAnimalSexes,
            'allAnimalSpeciesForExperiments' => $allAnimalSpeciesForExperiments,
            'allParasiteSpeciesForExperiments' => $allParasiteSpeciesForExperiments,
            'allParasiteStagesForExperiments' => $allParasiteStagesForExperiments,
            'allParasiteSexesForExperiments' => $allParasiteSexesForExperiments,
            'allParasiteStatesForExperiments' => $allParasiteStatesForExperiments,
            'allParasiteSampleTypesForExperiments' => $allParasiteSampleTypesForExperiments,
            'allCultureTypesForExperiments' => $allCultureTypesForExperiments,
            'allCultureMediumsForExperiments' => $allCultureMediumsForExperiments,
            'allNucleicTypesForExperiments' => $allNucleicTypesForExperiments,
            'availablePoolContentTypesForExperiments' => $availablePoolContentTypesForExperiments,
            'tracePrimaryAnimalSpeciesOptions' => $tracePrimaryAnimalSpeciesOptions,
            'tracePrimaryAnimalSexesOptions' => $tracePrimaryAnimalSexesOptions,
            'tracePrimaryAnimalAgesOptions' => $tracePrimaryAnimalAgesOptions,
            'tracePrimaryHumanEthnicitiesOptions' => $tracePrimaryHumanEthnicitiesOptions,
            'tracePrimaryHumanOccupationsOptions' => $tracePrimaryHumanOccupationsOptions,
            'tracePrimaryHumanCountriesOptions' => $tracePrimaryHumanCountriesOptions,
            'availableTraceTypes' => $availableTraceTypes,
            'availableDeepPrimaryTypes' => $availableDeepPrimaryTypes,
            'traceDeepAnimalSpeciesOptions' => $traceDeepAnimalSpeciesOptions,
            'traceDeepAnimalSexesOptions' => $traceDeepAnimalSexesOptions,
            'traceDeepAnimalAgesOptions' => $traceDeepAnimalAgesOptions,
            'traceDeepHumanEthnicitiesOptions' => $traceDeepHumanEthnicitiesOptions,
            'traceDeepHumanOccupationsOptions' => $traceDeepHumanOccupationsOptions,
            'traceDeepHumanCountriesOptions' => $traceDeepHumanCountriesOptions,
            'allTraceParasiteSpecies' => $allTraceParasiteSpecies,
            'allTraceParasiteStages' => $allTraceParasiteStages,
            'allTraceParasiteSexes' => $allTraceParasiteSexes,
            'allTraceParasiteStates' => $allTraceParasiteStates,
            'allTraceParasiteSampleTypes' => $allTraceParasiteSampleTypes,
            'allTraceCultureTypes' => $allTraceCultureTypes,
            'allTraceCultureMediums' => $allTraceCultureMediums,
            'poolsWithMissingPoolContents' => $poolsWithMissingPoolContents,
            'allSamplingSitesForExperiments' => $allSamplingSitesForExperiments,
            'canEdit' => $this->canEdit(),
        ];
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $pieChartTabs
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     */
    private function appendExperimentTypeSpecificChartTabs(
        array &$pieChartTabs,
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        $base,
        $tracePrimaryAnimalSpeciesOptions,
        $tracePrimaryAnimalSexesOptions,
        $tracePrimaryAnimalAgesOptions,
        $tracePrimaryHumanEthnicitiesOptions,
        $tracePrimaryHumanOccupationsOptions,
        $tracePrimaryHumanCountriesOptions,
        $traceDeepAnimalSpeciesOptions,
        $traceDeepAnimalSexesOptions,
        $traceDeepAnimalAgesOptions,
        $traceDeepHumanEthnicitiesOptions,
        $traceDeepHumanOccupationsOptions,
        $traceDeepHumanCountriesOptions,
        $allTraceParasiteSpecies,
        $allTraceParasiteStages,
        $allTraceParasiteSexes,
        $allTraceCultureTypes,
        $allTraceCultureMediums
    ): void {
        $normalizedType = $this->normalizedBasename((string) $this->experimentTypeFilter);

        if ($normalizedType === 'AnimalSamples') {
            $this->appendAnimalChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $this->groupedAnimalSpeciesDistribution($base),
                $this->groupedAnimalSexDistribution($base),
                $this->groupedAnimalAgeDistribution($base)
            );
            $this->appendSamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $base);
        }

        if ($normalizedType === 'HumanSamples') {
            $this->appendHumanChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $this->groupedHumanEthnicityDistribution($base),
                $this->groupedHumanOccupationDistribution($base),
                $this->groupedHumanCountryDistribution($base)
            );
            $this->appendSamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $base);
        }

        if ($normalizedType === 'EnvironmentSamples') {
            $this->appendUniqueChartTab($pieChartTabs, 'environment_sample_type', 'Environment Sample Type', $this->groupedEnvironmentSampleTypeDistribution($base));
            $this->appendUniqueMapColorOption($mapColorVariableOptions, 'environment_sample_type', 'Environment sample type');
            $this->appendSamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $base);
        }

        if ($normalizedType === 'ParasiteSamples') {
            $this->appendParasiteChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $this->groupedParasiteSpeciesDistribution($base),
                $this->groupedParasiteStageDistribution($base),
                $this->groupedParasiteSexDistribution($base),
                $this->groupedParasiteStateDistribution($base)
            );

            foreach ($this->activeTracePrimaryKeys() as $traceKey) {
                if ($traceKey === 'animal') {
                    $this->appendAnimalChartTabs(
                        $pieChartTabs,
                        $barChartTabs,
                        $mapColorVariableOptions,
                        $this->groupedTracePrimaryAnimalSpeciesDistribution('animal'),
                        $this->groupedTracePrimaryAnimalSexDistribution('animal'),
                        $this->groupedTracePrimaryAnimalAgeDistribution('animal')
                    );
                }

                if ($traceKey === 'human') {
                    $this->appendHumanChartTabs(
                        $pieChartTabs,
                        $barChartTabs,
                        $mapColorVariableOptions,
                        $this->groupedTracePrimaryHumanEthnicityDistribution('human'),
                        $this->groupedTracePrimaryHumanOccupationDistribution('human'),
                        $this->groupedTracePrimaryHumanCountryDistribution('human')
                    );
                }

                if (in_array($traceKey, ['human', 'animal', 'environment'], true)) {
                    $this->appendTracePrimarySamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $traceKey);
                }
            }

            $this->appendSamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $base);
        }

        if ($normalizedType === 'NucleicAcids') {
            $this->appendUniqueChartTab($pieChartTabs, 'nucleic_type', 'Nucleic Type', $this->groupedNucleicTypeDistribution($base));
            $this->appendUniqueChartTab($barChartTabs, 'nucleic_extraction_protocol', 'Extraction Protocol', $this->groupedNucleicExtractionProtocolDistribution($base));
            $this->appendUniqueMapColorOption($mapColorVariableOptions, 'nucleic_type', 'Nucleic type');
            $this->appendUniqueMapColorOption($mapColorVariableOptions, 'nucleic_extraction_protocol', 'Extraction protocol');
            $this->appendDerivedTraceChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $base,
                $tracePrimaryAnimalSpeciesOptions,
                $tracePrimaryAnimalSexesOptions,
                $tracePrimaryAnimalAgesOptions,
                $tracePrimaryHumanEthnicitiesOptions,
                $tracePrimaryHumanOccupationsOptions,
                $tracePrimaryHumanCountriesOptions,
                $traceDeepAnimalSpeciesOptions,
                $traceDeepAnimalSexesOptions,
                $traceDeepAnimalAgesOptions,
                $traceDeepHumanEthnicitiesOptions,
                $traceDeepHumanOccupationsOptions,
                $traceDeepHumanCountriesOptions,
                $allTraceParasiteSpecies,
                $allTraceParasiteStages,
                $allTraceParasiteSexes,
                $allTraceCultureTypes,
                $allTraceCultureMediums
            );
            $this->appendSamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $base);
        }

        if ($normalizedType === 'Cultures') {
            $this->appendCultureChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $this->groupedCultureTypeDistribution($base),
                $this->groupedCultureMediumDistribution($base)
            );
            $this->appendDerivedTraceChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $base,
                $tracePrimaryAnimalSpeciesOptions,
                $tracePrimaryAnimalSexesOptions,
                $tracePrimaryAnimalAgesOptions,
                $tracePrimaryHumanEthnicitiesOptions,
                $tracePrimaryHumanOccupationsOptions,
                $tracePrimaryHumanCountriesOptions,
                $traceDeepAnimalSpeciesOptions,
                $traceDeepAnimalSexesOptions,
                $traceDeepAnimalAgesOptions,
                $traceDeepHumanEthnicitiesOptions,
                $traceDeepHumanOccupationsOptions,
                $traceDeepHumanCountriesOptions,
                $allTraceParasiteSpecies,
                $allTraceParasiteStages,
                $allTraceParasiteSexes,
                $allTraceCultureTypes,
                $allTraceCultureMediums
            );
            $this->appendSamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $base);
        }

        if ($normalizedType === 'Pools') {
            $this->appendUniqueChartTab($barChartTabs, 'pool_nr_pooled', 'Pool Size', $this->poolSizeDistribution($base));
            $this->appendUniqueMapColorOption($mapColorVariableOptions, 'pool_nr_pooled', 'Pool size');
            $this->appendDerivedTraceChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $base,
                $tracePrimaryAnimalSpeciesOptions,
                $tracePrimaryAnimalSexesOptions,
                $tracePrimaryAnimalAgesOptions,
                $tracePrimaryHumanEthnicitiesOptions,
                $tracePrimaryHumanOccupationsOptions,
                $tracePrimaryHumanCountriesOptions,
                $traceDeepAnimalSpeciesOptions,
                $traceDeepAnimalSexesOptions,
                $traceDeepAnimalAgesOptions,
                $traceDeepHumanEthnicitiesOptions,
                $traceDeepHumanOccupationsOptions,
                $traceDeepHumanCountriesOptions,
                $allTraceParasiteSpecies,
                $allTraceParasiteStages,
                $allTraceParasiteSexes,
                $allTraceCultureTypes,
                $allTraceCultureMediums
            );
            $this->appendSamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $base);
        }
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     */
    private function appendSamplingSiteChartTabs(
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        $base
    ): void {
        $this->appendUniqueChartTab(
            $barChartTabs,
            'sampling_site',
            'Sampling Site',
            $this->groupedSamplingSiteDistribution($base)
        );
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'sampling_site', 'Sampling site');
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     */
    private function appendTracePrimarySamplingSiteChartTabs(
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        string $traceKey
    ): void {
        $label = match ($traceKey) {
            'human' => 'Sampling Site (trace human)',
            'animal' => 'Sampling Site (trace animal)',
            'environment' => 'Sampling Site (trace environment)',
            default => 'Sampling Site (trace)',
        };

        $this->appendUniqueChartTab(
            $barChartTabs,
            "trace_primary_{$traceKey}_sampling_site",
            $label,
            $this->groupedTracePrimarySamplingSiteDistribution($traceKey)
        );
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'sampling_site', 'Sampling site');
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $pieChartTabs
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     */
    private function appendDerivedTraceChartTabs(
        array &$pieChartTabs,
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        $base,
        $tracePrimaryAnimalSpeciesOptions,
        $tracePrimaryAnimalSexesOptions,
        $tracePrimaryAnimalAgesOptions,
        $tracePrimaryHumanEthnicitiesOptions,
        $tracePrimaryHumanOccupationsOptions,
        $tracePrimaryHumanCountriesOptions,
        $traceDeepAnimalSpeciesOptions,
        $traceDeepAnimalSexesOptions,
        $traceDeepAnimalAgesOptions,
        $traceDeepHumanEthnicitiesOptions,
        $traceDeepHumanOccupationsOptions,
        $traceDeepHumanCountriesOptions,
        $allTraceParasiteSpecies,
        $allTraceParasiteStages,
        $allTraceParasiteSexes,
        $allTraceCultureTypes,
        $allTraceCultureMediums
    ): void {
        foreach ($this->activeTracePrimaryKeys() as $traceKey) {
            if ($traceKey === 'animal') {
                $this->appendAnimalChartTabs(
                    $pieChartTabs,
                    $barChartTabs,
                    $mapColorVariableOptions,
                    $this->groupedTracePrimaryAnimalSpeciesDistribution('animal'),
                    $this->groupedTracePrimaryAnimalSexDistribution('animal'),
                    $this->groupedTracePrimaryAnimalAgeDistribution('animal')
                );
            }

            if ($traceKey === 'human') {
                $this->appendHumanChartTabs(
                    $pieChartTabs,
                    $barChartTabs,
                    $mapColorVariableOptions,
                    $this->groupedTracePrimaryHumanEthnicityDistribution('human'),
                    $this->groupedTracePrimaryHumanOccupationDistribution('human'),
                    $this->groupedTracePrimaryHumanCountryDistribution('human')
                );
            }

            if (in_array($traceKey, ['human', 'animal', 'environment'], true)) {
                $this->appendTracePrimarySamplingSiteChartTabs($barChartTabs, $mapColorVariableOptions, $traceKey);
            }

            if ($traceKey === 'parasite') {
                $this->appendParasiteChartTabs(
                    $pieChartTabs,
                    $barChartTabs,
                    $mapColorVariableOptions,
                    $this->groupedTracePrimaryParasiteSpeciesDistribution('parasite'),
                    $this->groupedTracePrimaryParasiteStageDistribution('parasite'),
                    $this->groupedTracePrimaryParasiteSexDistribution('parasite'),
                    []
                );
            }

            if ($traceKey === 'culture') {
                $this->appendCultureChartTabs(
                    $pieChartTabs,
                    $barChartTabs,
                    $mapColorVariableOptions,
                    $this->groupedTracePrimaryCultureTypeDistribution('culture'),
                    $this->groupedTracePrimaryCultureMediumDistribution('culture')
                );
            }

            if ($traceKey === 'pool') {
                $this->appendUniqueChartTab(
                    $barChartTabs,
                    'pool_nr_pooled',
                    'Pool Size (trace)',
                    $this->groupedTracePrimaryPoolSizeDistribution('pool')
                );
            }
        }

        if ($this->traceDeepPrimaryTypeFilter === 'animal') {
            $this->appendAnimalChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $this->groupedTraceDeepAnimalSpeciesDistribution(),
                $this->groupedTraceDeepAnimalSexDistribution(),
                $this->groupedTraceDeepAnimalAgeDistribution()
            );
        }

        if ($this->traceDeepPrimaryTypeFilter === 'human') {
            $this->appendHumanChartTabs(
                $pieChartTabs,
                $barChartTabs,
                $mapColorVariableOptions,
                $this->groupedTraceDeepHumanEthnicityDistribution(),
                $this->groupedTraceDeepHumanOccupationDistribution(),
                $this->groupedTraceDeepHumanCountryDistribution()
            );
        }
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $tabs
     * @param  array<string, int>  $data
     */
    private function appendUniqueChartTab(array &$tabs, string $key, string $label, array $data): void
    {
        if ($data === []) {
            return;
        }

        foreach ($tabs as $tab) {
            if (($tab['key'] ?? '') === $key) {
                return;
            }
        }

        $tabs[] = ['key' => $key, 'label' => $label, 'data' => $data];
    }

    /**
     * @param  array<int, array{key:string,label:string}>  $options
     */
    private function appendUniqueMapColorOption(array &$options, string $key, string $label): void
    {
        foreach ($options as $option) {
            if (($option['key'] ?? '') === $key) {
                return;
            }
        }

        $options[] = ['key' => $key, 'label' => $label];
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $pieChartTabs
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     * @param  array<string, int>  $speciesData
     * @param  array<string, int>  $sexData
     * @param  array<string, int>  $ageData
     */
    private function appendAnimalChartTabs(
        array &$pieChartTabs,
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        array $speciesData,
        array $sexData,
        array $ageData
    ): void {
        $this->appendUniqueChartTab($pieChartTabs, 'animal_species', 'Animal Species', $speciesData);
        $this->appendUniqueChartTab($barChartTabs, 'animal_sex', 'Animal Sex', $sexData);
        $this->appendUniqueChartTab($barChartTabs, 'animal_age', 'Animal Age', $ageData);
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'animal_species', 'Animal species');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'animal_sex', 'Animal sex');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'animal_age', 'Animal age');
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $pieChartTabs
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     * @param  array<string, int>  $ethnicityData
     * @param  array<string, int>  $occupationData
     * @param  array<string, int>  $countryData
     */
    private function appendHumanChartTabs(
        array &$pieChartTabs,
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        array $ethnicityData,
        array $occupationData,
        array $countryData
    ): void {
        $this->appendUniqueChartTab($pieChartTabs, 'human_ethnicity', 'Human Ethnicity', $ethnicityData);
        $this->appendUniqueChartTab($barChartTabs, 'human_occupation', 'Human Occupation', $occupationData);
        $this->appendUniqueChartTab($barChartTabs, 'human_country', 'Human Country', $countryData);
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'human_ethnicity', 'Human ethnicity');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'human_occupation', 'Human occupation');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'human_country', 'Human country');
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $pieChartTabs
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     * @param  array<string, int>  $speciesData
     * @param  array<string, int>  $stageData
     * @param  array<string, int>  $sexData
     * @param  array<string, int>  $stateData
     */
    private function appendParasiteChartTabs(
        array &$pieChartTabs,
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        array $speciesData,
        array $stageData,
        array $sexData,
        array $stateData
    ): void {
        $this->appendUniqueChartTab($pieChartTabs, 'parasite_species', 'Parasite Species', $speciesData);
        $this->appendUniqueChartTab($barChartTabs, 'parasite_stage', 'Parasite Stage', $stageData);
        $this->appendUniqueChartTab($barChartTabs, 'parasite_sex', 'Parasite Sex', $sexData);
        $this->appendUniqueChartTab($barChartTabs, 'parasite_state', 'Parasite State', $stateData);
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'parasite_species', 'Parasite species');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'parasite_stage', 'Parasite stage');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'parasite_sex', 'Parasite sex');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'parasite_state', 'Parasite state');
    }

    /**
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $pieChartTabs
     * @param  array<int, array{key:string,label:string,data:array<string,int>}>  $barChartTabs
     * @param  array<int, array{key:string,label:string}>  $mapColorVariableOptions
     * @param  array<string, int>  $typeData
     * @param  array<string, int>  $mediumData
     */
    private function appendCultureChartTabs(
        array &$pieChartTabs,
        array &$barChartTabs,
        array &$mapColorVariableOptions,
        array $typeData,
        array $mediumData
    ): void {
        $this->appendUniqueChartTab($pieChartTabs, 'culture_type', 'Culture Type', $typeData);
        $this->appendUniqueChartTab($barChartTabs, 'culture_medium', 'Culture Medium', $mediumData);
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'culture_type', 'Culture type');
        $this->appendUniqueMapColorOption($mapColorVariableOptions, 'culture_medium', 'Culture medium');
    }

    private function groupedAnimalAgeDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'AnimalSamples') {
            return [];
        }

        $variants = $this->typeVariants(AnimalSamples::class);

        return (clone $base)
            ->join('animal_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'animal_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->select(DB::raw('CAST(animals.age AS CHAR) as label'), DB::raw('count(*) as total'))
            ->groupBy('animals.age')
            ->orderBy('animals.age')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedHumanEthnicityDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'HumanSamples') {
            return [];
        }

        $variants = $this->typeVariants(HumanSamples::class);

        return (clone $base)
            ->join('human_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'human_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->select('humans.ethnicity as label', DB::raw('count(*) as total'))
            ->groupBy('humans.ethnicity')
            ->orderBy('humans.ethnicity')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedHumanOccupationDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'HumanSamples') {
            return [];
        }

        $variants = $this->typeVariants(HumanSamples::class);

        return (clone $base)
            ->join('human_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'human_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->select('humans.occupation as label', DB::raw('count(*) as total'))
            ->groupBy('humans.occupation')
            ->orderBy('humans.occupation')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedHumanCountryDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'HumanSamples') {
            return [];
        }

        $variants = $this->typeVariants(HumanSamples::class);

        return (clone $base)
            ->join('human_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'human_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->select('countries.name as label', DB::raw('count(*) as total'))
            ->groupBy('countries.name')
            ->orderBy('countries.name')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedEnvironmentSampleTypeDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'EnvironmentSamples') {
            return [];
        }

        $variants = $this->typeVariants(EnvironmentSamples::class);

        return (clone $base)
            ->join('environment_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'environment_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
            ->whereNotNull('environment_sample_types.name')
            ->select('environment_sample_types.name as label', DB::raw('count(*) as total'))
            ->groupBy('environment_sample_types.name')
            ->orderBy('environment_sample_types.name')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedParasiteStateDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return [];
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return (clone $base)
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.state')
            ->select('parasites.state as label', DB::raw('count(*) as total'))
            ->groupBy('parasites.state')
            ->orderBy('parasites.state')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    private function activeTracePrimaryKeys(): array
    {
        if ($this->tracePrimaryTypeFilter !== 'all') {
            return [$this->tracePrimaryTypeFilter];
        }

        return array_keys($this->availableTraceTypes());
    }

    private function groupedNucleicExtractionProtocolDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'NucleicAcids') {
            return [];
        }

        $variants = $this->typeVariants(NucleicAcids::class);

        return (clone $base)
            ->join('nucleic_acids', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'nucleic_acids.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->leftJoin('protocols as nucleic_extraction_protocols', 'nucleic_acids.protocols_id', '=', 'nucleic_extraction_protocols.id')
            ->whereNotNull('nucleic_extraction_protocols.name')
            ->select('nucleic_extraction_protocols.name as label', DB::raw('count(*) as total'))
            ->groupBy('nucleic_extraction_protocols.name')
            ->orderByDesc('total')
            ->limit(20)
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @param  array<int, string>  $except
     */
    private function baseForTraceChartDistribution(string $traceType, array $except = []): Builder
    {
        if ($this->tracePrimaryTypeFilter === 'all') {
            $originalTraceType = $this->tracePrimaryTypeFilter;
            $this->tracePrimaryTypeFilter = $traceType;
            $query = $this->baseFilteredQuery($except);
            $this->tracePrimaryTypeFilter = $originalTraceType;

            return $query;
        }

        return $this->baseFilteredQuery($except);
    }

    /**
     * @param  array<string, int>  ...$maps
     * @return array<string, int>
     */
    private function mergeDistributionMaps(array ...$maps): array
    {
        $merged = [];

        foreach ($maps as $map) {
            foreach ($map as $label => $count) {
                $merged[$label] = ($merged[$label] ?? 0) + (int) $count;
            }
        }

        return $merged;
    }

    private function groupedAnimalSpeciesDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'AnimalSamples') {
            return [];
        }

        $variants = $this->typeVariants(AnimalSamples::class);

        return (clone $base)
            ->join('animal_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'animal_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->select('animal_species.name_common as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('animal_species.name_common')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedAnimalSexDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'AnimalSamples') {
            return [];
        }

        $variants = $this->typeVariants(AnimalSamples::class);

        return (clone $base)
            ->join('animal_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'animal_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->select('animals.sex as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('animals.sex')
            ->orderBy('animals.sex')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedParasiteSpeciesDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return [];
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return (clone $base)
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.name_scientific')
            ->select('parasite_species.name_scientific as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('parasite_species.name_scientific')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedParasiteStageDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return [];
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return (clone $base)
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.stage')
            ->select('parasites.stage as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('parasites.stage')
            ->orderBy('parasites.stage')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedParasiteSexDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'ParasiteSamples') {
            return [];
        }

        $variants = $this->typeVariants(ParasiteSamples::class);

        return (clone $base)
            ->join('parasite_samples', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'parasite_samples.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.sex')
            ->select('parasites.sex as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('parasites.sex')
            ->orderBy('parasites.sex')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedCultureTypeDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'Cultures') {
            return [];
        }

        $variants = $this->typeVariants(Cultures::class);

        return (clone $base)
            ->join('cultures', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'cultures.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->whereNotNull('cultures.type')
            ->select('cultures.type as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('cultures.type')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedCultureMediumDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'Cultures') {
            return [];
        }

        $variants = $this->typeVariants(Cultures::class);

        return (clone $base)
            ->join('cultures', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'cultures.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->whereNotNull('cultures.medium')
            ->select('cultures.medium as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('cultures.medium')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedNucleicTypeDistribution($base): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'NucleicAcids') {
            return [];
        }

        $variants = $this->typeVariants(NucleicAcids::class);

        return (clone $base)
            ->join('nucleic_acids', function ($join) use ($variants) {
                $join->on('experiments.experiments_content_id', '=', 'nucleic_acids.id')
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->whereNotNull('nucleic_acids.type')
            ->select('nucleic_acids.type as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('nucleic_acids.type')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @return Builder|null
     */
    private function joinTracePrimaryAnimalOnExperiments($query)
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return null;
        }

        $q = clone $query;

        if ($target === 'NucleicAcids') {
            return $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'nucleic_acids.nucleic_content_id', '=', 'animal_samples.id');
        }

        if ($target === 'Cultures') {
            return $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id');
        }

        if ($target === 'Pools') {
            return $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'pool_contents.samples_id', '=', 'animal_samples.id');
        }

        return $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
            ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id');
    }

    /**
     * @return Builder|null
     */
    private function joinTracePrimaryHumanOnExperiments($query)
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return null;
        }

        $q = clone $query;

        if ($target === 'NucleicAcids') {
            return $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'nucleic_acids.nucleic_content_id', '=', 'human_samples.id');
        }

        if ($target === 'Cultures') {
            return $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id');
        }

        if ($target === 'Pools') {
            return $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'pool_contents.samples_id', '=', 'human_samples.id');
        }

        return $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
            ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id');
    }

    /**
     * @return Builder|null
     */
    private function joinTracePrimaryParasiteOnExperiments($query)
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return null;
        }

        $q = clone $query;

        if ($target === 'NucleicAcids') {
            return $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id');
        }

        if ($target === 'Cultures') {
            return $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id');
        }

        return $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
            ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
            ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id');
    }

    /**
     * @return Builder|null
     */
    private function joinTracePrimaryCultureOnExperiments($query)
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Pools'], true)) {
            return null;
        }

        $q = clone $query;

        if ($target === 'NucleicAcids') {
            return $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id');
        }

        return $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
            ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
            ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id');
    }

    private function groupedTracePrimaryAnimalSpeciesDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryAnimalOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryAnimalSpeciesFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->select('animal_species.name_common as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('animal_species.name_common')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryAnimalSexDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryAnimalOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryAnimalSexFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->select('animals.sex as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('animals.sex')
            ->orderBy('animals.sex')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryAnimalAgeDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryAnimalOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryAnimalAgeFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->select(DB::raw('CAST(animals.age AS CHAR) as label'), DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('animals.age')
            ->orderBy('animals.age')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryHumanEthnicityDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryHumanOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryHumanEthnicityFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->select('humans.ethnicity as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('humans.ethnicity')
            ->orderBy('humans.ethnicity')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryHumanOccupationDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryHumanOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryHumanOccupationFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->select('humans.occupation as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('humans.occupation')
            ->orderBy('humans.occupation')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryHumanCountryDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryHumanOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryHumanCountryFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->select('countries.name as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('countries.name')
            ->orderBy('countries.name')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryParasiteSpeciesDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryParasiteOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryParasiteSpeciesFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.name_scientific')
            ->select('parasite_species.name_scientific as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('parasite_species.name_scientific')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryParasiteStageDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryParasiteOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryParasiteStageFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.stage')
            ->select('parasites.stage as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('parasites.stage')
            ->orderBy('parasites.stage')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryParasiteSexDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryParasiteOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryParasiteSexFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereNotNull('parasites.sex')
            ->select('parasites.sex as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('parasites.sex')
            ->orderBy('parasites.sex')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryCultureTypeDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryCultureOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryCultureTypeFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->whereNotNull('cultures.type')
            ->select('cultures.type as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('cultures.type')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryCultureMediumDistribution(string $traceType): array
    {
        $joined = $this->joinTracePrimaryCultureOnExperiments(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryCultureMediumFilter'])
        );
        if (! $joined) {
            return [];
        }

        return $joined
            ->whereNotNull('cultures.medium')
            ->select('cultures.medium as label', DB::raw('count(distinct experiments.id) as total'))
            ->groupBy('cultures.medium')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function groupedTracePrimaryPoolSizeDistribution(string $traceType): array
    {
        return $this->poolSizeDistribution(
            $this->baseForTraceChartDistribution($traceType, ['tracePrimaryPoolMinNrPooled', 'tracePrimaryPoolMaxNrPooled'])
        );
    }

    private function groupedTraceDeepAnimalSpeciesDistribution(): array
    {
        return $this->mergeDistributionMaps(...$this->traceDeepAnimalDistributionQueries(
            'animal_species.name_common',
            ['traceDeepAnimalSpeciesFilter'],
            'animal_species.name_common'
        ));
    }

    private function groupedTraceDeepAnimalSexDistribution(): array
    {
        return $this->mergeDistributionMaps(...$this->traceDeepAnimalDistributionQueries(
            'animals.sex',
            ['traceDeepAnimalSexFilter'],
            'animals.sex'
        ));
    }

    private function groupedTraceDeepAnimalAgeDistribution(): array
    {
        return $this->mergeDistributionMaps(...$this->traceDeepAnimalDistributionQueries(
            'CAST(animals.age AS CHAR)',
            ['traceDeepAnimalAgeFilter'],
            'animals.age'
        ));
    }

    private function groupedTraceDeepHumanEthnicityDistribution(): array
    {
        return $this->mergeDistributionMaps(...$this->traceDeepHumanDistributionQueries(
            'humans.ethnicity',
            ['traceDeepHumanEthnicityFilter'],
            'humans.ethnicity'
        ));
    }

    private function groupedTraceDeepHumanOccupationDistribution(): array
    {
        return $this->mergeDistributionMaps(...$this->traceDeepHumanDistributionQueries(
            'humans.occupation',
            ['traceDeepHumanOccupationFilter'],
            'humans.occupation'
        ));
    }

    private function groupedTraceDeepHumanCountryDistribution(): array
    {
        return $this->mergeDistributionMaps(...$this->traceDeepHumanDistributionQueries(
            'countries.name',
            ['traceDeepHumanCountryFilter'],
            'countries.name'
        ));
    }

    /**
     * @param  array<int, string>  $except
     * @return array<int, array<string, int>>
     */
    private function traceDeepAnimalDistributionQueries(string $labelSelect, array $except, string $groupBy): array
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal') {
            return [];
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return [];
        }

        $base = $this->baseFilteredQuery($except);
        $maps = [];

        $appendAnimalMap = function ($query) use (&$maps, $labelSelect, $groupBy): void {
            $distributionQuery = $query
                ->join('animals', 'animal_samples.animals_id', '=', 'animals.id');

            if ($groupBy === 'animal_species.name_common') {
                $distributionQuery
                    ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                    ->whereNotNull('animal_species.name_common');
            } else {
                $distributionQuery->whereNotNull($groupBy);
            }

            $maps[] = $distributionQuery
                ->select(DB::raw("{$labelSelect} as label"), DB::raw('count(distinct experiments.id) as total'))
                ->groupBy($groupBy)
                ->pluck('total', 'label')
                ->map(fn ($count) => (int) $count)
                ->toArray();
        };

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'parasite') {
            $appendAnimalMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id'));
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'culture') {
            $appendAnimalMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id'));

            $appendAnimalMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id'));
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'pool') {
            $appendAnimalMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'pool_contents.samples_id', '=', 'animal_samples.id'));

            $appendAnimalMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id'));
        }

        if ($target === 'Cultures' && $this->tracePrimaryTypeFilter === 'parasite') {
            $appendAnimalMap((clone $base)
                ->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id'));
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'parasite') {
            $appendAnimalMap((clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id'));
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'culture') {
            $appendAnimalMap((clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id'));

            $appendAnimalMap((clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id'));
        }

        return $maps;
    }

    /**
     * @param  array<int, string>  $except
     * @return array<int, array<string, int>>
     */
    private function traceDeepHumanDistributionQueries(string $labelSelect, array $except, string $groupBy): array
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'human') {
            return [];
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return [];
        }

        $base = $this->baseFilteredQuery($except);
        $maps = [];

        $appendHumanMap = function ($query) use (&$maps, $labelSelect, $groupBy): void {
            $maps[] = $query
                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->when($groupBy === 'countries.name', fn ($q) => $q->leftJoin('countries', 'humans.countries_id', '=', 'countries.id'))
                ->whereNotNull($groupBy)
                ->select("{$labelSelect} as label", DB::raw('count(distinct experiments.id) as total'))
                ->groupBy($groupBy)
                ->pluck('total', 'label')
                ->map(fn ($count) => (int) $count)
                ->toArray();
        };

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'parasite') {
            $appendHumanMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id'));
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'culture') {
            $appendHumanMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id'));

            $appendHumanMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id'));
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'pool') {
            $appendHumanMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'pool_contents.samples_id', '=', 'human_samples.id'));

            $appendHumanMap((clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id'));
        }

        if ($target === 'Cultures' && $this->tracePrimaryTypeFilter === 'parasite') {
            $appendHumanMap((clone $base)
                ->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id'));
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'parasite') {
            $appendHumanMap((clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id'));
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'culture') {
            $appendHumanMap((clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id'));

            $appendHumanMap((clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id'));
        }

        return $maps;
    }

    private function contentTypeDistribution($base): array
    {
        return (clone $base)
            ->select('experiments_content_type', DB::raw('count(*) as total'))
            ->groupBy('experiments_content_type')
            ->get()
            ->mapWithKeys(function ($row) {
                return [class_basename((string) $row->experiments_content_type) => (int) $row->total];
            })
            ->toArray();
    }

    private function poolSizeDistribution($base): array
    {
        return (clone $base)
            ->join('pools', 'experiments.experiments_content_id', '=', 'pools.id')
            ->whereIn('experiments.experiments_content_type', $this->typeVariants(Pools::class))
            ->select(DB::raw('CAST(pools.nr_pooled AS CHAR) as pool_size'), DB::raw('count(*) as total'))
            ->groupBy('pool_size')
            ->orderBy('pool_size')
            ->pluck('total', 'pool_size')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * When experiment type is Pools, trace-to-primary depends on `pool_contents`.
     * If a pool has no linked contents, trace options will be empty for that pool.
     *
     * @return array<int,string> pool codes
     */
    private function poolsWithMissingPoolContentsForFilteredExperiments(): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'Pools') {
            return [];
        }

        // Use the filtered dataset but ignore trace filters (we're diagnosing trace availability).
        $base = $this->baseFilteredQuery([
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryParasiteStageFilter',
            'tracePrimaryParasiteSexFilter',
            'tracePrimaryParasiteStateFilter',
            'tracePrimaryParasiteSampleTypeFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
        ]);

        $poolIds = (clone $base)
            ->select('experiments.experiments_content_id')
            ->distinct()
            ->limit(500)
            ->pluck('experiments.experiments_content_id')
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        if ($poolIds === []) {
            return [];
        }

        $poolIdsWithContents = DB::table('pool_contents')
            ->whereIn('pool_contents.pools_id', $poolIds)
            ->select('pool_contents.pools_id')
            ->distinct()
            ->pluck('pool_contents.pools_id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $missingIds = array_values(array_diff($poolIds, $poolIdsWithContents));
        if ($missingIds === []) {
            return [];
        }

        return Pools::query()
            ->whereIn('pools.id', $missingIds)
            ->orderBy('pools.code')
            ->pluck('pools.code')
            ->map(fn ($v) => (string) $v)
            ->take(5)
            ->values()
            ->all();
    }

    private function traceDeepAnimalSpeciesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery(['traceDeepAnimalSpeciesFilter']);
        $animalSampleIds = $this->deepAnimalSampleIdsFromFilteredDataset($base, $target);
        if ($animalSampleIds->isEmpty()) {
            return collect();
        }

        return AnimalSamples::query()
            ->whereIn('animal_samples.id', $animalSampleIds)
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->distinct()
            ->orderBy('animal_species.name_common')
            ->pluck('animal_species.name_common')
            ->filter()
            ->values();
    }

    private function traceDeepAnimalSexesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery(['traceDeepAnimalSexFilter']);
        $animalSampleIds = $this->deepAnimalSampleIdsFromFilteredDataset($base, $target);
        if ($animalSampleIds->isEmpty()) {
            return collect();
        }

        return AnimalSamples::query()
            ->whereIn('animal_samples.id', $animalSampleIds)
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->distinct()
            ->orderBy('animals.sex')
            ->pluck('animals.sex')
            ->filter()
            ->values();
    }

    private function traceDeepAnimalAgesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery(['traceDeepAnimalAgeFilter']);
        $animalSampleIds = $this->deepAnimalSampleIdsFromFilteredDataset($base, $target);
        if ($animalSampleIds->isEmpty()) {
            return collect();
        }

        return AnimalSamples::query()
            ->whereIn('animal_samples.id', $animalSampleIds)
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->distinct()
            ->orderBy('animals.age')
            ->pluck('animals.age')
            ->filter()
            ->values();
    }

    private function traceDeepHumanEthnicitiesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'human') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery(['traceDeepHumanEthnicityFilter']);
        $humanSampleIds = $this->deepHumanSampleIdsFromFilteredDataset($base, $target);
        if ($humanSampleIds->isEmpty()) {
            return collect();
        }

        return HumanSamples::query()
            ->whereIn('human_samples.id', $humanSampleIds)
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->distinct()
            ->orderBy('humans.ethnicity')
            ->pluck('humans.ethnicity')
            ->filter()
            ->values();
    }

    private function traceDeepHumanOccupationsOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'human') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery(['traceDeepHumanOccupationFilter']);
        $humanSampleIds = $this->deepHumanSampleIdsFromFilteredDataset($base, $target);
        if ($humanSampleIds->isEmpty()) {
            return collect();
        }

        return HumanSamples::query()
            ->whereIn('human_samples.id', $humanSampleIds)
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->distinct()
            ->orderBy('humans.occupation')
            ->pluck('humans.occupation')
            ->filter()
            ->values();
    }

    private function traceDeepHumanCountriesOptions()
    {
        if ($this->traceDeepPrimaryTypeFilter !== 'human') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery(['traceDeepHumanCountryFilter']);
        $humanSampleIds = $this->deepHumanSampleIdsFromFilteredDataset($base, $target);
        if ($humanSampleIds->isEmpty()) {
            return collect();
        }

        return HumanSamples::query()
            ->whereIn('human_samples.id', $humanSampleIds)
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->distinct()
            ->orderBy('countries.name')
            ->pluck('countries.name')
            ->filter()
            ->values();
    }

    private function deepAnimalSampleIdsFromFilteredDataset($base, string $target)
    {
        if (! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'pool', 'nucleic'], true)) {
            return collect();
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'parasite') {
            return (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'culture') {
            $direct = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('cultures.cultures_content_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            $viaParasite = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            return $direct->merge($viaParasite)->unique()->values();
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'pool') {
            $direct = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('pool_contents.samples_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            $viaParasite = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            return $direct->merge($viaParasite)->unique()->values();
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'culture') {
            $direct = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('cultures.cultures_content_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            $viaParasite = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            return $direct->merge($viaParasite)->unique()->values();
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'parasite') {
            return (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();
        }

        if ($target === 'Cultures' && $this->tracePrimaryTypeFilter === 'parasite') {
            return (clone $base)
                ->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();
        }

        return collect();
    }

    private function deepHumanSampleIdsFromFilteredDataset($base, string $target)
    {
        if (! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'pool', 'nucleic'], true)) {
            return collect();
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'parasite') {
            return (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'culture') {
            $direct = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('cultures.cultures_content_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            $viaParasite = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            return $direct->merge($viaParasite)->unique()->values();
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'pool') {
            $direct = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('pool_contents.samples_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            $viaParasite = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            return $direct->merge($viaParasite)->unique()->values();
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'culture') {
            $direct = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('cultures.cultures_content_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            $viaParasite = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();

            return $direct->merge($viaParasite)->unique()->values();
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'parasite') {
            return (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();
        }

        if ($target === 'Cultures' && $this->tracePrimaryTypeFilter === 'parasite') {
            return (clone $base)
                ->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->distinct()
                ->pluck('parasites.parasites_origin_id')
                ->map(fn ($v) => (int) $v)
                ->values();
        }

        return collect();
    }

    private function tracePrimaryAnimalSpeciesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryAnimalSpeciesFilter',
        ]);

        $q = (clone $base);

        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id');
            $q->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'nucleic_acids.nucleic_content_id', '=', 'animal_samples.id');
        } elseif ($target === 'Cultures') {
            $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id');
            $q->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id');
        } elseif ($target === 'Pools') {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'pool_contents.samples_id', '=', 'animal_samples.id');
        } elseif ($target === 'ParasiteSamples') {
            $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id');
        }

        return $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common')
            ->distinct()
            ->orderBy('animal_species.name_common')
            ->pluck('animal_species.name_common')
            ->filter()
            ->values();
    }

    private function tracePrimaryAnimalSexesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryAnimalSexFilter',
        ]);

        $q = (clone $base);
        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'nucleic_acids.nucleic_content_id', '=', 'animal_samples.id');
        } elseif ($target === 'Cultures') {
            $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id');
        } elseif ($target === 'Pools') {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'pool_contents.samples_id', '=', 'animal_samples.id');
        } else {
            $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id');
        }

        return $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->distinct()
            ->orderBy('animals.sex')
            ->pluck('animals.sex')
            ->filter()
            ->values();
    }

    private function tracePrimaryAnimalAgesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryAnimalAgeFilter',
        ]);

        $q = (clone $base);
        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'nucleic_acids.nucleic_content_id', '=', 'animal_samples.id');
        } elseif ($target === 'Cultures') {
            $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'cultures.cultures_content_id', '=', 'animal_samples.id');
        } elseif ($target === 'Pools') {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'pool_contents.samples_id', '=', 'animal_samples.id');
        } else {
            $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->join('animal_samples', 'parasites.parasites_origin_id', '=', 'animal_samples.id');
        }

        return $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->distinct()
            ->orderBy('animals.age')
            ->pluck('animals.age')
            ->filter()
            ->values();
    }

    private function tracePrimaryHumanEthnicitiesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryHumanEthnicityFilter',
        ]);

        $q = (clone $base);
        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'nucleic_acids.nucleic_content_id', '=', 'human_samples.id');
        } elseif ($target === 'Cultures') {
            $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id');
        } elseif ($target === 'Pools') {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'pool_contents.samples_id', '=', 'human_samples.id');
        } else {
            $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id');
        }

        return $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->distinct()
            ->orderBy('humans.ethnicity')
            ->pluck('humans.ethnicity')
            ->filter()
            ->values();
    }

    private function tracePrimaryHumanOccupationsOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryHumanOccupationFilter',
        ]);

        $q = (clone $base);
        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'nucleic_acids.nucleic_content_id', '=', 'human_samples.id');
        } elseif ($target === 'Cultures') {
            $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id');
        } elseif ($target === 'Pools') {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'pool_contents.samples_id', '=', 'human_samples.id');
        } else {
            $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id');
        }

        return $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->distinct()
            ->orderBy('humans.occupation')
            ->pluck('humans.occupation')
            ->filter()
            ->values();
    }

    private function tracePrimaryHumanCountriesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return collect();
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryHumanCountryFilter',
        ]);

        $q = (clone $base);
        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'nucleic_acids.nucleic_content_id', '=', 'human_samples.id');
        } elseif ($target === 'Cultures') {
            $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'cultures.cultures_content_id', '=', 'human_samples.id');
        } elseif ($target === 'Pools') {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'pool_contents.samples_id', '=', 'human_samples.id');
        } else {
            $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->join('human_samples', 'parasites.parasites_origin_id', '=', 'human_samples.id');
        }

        return $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->distinct()
            ->orderBy('countries.name')
            ->pluck('countries.name')
            ->filter()
            ->values();
    }

    private function traceJoinTableForTarget(string $target): string
    {
        return match ($target) {
            'NucleicAcids' => 'nucleic_acids',
            'Cultures' => 'cultures',
            default => 'experiments', // unused
        };
    }

    private function traceJoinKeyForTarget(string $target): string
    {
        return match ($target) {
            'NucleicAcids' => 'nucleic_acids.id',
            'Cultures' => 'cultures.id',
            default => 'experiments.id', // unused
        };
    }

    /**
     * When tracing from a derived sample (parasite/culture/nucleic/pool), show only primary types
     * that actually exist upstream in the *currently filtered dataset*.
     *
     * @return array<string,string> key => label
     */
    private function availableDeepPrimaryTypes(): array
    {
        if (! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return [];
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return [];
        }

        $withoutDeep = [
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
        ];

        $base = $this->baseFilteredQuery($withoutDeep);

        $rawTypes = collect();

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'parasite') {
            $rawTypes = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'culture') {
            $direct = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereNotNull('cultures.cultures_content_type')
                ->distinct()
                ->pluck('cultures.cultures_content_type');

            $viaParasite = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');

            $rawTypes = $direct->merge($viaParasite);
        }

        if ($target === 'NucleicAcids' && $this->tracePrimaryTypeFilter === 'pool') {
            $direct = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereNotNull('pool_contents.samples_type')
                ->distinct()
                ->pluck('pool_contents.samples_type');

            $viaParasite = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->join('pools', 'nucleic_acids.nucleic_content_id', '=', 'pools.id')
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'pools.id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');

            $rawTypes = $direct->merge($viaParasite);
        }

        if ($target === 'Cultures' && in_array($this->tracePrimaryTypeFilter, ['parasite', 'nucleic'], true)) {
            // Cultures experiments: deep primary is defined by cultures.cultures_content_type
            // (for parasite) or nucleic_acids.nucleic_content_type (for nucleic).
            if ($this->tracePrimaryTypeFilter === 'parasite') {
                $rawTypes = (clone $base)
                    ->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                    ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                    ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                    ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                    ->whereNotNull('parasites.parasites_origin_type')
                    ->distinct()
                    ->pluck('parasites.parasites_origin_type');
            } else {
                $rawTypes = (clone $base)
                    ->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                    ->whereIn('cultures.cultures_content_type', $this->typeVariants(NucleicAcids::class))
                    ->join('nucleic_acids', 'cultures.cultures_content_id', '=', 'nucleic_acids.id')
                    ->whereNotNull('nucleic_acids.nucleic_content_type')
                    ->distinct()
                    ->pluck('nucleic_acids.nucleic_content_type');
            }
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'parasite') {
            $rawTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');
        }

        if ($target === 'Pools' && $this->tracePrimaryTypeFilter === 'culture') {
            $direct = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereNotNull('cultures.cultures_content_type')
                ->distinct()
                ->pluck('cultures.cultures_content_type');

            $viaParasite = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');

            $rawTypes = $direct->merge($viaParasite);
        }

        $map = [
            'HumanSamples' => ['human', 'Human'],
            'AnimalSamples' => ['animal', 'Animal'],
            'EnvironmentSamples' => ['environment', 'Environment'],
        ];

        $options = [];
        foreach ($rawTypes->filter()->unique() as $rawType) {
            $baseName = $this->normalizedBasename((string) $rawType);
            if (! isset($map[$baseName])) {
                continue;
            }
            [$key, $label] = $map[$baseName];
            $options[$key] = $label;
        }

        $order = ['human', 'animal', 'environment'];
        $sorted = [];
        foreach ($order as $k) {
            if (array_key_exists($k, $options)) {
                $sorted[$k] = $options[$k];
            }
        }

        return $sorted;
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableTraceTypes(): array
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['ParasiteSamples', 'NucleicAcids', 'Cultures', 'Pools'], true)) {
            return [];
        }

        $withoutTrace = [
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryParasiteStageFilter',
            'tracePrimaryParasiteSexFilter',
            'tracePrimaryParasiteStateFilter',
            'tracePrimaryParasiteSampleTypeFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
        ];

        $base = $this->baseFilteredQuery($withoutTrace);
        $types = collect();

        if ($target === 'NucleicAcids') {
            $types = (clone $base)
                ->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereNotNull('nucleic_acids.nucleic_content_type')
                ->distinct()
                ->pluck('nucleic_acids.nucleic_content_type');
        } elseif ($target === 'Cultures') {
            $types = (clone $base)
                ->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereNotNull('cultures.cultures_content_type')
                ->distinct()
                ->pluck('cultures.cultures_content_type');
        } elseif ($target === 'Pools') {
            // Pools behave like: pool -> pool_contents -> samples (morphTo).
            // For "trace to primary", we must also infer primary sample types behind derived contents
            // (e.g. pools containing parasite samples should expose Human/Animal/Environment options
            // based on parasite origin type).
            $directTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereNotNull('pool_contents.samples_type')
                ->distinct()
                ->pluck('pool_contents.samples_type');

            $viaParasiteOriginTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');

            $viaCultureContentTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereNotNull('cultures.cultures_content_type')
                ->distinct()
                ->pluck('cultures.cultures_content_type');

            $viaCultureParasiteOriginTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');

            $viaNucleicContentTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(NucleicAcids::class))
                ->join('nucleic_acids', 'pool_contents.samples_id', '=', 'nucleic_acids.id')
                ->whereNotNull('nucleic_acids.nucleic_content_type')
                ->distinct()
                ->pluck('nucleic_acids.nucleic_content_type');

            $viaNucleicParasiteOriginTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(NucleicAcids::class))
                ->join('nucleic_acids', 'pool_contents.samples_id', '=', 'nucleic_acids.id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');

            $viaNucleicCultureContentTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(NucleicAcids::class))
                ->join('nucleic_acids', 'pool_contents.samples_id', '=', 'nucleic_acids.id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereNotNull('cultures.cultures_content_type')
                ->distinct()
                ->pluck('cultures.cultures_content_type');

            $viaNucleicCultureParasiteOriginTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(NucleicAcids::class))
                ->join('nucleic_acids', 'pool_contents.samples_id', '=', 'nucleic_acids.id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');

            // One level of nested pools: pool -> pool_contents (Pools) -> pool_contents -> samples.
            $nestedDirectTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Pools::class))
                ->join('pool_contents as nested_pool_contents', 'nested_pool_contents.pools_id', '=', 'pool_contents.samples_id')
                ->whereNotNull('nested_pool_contents.samples_type')
                ->distinct()
                ->pluck('nested_pool_contents.samples_type');

            $nestedViaParasiteOriginTypes = (clone $base)
                ->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Pools::class))
                ->join('pool_contents as nested_pool_contents', 'nested_pool_contents.pools_id', '=', 'pool_contents.samples_id')
                ->whereIn('nested_pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples as nested_parasite_samples', 'nested_pool_contents.samples_id', '=', 'nested_parasite_samples.id')
                ->join('parasites as nested_parasites', 'nested_parasite_samples.parasites_id', '=', 'nested_parasites.id')
                ->whereNotNull('nested_parasites.parasites_origin_type')
                ->distinct()
                ->pluck('nested_parasites.parasites_origin_type');

            $types = $directTypes
                ->merge($nestedDirectTypes)
                ->merge($viaParasiteOriginTypes)
                ->merge($nestedViaParasiteOriginTypes)
                ->merge($viaCultureContentTypes)
                ->merge($viaCultureParasiteOriginTypes)
                ->merge($viaNucleicContentTypes)
                ->merge($viaNucleicParasiteOriginTypes)
                ->merge($viaNucleicCultureContentTypes)
                ->merge($viaNucleicCultureParasiteOriginTypes);
        } elseif ($target === 'ParasiteSamples') {
            $types = (clone $base)
                ->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->whereNotNull('parasites.parasites_origin_type')
                ->distinct()
                ->pluck('parasites.parasites_origin_type');
        }

        $map = [
            'HumanSamples' => ['human', 'Human'],
            'AnimalSamples' => ['animal', 'Animal'],
            'EnvironmentSamples' => ['environment', 'Environment'],
            'ParasiteSamples' => ['parasite', 'Parasite samples'],
            'Cultures' => ['culture', 'Cultures'],
            'Pools' => ['pool', 'Pools'],
            'NucleicAcids' => ['nucleic', 'Nucleic acids'],
        ];

        $options = [];
        foreach ($types->filter()->unique() as $rawType) {
            $baseName = $this->normalizedBasename((string) $rawType);
            if (! isset($map[$baseName])) {
                continue;
            }

            [$key, $label] = $map[$baseName];
            $options[$key] = $label;
        }

        // Stable ordering
        $order = ['human', 'animal', 'environment', 'parasite', 'culture', 'pool', 'nucleic'];
        $sorted = [];
        foreach ($order as $k) {
            if (array_key_exists($k, $options)) {
                $sorted[$k] = $options[$k];
            }
        }

        return $sorted;
    }

    private function traceParasiteSamplesBaseQuery()
    {
        if ($this->tracePrimaryTypeFilter !== 'parasite') {
            return null;
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools'], true)) {
            return null;
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryTypeFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryParasiteStageFilter',
            'tracePrimaryParasiteSexFilter',
            'tracePrimaryParasiteStateFilter',
            'tracePrimaryParasiteSampleTypeFilter',
        ]);

        $q = clone $base;

        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id');
        } elseif ($target === 'Cultures') {
            $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'cultures.cultures_content_id', '=', 'parasite_samples.id');
        } else {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                ->join('parasite_samples', 'pool_contents.samples_id', '=', 'parasite_samples.id');
        }

        return $q->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id');
    }

    private function traceCulturesBaseQuery()
    {
        if ($this->tracePrimaryTypeFilter !== 'culture') {
            return null;
        }

        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Pools'], true)) {
            return null;
        }

        $base = $this->baseFilteredQuery([
            'tracePrimaryTypeFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
        ]);

        $q = clone $base;

        if ($target === 'NucleicAcids') {
            $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'nucleic_acids.nucleic_content_id', '=', 'cultures.id');
        } else {
            $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                ->join('cultures', 'pool_contents.samples_id', '=', 'cultures.id');
        }

        return $q;
    }

    private function getAllTraceParasiteSpecies()
    {
        $q = $this->traceParasiteSamplesBaseQuery();
        if (! $q) {
            return collect();
        }

        return $q->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.name_scientific')
            ->distinct()
            ->orderBy('parasite_species.name_scientific')
            ->pluck('parasite_species.name_scientific')
            ->filter()
            ->values();
    }

    private function getAllTraceParasiteStages()
    {
        $q = $this->traceParasiteSamplesBaseQuery();
        if (! $q) {
            return collect();
        }

        return $q->whereNotNull('parasites.stage')
            ->distinct()
            ->orderBy('parasites.stage')
            ->pluck('parasites.stage')
            ->filter()
            ->values();
    }

    private function getAllTraceParasiteSexes()
    {
        $q = $this->traceParasiteSamplesBaseQuery();
        if (! $q) {
            return collect();
        }

        return $q->whereNotNull('parasites.sex')
            ->distinct()
            ->orderBy('parasites.sex')
            ->pluck('parasites.sex')
            ->filter()
            ->values();
    }

    private function getAllTraceParasiteStates()
    {
        $q = $this->traceParasiteSamplesBaseQuery();
        if (! $q) {
            return collect();
        }

        return $q->whereNotNull('parasites.state')
            ->distinct()
            ->orderBy('parasites.state')
            ->pluck('parasites.state')
            ->filter()
            ->values();
    }

    private function getAllTraceParasiteSampleTypes()
    {
        $q = $this->traceParasiteSamplesBaseQuery();
        if (! $q) {
            return collect();
        }

        return $q->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->whereNotNull('parasite_sample_types.name')
            ->distinct()
            ->orderBy('parasite_sample_types.name')
            ->pluck('parasite_sample_types.name')
            ->filter()
            ->values();
    }

    private function getAllTraceCultureTypes()
    {
        $q = $this->traceCulturesBaseQuery();
        if (! $q) {
            return collect();
        }

        return $q->whereNotNull('cultures.type')
            ->distinct()
            ->orderBy('cultures.type')
            ->pluck('cultures.type')
            ->filter()
            ->values();
    }

    private function getAllTraceCultureMediums()
    {
        $q = $this->traceCulturesBaseQuery();
        if (! $q) {
            return collect();
        }

        return $q->whereNotNull('cultures.medium')
            ->distinct()
            ->orderBy('cultures.medium')
            ->pluck('cultures.medium')
            ->filter()
            ->values();
    }

    private function getAllTraceAnimalSexes()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->select('animals.sex')
            ->distinct()
            ->orderBy('animals.sex');

        $this->applySeedVisibilityScope($q, AnimalSamples::class, 'animal_samples');

        return $q->pluck('animals.sex')->filter()->values();
    }

    private function getAllTraceAnimalAges()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->select('animals.age')
            ->distinct()
            ->orderBy('animals.age');

        $this->applySeedVisibilityScope($q, AnimalSamples::class, 'animal_samples');

        return $q->pluck('animals.age')->filter()->values();
    }

    private function getAllTraceHumanEthnicities()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->select('humans.ethnicity')
            ->distinct()
            ->orderBy('humans.ethnicity');

        $this->applySeedVisibilityScope($q, HumanSamples::class, 'human_samples');

        return $q->pluck('humans.ethnicity')->filter()->values();
    }

    private function getAllTraceHumanOccupations()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->select('humans.occupation')
            ->distinct()
            ->orderBy('humans.occupation');

        $this->applySeedVisibilityScope($q, HumanSamples::class, 'human_samples');

        return $q->pluck('humans.occupation')->filter()->values();
    }

    private function getAllTraceHumanCountries()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->select('countries.name')
            ->distinct()
            ->orderBy('countries.name');

        $this->applySeedVisibilityScope($q, HumanSamples::class, 'human_samples');

        return $q->pluck('countries.name')->filter()->values();
    }

    private function applySamplingSiteFilter($query, array $except = []): void
    {
        if (in_array('samplingSiteFilter', $except, true) || $this->samplingSiteFilter === '') {
            return;
        }

        $scopedPrimaryType = $this->scopedPrimaryTypeForSamplingSite();
        $experimentIds = app(PrimarySampleReachability::class)->experimentIdsFromSamplingSiteName(
            $this->samplingSiteFilter,
            $this->isGuestMode() ? null : $this->getProjectId(),
            $this->isGuestMode(),
            $scopedPrimaryType
        );

        if ($experimentIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function ($w) use ($experimentIds) {
            foreach (array_chunk($experimentIds, 800) as $chunk) {
                $w->orWhereIn('experiments.id', $chunk);
            }
        });
    }

    /**
     * @return class-string|null
     */
    private function scopedPrimaryTypeForSamplingSite(): ?string
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);

        return match ($target) {
            'HumanSamples' => HumanSamples::class,
            'AnimalSamples' => AnimalSamples::class,
            'EnvironmentSamples' => EnvironmentSamples::class,
            default => match ($this->traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => match ($this->tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                },
            },
        };
    }

    /**
     * @return Collection<int, string>
     */
    private function samplingSitesForExperimentsOptions()
    {
        $sites = collect();

        foreach ($this->scopedPrimaryTypesForSamplingSiteOptions() as $traceKey) {
            $sites = $sites->merge($this->distinctSamplingSitesForTracePrimaryKey($traceKey));
        }

        return $sites->filter()->unique()->sort()->values();
    }

    /**
     * @return array<int, string>
     */
    private function scopedPrimaryTypesForSamplingSiteOptions(): array
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);

        if ($target === 'HumanSamples') {
            return ['human'];
        }

        if ($target === 'AnimalSamples') {
            return ['animal'];
        }

        if ($target === 'EnvironmentSamples') {
            return ['environment'];
        }

        if (in_array($target, ['ParasiteSamples', 'NucleicAcids', 'Cultures', 'Pools'], true)) {
            if ($this->traceDeepPrimaryTypeFilter === 'human') {
                return ['human'];
            }

            if ($this->traceDeepPrimaryTypeFilter === 'animal') {
                return ['animal'];
            }

            if ($this->traceDeepPrimaryTypeFilter === 'environment') {
                return ['environment'];
            }

            if ($this->tracePrimaryTypeFilter === 'human') {
                return ['human'];
            }

            if ($this->tracePrimaryTypeFilter === 'animal') {
                return ['animal'];
            }

            if ($this->tracePrimaryTypeFilter === 'environment') {
                return ['environment'];
            }

            return ['human', 'animal', 'environment'];
        }

        return ['human', 'animal', 'environment'];
    }

    /**
     * @return Collection<int, string>
     */
    private function distinctSamplingSitesForTracePrimaryKey(string $traceKey)
    {
        if (! in_array($traceKey, ['human', 'animal', 'environment'], true)) {
            return collect();
        }

        $primaryClass = match ($traceKey) {
            'animal' => AnimalSamples::class,
            'human' => HumanSamples::class,
            'environment' => EnvironmentSamples::class,
            default => null,
        };

        if ($primaryClass === null) {
            return collect();
        }

        $table = (new $primaryClass)->getTable();
        $base = $this->baseFilteredQuery(['samplingSiteFilter']);
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);

        if ($target === class_basename($primaryClass)) {
            $variants = $this->typeVariants($primaryClass);

            return (clone $base)
                ->join($table, function ($join) use ($table, $variants) {
                    $join->on('experiments.experiments_content_id', '=', "{$table}.id")
                        ->whereIn('experiments.experiments_content_type', $variants);
                })
                ->join('sampling_sites', "{$table}.sampling_sites_id", '=', 'sampling_sites.id')
                ->whereNotNull('sampling_sites.name')
                ->distinct()
                ->orderBy('sampling_sites.name')
                ->pluck('sampling_sites.name')
                ->filter()
                ->values();
        }

        $joined = match ($traceKey) {
            'animal' => $this->joinTracePrimaryAnimalOnExperiments($base),
            'human' => $this->joinTracePrimaryHumanOnExperiments($base),
            'environment' => $this->joinTracePrimaryEnvironmentOnExperiments($base),
            default => null,
        };

        if (! $joined) {
            return collect();
        }

        return $joined
            ->join('sampling_sites', "{$table}.sampling_sites_id", '=', 'sampling_sites.id')
            ->whereNotNull('sampling_sites.name')
            ->distinct()
            ->orderBy('sampling_sites.name')
            ->pluck('sampling_sites.name')
            ->filter()
            ->values();
    }

    /**
     * @return array<string, int>
     */
    private function groupedSamplingSiteDistribution($base): array
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);

        if ($target === 'HumanSamples') {
            return $this->groupedDirectPrimarySamplingSiteDistribution($base, HumanSamples::class);
        }

        if ($target === 'AnimalSamples') {
            return $this->groupedDirectPrimarySamplingSiteDistribution($base, AnimalSamples::class);
        }

        if ($target === 'EnvironmentSamples') {
            return $this->groupedDirectPrimarySamplingSiteDistribution($base, EnvironmentSamples::class);
        }

        if (in_array($target, ['ParasiteSamples', 'NucleicAcids', 'Cultures', 'Pools'], true)) {
            return $this->groupedTracedSamplingSiteDistribution();
        }

        $maps = [
            $this->groupedDirectPrimarySamplingSiteDistribution($base, HumanSamples::class),
            $this->groupedDirectPrimarySamplingSiteDistribution($base, AnimalSamples::class),
            $this->groupedDirectPrimarySamplingSiteDistribution($base, EnvironmentSamples::class),
        ];

        foreach ($this->scopedPrimaryTypesForSamplingSiteOptions() as $traceKey) {
            $maps[] = $this->groupedTracePrimarySamplingSiteDistribution($traceKey);
        }

        return $this->mergeDistributionMaps(...$maps);
    }

    /**
     * @param  class-string  $primaryClass
     * @return array<string, int>
     */
    /**
     * Count expression for sampling-site distributions. In "screening with
     * confirmation" mode each qualifying sample owns a screening AND a
     * confirmation experiment, so counting experiments double-counts the
     * sample; count distinct origin samples instead.
     */
    private function samplingSiteCountExpression(): string
    {
        if ($this->purposeFilter === 'screening_with_confirmation') {
            return "count(distinct (experiments.experiments_content_type || '#' || experiments.experiments_content_id)) as total";
        }

        return 'count(distinct experiments.id) as total';
    }

    private function groupedDirectPrimarySamplingSiteDistribution($base, string $primaryClass): array
    {
        if ($this->normalizedBasename((string) $this->experimentTypeFilter) !== 'all'
            && $this->normalizedBasename((string) $this->experimentTypeFilter) !== class_basename($primaryClass)) {
            return [];
        }

        $table = (new $primaryClass)->getTable();
        $variants = $this->typeVariants($primaryClass);

        return (clone $base)
            ->join($table, function ($join) use ($table, $variants) {
                $join->on('experiments.experiments_content_id', '=', "{$table}.id")
                    ->whereIn('experiments.experiments_content_type', $variants);
            })
            ->join('sampling_sites', "{$table}.sampling_sites_id", '=', 'sampling_sites.id')
            ->whereNotNull('sampling_sites.name')
            ->select('sampling_sites.name as label', DB::raw($this->samplingSiteCountExpression()))
            ->groupBy('sampling_sites.name')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function groupedTracedSamplingSiteDistribution(): array
    {
        $maps = [];

        foreach ($this->scopedPrimaryTypesForSamplingSiteOptions() as $traceKey) {
            $maps[] = $this->groupedTracePrimarySamplingSiteDistribution($traceKey);
        }

        return $this->mergeDistributionMaps(...$maps);
    }

    /**
     * @return array<string, int>
     */
    private function groupedTracePrimarySamplingSiteDistribution(string $traceType): array
    {
        if (! in_array($traceType, ['human', 'animal', 'environment'], true)) {
            return [];
        }

        $joined = match ($traceType) {
            'animal' => $this->joinTracePrimaryAnimalOnExperiments(
                $this->baseForTraceChartDistribution($traceType, ['samplingSiteFilter'])
            ),
            'human' => $this->joinTracePrimaryHumanOnExperiments(
                $this->baseForTraceChartDistribution($traceType, ['samplingSiteFilter'])
            ),
            'environment' => $this->joinTracePrimaryEnvironmentOnExperiments(
                $this->baseForTraceChartDistribution($traceType, ['samplingSiteFilter'])
            ),
            default => null,
        };

        if (! $joined) {
            return [];
        }

        $table = match ($traceType) {
            'animal' => 'animal_samples',
            'human' => 'human_samples',
            'environment' => 'environment_samples',
            default => null,
        };

        if ($table === null) {
            return [];
        }

        return $joined
            ->join('sampling_sites', "{$table}.sampling_sites_id", '=', 'sampling_sites.id')
            ->whereNotNull('sampling_sites.name')
            ->select('sampling_sites.name as label', DB::raw($this->samplingSiteCountExpression()))
            ->groupBy('sampling_sites.name')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @return Builder|null
     */
    private function joinTracePrimaryEnvironmentOnExperiments($query)
    {
        $target = $this->normalizedBasename((string) $this->experimentTypeFilter);
        if (! in_array($target, ['NucleicAcids', 'Cultures', 'Pools', 'ParasiteSamples'], true)) {
            return null;
        }

        $q = clone $query;

        if ($target === 'NucleicAcids') {
            return $q->join('nucleic_acids', 'nucleic_acids.id', '=', 'experiments.experiments_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(EnvironmentSamples::class))
                ->join('environment_samples', 'nucleic_acids.nucleic_content_id', '=', 'environment_samples.id');
        }

        if ($target === 'Cultures') {
            return $q->join('cultures', 'cultures.id', '=', 'experiments.experiments_content_id')
                ->whereIn('cultures.cultures_content_type', $this->typeVariants(EnvironmentSamples::class))
                ->join('environment_samples', 'cultures.cultures_content_id', '=', 'environment_samples.id');
        }

        if ($target === 'Pools') {
            return $q->join('pool_contents', 'pool_contents.pools_id', '=', 'experiments.experiments_content_id')
                ->whereIn('pool_contents.samples_type', $this->typeVariants(EnvironmentSamples::class))
                ->join('environment_samples', 'pool_contents.samples_id', '=', 'environment_samples.id');
        }

        return $q->join('parasite_samples', 'parasite_samples.id', '=', 'experiments.experiments_content_id')
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereIn('parasites.parasites_origin_type', $this->typeVariants(EnvironmentSamples::class))
            ->join('environment_samples', 'parasites.parasites_origin_id', '=', 'environment_samples.id');
    }

    private function applySeedVisibilityScope($query, string $seedType, string $table): void
    {
        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) use ($seedType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($seedType))
                    ->where('tubes.is_private', false);
            });

            return;
        }

        $projectId = $this->getProjectId();
        $query->where(function ($w) use ($projectId, $seedType, $table) {
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

    public function render()
    {
        $viewData = $this->filteredData();

        return view('livewire.experiments-dashboard', $viewData);
    }
}
