<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\TubePositionsForm;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\TubePositions;
use App\Services\BoxesService;
use App\Services\TubesService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class TubePositionsIndex extends PlainComponent
{
    use ExportsTable;
    use WithColumnSorting;
    use WithPagination;

    public ?int $projectId = null;

    /**
     * @return array<string, string|callable>
     */
    protected function sortMap(): array
    {
        return [
            'tube_code' => fn ($q, $dir) => $this->orderByRelation($q, ['tubes'], 'code', $dir),
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'box_code' => fn ($q, $dir) => $this->orderByRelation($q, ['boxes'], 'code', $dir),
            'content_type' => fn ($q, $dir) => $this->orderByRelation($q, ['boxes'], 'content_type', $dir),
            'position_x' => 'position_x',
            'position_y' => 'position_y',
            'date_moved' => 'date_moved',
            'moved_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'last_name', $dir),
            'reason' => 'reason',
        ];
    }

    public TubePositionsForm $form;

    public bool $isEditing = false;

    public string $selectedTable = 'tube_positions_table';

    public array $filters = [
        'tubeCode' => null,
        'boxCode' => null,
        'contentType' => null,
        'positionX' => null,
        'positionY' => null,
        'dateMovedStart' => null,
        'dateMovedEnd' => null,
        'scientist' => null,
        'reason' => null,
        'subProjectCode' => null,

        // Box storage / location filters (available in all tables)
        'location' => null,
        'subLocation' => null,
        'facility' => null,
    ];

    public array $originFilters = [];

    public array $selectedTubePositions = [];

    public function mount(): void
    {
        $this->projectId = session('selected_project_id');
        $this->form->projectId = $this->projectId ?? 0;
    }

    public function updatedSelectedTable(): void
    {
        $this->originFilters = [];
        $this->resetPage('articles-page');
    }

    public function updating($field): void
    {
        // Only reset pagination when filters change. Toggling a row checkbox
        // (selectedTubePositions) or other UI state must not jump back to page 1.
        if (is_string($field) && (str_starts_with($field, 'filters') || str_starts_with($field, 'originFilters'))) {
            $this->resetPage('articles-page');
        }
    }

    public function toggleEditMode(): void
    {
        if (! $this->userCanWriteModule('tube_positions')) {
            $this->dispatchSwal(false, 'You do not have permission to edit tube positions.');

            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    protected function dispatchSwal(bool $ok, string $message, string $successTitle = 'Success', string $errorTitle = 'Error'): void
    {
        $this->dispatch(
            'swal',
            icon: $ok ? 'success' : 'error',
            title: $ok ? $successTitle : $errorTitle,
            text: $message
        );
    }

    public function updateField(int $tubePositionId, string $field, mixed $value): void
    {
        $tubePosition = TubePositions::find($tubePositionId);
        if (! $tubePosition || ! $this->userCanMutateOwnedRecord((int) $tubePosition->people_id, 'tube_positions')) {
            $this->dispatchSwal(false, 'You can only edit records you registered.');

            return;
        }

        $result = $this->form->updateField($tubePositionId, $field, $value);
        $this->dispatchSwal((bool) ($result['ok'] ?? false), (string) ($result['message'] ?? 'Unknown result'));
    }

    public function delete(int $tubePositionId): void
    {
        $tubePosition = TubePositions::find($tubePositionId);
        if (! $tubePosition) {
            $this->dispatchSwal(false, 'Tube position not found.');

            return;
        }
        if (! $this->userCanMutateOwnedRecord((int) $tubePosition->people_id, 'tube_positions')) {
            $this->dispatchSwal(false, 'You can only delete records you registered.');

            return;
        }

        try {
            $tubePosition->delete();
        } catch (\Throwable $e) {
            $this->dispatchSwal(false, 'Delete failed.');

            return;
        }

        $this->form->refreshData();
        $this->dispatchSwal(true, 'Tube position deleted successfully!');
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedTubePositions)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            $this->dispatchSwal(false, 'Please select at least one tube position.', 'Success', 'No selection');

            return;
        }

        $tubePositions = TubePositions::query()->whereIn('id', $selectedIds->all())->get();
        $deleted = 0;

        foreach ($tubePositions as $tubePosition) {
            if (! $this->userCanMutateOwnedRecord((int) $tubePosition->people_id, 'tube_positions')) {
                continue;
            }

            $tubePosition->delete();
            $deleted++;
        }

        $this->selectedTubePositions = [];
        $this->form->refreshData();
        $this->dispatchSwal(
            $deleted > 0,
            $deleted > 0 ? "{$deleted} selected tube position(s) deleted successfully!" : 'No selected tube positions could be deleted.',
            'Success',
            'Nothing deleted'
        );
    }

    protected function applyCommonFilters(Builder $query): Builder
    {
        $tubeCode = $this->filters['tubeCode'] ?? null;
        if ($tubeCode) {
            $query->whereHas('tubes', function (Builder $q) use ($tubeCode) {
                $q->where('code', 'like', '%'.$tubeCode.'%')
                    ->orWhere('alias_code', 'like', '%'.$tubeCode.'%');
            });
        }

        $boxCode = $this->filters['boxCode'] ?? null;
        if ($boxCode) {
            $query->whereHas('boxes', fn (Builder $q) => $q->where('code', 'like', '%'.$boxCode.'%'));
        }

        $contentType = $this->filters['contentType'] ?? null;
        if ($contentType) {
            $query->whereHas('boxes', fn (Builder $q) => $q->where('content_type', 'like', '%'.$contentType.'%'));
        }

        $positionX = $this->filters['positionX'] ?? null;
        if ($positionX) {
            $query->where('position_x', 'like', '%'.$positionX.'%');
        }

        $positionY = $this->filters['positionY'] ?? null;
        if ($positionY) {
            $query->where('position_y', 'like', '%'.$positionY.'%');
        }

        $startDate = $this->filters['dateMovedStart'] ?? null;
        $endDate = $this->filters['dateMovedEnd'] ?? null;
        if ($startDate && $endDate) {
            $query->whereBetween('date_moved', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('date_moved', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('date_moved', '<=', $endDate);
        }

        $scientist = $this->filters['scientist'] ?? null;
        if ($scientist) {
            $query->whereHas('people', function (Builder $q) use ($scientist) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$scientist.'%');
            });
        }

        $reason = $this->filters['reason'] ?? null;
        if ($reason) {
            $query->where('reason', 'like', '%'.$reason.'%');
        }

        $subProjectCode = $this->filters['subProjectCode'] ?? null;
        if ($subProjectCode) {
            $query->whereHas('subProjectAssignment.subProject', function (Builder $q) use ($subProjectCode) {
                $q->where('code', 'like', '%'.$subProjectCode.'%');
            });
        }

        $location = $this->filters['location'] ?? null;
        if ($location) {
            $query->whereHas('boxes.latest_box_position.locations', function (Builder $q) use ($location) {
                $q->where('name', 'like', '%'.$location.'%')
                    ->orWhere('room', 'like', '%'.$location.'%');
            });
        }

        $subLocation = $this->filters['subLocation'] ?? null;
        if ($subLocation) {
            $query->whereHas('boxes.latest_box_position', fn (Builder $q) => $q->where('sublocation', 'like', '%'.$subLocation.'%'));
        }

        $facility = $this->filters['facility'] ?? null;
        if ($facility) {
            $query->whereHas('boxes.latest_box_position.locations.laboratories', function (Builder $q) use ($facility) {
                $q->where('name', 'like', '%'.$facility.'%')
                    ->orWhereHas('countries', fn (Builder $cq) => $cq->where('name', 'like', '%'.$facility.'%'));
            });
        }

        return $query;
    }

    protected function buildBaseQueryForSelectedTable(): Builder
    {
        $config = $this->selectedTableConfig();

        $query = TubePositions::query();

        $with = array_values(array_unique(array_merge([
            'tubes',
            'tubes.projects',
            'tubes.tubes_content',
            'tubes.tubes_content.projects',
            'boxes',
            'boxes.projects',
            'boxes.latest_box_position',
            'boxes.latest_box_position.locations',
            'boxes.latest_box_position.locations.laboratories',
            'boxes.latest_box_position.locations.laboratories.countries',
            'people',
            'subProjectAssignment.subProject',
        ], $config['with'] ?? [])));

        $query->with($with);

        if (
            $this->selectedTable === 'tube_positions_pool_table'
            || str_starts_with($this->selectedTable, 'tube_positions_pool_')
        ) {
            $query->with([
                'tubes.tubes_content.pool_contents.samples' => function (MorphTo $morphTo): void {
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
                                    HumanSamples::class => [
                                        'sampling_sites',
                                    ],
                                    AnimalSamples::class => [
                                        'sampling_sites',
                                    ],
                                    EnvironmentSamples::class => [
                                        'sampling_sites',
                                    ],
                                    ParasiteSamples::class => [
                                        'parasites.parasites_origin',
                                        'parasites.parasites_origin.sampling_sites',
                                    ],
                                    Cultures::class => [
                                        'cultures_content' => function (MorphTo $morphTo): void {
                                            $morphTo->morphWith([
                                                HumanSamples::class => [
                                                    'sampling_sites',
                                                ],
                                                AnimalSamples::class => [
                                                    'sampling_sites',
                                                ],
                                                EnvironmentSamples::class => [
                                                    'sampling_sites',
                                                ],
                                                ParasiteSamples::class => [
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
                                    HumanSamples::class => [
                                        'sampling_sites',
                                    ],
                                    AnimalSamples::class => [
                                        'sampling_sites',
                                    ],
                                    EnvironmentSamples::class => [
                                        'sampling_sites',
                                    ],
                                    ParasiteSamples::class => [
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

        if ($this->selectedTable === 'tube_positions_nucleic_pool_table') {
            $query->with([
                'tubes.tubes_content.nucleic_content.pool_contents.samples' => function (MorphTo $morphTo): void {
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
                                                    'parasites.parasites_origin',
                                                    'parasites.parasites_origin.sampling_sites',
                                                ],
                                            ]);
                                        },
                                    ],
                                ]);
                            },
                        ],
                        Cultures::class => [
                            'cultures_content' => function (MorphTo $morphTo): void {
                                $morphTo->morphWith([
                                    HumanSamples::class => [
                                        'sampling_sites',
                                    ],
                                    AnimalSamples::class => [
                                        'sampling_sites',
                                    ],
                                    EnvironmentSamples::class => [
                                        'sampling_sites',
                                    ],
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
            ]);
        }

        if ($this->selectedTable === 'tube_positions_culture_pool_table') {
            $query->with([
                'tubes.tubes_content.cultures_content.pool_contents.samples' => function (MorphTo $morphTo): void {
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
                                                    'parasites.parasites_origin',
                                                    'parasites.parasites_origin.sampling_sites',
                                                ],
                                            ]);
                                        },
                                    ],
                                ]);
                            },
                        ],
                    ]);
                },
            ]);
        }

        if (isset($config['scope']) && is_callable($config['scope'])) {
            $query = $config['scope']($query);
        }

        $query = $this->applyCommonFilters($query);

        if (isset($config['filters']) && is_callable($config['filters'])) {
            $query = $config['filters']($query);
        }

        return $this->applySorting($query, $this->sortMap(), ['date_moved', 'desc']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function storageExtraColumns(): array
    {
        return [
            [
                'label' => 'Location (room)',
                'value' => function (TubePositions $p): string {
                    $pos = data_get($p, 'boxes.latest_box_position');
                    $location = $pos?->locations;
                    if (! $location) {
                        return 'N/A';
                    }

                    return trim(($location->name ?? '').' ('.($location->room ?? '').')') ?: 'N/A';
                },
                'filterModel' => 'filters.location',
            ],
            [
                'label' => 'Sub-location',
                'value' => fn (TubePositions $p): string => (data_get($p, 'boxes.latest_box_position.sublocation')) ?: 'N/A',
                'filterModel' => 'filters.subLocation',
            ],
            [
                'label' => 'Facility (country)',
                'value' => function (TubePositions $p): string {
                    $pos = data_get($p, 'boxes.latest_box_position');
                    $lab = $pos?->locations?->laboratories;
                    if (! $lab) {
                        return 'N/A';
                    }

                    return trim(($lab->name ?? '').' ('.($lab->countries->name ?? '').')') ?: 'N/A';
                },
                'filterModel' => 'filters.facility',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function storageCsvValues(TubePositions $p): array
    {
        $pos = data_get($p, 'boxes.latest_box_position');
        $location = $pos?->locations;
        $lab = $location?->laboratories;

        return [
            $location ? trim(($location->name ?? '').' ('.($location->room ?? '').')') : 'N/A',
            $pos?->sublocation ?? 'N/A',
            $lab ? trim(($lab->name ?? '').' ('.($lab->countries->name ?? '').')') : 'N/A',
        ];
    }

    /**
     * Inject storage (box location) columns + CSV fields into any table config.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function withStorageColumns(array $config): array
    {
        $storageColumns = $this->storageExtraColumns();

        $existingLabels = collect($config['extraColumns'] ?? [])
            ->pluck('label')
            ->filter()
            ->values()
            ->all();

        foreach ($storageColumns as $column) {
            $label = (string) ($column['label'] ?? '');
            if ($label && ! in_array($label, $existingLabels, true)) {
                $config['extraColumns'][] = $column;
            }
        }

        $headers = $config['csvHeaders'] ?? null;
        $rowBuilder = $config['csvRow'] ?? null;

        if (is_array($headers) && is_callable($rowBuilder)) {
            $storageHeaders = ['Location (room)', 'Sub-location', 'Facility (country)'];
            $alreadyIncluded = count(array_intersect($storageHeaders, $headers)) === count($storageHeaders);

            if (! $alreadyIncluded) {
                $injectAt = array_search('Date moved', $headers, true);
                if ($injectAt === false) {
                    $injectAt = array_search('Moved by', $headers, true);
                    if ($injectAt !== false) {
                        $injectAt = max(0, (int) $injectAt - 1);
                    }
                }

                if ($injectAt === false) {
                    $headers = array_merge($headers, $storageHeaders);
                } else {
                    $headers = array_merge(
                        array_slice($headers, 0, (int) $injectAt + 1),
                        $storageHeaders,
                        array_slice($headers, (int) $injectAt + 1)
                    );
                }

                $config['csvHeaders'] = $headers;

                $config['csvRow'] = function (TubePositions $p) use ($rowBuilder, $headers) {
                    $rows = $rowBuilder($p);
                    $values = $this->storageCsvValues($p);

                    $injectAt = array_search('Date moved', $headers, true);
                    if ($injectAt === false) {
                        $injectAt = array_search('Moved by', $headers, true);
                        if ($injectAt !== false) {
                            $injectAt = max(0, (int) $injectAt - 1);
                        }
                    }

                    $inject = function (array $row) use ($values, $injectAt): array {
                        if ($injectAt === false) {
                            return array_merge($row, $values);
                        }

                        return array_merge(
                            array_slice($row, 0, (int) $injectAt + 1),
                            $values,
                            array_slice($row, (int) $injectAt + 1)
                        );
                    };

                    // Multi-row CSV: inject storage columns into each row.
                    if (is_array($rows) && isset($rows[0]) && is_array($rows[0])) {
                        return collect($rows)->map(fn (array $row) => $inject($row))->all();
                    }

                    // Single-row CSV.
                    return $inject((array) $rows);
                };
            }
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function withTubeAliasColumns(array $config): array
    {
        $headers = $config['csvHeaders'] ?? null;
        $rowBuilder = $config['csvRow'] ?? null;

        if (! is_array($headers) || ! is_callable($rowBuilder)) {
            return $config;
        }

        $tubeCodeIndex = null;
        foreach (['Tube code', 'Tube Code'] as $label) {
            $idx = array_search($label, $headers, true);
            if ($idx !== false) {
                $tubeCodeIndex = (int) $idx;
                break;
            }
        }

        if ($tubeCodeIndex === null) {
            return $config;
        }

        $aliasIndex = $tubeCodeIndex + 1;
        if (($headers[$aliasIndex] ?? null) === 'Tube alias') {
            return $config;
        }

        $headers = array_merge(
            array_slice($headers, 0, $aliasIndex),
            ['Tube alias'],
            array_slice($headers, $aliasIndex)
        );

        $config['csvHeaders'] = $headers;
        $config['csvRow'] = function (TubePositions $p) use ($rowBuilder, $aliasIndex) {
            $rows = $rowBuilder($p);
            $alias = data_get($p, 'tubes.alias_code') ?: 'N/A';

            $inject = function (array $row) use ($alias, $aliasIndex): array {
                array_splice($row, $aliasIndex, 0, $alias);

                return $row;
            };

            if (is_array($rows) && isset($rows[0]) && is_array($rows[0])) {
                return collect($rows)->map(fn (array $row) => $inject($row))->all();
            }

            return $inject((array) $rows);
        };

        return $config;
    }

    private function parasiteSpeciesLabel(TubePositions $position, string $speciesPath): string
    {
        $scientific = data_get($position, $speciesPath.'.name_scientific');
        if ($scientific) {
            return (string) $scientific;
        }

        $common = data_get($position, $speciesPath.'.name_common');
        if ($common) {
            return (string) $common;
        }

        return 'N/A';
    }

    private function parasiteSpeciesLabelHtml(TubePositions $position, string $speciesPath): string
    {
        $scientific = data_get($position, $speciesPath.'.name_scientific');
        if ($scientific) {
            return '<i>'.e((string) $scientific).'</i>';
        }

        $common = data_get($position, $speciesPath.'.name_common');
        if ($common) {
            return e((string) $common);
        }

        return 'N/A';
    }

    private function applyParasiteSpeciesNameFilter(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $speciesQuery) use ($term): void {
            $speciesQuery->where('name_scientific', 'like', '%'.$term.'%')
                ->orWhere('name_common', 'like', '%'.$term.'%');
        });
    }

    private function poolContentsTypesLabel(TubePositions $p): string
    {
        $contents = data_get($p, 'tubes.tubes_content.pool_contents');
        if (! $contents) {
            return 'N/A';
        }

        $types = collect($contents)
            ->map(fn ($pc) => data_get($pc, 'samples_type'))
            ->filter()
            ->map(function (string $fqcn): string {
                $short = class_basename($fqcn);

                return match ($short) {
                    'HumanSamples' => 'Human samples',
                    'AnimalSamples' => 'Animal samples',
                    'EnvironmentSamples' => 'Environmental samples',
                    'ParasiteSamples' => 'Parasite samples',
                    'NucleicAcids' => 'Nucleic acids',
                    default => $short,
                };
            })
            ->unique()
            ->values()
            ->all();

        return empty($types) ? 'N/A' : implode(', ', $types);
    }

    /**
     * @return array<int, array{label: string, href: string}>
     */
    private function poolContentsCodeLinkItems(TubePositions $p): array
    {
        $contents = data_get($p, 'tubes.tubes_content.pool_contents');
        if (! $contents) {
            return [];
        }

        return collect($contents)
            ->map(function ($pc): ?array {
                $code = data_get($pc, 'samples.code');
                $type = data_get($pc, 'samples_type');

                if (! $code || ! $type) {
                    return null;
                }

                $href = match ((string) $type) {
                    HumanSamples::class => '/samples/humans/'.rawurlencode((string) $code),
                    AnimalSamples::class => '/samples/animals/'.rawurlencode((string) $code),
                    EnvironmentSamples::class => '/samples/environment/'.rawurlencode((string) $code),
                    ParasiteSamples::class => '/samples/parasites/'.rawurlencode((string) $code),
                    NucleicAcids::class => '/samples/nucleic/'.rawurlencode((string) $code),
                    default => '#',
                };

                return [
                    'label' => (string) $code,
                    'href' => $href,
                ];
            })
            ->filter()
            ->unique('label')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{label: string, href: string}>  $items
     */
    private function expandableLinksHtml(string $id, array $items, int $limit = 5): string
    {
        if (empty($items)) {
            return 'N/A';
        }

        $safeId = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $id) ?: 'pool-items';

        $first = array_slice($items, 0, $limit);
        $rest = array_slice($items, $limit);

        $renderLinks = function (array $subset): string {
            return collect($subset)
                ->map(function (array $it): string {
                    $label = e((string) ($it['label'] ?? ''));
                    $href = e((string) ($it['href'] ?? '#'));

                    return '<a href="'.$href.'" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">'.$label.'</a>';
                })
                ->implode(', ');
        };

        $html = '<div x-data="{ open: false }" class="space-y-2">';
        $html .= '<div class="flex flex-col items-center gap-2">';
        $html .= '<div class="text-gray-900 font-medium">';
        $html .= '<span x-show="!open">'.$renderLinks($first).'</span>';
        $html .= '<span x-show="open" x-cloak>'.$renderLinks($items).'</span>';
        $html .= '</div>';

        if (! empty($rest)) {
            $count = count($items);
            $html .= '<button type="button" class="text-xs text-gray-600 hover:text-gray-800 underline" x-on:click="open = !open" aria-controls="'.$safeId.'">';
            $html .= '<span x-show="!open">Show all ('.$count.')</span>';
            $html .= '<span x-show="open" x-cloak>Hide</span>';
            $html .= '</button>';
        }

        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private function poolContentsCodesHtml(TubePositions $p, int $limit = 5): string
    {
        return $this->expandableLinksHtml(
            id: 'pool-'.$p->id.'-codes',
            items: $this->poolContentsCodeLinkItems($p),
            limit: $limit
        );
    }

    private function poolContentsCount(TubePositions $p): int
    {
        $contents = data_get($p, 'tubes.tubes_content.pool_contents');
        if (! $contents) {
            return 0;
        }

        return (int) collect($contents)->filter(fn ($pc) => data_get($pc, 'samples') !== null)->count();
    }

    private function poolContentsCodes(TubePositions $p, int $limit = 8): string
    {
        $contents = data_get($p, 'tubes.tubes_content.pool_contents');
        if (! $contents) {
            return 'N/A';
        }

        $codes = collect($contents)
            ->map(fn ($pc) => data_get($pc, 'samples.code'))
            ->filter()
            ->map(fn ($c) => (string) $c)
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return 'N/A';
        }

        if ($codes->count() <= $limit) {
            return $codes->implode(', ');
        }

        return $codes->take($limit)->implode(', ').' (+'.($codes->count() - $limit).' more)';
    }

    private function poolContentsCollectedRange(TubePositions $p): string
    {
        $contents = data_get($p, 'tubes.tubes_content.pool_contents');
        if (! $contents) {
            return 'N/A';
        }

        $dates = collect($contents)
            ->map(fn ($pc) => data_get($pc, 'samples.date_collected'))
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

    private function poolContentsCollectedRangeByType(TubePositions $p, string $samplesType): string
    {
        $dates = $this->poolContentsByType($p, $samplesType)
            ->map(fn ($pc) => data_get($pc, 'samples.date_collected'))
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

    /**
     * @return array{site: string|null, date: string|null}
     */
    private function poolContentPrimarySiteAndDate(mixed $pc): array
    {
        $samplesType = (string) (data_get($pc, 'samples_type') ?? '');

        if ($samplesType === HumanSamples::class || $samplesType === AnimalSamples::class || $samplesType === EnvironmentSamples::class) {
            return [
                'site' => data_get($pc, 'samples.sampling_sites.name'),
                'date' => data_get($pc, 'samples.date_collected'),
            ];
        }

        if ($samplesType === ParasiteSamples::class) {
            return [
                'site' => data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name'),
                'date' => data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected'),
            ];
        }

        if ($samplesType === Cultures::class) {
            $culturesContentType = (string) (data_get($pc, 'samples.cultures_content_type') ?? '');

            if (in_array($culturesContentType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
                return [
                    'site' => data_get($pc, 'samples.cultures_content.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.cultures_content.date_collected'),
                ];
            }

            if ($culturesContentType === ParasiteSamples::class) {
                return [
                    'site' => data_get($pc, 'samples.cultures_content.parasites.parasites_origin.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.cultures_content.parasites.parasites_origin.date_collected')
                        ?? data_get($pc, 'samples.cultures_content.date_collected'),
                ];
            }
        }

        if ($samplesType === NucleicAcids::class) {
            $nucleicContentType = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');

            if (in_array($nucleicContentType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
                return [
                    'site' => data_get($pc, 'samples.nucleic_content.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.nucleic_content.date_collected'),
                ];
            }

            if ($nucleicContentType === ParasiteSamples::class) {
                return [
                    'site' => data_get($pc, 'samples.nucleic_content.parasites.parasites_origin.sampling_sites.name'),
                    'date' => data_get($pc, 'samples.nucleic_content.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.nucleic_content.date_collected'),
                ];
            }

            if ($nucleicContentType === Cultures::class) {
                $culturesContentType = (string) (data_get($pc, 'samples.nucleic_content.cultures_content_type') ?? '');

                if (in_array($culturesContentType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
                    return [
                        'site' => data_get($pc, 'samples.nucleic_content.cultures_content.sampling_sites.name'),
                        'date' => data_get($pc, 'samples.nucleic_content.cultures_content.date_collected'),
                    ];
                }

                if ($culturesContentType === ParasiteSamples::class) {
                    return [
                        'site' => data_get($pc, 'samples.nucleic_content.cultures_content.parasites.parasites_origin.sampling_sites.name'),
                        'date' => data_get($pc, 'samples.nucleic_content.cultures_content.parasites.parasites_origin.date_collected')
                            ?? data_get($pc, 'samples.nucleic_content.cultures_content.date_collected'),
                    ];
                }
            }
        }

        return ['site' => null, 'date' => null];
    }

    private function poolContentsCollectedRangeForPoolModel(?Pools $pool): string
    {
        $contents = $pool?->pool_contents;
        if (! $contents) {
            return 'N/A';
        }

        $dates = collect($contents)
            ->filter(fn ($pc) => data_get($pc, 'samples') !== null)
            ->map(function ($pc): ?string {
                $primary = $this->poolContentPrimarySiteAndDate($pc);

                return $primary['date'];
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

    private function poolContentsDetailsTextForPoolModel(?Pools $pool): string
    {
        $contents = $pool?->pool_contents;
        if (! $contents) {
            return 'N/A';
        }

        $items = collect($contents)
            ->filter(fn ($pc) => data_get($pc, 'samples') !== null)
            ->map(function ($pc): string {
                $type = (string) (data_get($pc, 'samples_type') ?? 'N/A');
                $typeLabel = class_basename($type ?: 'N/A');
                $code = (string) (data_get($pc, 'samples.code') ?? 'N/A');
                $primary = $this->poolContentPrimarySiteAndDate($pc);
                $site = (string) ($primary['site'] ?? 'N/A');
                $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                return $typeLabel.': '.$code.' ('.$site.' | '.$date.')';
            })
            ->values();

        return $items->isEmpty() ? 'N/A' : $items->implode('; ');
    }

    private function poolContentsDetailsCombinedHtmlForPoolModel(?Pools $pool, string $id): string
    {
        $contents = $pool?->pool_contents;
        if (! $contents) {
            return 'N/A';
        }

        $rowsAll = collect($contents)
            ->filter(fn ($pc) => data_get($pc, 'samples') !== null)
            ->map(function ($pc): string {
                $samplesType = (string) (data_get($pc, 'samples_type') ?? '');
                $code = (string) (data_get($pc, 'samples.code') ?? '');
                $primary = $this->poolContentPrimarySiteAndDate($pc);
                $site = (string) ($primary['site'] ?? '');
                $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : '';

                $typeLabel = $samplesType ? str_replace('App\\Models\\', '', $samplesType) : 'N/A';

                $href = match ($samplesType) {
                    HumanSamples::class => $code ? '/samples/humans/'.rawurlencode($code) : null,
                    AnimalSamples::class => $code ? '/samples/animals/'.rawurlencode($code) : null,
                    EnvironmentSamples::class => $code ? '/samples/environment/'.rawurlencode($code) : null,
                    ParasiteSamples::class => $code ? '/samples/parasites/'.rawurlencode($code) : null,
                    NucleicAcids::class => $code ? '/samples/nucleic/'.rawurlencode($code) : null,
                    Cultures::class => $code ? '/samples/cultures/'.rawurlencode($code) : null,
                    Pools::class => $code ? '/samples/pools/'.rawurlencode($code) : null,
                    default => null,
                };

                $codeCell = $code
                    ? ($href ? '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800">'.e($code).'</a>' : e($code))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1">'.($typeLabel ? e($typeLabel) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$codeCell.'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($date ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })
            ->values()
            ->all();

        if (empty($rowsAll)) {
            return 'N/A';
        }

        $maxVisible = 5;
        $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
        $rowsHidden = array_slice($rowsAll, $maxVisible);

        $thead = '<thead class="bg-gray-100 text-gray-700">'
            .'<tr>'
            .'<th class="px-2 py-1">Content type</th>'
            .'<th class="px-2 py-1">Content code</th>'
            .'<th class="px-2 py-1">Sampling site</th>'
            .'<th class="px-2 py-1">Date collected</th>'
            .'</tr>'
            .'</thead>';

        return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function poolContentsByType(TubePositions $p, string $samplesType): Collection
    {
        $contents = data_get($p, 'tubes.tubes_content.pool_contents');
        if (! $contents) {
            return collect();
        }

        return collect($contents)
            ->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $samplesType)
            ->values();
    }

    private function poolDerivedSubtableHtml(TubePositions $p, string $samplesType): string
    {
        $contents = $this->poolContentsByType($p, $samplesType);
        if ($contents->isEmpty()) {
            return 'N/A';
        }

        $maxVisible = 5;
        $id = 'pool-'.$p->id.'-details-'.class_basename($samplesType);
        $rowsVisible = [];
        $rowsHidden = [];

        if ($samplesType === HumanSamples::class) {
            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $patientCode = (string) (data_get($pc, 'samples.humans.code') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleLink = $sampleCode ? '/samples/humans/'.rawurlencode($sampleCode) : '#';
                $patientLink = $patientCode ? '/humans/'.rawurlencode($patientCode) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'
                    .($sampleCode ? '<a href="'.e($sampleLink).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : '<span class="text-gray-500">N/A</span>')
                    .'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'
                    .($patientCode ? '<a href="'.e($patientLink).'" class="text-blue-600 hover:text-blue-800">'.e($patientCode).'</a>' : '<span class="text-gray-500">N/A</span>')
                    .'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();

            $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
            $rowsHidden = array_slice($rowsAll, $maxVisible);

            $thead = '<thead class="bg-gray-100 text-gray-700">'
                .'<tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Patient code</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr>'
                .'</thead>';

            return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
        }

        if ($samplesType === AnimalSamples::class) {
            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $animalCode = (string) (data_get($pc, 'samples.animals.code') ?? '');
                $species = (string) (data_get($pc, 'samples.animals.animal_species.name_common') ?? '');
                $sampleType = (string) (data_get($pc, 'samples.sample_types.name') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleLink = $sampleCode ? '/samples/animals/'.rawurlencode($sampleCode) : '#';
                $animalLink = $animalCode ? '/animals/'.rawurlencode($animalCode) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'
                    .($sampleCode ? '<a href="'.e($sampleLink).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : '<span class="text-gray-500">N/A</span>')
                    .'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'
                    .($animalCode ? '<a href="'.e($animalLink).'" class="text-blue-600 hover:text-blue-800">'.e($animalCode).'</a>' : '<span class="text-gray-500">N/A</span>')
                    .'</td>'
                    .'<td class="px-2 py-1">'.($species ? e($species) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($sampleType ? e($sampleType) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();

            $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
            $rowsHidden = array_slice($rowsAll, $maxVisible);

            $thead = '<thead class="bg-gray-100 text-gray-700">'
                .'<tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Animal code</th>'
                .'<th class="px-2 py-1">Species</th>'
                .'<th class="px-2 py-1">Sample type</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr>'
                .'</thead>';

            return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
        }

        if ($samplesType === EnvironmentSamples::class) {
            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $type = (string) (data_get($pc, 'samples.environment_sample_types.name') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleLink = $sampleCode ? '/samples/environment/'.rawurlencode($sampleCode) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'
                    .($sampleCode ? '<a href="'.e($sampleLink).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : '<span class="text-gray-500">N/A</span>')
                    .'</td>'
                    .'<td class="px-2 py-1">'.($type ? e($type) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();

            $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
            $rowsHidden = array_slice($rowsAll, $maxVisible);

            $thead = '<thead class="bg-gray-100 text-gray-700">'
                .'<tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Sample type</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr>'
                .'</thead>';

            return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
        }

        if ($samplesType === ParasiteSamples::class) {
            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $species = (string) (data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? '');
                $sex = (string) (data_get($pc, 'samples.parasites.sex') ?? '');
                $stage = (string) (data_get($pc, 'samples.parasites.stage') ?? '');
                $site = (string) (data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleLink = $sampleCode ? '/samples/parasites/'.rawurlencode($sampleCode) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'
                    .($sampleCode ? '<a href="'.e($sampleLink).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : '<span class="text-gray-500">N/A</span>')
                    .'</td>'
                    .'<td class="px-2 py-1">'.($species ? '<i>'.e($species).'</i>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($sex ? e($sex) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($stage ? e($stage) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();

            $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
            $rowsHidden = array_slice($rowsAll, $maxVisible);

            $thead = '<thead class="bg-gray-100 text-gray-700">'
                .'<tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Tick species</th>'
                .'<th class="px-2 py-1">Tick sex</th>'
                .'<th class="px-2 py-1">Tick stage</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr>'
                .'</thead>';

            return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
        }

        if ($samplesType === NucleicAcids::class) {
            $rowsAll = $contents->map(function ($pc): string {
                $code = (string) (data_get($pc, 'samples.code') ?? '');
                $type = (string) (data_get($pc, 'samples.type') ?? '');
                $contentCode = (string) (data_get($pc, 'samples.nucleic_content.code') ?? '');
                $contentType = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');

                $primary = $this->poolContentPrimarySiteAndDate($pc);
                $originSite = (string) ($primary['site'] ?? '');
                $originDate = $primary['date'];

                $originDateYmd = $originDate ? (string) Carbon::parse($originDate)->format('Y-m-d') : '';

                $link = $code ? '/samples/nucleic/'.rawurlencode($code) : '#';
                $contentHref = match ($contentType) {
                    HumanSamples::class => $contentCode ? '/samples/humans/'.rawurlencode($contentCode) : null,
                    AnimalSamples::class => $contentCode ? '/samples/animals/'.rawurlencode($contentCode) : null,
                    EnvironmentSamples::class => $contentCode ? '/samples/environment/'.rawurlencode($contentCode) : null,
                    ParasiteSamples::class => $contentCode ? '/samples/parasites/'.rawurlencode($contentCode) : null,
                    Cultures::class => $contentCode ? '/samples/cultures/'.rawurlencode($contentCode) : null,
                    Pools::class => $contentCode ? '/samples/pools/'.rawurlencode($contentCode) : null,
                    default => null,
                };
                $contentCell = $contentCode
                    ? ($contentHref ? '<a href="'.e($contentHref).'" class="text-blue-600 hover:text-blue-800">'.e($contentCode).'</a>' : e($contentCode))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'
                    .($code ? '<a href="'.e($link).'" class="text-blue-600 hover:text-blue-800">'.e($code).'</a>' : '<span class="text-gray-500">N/A</span>')
                    .'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($type ? e($type) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$contentCell.'</td>'
                    .'<td class="px-2 py-1">'.($originSite ? e($originSite) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($originDateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();

            $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
            $rowsHidden = array_slice($rowsAll, $maxVisible);

            $thead = '<thead class="bg-gray-100 text-gray-700">'
                .'<tr>'
                .'<th class="px-2 py-1">Nucleic code</th>'
                .'<th class="px-2 py-1">Type</th>'
                .'<th class="px-2 py-1">Content code</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr>'
                .'</thead>';

            return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
        }

        return 'N/A';
    }

    /**
     * @param  array<int, string>  $rowsVisible
     * @param  array<int, string>  $rowsHidden
     */
    private function collapsibleSubtableHtml(string $id, string $theadHtml, array $rowsVisible, array $rowsHidden): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $id) ?: 'pool-details';
        $total = count($rowsVisible) + count($rowsHidden);

        $html = '<div x-data="{ open: false }" class="space-y-2">';
        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full text-xs text-left border border-gray-200 rounded-lg overflow-hidden">';
        $html .= $theadHtml;
        $html .= '<tbody class="bg-white">'.implode('', $rowsVisible).'</tbody>';

        if (! empty($rowsHidden)) {
            $html .= '<tbody id="'.$safeId.'" x-show="open" x-cloak class="bg-white">'.implode('', $rowsHidden).'</tbody>';
        }

        $html .= '</table>';
        $html .= '</div>';

        if (! empty($rowsHidden)) {
            $html .= '<button type="button" class="text-xs text-gray-600 hover:text-gray-800 underline" x-on:click="open = !open" aria-controls="'.$safeId.'">';
            $html .= '<span x-show="!open">Show all ('.$total.')</span>';
            $html .= '<span x-show="open" x-cloak>Hide</span>';
            $html .= '</button>';
        }

        $html .= '</div>';

        return $html;
    }

    public function export(string $format = 'csv')
    {
        $config = $this->withTubeAliasColumns($this->withStorageColumns($this->selectedTableConfig()));

        $fileName = $config['fileName'] ?? 'tube_positions.csv';
        $headers = $config['csvHeaders'] ?? [];
        $rowBuilder = $config['csvRow'] ?? null;

        if (! is_callable($rowBuilder) || empty($headers)) {
            $this->dispatchSwal(false, 'Export is not configured for this table.');

            return null;
        }

        $query = $this->buildBaseQueryForSelectedTable();

        $exportHeaders = $headers;
        array_splice($exportHeaders, 1, 0, 'Sub-project');

        $rows = [];
        $query->chunk(500, function ($positions) use (&$rows, $rowBuilder) {
            foreach ($positions as $position) {
                $built = $rowBuilder($position);
                $subProjectCode = data_get($position, 'subProjectAssignment.subProject.code') ?? 'N/A';

                if (is_array($built) && isset($built[0]) && is_array($built[0])) {
                    foreach ($built as $row) {
                        array_splice($row, 1, 0, $subProjectCode);
                        $rows[] = $row;
                    }
                } else {
                    array_splice($built, 1, 0, $subProjectCode);
                    $rows[] = $built;
                }
            }
        });

        $basename = preg_replace('/\.csv$/', '', (string) $fileName);

        return $this->exportTable($basename, $exportHeaders, $rows, $format);
    }

    /**
     * @return array{
     *   tableId: string,
     *   subtitle: string,
     *   tubeListKey: string,
     *   showBoxContentType?: bool,
     *   with?: array<int, string>,
     *   scope?: \Closure(Builder):Builder,
     *   filters?: \Closure(Builder):Builder,
     *   extraColumns?: array<int, array<string, mixed>>,
     *   fileName?: string,
     *   csvHeaders?: array<int, string>,
     *   csvRow?: \Closure(TubePositions):array<int, mixed>
     * }
     */
    public function selectedTableConfig(): array
    {
        $dateYmd = fn ($value): string => $value ? (string) Carbon::parse($value)->format('Y-m-d') : 'N/A';

        $baseScope = function (array $types, ?callable $morphCallback = null): \Closure {
            return function (Builder $q) use ($types, $morphCallback) {
                return $q->whereHas('tubes', function (Builder $tubeQ) use ($types, $morphCallback) {
                    $tubeQ->where('projects_id', $this->projectId)
                        ->whereHasMorph('tubes_content', $types, $morphCallback);
                });
            };
        };

        $humanCollectedFilter = function (Builder $q, string $startKey = 'collectedStart', string $endKey = 'collectedEnd'): Builder {
            $start = $this->originFilters[$startKey] ?? null;
            $end = $this->originFilters[$endKey] ?? null;

            if (! $start && ! $end) {
                return $q;
            }

            return $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [HumanSamples::class], function ($sq) use ($start, $end) {
                if ($start && $end) {
                    $sq->whereBetween('date_collected', [$start, $end]);
                } elseif ($start) {
                    $sq->where('date_collected', '>=', $start);
                } else {
                    $sq->where('date_collected', '<=', $end);
                }
            }));
        };

        // ===== Base (All) =====
        if ($this->selectedTable === 'tube_positions_table') {
            return [
                'tableId' => 'tube_positions_table',
                'subtitle' => 'all tube positions',
                'tubeListKey' => 'tubes',
                'showBoxContentType' => true,
                'scope' => $baseScope([
                    HumanSamples::class,
                    AnimalSamples::class,
                    EnvironmentSamples::class,
                    ParasiteSamples::class,
                    Cultures::class,
                    Pools::class,
                    NucleicAcids::class,
                ]),
                'extraColumns' => [],
                'fileName' => 'tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Box code', 'Content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'boxes.content_type', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    $dateYmd(data_get($p, 'date_moved')),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        // ===== Human tubes =====
        if ($this->selectedTable === 'tube_positions_human_table') {
            return [
                'tableId' => 'tube_positions_human_table',
                'subtitle' => 'linked to human samples',
                'tubeListKey' => 'human_tubes',
                'showBoxContentType' => true,
                'with' => ['tubes.tubes_content.humans', 'tubes.tubes_content.sample_types', 'tubes.tubes_content.sampling_sites'],
                'scope' => $baseScope([HumanSamples::class]),
                'extraColumns' => [
                    ['label' => 'Human code', 'valuePath' => 'tubes.tubes_content.humans.code', 'filterModel' => 'originFilters.humanCode', 'link' => '/humans/{value}'],
                    ['label' => 'Sample type', 'valuePath' => 'tubes.tubes_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) use ($humanCollectedFilter) {
                    $humanCode = $this->originFilters['humanCode'] ?? null;
                    if ($humanCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [HumanSamples::class], fn ($sq) => $sq->whereHas('humans', fn ($hq) => $hq->where('code', 'like', '%'.$humanCode.'%'))));
                    }

                    $sampleType = $this->originFilters['sampleType'] ?? null;
                    if ($sampleType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [HumanSamples::class], fn ($sq) => $sq->whereHas('sample_types', fn ($stq) => $stq->where('name', 'like', '%'.$sampleType.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [HumanSamples::class], fn ($sq) => $sq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%'))));
                    }

                    return $humanCollectedFilter($q);
                },
                'fileName' => 'human_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Human code', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.humans.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.sample_types.name', 'N/A'),
                        data_get($p, 'tubes.tubes_content.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.date_collected')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Animal tubes =====
        if ($this->selectedTable === 'tube_positions_animal_table') {
            return [
                'tableId' => 'tube_positions_animal_table',
                'subtitle' => 'linked to animal samples',
                'tubeListKey' => 'animal_tubes',
                'showBoxContentType' => true,
                'with' => [
                    'tubes.tubes_content.animals.animal_species',
                    'tubes.tubes_content.sample_types',
                    'tubes.tubes_content.sampling_sites',
                ],
                'scope' => $baseScope([AnimalSamples::class]),
                'extraColumns' => [
                    ['label' => 'Animal code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.animalSampleCode', 'link' => '/samples/animals/{value}'],
                    ['label' => 'Animal species', 'valuePath' => 'tubes.tubes_content.animals.animal_species.name_common', 'filterModel' => 'originFilters.animalSpecies'],
                    ['label' => 'Sample type', 'valuePath' => 'tubes.tubes_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $animalSampleCode = $this->originFilters['animalSampleCode'] ?? null;
                    if ($animalSampleCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [AnimalSamples::class], fn ($sq) => $sq->where('code', 'like', '%'.$animalSampleCode.'%')));
                    }

                    $animalSpecies = $this->originFilters['animalSpecies'] ?? null;
                    if ($animalSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [AnimalSamples::class], fn ($sq) => $sq->whereHas('animals.animal_species', fn ($aq) => $aq->where('name_common', 'like', '%'.$animalSpecies.'%'))));
                    }

                    $sampleType = $this->originFilters['sampleType'] ?? null;
                    if ($sampleType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [AnimalSamples::class], fn ($sq) => $sq->whereHas('sample_types', fn ($stq) => $stq->where('name', 'like', '%'.$sampleType.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [AnimalSamples::class], fn ($sq) => $sq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%'))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [AnimalSamples::class], function ($sq) use ($start, $end) {
                            if ($start && $end) {
                                $sq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $sq->where('date_collected', '>=', $start);
                            } else {
                                $sq->where('date_collected', '<=', $end);
                            }
                        }));
                    }

                    return $q;
                },
                'fileName' => 'animal_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Animal code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.animals.animal_species.name_common', 'N/A'),
                        data_get($p, 'tubes.tubes_content.sample_types.name', 'N/A'),
                        data_get($p, 'tubes.tubes_content.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.date_collected')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Environment tubes =====
        if ($this->selectedTable === 'tube_positions_environment_table') {
            return [
                'tableId' => 'tube_positions_environment_table',
                'subtitle' => 'linked to environment samples',
                'tubeListKey' => 'environment_tubes',
                'showBoxContentType' => true,
                'with' => [
                    'tubes.tubes_content.environment_sample_types',
                    'tubes.tubes_content.sampling_sites',
                ],
                'scope' => $baseScope([EnvironmentSamples::class]),
                'extraColumns' => [
                    ['label' => 'Environment code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.environmentSampleCode', 'link' => '/samples/environment/{value}'],
                    ['label' => 'Sample type', 'valuePath' => 'tubes.tubes_content.environment_sample_types.name', 'filterModel' => 'originFilters.environmentSampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $code = $this->originFilters['environmentSampleCode'] ?? null;
                    if ($code) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [EnvironmentSamples::class], fn ($sq) => $sq->where('code', 'like', '%'.$code.'%')));
                    }

                    $type = $this->originFilters['environmentSampleType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [EnvironmentSamples::class], fn ($sq) => $sq->whereHas('environment_sample_types', fn ($etq) => $etq->where('name', 'like', '%'.$type.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [EnvironmentSamples::class], fn ($sq) => $sq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%'))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [EnvironmentSamples::class], function ($sq) use ($start, $end) {
                            if ($start && $end) {
                                $sq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $sq->where('date_collected', '>=', $start);
                            } else {
                                $sq->where('date_collected', '<=', $end);
                            }
                        }));
                    }

                    return $q;
                },
                'fileName' => 'environment_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Environment code', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.environment_sample_types.name', 'N/A'),
                        data_get($p, 'tubes.tubes_content.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.date_collected')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Parasite tubes (all origins) =====
        if ($this->selectedTable === 'tube_positions_parasite_table') {
            return [
                'tableId' => 'tube_positions_parasite_table',
                'subtitle' => 'linked to parasite samples',
                'tubeListKey' => 'parasite_tubes',
                'showBoxContentType' => true,
                'with' => [
                    'tubes.tubes_content.parasites',
                    'tubes.tubes_content.parasites.parasite_species',
                    'tubes.tubes_content.parasite_sample_types',
                    'tubes.tubes_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => $baseScope([ParasiteSamples::class]),
                'extraColumns' => [
                    ['label' => 'Parasite code', 'valuePath' => 'tubes.tubes_content.parasites.code', 'filterModel' => 'originFilters.parasiteCode', 'link' => '/parasites/{value}'],
                    ['label' => 'Parasite species', 'value' => fn (TubePositions $p): string => $this->parasiteSpeciesLabelHtml($p, 'tubes.tubes_content.parasites.parasite_species'), 'html' => true, 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Parasite sex', 'valuePath' => 'tubes.tubes_content.parasites.sex', 'filterModel' => 'originFilters.parasiteSex'],
                    ['label' => 'Parasite stage', 'valuePath' => 'tubes.tubes_content.parasites.stage', 'filterModel' => 'originFilters.parasiteStage'],
                    ['label' => 'Sample type', 'valuePath' => 'tubes.tubes_content.parasite_sample_types.name', 'filterModel' => 'originFilters.parasiteSampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $parasiteCode = $this->originFilters['parasiteCode'] ?? null;
                    if ($parasiteCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('code', 'like', '%'.$parasiteCode.'%'))));
                    }

                    $parasiteSpecies = $this->originFilters['parasiteSpecies'] ?? null;
                    if ($parasiteSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasite_species', fn ($psq) => $this->applyParasiteSpeciesNameFilter($psq, (string) $parasiteSpecies))));
                    }

                    $parasiteSex = $this->originFilters['parasiteSex'] ?? null;
                    if ($parasiteSex) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('sex', 'like', '%'.$parasiteSex.'%'))));
                    }

                    $parasiteStage = $this->originFilters['parasiteStage'] ?? null;
                    if ($parasiteStage) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('stage', 'like', '%'.$parasiteStage.'%'))));
                    }

                    $sampleType = $this->originFilters['parasiteSampleType'] ?? null;
                    if ($sampleType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasite_sample_types', fn ($ptq) => $ptq->where('name', 'like', '%'.$sampleType.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%'))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], function ($sq) use ($start, $end) {
                            $sq->whereHas('parasites.parasites_origin', function ($oq) use ($start, $end) {
                                if ($start && $end) {
                                    $oq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $oq->where('date_collected', '>=', $start);
                                } else {
                                    $oq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'parasite_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Parasite code', 'Parasite species', 'Parasite sex', 'Parasite stage', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.code', 'N/A'),
                        $this->parasiteSpeciesLabel($p, 'tubes.tubes_content.parasites.parasite_species'),
                        data_get($p, 'tubes.tubes_content.parasites.sex', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.stage', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasite_sample_types.name', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Parasite tubes from human samples =====
        if ($this->selectedTable === 'tube_positions_parasite_human_table') {
            return [
                'tableId' => 'tube_positions_parasite_human_table',
                'subtitle' => 'linked to parasites from human samples',
                'tubeListKey' => 'parasite_tubes',
                'showBoxContentType' => true,
                'with' => [
                    'tubes.tubes_content.parasites.parasite_species',
                    'tubes.tubes_content.parasites.parasites_origin',
                    'tubes.tubes_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => $baseScope([ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('parasites_origin_type', HumanSamples::class))),
                'extraColumns' => [
                    ['label' => 'Parasite code', 'valuePath' => 'tubes.tubes_content.parasites.code', 'filterModel' => 'originFilters.parasiteCode', 'link' => '/parasites/{value}'],
                    ['label' => 'Parasite species', 'value' => fn (TubePositions $p): string => $this->parasiteSpeciesLabelHtml($p, 'tubes.tubes_content.parasites.parasite_species'), 'html' => true, 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Parasite sex', 'valuePath' => 'tubes.tubes_content.parasites.sex', 'filterModel' => 'originFilters.parasiteSex'],
                    ['label' => 'Parasite stage', 'valuePath' => 'tubes.tubes_content.parasites.stage', 'filterModel' => 'originFilters.parasiteStage'],
                    ['label' => 'Human code', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.code', 'filterModel' => 'originFilters.humanSampleCode', 'link' => '/samples/humans/{value}'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $parasiteCode = $this->originFilters['parasiteCode'] ?? null;
                    if ($parasiteCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('code', 'like', '%'.$parasiteCode.'%'))));
                    }

                    $parasiteSpecies = $this->originFilters['parasiteSpecies'] ?? null;
                    if ($parasiteSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasite_species', fn ($psq) => $this->applyParasiteSpeciesNameFilter($psq, (string) $parasiteSpecies))));
                    }

                    $parasiteSex = $this->originFilters['parasiteSex'] ?? null;
                    if ($parasiteSex) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('sex', 'like', '%'.$parasiteSex.'%'))));
                    }

                    $parasiteStage = $this->originFilters['parasiteStage'] ?? null;
                    if ($parasiteStage) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('stage', 'like', '%'.$parasiteStage.'%'))));
                    }

                    $humanSampleCode = $this->originFilters['humanSampleCode'] ?? null;
                    if ($humanSampleCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin', fn ($oq) => $oq->where('code', 'like', '%'.$humanSampleCode.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%'))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], function ($sq) use ($start, $end) {
                            $sq->whereHas('parasites.parasites_origin', function ($oq) use ($start, $end) {
                                if ($start && $end) {
                                    $oq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $oq->where('date_collected', '>=', $start);
                                } else {
                                    $oq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'parasite_human_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Parasite code', 'Parasite species', 'Parasite sex', 'Parasite stage', 'Human code', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.code', 'N/A'),
                        $this->parasiteSpeciesLabel($p, 'tubes.tubes_content.parasites.parasite_species'),
                        data_get($p, 'tubes.tubes_content.parasites.sex', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.stage', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Parasite tubes from animal samples =====
        if ($this->selectedTable === 'tube_positions_parasite_animal_table') {
            return [
                'tableId' => 'tube_positions_parasite_animal_table',
                'subtitle' => 'linked to parasites from animal samples',
                'tubeListKey' => 'parasite_tubes',
                'showBoxContentType' => true,
                'with' => [
                    'tubes.tubes_content.parasites.parasite_species',
                    'tubes.tubes_content.parasites.parasites_origin.animals.animal_species',
                    'tubes.tubes_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => $baseScope([ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('parasites_origin_type', AnimalSamples::class))),
                'extraColumns' => [
                    ['label' => 'Parasite code', 'valuePath' => 'tubes.tubes_content.parasites.code', 'filterModel' => 'originFilters.parasiteCode', 'link' => '/parasites/{value}'],
                    ['label' => 'Parasite species', 'value' => fn (TubePositions $p): string => $this->parasiteSpeciesLabelHtml($p, 'tubes.tubes_content.parasites.parasite_species'), 'html' => true, 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Parasite sex', 'valuePath' => 'tubes.tubes_content.parasites.sex', 'filterModel' => 'originFilters.parasiteSex'],
                    ['label' => 'Parasite stage', 'valuePath' => 'tubes.tubes_content.parasites.stage', 'filterModel' => 'originFilters.parasiteStage'],
                    ['label' => 'Animal code', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.animals.code', 'filterModel' => 'originFilters.animalCode', 'link' => '/animals/{value}'],
                    ['label' => 'Animal species', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.animals.animal_species.name_common', 'filterModel' => 'originFilters.animalSpecies'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $parasiteCode = $this->originFilters['parasiteCode'] ?? null;
                    if ($parasiteCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('code', 'like', '%'.$parasiteCode.'%'))));
                    }

                    $parasiteSpecies = $this->originFilters['parasiteSpecies'] ?? null;
                    if ($parasiteSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasite_species', fn ($psq) => $this->applyParasiteSpeciesNameFilter($psq, (string) $parasiteSpecies))));
                    }

                    $parasiteSex = $this->originFilters['parasiteSex'] ?? null;
                    if ($parasiteSex) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('sex', 'like', '%'.$parasiteSex.'%'))));
                    }

                    $parasiteStage = $this->originFilters['parasiteStage'] ?? null;
                    if ($parasiteStage) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('stage', 'like', '%'.$parasiteStage.'%'))));
                    }

                    $animalCode = $this->originFilters['animalCode'] ?? null;
                    if ($animalCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.animals', fn ($aq) => $aq->where('code', 'like', '%'.$animalCode.'%'))));
                    }

                    $animalSpecies = $this->originFilters['animalSpecies'] ?? null;
                    if ($animalSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.animals.animal_species', fn ($asq) => $asq->where('name_common', 'like', '%'.$animalSpecies.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%'))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], function ($sq) use ($start, $end) {
                            $sq->whereHas('parasites.parasites_origin', function ($oq) use ($start, $end) {
                                if ($start && $end) {
                                    $oq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $oq->where('date_collected', '>=', $start);
                                } else {
                                    $oq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'parasite_animal_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Parasite code', 'Parasite species', 'Parasite sex', 'Parasite stage', 'Animal code', 'Animal species', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.code', 'N/A'),
                        $this->parasiteSpeciesLabel($p, 'tubes.tubes_content.parasites.parasite_species'),
                        data_get($p, 'tubes.tubes_content.parasites.sex', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.stage', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.animals.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.animals.animal_species.name_common', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Parasite tubes from environment samples =====
        if ($this->selectedTable === 'tube_positions_parasite_environment_table') {
            return [
                'tableId' => 'tube_positions_parasite_environment_table',
                'subtitle' => 'linked to parasites from environment samples',
                'tubeListKey' => 'parasite_tubes',
                'showBoxContentType' => true,
                'with' => [
                    'tubes.tubes_content.parasites.parasite_species',
                    'tubes.tubes_content.parasites.parasites_origin.environment_sample_types',
                    'tubes.tubes_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => $baseScope([ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('parasites_origin_type', EnvironmentSamples::class))),
                'extraColumns' => [
                    ['label' => 'Parasite code', 'valuePath' => 'tubes.tubes_content.parasites.code', 'filterModel' => 'originFilters.parasiteCode', 'link' => '/parasites/{value}'],
                    ['label' => 'Parasite species', 'value' => fn (TubePositions $p): string => $this->parasiteSpeciesLabelHtml($p, 'tubes.tubes_content.parasites.parasite_species'), 'html' => true, 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Parasite sex', 'valuePath' => 'tubes.tubes_content.parasites.sex', 'filterModel' => 'originFilters.parasiteSex'],
                    ['label' => 'Parasite stage', 'valuePath' => 'tubes.tubes_content.parasites.stage', 'filterModel' => 'originFilters.parasiteStage'],
                    ['label' => 'Environment code', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.code', 'filterModel' => 'originFilters.environmentSampleCode', 'link' => '/samples/environment/{value}'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $parasiteCode = $this->originFilters['parasiteCode'] ?? null;
                    if ($parasiteCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('code', 'like', '%'.$parasiteCode.'%'))));
                    }

                    $parasiteSpecies = $this->originFilters['parasiteSpecies'] ?? null;
                    if ($parasiteSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasite_species', fn ($psq) => $this->applyParasiteSpeciesNameFilter($psq, (string) $parasiteSpecies))));
                    }

                    $parasiteSex = $this->originFilters['parasiteSex'] ?? null;
                    if ($parasiteSex) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('sex', 'like', '%'.$parasiteSex.'%'))));
                    }

                    $parasiteStage = $this->originFilters['parasiteStage'] ?? null;
                    if ($parasiteStage) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('stage', 'like', '%'.$parasiteStage.'%'))));
                    }

                    $envCode = $this->originFilters['environmentSampleCode'] ?? null;
                    if ($envCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin', fn ($oq) => $oq->where('code', 'like', '%'.$envCode.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%'))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [ParasiteSamples::class], function ($sq) use ($start, $end) {
                            $sq->whereHas('parasites.parasites_origin', function ($oq) use ($start, $end) {
                                if ($start && $end) {
                                    $oq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $oq->where('date_collected', '>=', $start);
                                } else {
                                    $oq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'parasite_environment_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Parasite code', 'Parasite species', 'Parasite sex', 'Parasite stage', 'Environment code', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.code', 'N/A'),
                        $this->parasiteSpeciesLabel($p, 'tubes.tubes_content.parasites.parasite_species'),
                        data_get($p, 'tubes.tubes_content.parasites.sex', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.stage', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.parasites.parasites_origin.date_collected')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Culture tubes =====
        if ($this->selectedTable === 'tube_positions_culture_table') {
            return [
                'tableId' => 'tube_positions_culture_table',
                'subtitle' => 'linked to cultures',
                'tubeListKey' => 'culture_tubes',
                'showBoxContentType' => true,
                'with' => ['tubes.tubes_content'],
                'scope' => $baseScope([Cultures::class]),
                'extraColumns' => [
                    ['label' => 'Medium', 'valuePath' => 'tubes.tubes_content.medium', 'filterModel' => 'originFilters.medium'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.cultureType'],
                    [
                        'label' => 'Date cultured',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.date_cultured')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.culturedStart',
                        'filterModelEnd' => 'originFilters.culturedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $medium = $this->originFilters['medium'] ?? null;
                    if ($medium) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('medium', 'like', '%'.$medium.'%')));
                    }

                    $type = $this->originFilters['cultureType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%')));
                    }

                    $start = $this->originFilters['culturedStart'] ?? null;
                    $end = $this->originFilters['culturedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function ($sq) use ($start, $end) {
                            if ($start && $end) {
                                $sq->whereBetween('date_cultured', [$start, $end]);
                            } elseif ($start) {
                                $sq->where('date_cultured', '>=', $start);
                            } else {
                                $sq->where('date_cultured', '<=', $end);
                            }
                        }));
                    }

                    return $q;
                },
                'fileName' => 'culture_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Medium', 'Type', 'Date cultured', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.medium', 'N/A'),
                        data_get($p, 'tubes.tubes_content.type', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.date_cultured')),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Pool tubes =====
        if ($this->selectedTable === 'tube_positions_pool_table') {
            return [
                'tableId' => 'tube_positions_pool_table',
                'subtitle' => 'linked to pools',
                'tubeListKey' => 'pool_tubes',
                'showBoxContentType' => true,
                'with' => ['tubes.tubes_content.pool_contents', 'tubes.tubes_content.pool_contents.samples'],
                'scope' => $baseScope([Pools::class]),
                'extraColumns' => [
                    [
                        'label' => 'Contents type(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsTypesLabel($p),
                        'filterModel' => 'originFilters.poolContentType',
                    ],
                    [
                        'label' => 'Contents count',
                        'value' => fn (TubePositions $p): string => (string) $this->poolContentsCount($p),
                        'filterModel' => 'originFilters.poolContentsCount',
                    ],
                    [
                        'label' => 'Contents code(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCodesHtml($p),
                        'html' => true,
                        'filterModel' => 'originFilters.poolContentCode',
                    ],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCollectedRange($p),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $contentType = $this->originFilters['poolContentType'] ?? null;
                    if ($contentType) {
                        $normalized = strtolower(trim((string) $contentType));
                        $typeMap = [
                            'human' => HumanSamples::class,
                            'animal' => AnimalSamples::class,
                            'environment' => EnvironmentSamples::class,
                            'environmental' => EnvironmentSamples::class,
                            'parasite' => ParasiteSamples::class,
                            'nucleic' => NucleicAcids::class,
                        ];

                        $matchType = null;
                        foreach ($typeMap as $needle => $fqcn) {
                            if (str_contains($normalized, $needle)) {
                                $matchType = $fqcn;
                                break;
                            }
                        }

                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', function (Builder $pcq) use ($contentType, $matchType) {
                            if ($matchType) {
                                $pcq->where('samples_type', $matchType);

                                return;
                            }

                            $pcq->where('samples_type', 'like', '%'.$contentType.'%');
                        })));
                    }

                    $contentCode = $this->originFilters['poolContentCode'] ?? null;
                    if ($contentCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [
                            HumanSamples::class,
                            AnimalSamples::class,
                            EnvironmentSamples::class,
                            ParasiteSamples::class,
                            NucleicAcids::class,
                        ], fn ($sampleQ) => $sampleQ->where('code', 'like', '%'.$contentCode.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], function ($sq) use ($start, $end) {
                            $sq->whereHas('pool_contents.samples', function ($sq2) use ($start, $end) {
                                if ($start && $end) {
                                    $sq2->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $sq2->where('date_collected', '>=', $start);
                                } else {
                                    $sq2->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'pool_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Contents type(s)', 'Contents count', 'Contents code(s)', 'Collected date(s)', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        $this->poolContentsTypesLabel($p),
                        $this->poolContentsCount($p),
                        $this->poolContentsCodes($p, limit: 1000),
                        $this->poolContentsCollectedRange($p),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Nucleic acid tubes (all nucleic contents) =====
        if ($this->selectedTable === 'tube_positions_nucleic_table') {
            return [
                'tableId' => 'tube_positions_nucleic_table',
                'subtitle' => 'linked to nucleic acids',
                'tubeListKey' => 'nucleic_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.laboratories'],
                'scope' => $baseScope([NucleicAcids::class]),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.nucleicCode', 'link' => '/samples/nucleic/{value}'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.date_extracted')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.extractedStart',
                        'filterModelEnd' => 'originFilters.extractedEnd',
                    ],
                    ['label' => 'Extracted at', 'valuePath' => 'tubes.tubes_content.laboratories.name', 'filterModel' => 'originFilters.laboratory'],
                ],
                'filters' => function (Builder $q) {
                    $code = $this->originFilters['nucleicCode'] ?? null;
                    if ($code) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('code', 'like', '%'.$code.'%')));
                    }

                    $type = $this->originFilters['nucleicType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%')));
                    }

                    $lab = $this->originFilters['laboratory'] ?? null;
                    if ($lab) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHas('laboratories', fn ($lq) => $lq->where('name', 'like', '%'.$lab.'%'))));
                    }

                    $start = $this->originFilters['extractedStart'] ?? null;
                    $end = $this->originFilters['extractedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                            if ($start && $end) {
                                $sq->whereBetween('date_extracted', [$start, $end]);
                            } elseif ($start) {
                                $sq->where('date_extracted', '>=', $start);
                            } else {
                                $sq->where('date_extracted', '<=', $end);
                            }
                        }));
                    }

                    return $q;
                },
                'fileName' => 'nucleic_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Type', 'Date extracted', 'Extracted at', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.type', 'N/A'),
                        $dateYmd(data_get($p, 'tubes.tubes_content.date_extracted')),
                        data_get($p, 'tubes.tubes_content.laboratories.name', 'N/A'),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Nucleic acids from human samples =====
        if ($this->selectedTable === 'tube_positions_nucleic_human_table') {
            return [
                'tableId' => 'tube_positions_nucleic_human_table',
                'subtitle' => 'linked to nucleic acids from human samples',
                'tubeListKey' => 'nucleic_tubes',
                'showBoxContentType' => true,
                'with' => ['tubes.tubes_content.nucleic_content.humans', 'tubes.tubes_content.nucleic_content.sampling_sites'],
                'scope' => $baseScope([NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [HumanSamples::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.nucleicCode', 'link' => '/samples/nucleic/{value}'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    ['label' => 'Human code', 'valuePath' => 'tubes.tubes_content.nucleic_content.humans.code', 'filterModel' => 'originFilters.humanCode', 'link' => '/humans/{value}'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.nucleic_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $nucleicCode = $this->originFilters['nucleicCode'] ?? null;
                    if ($nucleicCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('code', 'like', '%'.$nucleicCode.'%')));
                    }

                    $nucleicType = $this->originFilters['nucleicType'] ?? null;
                    if ($nucleicType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$nucleicType.'%')));
                    }

                    $humanCode = $this->originFilters['humanCode'] ?? null;
                    if ($humanCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [HumanSamples::class], fn ($hq) => $hq->whereHas('humans', fn ($h) => $h->where('code', 'like', '%'.$humanCode.'%')))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [HumanSamples::class], fn ($hq) => $hq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('nucleic_content', [HumanSamples::class], function ($hq) use ($start, $end) {
                                if ($start && $end) {
                                    $hq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $hq->where('date_collected', '>=', $start);
                                } else {
                                    $hq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'nucleic_human_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Type', 'Human code', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.humans.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.sampling_sites.name', 'N/A'),
                    $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_collected')),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'boxes.content_type', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    $dateYmd(data_get($p, 'date_moved')),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        // ===== Nucleic acids from animal samples =====
        if ($this->selectedTable === 'tube_positions_nucleic_animal_table') {
            return [
                'tableId' => 'tube_positions_nucleic_animal_table',
                'subtitle' => 'linked to nucleic acids from animal samples',
                'tubeListKey' => 'nucleic_tubes',
                'showBoxContentType' => false,
                'with' => [
                    'tubes.tubes_content.nucleic_content.animals.animal_species',
                    'tubes.tubes_content.nucleic_content.sample_types',
                    'tubes.tubes_content.nucleic_content.sampling_sites',
                ],
                'scope' => $baseScope([NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.nucleicCode', 'link' => '/samples/nucleic/{value}'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    ['label' => 'Sample code', 'valuePath' => 'tubes.tubes_content.nucleic_content.code', 'filterModel' => 'originFilters.animalSampleCode', 'link' => '/samples/animals/{value}'],
                    ['label' => 'Animal species', 'valuePath' => 'tubes.tubes_content.nucleic_content.animals.animal_species.name_common', 'filterModel' => 'originFilters.animalSpecies'],
                    ['label' => 'Sample type', 'valuePath' => 'tubes.tubes_content.nucleic_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.nucleic_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $nucleicCode = $this->originFilters['nucleicCode'] ?? null;
                    if ($nucleicCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('code', 'like', '%'.$nucleicCode.'%')));
                    }

                    $nucleicType = $this->originFilters['nucleicType'] ?? null;
                    if ($nucleicType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$nucleicType.'%')));
                    }

                    $animalSampleCode = $this->originFilters['animalSampleCode'] ?? null;
                    if ($animalSampleCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn ($aq) => $aq->where('code', 'like', '%'.$animalSampleCode.'%'))));
                    }

                    $animalSpecies = $this->originFilters['animalSpecies'] ?? null;
                    if ($animalSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn ($aq) => $aq->whereHas('animals.animal_species', fn ($asq) => $asq->where('name_common', 'like', '%'.$animalSpecies.'%')))));
                    }

                    $sampleType = $this->originFilters['sampleType'] ?? null;
                    if ($sampleType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn ($aq) => $aq->whereHas('sample_types', fn ($stq) => $stq->where('name', 'like', '%'.$sampleType.'%')))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn ($aq) => $aq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], function ($aq) use ($start, $end) {
                                if ($start && $end) {
                                    $aq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $aq->where('date_collected', '>=', $start);
                                } else {
                                    $aq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'nucleic_animal_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Type', 'Sample code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.animals.animal_species.name_common', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.sample_types.name', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.sampling_sites.name', 'N/A'),
                    $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_collected')),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    $dateYmd(data_get($p, 'date_moved')),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        // ===== Nucleic acids from parasite samples =====
        if ($this->selectedTable === 'tube_positions_nucleic_parasite_table') {
            return [
                'tableId' => 'tube_positions_nucleic_parasite_table',
                'subtitle' => 'linked to nucleic acids from parasite samples',
                'tubeListKey' => 'nucleic_tubes',
                'showBoxContentType' => true,
                'with' => [
                    'tubes.tubes_content.nucleic_content.parasites.parasite_species',
                    'tubes.tubes_content.nucleic_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => $baseScope([NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.nucleicCode', 'link' => '/samples/nucleic/{value}'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    ['label' => 'Sample code', 'valuePath' => 'tubes.tubes_content.nucleic_content.code', 'filterModel' => 'originFilters.parasiteSampleCode', 'link' => '/samples/parasites/{value}'],
                    ['label' => 'Parasite species', 'value' => fn (TubePositions $p): string => $this->parasiteSpeciesLabelHtml($p, 'tubes.tubes_content.nucleic_content.parasites.parasite_species'), 'html' => true, 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Parasite sex', 'valuePath' => 'tubes.tubes_content.nucleic_content.parasites.sex', 'filterModel' => 'originFilters.parasiteSex'],
                    ['label' => 'Parasite stage', 'valuePath' => 'tubes.tubes_content.nucleic_content.parasites.stage', 'filterModel' => 'originFilters.parasiteStage'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.nucleic_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $nucleicCode = $this->originFilters['nucleicCode'] ?? null;
                    if ($nucleicCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('code', 'like', '%'.$nucleicCode.'%')));
                    }

                    $nucleicType = $this->originFilters['nucleicType'] ?? null;
                    if ($nucleicType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$nucleicType.'%')));
                    }

                    $parasiteSampleCode = $this->originFilters['parasiteSampleCode'] ?? null;
                    if ($parasiteSampleCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class], fn ($pq) => $pq->where('code', 'like', '%'.$parasiteSampleCode.'%'))));
                    }

                    $parasiteSpecies = $this->originFilters['parasiteSpecies'] ?? null;
                    if ($parasiteSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites.parasite_species', fn ($psq) => $this->applyParasiteSpeciesNameFilter($psq, (string) $parasiteSpecies)))));
                    }

                    $parasiteSex = $this->originFilters['parasiteSex'] ?? null;
                    if ($parasiteSex) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites', fn ($parQ) => $parQ->where('sex', 'like', '%'.$parasiteSex.'%')))));
                    }

                    $parasiteStage = $this->originFilters['parasiteStage'] ?? null;
                    if ($parasiteStage) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites', fn ($parQ) => $parQ->where('stage', 'like', '%'.$parasiteStage.'%')))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites.parasites_origin.sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class], function ($pq) use ($start, $end) {
                                $pq->whereHas('parasites.parasites_origin', function ($oq) use ($start, $end) {
                                    if ($start && $end) {
                                        $oq->whereBetween('date_collected', [$start, $end]);
                                    } elseif ($start) {
                                        $oq->where('date_collected', '>=', $start);
                                    } else {
                                        $oq->where('date_collected', '<=', $end);
                                    }
                                });
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'nucleic_parasite_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Type', 'Sample code', 'Parasite species', 'Parasite sex', 'Parasite stage', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.code', 'N/A'),
                    $this->parasiteSpeciesLabel($p, 'tubes.tubes_content.nucleic_content.parasites.parasite_species'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.parasites.sex', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.parasites.stage', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                    $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.parasites.parasites_origin.date_collected')),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'boxes.content_type', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    $dateYmd(data_get($p, 'date_moved')),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        // ===== Nucleic acids from culture samples =====
        if ($this->selectedTable === 'tube_positions_nucleic_culture_table') {
            return [
                'tableId' => 'tube_positions_nucleic_culture_table',
                'subtitle' => 'linked to nucleic acids from culture samples',
                'tubeListKey' => 'nucleic_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.nucleic_content', 'tubes.tubes_content.nucleic_content.cultures_content'],
                'scope' => $baseScope([NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [Cultures::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.nucleicAcidCode', 'link' => '/samples/nucleic/{value}'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    ['label' => 'Sample code', 'valuePath' => 'tubes.tubes_content.nucleic_content.code', 'filterModel' => 'originFilters.cultureCode', 'link' => '/samples/cultures/{value}'],
                    [
                        'label' => 'Date cultured',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_cultured')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.culturedStart',
                        'filterModelEnd' => 'originFilters.culturedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $nucleic = $this->originFilters['nucleicAcidCode'] ?? null;
                    if ($nucleic) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('code', 'like', '%'.$nucleic.'%')));
                    }

                    $nucleicType = $this->originFilters['nucleicType'] ?? null;
                    if ($nucleicType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$nucleicType.'%')));
                    }

                    $cultureCode = $this->originFilters['cultureCode'] ?? null;
                    if ($cultureCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [Cultures::class], fn ($cq) => $cq->where('code', 'like', '%'.$cultureCode.'%'))));
                    }

                    $start = $this->originFilters['culturedStart'] ?? null;
                    $end = $this->originFilters['culturedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('nucleic_content', [Cultures::class], function ($cq) use ($start, $end) {
                                if ($start && $end) {
                                    $cq->whereBetween('date_cultured', [$start, $end]);
                                } elseif ($start) {
                                    $cq->where('date_cultured', '>=', $start);
                                } else {
                                    $cq->where('date_cultured', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'nucleic_acid_culture_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Type', 'Sample code', 'Date cultured', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.nucleic_content.code', 'N/A'),
                    $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_cultured')),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    $dateYmd(data_get($p, 'date_moved')),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        // ===== Nucleic acids from pool samples =====
        if ($this->selectedTable === 'tube_positions_nucleic_pool_table') {
            return [
                'tableId' => 'tube_positions_nucleic_pool_table',
                'subtitle' => 'linked to nucleic acids from pool samples',
                'tubeListKey' => 'nucleic_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.nucleic_content', 'tubes.tubes_content.nucleic_content.pool_contents', 'tubes.tubes_content.nucleic_content.pool_contents.samples'],
                'scope' => $baseScope([NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [Pools::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.nucleicAcidCode', 'link' => '/samples/nucleic/{value}'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    ['label' => 'Sample code', 'valuePath' => 'tubes.tubes_content.nucleic_content.code', 'filterModel' => 'originFilters.poolCode', 'link' => '/samples/pools/{value}'],
                    [
                        'label' => 'Date pooled',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_pooled')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.pooledStart',
                        'filterModelEnd' => 'originFilters.pooledEnd',
                    ],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCollectedRangeForPoolModel(data_get($p, 'tubes.tubes_content.nucleic_content')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (TubePositions $p): string => $this->poolContentsDetailsCombinedHtmlForPoolModel(
                            data_get($p, 'tubes.tubes_content.nucleic_content'),
                            'nucleic-pool-'.$p->id.'-contents'
                        ),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $nucleic = $this->originFilters['nucleicAcidCode'] ?? null;
                    if ($nucleic) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('code', 'like', '%'.$nucleic.'%')));
                    }

                    $nucleicType = $this->originFilters['nucleicType'] ?? null;
                    if ($nucleicType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$nucleicType.'%')));
                    }

                    $poolCode = $this->originFilters['poolCode'] ?? null;
                    if ($poolCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [Pools::class], fn ($pq) => $pq->where('code', 'like', '%'.$poolCode.'%'))));
                    }

                    $start = $this->originFilters['pooledStart'] ?? null;
                    $end = $this->originFilters['pooledEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('nucleic_content', [Pools::class], function ($pq) use ($start, $end) {
                                if ($start && $end) {
                                    $pq->whereBetween('date_pooled', [$start, $end]);
                                } elseif ($start) {
                                    $pq->where('date_pooled', '>=', $start);
                                } else {
                                    $pq->where('date_pooled', '<=', $end);
                                }
                            });
                        }));
                    }

                    $search = $this->originFilters['contentSearch'] ?? null;
                    if (filled($search)) {
                        $q->whereHas('tubes', function (Builder $tubeQ) use ($search): void {
                            $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function (Builder $sq) use ($search): void {
                                $sq->whereHasMorph('nucleic_content', [Pools::class], function (Builder $pq) use ($search): void {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($search): void {
                                        $pcq->where(function (Builder $w) use ($search): void {
                                            $w->whereHasMorph('samples', [HumanSamples::class], fn (Builder $hq) => $hq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [AnimalSamples::class], fn (Builder $aq) => $aq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [EnvironmentSamples::class], fn (Builder $eq) => $eq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [ParasiteSamples::class], fn (Builder $pq2) => $pq2->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [Pools::class], fn (Builder $plq) => $plq->where('code', 'like', '%'.$search.'%'));
                                        });
                                    });
                                });
                            });
                        });
                    }

                    $collectedStart = $this->originFilters['collectedStart'] ?? null;
                    $collectedEnd = $this->originFilters['collectedEnd'] ?? null;
                    if ($collectedStart || $collectedEnd) {
                        $q->whereHas('tubes', function (Builder $tubeQ) use ($collectedStart, $collectedEnd): void {
                            $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function (Builder $sq) use ($collectedStart, $collectedEnd): void {
                                $sq->whereHasMorph('nucleic_content', [Pools::class], function (Builder $pq) use ($collectedStart, $collectedEnd): void {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($collectedStart, $collectedEnd): void {
                                        $applyRange = function (Builder $q, string $column) use ($collectedStart, $collectedEnd): void {
                                            if ($collectedStart && $collectedEnd) {
                                                $q->whereBetween($column, [$collectedStart, $collectedEnd]);
                                            } elseif ($collectedStart) {
                                                $q->where($column, '>=', $collectedStart);
                                            } else {
                                                $q->where($column, '<=', $collectedEnd);
                                            }
                                        };

                                        $pcq->where(function (Builder $w) use ($applyRange): void {
                                            $w->whereHasMorph('samples', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $sq) use ($applyRange): void {
                                                $applyRange($sq, 'date_collected');
                                            })
                                                ->orWhereHasMorph('samples', [ParasiteSamples::class], function (Builder $sq) use ($applyRange): void {
                                                    $sq->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                                                })
                                                ->orWhereHasMorph('samples', [NucleicAcids::class], function (Builder $sq) use ($applyRange): void {
                                                    $sq->where(function (Builder $nq) use ($applyRange): void {
                                                        $nq->whereHasMorph('nucleic_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $cq) use ($applyRange): void {
                                                            $applyRange($cq, 'date_collected');
                                                        })->orWhereHasMorph('nucleic_content', [ParasiteSamples::class], function (Builder $cq) use ($applyRange): void {
                                                            $cq->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                                                        })->orWhereHasMorph('nucleic_content', [Cultures::class], function (Builder $cq) use ($applyRange): void {
                                                            $cq->whereHasMorph('cultures_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $ccq) use ($applyRange): void {
                                                                $applyRange($ccq, 'date_collected');
                                                            })->orWhereHasMorph('cultures_content', [ParasiteSamples::class], function (Builder $ccq) use ($applyRange): void {
                                                                $ccq->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                                                            });
                                                        });
                                                    });
                                                });
                                        });
                                    });
                                });
                            });
                        });
                    }

                    return $q;
                },
                'fileName' => 'nucleic_acid_pool_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Type', 'Pool code', 'Date pooled', 'Content type', 'Content code', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    /** @var Pools|null $pool */
                    $pool = data_get($p, 'tubes.tubes_content.nucleic_content');
                    $contents = collect(data_get($pool, 'pool_contents', []))->filter(fn ($pc) => data_get($pc, 'samples') !== null)->values();

                    $base = [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.type', 'N/A'),
                        data_get($pool, 'code', 'N/A'),
                        $dateYmd(data_get($pool, 'date_pooled')),
                    ];

                    $tail = [
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail): array {
                        $type = (string) (data_get($pc, 'samples_type') ?? '');
                        $typeLabel = $type ? str_replace('App\\Models\\', '', $type) : 'N/A';
                        $code = data_get($pc, 'samples.code') ?? 'N/A';
                        $primary = $this->poolContentPrimarySiteAndDate($pc);
                        $site = $primary['site'] ?? 'N/A';
                        $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                        return array_merge($base, [$typeLabel, $code, $site, $date], $tail);
                    })->all();
                },
            ];
        }

        // ===== Nucleic acids from environment samples (detailed) =====
        if ($this->selectedTable === 'tube_positions_nucleic_environment_table') {
            return [
                'tableId' => 'tube_positions_nucleic_environment_table',
                'subtitle' => 'linked to nucleic acids from environmental samples',
                'tubeListKey' => 'nucleic_tubes',
                'showBoxContentType' => false,
                'with' => [
                    'tubes.tubes_content',
                    'tubes.tubes_content.nucleic_content',
                    'tubes.tubes_content.nucleic_content.environment_sample_types',
                    'tubes.tubes_content.nucleic_content.sampling_sites',
                    'tubes.tubes_content.protocols',
                ],
                'scope' => $baseScope([NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class])),
                'extraColumns' => [
                    [
                        'label' => 'Environment Sample Code',
                        'valuePath' => 'tubes.tubes_content.nucleic_content.code',
                        'filterModel' => 'originFilters.environmentCode',
                        'link' => '/samples/environment/{value}',
                    ],
                    [
                        'label' => 'Environment Sample Type',
                        'valuePath' => 'tubes.tubes_content.nucleic_content.environment_sample_types.name',
                        'filterModel' => 'originFilters.environmentSampleType',
                    ],
                    [
                        'label' => 'Sampling Site',
                        'valuePath' => 'tubes.tubes_content.nucleic_content.sampling_sites.name',
                        'filterModel' => 'originFilters.samplingSite',
                    ],
                    [
                        'label' => 'Date Collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.nucleic_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Nucleic Acid Type',
                        'valuePath' => 'tubes.tubes_content.type',
                        'filterModel' => 'originFilters.nucleicType',
                    ],
                    [
                        'label' => 'Extraction Protocol',
                        'html' => true,
                        'filterModel' => 'originFilters.protocol',
                        'value' => function (TubePositions $p): string {
                            $code = data_get($p, 'tubes.tubes_content.protocols.code');
                            $name = data_get($p, 'tubes.tubes_content.protocols.name');
                            if (! $code || ! $name) {
                                return '<span class="text-gray-500">N/A</span>';
                            }

                            return '<a href="'.e("/protocols/{$code}").'" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">'.e($name).'</a>';
                        },
                    ],
                    [
                        'label' => 'Date Extracted',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.date_extracted')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.extractedStart',
                        'filterModelEnd' => 'originFilters.extractedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $environmentCode = $this->originFilters['environmentCode'] ?? null;
                    if ($environmentCode) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class], fn ($eq) => $eq->where('code', 'like', '%'.$environmentCode.'%'))));
                    }

                    $environmentSampleType = $this->originFilters['environmentSampleType'] ?? null;
                    if ($environmentSampleType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class], fn ($eq) => $eq->whereHas('environment_sample_types', fn ($etq) => $etq->where('name', 'like', '%'.$environmentSampleType.'%')))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class], fn ($eq) => $eq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $nucleicType = $this->originFilters['nucleicType'] ?? null;
                    if ($nucleicType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$nucleicType.'%')));
                    }

                    $protocol = $this->originFilters['protocol'] ?? null;
                    if ($protocol) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], fn ($sq) => $sq->whereHas('protocols', fn ($pq) => $pq->where('name', 'like', '%'.$protocol.'%'))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class], function ($eq) use ($start, $end) {
                                if ($start && $end) {
                                    $eq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $eq->where('date_collected', '>=', $start);
                                } else {
                                    $eq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    $exStart = $this->originFilters['extractedStart'] ?? null;
                    $exEnd = $this->originFilters['extractedEnd'] ?? null;
                    if ($exStart || $exEnd) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [NucleicAcids::class], function ($sq) use ($exStart, $exEnd) {
                            if ($exStart && $exEnd) {
                                $sq->whereBetween('date_extracted', [$exStart, $exEnd]);
                            } elseif ($exStart) {
                                $sq->where('date_extracted', '>=', $exStart);
                            } else {
                                $sq->where('date_extracted', '<=', $exEnd);
                            }
                        }));
                    }

                    return $q;
                },
                'fileName' => 'nucleic_environment_tube_positions.csv',
                'csvHeaders' => ['Tube Code', 'Environment Sample Code', 'Environment Sample Type', 'Sampling Site', 'Date Collected', 'Nucleic Acid Type', 'Extraction Protocol', 'Date Extracted', 'Box Code', 'Position X', 'Position Y', 'Date Moved', 'Scientist', 'Reason'],
                'csvRow' => function (TubePositions $p): array {
                    return [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.nucleic_content.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.nucleic_content.environment_sample_types.name', 'N/A'),
                        data_get($p, 'tubes.tubes_content.nucleic_content.sampling_sites.name', 'N/A'),
                        data_get($p, 'tubes.tubes_content.nucleic_content.date_collected', 'N/A'),
                        data_get($p, 'tubes.tubes_content.type', 'N/A'),
                        data_get($p, 'tubes.tubes_content.protocols.name', 'N/A'),
                        data_get($p, 'tubes.tubes_content.date_extracted', 'N/A'),
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        data_get($p, 'date_moved', 'N/A'),
                        trim((data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];
                },
            ];
        }

        // ===== Culture derived tables =====
        if ($this->selectedTable === 'tube_positions_culture_human_table') {
            return [
                'tableId' => 'tube_positions_culture_human_table',
                'subtitle' => 'linked to cultures from human samples',
                'tubeListKey' => 'culture_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.cultures_content', 'tubes.tubes_content.cultures_content.sampling_sites'],
                'scope' => $baseScope([Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [HumanSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.cultureCode', 'link' => '/samples/cultures/{value}'],
                    ['label' => 'Medium', 'valuePath' => 'tubes.tubes_content.medium', 'filterModel' => 'originFilters.medium'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.cultureType'],
                    ['label' => 'Sample code', 'valuePath' => 'tubes.tubes_content.cultures_content.code', 'filterModel' => 'originFilters.humanSampleCode', 'link' => '/samples/humans/{value}'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.cultures_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.cultures_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $culture = $this->originFilters['cultureCode'] ?? null;
                    if ($culture) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('code', 'like', '%'.$culture.'%')));
                    }

                    $medium = $this->originFilters['medium'] ?? null;
                    if ($medium) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('medium', 'like', '%'.$medium.'%')));
                    }

                    $type = $this->originFilters['cultureType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%')));
                    }

                    $humanSample = $this->originFilters['humanSampleCode'] ?? null;
                    if ($humanSample) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [HumanSamples::class], fn ($hq) => $hq->where('code', 'like', '%'.$humanSample.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [HumanSamples::class], fn ($hq) => $hq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('cultures_content', [HumanSamples::class], function ($hq) use ($start, $end) {
                                if ($start && $end) {
                                    $hq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $hq->where('date_collected', '>=', $start);
                                } else {
                                    $hq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'culture_human_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Culture code', 'Medium', 'Type', 'Sample code', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.medium', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.sampling_sites.name', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.date_collected', 'N/A'),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    data_get($p, 'date_moved', 'N/A'),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        if ($this->selectedTable === 'tube_positions_culture_animal_table') {
            return [
                'tableId' => 'tube_positions_culture_animal_table',
                'subtitle' => 'linked to cultures from animal samples',
                'tubeListKey' => 'culture_tubes',
                'showBoxContentType' => false,
                'with' => [
                    'tubes.tubes_content.cultures_content',
                    'tubes.tubes_content.cultures_content.animals.animal_species',
                    'tubes.tubes_content.cultures_content.sample_types',
                    'tubes.tubes_content.cultures_content.sampling_sites',
                ],
                'scope' => $baseScope([Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [AnimalSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.cultureCode', 'link' => '/samples/cultures/{value}'],
                    ['label' => 'Medium', 'valuePath' => 'tubes.tubes_content.medium', 'filterModel' => 'originFilters.medium'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.cultureType'],
                    ['label' => 'Sample code', 'valuePath' => 'tubes.tubes_content.cultures_content.code', 'filterModel' => 'originFilters.animalSampleCode', 'link' => '/samples/animals/{value}'],
                    ['label' => 'Animal species', 'valuePath' => 'tubes.tubes_content.cultures_content.animals.animal_species.name_common', 'filterModel' => 'originFilters.animalSpecies'],
                    ['label' => 'Sample type', 'valuePath' => 'tubes.tubes_content.cultures_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.cultures_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.cultures_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $culture = $this->originFilters['cultureCode'] ?? null;
                    if ($culture) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('code', 'like', '%'.$culture.'%')));
                    }

                    $medium = $this->originFilters['medium'] ?? null;
                    if ($medium) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('medium', 'like', '%'.$medium.'%')));
                    }

                    $type = $this->originFilters['cultureType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%')));
                    }

                    $animalSample = $this->originFilters['animalSampleCode'] ?? null;
                    if ($animalSample) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [AnimalSamples::class], fn ($aq) => $aq->where('code', 'like', '%'.$animalSample.'%'))));
                    }

                    $animalSpecies = $this->originFilters['animalSpecies'] ?? null;
                    if ($animalSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [AnimalSamples::class], fn ($aq) => $aq->whereHas('animals.animal_species', fn ($asq) => $asq->where('name_common', 'like', '%'.$animalSpecies.'%')))));
                    }

                    $sampleType = $this->originFilters['sampleType'] ?? null;
                    if ($sampleType) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [AnimalSamples::class], fn ($aq) => $aq->whereHas('sample_types', fn ($stq) => $stq->where('name', 'like', '%'.$sampleType.'%')))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [AnimalSamples::class], fn ($aq) => $aq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('cultures_content', [AnimalSamples::class], function ($aq) use ($start, $end) {
                                if ($start && $end) {
                                    $aq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $aq->where('date_collected', '>=', $start);
                                } else {
                                    $aq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'culture_animal_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Culture code', 'Medium', 'Type', 'Sample code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.medium', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.animals.animal_species.name_common', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.sample_types.name', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.sampling_sites.name', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.date_collected', 'N/A'),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    data_get($p, 'date_moved', 'N/A'),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        if ($this->selectedTable === 'tube_positions_culture_environment_table') {
            return [
                'tableId' => 'tube_positions_culture_environment_table',
                'subtitle' => 'linked to cultures from environmental samples',
                'tubeListKey' => 'culture_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.cultures_content', 'tubes.tubes_content.cultures_content.sampling_sites'],
                'scope' => $baseScope([Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [EnvironmentSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.cultureCode', 'link' => '/samples/cultures/{value}'],
                    ['label' => 'Medium', 'valuePath' => 'tubes.tubes_content.medium', 'filterModel' => 'originFilters.medium'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.cultureType'],
                    ['label' => 'Environment code', 'valuePath' => 'tubes.tubes_content.cultures_content.code', 'filterModel' => 'originFilters.environmentCode', 'link' => '/samples/environment/{value}'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.cultures_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.cultures_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $culture = $this->originFilters['cultureCode'] ?? null;
                    if ($culture) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('code', 'like', '%'.$culture.'%')));
                    }

                    $medium = $this->originFilters['medium'] ?? null;
                    if ($medium) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('medium', 'like', '%'.$medium.'%')));
                    }

                    $type = $this->originFilters['cultureType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%')));
                    }

                    $environment = $this->originFilters['environmentCode'] ?? null;
                    if ($environment) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [EnvironmentSamples::class], fn ($eq) => $eq->where('code', 'like', '%'.$environment.'%'))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [EnvironmentSamples::class], fn ($eq) => $eq->whereHas('sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('cultures_content', [EnvironmentSamples::class], function ($eq) use ($start, $end) {
                                if ($start && $end) {
                                    $eq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $eq->where('date_collected', '>=', $start);
                                } else {
                                    $eq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'culture_environment_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Culture code', 'Medium', 'Type', 'Environment code', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.medium', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.sampling_sites.name', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.date_collected', 'N/A'),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    data_get($p, 'date_moved', 'N/A'),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        if ($this->selectedTable === 'tube_positions_culture_parasite_table') {
            return [
                'tableId' => 'tube_positions_culture_parasite_table',
                'subtitle' => 'linked to cultures from parasite samples',
                'tubeListKey' => 'culture_tubes',
                'showBoxContentType' => false,
                'with' => [
                    'tubes.tubes_content.cultures_content',
                    'tubes.tubes_content.cultures_content.parasites.parasite_species',
                    'tubes.tubes_content.cultures_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => $baseScope([Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [ParasiteSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.cultureCode', 'link' => '/samples/cultures/{value}'],
                    ['label' => 'Medium', 'valuePath' => 'tubes.tubes_content.medium', 'filterModel' => 'originFilters.medium'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.cultureType'],
                    ['label' => 'Sample code', 'valuePath' => 'tubes.tubes_content.cultures_content.code', 'filterModel' => 'originFilters.parasiteCode', 'link' => '/samples/parasites/{value}'],
                    ['label' => 'Parasite species', 'value' => fn (TubePositions $p): string => $this->parasiteSpeciesLabelHtml($p, 'tubes.tubes_content.cultures_content.parasites.parasite_species'), 'html' => true, 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Parasite sex', 'valuePath' => 'tubes.tubes_content.cultures_content.parasites.sex', 'filterModel' => 'originFilters.parasiteSex'],
                    ['label' => 'Parasite stage', 'valuePath' => 'tubes.tubes_content.cultures_content.parasites.stage', 'filterModel' => 'originFilters.parasiteStage'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes.tubes_content.cultures_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.cultures_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $culture = $this->originFilters['cultureCode'] ?? null;
                    if ($culture) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('code', 'like', '%'.$culture.'%')));
                    }

                    $medium = $this->originFilters['medium'] ?? null;
                    if ($medium) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('medium', 'like', '%'.$medium.'%')));
                    }

                    $type = $this->originFilters['cultureType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%')));
                    }

                    $parasite = $this->originFilters['parasiteCode'] ?? null;
                    if ($parasite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [ParasiteSamples::class], fn ($pq) => $pq->where('code', 'like', '%'.$parasite.'%'))));
                    }

                    $parasiteSpecies = $this->originFilters['parasiteSpecies'] ?? null;
                    if ($parasiteSpecies) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites.parasite_species', fn ($psq) => $this->applyParasiteSpeciesNameFilter($psq, (string) $parasiteSpecies)))));
                    }

                    $parasiteSex = $this->originFilters['parasiteSex'] ?? null;
                    if ($parasiteSex) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites', fn ($parQ) => $parQ->where('sex', 'like', '%'.$parasiteSex.'%')))));
                    }

                    $parasiteStage = $this->originFilters['parasiteStage'] ?? null;
                    if ($parasiteStage) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites', fn ($parQ) => $parQ->where('stage', 'like', '%'.$parasiteStage.'%')))));
                    }

                    $samplingSite = $this->originFilters['samplingSite'] ?? null;
                    if ($samplingSite) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [ParasiteSamples::class], fn ($pq) => $pq->whereHas('parasites.parasites_origin.sampling_sites', fn ($ssq) => $ssq->where('name', 'like', '%'.$samplingSite.'%')))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('cultures_content', [ParasiteSamples::class], function ($pq) use ($start, $end) {
                                if ($start && $end) {
                                    $pq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $pq->where('date_collected', '>=', $start);
                                } else {
                                    $pq->where('date_collected', '<=', $end);
                                }
                            });
                        }));
                    }

                    return $q;
                },
                'fileName' => 'culture_parasite_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Culture code', 'Medium', 'Type', 'Sample code', 'Parasite species', 'Parasite sex', 'Parasite stage', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => fn (TubePositions $p) => [
                    data_get($p, 'tubes.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    data_get($p, 'tubes.tubes_content.medium', 'N/A'),
                    data_get($p, 'tubes.tubes_content.type', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.code', 'N/A'),
                    $this->parasiteSpeciesLabel($p, 'tubes.tubes_content.cultures_content.parasites.parasite_species'),
                    data_get($p, 'tubes.tubes_content.cultures_content.parasites.sex', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.parasites.stage', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                    data_get($p, 'tubes.tubes_content.cultures_content.date_collected', 'N/A'),
                    data_get($p, 'boxes.code', 'N/A'),
                    data_get($p, 'position_x', 'N/A'),
                    data_get($p, 'position_y', 'N/A'),
                    data_get($p, 'date_moved', 'N/A'),
                    trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                    data_get($p, 'reason', 'N/A'),
                ],
            ];
        }

        if ($this->selectedTable === 'tube_positions_culture_pool_table') {
            return [
                'tableId' => 'tube_positions_culture_pool_table',
                'subtitle' => 'linked to cultures from pool samples',
                'tubeListKey' => 'culture_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.cultures_content', 'tubes.tubes_content.cultures_content.laboratories', 'tubes.tubes_content.cultures_content.pool_contents', 'tubes.tubes_content.cultures_content.pool_contents.samples'],
                'scope' => $baseScope([Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [Pools::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.cultureCode', 'link' => '/samples/cultures/{value}'],
                    ['label' => 'Medium', 'valuePath' => 'tubes.tubes_content.medium', 'filterModel' => 'originFilters.medium'],
                    ['label' => 'Type', 'valuePath' => 'tubes.tubes_content.type', 'filterModel' => 'originFilters.cultureType'],
                    ['label' => 'Pool code', 'valuePath' => 'tubes.tubes_content.cultures_content.code', 'filterModel' => 'originFilters.poolCode', 'link' => '/samples/pools/{value}'],
                    ['label' => 'Laboratory', 'valuePath' => 'tubes.tubes_content.cultures_content.laboratories.name', 'filterModel' => 'originFilters.laboratory'],
                    [
                        'label' => 'Date cultured',
                        'value' => fn (TubePositions $p): string => $dateYmd(data_get($p, 'tubes.tubes_content.cultures_content.date_cultured')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.culturedStart',
                        'filterModelEnd' => 'originFilters.culturedEnd',
                    ],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCollectedRangeForPoolModel(data_get($p, 'tubes.tubes_content.cultures_content')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (TubePositions $p): string => $this->poolContentsDetailsCombinedHtmlForPoolModel(
                            data_get($p, 'tubes.tubes_content.cultures_content'),
                            'culture-pool-'.$p->id.'-contents'
                        ),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $culture = $this->originFilters['cultureCode'] ?? null;
                    if ($culture) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('code', 'like', '%'.$culture.'%')));
                    }

                    $medium = $this->originFilters['medium'] ?? null;
                    if ($medium) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('medium', 'like', '%'.$medium.'%')));
                    }

                    $type = $this->originFilters['cultureType'] ?? null;
                    if ($type) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%')));
                    }

                    $pool = $this->originFilters['poolCode'] ?? null;
                    if ($pool) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [Pools::class], fn ($pq) => $pq->where('code', 'like', '%'.$pool.'%'))));
                    }

                    $laboratory = $this->originFilters['laboratory'] ?? null;
                    if ($laboratory) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [Pools::class], fn ($pq) => $pq->whereHas('laboratories', fn ($lq) => $lq->where('name', 'like', '%'.$laboratory.'%')))));
                    }

                    $start = $this->originFilters['culturedStart'] ?? null;
                    $end = $this->originFilters['culturedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function ($sq) use ($start, $end) {
                            $sq->whereHasMorph('cultures_content', [Pools::class], function ($pq) use ($start, $end) {
                                if ($start && $end) {
                                    $pq->whereBetween('date_cultured', [$start, $end]);
                                } elseif ($start) {
                                    $pq->where('date_cultured', '>=', $start);
                                } else {
                                    $pq->where('date_cultured', '<=', $end);
                                }
                            });
                        }));
                    }

                    $search = $this->originFilters['contentSearch'] ?? null;
                    if (filled($search)) {
                        $q->whereHas('tubes', function (Builder $tubeQ) use ($search): void {
                            $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function (Builder $sq) use ($search): void {
                                $sq->whereHasMorph('cultures_content', [Pools::class], function (Builder $pq) use ($search): void {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($search): void {
                                        $pcq->where(function (Builder $w) use ($search): void {
                                            $w->whereHasMorph('samples', [HumanSamples::class], fn (Builder $hq) => $hq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [AnimalSamples::class], fn (Builder $aq) => $aq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [EnvironmentSamples::class], fn (Builder $eq) => $eq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [ParasiteSamples::class], fn (Builder $pq2) => $pq2->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('code', 'like', '%'.$search.'%'))
                                                ->orWhereHasMorph('samples', [Pools::class], fn (Builder $plq) => $plq->where('code', 'like', '%'.$search.'%'));
                                        });
                                    });
                                });
                            });
                        });
                    }

                    $collectedStart = $this->originFilters['collectedStart'] ?? null;
                    $collectedEnd = $this->originFilters['collectedEnd'] ?? null;
                    if ($collectedStart || $collectedEnd) {
                        $q->whereHas('tubes', function (Builder $tubeQ) use ($collectedStart, $collectedEnd): void {
                            $tubeQ->whereHasMorph('tubes_content', [Cultures::class], function (Builder $sq) use ($collectedStart, $collectedEnd): void {
                                $sq->whereHasMorph('cultures_content', [Pools::class], function (Builder $pq) use ($collectedStart, $collectedEnd): void {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($collectedStart, $collectedEnd): void {
                                        $applyRange = function (Builder $q, string $column) use ($collectedStart, $collectedEnd): void {
                                            if ($collectedStart && $collectedEnd) {
                                                $q->whereBetween($column, [$collectedStart, $collectedEnd]);
                                            } elseif ($collectedStart) {
                                                $q->where($column, '>=', $collectedStart);
                                            } else {
                                                $q->where($column, '<=', $collectedEnd);
                                            }
                                        };

                                        $pcq->where(function (Builder $w) use ($applyRange): void {
                                            $w->whereHasMorph('samples', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $sq) use ($applyRange): void {
                                                $applyRange($sq, 'date_collected');
                                            })
                                                ->orWhereHasMorph('samples', [ParasiteSamples::class], function (Builder $sq) use ($applyRange): void {
                                                    $sq->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                                                })
                                                ->orWhereHasMorph('samples', [NucleicAcids::class], function (Builder $sq) use ($applyRange): void {
                                                    $sq->where(function (Builder $nq) use ($applyRange): void {
                                                        $nq->whereHasMorph('nucleic_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $cq) use ($applyRange): void {
                                                            $applyRange($cq, 'date_collected');
                                                        })->orWhereHasMorph('nucleic_content', [ParasiteSamples::class], function (Builder $cq) use ($applyRange): void {
                                                            $cq->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                                                        })->orWhereHasMorph('nucleic_content', [Cultures::class], function (Builder $cq) use ($applyRange): void {
                                                            $cq->whereHasMorph('cultures_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $ccq) use ($applyRange): void {
                                                                $applyRange($ccq, 'date_collected');
                                                            })->orWhereHasMorph('cultures_content', [ParasiteSamples::class], function (Builder $ccq) use ($applyRange): void {
                                                                $ccq->whereHas('parasites.parasites_origin', fn (Builder $oq) => $applyRange($oq, 'date_collected'));
                                                            });
                                                        });
                                                    });
                                                });
                                        });
                                    });
                                });
                            });
                        });
                    }

                    return $q;
                },
                'fileName' => 'culture_pool_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Culture code', 'Medium', 'Type', 'Pool code', 'Cultured at', 'Date cultured', 'Content type', 'Content code', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    /** @var Pools|null $pool */
                    $pool = data_get($p, 'tubes.tubes_content.cultures_content');
                    $contents = collect(data_get($pool, 'pool_contents', []))->filter(fn ($pc) => data_get($pc, 'samples') !== null)->values();

                    $base = [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.medium', 'N/A'),
                        data_get($p, 'tubes.tubes_content.type', 'N/A'),
                        data_get($pool, 'code', 'N/A'),
                        data_get($pool, 'laboratories.name', 'N/A'),
                        $dateYmd(data_get($pool, 'date_cultured')),
                    ];

                    $tail = [
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail): array {
                        $type = (string) (data_get($pc, 'samples_type') ?? '');
                        $typeLabel = $type ? str_replace('App\\Models\\', '', $type) : 'N/A';
                        $code = data_get($pc, 'samples.code') ?? 'N/A';
                        $primary = $this->poolContentPrimarySiteAndDate($pc);
                        $site = $primary['site'] ?? 'N/A';
                        $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                        return array_merge($base, [$typeLabel, $code, $site, $date], $tail);
                    })->all();
                },
            ];
        }

        // ===== Pool derived tables =====
        if ($this->selectedTable === 'tube_positions_pool_human_table') {
            return [
                'tableId' => 'tube_positions_pool_human_table',
                'subtitle' => 'linked to pools from human samples',
                'tubeListKey' => 'pool_tubes',
                'showBoxContentType' => true,
                'with' => ['tubes.tubes_content.pool_contents.samples.humans', 'tubes.tubes_content.pool_contents.samples.sampling_sites'],
                'scope' => $baseScope([Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [HumanSamples::class]))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.poolCode', 'link' => '/samples/pools/{value}'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCollectedRangeByType($p, HumanSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (TubePositions $p): string => $this->poolDerivedSubtableHtml($p, HumanSamples::class),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $pool = $this->originFilters['poolCode'] ?? null;
                    if ($pool) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$pool.'%')));
                    }

                    $search = $this->originFilters['contentSearch'] ?? null;
                    if (filled($search)) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [HumanSamples::class], function ($hq) use ($search) {
                            $hq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            });
                        }))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', function (Builder $tubeQ) use ($start, $end) {
                            $tubeQ->whereHasMorph('tubes_content', [Pools::class], function ($sq) use ($start, $end) {
                                $sq->whereHas('pool_contents', function ($pcq) use ($start, $end) {
                                    $pcq->whereHasMorph('samples', [HumanSamples::class], function ($hq) use ($start, $end) {
                                        if ($start && $end) {
                                            $hq->whereBetween('date_collected', [$start, $end]);
                                        } elseif ($start) {
                                            $hq->where('date_collected', '>=', $start);
                                        } else {
                                            $hq->where('date_collected', '<=', $end);
                                        }
                                    });
                                });
                            });
                        });
                    }

                    return $q;
                },
                'fileName' => 'pool_human_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Human sample code', 'Patient code', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    $contents = $this->poolContentsByType($p, HumanSamples::class);
                    $tail = [
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];

                    $base = [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail, $dateYmd): array {
                        $sampleCode = data_get($pc, 'samples.code') ?? 'N/A';
                        $patientCode = data_get($pc, 'samples.humans.code') ?? 'N/A';
                        $site = data_get($pc, 'samples.sampling_sites.name') ?? 'N/A';
                        $date = $dateYmd(data_get($pc, 'samples.date_collected'));

                        return array_merge($base, [$sampleCode, $patientCode, $site, $date], $tail);
                    })->all();
                },
            ];
        }

        if ($this->selectedTable === 'tube_positions_pool_animal_table') {
            return [
                'tableId' => 'tube_positions_pool_animal_table',
                'subtitle' => 'linked to pools from animal samples',
                'tubeListKey' => 'pool_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.pool_contents.samples.animals.animal_species', 'tubes.tubes_content.pool_contents.samples.sample_types', 'tubes.tubes_content.pool_contents.samples.sampling_sites'],
                'scope' => $baseScope([Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [AnimalSamples::class]))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.poolCode', 'link' => '/samples/pools/{value}'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCollectedRangeByType($p, AnimalSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (TubePositions $p): string => $this->poolDerivedSubtableHtml($p, AnimalSamples::class),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $pool = $this->originFilters['poolCode'] ?? null;
                    if ($pool) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$pool.'%')));
                    }

                    $search = $this->originFilters['contentSearch'] ?? null;
                    if (filled($search)) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [AnimalSamples::class], function ($aq) use ($search) {
                            $aq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('animals', fn (Builder $anq) => $anq->where('code', 'like', '%'.$search.'%'))
                                    ->orWhereHas('animals.animal_species', fn (Builder $asq) => $asq->where('name_common', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sample_types', fn (Builder $stq) => $stq->where('name', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            });
                        }))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', function (Builder $tubeQ) use ($start, $end) {
                            $tubeQ->whereHasMorph('tubes_content', [Pools::class], function ($sq) use ($start, $end) {
                                $sq->whereHas('pool_contents', function ($pcq) use ($start, $end) {
                                    $pcq->whereHasMorph('samples', [AnimalSamples::class], function ($aq) use ($start, $end) {
                                        if ($start && $end) {
                                            $aq->whereBetween('date_collected', [$start, $end]);
                                        } elseif ($start) {
                                            $aq->where('date_collected', '>=', $start);
                                        } else {
                                            $aq->where('date_collected', '<=', $end);
                                        }
                                    });
                                });
                            });
                        });
                    }

                    return $q;
                },
                'fileName' => 'pool_animal_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Animal sample code', 'Animal code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    $contents = $this->poolContentsByType($p, AnimalSamples::class);
                    $tail = [
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];

                    $base = [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail, $dateYmd): array {
                        $sampleCode = data_get($pc, 'samples.code') ?? 'N/A';
                        $animalCode = data_get($pc, 'samples.animals.code') ?? 'N/A';
                        $species = data_get($pc, 'samples.animals.animal_species.name_common') ?? 'N/A';
                        $sampleType = data_get($pc, 'samples.sample_types.name') ?? 'N/A';
                        $site = data_get($pc, 'samples.sampling_sites.name') ?? 'N/A';
                        $date = $dateYmd(data_get($pc, 'samples.date_collected'));

                        return array_merge($base, [$sampleCode, $animalCode, $species, $sampleType, $site, $date], $tail);
                    })->all();
                },
            ];
        }

        if ($this->selectedTable === 'tube_positions_pool_environment_table') {
            return [
                'tableId' => 'tube_positions_pool_environment_table',
                'subtitle' => 'linked to pools from environmental samples',
                'tubeListKey' => 'pool_tubes',
                'showBoxContentType' => true,
                'with' => ['tubes.tubes_content.pool_contents', 'tubes.tubes_content.pool_contents.samples'],
                'scope' => $baseScope([Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [EnvironmentSamples::class]))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.poolCode', 'link' => '/samples/pools/{value}'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCollectedRangeByType($p, EnvironmentSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (TubePositions $p): string => $this->poolDerivedSubtableHtml($p, EnvironmentSamples::class),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $pool = $this->originFilters['poolCode'] ?? null;
                    if ($pool) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$pool.'%')));
                    }

                    $search = $this->originFilters['contentSearch'] ?? null;
                    if (filled($search)) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [EnvironmentSamples::class], function ($eq) use ($search) {
                            $eq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('environment_sample_types', fn (Builder $tq) => $tq->where('name', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            });
                        }))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [EnvironmentSamples::class], function ($eq) use ($start, $end) {
                            if ($start && $end) {
                                $eq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $eq->where('date_collected', '>=', $start);
                            } else {
                                $eq->where('date_collected', '<=', $end);
                            }
                        }))));
                    }

                    return $q;
                },
                'fileName' => 'pool_environment_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Environment code', 'Sample type', 'Sampling site', 'Date collected', 'Box code', 'Box content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    $contents = $this->poolContentsByType($p, EnvironmentSamples::class);
                    $tail = [
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'boxes.content_type', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];

                    $base = [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail, $dateYmd): array {
                        $sampleCode = data_get($pc, 'samples.code') ?? 'N/A';
                        $type = data_get($pc, 'samples.environment_sample_types.name') ?? 'N/A';
                        $site = data_get($pc, 'samples.sampling_sites.name') ?? 'N/A';
                        $date = $dateYmd(data_get($pc, 'samples.date_collected'));

                        return array_merge($base, [$sampleCode, $type, $site, $date], $tail);
                    })->all();
                },
            ];
        }

        if ($this->selectedTable === 'tube_positions_pool_parasite_table') {
            return [
                'tableId' => 'tube_positions_pool_parasite_table',
                'subtitle' => 'linked to pools from parasite samples',
                'tubeListKey' => 'pool_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.pool_contents', 'tubes.tubes_content.pool_contents.samples'],
                'scope' => $baseScope([Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [ParasiteSamples::class]))),
                'extraColumns' => [
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (TubePositions $p): string => $this->poolContentsCollectedRangeByType($p, ParasiteSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (TubePositions $p): string => $this->poolDerivedSubtableHtml($p, ParasiteSamples::class),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $search = $this->originFilters['contentSearch'] ?? null;
                    if (filled($search)) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [ParasiteSamples::class], function ($pq) use ($search) {
                            $pq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'))
                                    ->orWhereHas('parasites', fn (Builder $parq) => $parq->where('sex', 'like', '%'.$search.'%')->orWhere('stage', 'like', '%'.$search.'%'))
                                    ->orWhereHas('parasites', function (Builder $parQ) use ($search): void {
                                        $parQ->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%')));
                                    });
                            });
                        }))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [ParasiteSamples::class], function ($pq) use ($start, $end) {
                            if ($start && $end) {
                                $pq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $pq->where('date_collected', '>=', $start);
                            } else {
                                $pq->where('date_collected', '<=', $end);
                            }
                        }))));
                    }

                    return $q;
                },
                'fileName' => 'pool_parasite_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Parasite sample code', 'Tick species', 'Tick sex', 'Tick stage', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    $contents = $this->poolContentsByType($p, ParasiteSamples::class);
                    $tail = [
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];

                    $base = [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail, $dateYmd): array {
                        $sampleCode = data_get($pc, 'samples.code') ?? 'N/A';
                        $species = data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A';
                        $sex = data_get($pc, 'samples.parasites.sex') ?? 'N/A';
                        $stage = data_get($pc, 'samples.parasites.stage') ?? 'N/A';
                        $site = data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? 'N/A';
                        $date = $dateYmd(data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected'));

                        return array_merge($base, [$sampleCode, $species, $sex, $stage, $site, $date], $tail);
                    })->all();
                },
            ];
        }

        if ($this->selectedTable === 'tube_positions_pool_nucleic_table') {
            return [
                'tableId' => 'tube_positions_pool_nucleic_table',
                'subtitle' => 'linked to pools from nucleic acids',
                'tubeListKey' => 'pool_tubes',
                'showBoxContentType' => false,
                'with' => ['tubes.tubes_content.pool_contents', 'tubes.tubes_content.pool_contents.samples'],
                'scope' => $baseScope([Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [NucleicAcids::class]))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'valuePath' => 'tubes.tubes_content.code', 'filterModel' => 'originFilters.poolCode', 'link' => '/samples/pools/{value}'],
                    [
                        'label' => 'Date collected',
                        'value' => function (TubePositions $p): string {
                            $dates = $this->poolContentsByType($p, NucleicAcids::class)
                                ->map(function ($pc) {
                                    $type = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');

                                    return match ($type) {
                                        HumanSamples::class,
                                        AnimalSamples::class,
                                        EnvironmentSamples::class => data_get($pc, 'samples.nucleic_content.date_collected'),
                                        ParasiteSamples::class => data_get($pc, 'samples.nucleic_content.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.nucleic_content.date_collected'),
                                        Cultures::class => match ((string) (data_get($pc, 'samples.nucleic_content.cultures_content_type') ?? '')) {
                                            HumanSamples::class,
                                            AnimalSamples::class,
                                            EnvironmentSamples::class => data_get($pc, 'samples.nucleic_content.cultures_content.date_collected'),
                                            ParasiteSamples::class => data_get($pc, 'samples.nucleic_content.cultures_content.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.nucleic_content.cultures_content.date_collected'),
                                            default => null,
                                        },
                                        default => null,
                                    };
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
                        },
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (TubePositions $p): string => $this->poolDerivedSubtableHtml($p, NucleicAcids::class),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => function (Builder $q) {
                    $pool = $this->originFilters['poolCode'] ?? null;
                    if ($pool) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$pool.'%')));
                    }

                    $search = $this->originFilters['contentSearch'] ?? null;
                    if (filled($search)) {
                        $q->whereHas('tubes', fn (Builder $tubeQ) => $tubeQ->whereHasMorph('tubes_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [NucleicAcids::class], function ($nq) use ($search) {
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
                                        ], fn (Builder $cq) => $cq->where('code', 'like', '%'.$search.'%'))
                                        ->orWhereHasMorph('nucleic_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $cq) => $cq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%')))
                                        ->orWhereHasMorph('nucleic_content', [ParasiteSamples::class], fn (Builder $pq) => $pq->whereHas('parasites', function (Builder $parq) use ($search) {
                                            $parq->where('sex', 'like', '%'.$search.'%')
                                                ->orWhere('stage', 'like', '%'.$search.'%')
                                                ->orWhereHas('parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'));
                                        }));
                                });

                                return;
                            }

                            $nq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhere('type', 'like', '%'.$search.'%')
                                    ->orWhereHasMorph('nucleic_content', [
                                        HumanSamples::class,
                                        AnimalSamples::class,
                                        EnvironmentSamples::class,
                                    ], function (Builder $cq) use ($search) {
                                        $cq->where('code', 'like', '%'.$search.'%')
                                            ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                                    })
                                    ->orWhereHasMorph('nucleic_content', [ParasiteSamples::class], function (Builder $pq) use ($search) {
                                        $pq->where('code', 'like', '%'.$search.'%')
                                            ->orWhereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'))
                                            ->orWhereHas('parasites', fn (Builder $parq) => $parq->where('sex', 'like', '%'.$search.'%')->orWhere('stage', 'like', '%'.$search.'%'))
                                            ->orWhereHas('parasites', function (Builder $parQ) use ($search): void {
                                                $parQ->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%')));
                                            });
                                    })
                                    ->orWhereHasMorph('nucleic_content', [Cultures::class], function (Builder $cq) use ($search) {
                                        $cq->where('code', 'like', '%'.$search.'%')
                                            ->orWhereHasMorph('cultures_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], function (Builder $ccq) use ($search) {
                                                $ccq->where('code', 'like', '%'.$search.'%')
                                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                                            })
                                            ->orWhereHasMorph('cultures_content', [ParasiteSamples::class], function (Builder $pcq2) use ($search) {
                                                $pcq2->where('code', 'like', '%'.$search.'%')
                                                    ->orWhereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'))
                                                    ->orWhereHas('parasites', fn (Builder $parq) => $parq->where('sex', 'like', '%'.$search.'%')->orWhere('stage', 'like', '%'.$search.'%'))
                                                    ->orWhereHas('parasites', fn (Builder $parQ) => $parQ->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'))));
                                            });
                                    });
                            });
                        }))));
                    }

                    $start = $this->originFilters['collectedStart'] ?? null;
                    $end = $this->originFilters['collectedEnd'] ?? null;
                    if ($start || $end) {
                        $q->whereHas('tubes', function (Builder $tubeQ) use ($start, $end) {
                            $tubeQ->whereHasMorph('tubes_content', [Pools::class], function ($sq) use ($start, $end) {
                                $sq->whereHas('pool_contents', function ($pcq) use ($start, $end) {
                                    $pcq->whereHasMorph('samples', [NucleicAcids::class], function ($nq) use ($start, $end) {
                                        $nq->whereHasMorph('nucleic_content', [
                                            HumanSamples::class,
                                            AnimalSamples::class,
                                            EnvironmentSamples::class,
                                            ParasiteSamples::class,
                                        ], function (Builder $cq) use ($start, $end) {
                                            if ($start && $end) {
                                                $cq->whereBetween('date_collected', [$start, $end]);
                                            } elseif ($start) {
                                                $cq->where('date_collected', '>=', $start);
                                            } else {
                                                $cq->where('date_collected', '<=', $end);
                                            }
                                        });
                                    });
                                });
                            });
                        });
                    }

                    return $q;
                },
                'fileName' => 'pool_nucleic_tube_positions.csv',
                'csvHeaders' => ['Tube code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Content type', 'Content code', 'Sampling site', 'Date collected', 'Box code', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
                'csvRow' => function (TubePositions $p) use ($dateYmd): array {
                    $contents = $this->poolContentsByType($p, NucleicAcids::class);

                    $tail = [
                        data_get($p, 'boxes.code', 'N/A'),
                        data_get($p, 'position_x', 'N/A'),
                        data_get($p, 'position_y', 'N/A'),
                        $dateYmd(data_get($p, 'date_moved')),
                        trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                        data_get($p, 'reason', 'N/A'),
                    ];

                    $base = [
                        data_get($p, 'tubes.code', 'N/A'),
                        data_get($p, 'tubes.tubes_content.code', 'N/A'),
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail): array {
                        $nucleicCode = data_get($pc, 'samples.code') ?? 'N/A';
                        $nucleicType = data_get($pc, 'samples.type') ?? 'N/A';
                        $contentType = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');
                        $contentTypeLabel = $contentType ? str_replace('App\\Models\\', '', $contentType) : 'N/A';
                        $contentCode = data_get($pc, 'samples.nucleic_content.code') ?? 'N/A';
                        $primary = $this->poolContentPrimarySiteAndDate($pc);
                        $site = $primary['site'] ?? 'N/A';
                        $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                        return array_merge($base, [$nucleicCode, $nucleicType, $contentTypeLabel, $contentCode, $site, $date], $tail);
                    })->all();
                },
            ];
        }

        return [
            'tableId' => 'tube_positions_table',
            'subtitle' => 'all tube positions',
            'tubeListKey' => 'tubes',
            'showBoxContentType' => true,
            'scope' => $baseScope([
                HumanSamples::class,
                AnimalSamples::class,
                EnvironmentSamples::class,
                ParasiteSamples::class,
                Cultures::class,
                Pools::class,
                NucleicAcids::class,
            ]),
            'extraColumns' => [],
            'fileName' => 'tube_positions.csv',
            'csvHeaders' => ['Tube code', 'Box code', 'Content type', 'X position', 'Y position', 'Date moved', 'Moved by', 'Reason moved'],
            'csvRow' => fn (TubePositions $p) => [
                data_get($p, 'tubes.code', 'N/A'),
                data_get($p, 'boxes.code', 'N/A'),
                data_get($p, 'boxes.content_type', 'N/A'),
                data_get($p, 'position_x', 'N/A'),
                data_get($p, 'position_y', 'N/A'),
                $dateYmd(data_get($p, 'date_moved')),
                trim((data_get($p, 'people.title') ?? '').' '.(data_get($p, 'people.first_name') ?? '').' '.(data_get($p, 'people.last_name') ?? '')) ?: 'N/A',
                data_get($p, 'reason', 'N/A'),
            ],
        ];
    }

    public function render()
    {
        $tubesService = app(TubesService::class);
        $boxesService = app(BoxesService::class);

        $additionalData = array_merge(
            $tubesService->assign(),
            $boxesService->assign()
        );

        $tableConfig = $this->withStorageColumns($this->selectedTableConfig());
        $tubePositions = $this->buildBaseQueryForSelectedTable()->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('tube_positions');

        return view('livewire.tube-positions-index', array_merge($additionalData, [
            'tube_positions' => $tubePositions,
            'tableConfig' => $tableConfig,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'canEdit' => $canEdit,
            'isGuestMode' => $this->isGuestMode(),
        ]));
    }
}
