<?php

namespace App\Livewire;

use App\Models\MetaAnimal;
use App\Models\MetaEnvironment;
use App\Models\MetaHuman;
use App\Models\MetaParasite;
use App\Models\SubProject;
use App\Services\MetaService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

class MetaDashboard extends PlainComponent
{
    use WithPagination;

    protected $projectId;

    public string $metaTypeFilter = 'MetaAnimal';

    public ?int $startYear = null;

    public ?int $endYear = null;

    public $pathogenFilter;

    public $techniqueFilter;

    public $countryFilter;

    public $riskFactorFilter;

    public $clinicalSignFilter;

    public $lesionFilter;

    public string $subProjectFilter = '';

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

    public function updated($propertyName)
    {
        $data = $this->filteredData();
        Log::info('MetaDashboard updated', [
            'property' => $propertyName,
            'has_country_stats' => isset($data['country_stats']),
            'country_stats_count' => isset($data['country_stats']) ? count($data['country_stats']) : 0,
        ]);
        $this->dispatch('filtersUpdated', data: $data);
    }

    public function resetFilters()
    {
        $this->metaTypeFilter = 'MetaAnimal';
        $this->startYear = null;
        $this->endYear = null;
        $this->pathogenFilter = null;
        $this->techniqueFilter = null;
        $this->countryFilter = null;
        $this->riskFactorFilter = null;
        $this->clinicalSignFilter = null;
        $this->lesionFilter = null;
        $this->subProjectFilter = '';
        $this->resetPage();

        $this->dispatch('filtersUpdated', data: $this->filteredData());
    }

    /**
     * Build the base query with all necessary relationships and apply filters
     */
    private function buildFilteredQuery()
    {
        $query = $this->baseMetaQuery();
        $this->applyFilters($query);

        return $query->get();
    }

    private function baseMetaQuery()
    {
        $query = match ($this->metaTypeFilter) {
            'MetaHuman' => MetaHuman::query()->with([
                'studies',
                'sample_types',
                'pathogens',
                'techniques',
                'risk_factors',
                'countries',
                'clinical_signs',
                'lesions',
                'projects',
                'people',
            ]),
            'MetaParasite' => MetaParasite::query()->with([
                'studies',
                'parasite_species',
                'parasite_sample_types',
                'pathogens',
                'techniques',
                'risk_factors',
                'countries',
                'projects',
                'people',
            ]),
            'MetaEnvironment' => MetaEnvironment::query()->with([
                'studies',
                'environment_sample_types',
                'pathogens',
                'techniques',
                'risk_factors',
                'countries',
                'projects',
                'people',
            ]),
            default => MetaAnimal::query()->with([
                'studies',
                'animal_species',
                'sample_types',
                'pathogens',
                'techniques',
                'risk_factors',
                'countries',
                'clinical_signs',
                'lesions',
                'projects',
                'people',
            ]),
        };

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->whereHas('projects', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where('projects_id', $this->projectId);
        }

        return $query;
    }

    /**
     * Apply all active filters to the query
     */
    private function applyFilters($query)
    {
        // Apply publication year filter (Studies-level)
        if ($this->startYear || $this->endYear) {
            $startYear = $this->startYear ?? 0;
            $endYear = $this->endYear ?? (int) Carbon::now()->year;

            $query->whereHas('studies', function ($q) use ($startYear, $endYear) {
                $q->whereBetween('publication_year', [$startYear, $endYear]);
            });
        }

        // Apply pathogen filter
        if ($this->pathogenFilter) {
            $query->whereHas('pathogens', function ($q) {
                $q->where('species', $this->pathogenFilter);
            });
        }

        // Apply technique filter
        if ($this->techniqueFilter) {
            $query->whereHas('techniques', function ($q) {
                $q->where('type', $this->techniqueFilter);
            });
        }

        // Apply country filter
        if ($this->countryFilter) {
            $query->whereHas('countries', function ($q) {
                $q->where('name', $this->countryFilter);
            });
        }

        // Apply risk factor filter
        if ($this->riskFactorFilter) {
            $query->whereHas('risk_factors', function ($q) {
                $q->where('name', $this->riskFactorFilter);
            });
        }
        if ($this->supportsClinicalAndLesionFilters() && $this->clinicalSignFilter) {
            $query->whereHas('clinical_signs', function ($q) {
                $q->where('name', $this->clinicalSignFilter);
            });
        }
        if ($this->supportsClinicalAndLesionFilters() && $this->lesionFilter) {
            $query->whereHas('lesions', function ($q) {
                $q->where('name', $this->lesionFilter);
            });
        }
        if ($this->subProjectFilter !== '') {
            $query->whereHas('subProjectAssignment.subProject', function ($q) {
                $q->where('code', $this->subProjectFilter);
            });
        }

        return $query;
    }

    /**
     * Apply all active filters to the query except meta type filter (to avoid recursion)
     */
    private function applyFiltersWithoutMetaType($query)
    {
        // Apply publication year filter (Studies-level)
        if ($this->startYear || $this->endYear) {
            $startYear = $this->startYear ?? 0;
            $endYear = $this->endYear ?? (int) Carbon::now()->year;

            $query->whereHas('studies', function ($q) use ($startYear, $endYear) {
                $q->whereBetween('publication_year', [$startYear, $endYear]);
            });
        }

        // Apply pathogen filter
        if ($this->pathogenFilter) {
            $query->whereHas('pathogens', function ($q) {
                $q->where('species', $this->pathogenFilter);
            });
        }

        // Apply technique filter
        if ($this->techniqueFilter) {
            $query->whereHas('techniques', function ($q) {
                $q->where('type', $this->techniqueFilter);
            });
        }

        // Apply country filter
        if ($this->countryFilter) {
            $query->whereHas('countries', function ($q) {
                $q->where('name', $this->countryFilter);
            });
        }

        // Apply risk factor filter
        if ($this->riskFactorFilter) {
            $query->whereHas('risk_factors', function ($q) {
                $q->where('name', $this->riskFactorFilter);
            });
        }
        if ($this->supportsClinicalAndLesionFilters() && $this->clinicalSignFilter) {
            $query->whereHas('clinical_signs', function ($q) {
                $q->where('name', $this->clinicalSignFilter);
            });
        }
        if ($this->supportsClinicalAndLesionFilters() && $this->lesionFilter) {
            $query->whereHas('lesions', function ($q) {
                $q->where('name', $this->lesionFilter);
            });
        }

        return $query;
    }

    /**
     * Calculate descriptive statistics from filtered meta data
     */
    private function calculateStatistics($metaData)
    {
        $uniqueStudies = $metaData->pluck('studies_id')->filter()->unique();
        $currentYear = (int) Carbon::now()->year;

        $stats = [
            'total_studies' => $uniqueStudies->count(),
            'total_tested' => $metaData->sum('tested_n'),
            'total_positive' => $metaData->sum('pos_n'),
            'studies_this_year' => $metaData
                ->filter(function ($row) use ($currentYear) {
                    return $row->studies && (int) $row->studies->publication_year === $currentYear;
                })
                ->pluck('studies_id')
                ->filter()
                ->unique()
                ->count(),
            // Publication year is year-only; month-level stat isn't meaningful here.
            'studies_this_month' => 0,
        ];

        // Calculate overall positivity rate
        $stats['positivity_rate'] = $stats['total_tested'] > 0
            ? round(($stats['total_positive'] / $stats['total_tested']) * 100, 1)
            : 0;

        // Generate timeline data
        $stats['studies_timeline'] = $this->generateTimelineData($metaData);

        return $stats;
    }

    /**
     * Generate timeline data for all years
     */
    private function generateTimelineData($metaData)
    {
        return $metaData
            ->filter(function ($row) {
                return $row->studies && $row->studies->publication_year;
            })
            ->groupBy('studies.publication_year')
            ->map(function ($group) {
                return $group->pluck('studies_id')->filter()->unique()->count();
            })
            ->sortKeys()
            ->toArray();
    }

    /**
     * Parse a sampling date string that may be:
     * - a normal date (YYYY-MM-DD, etc.)
     * - a month/year (e.g. "May 2009")
     * - a range (e.g. "May 2009- June 2011" or "May 2009 - June 2011")
     *
     * Returns [start, end] as Carbon instances (inclusive range), or [null, null] if unparseable.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function parseSamplingDateRange(?string $value): array
    {
        if (! $value) {
            return [null, null];
        }

        $value = trim($value);
        if ($value === '') {
            return [null, null];
        }

        $normalized = str_replace(['–', '—'], '-', $value);

        // Treat as range only when the dash is used as a separator (has whitespace on at least one side).
        if (preg_match('/\s-\s|\s-\S|\S-\s/', $normalized)) {
            $parts = preg_split('/\s*-\s*/', $normalized, 2);
            $start = $this->parseSamplingDatePart($parts[0] ?? '', false);
            $end = $this->parseSamplingDatePart($parts[1] ?? '', true);

            if ($start && ! $end) {
                $end = $start->copy()->endOfDay();
            }

            if ($start && $end && $start->greaterThan($end)) {
                // Swap if reversed.
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [$start, $end];
        }

        $start = $this->parseSamplingDatePart($normalized, false);
        $end = $this->parseSamplingDatePart($normalized, true);

        return [$start, $end];
    }

    private function parseSamplingDatePart(string $part, bool $isEnd): ?Carbon
    {
        $part = trim($part);
        if ($part === '') {
            return null;
        }

        try {
            $dt = Carbon::parse($part);

            return $isEnd ? $dt->copy()->endOfDay() : $dt->copy()->startOfDay();
        } catch (\Throwable) {
            // Continue with fallback parsing.
        }

        // Month year (e.g. "May 2009" / "Jun 2011")
        if (preg_match('/^[A-Za-z]{3,9}\s+\d{4}$/', $part) === 1) {
            try {
                $dt = Carbon::createFromFormat('F Y', $part);
            } catch (\Throwable) {
                try {
                    $dt = Carbon::createFromFormat('M Y', $part);
                } catch (\Throwable) {
                    return null;
                }
            }

            return $isEnd ? $dt->copy()->endOfMonth() : $dt->copy()->startOfMonth();
        }

        // Year only (e.g. "2011")
        if (preg_match('/^\d{4}$/', $part) === 1) {
            try {
                $dt = Carbon::createFromFormat('Y', $part);
            } catch (\Throwable) {
                return null;
            }

            return $isEnd ? $dt->copy()->endOfYear() : $dt->copy()->startOfYear();
        }

        return null;
    }

    private function dateRangeOverlaps(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool
    {
        return $aStart->lessThanOrEqualTo($bEnd) && $aEnd->greaterThanOrEqualTo($bStart);
    }

    public function samplingDateDisplay(?string $value): string
    {
        if (! $value) {
            return 'N/A';
        }

        $value = trim($value);
        if ($value === '') {
            return 'N/A';
        }

        [$start, $end] = $this->parseSamplingDateRange($value);

        // If we can't parse it reliably, just show the raw string (avoids crashing on ranges).
        if (! $start || ! $end) {
            return $value;
        }

        if ($start->isSameDay($end)) {
            return $start->format('Y-m-d');
        }

        // For ranges, show a compact range using year-month (most of your ranges are month/year).
        return $start->format('Y-m').' - '.$end->format('Y-m');
    }

    /**
     * Get all available pathogens for filter dropdown
     */
    private function getAllPathogens()
    {
        // Get pathogens from the current filtered data
        $filteredData = $this->buildFilteredQuery();

        return $filteredData
            ->whereNotNull('pathogens.species')
            ->pluck('pathogens.species')
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Get all available techniques for filter dropdown
     */
    private function getAllTechniques()
    {
        // Get techniques from the current filtered data
        $filteredData = $this->buildFilteredQuery();

        return $filteredData
            ->whereNotNull('techniques.type')
            ->pluck('techniques.type')
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Get all available countries for filter dropdown
     */
    private function getAllCountries()
    {
        // Get countries from the current filtered data
        $filteredData = $this->buildFilteredQuery();

        return $filteredData
            ->whereNotNull('countries.name')
            ->pluck('countries.name')
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Get all available risk factors for filter dropdown
     */
    private function getAllRiskFactors()
    {
        // Get risk factors from the current filtered data
        $filteredData = $this->buildFilteredQuery();

        return $filteredData
            ->flatMap(function ($row) {
                return $row->risk_factors?->pluck('name') ?? collect();
            })
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    private function getAllClinicalSigns()
    {
        if (! $this->supportsClinicalAndLesionFilters()) {
            return collect();
        }

        $filteredData = $this->buildFilteredQuery();

        return $filteredData
            ->flatMap(function ($row) {
                return $row->clinical_signs?->pluck('name') ?? collect();
            })
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    private function getAllLesions()
    {
        if (! $this->supportsClinicalAndLesionFilters()) {
            return collect();
        }

        $filteredData = $this->buildFilteredQuery();

        return $filteredData
            ->flatMap(function ($row) {
                return $row->lesions?->pluck('name') ?? collect();
            })
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Get filter options without applying current filters (to avoid recursion)
     */
    private function getFilterOptions()
    {
        $base = $this->baseMetaQuery()->with(['pathogens', 'techniques', 'countries', 'risk_factors', 'clinical_signs', 'lesions', 'studies']);

        if ($this->isGuestMode()) {
            $base->whereHas('projects', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $base->where('projects_id', $this->projectId);
        }

        $pathogensResults = (clone $base);
        $this->applyFiltersExcept($pathogensResults, ['pathogen']);
        $pathogens = $pathogensResults->get()->whereNotNull('pathogens.species')->pluck('pathogens.species')->unique()->filter()->sort()->values();

        $techniquesResults = (clone $base);
        $this->applyFiltersExcept($techniquesResults, ['technique']);
        $techniques = $techniquesResults->get()->whereNotNull('techniques.type')->pluck('techniques.type')->unique()->filter()->sort()->values();

        $countriesResults = (clone $base);
        $this->applyFiltersExcept($countriesResults, ['country']);
        $countries = $countriesResults->get()->whereNotNull('countries.name')->pluck('countries.name')->unique()->filter()->sort()->values();

        $riskFactorsResults = (clone $base);
        $this->applyFiltersExcept($riskFactorsResults, ['risk_factor']);
        $riskFactors = $riskFactorsResults->get()
            ->flatMap(function ($row) {
                return $row->risk_factors?->pluck('name') ?? collect();
            })
            ->unique()
            ->filter()
            ->sort()
            ->values();

        $clinicalSigns = collect();
        $lesions = collect();
        if ($this->supportsClinicalAndLesionFilters()) {
            $clinicalSignsResults = (clone $base);
            $this->applyFiltersExcept($clinicalSignsResults, ['clinical_sign']);
            $clinicalSigns = $clinicalSignsResults->get()
                ->flatMap(function ($row) {
                    return $row->clinical_signs?->pluck('name') ?? collect();
                })
                ->unique()
                ->filter()
                ->sort()
                ->values();

            $lesionsResults = (clone $base);
            $this->applyFiltersExcept($lesionsResults, ['lesion']);
            $lesions = $lesionsResults->get()
                ->flatMap(function ($row) {
                    return $row->lesions?->pluck('name') ?? collect();
                })
                ->unique()
                ->filter()
                ->sort()
                ->values();
        }

        return [
            'pathogens' => $pathogens,
            'techniques' => $techniques,
            'countries' => $countries,
            'risk_factors' => $riskFactors,
            'clinical_signs' => $clinicalSigns,
            'lesions' => $lesions,
        ];
    }

    private function applyFiltersExcept($query, array $except = [])
    {
        // Publication year (Studies-level)
        if (! in_array('publication_year', $except, true) && ($this->startYear || $this->endYear)) {
            $startYear = $this->startYear ?? 0;
            $endYear = $this->endYear ?? (int) Carbon::now()->year;

            $query->whereHas('studies', function ($q) use ($startYear, $endYear) {
                $q->whereBetween('publication_year', [$startYear, $endYear]);
            });
        }

        if (! in_array('pathogen', $except, true) && $this->pathogenFilter) {
            $query->whereHas('pathogens', function ($q) {
                $q->where('species', $this->pathogenFilter);
            });
        }

        if (! in_array('technique', $except, true) && $this->techniqueFilter) {
            $query->whereHas('techniques', function ($q) {
                $q->where('type', $this->techniqueFilter);
            });
        }

        if (! in_array('country', $except, true) && $this->countryFilter) {
            $query->whereHas('countries', function ($q) {
                $q->where('name', $this->countryFilter);
            });
        }

        if (! in_array('risk_factor', $except, true) && $this->riskFactorFilter) {
            $query->whereHas('risk_factors', function ($q) {
                $q->where('name', $this->riskFactorFilter);
            });
        }
        if (
            ! in_array('clinical_sign', $except, true)
            && $this->supportsClinicalAndLesionFilters()
            && $this->clinicalSignFilter
        ) {
            $query->whereHas('clinical_signs', function ($q) {
                $q->where('name', $this->clinicalSignFilter);
            });
        }
        if (
            ! in_array('lesion', $except, true)
            && $this->supportsClinicalAndLesionFilters()
            && $this->lesionFilter
        ) {
            $query->whereHas('lesions', function ($q) {
                $q->where('name', $this->lesionFilter);
            });
        }

        return $query;
    }

    public function filteredData()
    {
        $service = app(MetaService::class);
        $additionalData = $service->assign();

        // Get filtered meta data for statistics
        $filteredMetaData = $this->buildFilteredQuery();
        $statistics = $this->calculateStatistics($filteredMetaData);

        // Get all filtered meta data for modal display (non-paginated)
        $allFilteredMetaData = $filteredMetaData->sortByDesc('created_at');
        $allFilteredStudies = $allFilteredMetaData->unique('studies_id')->values();

        // Create a simple pagination for the combined data
        $perPage = 10;
        $currentPage = request()->get('meta-page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedData = $allFilteredMetaData->slice($offset, $perPage);

        // Create a custom paginator
        $metaData = new LengthAwarePaginator(
            $paginatedData,
            $allFilteredMetaData->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'meta-page']
        );

        // Aggregate by country for positivity rate
        $countryStats = $filteredMetaData
            ->filter(function ($study) {
                return $study->countries && $study->countries->name;
            })
            ->groupBy(function ($study) {
                return $study->countries->name;
            })
            ->map(function ($group, $country) {
                $studies = $group->pluck('studies_id')->filter()->unique()->count();
                $tested = $group->sum('tested_n');
                $positive = $group->sum('pos_n');
                $positivity_rate = $tested > 0 ? round(($positive / $tested) * 100, 1) : 0;

                return [
                    'studies' => $studies,
                    'tested' => $tested,
                    'positive' => $positive,
                    'positivity_rate' => $positivity_rate,
                ];
            })->toArray();

        // Get studies by pathogen
        $studiesByPathogen = $filteredMetaData
            ->whereNotNull('pathogens.species')
            ->groupBy('pathogens.species')
            ->map(function ($group) {
                return $group->pluck('studies_id')->filter()->unique()->count();
            })
            ->sortDesc()
            ->toArray();

        // Get studies by technique
        $studiesByTechnique = $filteredMetaData
            ->whereNotNull('techniques.type')
            ->groupBy('techniques.type')
            ->map(function ($group) {
                return $group->pluck('studies_id')->filter()->unique()->count();
            })
            ->sortDesc()
            ->toArray();

        // Get studies by country
        $studiesByCountry = $filteredMetaData
            ->whereNotNull('countries.name')
            ->groupBy('countries.name')
            ->map(function ($group) {
                return $group->pluck('studies_id')->filter()->unique()->count();
            })
            ->sortDesc();

        $metaAnimalsForTestedModal = $allFilteredMetaData->values();
        $metaAnimalsForPositiveModal = $allFilteredMetaData->filter(function ($row) {
            return (int) $row->pos_n > 0;
        })->values();

        // Positivity at (study, pathogen) level: group by study ref_key AND pathogen.
        $pathogenPrevalenceRows = $allFilteredMetaData
            ->filter(function ($row) {
                return $row->studies && $row->studies->ref_key && $row->pathogens && $row->pathogens->species;
            })
            ->groupBy(function ($row) {
                return $row->studies->ref_key.'|'.$row->pathogens->species;
            })
            ->map(function ($group) {
                $first = $group->first();
                $tested = $group->sum('tested_n');
                $positive = $group->sum('pos_n');
                $prevalence = $tested > 0 ? round(($positive / $tested) * 100, 2) : 0;

                return [
                    'study_id' => $first->studies->id,
                    'ref_key' => $first->studies->ref_key,
                    'pathogen' => $first->pathogens->species,
                    'tested' => $tested,
                    'positive' => $positive,
                    'prevalence' => $prevalence,
                ];
            })
            ->sortByDesc('prevalence')
            ->values()
            ->all();

        $studiesModalRows = $allFilteredMetaData
            ->filter(function ($row) {
                return $row->studies && $row->studies_id;
            })
            ->groupBy('studies_id')
            ->map(function ($group) {
                $study = $group->first()->studies;
                $countries = $group
                    ->whereNotNull('countries.name')
                    ->pluck('countries.name')
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => $study->id,
                    'ref_key' => $study->ref_key,
                    'publication_year' => $study->publication_year,
                    'countries' => $countries,
                    'doi' => $study->doi,
                    'pdf_path' => $study->pdf_path,
                ];
            })
            ->sortByDesc('publication_year')
            ->values()
            ->all();

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        $allPathogens = $filterOptions['pathogens'];
        if ($this->pathogenFilter) {
            $allPathogens = $allPathogens->prepend($this->pathogenFilter)->unique()->values();
        }

        $allTechniques = $filterOptions['techniques'];
        if ($this->techniqueFilter) {
            $allTechniques = $allTechniques->prepend($this->techniqueFilter)->unique()->values();
        }

        $allCountries = $filterOptions['countries'];
        if ($this->countryFilter) {
            $allCountries = $allCountries->prepend($this->countryFilter)->unique()->values();
        }

        $allRiskFactors = $filterOptions['risk_factors'];
        if ($this->riskFactorFilter) {
            $allRiskFactors = $allRiskFactors->prepend($this->riskFactorFilter)->unique()->values();
        }

        $allClinicalSigns = $filterOptions['clinical_signs'] ?? collect();
        if ($this->clinicalSignFilter) {
            $allClinicalSigns = $allClinicalSigns->prepend($this->clinicalSignFilter)->unique()->values();
        }

        $allLesions = $filterOptions['lesions'] ?? collect();
        if ($this->lesionFilter) {
            $allLesions = $allLesions->prepend($this->lesionFilter)->unique()->values();
        }

        $allSubProjects = $this->allSubProjects();
        if ($this->subProjectFilter !== '') {
            $allSubProjects = $allSubProjects->prepend($this->subProjectFilter)->unique()->values();
        }

        $viewData = array_merge($additionalData, [
            'metaData' => $metaData,
            'all_meta_data' => $allFilteredStudies,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $this->canEdit(),
            'descriptive_stats' => $statistics,
            'studiesByPathogen' => $studiesByPathogen,
            'studiesByTechnique' => $studiesByTechnique,
            'studiesByCountry' => $studiesByCountry,
            'allPathogens' => $allPathogens,
            'allTechniques' => $allTechniques,
            'allCountries' => $allCountries,
            'allRiskFactors' => $allRiskFactors,
            'allClinicalSigns' => $allClinicalSigns,
            'allLesions' => $allLesions,
            'allSubProjects' => $allSubProjects,
            'country_stats' => $countryStats,
            'samples' => $countryStats, // Pass country_stats as samples like experiments dashboard
            'studies_modal_rows' => $studiesModalRows,
            'tested_modal_rows' => $metaAnimalsForTestedModal,
            'positive_modal_rows' => $metaAnimalsForPositiveModal,
            'positivity_modal_rows' => $pathogenPrevalenceRows,
        ]);

        return $viewData;
    }

    private function allSubProjects()
    {
        $assignableType = match ($this->metaTypeFilter) {
            'MetaHuman' => MetaHuman::class,
            'MetaParasite' => MetaParasite::class,
            'MetaEnvironment' => MetaEnvironment::class,
            default => MetaAnimal::class,
        };

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', $assignableType)
            ->whereIn('sub_project_assignments.assignable_id', $this->baseMetaQuery()->select('id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    private function supportsClinicalAndLesionFilters(): bool
    {
        return in_array($this->metaTypeFilter, ['MetaAnimal', 'MetaHuman'], true);
    }

    public function render()
    {

        $viewData = $this->filteredData();

        return view('livewire.meta-dashboard', $viewData);
    }
}
