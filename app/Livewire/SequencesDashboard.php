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
use App\Models\Sequences;
use App\Models\SubProject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;

#[Title('Sequences Dashboard')]
class SequencesDashboard extends PlainComponent
{
    protected ?int $projectId;

    public string $timelineGranularity = 'monthly';

    public string $sourceTypeFilter = 'all';

    public string $methodFilter = '';

    public string $instrumentFilter = '';

    public string $laboratoryFilter = '';

    public string $sequencedByFilter = '';

    public string $subProjectFilter = '';

    public ?int $startLength = null;

    public ?int $endLength = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

    public function updated($propertyName): void
    {
        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function resetFilters(): void
    {
        $this->reset([
            'timelineGranularity',
            'sourceTypeFilter',
            'methodFilter',
            'instrumentFilter',
            'laboratoryFilter',
            'sequencedByFilter',
            'subProjectFilter',
            'startLength',
            'endLength',
            'startDate',
            'endDate',
        ]);

        $this->dispatch('filtersUpdated', data: $this->dashboardPayload());
    }

    public function render()
    {
        return view('livewire.sequences-dashboard', array_merge(
            $this->dashboardPayload(),
            [
                'isGuestMode' => $this->isGuestMode(),
                'allMethods' => $this->allMethods(),
                'allInstruments' => $this->allInstruments(),
                'allLaboratories' => $this->allLaboratories(),
                'allPeople' => $this->allPeople(),
                'allSubProjects' => $this->allSubProjects(),
            ]
        ));
    }

    /**
     * Base query used across dashboard widgets.
     */
    private function baseQuery(): Builder
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

        if ($this->isGuestMode()) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'exp_na.id')
                    ->where('tubes.tubes_content_type', NucleicAcids::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('sequences.projects_id', $this->projectId);
        }

        return $query;
    }

    private function filteredQuery(): Builder
    {
        $query = $this->baseQuery();

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
                $query->where('orig_na.nucleic_content_type', $sourceType);
            }
        }

        if ($this->methodFilter !== '') {
            $query->where('sequences.method', $this->methodFilter);
        }

        if ($this->instrumentFilter !== '') {
            $query->where('sequences.instrument', $this->instrumentFilter);
        }

        if ($this->laboratoryFilter !== '') {
            $query->where('laboratories.name', $this->laboratoryFilter);
        }

        if ($this->sequencedByFilter !== '') {
            $query->whereRaw($this->peopleNameSql().' = ?', [$this->sequencedByFilter]);
        }
        if ($this->subProjectFilter !== '') {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'sequences.id')
                    ->where('sub_project_assignments.assignable_type', Sequences::class)
                    ->where('sub_projects.code', $this->subProjectFilter);
            });
        }

        if (! is_null($this->startLength) && ! is_null($this->endLength)) {
            $query->whereBetween('sequences.length', [$this->startLength, $this->endLength]);
        } elseif (! is_null($this->startLength)) {
            $query->where('sequences.length', '>=', $this->startLength);
        } elseif (! is_null($this->endLength)) {
            $query->where('sequences.length', '<=', $this->endLength);
        }

        if (! is_null($this->startDate) && ! is_null($this->endDate)) {
            $query->whereBetween('sequences.date_sequenced', [$this->startDate, $this->endDate]);
        } elseif (! is_null($this->startDate)) {
            $query->where('sequences.date_sequenced', '>=', $this->startDate);
        } elseif (! is_null($this->endDate)) {
            $query->where('sequences.date_sequenced', '<=', $this->endDate);
        }

        return $query;
    }

    /**
     * @return array{
     *   descriptive_stats: array{
     *     total_samples: int,
     *     human_samples: int,
     *     animal_samples: int,
     *     environment_samples: int,
     *     culture_samples: int,
     *     pool_samples: int,
     *     sequencing_timeline: array<string, int>
     *   },
     *   sequencesBySource: array<string, int>,
     *   sequencesByMethod: array<string, int>,
     *   sequencesByInstrument: array<string, int>,
     *   sequencesByLaboratory: array<string, int>,
     *   mapPointsUrl: string,
     *   activeFilters: array<string, mixed>,
     *   modalTableUrls: array<string, string>
     * }
     */
    private function dashboardPayload(): array
    {
        $filtered = $this->filteredQuery();

        $countsBySourceType = (clone $filtered)
            ->select([
                'orig_na.nucleic_content_type as source_type',
                DB::raw('COUNT(*) as aggregate'),
            ])
            ->groupBy('orig_na.nucleic_content_type')
            ->pluck('aggregate', 'source_type')
            ->mapWithKeys(function ($count, $type) {
                $key = $type ? class_basename((string) $type) : 'Unknown';

                return [$key => (int) $count];
            })
            ->toArray();

        $total = (clone $filtered)->count();

        $timeline = $this->sequencingTimeline(clone $filtered);

        $descriptive = [
            'total_samples' => (int) $total,
            'human_samples' => (int) (($countsBySourceType['HumanSamples'] ?? 0)),
            'animal_samples' => (int) (($countsBySourceType['AnimalSamples'] ?? 0)),
            'environment_samples' => (int) (($countsBySourceType['EnvironmentSamples'] ?? 0)),
            'culture_samples' => (int) (($countsBySourceType['Cultures'] ?? 0)),
            'pool_samples' => (int) (($countsBySourceType['Pools'] ?? 0)),
            'sequencing_timeline' => $timeline,
        ];

        $sequencesByMethod = (clone $filtered)
            ->select(['sequences.method', DB::raw('COUNT(*) as aggregate')])
            ->groupBy('sequences.method')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->pluck('aggregate', 'sequences.method')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $sequencesByInstrument = (clone $filtered)
            ->select(['sequences.instrument', DB::raw('COUNT(*) as aggregate')])
            ->groupBy('sequences.instrument')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->pluck('aggregate', 'sequences.instrument')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $sequencesByLaboratory = (clone $filtered)
            ->select(['laboratories.name as laboratory', DB::raw('COUNT(*) as aggregate')])
            ->groupBy('laboratories.name')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->pluck('aggregate', 'laboratory')
            ->filter(fn ($v, $k) => $k !== null && $k !== '')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        return [
            'descriptive_stats' => $descriptive,
            'sequencesBySource' => $countsBySourceType,
            'sequencesByMethod' => $sequencesByMethod,
            'sequencesByInstrument' => $sequencesByInstrument,
            'sequencesByLaboratory' => $sequencesByLaboratory,
            'mapPointsUrl' => route('sequences.dashboard.map-points'),
            'activeFilters' => $this->activeFilters(),
            'modalTableUrls' => [
                'sequencesModal' => route('sequences.dashboard.modal.all'),
                'humanSamplesModal' => route('sequences.dashboard.modal.human'),
                'animalSamplesModal' => route('sequences.dashboard.modal.animal'),
                'environmentSamplesModal' => route('sequences.dashboard.modal.environment'),
                'cultureSamplesModal' => route('sequences.dashboard.modal.culture'),
                'poolSamplesModal' => route('sequences.dashboard.modal.pool'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activeFilters(): array
    {
        return [
            'timelineGranularity' => $this->timelineGranularity,
            'sourceTypeFilter' => $this->sourceTypeFilter,
            'methodFilter' => $this->methodFilter,
            'instrumentFilter' => $this->instrumentFilter,
            'laboratoryFilter' => $this->laboratoryFilter,
            'sequencedByFilter' => $this->sequencedByFilter,
            'subProjectFilter' => $this->subProjectFilter,
            'startLength' => $this->startLength,
            'endLength' => $this->endLength,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function sequencingTimeline(Builder $query): array
    {
        $driver = DB::getDriverName();

        $groupSql = $this->timelineGranularity === 'yearly'
            ? match ($driver) {
                'mysql' => "DATE_FORMAT(sequences.date_sequenced, '%Y')",
                'pgsql' => "TO_CHAR(sequences.date_sequenced, 'YYYY')",
                default => "strftime('%Y', sequences.date_sequenced)",
            }
        : match ($driver) {
            'mysql' => "DATE_FORMAT(sequences.date_sequenced, '%Y-%m')",
            'pgsql' => "TO_CHAR(sequences.date_sequenced, 'YYYY-MM')",
            default => "strftime('%Y-%m', sequences.date_sequenced)",
        };

        $rows = $query
            ->select([DB::raw($groupSql.' as grp'), DB::raw('COUNT(*) as aggregate')])
            ->whereNotNull('sequences.date_sequenced')
            ->groupBy('grp')
            ->pluck('aggregate', 'grp')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $timeline = [];
        if ($this->timelineGranularity === 'yearly') {
            for ($i = 9; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->format('Y');
                $timeline[$year] = (int) ($rows[$year] ?? 0);
            }

            return $timeline;
        }

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $label = $month->format('M Y');
            $timeline[$label] = (int) ($rows[$key] ?? 0);
        }

        return $timeline;
    }

    /**
     * @return array<int, string>
     */
    private function allMethods(): array
    {
        return $this->baseQuery()
            ->select('sequences.method')
            ->distinct()
            ->orderBy('sequences.method')
            ->pluck('sequences.method')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function allInstruments(): array
    {
        return $this->baseQuery()
            ->select('sequences.instrument')
            ->distinct()
            ->orderBy('sequences.instrument')
            ->pluck('sequences.instrument')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function allLaboratories(): array
    {
        return $this->baseQuery()
            ->select('laboratories.name')
            ->whereNotNull('laboratories.name')
            ->distinct()
            ->orderBy('laboratories.name')
            ->pluck('laboratories.name')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function allPeople(): array
    {
        return $this->baseQuery()
            ->select(DB::raw($this->peopleNameSql().' as person'))
            ->whereNotNull('people.id')
            ->distinct()
            ->orderBy('person')
            ->pluck('person')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function allSubProjects(): array
    {
        return SubProject::query()
            ->join('sub_project_assignments', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->where('sub_project_assignments.assignable_type', Sequences::class)
            ->whereIn('sub_project_assignments.assignable_id', $this->baseQuery()->select('sequences.id'))
            ->distinct()
            ->orderBy('sub_projects.code')
            ->pluck('sub_projects.code')
            ->filter()
            ->values()
            ->all();
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
}
