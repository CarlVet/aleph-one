<?php

namespace App\Livewire;

use App\Models\EnvironmentSamples;
use App\Models\SubProject;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;

#[Title('Environment Samples Dashboard')]
class EnvironmentSamplesDashboard extends PlainComponent
{
    protected $projectId;

    public string $sampleVisibility = 'all';

    public string $timelineGranularity = 'monthly';

    public string $sampleTypeFilter = '';

    public string $samplingSiteFilter = '';

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
        $this->reset(['sampleVisibility', 'timelineGranularity', 'sampleTypeFilter', 'samplingSiteFilter', 'subProjectFilter', 'startDate', 'endDate']);
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function render()
    {
        return view('livewire.environment-samples-dashboard', array_merge(
            $this->dashboardPayload(),
            [
                'isGuestMode' => $this->isGuestMode(),
                'canEdit' => $this->canEdit(),
                'allSampleTypes' => $this->allSampleTypes(),
                'allSamplingSites' => $this->allSamplingSites(),
                'allSubProjects' => $this->allSubProjects(),
            ]
        ));
    }

    private function baseQuery()
    {
        $query = EnvironmentSamples::query()
            ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
            ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id')
            ->leftJoin('people', 'environment_samples.people_id', '=', 'people.id');

        if ($this->isGuestMode()) {
            $query->where('environment_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                    ->where('tubes.tubes_content_type', EnvironmentSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('environment_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->where('environment_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                        ->where('tubes.tubes_content_type', EnvironmentSamples::class);
                });
            }
        }

        if ($this->sampleTypeFilter !== '') {
            $query->where('environment_sample_types.name', $this->sampleTypeFilter);
        }
        if ($this->samplingSiteFilter !== '') {
            $query->where('sampling_sites.name', $this->samplingSiteFilter);
        }
        if ($this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'environment_samples.id')
                    ->where('sub_project_assignments.assignable_type', EnvironmentSamples::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('environment_samples.date_collected', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('environment_samples.date_collected', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('environment_samples.date_collected', '<=', $this->endDate);
        }

        return $query;
    }

    private function dashboardPayload(): array
    {
        $base = $this->baseQuery();

        $now = Carbon::now();
        $startOfYear = $now->copy()->startOfYear()->toDateString();
        $endOfYear = $now->copy()->endOfYear()->toDateString();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $totalSamples = (clone $base)->count('environment_samples.id');
        $samplesThisYear = (clone $base)->whereBetween('environment_samples.date_collected', [$startOfYear, $endOfYear])->count('environment_samples.id');
        $samplesThisMonth = (clone $base)->whereBetween('environment_samples.date_collected', [$startOfMonth, $endOfMonth])->count('environment_samples.id');

        $environmentSamplesByType = (clone $base)
            ->select('environment_sample_types.name as k', DB::raw('COUNT(environment_samples.id) as c'))
            ->whereNotNull('environment_sample_types.name')
            ->groupBy('environment_sample_types.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $environmentSamplesBySite = (clone $base)
            ->select('sampling_sites.name as k', DB::raw('COUNT(environment_samples.id) as c'))
            ->whereNotNull('sampling_sites.name')
            ->groupBy('sampling_sites.name')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->toArray();

        $collectorRows = (clone $base)
            ->select([
                'people.id as id',
                'people.first_name as first_name',
                'people.last_name as last_name',
                DB::raw('COUNT(environment_samples.id) as c'),
            ])
            ->whereNotNull('people.id')
            ->groupBy('people.id', 'people.first_name', 'people.last_name')
            ->orderByDesc('c')
            ->get();

        $environmentSamplesByCollector = [];
        foreach ($collectorRows as $row) {
            $label = trim((string) ($row->first_name ?? '').' '.(string) ($row->last_name ?? ''));
            $environmentSamplesByCollector[$label !== '' ? $label : 'Unknown'] = (int) $row->c;
        }

        $descriptive_stats = [
            'total_samples' => $totalSamples,
            'samples_this_year' => $samplesThisYear,
            'samples_this_month' => $samplesThisMonth,
            'collection_timeline' => $this->timelineCounts(clone $base),
        ];

        $environmentSamplesByTypeCount = collect($environmentSamplesByType)
            ->map(fn ($count, $type) => ['type' => $type, 'count' => $count])
            ->values();

        $environmentSamplesBySiteCount = collect($environmentSamplesBySite)
            ->map(fn ($count, $site) => ['site' => $site, 'count' => $count])
            ->values();

        $environmentSamplesByCollectorCount = collect($environmentSamplesByCollector)
            ->map(fn ($count, $collector) => ['collector' => $collector, 'count' => $count])
            ->values();

        $pieChartTabs = [
            ['key' => 'sample_type', 'label' => 'Sample Type', 'data' => $environmentSamplesByType],
            ['key' => 'collector', 'label' => 'Collector', 'data' => $environmentSamplesByCollector],
        ];

        $barChartTabs = [
            ['key' => 'sampling_site', 'label' => 'Sampling Site', 'data' => $environmentSamplesBySite],
            ['key' => 'collector', 'label' => 'Collector', 'data' => $environmentSamplesByCollector],
        ];

        $mapColorVariableOptions = [
            ['key' => 'type', 'label' => 'Sample Type'],
            ['key' => 'sampling_site', 'label' => 'Sampling Site'],
            ['key' => 'collector', 'label' => 'Collector'],
        ];

        return [
            'descriptive_stats' => $descriptive_stats,
            'environmentSamplesByType' => $environmentSamplesByType,
            'environmentSamplesBySite' => $environmentSamplesBySite,
            'environmentSamplesByCollector' => $environmentSamplesByCollector,
            'environmentSamplesByTypeCount' => $environmentSamplesByTypeCount,
            'environmentSamplesBySiteCount' => $environmentSamplesBySiteCount,
            'environmentSamplesByCollectorCount' => $environmentSamplesByCollectorCount,
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
            'mapPointsUrl' => route('environment.dashboard.map-points'),
            'modalTableUrls' => [
                'environmentSamplesModal' => route('environment.dashboard.modal.samples'),
            ],
            'activeFilters' => [
                'sampleVisibility' => $this->sampleVisibility,
                'sampleTypeFilter' => $this->sampleTypeFilter,
                'samplingSiteFilter' => $this->samplingSiteFilter,
                'subProjectFilter' => $this->subProjectFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ],
        ];
    }

    private function allSampleTypes()
    {
        $base = EnvironmentSamples::query()
            ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id');

        if ($this->isGuestMode()) {
            $base->where('environment_samples.processed', true);
            $base->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                    ->where('tubes.tubes_content_type', EnvironmentSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $base->where('environment_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $base->where('environment_samples.processed', true);
                $base->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                        ->where('tubes.tubes_content_type', EnvironmentSamples::class);
                });
            }
        }

        return $base->distinct()->orderBy('environment_sample_types.name')->pluck('environment_sample_types.name')->filter()->values();
    }

    private function allSamplingSites()
    {
        $base = EnvironmentSamples::query()
            ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id');

        if ($this->isGuestMode()) {
            $base->where('environment_samples.processed', true);
            $base->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                    ->where('tubes.tubes_content_type', EnvironmentSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $base->where('environment_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $base->where('environment_samples.processed', true);
                $base->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                        ->where('tubes.tubes_content_type', EnvironmentSamples::class);
                });
            }
        }

        return $base->distinct()->orderBy('sampling_sites.name')->pluck('sampling_sites.name')->filter()->values();
    }

    private function allSubProjects()
    {
        $base = EnvironmentSamples::query();
        $this->applyVisibilityScope($base);

        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', EnvironmentSamples::class)
            ->whereIn('sub_project_assignments.assignable_id', $base->select('environment_samples.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values();
    }

    private function applyVisibilityScope($query): void
    {
        if ($this->isGuestMode()) {
            $query->where('environment_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                    ->where('tubes.tubes_content_type', EnvironmentSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('environment_samples.projects_id', $this->projectId);

            if ($this->sampleVisibility === 'processed_with_tubes') {
                $query->where('environment_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                        ->where('tubes.tubes_content_type', EnvironmentSamples::class);
                });
            }
        }
    }

    private function timelineCounts($base): array
    {
        $driver = DB::getDriverName();
        $expr = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT(environment_samples.date_collected, '%Y')",
                'pgsql' => "to_char(environment_samples.date_collected, 'YYYY')",
                default => "strftime('%Y', environment_samples.date_collected)",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT(environment_samples.date_collected, '%Y-%m')",
            'pgsql' => "to_char(environment_samples.date_collected, 'YYYY-MM')",
            default => "strftime('%Y-%m', environment_samples.date_collected)",
        };

        $rows = $base
            ->select(DB::raw($expr.' as ym'), DB::raw('COUNT(environment_samples.id) as c'))
            ->whereNotNull('environment_samples.date_collected')
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
}
