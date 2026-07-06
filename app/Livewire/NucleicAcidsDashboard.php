<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\SubProject;
use App\Services\PrimarySampleReachability;
use Carbon\Carbon;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;

#[Title('Nucleic Acids Dashboard')]
class NucleicAcidsDashboard extends PlainComponent
{
    protected $projectId;

    public string $timelineGranularity = 'monthly';

    public string $nucleicTypeFilter = '';

    public string $sourceTypeFilter = 'all';

    public string $subProjectFilter = '';

    public string $animalSpeciesFilter = '';

    public string $parasiteSpeciesFilter = '';

    public string $parasiteOriginTypeFilter = 'all';

    public string $parasiteOriginAnimalSpeciesFilter = '';

    /**
     * When sourceType is derived (culture/pool), allow filtering by traced primary sample.
     */
    public string $tracePrimaryTypeFilter = 'all';

    public string $tracePrimaryAnimalSpeciesFilter = '';

    public string $tracePrimaryAnimalSexFilter = '';

    public string $tracePrimaryAnimalAgeFilter = '';

    public string $tracePrimaryHumanEthnicityFilter = '';

    public string $tracePrimaryHumanOccupationFilter = '';

    public string $tracePrimaryHumanCountryFilter = '';

    public string $tracePrimaryParasiteSpeciesFilter = '';

    public string $tracePrimaryCultureTypeFilter = '';

    public string $tracePrimaryCultureMediumFilter = '';

    public string $tracePrimaryNucleicTypeFilter = '';

    public ?int $tracePrimaryPoolMinNrPooled = null;

    public ?int $tracePrimaryPoolMaxNrPooled = null;

    public string $traceDeepPrimaryTypeFilter = 'all';

    public string $traceDeepAnimalSpeciesFilter = '';

    public string $traceDeepAnimalSexFilter = '';

    public string $traceDeepAnimalAgeFilter = '';

    public string $traceDeepHumanEthnicityFilter = '';

    public string $traceDeepHumanOccupationFilter = '';

    public string $traceDeepHumanCountryFilter = '';

    public string $protocolFilter = '';

    public string $laboratoryFilter = '';

    public string $extractedByFilter = '';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
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
            ->where('projects.id', $this->projectId)
            ->withPivot('role', 'date_joined', 'permission')
            ->first();

        if (! $project || ! $project->pivot) {
            return false;
        }

        return $project->pivot->permission !== 'viewer';
    }

    public function updated($propertyName): void
    {
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function updatedSourceTypeFilter(): void
    {
        $this->sourceTypeFilter = $this->normalizeFilterValue($this->sourceTypeFilter);

        $this->reset([
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryNucleicTypeFilter',
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
    }

    public function updatedTracePrimaryTypeFilter(): void
    {
        $this->tracePrimaryTypeFilter = $this->normalizeFilterValue($this->tracePrimaryTypeFilter);

        $this->reset([
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryNucleicTypeFilter',
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
    }

    public function updatedTraceDeepPrimaryTypeFilter(): void
    {
        $this->traceDeepPrimaryTypeFilter = $this->normalizeFilterValue($this->traceDeepPrimaryTypeFilter);

        $this->reset([
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
        ]);
    }

    public function updatedParasiteOriginTypeFilter(): void
    {
        $this->reset([
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryNucleicTypeFilter',
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

        if ($this->parasiteOriginTypeFilter !== 'animal') {
            $this->parasiteOriginAnimalSpeciesFilter = '';
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'timelineGranularity',
            'nucleicTypeFilter',
            'sourceTypeFilter',
            'subProjectFilter',
            'animalSpeciesFilter',
            'parasiteSpeciesFilter',
            'parasiteOriginTypeFilter',
            'parasiteOriginAnimalSpeciesFilter',
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryNucleicTypeFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
            'protocolFilter',
            'laboratoryFilter',
            'extractedByFilter',
            'startDate',
            'endDate',
        ]);

        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function render()
    {
        return view('livewire.nucleic-acids-dashboard', array_merge(
            $this->dashboardPayload(),
            [
                'isGuestMode' => $this->isGuestMode(),
                'canEdit' => $this->canEdit(),
                'allNucleicTypes' => $this->allNucleicTypes(),
                'allProtocols' => $this->allProtocols(),
                'allLaboratories' => $this->allLaboratories(),
                'allPeople' => $this->allPeople(),
                'allSubProjects' => $this->allSubProjects(),
                'allAnimalSpecies' => $this->allAnimalSpecies(),
                'allParasiteSpecies' => $this->allParasiteSpecies(),
                'availableSourceTypes' => $this->availableSourceTypes(),
                'availableParasiteOriginTypes' => $this->availableParasiteOriginTypes(),
                'availableTracePrimaryTypes' => $this->availableTracePrimaryTypes(),
                'tracePrimaryAnimalSpeciesOptions' => $this->tracePrimaryAnimalSpeciesOptions(),
                'tracePrimaryAnimalSexesOptions' => $this->tracePrimaryAnimalSexesOptions(),
                'tracePrimaryAnimalAgesOptions' => $this->tracePrimaryAnimalAgesOptions(),
                'tracePrimaryHumanEthnicitiesOptions' => $this->tracePrimaryHumanEthnicitiesOptions(),
                'tracePrimaryHumanOccupationsOptions' => $this->tracePrimaryHumanOccupationsOptions(),
                'tracePrimaryHumanCountriesOptions' => $this->tracePrimaryHumanCountriesOptions(),
                'tracePrimaryParasiteSpeciesOptions' => $this->tracePrimaryParasiteSpeciesOptions(),
                'tracePrimaryCultureTypesOptions' => $this->tracePrimaryCultureTypesOptions(),
                'tracePrimaryCultureMediumsOptions' => $this->tracePrimaryCultureMediumsOptions(),
                'tracePrimaryNucleicTypesOptions' => $this->tracePrimaryNucleicTypesOptions(),
                'availableTraceDeepPrimaryTypes' => $this->availableTraceDeepPrimaryTypes(),
                'traceDeepAnimalSpeciesOptions' => $this->traceDeepAnimalSpeciesOptions(),
                'traceDeepAnimalSexesOptions' => $this->traceDeepAnimalSexesOptions(),
                'traceDeepAnimalAgesOptions' => $this->traceDeepAnimalAgesOptions(),
                'traceDeepHumanEthnicitiesOptions' => $this->traceDeepHumanEthnicitiesOptions(),
                'traceDeepHumanOccupationsOptions' => $this->traceDeepHumanOccupationsOptions(),
                'traceDeepHumanCountriesOptions' => $this->traceDeepHumanCountriesOptions(),
            ]
        ));
    }

    /**
     * Base query used for dynamic option lists (applies visibility + global filters).
     * Pass $except keys to skip applying a filter.
     */
    private function baseQueryForOptions(array $except = [])
    {
        $query = NucleicAcids::query()
            ->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id')
            ->leftJoin('laboratories', 'nucleic_acids.laboratories_id', '=', 'laboratories.id')
            ->leftJoin('people', 'nucleic_acids.people_id', '=', 'people.id');

        $this->applyVisibilityScope($query);

        if (! in_array('nucleicTypeFilter', $except, true) && $this->nucleicTypeFilter !== '') {
            $query->where('nucleic_acids.type', $this->nucleicTypeFilter);
        }
        if (! in_array('subProjectFilter', $except, true) && $this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'nucleic_acids.id')
                    ->where('sub_project_assignments.assignable_type', NucleicAcids::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }

        if (! in_array('protocolFilter', $except, true) && $this->protocolFilter !== '') {
            $query->where('protocols.name', $this->protocolFilter);
        }

        if (! in_array('laboratoryFilter', $except, true) && $this->laboratoryFilter !== '') {
            $query->where('laboratories.name', $this->laboratoryFilter);
        }

        if (! in_array('extractedByFilter', $except, true) && $this->extractedByFilter !== '') {
            $query->whereRaw($this->peopleNameSql().' = ?', [$this->extractedByFilter]);
        }

        if (
            ! in_array('startDate', $except, true)
            && ! in_array('endDate', $except, true)
            && $this->startDate
            && $this->endDate
        ) {
            $query->whereBetween('nucleic_acids.date_extracted', [$this->startDate, $this->endDate]);
        } elseif (! in_array('startDate', $except, true) && $this->startDate) {
            $query->where('nucleic_acids.date_extracted', '>=', $this->startDate);
        } elseif (! in_array('endDate', $except, true) && $this->endDate) {
            $query->where('nucleic_acids.date_extracted', '<=', $this->endDate);
        }

        if (
            ! in_array('sourceTypeFilter', $except, true)
            && $this->sourceTypeFilter !== 'all'
            && $this->sourceTypeFilter !== ''
        ) {
            $sourceType = match ($this->sourceTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                'nucleic' => NucleicAcids::class,
                default => null,
            };

            if ($sourceType) {
                $query->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants($sourceType));
            }
        }

        if (
            $this->sourceTypeFilter === 'animal'
            && ! in_array('animalSpeciesFilter', $except, true)
            && $this->animalSpeciesFilter !== ''
        ) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('animal_samples')
                    ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                    ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                    ->whereColumn('animal_samples.id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('animal_species.name_common', $this->animalSpeciesFilter);
            });
        }

        if ($this->sourceTypeFilter === 'parasite' || $this->tracePrimaryTypeFilter === 'parasite') {
            $hasParasiteFilter =
                (! in_array('parasiteSpeciesFilter', $except, true) && $this->parasiteSpeciesFilter !== '')
                || (! in_array('parasiteOriginTypeFilter', $except, true) && $this->parasiteOriginTypeFilter !== 'all')
                || (! in_array('parasiteOriginAnimalSpeciesFilter', $except, true) && $this->parasiteOriginAnimalSpeciesFilter !== '');

            if ($hasParasiteFilter) {
                $applyParasiteSpecies = ! in_array('parasiteSpeciesFilter', $except, true) && $this->parasiteSpeciesFilter !== '';
                $applyOriginType = ! in_array('parasiteOriginTypeFilter', $except, true) && $this->parasiteOriginTypeFilter !== 'all';
                $applyOriginAnimalSpecies = ! in_array('parasiteOriginAnimalSpeciesFilter', $except, true) && $this->parasiteOriginAnimalSpeciesFilter !== '';

                $parasiteSpecies = $this->parasiteSpeciesFilter;
                $originAnimalSpecies = $this->parasiteOriginAnimalSpeciesFilter;

                $originType = match ($this->parasiteOriginTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'culture' => Cultures::class,
                    'pool' => Pools::class,
                    'nucleic' => NucleicAcids::class,
                    default => null,
                };

                $query->whereExists(function ($sub) use (
                    $originType,
                    $applyParasiteSpecies,
                    $applyOriginType,
                    $applyOriginAnimalSpecies,
                    $parasiteSpecies,
                    $originAnimalSpecies
                ) {
                    $sub->select(DB::raw(1))
                        ->from('parasite_samples')
                        ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                        ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                        ->leftJoin('animal_samples', function ($join) {
                            $join->on('parasites.parasites_origin_id', '=', 'animal_samples.id')
                                ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class));
                        })
                        ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
                        ->leftJoin('animal_species as origin_animal_species', 'animals.animal_species_id', '=', 'origin_animal_species.id')
                        ->whereColumn('parasite_samples.id', 'nucleic_acids.nucleic_content_id')
                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class));

                    if ($applyParasiteSpecies) {
                        $sub->where('parasite_species.name_scientific', $parasiteSpecies);
                    }

                    if ($originType && $applyOriginType) {
                        $sub->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));
                    }

                    if ($applyOriginAnimalSpecies) {
                        $sub->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                            ->where('origin_animal_species.name_common', $originAnimalSpecies);
                    }
                });
            }
        }

        return $query;
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableSourceTypes(): array
    {
        $types = $this->baseQueryForOptions([
            'sourceTypeFilter',
            'animalSpeciesFilter',
            'parasiteSpeciesFilter',
            'parasiteOriginTypeFilter',
            'parasiteOriginAnimalSpeciesFilter',
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
        ])
            ->select('nucleic_acids.nucleic_content_type')
            ->distinct()
            ->pluck('nucleic_acids.nucleic_content_type')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values();

        $map = [
            'HumanSamples' => ['human', 'Human'],
            'AnimalSamples' => ['animal', 'Animal'],
            'EnvironmentSamples' => ['environment', 'Environment'],
            'ParasiteSamples' => ['parasite', 'Parasite'],
            'Cultures' => ['culture', 'Culture'],
            'Pools' => ['pool', 'Pool'],
            'NucleicAcids' => ['nucleic', 'Nucleic acids'],
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

        $order = ['human', 'animal', 'environment', 'parasite', 'culture', 'pool', 'nucleic'];
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
    private function availableParasiteOriginTypes(): array
    {
        if ($this->sourceTypeFilter !== 'parasite') {
            return [];
        }

        $types = $this->baseQueryForOptions([
            'parasiteOriginTypeFilter',
            'parasiteOriginAnimalSpeciesFilter',
        ])
            ->where('nucleic_acids.nucleic_content_type', ParasiteSamples::class)
            ->join('parasite_samples', 'nucleic_acids.nucleic_content_id', '=', 'parasite_samples.id')
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->select('parasites.parasites_origin_type')
            ->distinct()
            ->pluck('parasites.parasites_origin_type')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values();

        $map = [
            'HumanSamples' => ['human', 'Human'],
            'AnimalSamples' => ['animal', 'Animal'],
            'EnvironmentSamples' => ['environment', 'Environment'],
            'ParasiteSamples' => ['parasite', 'Parasite'],
            'Cultures' => ['culture', 'Culture'],
            'Pools' => ['pool', 'Pool'],
            'NucleicAcids' => ['nucleic', 'Nucleic acids'],
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

        $order = ['human', 'animal', 'environment', 'parasite', 'culture', 'pool', 'nucleic'];
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
    private function availableTracePrimaryTypes(): array
    {
        $sourceType = $this->normalizeFilterValue($this->sourceTypeFilter);
        if ($this->sourceTypeFilter !== $sourceType) {
            $this->sourceTypeFilter = $sourceType;
        }

        $rows = $this->baseQueryForOptions([
            'tracePrimaryTypeFilter',
            'tracePrimaryAnimalSpeciesFilter',
            'tracePrimaryAnimalSexFilter',
            'tracePrimaryAnimalAgeFilter',
            'tracePrimaryHumanEthnicityFilter',
            'tracePrimaryHumanOccupationFilter',
            'tracePrimaryHumanCountryFilter',
            'tracePrimaryParasiteSpeciesFilter',
            'tracePrimaryCultureTypeFilter',
            'tracePrimaryCultureMediumFilter',
            'tracePrimaryNucleicTypeFilter',
            'tracePrimaryPoolMinNrPooled',
            'tracePrimaryPoolMaxNrPooled',
            'traceDeepPrimaryTypeFilter',
            'traceDeepAnimalSpeciesFilter',
            'traceDeepAnimalSexFilter',
            'traceDeepAnimalAgeFilter',
            'traceDeepHumanEthnicityFilter',
            'traceDeepHumanOccupationFilter',
            'traceDeepHumanCountryFilter',
        ])
            ->select('nucleic_acids.nucleic_content_type', 'nucleic_acids.nucleic_content_id')
            ->distinct()
            ->limit(5000)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $classToOption = [
            'HumanSamples' => ['key' => 'human', 'label' => 'Human'],
            'AnimalSamples' => ['key' => 'animal', 'label' => 'Animal'],
            'EnvironmentSamples' => ['key' => 'environment', 'label' => 'Environment'],
            'ParasiteSamples' => ['key' => 'parasite', 'label' => 'Parasite'],
            'Cultures' => ['key' => 'culture', 'label' => 'Culture'],
            'Pools' => ['key' => 'pool', 'label' => 'Pool'],
            'NucleicAcids' => ['key' => 'nucleic', 'label' => 'Nucleic acids'],
        ];

        $fallbackEntryFromRawType = function (string $rawType) use ($classToOption): ?array {
            $v = strtolower(trim($rawType));
            $v = str_replace('\\', '/', $v);

            return match (true) {
                str_contains($v, 'human') => $classToOption['HumanSamples'],
                str_contains($v, 'animal') => $classToOption['AnimalSamples'],
                str_contains($v, 'environment') => $classToOption['EnvironmentSamples'],
                str_contains($v, 'parasite') => $classToOption['ParasiteSamples'],
                str_contains($v, 'culture') => $classToOption['Cultures'],
                str_contains($v, 'pool') => $classToOption['Pools'],
                str_contains($v, 'nucleic') => $classToOption['NucleicAcids'],
                default => null,
            };
        };

        $allowedBySource = [
            'parasite' => ['human', 'animal', 'environment'],
            'culture' => ['human', 'animal', 'environment', 'parasite', 'pool'],
            'pool' => ['human', 'animal', 'environment', 'parasite', 'culture'],
        ];

        $keyToLabel = [
            'human' => 'Human',
            'animal' => 'Animal',
            'environment' => 'Environment',
            'parasite' => 'Parasite',
            'culture' => 'Culture',
            'pool' => 'Pool',
            'nucleic' => 'Nucleic acids',
        ];

        $out = [];

        if ($sourceType === 'parasite') {
            $parasiteIds = $rows
                ->filter(fn ($r) => $this->normalizedBasename((string) $r->nucleic_content_type) === class_basename(ParasiteSamples::class))
                ->pluck('nucleic_content_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();

            if ($parasiteIds->isNotEmpty()) {
                $originTypes = ParasiteSamples::query()
                    ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                    ->whereIn('parasite_samples.id', $parasiteIds)
                    ->select('parasites.parasites_origin_type')
                    ->distinct()
                    ->pluck('parasites.parasites_origin_type');

                foreach ($originTypes as $rawType) {
                    $base = $this->normalizedBasename((string) $rawType);
                    $entry = $classToOption[$base] ?? $fallbackEntryFromRawType((string) $rawType);
                    if (! $entry) {
                        continue;
                    }
                    if (! in_array($entry['key'], $allowedBySource['parasite'], true)) {
                        continue;
                    }
                    $out[$entry['key']] = $entry['label'];
                }
            }
        } elseif ($sourceType === 'culture') {
            $cultureIds = $rows
                ->filter(fn ($r) => $this->normalizedBasename((string) $r->nucleic_content_type) === class_basename(Cultures::class))
                ->pluck('nucleic_content_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();

            if ($cultureIds->isNotEmpty()) {
                $originTypes = Cultures::query()
                    ->whereIn('id', $cultureIds)
                    ->select('cultures_content_type')
                    ->distinct()
                    ->pluck('cultures_content_type');

                foreach ($originTypes as $rawType) {
                    $base = $this->normalizedBasename((string) $rawType);
                    $entry = $classToOption[$base] ?? $fallbackEntryFromRawType((string) $rawType);
                    if (! $entry) {
                        continue;
                    }
                    if (! in_array($entry['key'], $allowedBySource['culture'], true)) {
                        continue;
                    }
                    $out[$entry['key']] = $entry['label'];
                }
            }
        } elseif ($sourceType === 'pool') {
            $poolIds = $rows
                ->filter(function ($r) {
                    $raw = strtolower((string) $r->nucleic_content_type);

                    return str_contains($raw, 'pool') || $this->normalizedBasename((string) $r->nucleic_content_type) === class_basename(Pools::class);
                })
                ->pluck('nucleic_content_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();

            if ($poolIds->isNotEmpty()) {
                $sampleTypes = PoolContents::query()
                    ->whereIn('pools_id', $poolIds)
                    ->select('samples_type')
                    ->distinct()
                    ->pluck('samples_type');

                foreach ($sampleTypes as $rawType) {
                    $base = $this->normalizedBasename((string) $rawType);
                    $entry = $classToOption[$base] ?? $fallbackEntryFromRawType((string) $rawType);
                    if (! $entry) {
                        continue;
                    }
                    if (! in_array($entry['key'], $allowedBySource['pool'], true)) {
                        continue;
                    }
                    $out[$entry['key']] = $entry['label'];
                }

                $nestedNucleicIds = PoolContents::query()
                    ->whereIn('pools_id', $poolIds)
                    ->where(function ($q) {
                        $q->whereIn('samples_type', $this->typeVariants(NucleicAcids::class))
                            ->orWhereRaw('LOWER(samples_type) LIKE ?', ['%nucleic%']);
                    })
                    ->pluck('samples_id')
                    ->map(fn ($v) => (int) $v)
                    ->unique()
                    ->values();

                if ($nestedNucleicIds->isNotEmpty()) {
                    $nestedTypes = NucleicAcids::query()
                        ->whereIn('id', $nestedNucleicIds)
                        ->select('nucleic_content_type')
                        ->distinct()
                        ->pluck('nucleic_content_type');

                    foreach ($nestedTypes as $rawType) {
                        $base = $this->normalizedBasename((string) $rawType);
                        $entry = $classToOption[$base] ?? $fallbackEntryFromRawType((string) $rawType);
                        if (! $entry) {
                            continue;
                        }
                        if (! in_array($entry['key'], $allowedBySource['pool'], true)) {
                            continue;
                        }
                        $out[$entry['key']] = $entry['label'];
                    }
                }
            }

            if ($out === []) {
                $fallbackTypes = (clone $this->baseQueryForOptions([
                    'tracePrimaryTypeFilter',
                    'tracePrimaryAnimalSpeciesFilter',
                    'tracePrimaryAnimalSexFilter',
                    'tracePrimaryAnimalAgeFilter',
                    'tracePrimaryHumanEthnicityFilter',
                    'tracePrimaryHumanOccupationFilter',
                    'tracePrimaryHumanCountryFilter',
                    'tracePrimaryParasiteSpeciesFilter',
                    'tracePrimaryCultureTypeFilter',
                    'tracePrimaryCultureMediumFilter',
                    'tracePrimaryNucleicTypeFilter',
                    'tracePrimaryPoolMinNrPooled',
                    'tracePrimaryPoolMaxNrPooled',
                    'traceDeepPrimaryTypeFilter',
                    'traceDeepAnimalSpeciesFilter',
                    'traceDeepAnimalSexFilter',
                    'traceDeepAnimalAgeFilter',
                    'traceDeepHumanEthnicityFilter',
                    'traceDeepHumanOccupationFilter',
                    'traceDeepHumanCountryFilter',
                ]))
                    ->join('pool_contents as trace_pool_contents', 'trace_pool_contents.pools_id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                    ->select('trace_pool_contents.samples_type')
                    ->distinct()
                    ->pluck('trace_pool_contents.samples_type');

                foreach ($fallbackTypes as $rawType) {
                    $base = $this->normalizedBasename((string) $rawType);
                    $entry = $classToOption[$base] ?? $fallbackEntryFromRawType((string) $rawType);
                    if (! $entry) {
                        continue;
                    }
                    if (! in_array($entry['key'], $allowedBySource['pool'], true)) {
                        continue;
                    }
                    $out[$entry['key']] = $entry['label'];
                }
            }

            if ($out === []) {
                $prevState = [
                    'tracePrimaryTypeFilter' => $this->tracePrimaryTypeFilter,
                    'tracePrimaryAnimalSpeciesFilter' => $this->tracePrimaryAnimalSpeciesFilter,
                    'tracePrimaryAnimalSexFilter' => $this->tracePrimaryAnimalSexFilter,
                    'tracePrimaryAnimalAgeFilter' => $this->tracePrimaryAnimalAgeFilter,
                    'tracePrimaryHumanEthnicityFilter' => $this->tracePrimaryHumanEthnicityFilter,
                    'tracePrimaryHumanOccupationFilter' => $this->tracePrimaryHumanOccupationFilter,
                    'tracePrimaryHumanCountryFilter' => $this->tracePrimaryHumanCountryFilter,
                    'tracePrimaryParasiteSpeciesFilter' => $this->tracePrimaryParasiteSpeciesFilter,
                    'tracePrimaryCultureTypeFilter' => $this->tracePrimaryCultureTypeFilter,
                    'tracePrimaryCultureMediumFilter' => $this->tracePrimaryCultureMediumFilter,
                    'tracePrimaryNucleicTypeFilter' => $this->tracePrimaryNucleicTypeFilter,
                    'tracePrimaryPoolMinNrPooled' => $this->tracePrimaryPoolMinNrPooled,
                    'tracePrimaryPoolMaxNrPooled' => $this->tracePrimaryPoolMaxNrPooled,
                    'traceDeepPrimaryTypeFilter' => $this->traceDeepPrimaryTypeFilter,
                    'traceDeepAnimalSpeciesFilter' => $this->traceDeepAnimalSpeciesFilter,
                    'traceDeepAnimalSexFilter' => $this->traceDeepAnimalSexFilter,
                    'traceDeepAnimalAgeFilter' => $this->traceDeepAnimalAgeFilter,
                    'traceDeepHumanEthnicityFilter' => $this->traceDeepHumanEthnicityFilter,
                    'traceDeepHumanOccupationFilter' => $this->traceDeepHumanOccupationFilter,
                    'traceDeepHumanCountryFilter' => $this->traceDeepHumanCountryFilter,
                ];

                foreach (['human', 'animal', 'environment', 'parasite', 'culture'] as $candidateKey) {
                    $this->tracePrimaryTypeFilter = $candidateKey;
                    $this->tracePrimaryAnimalSpeciesFilter = '';
                    $this->tracePrimaryAnimalSexFilter = '';
                    $this->tracePrimaryAnimalAgeFilter = '';
                    $this->tracePrimaryHumanEthnicityFilter = '';
                    $this->tracePrimaryHumanOccupationFilter = '';
                    $this->tracePrimaryHumanCountryFilter = '';
                    $this->tracePrimaryParasiteSpeciesFilter = '';
                    $this->tracePrimaryCultureTypeFilter = '';
                    $this->tracePrimaryCultureMediumFilter = '';
                    $this->tracePrimaryNucleicTypeFilter = '';
                    $this->tracePrimaryPoolMinNrPooled = null;
                    $this->tracePrimaryPoolMaxNrPooled = null;
                    $this->traceDeepPrimaryTypeFilter = 'all';
                    $this->traceDeepAnimalSpeciesFilter = '';
                    $this->traceDeepAnimalSexFilter = '';
                    $this->traceDeepAnimalAgeFilter = '';
                    $this->traceDeepHumanEthnicityFilter = '';
                    $this->traceDeepHumanOccupationFilter = '';
                    $this->traceDeepHumanCountryFilter = '';

                    if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                        $out[$candidateKey] = $keyToLabel[$candidateKey];
                    }
                }

                $this->tracePrimaryTypeFilter = $prevState['tracePrimaryTypeFilter'];
                $this->tracePrimaryAnimalSpeciesFilter = $prevState['tracePrimaryAnimalSpeciesFilter'];
                $this->tracePrimaryAnimalSexFilter = $prevState['tracePrimaryAnimalSexFilter'];
                $this->tracePrimaryAnimalAgeFilter = $prevState['tracePrimaryAnimalAgeFilter'];
                $this->tracePrimaryHumanEthnicityFilter = $prevState['tracePrimaryHumanEthnicityFilter'];
                $this->tracePrimaryHumanOccupationFilter = $prevState['tracePrimaryHumanOccupationFilter'];
                $this->tracePrimaryHumanCountryFilter = $prevState['tracePrimaryHumanCountryFilter'];
                $this->tracePrimaryParasiteSpeciesFilter = $prevState['tracePrimaryParasiteSpeciesFilter'];
                $this->tracePrimaryCultureTypeFilter = $prevState['tracePrimaryCultureTypeFilter'];
                $this->tracePrimaryCultureMediumFilter = $prevState['tracePrimaryCultureMediumFilter'];
                $this->tracePrimaryNucleicTypeFilter = $prevState['tracePrimaryNucleicTypeFilter'];
                $this->tracePrimaryPoolMinNrPooled = $prevState['tracePrimaryPoolMinNrPooled'];
                $this->tracePrimaryPoolMaxNrPooled = $prevState['tracePrimaryPoolMaxNrPooled'];
                $this->traceDeepPrimaryTypeFilter = $prevState['traceDeepPrimaryTypeFilter'];
                $this->traceDeepAnimalSpeciesFilter = $prevState['traceDeepAnimalSpeciesFilter'];
                $this->traceDeepAnimalSexFilter = $prevState['traceDeepAnimalSexFilter'];
                $this->traceDeepAnimalAgeFilter = $prevState['traceDeepAnimalAgeFilter'];
                $this->traceDeepHumanEthnicityFilter = $prevState['traceDeepHumanEthnicityFilter'];
                $this->traceDeepHumanOccupationFilter = $prevState['traceDeepHumanOccupationFilter'];
                $this->traceDeepHumanCountryFilter = $prevState['traceDeepHumanCountryFilter'];
            }

        } else {
            foreach ($rows as $row) {
                $base = $this->normalizedBasename((string) $row->nucleic_content_type);
                if (! isset($classToOption[$base])) {
                    continue;
                }
                $entry = $classToOption[$base];
                $out[$entry['key']] = $entry['label'];
            }
        }

        if (
            $sourceType !== 'all'
            && $sourceType !== ''
            && isset($out[$sourceType])
            && count($out) > 1
        ) {
            unset($out[$sourceType]);
        }

        if ($this->tracePrimaryTypeFilter !== 'all' && ! isset($out[$this->tracePrimaryTypeFilter]) && isset($keyToLabel[$this->tracePrimaryTypeFilter])) {
            $out = [$this->tracePrimaryTypeFilter => $keyToLabel[$this->tracePrimaryTypeFilter]] + $out;
        }

        return $out;
    }

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

    private function normalizedBasename(string $rawType): string
    {
        $normalized = ltrim(trim($rawType), '\\');
        $base = class_basename($normalized);

        if (str_starts_with($base, 'AppModels')) {
            return substr($base, strlen('AppModels'));
        }

        return $base;
    }

    private function baseQuery()
    {
        $query = NucleicAcids::query()
            ->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id')
            ->leftJoin('laboratories', 'nucleic_acids.laboratories_id', '=', 'laboratories.id')
            ->leftJoin('people', 'nucleic_acids.people_id', '=', 'people.id');

        $this->applyVisibilityScope($query);

        if ($this->nucleicTypeFilter !== '') {
            $query->where('nucleic_acids.type', $this->nucleicTypeFilter);
        }
        if ($this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'nucleic_acids.id')
                    ->where('sub_project_assignments.assignable_type', NucleicAcids::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }

        if ($this->sourceTypeFilter !== '' && $this->sourceTypeFilter !== 'all') {
            $sourceType = match ($this->sourceTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                default => null,
            };

            if ($sourceType) {
                $query->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants($sourceType));
            }
        }

        $this->applyConditionalSourceFilters($query);

        if ($this->protocolFilter !== '') {
            $query->where('protocols.name', $this->protocolFilter);
        }

        if ($this->laboratoryFilter !== '') {
            $query->where('laboratories.name', $this->laboratoryFilter);
        }

        if ($this->extractedByFilter !== '') {
            $query->whereRaw($this->peopleNameSql().' = ?', [$this->extractedByFilter]);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('nucleic_acids.date_extracted', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('nucleic_acids.date_extracted', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('nucleic_acids.date_extracted', '<=', $this->endDate);
        }

        return $query;
    }

    private function applyConditionalSourceFilters($query): void
    {
        $this->sourceTypeFilter = $this->normalizeFilterValue($this->sourceTypeFilter);
        $this->tracePrimaryTypeFilter = $this->normalizeFilterValue($this->tracePrimaryTypeFilter);
        $this->traceDeepPrimaryTypeFilter = $this->normalizeFilterValue($this->traceDeepPrimaryTypeFilter);

        if ($this->sourceTypeFilter === 'animal' && $this->animalSpeciesFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('animal_samples')
                    ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                    ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                    ->whereColumn('animal_samples.id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('animal_species.name_common', $this->animalSpeciesFilter);
            });
        }

        if ($this->sourceTypeFilter === 'parasite' && ($this->parasiteSpeciesFilter !== '' || $this->parasiteOriginTypeFilter !== 'all' || $this->parasiteOriginAnimalSpeciesFilter !== '')) {
            $originType = match ($this->parasiteOriginTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                'nucleic' => NucleicAcids::class,
                default => null,
            };

            $query->whereExists(function ($sub) use ($originType) {
                $sub->select(DB::raw(1))
                    ->from('parasite_samples')
                    ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                    ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                    ->leftJoin('animal_samples', function ($join) {
                        $join->on('parasites.parasites_origin_id', '=', 'animal_samples.id')
                            ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class));
                    })
                    ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
                    ->leftJoin('animal_species as origin_animal_species', 'animals.animal_species_id', '=', 'origin_animal_species.id')
                    ->whereColumn('parasite_samples.id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class));

                if ($this->parasiteSpeciesFilter !== '') {
                    $sub->where('parasite_species.name_scientific', $this->parasiteSpeciesFilter);
                }

                if ($originType) {
                    $sub->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));
                }

                if ($this->parasiteOriginAnimalSpeciesFilter !== '') {
                    $sub->where('parasites.parasites_origin_type', AnimalSamples::class)
                        ->where('origin_animal_species.name_common', $this->parasiteOriginAnimalSpeciesFilter);
                }
            });
        }

        $hasDeepTrace = $this->traceDeepPrimaryTypeFilter !== 'all'
            || $this->traceDeepAnimalSpeciesFilter !== ''
            || $this->traceDeepAnimalSexFilter !== ''
            || $this->traceDeepAnimalAgeFilter !== ''
            || $this->traceDeepHumanEthnicityFilter !== ''
            || $this->traceDeepHumanOccupationFilter !== ''
            || $this->traceDeepHumanCountryFilter !== '';

        $hasAnyTrace = $this->tracePrimaryTypeFilter !== 'all'
            || $this->tracePrimaryAnimalSpeciesFilter !== ''
            || $this->tracePrimaryAnimalSexFilter !== ''
            || $this->tracePrimaryAnimalAgeFilter !== ''
            || $this->tracePrimaryHumanEthnicityFilter !== ''
            || $this->tracePrimaryHumanOccupationFilter !== ''
            || $this->tracePrimaryHumanCountryFilter !== ''
            || $this->tracePrimaryParasiteSpeciesFilter !== ''
            || $this->tracePrimaryCultureTypeFilter !== ''
            || $this->tracePrimaryCultureMediumFilter !== ''
            || $this->tracePrimaryNucleicTypeFilter !== ''
            || $this->tracePrimaryPoolMinNrPooled !== null
            || $this->tracePrimaryPoolMaxNrPooled !== null
            || $hasDeepTrace;

        if (! $hasAnyTrace) {
            return;
        }

        if (
            $this->sourceTypeFilter === 'pool'
            && $this->tracePrimaryTypeFilter === 'parasite'
            && (
                $this->tracePrimaryParasiteSpeciesFilter !== ''
                || $hasDeepTrace
            )
        ) {
            $deepPrimaryType = match ($this->traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            $query
                ->join('pool_contents as trace_pool_contents', function ($join) {
                    $join->on('trace_pool_contents.pools_id', '=', 'nucleic_acids.nucleic_content_id')
                        ->where(function ($typeQ) {
                            $typeQ->whereIn('trace_pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                                ->orWhereRaw('LOWER(trace_pool_contents.samples_type) LIKE ?', ['%parasite%']);
                        });
                })
                ->join('parasite_samples as trace_parasite_samples', 'trace_parasite_samples.id', '=', 'trace_pool_contents.samples_id')
                ->join('parasites as trace_parasites', 'trace_parasites.id', '=', 'trace_parasite_samples.parasites_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class));

            if ($this->tracePrimaryParasiteSpeciesFilter !== '') {
                $query->leftJoin('parasite_species as trace_parasite_species', 'trace_parasite_species.id', '=', 'trace_parasites.parasite_species_id')
                    ->where('trace_parasite_species.name_scientific', $this->tracePrimaryParasiteSpeciesFilter);
            }

            if ($hasDeepTrace && $deepPrimaryType) {
                $query->whereIn('trace_parasites.parasites_origin_type', $this->typeVariants($deepPrimaryType));

                if ($deepPrimaryType === AnimalSamples::class) {
                    $query->leftJoin('animal_samples as deep_trace_animal_samples', 'deep_trace_animal_samples.id', '=', 'trace_parasites.parasites_origin_id')
                        ->leftJoin('animals as deep_trace_animals', 'deep_trace_animals.id', '=', 'deep_trace_animal_samples.animals_id')
                        ->leftJoin('animal_species as deep_trace_animal_species', 'deep_trace_animal_species.id', '=', 'deep_trace_animals.animal_species_id');

                    if ($this->traceDeepAnimalSpeciesFilter !== '') {
                        $query->where('deep_trace_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                    }
                    if ($this->traceDeepAnimalSexFilter !== '') {
                        $query->where('deep_trace_animals.sex', $this->traceDeepAnimalSexFilter);
                    }
                    if ($this->traceDeepAnimalAgeFilter !== '') {
                        $query->where('deep_trace_animals.age', $this->traceDeepAnimalAgeFilter);
                    }
                }

                if ($deepPrimaryType === HumanSamples::class) {
                    $query->leftJoin('human_samples as deep_trace_human_samples', 'deep_trace_human_samples.id', '=', 'trace_parasites.parasites_origin_id')
                        ->leftJoin('humans as deep_trace_humans', 'deep_trace_humans.id', '=', 'deep_trace_human_samples.humans_id')
                        ->leftJoin('countries as deep_trace_countries', 'deep_trace_countries.id', '=', 'deep_trace_humans.countries_id');

                    if ($this->traceDeepHumanEthnicityFilter !== '') {
                        $query->where('deep_trace_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                    }
                    if ($this->traceDeepHumanOccupationFilter !== '') {
                        $query->where('deep_trace_humans.occupation', $this->traceDeepHumanOccupationFilter);
                    }
                    if ($this->traceDeepHumanCountryFilter !== '') {
                        $query->where('deep_trace_countries.name', $this->traceDeepHumanCountryFilter);
                    }
                }
            }

            return;
        }

        if (
            $this->sourceTypeFilter === 'pool'
            && $this->tracePrimaryTypeFilter === 'culture'
            && (
                $this->tracePrimaryCultureTypeFilter !== ''
                || $this->tracePrimaryCultureMediumFilter !== ''
                || $hasDeepTrace
            )
        ) {
            $deepPrimaryType = match ($this->traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            $query
                ->join('pool_contents as trace_pool_culture_contents', function ($join) {
                    $join->on('trace_pool_culture_contents.pools_id', '=', 'nucleic_acids.nucleic_content_id')
                        ->where(function ($typeQ) {
                            $typeQ->whereIn('trace_pool_culture_contents.samples_type', $this->typeVariants(Cultures::class))
                                ->orWhereRaw('LOWER(trace_pool_culture_contents.samples_type) LIKE ?', ['%culture%']);
                        });
                })
                ->join('cultures as trace_cultures', 'trace_cultures.id', '=', 'trace_pool_culture_contents.samples_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class));

            if ($this->tracePrimaryCultureTypeFilter !== '') {
                $query->where('trace_cultures.type', $this->tracePrimaryCultureTypeFilter);
            }
            if ($this->tracePrimaryCultureMediumFilter !== '') {
                $query->where('trace_cultures.medium', $this->tracePrimaryCultureMediumFilter);
            }

            if ($hasDeepTrace && $deepPrimaryType) {
                $query->where(function ($deepQ) use ($deepPrimaryType) {
                    $deepQ->whereIn('trace_cultures.cultures_content_type', $this->typeVariants($deepPrimaryType))
                        ->orWhereExists(function ($parasiteQ) use ($deepPrimaryType) {
                            $parasiteQ->select(DB::raw(1))
                                ->from('parasite_samples as deep_trace_parasite_samples')
                                ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                                ->whereColumn('deep_trace_parasite_samples.id', 'trace_cultures.cultures_content_id')
                                ->whereIn('trace_cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                                ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepPrimaryType));
                        });
                });
            }

            return;
        }

        if (
            $this->sourceTypeFilter === 'parasite'
            && ! $hasDeepTrace
            && in_array($this->tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
        ) {
            $originType = match ($this->tracePrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
            };

            $query->whereExists(function ($sub) use ($originType) {
                $sub->select(DB::raw(1))
                    ->from('parasite_samples')
                    ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                    ->whereColumn('parasite_samples.id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                    ->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));

                if ($originType === AnimalSamples::class) {
                    $sub->leftJoin('animal_samples as trace_animal_samples', 'trace_animal_samples.id', '=', 'parasites.parasites_origin_id')
                        ->leftJoin('animals as trace_animals', 'trace_animals.id', '=', 'trace_animal_samples.animals_id')
                        ->leftJoin('animal_species as trace_animal_species', 'trace_animal_species.id', '=', 'trace_animals.animal_species_id');

                    if ($this->tracePrimaryAnimalSpeciesFilter !== '') {
                        $sub->where('trace_animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
                    }
                    if ($this->tracePrimaryAnimalSexFilter !== '') {
                        $sub->where('trace_animals.sex', $this->tracePrimaryAnimalSexFilter);
                    }
                    if ($this->tracePrimaryAnimalAgeFilter !== '') {
                        $sub->where('trace_animals.age', $this->tracePrimaryAnimalAgeFilter);
                    }
                }

                if ($originType === HumanSamples::class) {
                    $sub->leftJoin('human_samples as trace_human_samples', 'trace_human_samples.id', '=', 'parasites.parasites_origin_id')
                        ->leftJoin('humans as trace_humans', 'trace_humans.id', '=', 'trace_human_samples.humans_id')
                        ->leftJoin('countries as trace_countries', 'trace_countries.id', '=', 'trace_humans.countries_id');

                    if ($this->tracePrimaryHumanEthnicityFilter !== '') {
                        $sub->where('trace_humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
                    }
                    if ($this->tracePrimaryHumanOccupationFilter !== '') {
                        $sub->where('trace_humans.occupation', $this->tracePrimaryHumanOccupationFilter);
                    }
                    if ($this->tracePrimaryHumanCountryFilter !== '') {
                        $sub->where('trace_countries.name', $this->tracePrimaryHumanCountryFilter);
                    }
                }
            });

            return;
        }

        if (
            $this->sourceTypeFilter === 'culture'
            && ! $hasDeepTrace
            && in_array($this->tracePrimaryTypeFilter, ['human', 'animal', 'environment', 'parasite', 'pool'], true)
        ) {
            $traceType = match ($this->tracePrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'pool' => Pools::class,
            };

            $query->whereExists(function ($sub) use ($traceType) {
                $sub->select(DB::raw(1))
                    ->from('cultures')
                    ->whereColumn('cultures.id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                    ->whereIn('cultures.cultures_content_type', $this->typeVariants($traceType));

                if ($traceType === ParasiteSamples::class && $this->tracePrimaryParasiteSpeciesFilter !== '') {
                    $sub->join('parasite_samples as trace_parasite_samples', 'trace_parasite_samples.id', '=', 'cultures.cultures_content_id')
                        ->join('parasites as trace_parasites', 'trace_parasites.id', '=', 'trace_parasite_samples.parasites_id')
                        ->leftJoin('parasite_species as trace_parasite_species', 'trace_parasite_species.id', '=', 'trace_parasites.parasite_species_id')
                        ->where('trace_parasite_species.name_scientific', $this->tracePrimaryParasiteSpeciesFilter);
                }

                if ($traceType === Pools::class) {
                    $sub->join('pools as trace_pools', 'trace_pools.id', '=', 'cultures.cultures_content_id');

                    if ($this->tracePrimaryPoolMinNrPooled !== null) {
                        $sub->where('trace_pools.nr_pooled', '>=', $this->tracePrimaryPoolMinNrPooled);
                    }
                    if ($this->tracePrimaryPoolMaxNrPooled !== null) {
                        $sub->where('trace_pools.nr_pooled', '<=', $this->tracePrimaryPoolMaxNrPooled);
                    }
                }
            });

            return;
        }

        if (
            $this->sourceTypeFilter === 'pool'
            && $this->tracePrimaryTypeFilter === 'parasite'
        ) {
            $deepPrimaryType = match ($this->traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            $query->whereExists(function ($sub) use ($hasDeepTrace, $deepPrimaryType) {
                $sub->select(DB::raw(1))
                    ->from('pool_contents')
                    ->join('parasite_samples as trace_parasite_samples', 'trace_parasite_samples.id', '=', 'pool_contents.samples_id')
                    ->join('parasites as trace_parasites', 'trace_parasites.id', '=', 'trace_parasite_samples.parasites_id')
                    ->whereColumn('pool_contents.pools_id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                    ->where(function ($typeQ) {
                        $typeQ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                            ->orWhereRaw('LOWER(pool_contents.samples_type) LIKE ?', ['%parasite%']);
                    });

                if ($this->tracePrimaryParasiteSpeciesFilter !== '') {
                    $sub->leftJoin('parasite_species as trace_parasite_species', 'trace_parasite_species.id', '=', 'trace_parasites.parasite_species_id')
                        ->where('trace_parasite_species.name_scientific', $this->tracePrimaryParasiteSpeciesFilter);
                }

                if ($hasDeepTrace && $deepPrimaryType) {
                    $sub->whereIn('trace_parasites.parasites_origin_type', $this->typeVariants($deepPrimaryType));

                    if ($deepPrimaryType === AnimalSamples::class) {
                        $sub->leftJoin('animal_samples as deep_trace_animal_samples', 'deep_trace_animal_samples.id', '=', 'trace_parasites.parasites_origin_id')
                            ->leftJoin('animals as deep_trace_animals', 'deep_trace_animals.id', '=', 'deep_trace_animal_samples.animals_id')
                            ->leftJoin('animal_species as deep_trace_animal_species', 'deep_trace_animal_species.id', '=', 'deep_trace_animals.animal_species_id');

                        if ($this->traceDeepAnimalSpeciesFilter !== '') {
                            $sub->where('deep_trace_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                        }
                        if ($this->traceDeepAnimalSexFilter !== '') {
                            $sub->where('deep_trace_animals.sex', $this->traceDeepAnimalSexFilter);
                        }
                        if ($this->traceDeepAnimalAgeFilter !== '') {
                            $sub->where('deep_trace_animals.age', $this->traceDeepAnimalAgeFilter);
                        }
                    }

                    if ($deepPrimaryType === HumanSamples::class) {
                        $sub->leftJoin('human_samples as deep_trace_human_samples', 'deep_trace_human_samples.id', '=', 'trace_parasites.parasites_origin_id')
                            ->leftJoin('humans as deep_trace_humans', 'deep_trace_humans.id', '=', 'deep_trace_human_samples.humans_id')
                            ->leftJoin('countries as deep_trace_countries', 'deep_trace_countries.id', '=', 'deep_trace_humans.countries_id');

                        if ($this->traceDeepHumanEthnicityFilter !== '') {
                            $sub->where('deep_trace_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                        }
                        if ($this->traceDeepHumanOccupationFilter !== '') {
                            $sub->where('deep_trace_humans.occupation', $this->traceDeepHumanOccupationFilter);
                        }
                        if ($this->traceDeepHumanCountryFilter !== '') {
                            $sub->where('deep_trace_countries.name', $this->traceDeepHumanCountryFilter);
                        }
                    }
                }
            });

            return;
        }

        if (
            $this->sourceTypeFilter === 'pool'
            && $this->tracePrimaryTypeFilter === 'culture'
        ) {
            $deepPrimaryType = match ($this->traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            $query->whereExists(function ($sub) use ($hasDeepTrace, $deepPrimaryType) {
                $sub->select(DB::raw(1))
                    ->from('pool_contents')
                    ->join('cultures as trace_cultures', 'trace_cultures.id', '=', 'pool_contents.samples_id')
                    ->whereColumn('pool_contents.pools_id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                    ->where(function ($typeQ) {
                        $typeQ->whereIn('pool_contents.samples_type', $this->typeVariants(Cultures::class))
                            ->orWhereRaw('LOWER(pool_contents.samples_type) LIKE ?', ['%culture%']);
                    });

                if ($this->tracePrimaryCultureTypeFilter !== '') {
                    $sub->where('trace_cultures.type', $this->tracePrimaryCultureTypeFilter);
                }
                if ($this->tracePrimaryCultureMediumFilter !== '') {
                    $sub->where('trace_cultures.medium', $this->tracePrimaryCultureMediumFilter);
                }

                if ($hasDeepTrace && $deepPrimaryType) {
                    $sub->where(function ($deepQ) use ($deepPrimaryType) {
                        $deepQ->whereIn('trace_cultures.cultures_content_type', $this->typeVariants($deepPrimaryType))
                            ->orWhereExists(function ($parasiteQ) use ($deepPrimaryType) {
                                $parasiteQ->select(DB::raw(1))
                                    ->from('parasite_samples as deep_trace_parasite_samples')
                                    ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                                    ->whereColumn('deep_trace_parasite_samples.id', 'trace_cultures.cultures_content_id')
                                    ->whereIn('trace_cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                                    ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepPrimaryType));
                            });
                    });
                }
            });

            return;
        }

        if (
            $hasDeepTrace
            && $this->tracePrimaryTypeFilter === 'parasite'
            && in_array($this->traceDeepPrimaryTypeFilter, ['human', 'animal', 'environment'], true)
            && in_array($this->sourceTypeFilter, ['parasite', 'culture', 'pool'], true)
        ) {
            $deepOriginType = match ($this->traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
            };

            $query->whereExists(function ($sub) use ($deepOriginType) {
                if ($this->sourceTypeFilter === 'culture') {
                    $sub->select(DB::raw(1))
                        ->from('cultures')
                        ->join('parasite_samples as deep_trace_parasite_samples', 'deep_trace_parasite_samples.id', '=', 'cultures.cultures_content_id')
                        ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                        ->whereColumn('cultures.id', 'nucleic_acids.nucleic_content_id')
                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                        ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                        ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepOriginType));
                } elseif ($this->sourceTypeFilter === 'pool') {
                    $sub->select(DB::raw(1))
                        ->from('pool_contents')
                        ->join('parasite_samples as deep_trace_parasite_samples', 'deep_trace_parasite_samples.id', '=', 'pool_contents.samples_id')
                        ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                        ->whereColumn('pool_contents.pools_id', 'nucleic_acids.nucleic_content_id')
                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                        ->where(function ($typeQ) {
                            $typeQ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                                ->orWhereRaw('LOWER(pool_contents.samples_type) LIKE ?', ['%parasite%']);
                        })
                        ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepOriginType));
                } else {
                    $sub->select(DB::raw(1))
                        ->from('parasite_samples as deep_trace_parasite_samples')
                        ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                        ->whereColumn('deep_trace_parasite_samples.id', 'nucleic_acids.nucleic_content_id')
                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                        ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepOriginType));
                }

                if ($deepOriginType === AnimalSamples::class) {
                    $sub->leftJoin('animal_samples as deep_trace_animal_samples', 'deep_trace_animal_samples.id', '=', 'deep_trace_parasites.parasites_origin_id')
                        ->leftJoin('animals as deep_trace_animals', 'deep_trace_animals.id', '=', 'deep_trace_animal_samples.animals_id')
                        ->leftJoin('animal_species as deep_trace_animal_species', 'deep_trace_animal_species.id', '=', 'deep_trace_animals.animal_species_id');

                    if ($this->traceDeepAnimalSpeciesFilter !== '') {
                        $sub->where('deep_trace_animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
                    }
                    if ($this->traceDeepAnimalSexFilter !== '') {
                        $sub->where('deep_trace_animals.sex', $this->traceDeepAnimalSexFilter);
                    }
                    if ($this->traceDeepAnimalAgeFilter !== '') {
                        $sub->where('deep_trace_animals.age', $this->traceDeepAnimalAgeFilter);
                    }
                }

                if ($deepOriginType === HumanSamples::class) {
                    $sub->leftJoin('human_samples as deep_trace_human_samples', 'deep_trace_human_samples.id', '=', 'deep_trace_parasites.parasites_origin_id')
                        ->leftJoin('humans as deep_trace_humans', 'deep_trace_humans.id', '=', 'deep_trace_human_samples.humans_id')
                        ->leftJoin('countries as deep_trace_countries', 'deep_trace_countries.id', '=', 'deep_trace_humans.countries_id');

                    if ($this->traceDeepHumanEthnicityFilter !== '') {
                        $sub->where('deep_trace_humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
                    }
                    if ($this->traceDeepHumanOccupationFilter !== '') {
                        $sub->where('deep_trace_humans.occupation', $this->traceDeepHumanOccupationFilter);
                    }
                    if ($this->traceDeepHumanCountryFilter !== '') {
                        $sub->where('deep_trace_countries.name', $this->traceDeepHumanCountryFilter);
                    }
                }
            });

            return;
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'human' => HumanSamples::class,
            'animal' => AnimalSamples::class,
            'environment' => EnvironmentSamples::class,
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return;
        }

        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);
        if ($upstreamSeedIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($hasDeepTrace && in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            $deepPrimaryType = match ($this->traceDeepPrimaryTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            if ($deepPrimaryType) {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing($deepPrimaryType);
                $reachability = app(PrimarySampleReachability::class);

                $reachableUpstream = match ($upstreamType) {
                    ParasiteSamples::class => $reachability->parasiteSampleIdsFromSeed($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                    Cultures::class => $reachability->cultureIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                    NucleicAcids::class => $reachability->nucleicIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                    Pools::class => $reachability->poolIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
                    default => [],
                };

                if ($reachableUpstream === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $upstreamSeedIds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));

                if ($upstreamSeedIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }
            }
        }

        $reachability = app(PrimarySampleReachability::class);
        $maxDepth = $upstreamType === Pools::class ? 10 : 6;

        $ids = in_array($this->tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
            ? $reachability->nucleicIdsFromPrimary($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth)
            : $reachability->nucleicIdsFromSeed($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth);

        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('nucleic_acids.id', $ids);
    }

    /**
     * @param  class-string  $upstreamType
     * @return array<int,int>
     */
    private function seedIdsForTraceUpstream(string $upstreamType): array
    {
        if (in_array($upstreamType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
            return $this->primarySampleIdsForTracing($upstreamType);
        }

        if ($upstreamType === ParasiteSamples::class) {
            $q = ParasiteSamples::query()
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                ->select('parasite_samples.id');

            if ($this->tracePrimaryParasiteSpeciesFilter !== '') {
                $q->where('parasite_species.name_scientific', $this->tracePrimaryParasiteSpeciesFilter);
            }

            return $q->distinct()->limit(5000)->pluck('parasite_samples.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Cultures::class) {
            $q = Cultures::query()->select('cultures.id');

            if ($this->tracePrimaryCultureTypeFilter !== '') {
                $q->where('cultures.type', $this->tracePrimaryCultureTypeFilter);
            }
            if ($this->tracePrimaryCultureMediumFilter !== '') {
                $q->where('cultures.medium', $this->tracePrimaryCultureMediumFilter);
            }

            return $q->distinct()->limit(5000)->pluck('cultures.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === NucleicAcids::class) {
            $q = NucleicAcids::query()->select('nucleic_acids.id');

            if ($this->tracePrimaryNucleicTypeFilter !== '') {
                $q->where('nucleic_acids.type', $this->tracePrimaryNucleicTypeFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'nucleic_acids', NucleicAcids::class);

            return $q->distinct()->limit(5000)->pluck('nucleic_acids.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Pools::class) {
            $q = Pools::query()->select('pools.id');

            if ($this->tracePrimaryPoolMinNrPooled !== null) {
                $q->where('pools.nr_pooled', '>=', $this->tracePrimaryPoolMinNrPooled);
            }
            if ($this->tracePrimaryPoolMaxNrPooled !== null) {
                $q->where('pools.nr_pooled', '<=', $this->tracePrimaryPoolMaxNrPooled);
            }

            return $q->distinct()->limit(5000)->pluck('pools.id')->map(fn ($v) => (int) $v)->all();
        }

        return [];
    }

    private function applyVisibilityToSeedQuery($q, string $table, string $type): void
    {
        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) use ($table, $type) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                    ->where('tubes.is_private', false);
            });

            return;
        }

        if ($this->projectId) {
            $q->where(function ($w) use ($table, $type) {
                $w->where($table.'.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) use ($table, $type) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForDeepTracing(string $primaryType): array
    {
        $table = (new $primaryType)->getTable();
        $q = $primaryType::query()->select($table.'.id');

        if ($primaryType === AnimalSamples::class) {
            $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

            if ($this->traceDeepAnimalSpeciesFilter !== '') {
                $q->where('animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
            }
            if ($this->traceDeepAnimalSexFilter !== '') {
                $q->where('animals.sex', $this->traceDeepAnimalSexFilter);
            }
            if ($this->traceDeepAnimalAgeFilter !== '') {
                $q->where('animals.age', $this->traceDeepAnimalAgeFilter);
            }
        }

        if ($primaryType === HumanSamples::class) {
            $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

            if ($this->traceDeepHumanEthnicityFilter !== '') {
                $q->where('humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
            }
            if ($this->traceDeepHumanOccupationFilter !== '') {
                $q->where('humans.occupation', $this->traceDeepHumanOccupationFilter);
            }
            if ($this->traceDeepHumanCountryFilter !== '') {
                $q->where('countries.name', $this->traceDeepHumanCountryFilter);
            }
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) use ($primaryType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) use ($primaryType, $table) {
                $w->where($table.'.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) use ($primaryType, $table) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        return $q->limit(8000)->pluck($table.'.id')->map(fn ($v) => (int) $v)->all();
    }

    private function allCultureTypesForTrace()
    {
        $q = Cultures::query()->whereNotNull('cultures.type')->select('cultures.type');
        $this->applyVisibilityToSeedQuery($q, 'cultures', Cultures::class);

        return $q->distinct()->orderBy('cultures.type')->pluck('cultures.type')->filter()->values();
    }

    private function allCultureMediumsForTrace()
    {
        $q = Cultures::query()->whereNotNull('cultures.medium')->select('cultures.medium');
        $this->applyVisibilityToSeedQuery($q, 'cultures', Cultures::class);

        return $q->distinct()->orderBy('cultures.medium')->pluck('cultures.medium')->filter()->values();
    }

    private function tracePrimaryParasiteSpeciesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'parasite') {
            return collect();
        }

        $prev = $this->tracePrimaryParasiteSpeciesFilter;
        $this->tracePrimaryParasiteSpeciesFilter = '';
        $candidates = $this->allParasiteSpecies();
        $this->tracePrimaryParasiteSpeciesFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryParasiteSpeciesFilter = (string) $candidate;
            $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryParasiteSpeciesFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryCultureTypesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'culture') {
            return collect();
        }

        $prev = $this->tracePrimaryCultureTypeFilter;
        $this->tracePrimaryCultureTypeFilter = '';
        $candidates = $this->allCultureTypesForTrace();
        $this->tracePrimaryCultureTypeFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryCultureTypeFilter = (string) $candidate;
            $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryCultureTypeFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryCultureMediumsOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'culture') {
            return collect();
        }

        $prev = $this->tracePrimaryCultureMediumFilter;
        $this->tracePrimaryCultureMediumFilter = '';
        $candidates = $this->allCultureMediumsForTrace();
        $this->tracePrimaryCultureMediumFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryCultureMediumFilter = (string) $candidate;
            $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryCultureMediumFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryNucleicTypesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'nucleic') {
            return collect();
        }

        $prev = $this->tracePrimaryNucleicTypeFilter;
        $this->tracePrimaryNucleicTypeFilter = '';
        $candidates = $this->allNucleicTypes();
        $this->tracePrimaryNucleicTypeFilter = $prev;

        $out = [];
        foreach ($candidates as $candidate) {
            $this->tracePrimaryNucleicTypeFilter = (string) $candidate;
            $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
            if ($has) {
                $out[] = (string) $candidate;
            }
        }

        $this->tracePrimaryNucleicTypeFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    /**
     * @return array<string,string> key => label
     */
    private function availableTraceDeepPrimaryTypes(): array
    {
        $tracePrimaryType = $this->normalizeFilterValue($this->tracePrimaryTypeFilter);
        $sourceType = $this->normalizeFilterValue($this->sourceTypeFilter);
        if ($this->tracePrimaryTypeFilter !== $tracePrimaryType) {
            $this->tracePrimaryTypeFilter = $tracePrimaryType;
        }
        if ($this->sourceTypeFilter !== $sourceType) {
            $this->sourceTypeFilter = $sourceType;
        }

        if (! in_array($tracePrimaryType, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return [];
        }

        $prevState = [
            'traceDeepPrimaryTypeFilter' => $this->traceDeepPrimaryTypeFilter,
            'traceDeepAnimalSpeciesFilter' => $this->traceDeepAnimalSpeciesFilter,
            'traceDeepAnimalSexFilter' => $this->traceDeepAnimalSexFilter,
            'traceDeepAnimalAgeFilter' => $this->traceDeepAnimalAgeFilter,
            'traceDeepHumanEthnicityFilter' => $this->traceDeepHumanEthnicityFilter,
            'traceDeepHumanOccupationFilter' => $this->traceDeepHumanOccupationFilter,
            'traceDeepHumanCountryFilter' => $this->traceDeepHumanCountryFilter,
        ];

        $this->traceDeepPrimaryTypeFilter = 'all';
        $this->traceDeepAnimalSpeciesFilter = '';
        $this->traceDeepAnimalSexFilter = '';
        $this->traceDeepAnimalAgeFilter = '';
        $this->traceDeepHumanEthnicityFilter = '';
        $this->traceDeepHumanOccupationFilter = '';
        $this->traceDeepHumanCountryFilter = '';

        $out = [];
        foreach (['human', 'animal', 'environment'] as $candidate) {
            $this->traceDeepPrimaryTypeFilter = $candidate;

            if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                $out[$candidate] = ucfirst($candidate);
            }
        }

        $this->traceDeepPrimaryTypeFilter = $prevState['traceDeepPrimaryTypeFilter'];
        $this->traceDeepAnimalSpeciesFilter = $prevState['traceDeepAnimalSpeciesFilter'];
        $this->traceDeepAnimalSexFilter = $prevState['traceDeepAnimalSexFilter'];
        $this->traceDeepAnimalAgeFilter = $prevState['traceDeepAnimalAgeFilter'];
        $this->traceDeepHumanEthnicityFilter = $prevState['traceDeepHumanEthnicityFilter'];
        $this->traceDeepHumanOccupationFilter = $prevState['traceDeepHumanOccupationFilter'];
        $this->traceDeepHumanCountryFilter = $prevState['traceDeepHumanCountryFilter'];

        if ($prevState['traceDeepPrimaryTypeFilter'] !== 'all' && ! isset($out[$prevState['traceDeepPrimaryTypeFilter']])) {
            $out = [$prevState['traceDeepPrimaryTypeFilter'] => ucfirst($prevState['traceDeepPrimaryTypeFilter'])] + $out;
        }

        return $out;
    }

    private function normalizeFilterValue(string $value): string
    {
        return match (strtolower(trim($value))) {
            'humans' => 'human',
            'animals' => 'animal',
            'environments' => 'environment',
            'parasites' => 'parasite',
            'cultures' => 'culture',
            'pools' => 'pool',
            'nucleics' => 'nucleic',
            default => strtolower(trim($value)),
        };
    }

    private function allAnimalSexesForTrace()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex')
            ->select('animals.sex');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('animal_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('animals.sex')->pluck('animals.sex')->filter()->values();
    }

    private function allAnimalAgesForTrace()
    {
        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age')
            ->select('animals.age');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('animal_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('animals.age')->pluck('animals.age')->filter()->values();
    }

    private function allHumanEthnicitiesForTrace()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->select('humans.ethnicity');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('human_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('humans.ethnicity')->pluck('humans.ethnicity')->filter()->values();
    }

    private function allHumanOccupationsForTrace()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->select('humans.occupation');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('human_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('humans.occupation')->pluck('humans.occupation')->filter()->values();
    }

    private function allHumanCountriesForTrace()
    {
        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->select('countries.name');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('human_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('countries.name')->pluck('countries.name')->filter()->values();
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForTracing(string $primaryType): array
    {
        $table = (new $primaryType)->getTable();

        $q = $primaryType::query()->select($table.'.id');

        if ($primaryType === AnimalSamples::class) {
            $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

            if ($this->tracePrimaryAnimalSpeciesFilter !== '') {
                $q->where('animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter);
            }

            if ($this->tracePrimaryAnimalSexFilter !== '') {
                $q->where('animals.sex', $this->tracePrimaryAnimalSexFilter);
            }

            if ($this->tracePrimaryAnimalAgeFilter !== '') {
                $q->where('animals.age', $this->tracePrimaryAnimalAgeFilter);
            }
        }

        if ($primaryType === HumanSamples::class) {
            $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

            if ($this->tracePrimaryHumanEthnicityFilter !== '') {
                $q->where('humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter);
            }

            if ($this->tracePrimaryHumanOccupationFilter !== '') {
                $q->where('humans.occupation', $this->tracePrimaryHumanOccupationFilter);
            }

            if ($this->tracePrimaryHumanCountryFilter !== '') {
                $q->where('countries.name', $this->tracePrimaryHumanCountryFilter);
            }
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) use ($primaryType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) use ($primaryType, $table) {
                $w->where($table.'.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) use ($primaryType, $table) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        return $q->limit(8000)->pluck($table.'.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @return array<int,int> nucleic_acids.id
     */
    private function baseNucleicIdsForTraceOptions(array $overrides): array
    {
        $original = [];
        foreach ($overrides as $key => $value) {
            $original[$key] = $this->{$key};
            $this->{$key} = $value;
        }

        try {
            return $this->baseQuery()
                ->select('nucleic_acids.id')
                ->distinct()
                ->limit(5000)
                ->pluck('nucleic_acids.id')
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all();
        } finally {
            foreach ($original as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return array<int,int> nucleic_acids.id
     */
    private function baseNucleicIdsForDeepOptions(array $overrides): array
    {
        return $this->baseNucleicIdsForTraceOptions($overrides);
    }

    /**
     * @param  class-string  $upstreamType
     * @param  class-string  $deepPrimaryType
     * @param  array<int,int>  $deepPrimaryIds
     * @return array<int,int>
     */
    private function reachableUpstreamIdsFromDeepPrimary(string $upstreamType, string $deepPrimaryType, array $deepPrimaryIds): array
    {
        $reachability = app(PrimarySampleReachability::class);

        return match ($upstreamType) {
            ParasiteSamples::class => $reachability->parasiteSampleIdsFromSeed($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            Cultures::class => $reachability->cultureIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            NucleicAcids::class => $reachability->nucleicIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            Pools::class => $reachability->poolIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $this->projectId, $this->isGuestMode(), maxDepth: 10),
            default => [],
        };
    }

    /**
     * @param  class-string  $upstreamType
     * @param  array<int,int>  $upstreamSeedIds
     * @return array<int,int> nucleic_acids.id
     */
    private function nucleicIdsFromUpstreamSeeds(string $upstreamType, array $upstreamSeedIds): array
    {
        $reachability = app(PrimarySampleReachability::class);
        $maxDepth = $upstreamType === Pools::class ? 10 : 6;

        return $reachability->nucleicIdsFromSeed($upstreamType, $upstreamSeedIds, $this->projectId, $this->isGuestMode(), $maxDepth);
    }

    private function traceDeepAnimalSpeciesOptions()
    {
        if (
            in_array($this->sourceTypeFilter, ['pool', 'culture'], true)
            && $this->tracePrimaryTypeFilter === 'parasite'
            && $this->traceDeepPrimaryTypeFilter === 'animal'
        ) {
            $prev = $this->traceDeepAnimalSpeciesFilter;
            $this->traceDeepAnimalSpeciesFilter = '';
            $candidates = $this->allAnimalSpecies();
            $this->traceDeepAnimalSpeciesFilter = $prev;

            $out = [];
            foreach ($candidates as $candidate) {
                $this->traceDeepAnimalSpeciesFilter = (string) $candidate;
                $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
                if ($has) {
                    $out[] = (string) $candidate;
                }
            }

            $this->traceDeepAnimalSpeciesFilter = $prev;

            $col = collect($out)->values();
            if ($prev !== '' && ! $col->contains($prev)) {
                $col = collect([$prev])->merge($col)->values();
            }

            return $col;
        }

        if ($this->traceDeepPrimaryTypeFilter !== 'animal' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseNucleicIdsForDeepOptions(['traceDeepAnimalSpeciesFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->whereNotNull('animal_species.name_common');

        if ($this->traceDeepAnimalSexFilter !== '') {
            $q->where('animals.sex', $this->traceDeepAnimalSexFilter);
        }
        if ($this->traceDeepAnimalAgeFilter !== '') {
            $q->where('animals.age', $this->traceDeepAnimalAgeFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('animal_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('animal_species.name_common')->pluck('animal_species.name_common')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepAnimalSpeciesFilter;
            $this->traceDeepAnimalSpeciesFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(AnimalSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, AnimalSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->nucleicIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepAnimalSpeciesFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepAnimalSpeciesFilter !== '' && ! $col->contains($this->traceDeepAnimalSpeciesFilter)) {
            $col = collect([$this->traceDeepAnimalSpeciesFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepAnimalSexesOptions()
    {
        if (
            in_array($this->sourceTypeFilter, ['pool', 'culture'], true)
            && $this->tracePrimaryTypeFilter === 'parasite'
            && $this->traceDeepPrimaryTypeFilter === 'animal'
        ) {
            $prev = $this->traceDeepAnimalSexFilter;
            $this->traceDeepAnimalSexFilter = '';
            $candidates = $this->allAnimalSexesForTrace();
            $this->traceDeepAnimalSexFilter = $prev;

            $out = [];
            foreach ($candidates as $candidate) {
                $this->traceDeepAnimalSexFilter = (string) $candidate;
                $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
                if ($has) {
                    $out[] = (string) $candidate;
                }
            }

            $this->traceDeepAnimalSexFilter = $prev;

            $col = collect($out)->values();
            if ($prev !== '' && ! $col->contains($prev)) {
                $col = collect([$prev])->merge($col)->values();
            }

            return $col;
        }

        if ($this->traceDeepPrimaryTypeFilter !== 'animal' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseNucleicIdsForDeepOptions(['traceDeepAnimalSexFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.sex');

        if ($this->traceDeepAnimalSpeciesFilter !== '') {
            $q->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                ->where('animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
        }
        if ($this->traceDeepAnimalAgeFilter !== '') {
            $q->where('animals.age', $this->traceDeepAnimalAgeFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('animal_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('animals.sex')->pluck('animals.sex')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepAnimalSexFilter;
            $this->traceDeepAnimalSexFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(AnimalSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, AnimalSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->nucleicIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepAnimalSexFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepAnimalSexFilter !== '' && ! $col->contains($this->traceDeepAnimalSexFilter)) {
            $col = collect([$this->traceDeepAnimalSexFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepAnimalAgesOptions()
    {
        if (
            in_array($this->sourceTypeFilter, ['pool', 'culture'], true)
            && $this->tracePrimaryTypeFilter === 'parasite'
            && $this->traceDeepPrimaryTypeFilter === 'animal'
        ) {
            $prev = $this->traceDeepAnimalAgeFilter;
            $this->traceDeepAnimalAgeFilter = '';
            $candidates = $this->allAnimalAgesForTrace();
            $this->traceDeepAnimalAgeFilter = $prev;

            $out = [];
            foreach ($candidates as $candidate) {
                $this->traceDeepAnimalAgeFilter = (string) $candidate;
                $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
                if ($has) {
                    $out[] = (string) $candidate;
                }
            }

            $this->traceDeepAnimalAgeFilter = $prev;

            $col = collect($out)->values();
            if ($prev !== '' && ! $col->contains($prev)) {
                $col = collect([$prev])->merge($col)->values();
            }

            return $col;
        }

        if ($this->traceDeepPrimaryTypeFilter !== 'animal' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseNucleicIdsForDeepOptions(['traceDeepAnimalAgeFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->whereNotNull('animals.age');

        if ($this->traceDeepAnimalSpeciesFilter !== '') {
            $q->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                ->where('animal_species.name_common', $this->traceDeepAnimalSpeciesFilter);
        }
        if ($this->traceDeepAnimalSexFilter !== '') {
            $q->where('animals.sex', $this->traceDeepAnimalSexFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('animal_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(AnimalSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('animals.age')->pluck('animals.age')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepAnimalAgeFilter;
            $this->traceDeepAnimalAgeFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(AnimalSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, AnimalSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->nucleicIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepAnimalAgeFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepAnimalAgeFilter !== '' && ! $col->contains($this->traceDeepAnimalAgeFilter)) {
            $col = collect([$this->traceDeepAnimalAgeFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepHumanEthnicitiesOptions()
    {
        $sourceType = $this->normalizeFilterValue($this->sourceTypeFilter);
        if ($this->sourceTypeFilter !== $sourceType) {
            $this->sourceTypeFilter = $sourceType;
        }

        if (
            in_array($sourceType, ['pool', 'culture'], true)
            && $this->tracePrimaryTypeFilter === 'parasite'
            && $this->traceDeepPrimaryTypeFilter === 'human'
        ) {
            $prev = $this->traceDeepHumanEthnicityFilter;
            $this->traceDeepHumanEthnicityFilter = '';
            if ($sourceType === 'culture') {
                $candidates = (clone $this->baseQueryForOptions([
                    'traceDeepHumanEthnicityFilter',
                    'traceDeepHumanOccupationFilter',
                    'traceDeepHumanCountryFilter',
                ]))
                    ->join('cultures as option_cultures', 'option_cultures.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'option_cultures.cultures_content_id')
                    ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                    ->join('human_samples as option_human_samples', 'option_human_samples.id', '=', 'option_parasites.parasites_origin_id')
                    ->leftJoin('humans as option_humans', 'option_humans.id', '=', 'option_human_samples.humans_id')
                    ->leftJoin('countries as option_countries', 'option_countries.id', '=', 'option_humans.countries_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                    ->whereIn('option_cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                    ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                    ->whereNotNull('option_humans.ethnicity')
                    ->when($this->traceDeepHumanOccupationFilter !== '', fn ($q) => $q->where('option_humans.occupation', $this->traceDeepHumanOccupationFilter))
                    ->when($this->traceDeepHumanCountryFilter !== '', fn ($q) => $q->where('option_countries.name', $this->traceDeepHumanCountryFilter))
                    ->distinct()
                    ->orderBy('option_humans.ethnicity')
                    ->pluck('option_humans.ethnicity')
                    ->filter()
                    ->values();
            } else {
                $candidates = $this->allHumanEthnicitiesForTrace();
            }
            $this->traceDeepHumanEthnicityFilter = $prev;

            $out = [];
            foreach ($candidates as $candidate) {
                $this->traceDeepHumanEthnicityFilter = (string) $candidate;
                $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
                if ($has) {
                    $out[] = (string) $candidate;
                }
            }

            $this->traceDeepHumanEthnicityFilter = $prev;

            $col = collect($out)->values();
            if ($prev !== '' && ! $col->contains($prev)) {
                $col = collect([$prev])->merge($col)->values();
            }

            return $col;
        }

        if ($this->traceDeepPrimaryTypeFilter !== 'human' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseNucleicIdsForDeepOptions(['traceDeepHumanEthnicityFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.ethnicity')
            ->select('humans.ethnicity');

        if ($this->traceDeepHumanOccupationFilter !== '') {
            $q->where('humans.occupation', $this->traceDeepHumanOccupationFilter);
        }
        if ($this->traceDeepHumanCountryFilter !== '') {
            $q->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
                ->where('countries.name', $this->traceDeepHumanCountryFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('human_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('humans.ethnicity')->pluck('humans.ethnicity')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepHumanEthnicityFilter;
            $this->traceDeepHumanEthnicityFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(HumanSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, HumanSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->nucleicIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepHumanEthnicityFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepHumanEthnicityFilter !== '' && ! $col->contains($this->traceDeepHumanEthnicityFilter)) {
            $col = collect([$this->traceDeepHumanEthnicityFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepHumanOccupationsOptions()
    {
        $sourceType = $this->normalizeFilterValue($this->sourceTypeFilter);
        if ($this->sourceTypeFilter !== $sourceType) {
            $this->sourceTypeFilter = $sourceType;
        }

        if (
            in_array($sourceType, ['pool', 'culture'], true)
            && $this->tracePrimaryTypeFilter === 'parasite'
            && $this->traceDeepPrimaryTypeFilter === 'human'
        ) {
            $prev = $this->traceDeepHumanOccupationFilter;
            $this->traceDeepHumanOccupationFilter = '';
            if ($sourceType === 'culture') {
                $candidates = (clone $this->baseQueryForOptions([
                    'traceDeepHumanEthnicityFilter',
                    'traceDeepHumanOccupationFilter',
                    'traceDeepHumanCountryFilter',
                ]))
                    ->join('cultures as option_cultures', 'option_cultures.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'option_cultures.cultures_content_id')
                    ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                    ->join('human_samples as option_human_samples', 'option_human_samples.id', '=', 'option_parasites.parasites_origin_id')
                    ->leftJoin('humans as option_humans', 'option_humans.id', '=', 'option_human_samples.humans_id')
                    ->leftJoin('countries as option_countries', 'option_countries.id', '=', 'option_humans.countries_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                    ->whereIn('option_cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                    ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                    ->whereNotNull('option_humans.occupation')
                    ->when($this->traceDeepHumanEthnicityFilter !== '', fn ($q) => $q->where('option_humans.ethnicity', $this->traceDeepHumanEthnicityFilter))
                    ->when($this->traceDeepHumanCountryFilter !== '', fn ($q) => $q->where('option_countries.name', $this->traceDeepHumanCountryFilter))
                    ->distinct()
                    ->orderBy('option_humans.occupation')
                    ->pluck('option_humans.occupation')
                    ->filter()
                    ->values();
            } else {
                $candidates = $this->allHumanOccupationsForTrace();
            }
            $this->traceDeepHumanOccupationFilter = $prev;

            $out = [];
            foreach ($candidates as $candidate) {
                $this->traceDeepHumanOccupationFilter = (string) $candidate;
                $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
                if ($has) {
                    $out[] = (string) $candidate;
                }
            }

            $this->traceDeepHumanOccupationFilter = $prev;

            $col = collect($out)->values();
            if ($prev !== '' && ! $col->contains($prev)) {
                $col = collect([$prev])->merge($col)->values();
            }

            return $col;
        }

        if ($this->traceDeepPrimaryTypeFilter !== 'human' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseNucleicIdsForDeepOptions(['traceDeepHumanOccupationFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->whereNotNull('humans.occupation')
            ->select('humans.occupation');

        if ($this->traceDeepHumanEthnicityFilter !== '') {
            $q->where('humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
        }
        if ($this->traceDeepHumanCountryFilter !== '') {
            $q->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
                ->where('countries.name', $this->traceDeepHumanCountryFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('human_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('humans.occupation')->pluck('humans.occupation')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepHumanOccupationFilter;
            $this->traceDeepHumanOccupationFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(HumanSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, HumanSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->nucleicIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepHumanOccupationFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepHumanOccupationFilter !== '' && ! $col->contains($this->traceDeepHumanOccupationFilter)) {
            $col = collect([$this->traceDeepHumanOccupationFilter])->merge($col)->values();
        }

        return $col;
    }

    private function traceDeepHumanCountriesOptions()
    {
        $sourceType = $this->normalizeFilterValue($this->sourceTypeFilter);
        if ($this->sourceTypeFilter !== $sourceType) {
            $this->sourceTypeFilter = $sourceType;
        }

        if (
            in_array($sourceType, ['pool', 'culture'], true)
            && $this->tracePrimaryTypeFilter === 'parasite'
            && $this->traceDeepPrimaryTypeFilter === 'human'
        ) {
            $prev = $this->traceDeepHumanCountryFilter;
            $this->traceDeepHumanCountryFilter = '';
            if ($sourceType === 'culture') {
                $candidates = (clone $this->baseQueryForOptions([
                    'traceDeepHumanEthnicityFilter',
                    'traceDeepHumanOccupationFilter',
                    'traceDeepHumanCountryFilter',
                ]))
                    ->join('cultures as option_cultures', 'option_cultures.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'option_cultures.cultures_content_id')
                    ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                    ->join('human_samples as option_human_samples', 'option_human_samples.id', '=', 'option_parasites.parasites_origin_id')
                    ->leftJoin('humans as option_humans', 'option_humans.id', '=', 'option_human_samples.humans_id')
                    ->leftJoin('countries as option_countries', 'option_countries.id', '=', 'option_humans.countries_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                    ->whereIn('option_cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                    ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                    ->whereNotNull('option_countries.name')
                    ->when($this->traceDeepHumanEthnicityFilter !== '', fn ($q) => $q->where('option_humans.ethnicity', $this->traceDeepHumanEthnicityFilter))
                    ->when($this->traceDeepHumanOccupationFilter !== '', fn ($q) => $q->where('option_humans.occupation', $this->traceDeepHumanOccupationFilter))
                    ->distinct()
                    ->orderBy('option_countries.name')
                    ->pluck('option_countries.name')
                    ->filter()
                    ->values();
            } else {
                $candidates = $this->allHumanCountriesForTrace();
            }
            $this->traceDeepHumanCountryFilter = $prev;

            $out = [];
            foreach ($candidates as $candidate) {
                $this->traceDeepHumanCountryFilter = (string) $candidate;
                $has = (clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists();
                if ($has) {
                    $out[] = (string) $candidate;
                }
            }

            $this->traceDeepHumanCountryFilter = $prev;

            $col = collect($out)->values();
            if ($prev !== '' && ! $col->contains($prev)) {
                $col = collect([$prev])->merge($col)->values();
            }

            return $col;
        }

        if ($this->traceDeepPrimaryTypeFilter !== 'human' || ! in_array($this->tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
            return collect();
        }

        $baseIds = $this->baseNucleicIdsForDeepOptions(['traceDeepHumanCountryFilter' => '']);
        if ($baseIds === []) {
            return collect();
        }

        $upstreamType = match ($this->tracePrimaryTypeFilter) {
            'parasite' => ParasiteSamples::class,
            'culture' => Cultures::class,
            'nucleic' => NucleicAcids::class,
            'pool' => Pools::class,
            default => null,
        };

        if (! $upstreamType) {
            return collect();
        }

        $baseSet = array_fill_keys($baseIds, true);
        $upstreamSeedIds = $this->seedIdsForTraceUpstream($upstreamType);

        $q = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->whereNotNull('countries.name')
            ->select('countries.name');

        if ($this->traceDeepHumanEthnicityFilter !== '') {
            $q->where('humans.ethnicity', $this->traceDeepHumanEthnicityFilter);
        }
        if ($this->traceDeepHumanOccupationFilter !== '') {
            $q->where('humans.occupation', $this->traceDeepHumanOccupationFilter);
        }

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where(function ($w) {
                $w->where('human_samples.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(HumanSamples::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }

        $candidates = $q->distinct()->orderBy('countries.name')->pluck('countries.name')->filter()->values();

        $out = [];
        foreach ($candidates as $candidate) {
            $prev = $this->traceDeepHumanCountryFilter;
            $this->traceDeepHumanCountryFilter = (string) $candidate;

            try {
                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(HumanSamples::class);
                $reachableUpstream = $this->reachableUpstreamIdsFromDeepPrimary($upstreamType, HumanSamples::class, $deepPrimaryIds);
                if ($reachableUpstream === []) {
                    continue;
                }

                $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                $effectiveSeeds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                if ($effectiveSeeds === []) {
                    continue;
                }

                $ids = $this->nucleicIdsFromUpstreamSeeds($upstreamType, $effectiveSeeds);
                foreach ($ids as $id) {
                    if (isset($baseSet[(int) $id])) {
                        $out[] = (string) $candidate;
                        break;
                    }
                }
            } finally {
                $this->traceDeepHumanCountryFilter = $prev;
            }
        }

        $col = collect($out)->values();
        if ($this->traceDeepHumanCountryFilter !== '' && ! $col->contains($this->traceDeepHumanCountryFilter)) {
            $col = collect([$this->traceDeepHumanCountryFilter])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryAnimalSpeciesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $prev = $this->tracePrimaryAnimalSpeciesFilter;
        $this->tracePrimaryAnimalSpeciesFilter = '';
        if ($this->sourceTypeFilter === 'parasite') {
            $species = (clone $this->baseQueryForOptions([
                'tracePrimaryAnimalSpeciesFilter',
                'tracePrimaryAnimalSexFilter',
                'tracePrimaryAnimalAgeFilter',
            ]))
                ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                ->join('animal_samples as option_animal_samples', 'option_animal_samples.id', '=', 'option_parasites.parasites_origin_id')
                ->join('animals as option_animals', 'option_animals.id', '=', 'option_animal_samples.animals_id')
                ->leftJoin('animal_species as option_animal_species', 'option_animal_species.id', '=', 'option_animals.animal_species_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('option_animal_species.name_common')
                ->distinct()
                ->orderBy('option_animal_species.name_common')
                ->pluck('option_animal_species.name_common')
                ->filter()
                ->values();
        } else {
            $species = $this->allAnimalSpecies();
        }
        $this->tracePrimaryAnimalSpeciesFilter = $prev;

        $out = [];
        foreach ($species as $name) {
            $this->tracePrimaryAnimalSpeciesFilter = (string) $name;
            if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                $out[] = (string) $name;
            }
        }
        $this->tracePrimaryAnimalSpeciesFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryAnimalSexesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $prev = $this->tracePrimaryAnimalSexFilter;
        $this->tracePrimaryAnimalSexFilter = '';
        if ($this->sourceTypeFilter === 'parasite') {
            $sexes = (clone $this->baseQueryForOptions([
                'tracePrimaryAnimalSpeciesFilter',
                'tracePrimaryAnimalSexFilter',
                'tracePrimaryAnimalAgeFilter',
            ]))
                ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                ->join('animal_samples as option_animal_samples', 'option_animal_samples.id', '=', 'option_parasites.parasites_origin_id')
                ->join('animals as option_animals', 'option_animals.id', '=', 'option_animal_samples.animals_id')
                ->leftJoin('animal_species as option_animal_species', 'option_animal_species.id', '=', 'option_animals.animal_species_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('option_animals.sex')
                ->when($this->tracePrimaryAnimalSpeciesFilter !== '', fn ($q) => $q->where('option_animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter))
                ->when($this->tracePrimaryAnimalAgeFilter !== '', fn ($q) => $q->where('option_animals.age', $this->tracePrimaryAnimalAgeFilter))
                ->distinct()
                ->orderBy('option_animals.sex')
                ->pluck('option_animals.sex')
                ->filter()
                ->values();
        } else {
            $sexes = $this->allAnimalSexesForTrace();
        }
        $this->tracePrimaryAnimalSexFilter = $prev;

        $out = [];
        foreach ($sexes as $sex) {
            $this->tracePrimaryAnimalSexFilter = (string) $sex;
            if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                $out[] = (string) $sex;
            }
        }
        $this->tracePrimaryAnimalSexFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryAnimalAgesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'animal') {
            return collect();
        }

        $prev = $this->tracePrimaryAnimalAgeFilter;
        $this->tracePrimaryAnimalAgeFilter = '';
        if ($this->sourceTypeFilter === 'parasite') {
            $ages = (clone $this->baseQueryForOptions([
                'tracePrimaryAnimalSpeciesFilter',
                'tracePrimaryAnimalSexFilter',
                'tracePrimaryAnimalAgeFilter',
            ]))
                ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                ->join('animal_samples as option_animal_samples', 'option_animal_samples.id', '=', 'option_parasites.parasites_origin_id')
                ->join('animals as option_animals', 'option_animals.id', '=', 'option_animal_samples.animals_id')
                ->leftJoin('animal_species as option_animal_species', 'option_animal_species.id', '=', 'option_animals.animal_species_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('option_animals.age')
                ->when($this->tracePrimaryAnimalSpeciesFilter !== '', fn ($q) => $q->where('option_animal_species.name_common', $this->tracePrimaryAnimalSpeciesFilter))
                ->when($this->tracePrimaryAnimalSexFilter !== '', fn ($q) => $q->where('option_animals.sex', $this->tracePrimaryAnimalSexFilter))
                ->distinct()
                ->orderBy('option_animals.age')
                ->pluck('option_animals.age')
                ->filter()
                ->values();
        } else {
            $ages = $this->allAnimalAgesForTrace();
        }
        $this->tracePrimaryAnimalAgeFilter = $prev;

        $out = [];
        foreach ($ages as $age) {
            $this->tracePrimaryAnimalAgeFilter = (string) $age;
            if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                $out[] = (string) $age;
            }
        }
        $this->tracePrimaryAnimalAgeFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryHumanEthnicitiesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $prev = $this->tracePrimaryHumanEthnicityFilter;
        $this->tracePrimaryHumanEthnicityFilter = '';
        if ($this->sourceTypeFilter === 'parasite') {
            $ethnicities = (clone $this->baseQueryForOptions([
                'tracePrimaryHumanEthnicityFilter',
                'tracePrimaryHumanOccupationFilter',
                'tracePrimaryHumanCountryFilter',
            ]))
                ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                ->join('human_samples as option_human_samples', 'option_human_samples.id', '=', 'option_parasites.parasites_origin_id')
                ->leftJoin('humans as option_humans', 'option_humans.id', '=', 'option_human_samples.humans_id')
                ->leftJoin('countries as option_countries', 'option_countries.id', '=', 'option_humans.countries_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('option_humans.ethnicity')
                ->when($this->tracePrimaryHumanOccupationFilter !== '', fn ($q) => $q->where('option_humans.occupation', $this->tracePrimaryHumanOccupationFilter))
                ->when($this->tracePrimaryHumanCountryFilter !== '', fn ($q) => $q->where('option_countries.name', $this->tracePrimaryHumanCountryFilter))
                ->distinct()
                ->orderBy('option_humans.ethnicity')
                ->pluck('option_humans.ethnicity')
                ->filter()
                ->values();
        } else {
            $ethnicities = $this->allHumanEthnicitiesForTrace();
        }
        $this->tracePrimaryHumanEthnicityFilter = $prev;

        $out = [];
        foreach ($ethnicities as $ethnicity) {
            $this->tracePrimaryHumanEthnicityFilter = (string) $ethnicity;
            if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                $out[] = (string) $ethnicity;
            }
        }
        $this->tracePrimaryHumanEthnicityFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryHumanOccupationsOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $prev = $this->tracePrimaryHumanOccupationFilter;
        $this->tracePrimaryHumanOccupationFilter = '';
        if ($this->sourceTypeFilter === 'parasite') {
            $occupations = (clone $this->baseQueryForOptions([
                'tracePrimaryHumanEthnicityFilter',
                'tracePrimaryHumanOccupationFilter',
                'tracePrimaryHumanCountryFilter',
            ]))
                ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                ->join('human_samples as option_human_samples', 'option_human_samples.id', '=', 'option_parasites.parasites_origin_id')
                ->leftJoin('humans as option_humans', 'option_humans.id', '=', 'option_human_samples.humans_id')
                ->leftJoin('countries as option_countries', 'option_countries.id', '=', 'option_humans.countries_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('option_humans.occupation')
                ->when($this->tracePrimaryHumanEthnicityFilter !== '', fn ($q) => $q->where('option_humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter))
                ->when($this->tracePrimaryHumanCountryFilter !== '', fn ($q) => $q->where('option_countries.name', $this->tracePrimaryHumanCountryFilter))
                ->distinct()
                ->orderBy('option_humans.occupation')
                ->pluck('option_humans.occupation')
                ->filter()
                ->values();
        } else {
            $occupations = $this->allHumanOccupationsForTrace();
        }
        $this->tracePrimaryHumanOccupationFilter = $prev;

        $out = [];
        foreach ($occupations as $occupation) {
            $this->tracePrimaryHumanOccupationFilter = (string) $occupation;
            if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                $out[] = (string) $occupation;
            }
        }
        $this->tracePrimaryHumanOccupationFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function tracePrimaryHumanCountriesOptions()
    {
        if ($this->tracePrimaryTypeFilter !== 'human') {
            return collect();
        }

        $prev = $this->tracePrimaryHumanCountryFilter;
        $this->tracePrimaryHumanCountryFilter = '';
        if ($this->sourceTypeFilter === 'parasite') {
            $countries = (clone $this->baseQueryForOptions([
                'tracePrimaryHumanEthnicityFilter',
                'tracePrimaryHumanOccupationFilter',
                'tracePrimaryHumanCountryFilter',
            ]))
                ->join('parasite_samples as option_parasite_samples', 'option_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as option_parasites', 'option_parasites.id', '=', 'option_parasite_samples.parasites_id')
                ->join('human_samples as option_human_samples', 'option_human_samples.id', '=', 'option_parasites.parasites_origin_id')
                ->leftJoin('humans as option_humans', 'option_humans.id', '=', 'option_human_samples.humans_id')
                ->leftJoin('countries as option_countries', 'option_countries.id', '=', 'option_humans.countries_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereIn('option_parasites.parasites_origin_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('option_countries.name')
                ->when($this->tracePrimaryHumanEthnicityFilter !== '', fn ($q) => $q->where('option_humans.ethnicity', $this->tracePrimaryHumanEthnicityFilter))
                ->when($this->tracePrimaryHumanOccupationFilter !== '', fn ($q) => $q->where('option_humans.occupation', $this->tracePrimaryHumanOccupationFilter))
                ->distinct()
                ->orderBy('option_countries.name')
                ->pluck('option_countries.name')
                ->filter()
                ->values();
        } else {
            $countries = $this->allHumanCountriesForTrace();
        }
        $this->tracePrimaryHumanCountryFilter = $prev;

        $out = [];
        foreach ($countries as $country) {
            $this->tracePrimaryHumanCountryFilter = (string) $country;
            if ((clone $this->baseQuery())->select('nucleic_acids.id')->limit(1)->exists()) {
                $out[] = (string) $country;
            }
        }
        $this->tracePrimaryHumanCountryFilter = $prev;

        $col = collect($out)->values();
        if ($prev !== '' && ! $col->contains($prev)) {
            $col = collect([$prev])->merge($col)->values();
        }

        return $col;
    }

    private function dashboardPayload(): array
    {
        $base = $this->baseQuery();

        $now = Carbon::now();
        $startOfYear = $now->copy()->startOfYear()->toDateString();
        $endOfYear = $now->copy()->endOfYear()->toDateString();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $total = (clone $base)->count('nucleic_acids.id');

        $human = (clone $base)->where('nucleic_acids.nucleic_content_type', HumanSamples::class)->count('nucleic_acids.id');
        $animal = (clone $base)->where('nucleic_acids.nucleic_content_type', AnimalSamples::class)->count('nucleic_acids.id');
        $environment = (clone $base)->where('nucleic_acids.nucleic_content_type', EnvironmentSamples::class)->count('nucleic_acids.id');
        $parasite = (clone $base)->where('nucleic_acids.nucleic_content_type', ParasiteSamples::class)->count('nucleic_acids.id');
        $culture = (clone $base)->where('nucleic_acids.nucleic_content_type', Cultures::class)->count('nucleic_acids.id');
        $pool = (clone $base)->where('nucleic_acids.nucleic_content_type', Pools::class)->count('nucleic_acids.id');

        $samplesThisYear = (clone $base)->whereBetween('nucleic_acids.date_extracted', [$startOfYear, $endOfYear])->count('nucleic_acids.id');
        $samplesThisMonth = (clone $base)->whereBetween('nucleic_acids.date_extracted', [$startOfMonth, $endOfMonth])->count('nucleic_acids.id');

        $nucleicAcidsBySource = [
            'Human' => $human,
            'Animal' => $animal,
            'Environment' => $environment,
            'Parasite' => $parasite,
            'Culture' => $culture,
            'Pool' => $pool,
        ];

        $nucleicAcidsByType = (clone $base)
            ->select('nucleic_acids.type as k', DB::raw('COUNT(nucleic_acids.id) as c'))
            ->whereNotNull('nucleic_acids.type')
            ->groupBy('nucleic_acids.type')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $nucleicAcidsByProtocol = (clone $base)
            ->select('protocols.name as k', DB::raw('COUNT(nucleic_acids.id) as c'))
            ->whereNotNull('protocols.name')
            ->groupBy('protocols.name')
            ->orderByDesc('c')
            ->limit(20)
            ->pluck('c', 'k')
            ->toArray();

        $nucleicAcidsByLaboratory = (clone $base)
            ->select('laboratories.name as k', DB::raw('COUNT(nucleic_acids.id) as c'))
            ->whereNotNull('laboratories.name')
            ->groupBy('laboratories.name')
            ->orderByDesc('c')
            ->limit(20)
            ->pluck('c', 'k')
            ->toArray();

        $nucleicAcidsByExtractedBy = (clone $base)
            ->selectRaw($this->peopleNameSql().' as k')
            ->selectRaw('COUNT(nucleic_acids.id) as c')
            ->whereNotNull('people.id')
            ->groupBy('k')
            ->orderByDesc('c')
            ->limit(20)
            ->pluck('c', 'k')
            ->toArray();

        $pieChartTabs = [
            [
                'key' => 'source',
                'label' => 'Source',
                'data' => $nucleicAcidsBySource,
            ],
            [
                'key' => 'type',
                'label' => 'Nucleic type',
                'data' => $nucleicAcidsByType,
            ],
        ];

        $barChartTabs = [
            [
                'key' => 'protocol',
                'label' => 'Top Protocols',
                'data' => $nucleicAcidsByProtocol,
            ],
            [
                'key' => 'laboratory',
                'label' => 'Top Laboratories',
                'data' => $nucleicAcidsByLaboratory,
            ],
            [
                'key' => 'extracted_by',
                'label' => 'Extracted by',
                'data' => $nucleicAcidsByExtractedBy,
            ],
        ];

        $mapColorVariableOptions = [
            ['key' => 'source', 'label' => 'Source type'],
            ['key' => 'type', 'label' => 'Nucleic type'],
            ['key' => 'protocol', 'label' => 'Protocol'],
            ['key' => 'extracted_by', 'label' => 'Extracted by'],
        ];

        if ($this->sourceTypeFilter === 'human') {
            $humanEthnicity = (clone $base)
                ->join('human_samples as content_human_samples', 'content_human_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('humans as content_humans', 'content_humans.id', '=', 'content_human_samples.humans_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('content_humans.ethnicity')
                ->select('content_humans.ethnicity as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $humanOccupation = (clone $base)
                ->join('human_samples as content_human_samples', 'content_human_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('humans as content_humans', 'content_humans.id', '=', 'content_human_samples.humans_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('content_humans.occupation')
                ->select('content_humans.occupation as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $humanCountry = (clone $base)
                ->join('human_samples as content_human_samples', 'content_human_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('humans as content_humans', 'content_humans.id', '=', 'content_human_samples.humans_id')
                ->leftJoin('countries as content_countries', 'content_countries.id', '=', 'content_humans.countries_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class))
                ->whereNotNull('content_countries.name')
                ->select('content_countries.name as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $pieChartTabs[] = ['key' => 'human_ethnicity', 'label' => 'Human Ethnicity', 'data' => $humanEthnicity];
            $pieChartTabs[] = ['key' => 'human_occupation', 'label' => 'Human Occupation', 'data' => $humanOccupation];
            $barChartTabs[] = ['key' => 'human_country', 'label' => 'Human Country', 'data' => $humanCountry];
            $mapColorVariableOptions[] = ['key' => 'human_ethnicity', 'label' => 'Human ethnicity'];
            $mapColorVariableOptions[] = ['key' => 'human_occupation', 'label' => 'Human occupation'];
            $mapColorVariableOptions[] = ['key' => 'human_country', 'label' => 'Human country'];
        }

        if ($this->sourceTypeFilter === 'animal') {
            $animalSpecies = (clone $base)
                ->join('animal_samples as content_animal_samples', 'content_animal_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('animals as content_animals', 'content_animals.id', '=', 'content_animal_samples.animals_id')
                ->leftJoin('animal_species as content_animal_species', 'content_animal_species.id', '=', 'content_animals.animal_species_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('content_animal_species.name_common')
                ->select('content_animal_species.name_common as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $animalSex = (clone $base)
                ->join('animal_samples as content_animal_samples', 'content_animal_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('animals as content_animals', 'content_animals.id', '=', 'content_animal_samples.animals_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('content_animals.sex')
                ->select('content_animals.sex as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $animalAge = (clone $base)
                ->join('animal_samples as content_animal_samples', 'content_animal_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('animals as content_animals', 'content_animals.id', '=', 'content_animal_samples.animals_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                ->whereNotNull('content_animals.age')
                ->select('content_animals.age as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $barChartTabs[] = ['key' => 'animal_species', 'label' => 'Animal Species', 'data' => $animalSpecies];
            $pieChartTabs[] = ['key' => 'animal_sex', 'label' => 'Animal Sex', 'data' => $animalSex];
            $pieChartTabs[] = ['key' => 'animal_age', 'label' => 'Animal Age', 'data' => $animalAge];
            $mapColorVariableOptions[] = ['key' => 'animal_species', 'label' => 'Animal species'];
            $mapColorVariableOptions[] = ['key' => 'animal_sex', 'label' => 'Animal sex'];
            $mapColorVariableOptions[] = ['key' => 'animal_age', 'label' => 'Animal age'];
        }

        if ($this->sourceTypeFilter === 'parasite') {
            $parasiteSpecies = (clone $base)
                ->join('parasite_samples as content_parasite_samples', 'content_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as content_parasites', 'content_parasites.id', '=', 'content_parasite_samples.parasites_id')
                ->leftJoin('parasite_species as content_parasite_species', 'content_parasite_species.id', '=', 'content_parasites.parasite_species_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereNotNull('content_parasite_species.name_scientific')
                ->select('content_parasite_species.name_scientific as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $parasiteStage = (clone $base)
                ->join('parasite_samples as content_parasite_samples', 'content_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as content_parasites', 'content_parasites.id', '=', 'content_parasite_samples.parasites_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereNotNull('content_parasites.stage')
                ->select('content_parasites.stage as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $parasiteSex = (clone $base)
                ->join('parasite_samples as content_parasite_samples', 'content_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                ->join('parasites as content_parasites', 'content_parasites.id', '=', 'content_parasite_samples.parasites_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                ->whereNotNull('content_parasites.sex')
                ->select('content_parasites.sex as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $barChartTabs[] = ['key' => 'parasite_species', 'label' => 'Parasite Species', 'data' => $parasiteSpecies];
            $pieChartTabs[] = ['key' => 'parasite_stage', 'label' => 'Parasite Stage', 'data' => $parasiteStage];
            $pieChartTabs[] = ['key' => 'parasite_sex', 'label' => 'Parasite Sex', 'data' => $parasiteSex];
            $mapColorVariableOptions[] = ['key' => 'parasite_species', 'label' => 'Parasite species'];
            $mapColorVariableOptions[] = ['key' => 'parasite_stage', 'label' => 'Parasite stage'];
            $mapColorVariableOptions[] = ['key' => 'parasite_sex', 'label' => 'Parasite sex'];
        }

        if ($this->sourceTypeFilter === 'culture' || $this->tracePrimaryTypeFilter === 'culture') {
            $cultureType = (clone $base)
                ->join('cultures as content_cultures', 'content_cultures.id', '=', 'nucleic_acids.nucleic_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->whereNotNull('content_cultures.type')
                ->select('content_cultures.type as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $cultureMedium = (clone $base)
                ->join('cultures as content_cultures', 'content_cultures.id', '=', 'nucleic_acids.nucleic_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                ->whereNotNull('content_cultures.medium')
                ->select('content_cultures.medium as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $pieChartTabs[] = ['key' => 'culture_type', 'label' => 'Culture Type', 'data' => $cultureType];
            $barChartTabs[] = ['key' => 'culture_medium', 'label' => 'Culture Medium', 'data' => $cultureMedium];
            $mapColorVariableOptions[] = ['key' => 'culture_type', 'label' => 'Culture type'];
            $mapColorVariableOptions[] = ['key' => 'culture_medium', 'label' => 'Culture medium'];
        }

        if ($this->sourceTypeFilter === 'pool' || $this->tracePrimaryTypeFilter === 'pool') {
            $poolNrPooled = (clone $base)
                ->join('pools as content_pools', 'content_pools.id', '=', 'nucleic_acids.nucleic_content_id')
                ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                ->whereNotNull('content_pools.nr_pooled')
                ->select('content_pools.nr_pooled as k', DB::raw('COUNT(nucleic_acids.id) as c'))
                ->groupBy('k')
                ->orderByDesc('c')
                ->pluck('c', 'k')
                ->toArray();

            $barChartTabs[] = ['key' => 'pool_nr_pooled', 'label' => 'Pool size', 'data' => $poolNrPooled];
            $mapColorVariableOptions[] = ['key' => 'pool_nr_pooled', 'label' => 'Pool size'];
        }

        $hasPieTab = fn (string $key): bool => collect($pieChartTabs)->contains(fn ($tab) => ($tab['key'] ?? '') === $key);
        $hasBarTab = fn (string $key): bool => collect($barChartTabs)->contains(fn ($tab) => ($tab['key'] ?? '') === $key);
        $hasMapOption = fn (string $key): bool => collect($mapColorVariableOptions)->contains(fn ($tab) => ($tab['key'] ?? '') === $key);

        if ($this->tracePrimaryTypeFilter === 'parasite') {
            $traceParasiteSpecies = [];
            $traceParasiteStage = [];
            $traceParasiteSex = [];

            if ($this->sourceTypeFilter === 'pool') {
                $baseIds = (clone $base)
                    ->select('nucleic_acids.id')
                    ->distinct();

                $traceBase = NucleicAcids::query()
                    ->whereIn('nucleic_acids.id', $baseIds)
                    ->join('pool_contents as trace_pool_contents', 'trace_pool_contents.pools_id', '=', 'nucleic_acids.nucleic_content_id')
                    ->join('parasite_samples as trace_parasite_samples', 'trace_parasite_samples.id', '=', 'trace_pool_contents.samples_id')
                    ->join('parasites as trace_parasites', 'trace_parasites.id', '=', 'trace_parasite_samples.parasites_id')
                    ->leftJoin('parasite_species as trace_parasite_species', 'trace_parasite_species.id', '=', 'trace_parasites.parasite_species_id')
                    ->where(function ($typeQ) {
                        $typeQ->whereIn('trace_pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                            ->orWhereRaw('LOWER(trace_pool_contents.samples_type) LIKE ?', ['%parasite%']);
                    });

                $traceParasiteSpecies = (clone $traceBase)
                    ->whereNotNull('trace_parasite_species.name_scientific')
                    ->select('trace_parasite_species.name_scientific as k', DB::raw('COUNT(DISTINCT nucleic_acids.id) as c'))
                    ->groupBy('k')
                    ->orderByDesc('c')
                    ->pluck('c', 'k')
                    ->toArray();

                $traceParasiteStage = (clone $traceBase)
                    ->whereNotNull('trace_parasites.stage')
                    ->select('trace_parasites.stage as k', DB::raw('COUNT(DISTINCT nucleic_acids.id) as c'))
                    ->groupBy('k')
                    ->orderByDesc('c')
                    ->pluck('c', 'k')
                    ->toArray();

                $traceParasiteSex = (clone $traceBase)
                    ->whereNotNull('trace_parasites.sex')
                    ->select('trace_parasites.sex as k', DB::raw('COUNT(DISTINCT nucleic_acids.id) as c'))
                    ->groupBy('k')
                    ->orderByDesc('c')
                    ->pluck('c', 'k')
                    ->toArray();
            }

            if (! $hasBarTab('parasite_species')) {
                $barChartTabs[] = ['key' => 'parasite_species', 'label' => 'Parasite Species', 'data' => $traceParasiteSpecies];
            }
            if (! $hasPieTab('parasite_stage')) {
                $pieChartTabs[] = ['key' => 'parasite_stage', 'label' => 'Parasite Stage', 'data' => $traceParasiteStage];
            }
            if (! $hasPieTab('parasite_sex')) {
                $pieChartTabs[] = ['key' => 'parasite_sex', 'label' => 'Parasite Sex', 'data' => $traceParasiteSex];
            }
            if (! $hasMapOption('parasite_species')) {
                $mapColorVariableOptions[] = ['key' => 'parasite_species', 'label' => 'Parasite species'];
            }
            if (! $hasMapOption('parasite_stage')) {
                $mapColorVariableOptions[] = ['key' => 'parasite_stage', 'label' => 'Parasite stage'];
            }
            if (! $hasMapOption('parasite_sex')) {
                $mapColorVariableOptions[] = ['key' => 'parasite_sex', 'label' => 'Parasite sex'];
            }
        }

        if ($this->tracePrimaryTypeFilter === 'culture') {
            if (! $hasPieTab('culture_type')) {
                $pieChartTabs[] = ['key' => 'culture_type', 'label' => 'Culture Type', 'data' => []];
            }
            if (! $hasBarTab('culture_medium')) {
                $barChartTabs[] = ['key' => 'culture_medium', 'label' => 'Culture Medium', 'data' => []];
            }
            if (! $hasMapOption('culture_type')) {
                $mapColorVariableOptions[] = ['key' => 'culture_type', 'label' => 'Culture type'];
            }
            if (! $hasMapOption('culture_medium')) {
                $mapColorVariableOptions[] = ['key' => 'culture_medium', 'label' => 'Culture medium'];
            }
        }

        if ($this->tracePrimaryTypeFilter === 'pool') {
            if (! $hasBarTab('pool_nr_pooled')) {
                $barChartTabs[] = ['key' => 'pool_nr_pooled', 'label' => 'Pool size', 'data' => []];
            }
            if (! $hasMapOption('pool_nr_pooled')) {
                $mapColorVariableOptions[] = ['key' => 'pool_nr_pooled', 'label' => 'Pool size'];
            }
        }

        $descriptive_stats = [
            'total_samples' => $total,
            'human_samples' => $human,
            'animal_samples' => $animal,
            'environment_samples' => $environment,
            'parasite_samples' => $parasite,
            'culture_samples' => $culture,
            'pool_samples' => $pool,
            'samples_this_year' => $samplesThisYear,
            'samples_this_month' => $samplesThisMonth,
            'extraction_timeline' => $this->timelineCounts(clone $base),
        ];

        return [
            'descriptive_stats' => $descriptive_stats,
            'nucleicAcidsBySource' => $nucleicAcidsBySource,
            'nucleicAcidsByType' => $nucleicAcidsByType,
            'nucleicAcidsByProtocol' => $nucleicAcidsByProtocol,
            'nucleicAcidsByLaboratory' => $nucleicAcidsByLaboratory,
            'nucleicAcidsByExtractedBy' => $nucleicAcidsByExtractedBy,
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
            'mapPointsUrl' => route('nucleic.dashboard.map-points'),
            'modalTableUrls' => [
                'nucleicAcidsModal' => route('nucleic.dashboard.modal.all'),
                'humanSamplesModal' => route('nucleic.dashboard.modal.human'),
                'animalSamplesModal' => route('nucleic.dashboard.modal.animal'),
                'environmentSamplesModal' => route('nucleic.dashboard.modal.environment'),
                'parasiteSamplesModal' => route('nucleic.dashboard.modal.parasite'),
                'cultureSamplesModal' => route('nucleic.dashboard.modal.culture'),
                'poolSamplesModal' => route('nucleic.dashboard.modal.pool'),
            ],
            'activeFilters' => [
                'nucleicTypeFilter' => $this->nucleicTypeFilter,
                'sourceTypeFilter' => $this->sourceTypeFilter,
                'subProjectFilter' => $this->subProjectFilter,
                'animalSpeciesFilter' => $this->animalSpeciesFilter,
                'parasiteSpeciesFilter' => $this->parasiteSpeciesFilter,
                'parasiteOriginTypeFilter' => $this->parasiteOriginTypeFilter,
                'parasiteOriginAnimalSpeciesFilter' => $this->parasiteOriginAnimalSpeciesFilter,
                'tracePrimaryTypeFilter' => $this->tracePrimaryTypeFilter,
                'tracePrimaryAnimalSpeciesFilter' => $this->tracePrimaryAnimalSpeciesFilter,
                'tracePrimaryAnimalSexFilter' => $this->tracePrimaryAnimalSexFilter,
                'tracePrimaryAnimalAgeFilter' => $this->tracePrimaryAnimalAgeFilter,
                'tracePrimaryHumanEthnicityFilter' => $this->tracePrimaryHumanEthnicityFilter,
                'tracePrimaryHumanOccupationFilter' => $this->tracePrimaryHumanOccupationFilter,
                'tracePrimaryHumanCountryFilter' => $this->tracePrimaryHumanCountryFilter,
                'tracePrimaryParasiteSpeciesFilter' => $this->tracePrimaryParasiteSpeciesFilter,
                'tracePrimaryCultureTypeFilter' => $this->tracePrimaryCultureTypeFilter,
                'tracePrimaryCultureMediumFilter' => $this->tracePrimaryCultureMediumFilter,
                'tracePrimaryNucleicTypeFilter' => $this->tracePrimaryNucleicTypeFilter,
                'tracePrimaryPoolMinNrPooled' => $this->tracePrimaryPoolMinNrPooled,
                'tracePrimaryPoolMaxNrPooled' => $this->tracePrimaryPoolMaxNrPooled,
                'traceDeepPrimaryTypeFilter' => $this->traceDeepPrimaryTypeFilter,
                'traceDeepAnimalSpeciesFilter' => $this->traceDeepAnimalSpeciesFilter,
                'traceDeepAnimalSexFilter' => $this->traceDeepAnimalSexFilter,
                'traceDeepAnimalAgeFilter' => $this->traceDeepAnimalAgeFilter,
                'traceDeepHumanEthnicityFilter' => $this->traceDeepHumanEthnicityFilter,
                'traceDeepHumanOccupationFilter' => $this->traceDeepHumanOccupationFilter,
                'traceDeepHumanCountryFilter' => $this->traceDeepHumanCountryFilter,
                'protocolFilter' => $this->protocolFilter,
                'laboratoryFilter' => $this->laboratoryFilter,
                'extractedByFilter' => $this->extractedByFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ],
        ];
    }

    private function timelineCounts($base): array
    {
        $driver = DB::getDriverName();
        $expr = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT(nucleic_acids.date_extracted, '%Y')",
                'pgsql' => "to_char(nucleic_acids.date_extracted, 'YYYY')",
                default => "strftime('%Y', nucleic_acids.date_extracted)",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT(nucleic_acids.date_extracted, '%Y-%m')",
            'pgsql' => "to_char(nucleic_acids.date_extracted, 'YYYY-MM')",
            default => "strftime('%Y-%m', nucleic_acids.date_extracted)",
        };

        $rows = $base
            ->select(DB::raw($expr.' as ym'), DB::raw('COUNT(nucleic_acids.id) as c'))
            ->whereNotNull('nucleic_acids.date_extracted')
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->toArray();

        $timeline = [];
        if ($this->timelineGranularity === 'yearly') {
            for ($i = 9; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->format('Y');
                $timeline[$year] = (int) ($rows[$year] ?? 0);
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $key = $month->format('Y-m');
                $label = $month->format('M Y');
                $timeline[$label] = (int) ($rows[$key] ?? 0);
            }
        }

        return $timeline;
    }

    private function allNucleicTypes()
    {
        $base = NucleicAcids::query()->select('nucleic_acids.type')->whereNotNull('nucleic_acids.type');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('nucleic_acids.type')->pluck('nucleic_acids.type')->values();
    }

    private function allSubProjects()
    {
        $base = $this->baseQueryForOptions(['subProjectFilter']);

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', NucleicAcids::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('nucleic_acids.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    private function allProtocols()
    {
        $base = NucleicAcids::query()->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('protocols.name')->pluck('protocols.name')->filter()->values();
    }

    private function allLaboratories()
    {
        $base = NucleicAcids::query()->leftJoin('laboratories', 'nucleic_acids.laboratories_id', '=', 'laboratories.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('laboratories.name')->pluck('laboratories.name')->filter()->values();
    }

    private function allPeople()
    {
        $base = NucleicAcids::query()->leftJoin('people', 'nucleic_acids.people_id', '=', 'people.id');
        $this->applyVisibilityScope($base);

        return $base
            ->select($this->peopleNameExpression('name'))
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values();
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
            $q->where('animal_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('animal_species.name_common')->pluck('animal_species.name_common')->filter()->values();
    }

    private function allParasiteSpecies()
    {
        $q = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->whereNotNull('parasite_species.name_scientific')
            ->select('parasite_species.name_scientific');

        if ($this->isGuestMode()) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $q->where('parasite_samples.projects_id', $this->projectId);
        }

        return $q->distinct()->orderBy('parasite_species.name_scientific')->pluck('parasite_species.name_scientific')->filter()->values();
    }

    private function peopleNameExpression(string $alias): Expression
    {
        return DB::raw($this->peopleNameSql()." as {$alias}");
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

    private function applyVisibilityScope($query): void
    {
        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(NucleicAcids::class))
                    ->where('tubes.is_private', false);
            });

            return;
        } else {
            $query->where(function ($w) {
                $w->where('nucleic_acids.projects_id', $this->projectId)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(NucleicAcids::class))
                            ->where('tubes.projects_id', $this->projectId);
                    });
            });
        }
    }
}
