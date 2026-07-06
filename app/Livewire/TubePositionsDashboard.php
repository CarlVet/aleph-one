<?php

namespace App\Livewire;

use App\Models\SubProject;
use App\Models\Tubes;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TubePositionsDashboard extends PlainComponent
{
    public string $laboratoryFilter = '';

    public string $locationFilter = '';

    public string $boxFilter = '';

    public string $contentTypeFilter = '';

    public string $subProjectFilter = '';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $timelineGranularity = 'monthly';

    public function canEdit(): bool
    {
        if ($this->isGuestMode()) {
            return false;
        }

        $user = Auth::user();
        if (! $user || ! $user->people) {
            return false;
        }

        $project = $user->people->projects()
            ->where('projects.id', $this->selectedProjectId())
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
            'laboratoryFilter',
            'locationFilter',
            'boxFilter',
            'contentTypeFilter',
            'subProjectFilter',
            'startDate',
            'endDate',
            'timelineGranularity',
        ]);

        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function render()
    {
        return view('livewire.tube-positions-dashboard', $this->dashboardPayload());
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardPayload(): array
    {
        $positioned = $this->positionedTubesQuery();
        $withoutPosition = $this->tubesWithoutPositionQuery();

        $tubesWithPosition = (clone $positioned)->count('tubes.id');
        $tubesWithoutPosition = (clone $withoutPosition)->count('tubes.id');
        $uniqueLocations = (clone $positioned)->distinct()->count('locations.id');
        $uniqueLaboratories = (clone $positioned)->distinct()->count('laboratories.id');
        $uniqueBoxes = (clone $positioned)->distinct()->count('boxes.id');

        $byContentType = $this->contentTypeCounts(clone $positioned);
        $byLaboratory = $this->groupedCounts(clone $positioned, 'laboratories.name');
        $byLocation = $this->groupedCounts(clone $positioned, 'locations.name');
        $byBox = $this->groupedCounts(clone $positioned, 'boxes.code');

        $pieChartTabs = [
            ['key' => 'content_type', 'label' => 'Content type', 'data' => $byContentType],
            ['key' => 'laboratory', 'label' => 'Laboratory', 'data' => $byLaboratory],
            ['key' => 'location', 'label' => 'Location', 'data' => $byLocation],
            ['key' => 'box', 'label' => 'Box', 'data' => $byBox],
        ];

        $barChartTabs = $pieChartTabs;

        $mapColorVariableOptions = [
            ['key' => 'content_type', 'label' => 'Content type'],
            ['key' => 'laboratory', 'label' => 'Laboratory'],
            ['key' => 'location', 'label' => 'Location'],
            ['key' => 'box', 'label' => 'Box'],
        ];

        $descriptive_stats = [
            'tubes_with_position' => $tubesWithPosition,
            'tubes_without_position' => $tubesWithoutPosition,
            'unique_locations' => $uniqueLocations,
            'unique_laboratories' => $uniqueLaboratories,
            'unique_boxes' => $uniqueBoxes,
            'position_timeline' => $this->timelineCounts(clone $positioned),
        ];

        return [
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $this->canEdit(),
            'descriptive_stats' => $descriptive_stats,
            'pieChartTabs' => $pieChartTabs,
            'barChartTabs' => $barChartTabs,
            'mapColorVariableOptions' => $mapColorVariableOptions,
            'mapPointsUrl' => route('tube-positions.dashboard.map-points'),
            'activeFilters' => [
                'laboratoryFilter' => $this->laboratoryFilter,
                'locationFilter' => $this->locationFilter,
                'boxFilter' => $this->boxFilter,
                'contentTypeFilter' => $this->contentTypeFilter,
                'subProjectFilter' => $this->subProjectFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'timelineGranularity' => $this->timelineGranularity,
            ],
            'allLaboratories' => $this->ensureSelectedInOptions($this->allLaboratories(), $this->laboratoryFilter),
            'allLocations' => $this->ensureSelectedInOptions($this->allLocations(), $this->locationFilter),
            'allBoxes' => $this->ensureSelectedInOptions($this->allBoxes(), $this->boxFilter),
            'allContentTypes' => $this->ensureSelectedInOptions($this->allContentTypes(), $this->contentTypeFilter),
            'allSubProjects' => $this->ensureSelectedInOptions($this->allSubProjects(), $this->subProjectFilter),
            'tubesWithPositionRows' => $this->tubesWithPositionRows(clone $positioned),
            'tubesWithoutPositionRows' => $this->tubesWithoutPositionRows(clone $withoutPosition),
            'locationSummaryRows' => $this->locationSummaryRows(clone $positioned),
            'laboratorySummaryRows' => $this->laboratorySummaryRows(clone $positioned),
            'boxSummaryRows' => $this->boxSummaryRows(clone $positioned),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function ensureSelectedInOptions(Collection $options, string $current): Collection
    {
        $current = trim($current);
        if ($current === '') {
            return $options->values();
        }

        if ($options->contains($current)) {
            return $options->values();
        }

        return $options->push($current)->sort()->values();
    }

    private function latestTubePositionsSubquery()
    {
        return DB::table('tube_positions')
            ->select('tubes_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('tubes_id');
    }

    private function latestBoxPositionsSubquery()
    {
        return DB::table('box_positions')
            ->select('boxes_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('boxes_id');
    }

    private function positionedTubesQuery()
    {
        $ltp = $this->latestTubePositionsSubquery();
        $lbp = $this->latestBoxPositionsSubquery();

        $query = DB::table('tubes')
            ->joinSub($ltp, 'ltp', 'tubes.id', '=', 'ltp.tubes_id')
            ->join('tube_positions as tp', 'tp.id', '=', 'ltp.latest_id')
            ->join('boxes', 'boxes.id', '=', 'tp.boxes_id')
            ->joinSub($lbp, 'lbp', 'boxes.id', '=', 'lbp.boxes_id')
            ->join('box_positions as bp', 'bp.id', '=', 'lbp.latest_id')
            ->join('locations', 'locations.id', '=', 'bp.locations_id')
            ->join('laboratories', 'laboratories.id', '=', 'locations.laboratories_id');

        $this->applyProjectScope($query);
        $this->applyPositionFilters($query);

        return $query;
    }

    private function tubesWithoutPositionQuery()
    {
        $query = DB::table('tubes')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tube_positions')
                    ->whereColumn('tube_positions.tubes_id', 'tubes.id');
            });

        $this->applyProjectScope($query);
        $this->applyTubeOnlyFilters($query);

        return $query;
    }

    private function applyProjectScope($query): void
    {
        if ($this->isGuestMode()) {
            $query->where('tubes.is_private', false);

            return;
        }

        $query->where('tubes.projects_id', $this->selectedProjectId());
    }

    private function applyPositionFilters($query): void
    {
        if ($this->laboratoryFilter !== '') {
            $query->where('laboratories.name', $this->laboratoryFilter);
        }

        if ($this->locationFilter !== '') {
            $query->where('locations.name', $this->locationFilter);
        }

        if ($this->boxFilter !== '') {
            $query->where(function ($w) {
                $w->where('boxes.code', $this->boxFilter)
                    ->orWhere('boxes.name', $this->boxFilter);
            });
        }

        $this->applyTubeOnlyFilters($query);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('tp.date_moved', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('tp.date_moved', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('tp.date_moved', '<=', $this->endDate);
        }
    }

    private function applyTubeOnlyFilters($query): void
    {
        if ($this->contentTypeFilter !== '') {
            $basename = $this->contentTypeFilter;
            $query->where(function ($w) use ($basename) {
                $w->where('tubes.tubes_content_type', 'like', '%'.$basename)
                    ->orWhere('tubes.tubes_content_type', 'App\\Models\\'.$basename);
            });
        }

        if ($this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'tubes.id')
                    ->where('sub_project_assignments.assignable_type', Tubes::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }
    }

    /**
     * @return array<string, int>
     */
    private function contentTypeCounts($query): array
    {
        return $query
            ->select('tubes.tubes_content_type')
            ->get()
            ->groupBy(fn ($row) => $this->normalizeContentTypeLabel((string) $row->tubes_content_type))
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function groupedCounts($query, string $column): array
    {
        return $query
            ->select(DB::raw("{$column} as k"), DB::raw('COUNT(tubes.id) as c'))
            ->whereNotNull(DB::raw($column))
            ->where(DB::raw($column), '!=', '')
            ->groupBy('k')
            ->orderByDesc('c')
            ->pluck('c', 'k')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function timelineCounts($base): array
    {
        $driver = DB::getDriverName();
        $expr = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT(tp.date_moved, '%Y')",
                'pgsql' => "to_char(tp.date_moved, 'YYYY')",
                default => "strftime('%Y', tp.date_moved)",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT(tp.date_moved, '%Y-%m')",
            'pgsql' => "to_char(tp.date_moved, 'YYYY-MM')",
            default => "strftime('%Y-%m', tp.date_moved)",
        };

        $rows = $base
            ->select(DB::raw($expr.' as ym'), DB::raw('COUNT(tubes.id) as c'))
            ->whereNotNull('tp.date_moved')
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->map(fn ($count) => (int) $count)
            ->toArray();

        $timeline = [];
        if ($this->timelineGranularity === 'yearly') {
            for ($i = 9; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->format('Y');
                $timeline[$year] = $rows[$year] ?? 0;
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $key = $month->format('Y-m');
                $label = $month->format('M Y');
                $timeline[$label] = $rows[$key] ?? 0;
            }
        }

        return $timeline;
    }

    private function allLaboratories(): Collection
    {
        return (clone $this->positionedTubesQuery())
            ->select('laboratories.name')
            ->whereNotNull('laboratories.name')
            ->distinct()
            ->orderBy('laboratories.name')
            ->pluck('laboratories.name');
    }

    private function allLocations(): Collection
    {
        return (clone $this->positionedTubesQuery())
            ->select('locations.name')
            ->whereNotNull('locations.name')
            ->distinct()
            ->orderBy('locations.name')
            ->pluck('locations.name');
    }

    private function allBoxes(): Collection
    {
        return (clone $this->positionedTubesQuery())
            ->select('boxes.code')
            ->whereNotNull('boxes.code')
            ->distinct()
            ->orderBy('boxes.code')
            ->pluck('boxes.code');
    }

    private function allContentTypes(): Collection
    {
        return (clone $this->positionedTubesQuery())
            ->select('tubes.tubes_content_type')
            ->whereNotNull('tubes.tubes_content_type')
            ->distinct()
            ->pluck('tubes.tubes_content_type')
            ->map(fn ($type) => $this->normalizeContentTypeLabel((string) $type))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function allSubProjects(): Collection
    {
        if ($this->isGuestMode()) {
            return collect();
        }

        return SubProject::query()
            ->where('project_id', $this->selectedProjectId())
            ->orderBy('code')
            ->pluck('code');
    }

    private function normalizeContentTypeLabel(string $type): string
    {
        if (str_starts_with($type, 'AppModels')) {
            return substr($type, strlen('AppModels'));
        }

        return class_basename($type);
    }

    private function tubesWithPositionRows($query): Collection
    {
        return $query
            ->select([
                'tubes.code',
                'tubes.alias_code',
                'tubes.tubes_content_type',
                'laboratories.name as laboratory',
                'locations.name as location',
                'boxes.code as box_code',
                'tp.position_x',
                'tp.position_y',
                'tp.date_moved',
            ])
            ->orderBy('tubes.code')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $row->content_type = $this->normalizeContentTypeLabel((string) $row->tubes_content_type);
                $row->position = trim(($row->position_x ?? '').($row->position_y ?? ''));

                return $row;
            });
    }

    private function tubesWithoutPositionRows($query): Collection
    {
        return $query
            ->select([
                'tubes.code',
                'tubes.alias_code',
                'tubes.tubes_content_type',
                'tubes.date_processed',
            ])
            ->orderBy('tubes.code')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $row->content_type = $this->normalizeContentTypeLabel((string) $row->tubes_content_type);

                return $row;
            });
    }

    private function locationSummaryRows($query): Collection
    {
        return $query
            ->select([
                'locations.name as location',
                'laboratories.name as laboratory',
                DB::raw('COUNT(tubes.id) as tube_count'),
            ])
            ->groupBy('locations.name', 'laboratories.name')
            ->orderByDesc('tube_count')
            ->limit(200)
            ->get();
    }

    private function laboratorySummaryRows($query): Collection
    {
        return $query
            ->select([
                'laboratories.name as laboratory',
                DB::raw('COUNT(DISTINCT locations.id) as location_count'),
                DB::raw('COUNT(tubes.id) as tube_count'),
            ])
            ->groupBy('laboratories.name')
            ->orderByDesc('tube_count')
            ->limit(200)
            ->get();
    }

    private function boxSummaryRows($query): Collection
    {
        return $query
            ->select([
                'boxes.code as box_code',
                'boxes.name as box_name',
                'locations.name as location',
                'laboratories.name as laboratory',
                DB::raw('COUNT(tubes.id) as tube_count'),
            ])
            ->groupBy('boxes.code', 'boxes.name', 'locations.name', 'laboratories.name')
            ->orderByDesc('tube_count')
            ->limit(200)
            ->get();
    }
}
