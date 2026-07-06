<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\PoolsForm;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Tubes;
use App\Services\TubesService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('Pools Index')]
class PoolsIndex extends PlainComponent
{
    use ExportsTable;
    use WithColumnSorting;
    use WithPagination;

    public PoolsForm $form;

    protected ?int $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    protected function sortingPageName(): ?string
    {
        return 'articles-page';
    }

    /**
     * The list model is Tubes; pool columns are reached through the polymorphic
     * tubes_content relation and sorted via correlated subqueries on pools.
     */
    private function poolContentSort(string $column, ?array $join = null)
    {
        $sub = Pools::query()->whereColumn('pools.id', 'tubes.tubes_content_id');

        if ($join !== null) {
            [$table, $foreignKey] = $join;
            $sub->join($table, 'pools.'.$foreignKey, '=', $table.'.id');
        }

        return $sub->select($column)->limit(1);
    }

    /**
     * @return array<string, string|callable>
     */
    protected function sortMap(): array
    {
        return [
            'tube_code' => 'code',
            'pool_code' => fn ($q, $dir) => $q->orderBy($this->poolContentSort('pools.code'), $dir),
            'pool_date' => fn ($q, $dir) => $q->orderBy($this->poolContentSort('pools.date_pooled'), $dir),
            'pool_laboratory' => fn ($q, $dir) => $q->orderBy($this->poolContentSort('laboratories.name', ['laboratories', 'laboratories_id']), $dir),
            'pool_created_by' => fn ($q, $dir) => $q->orderBy($this->poolContentSort('people.last_name', ['people', 'people_id']), $dir),
        ];
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

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
            ->where('projects.id', $this->projectId)
            ->withPivot('permission')
            ->first();

        if (! $project || ! $project->pivot) {
            return false;
        }

        return $this->userCanWriteModule('pools');
    }

    public function updateField(int $tubeId, string $field, mixed $value): void
    {
        $tube = Tubes::find($tubeId);
        $ownerPeopleId = (int) optional(optional($tube)->tubes_content)->people_id;
        if (! $tube || ! $this->userCanMutateOwnedRecord($ownerPeopleId, 'pools')) {
            return;
        }

        $this->form->updateField($tubeId, $field, $value);
    }

    public function addContentCode(int $tubeId, string $contentCode, string $sampleType): void
    {
        $this->form->addContentCode($tubeId, $contentCode, $sampleType);
    }

    public function removeContentCode(int $tubeId, int $contentId): void
    {
        $this->form->removeContentCode($tubeId, $contentId);
    }

    public function delete(Tubes $tube): void
    {
        $ownerPeopleId = (int) optional($tube->tubes_content)->people_id;
        if (! $this->userCanMutateOwnedRecord($ownerPeopleId, 'pools')) {
            return;
        }

        $tube->delete();
        $this->form->refreshData();
    }

    public array $selectedPoolTubes = [];

    public bool $selectAllFiltered = false;

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedPoolTubes)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one pool tube.');

            return;
        }

        $tubes = Tubes::query()
            ->with('tubes_content')
            ->whereIn('id', $selectedIds->all())
            ->get();

        $deleted = 0;
        foreach ($tubes as $tube) {
            $ownerPeopleId = (int) optional($tube->tubes_content)->people_id;
            if (! $this->userCanMutateOwnedRecord($ownerPeopleId, 'pools')) {
                continue;
            }

            $tube->delete();
            $deleted++;
        }

        $this->selectedPoolTubes = [];
        session()->flash(
            $deleted > 0 ? 'message' : 'error',
            $deleted > 0 ? "{$deleted} selected pool tube(s) deleted successfully." : 'No selected pool tubes could be deleted.'
        );
    }

    public bool $isEditing = false;

    public function toggleEditMode(): void
    {
        if (! $this->userCanWriteModule('pools')) {
            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public string $selectedTable = 'pools_table';

    public array $originFilters = [];

    public function updatedSelectedTable(): void
    {
        $this->originFilters = [];
        $this->selectedPoolTubes = [];
        $this->selectAllFiltered = false;
        $this->resetPage('articles-page');
    }

    public function updating($field): void
    {
        if (
            is_string($field)
            && (
                str_starts_with($field, 'selectedPoolTubes')
                || $field === 'selectAllFiltered'
            )
        ) {
            return;
        }

        $this->resetPage('articles-page');
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedPoolTubes = [];

            return;
        }

        $query = $this->buildBaseQueryForSelectedTable();

        if (! $this->isGuestMode()) {
            $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
            if ($currentPeopleId <= 0) {
                $this->selectedPoolTubes = [];

                return;
            }

            $query->whereHas('tubes_content', function (Builder $contentQuery) use ($currentPeopleId): void {
                $contentQuery->where('people_id', $currentPeopleId);
            });
        }

        $ids = $query
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $this->selectedPoolTubes = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    protected function buildBaseQueryForSelectedTable(): Builder
    {
        $config = $this->selectedTableConfig();

        $query = Tubes::query()
            ->whereHas('tubes_content', fn (Builder $q) => $q->where('tubes_content_type', Pools::class))
            ->with(array_merge([
                'tubes_content',
                'tubes_content.subProjectAssignment.subProject',
                'tubes_content.pool_contents',
                'tubes_content.pool_contents.samples',
                'tubes_content.people',
                'tubes_content.laboratories',
                'tubes_content.projects',
                'projects',
            ], $config['with'] ?? []));

        if (
            $this->selectedTable === 'pools_table'
            || str_starts_with($this->selectedTable, 'pool_')
        ) {
            $query->with([
                'tubes_content.pool_contents.samples' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => [
                            'humans',
                            'sampling_sites',
                        ],
                        AnimalSamples::class => [
                            'animals.animal_species',
                            'sample_types',
                            'sampling_sites',
                        ],
                        EnvironmentSamples::class => [
                            'environment_sample_types',
                            'sampling_sites',
                        ],
                        ParasiteSamples::class => [
                            'parasites.parasite_species',
                            'parasites.parasites_origin',
                            'parasites.parasites_origin.sampling_sites',
                        ],
                        NucleicAcids::class => [
                            'nucleic_content' => function (MorphTo $morphTo): void {
                                $morphTo->morphWith([
                                    HumanSamples::class => ['sampling_sites'],
                                    AnimalSamples::class => ['sampling_sites'],
                                    EnvironmentSamples::class => ['sampling_sites'],
                                    ParasiteSamples::class => [
                                        'parasites.parasite_species',
                                        'parasites.parasites_origin',
                                        'parasites.parasites_origin.sampling_sites',
                                    ],
                                    Cultures::class => [
                                        'cultures_content' => function (MorphTo $morphTo): void {
                                            $morphTo->morphWith([
                                                HumanSamples::class => ['sampling_sites'],
                                                AnimalSamples::class => ['sampling_sites'],
                                                EnvironmentSamples::class => ['sampling_sites'],
                                                ParasiteSamples::class => [
                                                    'parasites.parasite_species',
                                                    'parasites.parasites_origin',
                                                    'parasites.parasites_origin.sampling_sites',
                                                ],
                                            ]);
                                        },
                                    ],
                                    Pools::class => [
                                        'pool_contents',
                                        'pool_contents.samples',
                                    ],
                                ]);
                            },
                        ],
                        Cultures::class => [
                            'cultures_content' => function (MorphTo $morphTo): void {
                                $morphTo->morphWith([
                                    HumanSamples::class => ['sampling_sites'],
                                    AnimalSamples::class => ['sampling_sites'],
                                    EnvironmentSamples::class => ['sampling_sites'],
                                    ParasiteSamples::class => [
                                        'parasites.parasite_species',
                                        'parasites.parasites_origin',
                                        'parasites.parasites_origin.sampling_sites',
                                    ],
                                    Pools::class => [
                                        'pool_contents',
                                        'pool_contents.samples',
                                    ],
                                ]);
                            },
                        ],
                    ]);
                },
            ]);
        }

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->where('is_private', false);
        } else {
            $query->where('projects_id', $this->projectId);
        }

        if (isset($config['scope']) && is_callable($config['scope'])) {
            $query = $config['scope']($query) ?? $query;
        }

        $filters = $config['filters'] ?? [];
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $query = $filter($query, $this) ?? $query;
            }
        }

        return $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);
    }

    public function export(string $format = 'csv')
    {
        $config = $this->selectedTableConfig();

        $fileName = $config['fileName'] ?? 'pools.csv';
        $headersRow = $config['csvHeaders'] ?? ['Tube code'];
        $rowBuilder = $config['csvRow'] ?? null;

        $query = $this->buildBaseQueryForSelectedTable();

        $headers = $headersRow;
        array_splice($headers, 1, 0, 'Sub-project');

        $rows = $query->get()->flatMap(function ($tube) use ($rowBuilder) {
            if (! is_callable($rowBuilder)) {
                return [];
            }

            $rows = $rowBuilder($tube);

            $subProjectCode = data_get($tube, 'tubes_content.subProjectAssignment.subProject.code') ?? 'N/A';

            if (is_array($rows) && isset($rows[0]) && is_array($rows[0])) {
                $built = [];
                foreach ($rows as $row) {
                    array_splice($row, 1, 0, $subProjectCode);
                    $built[] = $row;
                }

                return $built;
            }

            array_splice($rows, 1, 0, $subProjectCode);

            return [$rows];
        });

        return $this->exportTable(Str::replaceLast('.csv', '', $fileName), $headers, $rows, $format);
    }

    public function render()
    {
        $service = app(TubesService::class);

        $additionalData = $service->assign();

        $poolTubes = $this->buildBaseQueryForSelectedTable()->paginate($this->perPage, pageName: 'articles-page');

        // Get available tube codes and content codes for datalists (edit mode)
        $tubeCodes = $this->isGuestMode() ? [] : Tubes::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $animalCodes = $this->isGuestMode() ? [] : AnimalSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $humanCodes = $this->isGuestMode() ? [] : HumanSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $environmentCodes = $this->isGuestMode() ? [] : EnvironmentSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $parasiteCodes = $this->isGuestMode() ? [] : ParasiteSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $nucleicCodes = $this->isGuestMode() ? [] : NucleicAcids::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $cultureCodes = $this->isGuestMode() ? [] : Cultures::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $poolCodes = $this->isGuestMode() ? [] : Pools::where('projects_id', $this->projectId)->pluck('code')->toArray();

        $viewData = array_merge($additionalData, [
            'poolTubes' => $poolTubes,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'originFilters' => $this->originFilters,
            'tubeCodes' => $tubeCodes,
            'animalCodes' => $animalCodes,
            'humanCodes' => $humanCodes,
            'environmentCodes' => $environmentCodes,
            'parasiteCodes' => $parasiteCodes,
            'nucleicCodes' => $nucleicCodes,
            'cultureCodes' => $cultureCodes,
            'poolCodes' => $poolCodes,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $this->canEdit(),
            'tableConfig' => $this->selectedTableConfig(),
        ]);

        return view('livewire.pools-index', $viewData);
    }

    public function selectedTableConfig(): array
    {
        $dateYmd = static fn ($value): string => $value ? (string) Carbon::parse($value)->format('Y-m-d') : 'N/A';
        $linkToPool = function (?string $code): string {
            if (! $code) {
                return '<span class="text-gray-500">N/A</span>';
            }

            if ($this->isGuestMode()) {
                return e($code);
            }

            return '<a class="text-blue-600 hover:text-blue-800 transition-colors duration-200" href="/samples/pools/'.urlencode($code).'">'.e($code).'</a>';
        };

        $poolTubeBaseFilters = [
            fn (Builder $q, self $c) => filled($c->originFilters['tubeCode'] ?? null)
                ? $q->where('code', 'like', '%'.$c->originFilters['tubeCode'].'%')
                : $q,
            fn (Builder $q, self $c) => filled($c->originFilters['poolCode'] ?? null)
                ? $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->where('code', 'like', '%'.$c->originFilters['poolCode'].'%'))
                : $q,
            fn (Builder $q, self $c) => filled($c->originFilters['subProjectCode'] ?? null)
                ? $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('subProjectAssignment.subProject', fn (Builder $subProjectQuery) => $subProjectQuery->where('code', 'like', '%'.$c->originFilters['subProjectCode'].'%')))
                : $q,
            fn (Builder $q, self $c) => (($c->originFilters['pooledStart'] ?? null) || ($c->originFilters['pooledEnd'] ?? null))
                ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c) {
                    $start = $c->originFilters['pooledStart'] ?? null;
                    $end = $c->originFilters['pooledEnd'] ?? null;

                    if ($start && $end) {
                        $sq->whereBetween('date_pooled', [$start, $end]);
                    } elseif ($start) {
                        $sq->where('date_pooled', '>=', $start);
                    } else {
                        $sq->where('date_pooled', '<=', $end);
                    }
                })
                : $q,
            fn (Builder $q, self $c) => filled($c->originFilters['laboratory'] ?? null)
                ? $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('laboratories', fn (Builder $lq) => $lq->where('name', 'like', '%'.$c->originFilters['laboratory'].'%')))
                : $q,
            fn (Builder $q, self $c) => filled($c->originFilters['createdBy'] ?? null)
                ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c) {
                    $search = (string) $c->originFilters['createdBy'];
                    $sq->whereHas('people', fn (Builder $pq) => $pq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$search.'%'));
                })
                : $q,
        ];

        $poolContentsCommonFilters = [
            fn (Builder $q, self $c) => filled($c->originFilters['poolContentType'] ?? null)
                ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c) {
                    $contentType = strtolower(trim((string) $c->originFilters['poolContentType']));
                    $typeMap = [
                        'human' => HumanSamples::class,
                        'animal' => AnimalSamples::class,
                        'environment' => EnvironmentSamples::class,
                        'environmental' => EnvironmentSamples::class,
                        'parasite' => ParasiteSamples::class,
                        'nucleic' => NucleicAcids::class,
                        'culture' => Cultures::class,
                        'pool' => Pools::class,
                    ];

                    $matchType = null;
                    foreach ($typeMap as $needle => $fqcn) {
                        if (str_contains($contentType, $needle)) {
                            $matchType = $fqcn;
                            break;
                        }
                    }

                    $sq->whereHas('pool_contents', function (Builder $pcq) use ($matchType, $contentType) {
                        if ($matchType) {
                            $pcq->where('samples_type', $matchType);

                            return;
                        }

                        $pcq->where('samples_type', 'like', '%'.$contentType.'%');
                    });
                })
                : $q,

            fn (Builder $q, self $c) => filled($c->originFilters['poolContentCode'] ?? null)
                ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c) {
                    $code = (string) $c->originFilters['poolContentCode'];
                    $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [
                        HumanSamples::class,
                        AnimalSamples::class,
                        EnvironmentSamples::class,
                        ParasiteSamples::class,
                        NucleicAcids::class,
                        Cultures::class,
                        Pools::class,
                    ], fn (Builder $sampleQ) => $sampleQ->where('code', 'like', '%'.$code.'%')));
                })
                : $q,
        ];

        $poolContentsDetailsFilterAll = fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
            ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c) {
                $search = (string) $c->originFilters['contentSearch'];
                $isSqlite = DB::connection()->getDriverName() === 'sqlite';

                $sq->whereHas('pool_contents', function (Builder $pcq) use ($search, $isSqlite) {
                    $pcq->where(function (Builder $w) use ($search, $isSqlite) {
                        $w->whereHasMorph('samples', [HumanSamples::class], function (Builder $hq) use ($search) {
                            $hq->where('code', 'like', '%'.$search.'%')
                                ->orWhereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$search.'%'))
                                ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                        })
                            ->orWhereHasMorph('samples', [AnimalSamples::class], function (Builder $aq) use ($search) {
                                $aq->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('animals', fn (Builder $anq) => $anq->where('code', 'like', '%'.$search.'%'))
                                    ->orWhereHas('animals.animal_species', fn (Builder $asq) => $asq->where('name_common', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sample_types', fn (Builder $stq) => $stq->where('name', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            })
                            ->orWhereHasMorph('samples', [EnvironmentSamples::class], function (Builder $eq) use ($search) {
                                $eq->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('environment_sample_types', fn (Builder $tq) => $tq->where('name', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            })
                            ->orWhereHasMorph('samples', [ParasiteSamples::class], function (Builder $pq) use ($search) {
                                $pq->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'))
                                    ->orWhereHas('parasites', fn (Builder $parq) => $parq->where('sex', 'like', '%'.$search.'%')->orWhere('stage', 'like', '%'.$search.'%'))
                                    ->orWhereHas('parasites', fn (Builder $parq) => $parq->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'))));
                            })
                            ->orWhereHasMorph('samples', [NucleicAcids::class], function (Builder $nq) use ($search, $isSqlite) {
                                if ($isSqlite) {
                                    $nq->where(function (Builder $nw) use ($search) {
                                        $nw->where('code', 'like', '%'.$search.'%')
                                            ->orWhere('type', 'like', '%'.$search.'%')
                                            ->orWhereHasMorph('nucleic_content', [
                                                HumanSamples::class,
                                                AnimalSamples::class,
                                                EnvironmentSamples::class,
                                                ParasiteSamples::class,
                                                Cultures::class,
                                            ], fn (Builder $cq) => $cq->where('code', 'like', '%'.$search.'%'));
                                    });

                                    return;
                                }

                                $nq->where('code', 'like', '%'.$search.'%')
                                    ->orWhere('type', 'like', '%'.$search.'%');
                            })
                            ->orWhereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('code', 'like', '%'.$search.'%')->orWhere('medium', 'like', '%'.$search.'%')->orWhere('type', 'like', '%'.$search.'%'))
                            ->orWhereHasMorph('samples', [Pools::class], fn (Builder $plq) => $plq->where('code', 'like', '%'.$search.'%'));
                    });
                });
            })
            : $q;

        $poolDerivedFilters = function (string $type) use ($poolTubeBaseFilters): array {
            return array_merge($poolTubeBaseFilters, [
                fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                    ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c, $type) {
                        $search = (string) $c->originFilters['contentSearch'];
                        $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [$type], function (Builder $tq) use ($search, $type) {
                            $tq->where(function (Builder $w) use ($search, $type) {
                                if ($type === HumanSamples::class) {
                                    $w->where('code', 'like', '%'.$search.'%')
                                        ->orWhereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$search.'%'))
                                        ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));

                                    return;
                                }

                                if ($type === AnimalSamples::class) {
                                    $w->where('code', 'like', '%'.$search.'%')
                                        ->orWhereHas('animals', fn (Builder $anq) => $anq->where('code', 'like', '%'.$search.'%'))
                                        ->orWhereHas('animals.animal_species', fn (Builder $asq) => $asq->where('name_common', 'like', '%'.$search.'%'))
                                        ->orWhereHas('sample_types', fn (Builder $stq) => $stq->where('name', 'like', '%'.$search.'%'))
                                        ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));

                                    return;
                                }

                                if ($type === EnvironmentSamples::class) {
                                    $w->where('code', 'like', '%'.$search.'%')
                                        ->orWhereHas('environment_sample_types', fn (Builder $tq) => $tq->where('name', 'like', '%'.$search.'%'))
                                        ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));

                                    return;
                                }

                                if ($type === ParasiteSamples::class) {
                                    $w->where('code', 'like', '%'.$search.'%')
                                        ->orWhereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'))
                                        ->orWhereHas('parasites', fn (Builder $parq) => $parq->where('sex', 'like', '%'.$search.'%')->orWhere('stage', 'like', '%'.$search.'%'))
                                        ->orWhereHas('parasites', fn (Builder $parq) => $parq->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'))));

                                    return;
                                }

                                $w->where('code', 'like', '%'.$search.'%');
                            });
                        }));
                    })
                    : $q,
                fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                    ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c, $type) {
                        $start = $c->originFilters['collectedStart'] ?? null;
                        $end = $c->originFilters['collectedEnd'] ?? null;

                        $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [$type], function (Builder $tq) use ($start, $end, $type) {
                            $applyRange = function (Builder $q, string $column) use ($start, $end): void {
                                if ($start && $end) {
                                    $q->whereBetween($column, [$start, $end]);
                                } elseif ($start) {
                                    $q->where($column, '>=', $start);
                                } else {
                                    $q->where($column, '<=', $end);
                                }
                            };

                            if ($type === ParasiteSamples::class) {
                                $tq->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));

                                return;
                            }

                            $applyRange($tq, 'date_collected');
                        }));
                    })
                    : $q,
            ]);
        };

        // ----- config per table -----
        return match ($this->selectedTable) {
            'pools_table' => [
                'tableId' => 'pools_table',
                'subtitle' => 'pool tubes (all pools)',
                'with' => [],
                'extraColumns' => [
                    [
                        'label' => 'Pool code',
                        'html' => true,
                        'sortKey' => 'pool_code',
                        'value' => fn (Tubes $t): string => $linkToPool(data_get($t, 'tubes_content.code')),
                        'filterModel' => 'originFilters.poolCode',
                    ],
                    [
                        'key' => 'date_pooled',
                        'label' => 'Date pooled',
                        'sortKey' => 'pool_date',
                        'value' => fn (Tubes $t): string => $dateYmd(data_get($t, 'tubes_content.date_pooled')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.pooledStart',
                        'filterModelEnd' => 'originFilters.pooledEnd',
                    ],
                    [
                        'label' => 'Location',
                        'sortKey' => 'pool_laboratory',
                        'valuePath' => 'tubes_content.laboratories.name',
                        'filterModel' => 'originFilters.laboratory',
                    ],
                    [
                        'label' => 'Created by',
                        'sortKey' => 'pool_created_by',
                        'value' => fn (Tubes $t): string => trim((data_get($t, 'tubes_content.people.title') ?? '').' '.(data_get($t, 'tubes_content.people.first_name') ?? '').' '.(data_get($t, 'tubes_content.people.last_name') ?? '')) ?: 'N/A',
                        'filterModel' => 'originFilters.createdBy',
                    ],
                    [
                        'label' => 'Contents type(s)',
                        'value' => fn (Tubes $t): string => $this->poolContentTypesLabel($t),
                        'filterModel' => 'originFilters.poolContentType',
                    ],
                    [
                        'label' => 'Contents count',
                        'value' => fn (Tubes $t): string => (string) $this->poolContents($t)->count(),
                    ],
                    [
                        'key' => 'contents_codes',
                        'label' => 'Contents code(s)',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolContentsCodesHtml($t, 'pool-'.$t->id.'-codes'),
                        'filterModel' => 'originFilters.poolContentCode',
                    ],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->poolCollectedRange($t),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolContentsDetailsCombinedHtml($t, 'pool-'.$t->id.'-details'),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => array_merge($poolTubeBaseFilters, $poolContentsCommonFilters, [
                    $poolContentsDetailsFilterAll,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c): void {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;

                            $applyRange = function (Builder $q, string $column) use ($start, $end): void {
                                if ($start && $end) {
                                    $q->whereBetween($column, [$start, $end]);
                                } elseif ($start) {
                                    $q->where($column, '>=', $start);
                                } else {
                                    $q->where($column, '<=', $end);
                                }
                            };

                            $sq->whereHas('pool_contents', function (Builder $pcq) use ($applyRange): void {
                                $pcq->where(function (Builder $w) use ($applyRange): void {
                                    $w->whereHasMorph('samples', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $s) use ($applyRange): void {
                                        $applyRange($s, 'date_collected');
                                    })
                                        ->orWhereHasMorph('samples', [ParasiteSamples::class], function (Builder $s) use ($applyRange): void {
                                            $s->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                                        })
                                        ->orWhereHasMorph('samples', [NucleicAcids::class], function (Builder $s) use ($applyRange): void {
                                            $s->whereHasMorph('nucleic_content', [
                                                HumanSamples::class,
                                                AnimalSamples::class,
                                                EnvironmentSamples::class,
                                                ParasiteSamples::class,
                                            ], function (Builder $cq) use ($applyRange): void {
                                                $applyRange($cq, 'date_collected');
                                            });
                                        })
                                        ->orWhereHasMorph('samples', [Cultures::class], function (Builder $s) use ($applyRange): void {
                                            $s->whereHasMorph('cultures_content', [
                                                HumanSamples::class,
                                                AnimalSamples::class,
                                                EnvironmentSamples::class,
                                                ParasiteSamples::class,
                                            ], function (Builder $cq) use ($applyRange): void {
                                                $applyRange($cq, 'date_collected');
                                            });
                                        });
                                });
                            });
                        })
                        : $q,
                ]),
                'fileName' => 'pools.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Date pooled', 'Location', 'Created by', 'Contents type(s)', 'Contents count', 'Contents code(s)', 'Collected date(s)'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');

                    return [
                        data_get($t, 'code', 'N/A'),
                        data_get($pool, 'code', 'N/A'),
                        $dateYmd(data_get($pool, 'date_pooled')),
                        data_get($pool, 'laboratories.name', 'N/A'),
                        trim((data_get($pool, 'people.title') ?? '').' '.(data_get($pool, 'people.first_name') ?? '').' '.(data_get($pool, 'people.last_name') ?? '')) ?: 'N/A',
                        $this->poolContentTypesLabel($t),
                        $this->poolContents($t)->count(),
                        collect($this->poolContentCodeLinkItems($t))->pluck('label')->implode('; '),
                        $this->poolCollectedRange($t),
                    ];
                },
            ],

            'pool_human_table' => [
                'tableId' => 'pool_human_table',
                'subtitle' => 'pools (human content)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->where('samples_type', HumanSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->poolCollectedRangeByType($t, HumanSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, HumanSamples::class, 'pool-human-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => $poolDerivedFilters(HumanSamples::class),
                'fileName' => 'pools.human.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Human sample code', 'Patient code', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === HumanSamples::class)->values();

                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base, $dateYmd): array {
                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.humans.code') ?? 'N/A',
                            data_get($pc, 'samples.sampling_sites.name') ?? 'N/A',
                            $dateYmd(data_get($pc, 'samples.date_collected')),
                        ]);
                    })->all();
                },
            ],

            'pool_animal_table' => [
                'tableId' => 'pool_animal_table',
                'subtitle' => 'pools (animal content)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->where('samples_type', AnimalSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->poolCollectedRangeByType($t, AnimalSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, AnimalSamples::class, 'pool-animal-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => $poolDerivedFilters(AnimalSamples::class),
                'fileName' => 'pools.animal.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Animal sample code', 'Animal code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === AnimalSamples::class)->values();
                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base, $dateYmd): array {
                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.animals.code') ?? 'N/A',
                            data_get($pc, 'samples.animals.animal_species.name_common') ?? 'N/A',
                            data_get($pc, 'samples.sample_types.name') ?? 'N/A',
                            data_get($pc, 'samples.sampling_sites.name') ?? 'N/A',
                            $dateYmd(data_get($pc, 'samples.date_collected')),
                        ]);
                    })->all();
                },
            ],

            'pool_environment_table' => [
                'tableId' => 'pool_environment_table',
                'subtitle' => 'pools (environment content)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->where('samples_type', EnvironmentSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->poolCollectedRangeByType($t, EnvironmentSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, EnvironmentSamples::class, 'pool-env-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => $poolDerivedFilters(EnvironmentSamples::class),
                'fileName' => 'pools.environment.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Environment code', 'Environment type', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === EnvironmentSamples::class)->values();
                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base, $dateYmd): array {
                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.environment_sample_types.name') ?? 'N/A',
                            data_get($pc, 'samples.sampling_sites.name') ?? 'N/A',
                            $dateYmd(data_get($pc, 'samples.date_collected')),
                        ]);
                    })->all();
                },
            ],

            'pool_parasite_table' => [
                'tableId' => 'pool_parasite_table',
                'subtitle' => 'pools (parasite content)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->where('samples_type', ParasiteSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, ParasiteSamples::class, 'pool-par-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => $poolDerivedFilters(ParasiteSamples::class),
                'fileName' => 'pools.parasite.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Parasite sample code', 'Tick species', 'Tick sex', 'Tick stage', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === ParasiteSamples::class)->values();
                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base, $dateYmd): array {
                        $dateCollected = data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected');

                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A',
                            data_get($pc, 'samples.parasites.sex') ?? 'N/A',
                            data_get($pc, 'samples.parasites.stage') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? 'N/A',
                            $dateYmd($dateCollected),
                        ]);
                    })->all();
                },
            ],

            'pool_parasite_human_table' => [
                'tableId' => 'pool_parasite_human_table',
                'subtitle' => 'pools (parasite content from human samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', ParasiteSamples::class)
                    ->whereHasMorph('samples', [ParasiteSamples::class], fn (Builder $pq) => $pq->whereHas('parasites', fn (Builder $parq) => $parq->where('parasites_origin_type', HumanSamples::class)))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                                && (string) data_get($pc, 'samples.parasites.parasites_origin_type') === HumanSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolParasiteOriginSubtableHtml($t, HumanSamples::class, 'pool-par-human-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    ...$poolTubeBaseFilters,
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c): void {
                            $search = (string) $c->originFilters['contentSearch'];
                            $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                                ->where('samples_type', ParasiteSamples::class)
                                ->whereHasMorph('samples', [ParasiteSamples::class], function (Builder $pq) use ($search): void {
                                    $pq->whereHas('parasites', fn (Builder $parq) => $parq->where('parasites_origin_type', HumanSamples::class))
                                        ->where(function (Builder $w) use ($search): void {
                                            $w->where('code', 'like', '%'.$search.'%')
                                                ->orWhereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'))
                                                ->orWhereHas('parasites', fn (Builder $parq) => $parq->where('sex', 'like', '%'.$search.'%')->orWhere('stage', 'like', '%'.$search.'%'))
                                                ->orWhereHas('parasites.parasites_origin', fn (Builder $oq) => $oq->where('code', 'like', '%'.$search.'%')->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%')));
                                        });
                                })
                            );
                        })
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c): void {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;

                            $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                                ->where('samples_type', ParasiteSamples::class)
                                ->whereHasMorph('samples', [ParasiteSamples::class], function (Builder $pq) use ($start, $end): void {
                                    $pq->whereHas('parasites', fn (Builder $parq) => $parq->where('parasites_origin_type', HumanSamples::class))
                                        ->whereHas('parasites.parasites_origin', function (Builder $oq) use ($start, $end): void {
                                            if ($start && $end) {
                                                $oq->whereBetween('date_collected', [$start, $end]);
                                            } elseif ($start) {
                                                $oq->where('date_collected', '>=', $start);
                                            } else {
                                                $oq->where('date_collected', '<=', $end);
                                            }
                                        });
                                })
                            );
                        })
                        : $q,
                ],
                'fileName' => 'pools.parasite.human.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Parasite sample code', 'Tick species', 'Tick sex', 'Tick stage', 'Human sample code', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(function ($pc) {
                        return (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                            && (string) data_get($pc, 'samples.parasites.parasites_origin_type') === HumanSamples::class;
                    })->values();

                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];
                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base, $dateYmd): array {
                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A',
                            data_get($pc, 'samples.parasites.sex') ?? 'N/A',
                            data_get($pc, 'samples.parasites.stage') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasites_origin.code') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? 'N/A',
                            $dateYmd(data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected')),
                        ]);
                    })->all();
                },
            ],

            'pool_parasite_animal_table' => [
                'tableId' => 'pool_parasite_animal_table',
                'subtitle' => 'pools (parasite content from animal samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', ParasiteSamples::class)
                    ->whereHasMorph('samples', [ParasiteSamples::class], fn (Builder $pq) => $pq->whereHas('parasites', fn (Builder $parq) => $parq->where('parasites_origin_type', AnimalSamples::class)))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                                && (string) data_get($pc, 'samples.parasites.parasites_origin_type') === AnimalSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolParasiteOriginSubtableHtml($t, AnimalSamples::class, 'pool-par-animal-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    ...$poolTubeBaseFilters,
                ],
                'fileName' => 'pools.parasite.animal.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Parasite sample code', 'Tick species', 'Tick sex', 'Tick stage', 'Animal sample code', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(function ($pc) {
                        return (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                            && (string) data_get($pc, 'samples.parasites.parasites_origin_type') === AnimalSamples::class;
                    })->values();

                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];
                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base, $dateYmd): array {
                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A',
                            data_get($pc, 'samples.parasites.sex') ?? 'N/A',
                            data_get($pc, 'samples.parasites.stage') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasites_origin.code') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? 'N/A',
                            $dateYmd(data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected')),
                        ]);
                    })->all();
                },
            ],

            'pool_parasite_environment_table' => [
                'tableId' => 'pool_parasite_environment_table',
                'subtitle' => 'pools (parasite content from environment samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', ParasiteSamples::class)
                    ->whereHasMorph('samples', [ParasiteSamples::class], fn (Builder $pq) => $pq->whereHas('parasites', fn (Builder $parq) => $parq->where('parasites_origin_type', EnvironmentSamples::class)))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                                && (string) data_get($pc, 'samples.parasites.parasites_origin_type') === EnvironmentSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolParasiteOriginSubtableHtml($t, EnvironmentSamples::class, 'pool-par-env-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    ...$poolTubeBaseFilters,
                ],
                'fileName' => 'pools.parasite.environment.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Parasite sample code', 'Tick species', 'Tick sex', 'Tick stage', 'Environment sample code', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t) use ($dateYmd): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(function ($pc) {
                        return (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                            && (string) data_get($pc, 'samples.parasites.parasites_origin_type') === EnvironmentSamples::class;
                    })->values();

                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];
                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base, $dateYmd): array {
                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A',
                            data_get($pc, 'samples.parasites.sex') ?? 'N/A',
                            data_get($pc, 'samples.parasites.stage') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasites_origin.code') ?? 'N/A',
                            data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? 'N/A',
                            $dateYmd(data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected')),
                        ]);
                    })->all();
                },
            ],

            'pool_nucleic_table' => [
                'tableId' => 'pool_nucleic_table',
                'subtitle' => 'pools (nucleic acid content)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->where('samples_type', NucleicAcids::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, NucleicAcids::class, 'pool-nuc-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => array_merge($poolTubeBaseFilters, [
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [NucleicAcids::class], function (Builder $nq) use ($c) {
                            $search = (string) $c->originFilters['contentSearch'];
                            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

                            if ($isSqlite) {
                                $nq->where(function (Builder $w) use ($search) {
                                    $w->where('code', 'like', '%'.$search.'%')
                                        ->orWhere('type', 'like', '%'.$search.'%')
                                        ->orWhereHasMorph('nucleic_content', [
                                            HumanSamples::class,
                                            AnimalSamples::class,
                                            EnvironmentSamples::class,
                                            ParasiteSamples::class,
                                            Cultures::class,
                                        ], fn (Builder $cq) => $cq->where('code', 'like', '%'.$search.'%'));
                                });

                                return;
                            }

                            $nq->where('code', 'like', '%'.$search.'%')
                                ->orWhere('type', 'like', '%'.$search.'%');
                        })))
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c): void {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;

                            $applyRange = function (Builder $q, string $column) use ($start, $end): void {
                                if ($start && $end) {
                                    $q->whereBetween($column, [$start, $end]);
                                } elseif ($start) {
                                    $q->where($column, '>=', $start);
                                } else {
                                    $q->where($column, '<=', $end);
                                }
                            };

                            $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [NucleicAcids::class], function (Builder $nq) use ($applyRange): void {
                                $nq->where(function (Builder $w) use ($applyRange): void {
                                    $w->whereHasMorph('nucleic_content', [
                                        HumanSamples::class,
                                        AnimalSamples::class,
                                        EnvironmentSamples::class,
                                        ParasiteSamples::class,
                                    ], fn (Builder $cq) => $applyRange($cq, 'date_collected'))
                                        ->orWhereHasMorph('nucleic_content', [Cultures::class], function (Builder $cq) use ($applyRange): void {
                                            $cq->whereHasMorph('cultures_content', [
                                                HumanSamples::class,
                                                AnimalSamples::class,
                                                EnvironmentSamples::class,
                                                ParasiteSamples::class,
                                            ], fn (Builder $ccq) => $applyRange($ccq, 'date_collected'));
                                        });
                                });
                            }));
                        })
                        : $q,
                ]),
                'fileName' => 'pools.nucleic.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Content type', 'Content code', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class)->values();
                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base): array {
                        $nucleicCode = data_get($pc, 'samples.code') ?? 'N/A';
                        $nucleicType = data_get($pc, 'samples.type') ?? 'N/A';
                        $contentType = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');
                        $contentCode = data_get($pc, 'samples.nucleic_content.code') ?? data_get($pc, 'samples.nucleic_content.cultures_content.code') ?? 'N/A';
                        $primary = $this->poolContentPrimarySiteAndDate($pc);
                        $site = $primary['site'] ?? 'N/A';
                        $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                        return array_merge($base, [
                            $nucleicCode,
                            $nucleicType,
                            $contentType ? class_basename($contentType) : 'N/A',
                            $contentCode,
                            $site,
                            $date,
                        ]);
                    })->all();
                },
            ],

            'pool_nucleic_human_table' => [
                'tableId' => 'pool_nucleic_human_table',
                'subtitle' => 'pools (nucleic acids extracted from human samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', NucleicAcids::class)
                    ->whereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('nucleic_content_type', HumanSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                                && (string) data_get($pc, 'samples.nucleic_content_type') === HumanSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, NucleicAcids::class, 'pool-nuc-human-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.nucleic_content_type') === HumanSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    ...$poolTubeBaseFilters,
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c): void {
                            $search = (string) $c->originFilters['contentSearch'];

                            $sq->whereHas('pool_contents', function (Builder $pcq) use ($search): void {
                                $pcq->where('samples_type', NucleicAcids::class)
                                    ->whereHasMorph('samples', [NucleicAcids::class], function (Builder $nq) use ($search): void {
                                        $nq->where('nucleic_content_type', HumanSamples::class)
                                            ->where(function (Builder $w) use ($search): void {
                                                $w->where('code', 'like', '%'.$search.'%')
                                                    ->orWhere('type', 'like', '%'.$search.'%')
                                                    ->orWhereHas('nucleic_content', function (Builder $cq) use ($search): void {
                                                        $cq->where('code', 'like', '%'.$search.'%')
                                                            ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                                                    });
                                            });
                                    });
                            });
                        })
                        : $q,
                ],
                'fileName' => 'pools.nucleic.human.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Human sample code', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                        && (string) data_get($pc, 'samples.nucleic_content_type') === HumanSamples::class)->values();
                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base): array {
                        $primary = $this->poolContentPrimarySiteAndDate($pc);
                        $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                        return array_merge($base, [
                            data_get($pc, 'samples.code') ?? 'N/A',
                            data_get($pc, 'samples.type') ?? 'N/A',
                            data_get($pc, 'samples.nucleic_content.code') ?? 'N/A',
                            $primary['site'] ?? 'N/A',
                            $date,
                        ]);
                    })->all();
                },
            ],

            'pool_nucleic_animal_table' => [
                'tableId' => 'pool_nucleic_animal_table',
                'subtitle' => 'pools (nucleic acids extracted from animal samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', NucleicAcids::class)
                    ->whereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('nucleic_content_type', AnimalSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                                && (string) data_get($pc, 'samples.nucleic_content_type') === AnimalSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, NucleicAcids::class, 'pool-nuc-animal-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.nucleic_content_type') === AnimalSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.nucleic.animal.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Animal sample code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForNucleicByContentType($t, AnimalSamples::class),
            ],

            'pool_nucleic_environment_table' => [
                'tableId' => 'pool_nucleic_environment_table',
                'subtitle' => 'pools (nucleic acids extracted from environment samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', NucleicAcids::class)
                    ->whereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('nucleic_content_type', EnvironmentSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                                && (string) data_get($pc, 'samples.nucleic_content_type') === EnvironmentSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, NucleicAcids::class, 'pool-nuc-env-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.nucleic_content_type') === EnvironmentSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.nucleic.environment.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Environment sample code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForNucleicByContentType($t, EnvironmentSamples::class),
            ],

            'pool_nucleic_parasite_table' => [
                'tableId' => 'pool_nucleic_parasite_table',
                'subtitle' => 'pools (nucleic acids extracted from parasite samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', NucleicAcids::class)
                    ->whereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('nucleic_content_type', ParasiteSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                                && (string) data_get($pc, 'samples.nucleic_content_type') === ParasiteSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, NucleicAcids::class, 'pool-nuc-par-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.nucleic_content_type') === ParasiteSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.nucleic.parasite.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Parasite sample code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForNucleicByContentType($t, ParasiteSamples::class),
            ],

            'pool_nucleic_culture_table' => [
                'tableId' => 'pool_nucleic_culture_table',
                'subtitle' => 'pools (nucleic acids extracted from cultures)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', NucleicAcids::class)
                    ->whereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('nucleic_content_type', Cultures::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                                && (string) data_get($pc, 'samples.nucleic_content_type') === Cultures::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, NucleicAcids::class, 'pool-nuc-cul-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.nucleic_content_type') === Cultures::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.nucleic.culture.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Culture code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForNucleicByContentType($t, Cultures::class),
            ],

            'pool_nucleic_pool_table' => [
                'tableId' => 'pool_nucleic_pool_table',
                'subtitle' => 'pools (nucleic acids extracted from pools)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', NucleicAcids::class)
                    ->whereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('nucleic_content_type', Pools::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
                                && (string) data_get($pc, 'samples.nucleic_content_type') === Pools::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, NucleicAcids::class, 'pool-nuc-pool-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.nucleic_content_type') === Pools::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.nucleic.pool.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Pool code (origin)', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForNucleicByContentType($t, Pools::class),
            ],

            'pool_culture_table' => [
                'tableId' => 'pool_culture_table',
                'subtitle' => 'pools (culture content)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->where('samples_type', Cultures::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, Cultures::class, 'pool-cul-'.$t->id),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => array_merge($poolTubeBaseFilters, [
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [Cultures::class], function (Builder $cq) use ($c) {
                            $search = (string) $c->originFilters['contentSearch'];
                            $cq->where(function (Builder $w) use ($search): void {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhere('medium', 'like', '%'.$search.'%')
                                    ->orWhere('type', 'like', '%'.$search.'%')
                                    ->orWhereHasMorph('cultures_content', [
                                        HumanSamples::class,
                                        AnimalSamples::class,
                                        EnvironmentSamples::class,
                                        ParasiteSamples::class,
                                    ], fn (Builder $oq) => $oq->where('code', 'like', '%'.$search.'%'));
                            });
                        })))
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('tubes_content', [Pools::class], function (Builder $sq) use ($c): void {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;

                            $applyRange = function (Builder $q, string $column) use ($start, $end): void {
                                if ($start && $end) {
                                    $q->whereBetween($column, [$start, $end]);
                                } elseif ($start) {
                                    $q->where($column, '>=', $start);
                                } else {
                                    $q->where($column, '<=', $end);
                                }
                            };

                            $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [Cultures::class], function (Builder $cq) use ($applyRange): void {
                                $cq->whereHasMorph('cultures_content', [
                                    HumanSamples::class,
                                    AnimalSamples::class,
                                    EnvironmentSamples::class,
                                    ParasiteSamples::class,
                                ], fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                            }));
                        })
                        : $q,
                ]),
                'fileName' => 'pools.culture.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Culture code', 'Medium', 'Culture type', 'Content type', 'Content code', 'Sampling site', 'Date collected'],
                'csvRow' => function (Tubes $t): array {
                    $pool = data_get($t, 'tubes_content');
                    $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class)->values();
                    $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
                    }

                    return $contents->map(function ($pc) use ($base): array {
                        $cultureCode = (string) (data_get($pc, 'samples.code') ?? 'N/A');
                        $medium = (string) (data_get($pc, 'samples.medium') ?? 'N/A');
                        $cultureType = (string) (data_get($pc, 'samples.type') ?? 'N/A');
                        $contentType = (string) (data_get($pc, 'samples.cultures_content_type') ?? '');
                        $contentCode = (string) (data_get($pc, 'samples.cultures_content.code') ?? 'N/A');
                        $primary = $this->poolContentPrimarySiteAndDate($pc);
                        $site = $primary['site'] ?? 'N/A';
                        $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                        return array_merge($base, [
                            $cultureCode,
                            $medium,
                            $cultureType,
                            $contentType ? class_basename($contentType) : 'N/A',
                            $contentCode,
                            $site,
                            $date,
                        ]);
                    })->all();
                },
            ],

            'pool_culture_human_table' => [
                'tableId' => 'pool_culture_human_table',
                'subtitle' => 'pools (culture content from human samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', Cultures::class)
                    ->whereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('cultures_content_type', HumanSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class
                                && (string) data_get($pc, 'samples.cultures_content_type') === HumanSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, Cultures::class, 'pool-cul-human-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.cultures_content_type') === HumanSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.culture.human.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Culture code', 'Medium', 'Culture type', 'Human sample code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForCultureByContentType($t, HumanSamples::class),
            ],

            'pool_culture_animal_table' => [
                'tableId' => 'pool_culture_animal_table',
                'subtitle' => 'pools (culture content from animal samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', Cultures::class)
                    ->whereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('cultures_content_type', AnimalSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class
                                && (string) data_get($pc, 'samples.cultures_content_type') === AnimalSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, Cultures::class, 'pool-cul-animal-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.cultures_content_type') === AnimalSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.culture.animal.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Culture code', 'Medium', 'Culture type', 'Animal sample code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForCultureByContentType($t, AnimalSamples::class),
            ],

            'pool_culture_environment_table' => [
                'tableId' => 'pool_culture_environment_table',
                'subtitle' => 'pools (culture content from environment samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', Cultures::class)
                    ->whereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('cultures_content_type', EnvironmentSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class
                                && (string) data_get($pc, 'samples.cultures_content_type') === EnvironmentSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, Cultures::class, 'pool-cul-env-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.cultures_content_type') === EnvironmentSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.culture.environment.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Culture code', 'Medium', 'Culture type', 'Environment sample code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForCultureByContentType($t, EnvironmentSamples::class),
            ],

            'pool_culture_parasite_table' => [
                'tableId' => 'pool_culture_parasite_table',
                'subtitle' => 'pools (culture content from parasite samples)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', Cultures::class)
                    ->whereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('cultures_content_type', ParasiteSamples::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class
                                && (string) data_get($pc, 'samples.cultures_content_type') === ParasiteSamples::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, Cultures::class, 'pool-cul-par-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.cultures_content_type') === ParasiteSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.culture.parasite.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Culture code', 'Medium', 'Culture type', 'Parasite sample code', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForCultureByContentType($t, ParasiteSamples::class),
            ],

            'pool_culture_pool_table' => [
                'tableId' => 'pool_culture_pool_table',
                'subtitle' => 'pools (culture content from pools)',
                'with' => [],
                'scope' => fn (Builder $q) => $q->whereHasMorph('tubes_content', [Pools::class], fn (Builder $sq) => $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq
                    ->where('samples_type', Cultures::class)
                    ->whereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('cultures_content_type', Pools::class))
                )),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Tubes $t): string => $this->collectedRangeForPoolContents($this->poolContents($t)->filter(
                            fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class
                                && (string) data_get($pc, 'samples.cultures_content_type') === Pools::class
                        )),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Tubes $t): string => $this->poolDerivedSubtableHtml($t, Cultures::class, 'pool-cul-pool-'.$t->id, fn ($pc) => (string) data_get($pc, 'samples.cultures_content_type') === Pools::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [...$poolTubeBaseFilters],
                'fileName' => 'pools.culture.pool.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Culture code', 'Medium', 'Culture type', 'Pool code (origin)', 'Sampling site', 'Date collected'],
                'csvRow' => fn (Tubes $t) => $this->csvRowsForCultureByContentType($t, Pools::class),
            ],

            default => [
                'tableId' => 'pools_table',
                'subtitle' => 'pool tubes',
                'with' => [],
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $linkToPool(data_get($t, 'tubes_content.code')), 'filterModel' => 'originFilters.poolCode'],
                ],
                'filters' => $poolTubeBaseFilters,
                'fileName' => 'pools.csv',
                'csvHeaders' => ['Tube code', 'Pool code'],
                'csvRow' => fn (Tubes $t) => [data_get($t, 'code', 'N/A'), data_get($t, 'tubes_content.code', 'N/A')],
            ],
        };
    }

    private function poolContents(Tubes $tube)
    {
        return collect(data_get($tube, 'tubes_content.pool_contents', []))
            ->filter(fn ($pc) => data_get($pc, 'samples') !== null)
            ->values();
    }

    private function collectedRangeForPoolContents($contents): string
    {
        $contents = collect($contents)->values();

        $dates = $contents
            ->map(function ($pc) {
                $primary = $this->poolContentPrimarySiteAndDate($pc);

                return $primary['date'] ?? null;
            })
            ->filter()
            ->map(fn ($d) => (string) Carbon::parse($d)->format('Y-m-d'))
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return 'N/A';
        }

        $min = $dates->first();
        $max = $dates->last();

        return $min === $max ? $min : ($min.' to '.$max);
    }

    private function poolParasiteOriginSubtableHtml(Tubes $tube, string $originType, string $id): string
    {
        $contents = $this->poolContents($tube)->filter(function ($pc) use ($originType) {
            return (string) data_get($pc, 'samples_type') === ParasiteSamples::class
                && (string) data_get($pc, 'samples.parasites.parasites_origin_type') === $originType;
        })->values();

        if ($contents->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $headers = match ($originType) {
            HumanSamples::class => ['Parasite sample', 'Tick species', 'Sex', 'Stage', 'Human sample', 'Sampling site', 'Date collected'],
            AnimalSamples::class => ['Parasite sample', 'Tick species', 'Sex', 'Stage', 'Animal sample', 'Sampling site', 'Date collected'],
            EnvironmentSamples::class => ['Parasite sample', 'Tick species', 'Sex', 'Stage', 'Environment sample', 'Sampling site', 'Date collected'],
            default => ['Parasite sample', 'Tick species', 'Sex', 'Stage', 'Origin sample', 'Sampling site', 'Date collected'],
        };

        $rows = $contents->map(function ($pc) use ($originType): array {
            $parasiteSampleCode = (string) (data_get($pc, 'samples.code') ?? 'N/A');
            $originCode = (string) (data_get($pc, 'samples.parasites.parasites_origin.code') ?? 'N/A');
            $originLink = $originCode !== 'N/A' ? $this->poolContentLinkHtml($originType, $originCode) : '<span class="text-gray-500">N/A</span>';
            $dateCollected = data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected');

            return [
                $this->poolContentLinkHtml(ParasiteSamples::class, $parasiteSampleCode),
                e((string) (data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A')),
                e((string) (data_get($pc, 'samples.parasites.sex') ?? 'N/A')),
                e((string) (data_get($pc, 'samples.parasites.stage') ?? 'N/A')),
                $originLink,
                e((string) (data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? 'N/A')),
                e($dateCollected ? (string) Carbon::parse($dateCollected)->format('Y-m-d') : 'N/A'),
            ];
        });

        $limit = 5;
        $first = $rows->take($limit);
        $rest = $rows->slice($limit);

        $renderRows = function ($rows): string {
            return collect($rows)->map(function ($cells) {
                $cells = collect($cells)->map(fn ($c) => '<td class="px-3 py-2 text-sm text-gray-700 whitespace-nowrap">'.($c ?: '<span class="text-gray-500">N/A</span>').'</td>')->implode('');

                return '<tr class="border-t border-gray-100">'.$cells.'</tr>';
            })->implode('');
        };

        $thead = collect($headers)->map(fn ($h) => '<th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider bg-gray-50">'.e($h).'</th>')->implode('');

        $firstHtml = $renderRows($first);
        $restHtml = $rest->isEmpty() ? '' : $renderRows($rest);

        $toggle = '';
        if (! $rest->isEmpty()) {
            $toggle = <<<'HTML'
<button type="button" class="text-xs text-blue-600 hover:text-blue-800" x-on:click="open = !open">
  <span x-show="!open">Show all</span>
  <span x-show="open" x-cloak>Show less</span>
</button>
HTML;
        }

        $restBlock = $rest->isEmpty() ? '' : '<tbody x-show="open" x-cloak>'.$restHtml.'</tbody>';

        return <<<HTML
<div x-data="{ open: false }" class="space-y-2">
  <div class="overflow-x-auto border border-gray-200 rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
      <thead><tr>{$thead}</tr></thead>
      <tbody>{$firstHtml}</tbody>
      {$restBlock}
    </table>
  </div>
  {$toggle}
</div>
HTML;
    }

    private function csvRowsForNucleicByContentType(Tubes $t, string $contentType): array
    {
        $pool = data_get($t, 'tubes_content');
        $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class
            && (string) data_get($pc, 'samples.nucleic_content_type') === $contentType)->values();
        $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

        if ($contents->isEmpty()) {
            return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
        }

        return $contents->map(function ($pc) use ($base, $contentType): array {
            $primary = $this->poolContentPrimarySiteAndDate($pc);
            $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

            $originCode = match ($contentType) {
                Cultures::class => (string) (data_get($pc, 'samples.nucleic_content.code') ?? 'N/A'),
                Pools::class => (string) (data_get($pc, 'samples.nucleic_content.code') ?? 'N/A'),
                default => (string) (data_get($pc, 'samples.nucleic_content.code') ?? 'N/A'),
            };

            return array_merge($base, [
                data_get($pc, 'samples.code') ?? 'N/A',
                data_get($pc, 'samples.type') ?? 'N/A',
                $originCode ?: 'N/A',
                $primary['site'] ?? 'N/A',
                $date,
            ]);
        })->all();
    }

    private function csvRowsForCultureByContentType(Tubes $t, string $contentType): array
    {
        $pool = data_get($t, 'tubes_content');
        $contents = $this->poolContents($t)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === Cultures::class
            && (string) data_get($pc, 'samples.cultures_content_type') === $contentType)->values();
        $base = [data_get($t, 'code', 'N/A'), data_get($pool, 'code', 'N/A')];

        if ($contents->isEmpty()) {
            return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'])];
        }

        return $contents->map(function ($pc) use ($base): array {
            $primary = $this->poolContentPrimarySiteAndDate($pc);
            $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

            return array_merge($base, [
                data_get($pc, 'samples.code') ?? 'N/A',
                data_get($pc, 'samples.medium') ?? 'N/A',
                data_get($pc, 'samples.type') ?? 'N/A',
                data_get($pc, 'samples.cultures_content.code') ?? 'N/A',
                $primary['site'] ?? 'N/A',
                $date,
            ]);
        })->all();
    }

    /**
     * @return array<int, array{label: string, type: string, href: string}>
     */
    private function poolContentCodeLinkItems(Tubes $tube): array
    {
        return $this->poolContents($tube)
            ->map(function ($pc) {
                $type = (string) (data_get($pc, 'samples_type') ?? '');
                $code = (string) (data_get($pc, 'samples.code') ?? '');
                $label = $code ?: 'N/A';
                $href = '#';

                if ($type && $code) {
                    $path = match ($type) {
                        HumanSamples::class => '/samples/humans/'.$code,
                        AnimalSamples::class => '/samples/animals/'.$code,
                        EnvironmentSamples::class => '/samples/environment/'.$code,
                        ParasiteSamples::class => '/samples/parasites/'.$code,
                        NucleicAcids::class => '/samples/nucleic/'.$code,
                        Cultures::class => '/samples/cultures/'.$code,
                        Pools::class => '/samples/pools/'.$code,
                        default => '#',
                    };

                    $href = $path;
                }

                return [
                    'type' => $type ?: 'N/A',
                    'label' => $label,
                    'href' => $href,
                ];
            })
            ->all();
    }

    private function poolContentTypesLabel(Tubes $tube): string
    {
        $types = $this->poolContents($tube)
            ->map(fn ($pc) => (string) (data_get($pc, 'samples_type') ?? ''))
            ->filter()
            ->map(fn (string $fqcn) => class_basename($fqcn))
            ->unique()
            ->values();

        return $types->isEmpty() ? 'N/A' : $types->implode('; ');
    }

    private function poolCollectedRange(Tubes $tube): string
    {
        $dates = $this->poolContents($tube)
            ->map(function ($pc) {
                $type = (string) (data_get($pc, 'samples_type') ?? '');

                if ($type === ParasiteSamples::class) {
                    return data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected');
                }

                if ($type === NucleicAcids::class || $type === Cultures::class) {
                    $primary = $this->poolContentPrimarySiteAndDate($pc);

                    return $primary['date'] ?? null;
                }

                return data_get($pc, 'samples.date_collected');
            })
            ->filter()
            ->map(fn ($d) => (string) Carbon::parse($d)->format('Y-m-d'))
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return 'N/A';
        }

        $min = $dates->first();
        $max = $dates->last();

        return $min === $max ? $min : ($min.' to '.$max);
    }

    private function poolCollectedRangeByType(Tubes $tube, string $type): string
    {
        $dates = $this->poolContents($tube)
            ->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $type)
            ->map(function ($pc) use ($type) {
                if ($type === ParasiteSamples::class) {
                    return data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected');
                }

                if ($type === NucleicAcids::class || $type === Cultures::class) {
                    $primary = $this->poolContentPrimarySiteAndDate($pc);

                    return $primary['date'] ?? null;
                }

                return data_get($pc, 'samples.date_collected');
            })
            ->filter()
            ->map(fn ($d) => (string) Carbon::parse($d)->format('Y-m-d'))
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return 'N/A';
        }

        $min = $dates->first();
        $max = $dates->last();

        return $min === $max ? $min : ($min.' to '.$max);
    }

    private function poolContentsCodesHtml(Tubes $tube, string $id): string
    {
        $items = collect($this->poolContentCodeLinkItems($tube));
        if ($items->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $limit = 5;
        $first = $items->take($limit);
        $rest = $items->slice($limit);

        $renderItems = function ($coll): string {
            return $coll->map(function ($item) {
                $href = e((string) ($item['href'] ?? '#'));
                $label = e((string) ($item['label'] ?? 'N/A'));
                if ($this->isGuestMode() || $href === '#') {
                    return $label;
                }

                return '<a class="text-blue-600 hover:text-blue-800 transition-colors duration-200" href="'.$href.'">'.$label.'</a>';
            })->implode(', ');
        };

        $firstHtml = $renderItems($first);
        if ($rest->isEmpty()) {
            return $firstHtml;
        }

        $restHtml = $renderItems($rest);

        return <<<HTML
<div x-data="{ open: false }" class="space-y-1">
  <div>{$firstHtml}<span x-show="open" x-cloak>, {$restHtml}</span></div>
  <button type="button" class="text-xs text-blue-600 hover:text-blue-800" x-on:click="open = !open">
    <span x-show="!open">Show all</span>
    <span x-show="open" x-cloak>Show less</span>
  </button>
</div>
HTML;
    }

    private function poolDerivedSubtableHtml(Tubes $tube, string $type, string $id, ?callable $includePoolContent = null): string
    {
        $contents = $this->poolContents($tube)
            ->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $type)
            ->when($includePoolContent !== null, fn ($c) => $c->filter($includePoolContent))
            ->values();
        if ($contents->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $rows = $contents->map(function ($pc) use ($type): array {
            if ($type === HumanSamples::class) {
                return [
                    $this->poolContentLinkHtml(HumanSamples::class, (string) (data_get($pc, 'samples.code') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.humans.code') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.sampling_sites.name') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.date_collected') ? Carbon::parse(data_get($pc, 'samples.date_collected'))->format('Y-m-d') : 'N/A')),
                ];
            }

            if ($type === AnimalSamples::class) {
                return [
                    $this->poolContentLinkHtml(AnimalSamples::class, (string) (data_get($pc, 'samples.code') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.animals.code') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.animals.animal_species.name_common') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.sample_types.name') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.sampling_sites.name') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.date_collected') ? Carbon::parse(data_get($pc, 'samples.date_collected'))->format('Y-m-d') : 'N/A')),
                ];
            }

            if ($type === EnvironmentSamples::class) {
                return [
                    $this->poolContentLinkHtml(EnvironmentSamples::class, (string) (data_get($pc, 'samples.code') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.environment_sample_types.name') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.sampling_sites.name') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.date_collected') ? Carbon::parse(data_get($pc, 'samples.date_collected'))->format('Y-m-d') : 'N/A')),
                ];
            }

            if ($type === ParasiteSamples::class) {
                $dateCollected = data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected');

                return [
                    $this->poolContentLinkHtml(ParasiteSamples::class, (string) (data_get($pc, 'samples.code') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.parasites.sex') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.parasites.stage') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? 'N/A')),
                    e((string) ($dateCollected ? Carbon::parse($dateCollected)->format('Y-m-d') : 'N/A')),
                ];
            }

            if ($type === Cultures::class) {
                $cultureCode = (string) (data_get($pc, 'samples.code') ?? 'N/A');
                $contentType = (string) (data_get($pc, 'samples.cultures_content_type') ?? '');
                $contentCode = (string) (data_get($pc, 'samples.cultures_content.code') ?? 'N/A');
                $primary = $this->poolContentPrimarySiteAndDate($pc);
                $site = $primary['site'] ?? 'N/A';
                $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                return [
                    $this->poolContentLinkHtml(Cultures::class, $cultureCode),
                    e((string) (data_get($pc, 'samples.medium') ?? 'N/A')),
                    e((string) (data_get($pc, 'samples.type') ?? 'N/A')),
                    e($contentType ? class_basename($contentType) : 'N/A'),
                    $contentType && $contentCode !== 'N/A'
                        ? $this->poolContentLinkHtml($contentType, $contentCode)
                        : '<span class="text-gray-500">N/A</span>',
                    e((string) $site),
                    e((string) $date),
                ];
            }

            if ($type === NucleicAcids::class) {
                $nucleicCode = (string) (data_get($pc, 'samples.code') ?? 'N/A');
                $contentType = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');
                $contentCode = data_get($pc, 'samples.nucleic_content.code') ?? data_get($pc, 'samples.nucleic_content.cultures_content.code') ?? 'N/A';
                $primary = $this->poolContentPrimarySiteAndDate($pc);
                $site = $primary['site'] ?? 'N/A';
                $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                return [
                    $this->poolContentLinkHtml(NucleicAcids::class, $nucleicCode),
                    e((string) (data_get($pc, 'samples.type') ?? 'N/A')),
                    e($contentType ? class_basename($contentType) : 'N/A'),
                    $contentType && $contentCode !== 'N/A'
                        ? $this->poolContentLinkHtml($contentType, (string) $contentCode)
                        : '<span class="text-gray-500">N/A</span>',
                    e((string) $site),
                    e((string) $date),
                ];
            }

            return [e((string) (data_get($pc, 'samples.code') ?? 'N/A'))];
        });

        $headers = match ($type) {
            HumanSamples::class => ['Human sample', 'Patient', 'Sampling site', 'Date collected'],
            AnimalSamples::class => ['Animal sample', 'Animal', 'Species', 'Sample type', 'Sampling site', 'Date collected'],
            EnvironmentSamples::class => ['Environment', 'Type', 'Sampling site', 'Date collected'],
            ParasiteSamples::class => ['Parasite sample', 'Tick species', 'Sex', 'Stage', 'Sampling site', 'Date collected'],
            Cultures::class => ['Culture', 'Medium', 'Culture type', 'Content type', 'Content code', 'Sampling site', 'Date collected'],
            NucleicAcids::class => ['Nucleic', 'Nucleic type', 'Content type', 'Content code', 'Sampling site', 'Date collected'],
            default => ['Code'],
        };

        $limit = 5;
        $first = $rows->take($limit);
        $rest = $rows->slice($limit);

        $renderRows = function ($rows): string {
            return collect($rows)->map(function ($cells) {
                $cells = collect($cells)->map(fn ($c) => '<td class="px-3 py-2 text-sm text-gray-700 whitespace-nowrap">'.($c ?: '<span class="text-gray-500">N/A</span>').'</td>')->implode('');

                return '<tr class="border-t border-gray-100">'.$cells.'</tr>';
            })->implode('');
        };

        $thead = collect($headers)->map(fn ($h) => '<th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider bg-gray-50">'.e($h).'</th>')->implode('');

        $firstHtml = $renderRows($first);
        $restHtml = $rest->isEmpty() ? '' : $renderRows($rest);

        $toggle = '';
        if (! $rest->isEmpty()) {
            $toggle = <<<'HTML'
<button type="button" class="text-xs text-blue-600 hover:text-blue-800" x-on:click="open = !open">
  <span x-show="!open">Show all</span>
  <span x-show="open" x-cloak>Show less</span>
</button>
HTML;
        }

        $restBlock = $rest->isEmpty() ? '' : '<tbody x-show="open" x-cloak>'.$restHtml.'</tbody>';

        return <<<HTML
<div x-data="{ open: false }" class="space-y-2">
  <div class="overflow-x-auto border border-gray-200 rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
      <thead><tr>{$thead}</tr></thead>
      <tbody>{$firstHtml}</tbody>
      {$restBlock}
    </table>
  </div>
  {$toggle}
</div>
HTML;
    }

    private function poolContentsDetailsCombinedHtml(Tubes $tube, string $id): string
    {
        $contents = $this->poolContents($tube);
        if ($contents->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $groups = $contents->groupBy(fn ($pc) => (string) (data_get($pc, 'samples_type') ?? 'N/A'));

        $html = '<div class="space-y-2">';
        foreach ($groups as $type => $items) {
            $label = e(class_basename($type));
            $codes = $items->map(fn ($pc) => [
                'type' => $type,
                'code' => (string) (data_get($pc, 'samples.code') ?? 'N/A'),
            ]);

            $links = $codes->map(function ($c) {
                $type = $c['type'] ?? null;
                $code = $c['code'] ?? null;

                return $type && $code ? $this->poolContentLinkHtml((string) $type, (string) $code) : '<span class="text-gray-500">N/A</span>';
            });

            $html .= '<div><div class="text-xs font-semibold text-gray-700">'.$label.' ('.$items->count().')</div>';
            $html .= '<div class="text-sm">'.$this->poolCodesExpandableHtml($links, $id.'-'.$label).'</div></div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function poolCodesExpandableHtml($links, string $id): string
    {
        $links = collect($links)->filter()->values();
        if ($links->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $limit = 5;
        $first = $links->take($limit)->implode(', ');
        $rest = $links->slice($limit)->implode(', ');

        if (! $rest) {
            return $first;
        }

        return <<<HTML
<div x-data="{ open: false }" class="space-y-1">
  <div>{$first}<span x-show="open" x-cloak>, {$rest}</span></div>
  <button type="button" class="text-xs text-blue-600 hover:text-blue-800" x-on:click="open = !open">
    <span x-show="!open">Show all</span>
    <span x-show="open" x-cloak>Show less</span>
  </button>
</div>
HTML;
    }

    private function poolContentLinkHtml(string $type, string $code): string
    {
        $path = match ($type) {
            HumanSamples::class => 'humans',
            AnimalSamples::class => 'animals',
            EnvironmentSamples::class => 'environment',
            ParasiteSamples::class => 'parasites',
            NucleicAcids::class => 'nucleic',
            Cultures::class => 'cultures',
            Pools::class => 'pools',
            default => null,
        };

        if (! $path || ! $code) {
            return '<span class="text-gray-500">N/A</span>';
        }

        if ($this->isGuestMode()) {
            return e($code);
        }

        $href = '/samples/'.$path.'/'.urlencode($code);

        return '<a class="text-blue-600 hover:text-blue-800 transition-colors duration-200" href="'.e($href).'">'.e($code).'</a>';
    }

    /**
     * @return array{site: ?string, date: ?string}
     */
    private function poolContentPrimarySiteAndDate(mixed $pc): array
    {
        $type = (string) (data_get($pc, 'samples_type') ?? '');

        if ($type === HumanSamples::class || $type === AnimalSamples::class || $type === EnvironmentSamples::class) {
            return [
                'site' => data_get($pc, 'samples.sampling_sites.name'),
                'date' => data_get($pc, 'samples.date_collected'),
            ];
        }

        if ($type === ParasiteSamples::class) {
            return [
                'site' => data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name'),
                'date' => data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected'),
            ];
        }

        if ($type === NucleicAcids::class) {
            $contentType = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');

            return match ($contentType) {
                HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class => [
                    'site' => data_get($pc, 'samples.nucleic_content.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.nucleic_content.date_collected'),
                ],
                ParasiteSamples::class => [
                    'site' => data_get($pc, 'samples.nucleic_content.parasites.parasites_origin.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.nucleic_content.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.nucleic_content.date_collected'),
                ],
                Cultures::class => [
                    'site' => data_get($pc, 'samples.nucleic_content.cultures_content.sampling_sites.name')
                        ?? data_get($pc, 'samples.nucleic_content.cultures_content.parasites.parasites_origin.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.nucleic_content.cultures_content.date_collected')
                        ?? data_get($pc, 'samples.nucleic_content.cultures_content.parasites.parasites_origin.date_collected'),
                ],
                default => ['site' => null, 'date' => null],
            };
        }

        if ($type === Cultures::class) {
            $contentType = (string) (data_get($pc, 'samples.cultures_content_type') ?? '');

            return match ($contentType) {
                HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class => [
                    'site' => data_get($pc, 'samples.cultures_content.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.cultures_content.date_collected'),
                ],
                ParasiteSamples::class => [
                    'site' => data_get($pc, 'samples.cultures_content.parasites.parasites_origin.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.cultures_content.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.cultures_content.date_collected'),
                ],
                default => ['site' => null, 'date' => null],
            };
        }

        return ['site' => null, 'date' => null];
    }
}
