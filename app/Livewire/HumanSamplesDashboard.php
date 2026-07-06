<?php

namespace App\Livewire;

use App\Models\HumanSamples;
use App\Models\SubProject;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HumanSamplesDashboard extends PlainComponent
{
    protected $projectId;

    public string $visualize_by = 'samples';

    public string $sampleVisibility = 'all';

    public string $timelineGranularity = 'monthly';

    public string $sampleTypeFilter = '';

    public string $samplingSiteFilter = '';

    public ?string $ethnicityFilter = null;

    public ?string $occupationFilter = null;

    public ?string $countryFilter = null;

    public string $subProjectFilter = '';

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

    public function resetFilters(): void
    {
        $this->reset([
            'visualize_by',
            'sampleVisibility',
            'timelineGranularity',
            'sampleTypeFilter',
            'samplingSiteFilter',
            'ethnicityFilter',
            'occupationFilter',
            'countryFilter',
            'subProjectFilter',
            'startDate',
            'endDate',
        ]);

        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function render()
    {
        return view('livewire.human-samples-dashboard', array_merge(
            $this->dashboardPayload(),
            [
                'isGuestMode' => $this->isGuestMode(),
                'canEdit' => $this->canEdit(),
                'allSampleTypes' => $this->allSampleTypes(),
                'allSamplingSites' => $this->allSamplingSites(),
                'allEthnicities' => $this->allEthnicities(),
                'allOccupations' => $this->allOccupations(),
                'allCountries' => $this->allCountries(),
                'allSubProjects' => $this->allSubProjects(),
            ]
        ));
    }

    private function baseQuery()
    {
        $query = HumanSamples::query()
            ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id');

        if ($this->isGuestMode()) {
            $query->where('human_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->where('tubes.tubes_content_type', HumanSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('human_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->where('human_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                        ->where('tubes.tubes_content_type', HumanSamples::class);
                });
            }
        }

        if ($this->sampleTypeFilter !== '') {
            $query->where('sample_types.name', $this->sampleTypeFilter);
        }
        if ($this->samplingSiteFilter !== '') {
            $query->where('sampling_sites.name', $this->samplingSiteFilter);
        }
        if ($this->ethnicityFilter) {
            $query->where('humans.ethnicity', $this->ethnicityFilter);
        }
        if ($this->occupationFilter) {
            $query->where('humans.occupation', $this->occupationFilter);
        }
        if ($this->countryFilter) {
            $query->where('countries.name', $this->countryFilter);
        }
        if ($this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'human_samples.id')
                    ->where('sub_project_assignments.assignable_type', HumanSamples::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('human_samples.date_collected', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('human_samples.date_collected', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('human_samples.date_collected', '<=', $this->endDate);
        }

        return $query;
    }

    private function dashboardPayload(): array
    {
        $base = $this->baseQuery();

        $countExpression = $this->countExpression();
        $totalSamples = $this->visualize_by === 'patients'
            ? (clone $base)->distinct()->count('humans.id')
            : (clone $base)->count('human_samples.id');
        $uniquePatients = (clone $base)->distinct()->count('humans.id');
        $uniqueSampleTypes = (clone $base)->distinct()->count('sample_types.id');
        $uniqueSamplingSites = (clone $base)->distinct()->count('sampling_sites.id');

        $humanSamplesByType = (clone $base)
            ->select('sample_types.name as k', DB::raw($countExpression.' as c'))
            ->whereNotNull('sample_types.name')
            ->groupBy('sample_types.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $humanSamplesBySite = (clone $base)
            ->select('sampling_sites.name as k', DB::raw($countExpression.' as c'))
            ->whereNotNull('sampling_sites.name')
            ->groupBy('sampling_sites.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $humanSamplesByEthnicity = (clone $base)
            ->select('humans.ethnicity as k', DB::raw($countExpression.' as c'))
            ->whereNotNull('humans.ethnicity')
            ->groupBy('humans.ethnicity')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $humanSamplesByOccupation = (clone $base)
            ->select('humans.occupation as k', DB::raw($countExpression.' as c'))
            ->whereNotNull('humans.occupation')
            ->groupBy('humans.occupation')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $humanSamplesByCountry = (clone $base)
            ->select('countries.name as k', DB::raw($countExpression.' as c'))
            ->whereNotNull('countries.name')
            ->groupBy('countries.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $humanSamplesBySex = (clone $base)
            ->select('humans.sex as k', DB::raw($countExpression.' as c'))
            ->whereNotNull('humans.sex')
            ->groupBy('humans.sex')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $humanSamplesByAgeRange = $this->ageRangeCounts(clone $base);

        $descriptive_stats = [
            'total_samples' => $totalSamples,
            'unique_patients' => $uniquePatients,
            'unique_sampling_sites' => $uniqueSamplingSites,
            'unique_sample_types' => $uniqueSampleTypes,
            'collection_timeline' => $this->timelineCounts(clone $base),
        ];

        $pieChartTabs = [
            ['key' => 'sample_type', 'label' => 'Sample Type', 'data' => $humanSamplesByType],
            ['key' => 'ethnicity', 'label' => 'Ethnicity', 'data' => $humanSamplesByEthnicity],
            ['key' => 'sex', 'label' => 'Sex', 'data' => $humanSamplesBySex],
        ];

        $barChartTabs = [
            ['key' => 'sampling_site', 'label' => 'Sampling Site', 'data' => $humanSamplesBySite],
            ['key' => 'occupation', 'label' => 'Occupation', 'data' => $humanSamplesByOccupation],
            ['key' => 'country', 'label' => 'Country', 'data' => $humanSamplesByCountry],
            ['key' => 'age_range', 'label' => 'Age Range', 'data' => $humanSamplesByAgeRange],
        ];

        $mapColorVariableOptions = [
            ['key' => 'type', 'label' => 'Sample Type'],
            ['key' => 'ethnicity', 'label' => 'Ethnicity'],
            ['key' => 'occupation', 'label' => 'Occupation'],
            ['key' => 'country', 'label' => 'Country'],
            ['key' => 'sex', 'label' => 'Sex'],
            ['key' => 'age_range', 'label' => 'Age Range'],
            ['key' => 'sampling_site', 'label' => 'Sampling Site'],
        ];

        return [
            'descriptive_stats' => $descriptive_stats,
            'humanSamplesByType' => $humanSamplesByType,
            'humanSamplesBySite' => $humanSamplesBySite,
            'humanSamplesByEthnicity' => $humanSamplesByEthnicity,
            'humanSamplesByOccupation' => $humanSamplesByOccupation,
            'humanSamplesByCountry' => $humanSamplesByCountry,
            'humanSamplesBySamplingSite' => $humanSamplesBySite,
            'humanSamplesBySex' => $humanSamplesBySex,
            'humanSamplesByAgeRange' => $humanSamplesByAgeRange,
            'humanSamplesByEthnicityCount' => collect($humanSamplesByEthnicity)->map(fn ($c, $k) => ['ethnicity' => $k, 'count' => $c])->values(),
            'humanSamplesByOccupationCount' => collect($humanSamplesByOccupation)->map(fn ($c, $k) => ['occupation' => $k, 'count' => $c])->values(),
            'humanSamplesByCountryCount' => collect($humanSamplesByCountry)->map(fn ($c, $k) => ['country' => $k, 'count' => $c])->values(),
            'humanSamplesBySamplingSiteCount' => collect($humanSamplesBySite)->map(fn ($c, $k) => ['sampling_site' => $k, 'count' => $c])->values(),
            'humanSamplesBySexCount' => collect($humanSamplesBySex)->map(fn ($c, $k) => ['sex' => $k, 'count' => $c])->values(),
            'humanSamplesByAgeRangeCount' => collect($humanSamplesByAgeRange)->map(fn ($c, $k) => ['age_range' => $k, 'count' => $c])->values(),
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
            'mapPointsUrl' => route('humans.dashboard.map-points'),
            'modalTableUrls' => [
                'humanSamplesModal' => route('humans.dashboard.modal.samples'),
            ],
            'activeFilters' => [
                'visualize_by' => $this->visualize_by,
                'sampleVisibility' => $this->sampleVisibility,
                'sampleTypeFilter' => $this->sampleTypeFilter,
                'samplingSiteFilter' => $this->samplingSiteFilter,
                'ethnicityFilter' => $this->ethnicityFilter,
                'occupationFilter' => $this->occupationFilter,
                'countryFilter' => $this->countryFilter,
                'subProjectFilter' => $this->subProjectFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ],
        ];
    }

    private function allSampleTypes()
    {
        $base = HumanSamples::query()->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('sample_types.name')->pluck('sample_types.name')->filter()->values();
    }

    private function allSamplingSites()
    {
        $base = HumanSamples::query()->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('sampling_sites.name')->pluck('sampling_sites.name')->filter()->values();
    }

    private function allEthnicities()
    {
        $base = HumanSamples::query()->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('humans.ethnicity')->pluck('humans.ethnicity')->filter()->values();
    }

    private function allOccupations()
    {
        $base = HumanSamples::query()->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('humans.occupation')->pluck('humans.occupation')->filter()->values();
    }

    private function allCountries()
    {
        $base = HumanSamples::query()
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');
        $this->applyVisibilityScope($base);

        return $base->distinct()->orderBy('countries.name')->pluck('countries.name')->filter()->values();
    }

    private function allSubProjects()
    {
        $base = HumanSamples::query();
        $this->applyVisibilityScope($base);

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', HumanSamples::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('human_samples.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    private function applyVisibilityScope($query): void
    {
        if ($this->isGuestMode()) {
            $query->where('human_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->where('tubes.tubes_content_type', HumanSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('human_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->where('human_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                        ->where('tubes.tubes_content_type', HumanSamples::class);
                });
            }
        }
    }

    private function timelineCounts($base): array
    {
        $driver = DB::getDriverName();
        $expr = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT(human_samples.date_collected, '%Y')",
                'pgsql' => "to_char(human_samples.date_collected, 'YYYY')",
                default => "strftime('%Y', human_samples.date_collected)",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT(human_samples.date_collected, '%Y-%m')",
            'pgsql' => "to_char(human_samples.date_collected, 'YYYY-MM')",
            default => "strftime('%Y-%m', human_samples.date_collected)",
        };

        $rows = $base
            ->select(DB::raw($expr.' as ym'), DB::raw($this->countExpression().' as c'))
            ->whereNotNull('human_samples.date_collected')
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

    private function ageRangeCounts($base): array
    {
        $driver = DB::getDriverName();
        $ageExpr = match ($driver) {
            'mysql' => 'TIMESTAMPDIFF(YEAR, humans.date_of_birth, CURDATE())',
            'pgsql' => "DATE_PART('year', age(CURRENT_DATE, humans.date_of_birth))",
            default => "CAST((julianday('now') - julianday(humans.date_of_birth)) / 365.25 AS INTEGER)",
        };

        $case = "CASE
            WHEN {$ageExpr} < 18 THEN '0-17'
            WHEN {$ageExpr} < 30 THEN '18-29'
            WHEN {$ageExpr} < 50 THEN '30-49'
            WHEN {$ageExpr} < 70 THEN '50-69'
            ELSE '70+'
        END";

        return $base
            ->whereNotNull('humans.date_of_birth')
            ->select(DB::raw($case.' as bucket'), DB::raw($this->countExpression().' as c'))
            ->groupBy('bucket')
            ->orderByDesc('c')
            ->pluck('c', 'bucket')
            ->toArray();
    }

    private function countExpression(): string
    {
        return $this->visualize_by === 'patients'
            ? 'COUNT(DISTINCT humans.id)'
            : 'COUNT(human_samples.id)';
    }
}
