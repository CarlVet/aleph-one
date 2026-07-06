<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Microplastics;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Title('Microplastics Dashboard')]
class MicroplasticsDashboard extends PlainComponent
{
    protected ?int $projectId;

    public string $timelineGranularity = 'monthly';

    public string $mpsTypeFilter = '';

    public string $sourceTypeFilter = 'all';

    public string $subProjectFilter = '';

    public string $protocolFilter = '';

    public string $laboratoryFilter = '';

    public string $identifiedByFilter = '';

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

    public function canEdit(): bool
    {
        return ! $this->isGuestMode() && Auth::check() && $this->userCanWriteModule('microplastics');
    }

    public function resetFilters(): void
    {
        $this->reset([
            'timelineGranularity',
            'mpsTypeFilter',
            'sourceTypeFilter',
            'subProjectFilter',
            'protocolFilter',
            'laboratoryFilter',
            'identifiedByFilter',
        ]);
    }

    public function updated($propertyName): void
    {
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function render()
    {
        return view('livewire.microplastics-dashboard', array_merge(
            $this->dashboardPayload(),
            [
                'isGuestMode' => $this->isGuestMode(),
                'canEdit' => $this->canEdit(),
                'allMpsTypes' => $this->uniqueOptions(fn (Microplastics $record) => $record->mps_types?->name, ['mpsTypeFilter']),
                'allProtocols' => $this->uniqueOptions(fn (Microplastics $record) => $record->protocols?->name, ['protocolFilter']),
                'allLaboratories' => $this->uniqueOptions(fn (Microplastics $record) => $record->laboratories?->name, ['laboratoryFilter']),
                'allPeople' => $this->uniqueOptions(
                    fn (Microplastics $record) => trim(($record->people?->title ?? '').' '.($record->people?->first_name ?? '').' '.($record->people?->last_name ?? '')),
                    ['identifiedByFilter']
                ),
                'allSubProjects' => $this->subProjectOptions(),
                'availableSourceTypes' => $this->availableSourceTypes(),
            ]
        ));
    }

    private function groupCounts(Collection $records, callable|string $key): array
    {
        return $records
            ->groupBy($key)
            ->map(fn (Collection $rows, $label) => [
                'label' => $label ?: 'N/A',
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->all();
    }

    private function filteredQuery(array $except = []): Builder
    {
        $query = Microplastics::query()
            ->with([
                'mps_types',
                'protocols',
                'laboratories',
                'people',
                'microplastics_content',
                'subProjectAssignment.subProject',
                'tubes',
            ]);

        $query->whereIn('microplastics_content_type', $this->allowedSourceTypes());

        if ($this->projectId) {
            $query->where('projects_id', $this->projectId);
        } else {
            $query->where('is_private', false);
        }

        if (! in_array('mpsTypeFilter', $except, true) && $this->mpsTypeFilter !== '') {
            $query->whereHas('mps_types', fn (Builder $typeQuery) => $typeQuery->where('name', $this->mpsTypeFilter));
        }

        if (! in_array('sourceTypeFilter', $except, true) && $this->sourceTypeFilter !== 'all') {
            $query->where('microplastics_content_type', $this->sourceModelForFilter($this->sourceTypeFilter));
        }

        if (! in_array('subProjectFilter', $except, true) && $this->subProjectFilter !== '') {
            $query->whereHas('subProjectAssignment.subProject', fn (Builder $subProjectQuery) => $subProjectQuery->where('code', $this->subProjectFilter));
        }

        if (! in_array('protocolFilter', $except, true) && $this->protocolFilter !== '') {
            $query->whereHas('protocols', fn (Builder $protocolQuery) => $protocolQuery->where('name', $this->protocolFilter));
        }

        if (! in_array('laboratoryFilter', $except, true) && $this->laboratoryFilter !== '') {
            $query->whereHas('laboratories', fn (Builder $labQuery) => $labQuery->where('name', $this->laboratoryFilter));
        }

        if (! in_array('identifiedByFilter', $except, true) && $this->identifiedByFilter !== '') {
            $query->whereHas('people', function (Builder $peopleQuery): void {
                $needle = '%'.$this->identifiedByFilter.'%';
                $peopleQuery->where(function (Builder $nestedQuery) use ($needle): void {
                    $nestedQuery
                        ->where('title', 'like', $needle)
                        ->orWhere('first_name', 'like', $needle)
                        ->orWhere('last_name', 'like', $needle);
                });
            });
        }

        return $query;
    }

    private function subProjectOptions(): Collection
    {
        return $this->uniqueOptions(fn (Microplastics $record) => $record->subProject?->code, ['subProjectFilter']);
    }

    private function availableSourceTypes(): array
    {
        return $this->filteredQuery(['sourceTypeFilter'])
            ->get()
            ->pluck('microplastics_content_type')
            ->map(fn ($type) => class_basename((string) $type))
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn ($label) => [strtolower((string) $label) => (string) $label])
            ->all();
    }

    private function sourceModelForFilter(string $filter): string
    {
        return $this->filteredQuery(['sourceTypeFilter'])
            ->get()
            ->pluck('microplastics_content_type')
            ->first(fn ($type) => strtolower(class_basename((string) $type)) === $filter) ?? $filter;
    }

    private function allowedSourceTypes(): array
    {
        return [
            HumanSamples::class,
            AnimalSamples::class,
            EnvironmentSamples::class,
            ParasiteSamples::class,
            Pools::class,
        ];
    }

    private function dashboardPayload(): array
    {
        $records = $this->filteredQuery()->get();
        $concentrationRows = $this->concentrationRows($records);
        $feretRows = $this->feretRows($records);
        $pearsonRows = $this->pearsonRows($records);
        $averageConcentration = $concentrationRows
            ->pluck('concentration')
            ->filter(fn ($value) => $value !== null)
            ->avg();

        $bySource = $this->countsBy($records, fn (Microplastics $record) => class_basename((string) $record->microplastics_content_type));
        $byType = $this->countsBy($records, fn (Microplastics $record) => $record->mps_types?->name);
        $byProtocol = $this->countsBy($records, fn (Microplastics $record) => $record->protocols?->name);
        $byLaboratory = $this->countsBy($records, fn (Microplastics $record) => $record->laboratories?->name);
        $byIdentifiedBy = $this->countsBy($records, fn (Microplastics $record) => trim(($record->people?->title ?? '').' '.($record->people?->first_name ?? '').' '.($record->people?->last_name ?? '')));

        return [
            'totalRecords' => $records->count(),
            'uniqueProtocols' => $records->pluck('protocols.name')->filter()->unique()->count(),
            'uniqueLaboratories' => $records->pluck('laboratories.name')->filter()->unique()->count(),
            'uniqueSources' => $records->pluck('microplastics_content_type')->map(fn ($type) => class_basename((string) $type))->filter()->unique()->count(),
            'concentrationPerSample' => $averageConcentration !== null ? round((float) $averageConcentration, 2) : null,
            'averageSampleWeightPerSample' => round((float) $concentrationRows->pluck('sample_weight')->filter(fn ($value) => $value !== null)->avg(), 1),
            'averageFeret' => round((float) $feretRows->avg('m_feret'), 1),
            'averagePearson' => round((float) $pearsonRows->avg('r_coeff'), 3),
            'typeBreakdown' => $this->groupCounts($records, fn ($row) => $row->mps_types?->name),
            'protocolBreakdown' => $this->groupCounts($records, fn ($row) => $row->protocols?->name),
            'laboratoryBreakdown' => $this->groupCounts($records, fn ($row) => $row->laboratories?->name),
            'sourceBreakdown' => $this->groupCounts($records, fn ($row) => class_basename((string) $row->microplastics_content_type)),
            'recordsModalRows' => $this->recordRows($records),
            'protocolModalRows' => collect($this->groupCounts($records, fn ($row) => $row->protocols?->name)),
            'laboratoryModalRows' => collect($this->groupCounts($records, fn ($row) => $row->laboratories?->name)),
            'concentrationModalRows' => $concentrationRows,
            'feretModalRows' => $feretRows,
            'pearsonModalRows' => $pearsonRows,
            'timelineData' => $this->timelineCounts($records),
            'pieChartTabs' => [
                ['key' => 'source', 'label' => 'Source', 'data' => $bySource],
                ['key' => 'mps_type', 'label' => 'MPS Type', 'data' => $byType],
                ['key' => 'protocol', 'label' => 'Protocol', 'data' => $byProtocol],
            ],
            'barChartTabs' => [
                ['key' => 'laboratory', 'label' => 'Laboratory', 'data' => $byLaboratory],
                ['key' => 'identified_by', 'label' => 'Identified by', 'data' => $byIdentifiedBy],
                ['key' => 'protocol', 'label' => 'Protocol', 'data' => $byProtocol],
            ],
            'mapColorVariableOptions' => [
                ['key' => 'source', 'label' => 'Source'],
                ['key' => 'mps_type', 'label' => 'MPS type'],
                ['key' => 'protocol', 'label' => 'Protocol'],
                ['key' => 'laboratory', 'label' => 'Laboratory'],
                ['key' => 'identified_by', 'label' => 'Identified by'],
            ],
            'mapPointsUrl' => route('microplastics.dashboard.map-points'),
            'activeFilters' => [
                'mpsTypeFilter' => $this->mpsTypeFilter,
                'sourceTypeFilter' => $this->sourceTypeFilter,
                'subProjectFilter' => $this->subProjectFilter,
                'protocolFilter' => $this->protocolFilter,
                'laboratoryFilter' => $this->laboratoryFilter,
                'identifiedByFilter' => $this->identifiedByFilter,
            ],
        ];
    }

    private function countsBy(Collection $records, callable $callback): array
    {
        return $records
            ->groupBy(fn (Microplastics $record) => $this->normalizeLabel($callback($record)))
            ->map(fn (Collection $rows): int => $rows->count())
            ->sortDesc()
            ->all();
    }

    private function timelineCounts(Collection $records): array
    {
        $grouped = $records
            ->mapWithKeys(function (Microplastics $record): array {
                $date = $this->timelineDateForRecord($record);

                return $date ? [$record->id => $date] : [];
            })
            ->groupBy(function (CarbonInterface $date): string {
                return $this->timelineGranularity === 'yearly'
                    ? $date->format('Y')
                    : $date->format('Y-m');
            })
            ->map(fn (Collection $rows): int => $rows->count());

        if ($this->timelineGranularity === 'yearly') {
            return $grouped->sortKeys()->all();
        }

        return $grouped
            ->sortKeys()
            ->mapWithKeys(function (int $count, string $month): array {
                return [Carbon::createFromFormat('Y-m', $month)->format('M Y') => $count];
            })
            ->all();
    }

    private function timelineDateForRecord(Microplastics $record): ?CarbonInterface
    {
        if ($record->identification_date instanceof CarbonInterface) {
            return $record->identification_date;
        }

        if ($record->created_at instanceof CarbonInterface) {
            return $record->created_at;
        }

        return null;
    }

    private function normalizeLabel(mixed $value): string
    {
        $label = trim((string) $value);

        return $label === '' ? 'Unknown' : $label;
    }

    private function uniqueOptions(callable $callback, array $except = []): Collection
    {
        return $this->filteredQuery($except)
            ->get()
            ->map(fn (Microplastics $record) => trim((string) $callback($record)))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function recordRows(Collection $records): Collection
    {
        return $records
            ->sortBy('code')
            ->values()
            ->map(fn (Microplastics $record): array => [
                'code' => (string) $record->code,
                'source_type' => class_basename((string) $record->microplastics_content_type),
                'source_code' => (string) ($record->microplastics_content?->code ?? 'N/A'),
                'mps_type' => (string) ($record->mps_types?->name ?? 'Unknown'),
                'protocol' => (string) ($record->protocols?->name ?? 'Unknown'),
                'laboratory' => (string) ($record->laboratories?->name ?? 'Unknown'),
                'r_coeff' => $record->r_coeff !== null ? (float) $record->r_coeff : null,
                'm_feret' => $record->m_feret !== null ? (float) $record->m_feret : null,
            ]);
    }

    private function concentrationRows(Collection $records): Collection
    {
        return $records
            ->groupBy(fn (Microplastics $record) => $record->microplastics_content_type.'#'.$record->microplastics_content_id)
            ->map(function (Collection $group): array {
                /** @var Microplastics $first */
                $first = $group->first();
                $sampleWeight = $group
                    ->pluck('sample_weight')
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (float) $value)
                    ->avg();
                $count = $group->count();

                return [
                    'source_type' => class_basename((string) $first->microplastics_content_type),
                    'source_code' => (string) ($first->microplastics_content?->code ?? 'N/A'),
                    'count' => $count,
                    'sample_weight' => $sampleWeight !== null ? (float) $sampleWeight : null,
                    'concentration' => $sampleWeight && $sampleWeight > 0 ? round($count / $sampleWeight, 4) : null,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['concentration'] ?? -1)
            ->values();
    }

    private function feretRows(Collection $records): Collection
    {
        return $records
            ->filter(fn (Microplastics $record) => $record->m_feret !== null)
            ->sortByDesc(fn (Microplastics $record) => (float) $record->m_feret)
            ->values()
            ->map(fn (Microplastics $record): array => [
                'code' => (string) $record->code,
                'source_type' => class_basename((string) $record->microplastics_content_type),
                'source_code' => (string) ($record->microplastics_content?->code ?? 'N/A'),
                'mps_type' => (string) ($record->mps_types?->name ?? 'Unknown'),
                'm_feret' => (float) $record->m_feret,
            ]);
    }

    private function pearsonRows(Collection $records): Collection
    {
        return $records
            ->filter(fn (Microplastics $record) => $record->r_coeff !== null)
            ->sortByDesc(fn (Microplastics $record) => (float) $record->r_coeff)
            ->values()
            ->map(fn (Microplastics $record): array => [
                'code' => (string) $record->code,
                'source_type' => class_basename((string) $record->microplastics_content_type),
                'source_code' => (string) ($record->microplastics_content?->code ?? 'N/A'),
                'mps_type' => (string) ($record->mps_types?->name ?? 'Unknown'),
                'r_coeff' => (float) $record->r_coeff,
            ]);
    }
}
