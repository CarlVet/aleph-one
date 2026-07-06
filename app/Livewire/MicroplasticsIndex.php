<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\MicroplasticsForm;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Microplastics;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Microplastics Index')]
class MicroplasticsIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithPagination;

    public MicroplasticsForm $form;

    protected function sortingPageName(): ?string
    {
        return 'articles-page';
    }

    /**
     * @return array<string, string|callable>
     */
    protected function sortMap(): array
    {
        return [
            'code' => 'code',
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'type' => fn ($q, $dir) => $this->orderByRelation($q, ['mps_types'], 'name', $dir),
            'protocol' => fn ($q, $dir) => $this->orderByRelation($q, ['protocols'], 'name', $dir),
            'laboratory' => fn ($q, $dir) => $this->orderByRelation($q, ['laboratories'], 'name', $dir),
            'identifier' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'last_name', $dir),
            'identification_date' => 'identification_date',
            'sample_weight' => 'sample_weight',
            'r_coeff' => 'r_coeff',
            'm_feret' => 'm_feret',
        ];
    }

    public string $selectedTable = 'microplastics_table';

    public bool $isEditing = false;

    public array $selectedMicroplastics = [];

    public bool $selectAllFiltered = false;

    public $codeFilter = '';

    public $typeFilter = '';

    public $protocolFilter = '';

    public $laboratoryFilter = '';

    public $sourceFilter = '';

    public $identifierFilter = '';

    public $subProjectFilter = '';

    public array $sourceSpecificFilters = [];

    protected ?int $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function updating($field = null): void
    {
        // Toggling a row checkbox (or select-all) must not reset pagination or
        // wipe the current selection.
        if (is_string($field) && (str_starts_with($field, 'selectedMicroplastics') || $field === 'selectAllFiltered')) {
            return;
        }

        $this->resetPage('articles-page');
        $this->selectedMicroplastics = [];
        $this->selectAllFiltered = false;
    }

    public function updatedSelectedTable(): void
    {
        $this->selectedMicroplastics = [];
        $this->selectAllFiltered = false;
        $this->sourceSpecificFilters = [];
        $this->resetPage('articles-page');
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedMicroplastics = [];

            return;
        }

        $ids = $this->filteredQuery()
            ->pluck('microplastics.id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $this->selectedMicroplastics = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    public function updatedSelectedMicroplastics(): void
    {
        $ids = $this->filteredQuery()
            ->pluck('microplastics.id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($ids === []) {
            $this->selectAllFiltered = false;

            return;
        }

        $selectedIds = collect($this->selectedMicroplastics)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->all();

        $this->selectAllFiltered = array_diff($ids, $selectedIds) === [];
    }

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

    public function canEdit(): bool
    {
        return ! $this->isGuestMode() && Auth::check() && $this->userCanWriteModule('microplastics');
    }

    public function toggleEditMode(): void
    {
        if (! $this->canEdit()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You do not have permission to edit microplastics records.',
            ]);

            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function updateField(int $microplasticId, string $field, mixed $value): void
    {
        $microplastic = Microplastics::query()->find($microplasticId);
        if (! $microplastic || ! $this->userCanMutateOwnedRecord((int) $microplastic->people_id, 'microplastics')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only edit records you registered.',
            ]);

            return;
        }

        $result = $this->form->updateField($microplasticId, $field, $value);

        $this->dispatch('swal', [
            'icon' => $result['ok'] ? 'success' : 'error',
            'title' => $result['ok'] ? 'Success' : 'Error',
            'text' => $result['message'],
        ]);
    }

    public function delete(int $microplasticId): void
    {
        $microplastic = Microplastics::query()->find($microplasticId);
        if (! $microplastic || ! $this->userCanMutateOwnedRecord((int) $microplastic->people_id, 'microplastics')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only delete records you registered.',
            ]);

            return;
        }

        $microplastic->delete();
        $this->form->refreshData();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Microplastics record deleted successfully!',
        ]);
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedMicroplastics)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No selection',
                'text' => 'Please select at least one microplastics record.',
            ]);

            return;
        }

        /** @var EloquentCollection<int, Microplastics> $records */
        $records = Microplastics::query()->whereIn('id', $selectedIds->all())->get();
        $deleted = 0;

        foreach ($records as $record) {
            if (! $this->userCanMutateOwnedRecord((int) $record->people_id, 'microplastics')) {
                continue;
            }

            $record->delete();
            $deleted++;
        }

        $this->selectedMicroplastics = [];
        $this->selectAllFiltered = false;
        $this->form->refreshData();

        $this->dispatch('swal', [
            'icon' => $deleted > 0 ? 'success' : 'error',
            'title' => $deleted > 0 ? 'Success' : 'Nothing deleted',
            'text' => $deleted > 0
                ? "{$deleted} selected microplastics record(s) deleted successfully."
                : 'No selected microplastics records could be deleted.',
        ]);
    }

    public function export(): StreamedResponse
    {
        $records = $this->filteredQuery()
            ->orderByDesc('id')
            ->get();

        $filename = 'microplastics_export_'.now()->format('Y-m-d_H-i-s').'.csv';
        $sourceSpecificColumns = $this->sourceSpecificColumns();
        $sourceSpecificHeaders = array_map(
            fn (array $column): string => (string) ($column['key'] ?? 'source_specific'),
            $sourceSpecificColumns
        );

        return response()->streamDownload(function () use ($records, $sourceSpecificHeaders): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_merge([
                'code',
                'source_type',
                'source_code',
            ], $sourceSpecificHeaders, [
                'sub_project',
                'mps_type',
                'protocol',
                'laboratory',
                'identified_by',
                'identification_date',
                'sample_weight',
                'r_coeff',
                'm_feret',
            ]));

            foreach ($records as $record) {
                fputcsv($handle, array_merge([
                    $record->code,
                    class_basename((string) $record->microplastics_content_type),
                    $record->microplastics_content?->code,
                ], $this->sourceSpecificExportValues($record), [
                    $record->subProject?->code,
                    $record->mps_types?->name,
                    $record->protocols?->name,
                    $record->laboratories?->name,
                    trim(($record->people?->title ?? '').' '.($record->people?->first_name ?? '').' '.($record->people?->last_name ?? '')),
                    optional($record->identification_date)?->format('Y-m-d'),
                    $record->sample_weight,
                    $record->r_coeff,
                    $record->m_feret,
                ]));
            }

            fclose($handle);
        }, $filename);
    }

    public function render()
    {
        $records = $this->applySorting($this->filteredQuery(), $this->sortMap(), ['id', 'desc'])
            ->paginate($this->perPage, ['*'], 'articles-page');

        $sourceSpecificColumns = $this->sourceSpecificColumns();
        $sourceSpecificCellsByRecord = $records->getCollection()
            ->mapWithKeys(fn (Microplastics $record): array => [
                $record->id => $this->sourceSpecificCells($record),
            ])
            ->all();

        return view('livewire.microplastics-index', [
            'records' => $records,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $this->canEdit(),
            'showBulkActions' => $this->canEdit() && ! $this->isGuestMode(),
            'selectedTableLabel' => $this->selectedTableLabel(),
            'sourceSpecificColumns' => $sourceSpecificColumns,
            'sourceSpecificFilterDefinitions' => $this->sourceSpecificFilterDefinitions(),
            'sourceSpecificCellsByRecord' => $sourceSpecificCellsByRecord,
            'availableTypes' => $this->filteredQuery(['typeFilter'])->select('mps_types.name')->leftJoin('mps_types', 'microplastics.mps_types_id', '=', 'mps_types.id')->distinct()->orderBy('mps_types.name')->pluck('mps_types.name')->filter()->values(),
            'availableProtocols' => $this->filteredQuery(['protocolFilter'])->select('protocols.name')->leftJoin('protocols', 'microplastics.protocols_id', '=', 'protocols.id')->distinct()->orderBy('protocols.name')->pluck('protocols.name')->filter()->values(),
            'availableLaboratories' => $this->filteredQuery(['laboratoryFilter'])->select('laboratories.name')->leftJoin('laboratories', 'microplastics.laboratories_id', '=', 'laboratories.id')->distinct()->orderBy('laboratories.name')->pluck('laboratories.name')->filter()->values(),
            'availableIdentifiers' => $this->filteredQuery(['identifierFilter'])
                ->get()
                ->map(fn (Microplastics $record) => trim(($record->people?->title ?? '').' '.($record->people?->first_name ?? '').' '.($record->people?->last_name ?? '')))
                ->unique()
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values(),
        ]);
    }

    private function filteredQuery(array $except = []): Builder
    {
        $query = Microplastics::query()
            ->with([
                'mps_types',
                'protocols',
                'laboratories',
                'people',
                'projects',
                'subProjectAssignment.subProject',
                'tubes',
                'microplastics_content' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => [
                            'humans.countries',
                            'sampling_sites',
                        ],
                        AnimalSamples::class => [
                            'animals.animal_species',
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
                        Pools::class => [
                            'pool_contents.samples' => function (MorphTo $nestedMorphTo): void {
                                $nestedMorphTo->morphWith([
                                    HumanSamples::class => [
                                        'humans.countries',
                                        'sampling_sites',
                                    ],
                                    AnimalSamples::class => [
                                        'animals.animal_species',
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
                                        'nucleic_content' => function (MorphTo $originMorphTo): void {
                                            $originMorphTo->morphWith([
                                                HumanSamples::class => [
                                                    'humans.countries',
                                                    'sampling_sites',
                                                ],
                                                AnimalSamples::class => [
                                                    'animals.animal_species',
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
                                            ]);
                                        },
                                    ],
                                    Cultures::class => [
                                        'cultures_content' => function (MorphTo $originMorphTo): void {
                                            $originMorphTo->morphWith([
                                                HumanSamples::class => [
                                                    'humans.countries',
                                                    'sampling_sites',
                                                ],
                                                AnimalSamples::class => [
                                                    'animals.animal_species',
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
                                            ]);
                                        },
                                    ],
                                ]);
                            },
                        ],
                    ]);
                },
            ]);

        $query->whereIn('microplastics_content_type', $this->allowedSourceTypes());

        $this->applySelectedTableFilter($query);

        if ($this->projectId) {
            $query->where('projects_id', $this->projectId);
        } else {
            $query->where('is_private', false);
        }

        if (! in_array('codeFilter', $except, true) && $this->codeFilter !== '') {
            $query->where('code', 'like', '%'.$this->codeFilter.'%');
        }

        if (! in_array('typeFilter', $except, true) && $this->typeFilter !== '') {
            $query->whereHas('mps_types', fn (Builder $mpsTypeQuery) => $mpsTypeQuery->where('name', 'like', '%'.$this->typeFilter.'%'));
        }

        if (! in_array('protocolFilter', $except, true) && $this->protocolFilter !== '') {
            $query->whereHas('protocols', fn (Builder $protocolQuery) => $protocolQuery->where('name', 'like', '%'.$this->protocolFilter.'%'));
        }

        if (! in_array('laboratoryFilter', $except, true) && $this->laboratoryFilter !== '') {
            $query->whereHas('laboratories', fn (Builder $labQuery) => $labQuery->where('name', 'like', '%'.$this->laboratoryFilter.'%'));
        }

        if (! in_array('sourceFilter', $except, true) && $this->sourceFilter !== '') {
            $query->where('microplastics_content_type', 'like', '%'.$this->sourceFilter.'%');
        }

        if (! in_array('identifierFilter', $except, true) && $this->identifierFilter !== '') {
            $query->whereHas('people', function (Builder $peopleQuery): void {
                $needle = '%'.$this->identifierFilter.'%';
                $peopleQuery->where(function (Builder $nestedQuery) use ($needle): void {
                    $nestedQuery
                        ->where('title', 'like', $needle)
                        ->orWhere('first_name', 'like', $needle)
                        ->orWhere('last_name', 'like', $needle);
                });
            });
        }

        if (! in_array('subProjectFilter', $except, true) && $this->subProjectFilter !== '') {
            $query->whereHas('subProjectAssignment.subProject', fn (Builder $subProjectQuery) => $subProjectQuery->where('code', 'like', '%'.$this->subProjectFilter.'%'));
        }

        $this->applySourceSpecificFilters($query);

        return $query;
    }

    private function applySelectedTableFilter(Builder $query): void
    {
        $sourceType = $this->selectedTableSourceType();

        if ($sourceType !== null) {
            $query->where('microplastics_content_type', $sourceType);
        }
    }

    private function selectedTableSourceType(): ?string
    {
        return match ($this->selectedTable) {
            'microplastics_human_table' => HumanSamples::class,
            'microplastics_animal_table' => AnimalSamples::class,
            'microplastics_environment_table' => EnvironmentSamples::class,
            'microplastics_parasite_table' => ParasiteSamples::class,
            'microplastics_pool_table' => Pools::class,
            default => null,
        };
    }

    private function selectedTableLabel(): string
    {
        return match ($this->selectedTable) {
            'microplastics_human_table' => 'Human-derived Microplastics',
            'microplastics_animal_table' => 'Animal-derived Microplastics',
            'microplastics_environment_table' => 'Environment-derived Microplastics',
            'microplastics_parasite_table' => 'Parasite-derived Microplastics',
            'microplastics_pool_table' => 'Pool-derived Microplastics',
            default => 'List of Microplastics',
        };
    }

    public function sourceProfileUrl(Microplastics $record): ?string
    {
        $code = $record->microplastics_content?->code;
        if (! $code) {
            return null;
        }

        return match ($record->microplastics_content_type) {
            HumanSamples::class => '/samples/humans/'.$code,
            AnimalSamples::class => '/samples/animals/'.$code,
            EnvironmentSamples::class => '/samples/environment/'.$code,
            ParasiteSamples::class => '/samples/parasites/'.$code,
            Pools::class => '/samples/pools/'.$code,
            default => null,
        };
    }

    public function sourceSpecificColumns(): array
    {
        return match ($this->selectedTable) {
            'microplastics_animal_table' => [
                ['key' => 'animal_species', 'label' => 'Animal species', 'minWidth' => 'min-w-[180px]'],
                ['key' => 'animal_sex', 'label' => 'Sex', 'minWidth' => 'min-w-[120px]'],
                ['key' => 'animal_age', 'label' => 'Age', 'minWidth' => 'min-w-[130px]'],
                ['key' => 'animal_sampling_site', 'label' => 'Sampling site', 'minWidth' => 'min-w-[220px]'],
                ['key' => 'animal_collection_date', 'label' => 'Collection date', 'minWidth' => 'min-w-[170px]'],
            ],
            'microplastics_human_table' => [
                ['key' => 'human_occupation', 'label' => 'Occupation', 'minWidth' => 'min-w-[180px]'],
                ['key' => 'human_country', 'label' => 'Country', 'minWidth' => 'min-w-[180px]'],
                ['key' => 'human_sex', 'label' => 'Sex', 'minWidth' => 'min-w-[120px]'],
                ['key' => 'human_age', 'label' => 'Age', 'minWidth' => 'min-w-[120px]'],
                ['key' => 'human_sampling_site', 'label' => 'Sampling site', 'minWidth' => 'min-w-[220px]'],
                ['key' => 'human_collection_date', 'label' => 'Collection date', 'minWidth' => 'min-w-[170px]'],
            ],
            'microplastics_environment_table' => [
                ['key' => 'environment_sample_type', 'label' => 'Sample type', 'minWidth' => 'min-w-[180px]'],
                ['key' => 'environment_area', 'label' => 'Area', 'minWidth' => 'min-w-[180px]'],
                ['key' => 'environment_sampling_site', 'label' => 'Sampling site', 'minWidth' => 'min-w-[220px]'],
                ['key' => 'environment_collection_date', 'label' => 'Collection date', 'minWidth' => 'min-w-[170px]'],
            ],
            'microplastics_parasite_table' => [
                ['key' => 'parasite_species', 'label' => 'Parasite species', 'minWidth' => 'min-w-[220px]'],
                ['key' => 'parasite_stage', 'label' => 'Stage', 'minWidth' => 'min-w-[130px]'],
                ['key' => 'parasite_sex', 'label' => 'Sex', 'minWidth' => 'min-w-[120px]'],
                ['key' => 'parasite_sampling_site', 'label' => 'Sampling site', 'minWidth' => 'min-w-[220px]'],
                ['key' => 'parasite_collection_date', 'label' => 'Collection date', 'minWidth' => 'min-w-[170px]'],
            ],
            'microplastics_pool_table' => [
                ['key' => 'pool_content_details', 'label' => 'Content details', 'minWidth' => 'min-w-[360px]'],
                ['key' => 'pool_sampling_sites', 'label' => 'Sampling site(s)', 'minWidth' => 'min-w-[240px]'],
                ['key' => 'pool_collection_dates', 'label' => 'Collection date(s)', 'minWidth' => 'min-w-[200px]'],
            ],
            default => [],
        };
    }

    public function sourceSpecificFilterDefinitions(): array
    {
        return array_map(
            function (array $column): array {
                $key = (string) ($column['key'] ?? '');
                $isDateRange = str_contains($key, 'collection_date');

                if ($isDateRange) {
                    return [
                        'type' => 'date_range',
                        'startKey' => $key.'_start',
                        'endKey' => $key.'_end',
                    ];
                }

                return [
                    'type' => 'text',
                    'key' => $key,
                    'placeholder' => 'Filter',
                ];
            },
            $this->sourceSpecificColumns(),
        );
    }

    public function sourceSpecificCells(Microplastics $record): array
    {
        $content = $record->microplastics_content;

        if (! $content) {
            return array_map(fn (): array => $this->textCell(null, 'No related data registered'), $this->sourceSpecificColumns());
        }

        return match ($this->selectedTable) {
            'microplastics_animal_table' => [
                $this->textCell(data_get($content, 'animals.animal_species.name_common'), 'No related data registered'),
                $this->textCell(data_get($content, 'animals.sex'), 'No related data registered'),
                $this->textCell(data_get($content, 'animals.age'), 'No related data registered'),
                $this->textCell(data_get($content, 'sampling_sites.name'), 'No related data registered'),
                $this->textCell($this->formatDateYmd(data_get($content, 'date_collected')), 'No related data registered'),
            ],
            'microplastics_human_table' => [
                $this->textCell(data_get($content, 'humans.occupation'), 'No related data registered'),
                $this->textCell(data_get($content, 'humans.countries.name'), 'No related data registered'),
                $this->textCell(data_get($content, 'humans.sex'), 'No related data registered'),
                $this->textCell($this->humanAge((string) data_get($content, 'humans.date_of_birth')), 'No related data registered'),
                $this->textCell(data_get($content, 'sampling_sites.name'), 'No related data registered'),
                $this->textCell($this->formatDateYmd(data_get($content, 'date_collected')), 'No related data registered'),
            ],
            'microplastics_environment_table' => [
                $this->textCell(data_get($content, 'environment_sample_types.name'), 'No related data registered'),
                $this->textCell(data_get($content, 'area'), 'No related data registered'),
                $this->textCell(data_get($content, 'sampling_sites.name'), 'No related data registered'),
                $this->textCell($this->formatDateYmd(data_get($content, 'date_collected')), 'No related data registered'),
            ],
            'microplastics_parasite_table' => [
                $this->textCell(data_get($content, 'parasites.parasite_species.name_scientific'), 'No related data registered'),
                $this->textCell(data_get($content, 'parasites.stage'), 'No related data registered'),
                $this->textCell(data_get($content, 'parasites.sex'), 'No related data registered'),
                $this->textCell(data_get($content, 'parasites.parasites_origin.sampling_sites.name'), 'No related data registered'),
                $this->textCell($this->formatDateYmd(data_get($content, 'parasites.parasites_origin.date_collected')), 'No related data registered'),
            ],
            'microplastics_pool_table' => [
                $this->htmlCell($this->poolContentDetailsTableHtml($content instanceof Pools ? $content : null)),
                $this->htmlCell($this->poolContentMetadataHtml($content instanceof Pools ? $content : null, 'site')),
                $this->htmlCell($this->poolContentMetadataHtml($content instanceof Pools ? $content : null, 'date')),
            ],
            default => [],
        };
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

    private function textCell(mixed $value, string $emptyLabel = 'N/A'): array
    {
        return [
            'value' => $this->normalizeDisplayValue($value) ?? $emptyLabel,
            'html' => false,
        ];
    }

    private function htmlCell(string $value): array
    {
        return [
            'value' => $value,
            'html' => true,
        ];
    }

    private function normalizeDisplayValue(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        $text = trim((string) $value);

        return $text === '' || strtoupper($text) === 'N/A' ? null : $text;
    }

    private function humanAge(string $birthDate): string
    {
        if (trim($birthDate) === '') {
            return 'N/A';
        }

        try {
            return (string) Carbon::parse($birthDate)->age;
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    private function formatDateYmd(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return 'N/A';
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    private function poolContentDetailsTableHtml(?Pools $pool): string
    {
        $contents = $pool?->pool_contents;

        if (! $contents || $contents->isEmpty()) {
            return '<div class="min-w-[340px] rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center text-sm text-gray-500">No related data registered</div>';
        }

        $rows = $contents->map(function (PoolContents $content): string {
            $sample = $content->samples;
            $sampleType = class_basename((string) $content->samples_type) ?: 'N/A';
            $sampleCode = (string) (data_get($sample, 'code') ?? 'N/A');
            $details = $this->poolContentDetailsText($content);

            return '<tr class="divide-x divide-gray-100">'
                .'<td class="px-3 py-2 text-left text-gray-700">'.e($sampleType).'</td>'
                .'<td class="px-3 py-2 text-left font-medium text-gray-900">'.e($sampleCode).'</td>'
                .'<td class="px-3 py-2 text-left text-gray-700">'.e($details).'</td>'
                .'</tr>';
        })->implode('');

        return '<div class="min-w-[340px] overflow-hidden rounded-lg border border-gray-200">'
            .'<table class="min-w-full divide-y divide-gray-200 text-xs">'
            .'<thead class="bg-gray-50"><tr>'
            .'<th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-600">Type</th>'
            .'<th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-600">Code</th>'
            .'<th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-600">Details</th>'
            .'</tr></thead>'
            .'<tbody class="divide-y divide-gray-100 bg-white">'.$rows.'</tbody>'
            .'</table>'
            .'</div>';
    }

    private function poolContentDetailsText(PoolContents $content): string
    {
        $sample = $content->samples;

        return match ($content->samples_type) {
            HumanSamples::class => 'Occupation: '.((string) (data_get($sample, 'humans.occupation') ?? 'N/A')).', Country: '.((string) (data_get($sample, 'humans.countries.name') ?? 'N/A')),
            AnimalSamples::class => 'Species: '.((string) (data_get($sample, 'animals.animal_species.name_common') ?? 'N/A')).', Sex: '.((string) (data_get($sample, 'animals.sex') ?? 'N/A')).', Age: '.((string) (data_get($sample, 'animals.age') ?? 'N/A')),
            EnvironmentSamples::class => 'Sample type: '.((string) (data_get($sample, 'environment_sample_types.name') ?? 'N/A')).', Area: '.((string) (data_get($sample, 'area') ?? 'N/A')),
            ParasiteSamples::class => 'Species: '.((string) (data_get($sample, 'parasites.parasite_species.name_scientific') ?? 'N/A')).', Stage: '.((string) (data_get($sample, 'parasites.stage') ?? 'N/A')),
            NucleicAcids::class => 'Nucleic type: '.((string) (data_get($sample, 'type') ?? 'N/A')).', Extracted: '.$this->formatDateYmd(data_get($sample, 'date_extracted')),
            Cultures::class => 'Medium: '.((string) (data_get($sample, 'medium') ?? 'N/A')).', Step: '.((string) (data_get($sample, 'step') ?? 'N/A')),
            Pools::class => 'Nr pooled: '.((string) (data_get($sample, 'nr_pooled') ?? 'N/A')).', Date pooled: '.$this->formatDateYmd(data_get($sample, 'date_pooled')),
            default => 'N/A',
        };
    }

    private function poolContentMetadataHtml(?Pools $pool, string $type): string
    {
        $contents = $pool?->pool_contents;

        if (! $contents || $contents->isEmpty()) {
            return '<span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">No related data registered</span>';
        }

        $values = $contents
            ->flatMap(fn (PoolContents $content): array => $this->contentMetadataValues($content->samples, (string) $content->samples_type, $type))
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return '<span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">No related data registered</span>';
        }

        return '<div class="flex min-w-[220px] flex-col items-center gap-1">'
            .$values->map(fn (string $value): string => '<span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">'.e($value).'</span>')->implode('')
            .'</div>';
    }

    private function contentMetadataValues(mixed $content, string $contentType, string $type): array
    {
        if (! $content) {
            return [];
        }

        return match ($contentType) {
            HumanSamples::class => $this->directMetadataValues($type, data_get($content, 'sampling_sites.name'), data_get($content, 'date_collected')),
            AnimalSamples::class => $this->directMetadataValues($type, data_get($content, 'sampling_sites.name'), data_get($content, 'date_collected')),
            EnvironmentSamples::class => $this->directMetadataValues($type, data_get($content, 'sampling_sites.name'), data_get($content, 'date_collected')),
            ParasiteSamples::class => $this->directMetadataValues($type, data_get($content, 'parasites.parasites_origin.sampling_sites.name'), data_get($content, 'parasites.parasites_origin.date_collected')),
            NucleicAcids::class => $this->contentMetadataValues(data_get($content, 'nucleic_content'), (string) data_get($content, 'nucleic_content_type'), $type),
            Cultures::class => $this->contentMetadataValues(data_get($content, 'cultures_content'), (string) data_get($content, 'cultures_content_type'), $type),
            default => [],
        };
    }

    private function directMetadataValues(string $type, mixed $site, mixed $date): array
    {
        $value = $type === 'site'
            ? $this->normalizeDisplayValue($site)
            : $this->normalizeDisplayValue($this->formatDateYmd($date));

        return $value ? [$value] : [];
    }

    private function sourceSpecificExportValues(Microplastics $record): array
    {
        $content = $record->microplastics_content;

        if (! $content) {
            return array_fill(0, count($this->sourceSpecificColumns()), '');
        }

        return match ($this->selectedTable) {
            'microplastics_animal_table' => [
                (string) (data_get($content, 'animals.animal_species.name_common') ?? ''),
                (string) (data_get($content, 'animals.sex') ?? ''),
                (string) (data_get($content, 'animals.age') ?? ''),
                (string) (data_get($content, 'sampling_sites.name') ?? ''),
                $this->exportDateValue(data_get($content, 'date_collected')),
            ],
            'microplastics_human_table' => [
                (string) (data_get($content, 'humans.occupation') ?? ''),
                (string) (data_get($content, 'humans.countries.name') ?? ''),
                (string) (data_get($content, 'humans.sex') ?? ''),
                $this->exportBlankableValue($this->humanAge((string) data_get($content, 'humans.date_of_birth'))),
                (string) (data_get($content, 'sampling_sites.name') ?? ''),
                $this->exportDateValue(data_get($content, 'date_collected')),
            ],
            'microplastics_environment_table' => [
                (string) (data_get($content, 'environment_sample_types.name') ?? ''),
                (string) (data_get($content, 'area') ?? ''),
                (string) (data_get($content, 'sampling_sites.name') ?? ''),
                $this->exportDateValue(data_get($content, 'date_collected')),
            ],
            'microplastics_parasite_table' => [
                (string) (data_get($content, 'parasites.parasite_species.name_scientific') ?? ''),
                (string) (data_get($content, 'parasites.stage') ?? ''),
                (string) (data_get($content, 'parasites.sex') ?? ''),
                (string) (data_get($content, 'parasites.parasites_origin.sampling_sites.name') ?? ''),
                $this->exportDateValue(data_get($content, 'parasites.parasites_origin.date_collected')),
            ],
            'microplastics_pool_table' => [
                $content instanceof Pools ? $this->poolContentDetailsCsvValue($content) : '',
                $content instanceof Pools ? $this->poolContentMetadataCsvValue($content, 'site') : '',
                $content instanceof Pools ? $this->poolContentMetadataCsvValue($content, 'date') : '',
            ],
            default => [],
        };
    }

    private function exportDateValue(mixed $value): string
    {
        $formatted = $this->formatDateYmd($value);

        return $formatted === 'N/A' ? '' : $formatted;
    }

    private function exportBlankableValue(string $value): string
    {
        return $value === 'N/A' ? '' : $value;
    }

    private function poolContentDetailsCsvValue(Pools $pool): string
    {
        $contents = $pool->pool_contents;

        if ($contents->isEmpty()) {
            return '';
        }

        return $contents
            ->map(function (PoolContents $content): string {
                $sampleType = class_basename((string) $content->samples_type) ?: 'Unknown';
                $sampleCode = (string) (data_get($content->samples, 'code') ?? '');
                $details = $this->poolContentDetailsText($content);

                return trim($sampleType.' '.$sampleCode.' '.$details);
            })
            ->filter()
            ->implode(' | ');
    }

    private function poolContentMetadataCsvValue(Pools $pool, string $type): string
    {
        return $pool->pool_contents
            ->flatMap(fn (PoolContents $content): array => $this->contentMetadataValues($content->samples, (string) $content->samples_type, $type))
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->unique()
            ->implode(' | ');
    }

    private function applySourceSpecificFilters(Builder $query): void
    {
        $filters = collect($this->sourceSpecificFilters)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '');

        if ($filters->isEmpty()) {
            return;
        }

        match ($this->selectedTable) {
            'microplastics_animal_table' => $this->applyAnimalFilters($query, $filters->all()),
            'microplastics_human_table' => $this->applyHumanFilters($query, $filters->all()),
            'microplastics_environment_table' => $this->applyEnvironmentFilters($query, $filters->all()),
            'microplastics_parasite_table' => $this->applyParasiteFilters($query, $filters->all()),
            'microplastics_pool_table' => $this->applyPoolFilters($query, $filters->all()),
            default => null,
        };
    }

    private function applyAnimalFilters(Builder $query, array $filters): void
    {
        $query->whereHasMorph('microplastics_content', [AnimalSamples::class], function (Builder $animalQuery) use ($filters): void {
            foreach ($filters as $key => $value) {
                match ($key) {
                    'animal_species' => $animalQuery->whereHas('animals.animal_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_common', 'like', '%'.$value.'%')),
                    'animal_sex' => $animalQuery->whereHas('animals', fn (Builder $animalsQuery) => $animalsQuery->where('sex', 'like', '%'.$value.'%')),
                    'animal_age' => $animalQuery->whereHas('animals', fn (Builder $animalsQuery) => $animalsQuery->where('age', 'like', '%'.$value.'%')),
                    'animal_sampling_site' => $animalQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')),
                    'animal_collection_date_start' => $animalQuery->whereDate('date_collected', '>=', $value),
                    'animal_collection_date_end' => $animalQuery->whereDate('date_collected', '<=', $value),
                    default => null,
                };
            }
        });
    }

    private function applyHumanFilters(Builder $query, array $filters): void
    {
        $query->whereHasMorph('microplastics_content', [HumanSamples::class], function (Builder $humanQuery) use ($filters): void {
            foreach ($filters as $key => $value) {
                match ($key) {
                    'human_occupation' => $humanQuery->whereHas('humans', fn (Builder $humansQuery) => $humansQuery->where('occupation', 'like', '%'.$value.'%')),
                    'human_country' => $humanQuery->whereHas('humans.countries', fn (Builder $countryQuery) => $countryQuery->where('name', 'like', '%'.$value.'%')),
                    'human_sex' => $humanQuery->whereHas('humans', fn (Builder $humansQuery) => $humansQuery->where('sex', 'like', '%'.$value.'%')),
                    'human_age' => $humanQuery->whereHas('humans', fn (Builder $humansQuery) => $this->applyHumanAgeFilterToHumansQuery($humansQuery, $value)),
                    'human_sampling_site' => $humanQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')),
                    'human_collection_date_start' => $humanQuery->whereDate('date_collected', '>=', $value),
                    'human_collection_date_end' => $humanQuery->whereDate('date_collected', '<=', $value),
                    default => null,
                };
            }
        });
    }

    private function applyEnvironmentFilters(Builder $query, array $filters): void
    {
        $query->whereHasMorph('microplastics_content', [EnvironmentSamples::class], function (Builder $environmentQuery) use ($filters): void {
            foreach ($filters as $key => $value) {
                match ($key) {
                    'environment_sample_type' => $environmentQuery->whereHas('environment_sample_types', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', '%'.$value.'%')),
                    'environment_area' => $environmentQuery->where('area', 'like', '%'.$value.'%'),
                    'environment_sampling_site' => $environmentQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')),
                    'environment_collection_date_start' => $environmentQuery->whereDate('date_collected', '>=', $value),
                    'environment_collection_date_end' => $environmentQuery->whereDate('date_collected', '<=', $value),
                    default => null,
                };
            }
        });
    }

    private function applyParasiteFilters(Builder $query, array $filters): void
    {
        $query->whereHasMorph('microplastics_content', [ParasiteSamples::class], function (Builder $parasiteQuery) use ($filters): void {
            foreach ($filters as $key => $value) {
                match ($key) {
                    'parasite_species' => $parasiteQuery->whereHas('parasites.parasite_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_scientific', 'like', '%'.$value.'%')->orWhere('name_common', 'like', '%'.$value.'%')),
                    'parasite_stage' => $parasiteQuery->whereHas('parasites', fn (Builder $parasitesQuery) => $parasitesQuery->where('stage', 'like', '%'.$value.'%')),
                    'parasite_sex' => $parasiteQuery->whereHas('parasites', fn (Builder $parasitesQuery) => $parasitesQuery->where('sex', 'like', '%'.$value.'%')),
                    'parasite_sampling_site' => $parasiteQuery->whereHas('parasites.parasites_origin.sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')),
                    'parasite_collection_date_start' => $parasiteQuery->whereHas('parasites.parasites_origin', fn (Builder $originQuery) => $originQuery->whereDate('date_collected', '>=', $value)),
                    'parasite_collection_date_end' => $parasiteQuery->whereHas('parasites.parasites_origin', fn (Builder $originQuery) => $originQuery->whereDate('date_collected', '<=', $value)),
                    default => null,
                };
            }
        });
    }

    private function applyPoolFilters(Builder $query, array $filters): void
    {
        $query->whereHasMorph('microplastics_content', [Pools::class], function (Builder $poolQuery) use ($filters): void {
            foreach ($filters as $key => $value) {
                match ($key) {
                    'pool_content_details' => $poolQuery->whereHas('pool_contents', fn (Builder $poolContentsQuery) => $this->applyPoolContentDetailsFilter($poolContentsQuery, $value)),
                    'pool_sampling_sites' => $poolQuery->whereHas('pool_contents', fn (Builder $poolContentsQuery) => $this->applyPoolContentSamplingSiteFilter($poolContentsQuery, $value)),
                    'pool_collection_dates_start' => $poolQuery->whereHas('pool_contents', fn (Builder $poolContentsQuery) => $this->applyPoolContentCollectionDateFilter($poolContentsQuery, $value, '>=')),
                    'pool_collection_dates_end' => $poolQuery->whereHas('pool_contents', fn (Builder $poolContentsQuery) => $this->applyPoolContentCollectionDateFilter($poolContentsQuery, $value, '<=')),
                    default => null,
                };
            }
        });
    }

    private function applyPoolContentDetailsFilter(Builder $poolContentsQuery, string $value): void
    {
        $poolContentsQuery->where(function (Builder $query) use ($value): void {
            $query
                ->whereHasMorph('samples', [HumanSamples::class], function (Builder $humanQuery) use ($value): void {
                    $humanQuery->where('code', 'like', '%'.$value.'%')
                        ->orWhereHas('humans', fn (Builder $humansQuery) => $humansQuery->where('occupation', 'like', '%'.$value.'%'))
                        ->orWhereHas('humans.countries', fn (Builder $countryQuery) => $countryQuery->where('name', 'like', '%'.$value.'%'));
                })
                ->orWhereHasMorph('samples', [AnimalSamples::class], function (Builder $animalQuery) use ($value): void {
                    $animalQuery->where('code', 'like', '%'.$value.'%')
                        ->orWhereHas('animals.animal_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_common', 'like', '%'.$value.'%'))
                        ->orWhereHas('animals', fn (Builder $animalsQuery) => $animalsQuery->where('sex', 'like', '%'.$value.'%')->orWhere('age', 'like', '%'.$value.'%'));
                })
                ->orWhereHasMorph('samples', [EnvironmentSamples::class], function (Builder $environmentQuery) use ($value): void {
                    $environmentQuery->where('code', 'like', '%'.$value.'%')
                        ->orWhereHas('environment_sample_types', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', '%'.$value.'%'))
                        ->orWhere('area', 'like', '%'.$value.'%');
                })
                ->orWhereHasMorph('samples', [ParasiteSamples::class], function (Builder $parasiteQuery) use ($value): void {
                    $parasiteQuery->where('code', 'like', '%'.$value.'%')
                        ->orWhereHas('parasites.parasite_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_scientific', 'like', '%'.$value.'%')->orWhere('name_common', 'like', '%'.$value.'%'))
                        ->orWhereHas('parasites', fn (Builder $parasitesQuery) => $parasitesQuery->where('stage', 'like', '%'.$value.'%')->orWhere('sex', 'like', '%'.$value.'%'));
                })
                ->orWhereHasMorph('samples', [NucleicAcids::class], function (Builder $nucleicQuery) use ($value): void {
                    $nucleicQuery->where('code', 'like', '%'.$value.'%')
                        ->orWhere('type', 'like', '%'.$value.'%')
                        ->orWhere('date_extracted', 'like', '%'.$value.'%');
                })
                ->orWhereHasMorph('samples', [Cultures::class], function (Builder $cultureQuery) use ($value): void {
                    $cultureQuery->where('code', 'like', '%'.$value.'%')
                        ->orWhere('medium', 'like', '%'.$value.'%')
                        ->orWhere('step', 'like', '%'.$value.'%');
                })
                ->orWhereHasMorph('samples', [Pools::class], function (Builder $nestedPoolQuery) use ($value): void {
                    $nestedPoolQuery->where('code', 'like', '%'.$value.'%')
                        ->orWhere('nr_pooled', 'like', '%'.$value.'%')
                        ->orWhere('date_pooled', 'like', '%'.$value.'%');
                });
        });
    }

    private function applyPoolContentSamplingSiteFilter(Builder $poolContentsQuery, string $value): void
    {
        $poolContentsQuery->where(function (Builder $query) use ($value): void {
            $query
                ->whereHasMorph('samples', [HumanSamples::class], fn (Builder $humanQuery) => $humanQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')))
                ->orWhereHasMorph('samples', [AnimalSamples::class], fn (Builder $animalQuery) => $animalQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')))
                ->orWhereHasMorph('samples', [EnvironmentSamples::class], fn (Builder $environmentQuery) => $environmentQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')))
                ->orWhereHasMorph('samples', [ParasiteSamples::class], fn (Builder $parasiteQuery) => $parasiteQuery->whereHas('parasites.parasites_origin.sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')))
                ->orWhereHasMorph('samples', [NucleicAcids::class], fn (Builder $nucleicQuery) => $this->applyNestedOriginSamplingSiteFilter($nucleicQuery, $value, 'nucleic_content'))
                ->orWhereHasMorph('samples', [Cultures::class], fn (Builder $cultureQuery) => $this->applyNestedOriginSamplingSiteFilter($cultureQuery, $value, 'cultures_content'));
        });
    }

    private function applyPoolContentCollectionDateFilter(Builder $poolContentsQuery, string $value, string $operator): void
    {
        $poolContentsQuery->where(function (Builder $query) use ($value, $operator): void {
            $query
                ->whereHasMorph('samples', [HumanSamples::class], fn (Builder $humanQuery) => $humanQuery->whereDate('date_collected', $operator, $value))
                ->orWhereHasMorph('samples', [AnimalSamples::class], fn (Builder $animalQuery) => $animalQuery->whereDate('date_collected', $operator, $value))
                ->orWhereHasMorph('samples', [EnvironmentSamples::class], fn (Builder $environmentQuery) => $environmentQuery->whereDate('date_collected', $operator, $value))
                ->orWhereHasMorph('samples', [ParasiteSamples::class], fn (Builder $parasiteQuery) => $parasiteQuery->whereHas('parasites.parasites_origin', fn (Builder $originQuery) => $originQuery->whereDate('date_collected', $operator, $value)))
                ->orWhereHasMorph('samples', [NucleicAcids::class], fn (Builder $nucleicQuery) => $this->applyNestedOriginCollectionDateFilter($nucleicQuery, $value, 'nucleic_content', $operator))
                ->orWhereHasMorph('samples', [Cultures::class], fn (Builder $cultureQuery) => $this->applyNestedOriginCollectionDateFilter($cultureQuery, $value, 'cultures_content', $operator));
        });
    }

    private function applyNestedOriginSamplingSiteFilter(Builder $query, string $value, string $relation): void
    {
        $query->whereHasMorph($relation, [HumanSamples::class], fn (Builder $originQuery) => $originQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')))
            ->orWhereHasMorph($relation, [AnimalSamples::class], fn (Builder $originQuery) => $originQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')))
            ->orWhereHasMorph($relation, [EnvironmentSamples::class], fn (Builder $originQuery) => $originQuery->whereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')))
            ->orWhereHasMorph($relation, [ParasiteSamples::class], fn (Builder $originQuery) => $originQuery->whereHas('parasites.parasites_origin.sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%')));
    }

    private function applyNestedOriginCollectionDateFilter(Builder $query, string $value, string $relation, string $operator): void
    {
        $query->whereHasMorph($relation, [HumanSamples::class], fn (Builder $originQuery) => $originQuery->whereDate('date_collected', $operator, $value))
            ->orWhereHasMorph($relation, [AnimalSamples::class], fn (Builder $originQuery) => $originQuery->whereDate('date_collected', $operator, $value))
            ->orWhereHasMorph($relation, [EnvironmentSamples::class], fn (Builder $originQuery) => $originQuery->whereDate('date_collected', $operator, $value))
            ->orWhereHasMorph($relation, [ParasiteSamples::class], fn (Builder $originQuery) => $originQuery->whereHas('parasites.parasites_origin', fn (Builder $parasiteOriginQuery) => $parasiteOriginQuery->whereDate('date_collected', $operator, $value)));
    }

    private function applyHumanAgeFilterToHumansQuery(Builder $humansQuery, string $value): void
    {
        $value = trim($value);

        if ($value === '') {
            return;
        }

        if (! ctype_digit($value)) {
            $humansQuery->where('date_of_birth', 'like', '%'.$value.'%');

            return;
        }

        $age = (int) $value;
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $humansQuery->whereRaw(
                "CAST((julianday('now') - julianday(date_of_birth)) / 365.25 AS INTEGER) = ?",
                [$age]
            );

            return;
        }

        $humansQuery->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) = ?', [$age]);
    }
}
