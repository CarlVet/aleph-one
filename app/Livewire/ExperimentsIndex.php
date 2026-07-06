<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\ExperimentsForm;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Services\ExperimentsService;
use App\Support\ProjectPermission;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExperimentsIndex extends PlainComponent
{
    use ExportsTable;
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    protected ?int $projectId;

    /**
     * @return array<string, string|callable>
     */
    protected function sortMap(): array
    {
        return [
            'code' => 'code',
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'protocol' => fn ($q, $dir) => $this->orderByRelation($q, ['protocols'], 'name', $dir),
            'protocol_type' => fn ($q, $dir) => $this->orderByRelation($q, ['protocols', 'techniques'], 'type', $dir),
            'pathogen' => fn ($q, $dir) => $this->orderByRelation($q, ['pathogens'], 'species', $dir),
            'outcome_discrete' => 'outcome_discrete',
            'outcome_quant' => 'outcome_quant',
            'purpose' => 'purpose',
            'date_tested' => 'date_tested',
            'performed_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'last_name', $dir),
            'performed_at' => fn ($q, $dir) => $this->orderByRelation($q, ['laboratories'], 'name', $dir),
        ];
    }

    public ExperimentsForm $form;

    public $photo;

    public $bulkPhoto;

    public $uploadingPhotoId = null;

    public $uploadErrors = [];

    public $currentPhotoId = null;

    public array $selectedExperiments = [];

    public bool $selectAllFiltered = false;

    public $experimentIdFilter;

    public $projectFilter;

    public $protocolFilter;

    public $protocolTypeFilter;

    public $pathogenFilter;

    public $discreteFilter;

    public $quantitativeFilter;

    public $purposeFilter;

    public $photoFilter;

    public $startDate;

    public $endDate;

    public $scientistFilter;

    public $placeFilter;

    public $subProjectCodeFilter;

    /**
     * Table-specific filters.
     *
     * Used by the shared partial via `wire:model="originFilters.*"`.
     */
    public array $originFilters = [];

    public bool $isEditing = false;

    public string $selectedTable = 'experiments_table';

    // Properties for edit validation + datalists
    public $exp_protocols;

    public $pathogens;

    public $laboratories_by_country;

    public $people;

    public ?int $photoPreviewExperimentId = null;

    public ?string $photoPreviewUrl = null;

    public ?string $photoPreviewCode = null;

    public bool $photoPreviewCanDelete = false;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function mount(): void
    {
        // Allow guest-only routes to land directly on a specific tab
        $routeName = request()->route()?->getName();

        $this->selectedTable = match ($routeName) {
            'guest.experiments.human' => 'experiment_human_table',
            'guest.experiments.animal' => 'experiment_animal_table',
            'guest.experiments.environment' => 'experiment_environment_table',
            'guest.experiments.parasite' => 'experiment_parasite_table',
            'guest.experiments.nucleic' => 'experiment_nucleic_table',
            'guest.experiments.culture' => 'experiment_culture_table',
            'guest.experiments.pool' => 'experiment_pool_table',
            default => $this->selectedTable,
        };
    }

    public function updatedSelectedTable(): void
    {
        $this->originFilters = [];
        $this->selectedExperiments = [];
        $this->selectAllFiltered = false;
        $this->reset('bulkPhoto');
        $this->resetPage('articles-page');
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedExperiments = [];

            return;
        }

        $query = $this->buildBaseQueryForSelectedTable();

        if (! $this->canMutateAnyExperimentRecord()) {
            $currentPeopleId = $this->currentPeopleId();
            if (! $currentPeopleId) {
                $this->selectedExperiments = [];

                return;
            }

            $query->where('experiments.people_id', (int) $currentPeopleId);
        }

        $ids = $query
            ->pluck('experiments.id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $this->selectedExperiments = $ids
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
    }

    public function updatedBulkPhoto(): void
    {
        if ($this->bulkPhoto) {
            $this->uploadPhotoToSelected();
        }
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

        return $this->userCanWriteModule('experiments');
    }

    public function canFilterBrokenPhotos(): bool
    {
        if ($this->isGuestMode() || $this->projectId === null) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return ProjectPermission::canAssignRegistrar($user, (int) $this->projectId);
    }

    public function canMutateAnyExperimentRecord(): bool
    {
        if ($this->isGuestMode() || $this->projectId === null) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return ProjectPermission::canAssignRegistrar($user, (int) $this->projectId);
    }

    public function canMutateExperimentRecord(?int $ownerPeopleId): bool
    {
        if ($this->isGuestMode()) {
            return false;
        }

        return $this->userCanMutateOwnedRecord($ownerPeopleId, 'experiments');
    }

    public function updatedPhotoFilter($value): void
    {
        if ($value === 'broken' && ! $this->canFilterBrokenPhotos()) {
            $this->photoFilter = '';
        }
    }

    public function toggleEditMode(): void
    {
        if (! $this->userCanWriteModule('experiments')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You do not have permission to edit experiments in this project.',
            ]);

            return;
        }

        $this->isEditing = ! $this->isEditing;
        $this->dispatch('edit-mode-toggled', isEditing: $this->isEditing);
    }

    public function updateField($experimentId, $field, $value): void
    {
        $experiment = Experiments::find($experimentId);
        if ($field === 'people_id' && ! $this->canMutateAnyExperimentRecord()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'Only project admins can edit the Performed by field.',
            ]);

            return;
        }

        if (! $experiment || ! $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only edit records you registered.',
            ]);

            return;
        }

        $result = $this->form->updateField($experimentId, $field, $value);

        $this->dispatch('swal', [
            'icon' => $result['ok'] ? 'success' : 'error',
            'title' => $result['ok'] ? 'Success' : 'Error',
            'text' => $result['message'],
        ]);
    }

    public function delete(Experiments $experiment): void
    {
        if (! $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only delete records you registered.',
            ]);

            return;
        }

        $experiment->delete();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Experiment deleted successfully!',
        ]);
    }

    public function deleteSelected(): void
    {
        if ($this->isGuestMode() || ! $this->canEdit()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You do not have permission to delete experiments in this list.',
            ]);

            return;
        }

        $selectedIds = collect($this->selectedExperiments)
            ->filter(fn ($checked) => (bool) $checked)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($selectedIds->isEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No items selected',
                'text' => 'Please check at least one experiment before deleting.',
            ]);

            return;
        }

        $deleted = 0;
        $skipped = 0;

        Experiments::query()
            ->whereIn('id', $selectedIds->all())
            ->get()
            ->each(function (Experiments $experiment) use (&$deleted, &$skipped): void {
                if (! $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments')) {
                    $skipped++;

                    return;
                }

                $experiment->delete();
                $deleted++;
            });

        $this->selectedExperiments = [];

        if ($deleted === 0 && $skipped > 0) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You are not allowed to delete these experiment(s) because you have not registered them.',
            ]);

            return;
        }

        $message = "Deleted {$deleted} experiment(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} experiment(s) were not deleted because you have not registered them.";
        }

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Done',
            'text' => $message,
        ]);
    }

    public function updating($field)
    {
        if (
            is_string($field)
            && (
                str_starts_with($field, 'selectedExperiments')
                || $field === 'selectAllFiltered'
                || str_starts_with($field, 'bulkPhoto')
            )
        ) {
            return;
        }

        // Reset pagination whenever a filter changes
        $this->resetPage('articles-page');
    }

    protected function applyFilters(Builder $query): Builder
    {
        // Apply filters dynamically if they exist
        if ($this->experimentIdFilter) {
            $query->where('code', 'like', '%'.$this->experimentIdFilter.'%');
        }
        if ($this->projectFilter && $this->isGuestMode()) {
            $query->whereHas('projects', function ($q) {
                $q->where('code', 'like', '%'.$this->projectFilter.'%');
            });
        }
        if ($this->protocolFilter) {
            $query->whereHas('protocols', function ($q) {
                $q->where('name', 'like', '%'.$this->protocolFilter.'%');
            });
        }
        if ($this->protocolTypeFilter) {
            $query->whereHas('protocols.techniques', function ($q) {
                $q->where('type', 'like', '%'.$this->protocolTypeFilter.'%');
            });
        }
        if ($this->pathogenFilter) {
            $query->whereHas('pathogens', function ($q) {
                $q->where('species', 'like', '%'.$this->pathogenFilter.'%');
            });
        }
        if ($this->discreteFilter) {
            $query->where('outcome_discrete', 'like', '%'.$this->discreteFilter.'%');
        }
        if ($this->quantitativeFilter) {
            $query->where('outcome_quant', 'like', '%'.$this->quantitativeFilter.'%');
        }
        if ($this->purposeFilter) {
            $query->where('purpose', $this->purposeFilter);
        }
        if ($this->photoFilter === 'has') {
            $query->whereNotNull('photo_path')
                ->where('photo_path', '<>', '');
        } elseif ($this->photoFilter === 'none') {
            $query->where(function (Builder $photoQuery): void {
                $photoQuery->whereNull('photo_path')
                    ->orWhere('photo_path', '');
            });
        } elseif ($this->photoFilter === 'broken' && $this->canFilterBrokenPhotos()) {
            $missingPaths = (clone $query)
                ->select('photo_path')
                ->whereNotNull('photo_path')
                ->where('photo_path', '<>', '')
                ->distinct()
                ->pluck('photo_path')
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->filter(fn (string $path) => ! Storage::disk('local')->exists($path))
                ->values();

            if ($missingPaths->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('photo_path', $missingPaths->all());
            }
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_tested', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_tested', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_tested', '<=', $this->endDate);
        }
        if ($this->scientistFilter) {
            $query->whereHas('people', function ($q) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$this->scientistFilter.'%');
            });
        }
        if ($this->placeFilter) {
            $query->whereHas('laboratories', function ($q) {
                $q->where('name', 'like', '%'.$this->placeFilter.'%');
            });
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHas('subProjectAssignment.subProject', function ($q) {
                $q->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
            });
        }

        return $query;
    }

    protected function buildBaseQueryForSelectedTable(): Builder
    {
        $config = $this->selectedTableConfig();

        $query = Experiments::query()->with(array_merge([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'projects',
            'experiments_content',
            'experiments_content.tubes',
            'subProjectAssignment.subProject',
        ], $config['with'] ?? []));

        if (
            $this->selectedTable === 'experiment_pool_table'
            || str_starts_with($this->selectedTable, 'experiment_pool_')
        ) {
            $query->with([
                'experiments_content.pool_contents.samples' => function (MorphTo $morphTo): void {
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

        if ($this->selectedTable === 'experiment_nucleic_pool_table') {
            $query->with([
                'experiments_content.nucleic_content.pool_contents.samples' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => ['humans', 'sampling_sites'],
                        AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
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
                    ]);
                },
            ]);
        }

        if ($this->selectedTable === 'experiment_culture_pool_table') {
            $query->with([
                'experiments_content.cultures_content.pool_contents.samples' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => ['humans', 'sampling_sites'],
                        AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
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

        return $this->applySorting(
            $this->applySelectedTableFilters($this->applyFilters($query)),
            $this->sortMap(),
            ['created_at', 'desc']
        );
    }

    protected function applySelectedTableFilters(Builder $query): Builder
    {
        $config = $this->selectedTableConfig();
        $filters = $config['filters'] ?? [];

        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $query = $filter($query, $this) ?? $query;
            }
        }

        return $query;
    }

    protected function applyTubeCodeOrAliasFilter(Builder $query, string $search): Builder
    {
        return $query->where('code', 'like', '%'.$search.'%')
            ->orWhere('alias_code', 'like', '%'.$search.'%');
    }

    public function export(string $format = 'csv')
    {
        $config = $this->selectedTableConfig();

        $fileName = $config['fileName'] ?? 'experiments.csv';
        $headersRow = $config['csvHeaders'] ?? ['Experiment code'];
        $rowBuilder = $config['csvRow'] ?? null;

        $query = $this->buildBaseQueryForSelectedTable();

        $headers = $headersRow;
        array_splice($headers, 1, 0, 'Sub-project');
        $headers[] = 'Test purpose';

        $rows = [];
        $query->chunk(200, function ($experiments) use (&$rows, $rowBuilder) {
            foreach ($experiments as $experiment) {
                if (is_callable($rowBuilder)) {
                    $built = $rowBuilder($experiment);
                    $subProjectCode = data_get($experiment, 'subProjectAssignment.subProject.code') ?? 'N/A';
                    $purposeLabel = $experiment->purpose?->label() ?? 'N/A';

                    if (is_array($built) && isset($built[0]) && is_array($built[0])) {
                        foreach ($built as $row) {
                            array_splice($row, 1, 0, $subProjectCode);
                            $row[] = $purposeLabel;
                            $rows[] = $row;
                        }
                    } else {
                        array_splice($built, 1, 0, $subProjectCode);
                        $built[] = $purposeLabel;
                        $rows[] = $built;
                    }
                }
            }
        });

        $basename = preg_replace('/\.csv$/', '', (string) $fileName);

        return $this->exportTable($basename, $headers, $rows, $format);
    }

    public function uploadPhoto($experimentId)
    {
        try {
            // Set the current photo ID
            $this->currentPhotoId = $experimentId;

            if (! $this->photo) {
                $this->uploadErrors[$experimentId] = 'No photo was selected';

                return;
            }

            // Check file size before validation
            $maxSize = 50 * 1024 * 1024; // 50MB in bytes
            if ($this->photo->getSize() > $maxSize) {
                $this->uploadErrors[$experimentId] = 'File size exceeds 50MB limit';

                return;
            }

            try {
                $this->validate([
                    'photo' => 'required|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200', // 50MB Max
                ], [
                    'photo.max' => 'The photo size must not exceed 50MB.',
                    'photo.mimes' => 'Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.',
                ]);
            } catch (ValidationException $e) {
                $this->uploadErrors[$experimentId] = $e->validator->errors()->first('photo');

                return;
            }

            $experiment = Experiments::find($experimentId);

            if (! $experiment) {
                $this->uploadErrors[$experimentId] = 'Experiment not found';

                return;
            }

            if (! $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments')) {
                $this->uploadErrors[$experimentId] = 'You can only upload photos for records you registered.';

                return;
            }

            $this->uploadingPhotoId = $experimentId;

            $path = $this->storePhotoForExperiment($experiment, $this->photo);
            if (! $path) {
                $this->uploadErrors[$experimentId] = 'Failed to store photo.';

                return;
            }

            $this->reset(['photo', 'uploadingPhotoId', 'currentPhotoId']);
            unset($this->uploadErrors[$experimentId]);

            $this->dispatch('photo-uploaded', experimentId: $experimentId);
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Success',
                'text' => 'Photo uploaded successfully!',
            ]);

        } catch (\Exception $e) {
            $this->uploadErrors[$experimentId] = 'Failed to upload photo: '.$e->getMessage();
            $this->reset(['photo', 'uploadingPhotoId', 'currentPhotoId']);
        }
    }

    public function openPhotoPreview(int $experimentId): void
    {
        $experiment = Experiments::query()->find($experimentId);
        if (! $experiment) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Experiment not found.',
            ]);

            return;
        }

        $path = (string) ($experiment->photo_path ?? '');
        if ($path === '') {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'No photo',
                'text' => 'This experiment has no photo path.',
            ]);

            return;
        }

        if (! Storage::disk('local')->exists($path)) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Missing file',
                'text' => 'This photo path is not linked to an existing file in storage.',
            ]);

            return;
        }

        $this->photoPreviewExperimentId = (int) $experiment->id;
        $this->photoPreviewUrl = Storage::url($path);
        $this->photoPreviewCode = (string) ($experiment->code ?? '');
        $this->photoPreviewCanDelete = ! $this->isGuestMode()
            && $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments');
    }

    public function closePhotoPreview(): void
    {
        $this->photoPreviewExperimentId = null;
        $this->photoPreviewUrl = null;
        $this->photoPreviewCode = null;
        $this->photoPreviewCanDelete = false;
    }

    public function deletePreviewPhoto(): void
    {
        if (! $this->photoPreviewExperimentId) {
            return;
        }

        $experiment = Experiments::query()->find($this->photoPreviewExperimentId);
        if (! $experiment) {
            $this->closePhotoPreview();

            return;
        }

        if (! $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only delete photos from experiments you registered.',
            ]);

            return;
        }

        $path = (string) ($experiment->photo_path ?? '');
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        if ($path !== '') {
            Experiments::query()
                ->where('photo_path', $path)
                ->update(['photo_path' => null]);
        } else {
            $experiment->update(['photo_path' => null]);
        }
        $this->closePhotoPreview();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Photo deleted successfully.',
        ]);
    }

    public function clearBrokenPhotoPath(int $experimentId): void
    {
        $experiment = Experiments::query()->find($experimentId);
        if (! $experiment) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Experiment not found.',
            ]);

            return;
        }

        if (! $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only clear paths on experiments you registered.',
            ]);

            return;
        }

        $experiment->update(['photo_path' => null]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Path cleared',
            'text' => 'Broken photo path cleared for this experiment.',
        ]);
    }

    public function uploadPhotoToSelected(): void
    {
        if ($this->isGuestMode() || ! $this->canEdit()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You do not have permission to upload photos in this list.',
            ]);

            return;
        }

        if (! $this->bulkPhoto) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No photo selected',
                'text' => 'Please select one photo to apply to selected items.',
            ]);

            return;
        }

        $selectedIds = collect($this->selectedExperiments)
            ->filter(fn ($checked) => (bool) $checked)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($selectedIds->isEmpty()) {
            $this->reset('bulkPhoto');
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No items selected',
                'text' => 'Please check at least one experiment before uploading.',
            ]);

            return;
        }

        try {
            $this->validate([
                'bulkPhoto' => 'required|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200',
            ], [
                'bulkPhoto.max' => 'The photo size must not exceed 50MB.',
                'bulkPhoto.mimes' => 'Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Upload failed',
                'text' => $e->validator->errors()->first('bulkPhoto'),
            ]);

            return;
        }

        $uploaded = 0;
        $skipped = 0;
        $sharedPath = null;

        Experiments::query()
            ->whereIn('id', $selectedIds->all())
            ->get()
            ->each(function (Experiments $experiment) use (&$uploaded, &$skipped, &$sharedPath): void {
                if (! $this->userCanMutateOwnedRecord((int) $experiment->people_id, 'experiments')) {
                    $skipped++;

                    return;
                }

                if ($sharedPath === null) {
                    $sharedPath = $this->storeBulkPhotoOnce($this->bulkPhoto, 'experiments', 'experiment_bulk');
                    if (! $sharedPath) {
                        $skipped++;

                        return;
                    }
                }

                try {
                    $experiment->update(['photo_path' => $sharedPath]);
                } catch (\Exception) {
                    $skipped++;

                    return;
                }

                $uploaded++;
            });

        $this->reset(['bulkPhoto']);
        $this->selectedExperiments = [];

        $message = "Uploaded photo to {$uploaded} experiment(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} item(s) due to permission or storage errors.";
        }

        $this->dispatch('swal', [
            'icon' => $uploaded > 0 ? 'success' : 'warning',
            'title' => $uploaded > 0 ? 'Done' : 'No uploads completed',
            'text' => $message,
        ]);
    }

    private function storePhotoForExperiment(Experiments $experiment, $file): ?string
    {
        if (! $file) {
            return null;
        }

        if ($experiment->photo_path) {
            Storage::disk('local')->delete($experiment->photo_path);
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg'));
        $fileName = 'experiment_'.$experiment->id.'_'.time().'_'.Str::random(6).'.'.$ext;
        $path = 'experiments/'.$fileName;

        try {
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                return null;
            }

            Storage::disk('local')->put($path, $contents);

            if (! Storage::disk('local')->exists($path)) {
                return null;
            }

            $experiment->update(['photo_path' => $path]);

            return $path;
        } catch (\Exception) {
            return null;
        }
    }

    private function storeBulkPhotoOnce($file, string $directory, string $prefix): ?string
    {
        if (! $file) {
            return null;
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg'));
        $fileName = $prefix.'_'.time().'_'.Str::random(10).'.'.$ext;
        $path = trim($directory, '/').'/'.$fileName;

        try {
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                return null;
            }

            Storage::disk('local')->put($path, $contents);

            return Storage::disk('local')->exists($path) ? $path : null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Pool helper: return pool contents collection for a pool experiment.
     *
     * @return Collection<int, mixed>
     */
    private function poolContents(Experiments $e): Collection
    {
        $contents = data_get($e, 'experiments_content.pool_contents');

        return $contents ? collect($contents)->values() : collect();
    }

    /**
     * @return array<int, array{label: string, href: string}>
     */
    private function poolContentCodeLinkItems(Experiments $e): array
    {
        return $this->poolContents($e)
            ->map(function ($pc): ?array {
                $code = data_get($pc, 'samples.code');
                $type = (string) (data_get($pc, 'samples_type') ?? '');

                if (! $code || ! $type) {
                    return null;
                }

                $href = match ($type) {
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
            return '<span class="text-gray-500">N/A</span>';
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

    private function poolContentTypesLabel(Experiments $e): string
    {
        $types = $this->poolContents($e)
            ->map(fn ($pc) => (string) (data_get($pc, 'samples_type') ?? ''))
            ->filter()
            ->map(fn (string $fqcn) => match (class_basename($fqcn)) {
                'HumanSamples' => 'Human samples',
                'AnimalSamples' => 'Animal samples',
                'EnvironmentSamples' => 'Environmental samples',
                'ParasiteSamples' => 'Parasite samples',
                'NucleicAcids' => 'Nucleic acids',
                default => class_basename($fqcn),
            })
            ->unique()
            ->values()
            ->all();

        return empty($types) ? 'N/A' : implode(', ', $types);
    }

    private function poolCollectedRange(Experiments $e): string
    {
        $dates = $this->poolContents($e)
            ->map(function ($pc) {
                $type = (string) (data_get($pc, 'samples_type') ?? '');

                return match ($type) {
                    NucleicAcids::class => match ((string) (data_get($pc, 'samples.nucleic_content_type') ?? '')) {
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
                    },
                    default => data_get($pc, 'samples.date_collected'),
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
    }

    private function poolCollectedRangeByType(Experiments $e, string $samplesType): string
    {
        $dates = $this->poolContents($e)
            ->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $samplesType)
            ->map(function ($pc) use ($samplesType) {
                return match ($samplesType) {
                    NucleicAcids::class => match ((string) (data_get($pc, 'samples.nucleic_content_type') ?? '')) {
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
                    },
                    default => data_get($pc, 'samples.date_collected'),
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
            return '<span class="text-gray-500">N/A</span>';
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
            return '<span class="text-gray-500">N/A</span>';
        }

        $maxVisible = 5;
        $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
        $rowsHidden = array_slice($rowsAll, $maxVisible);

        $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
            .'<th class="px-2 py-1">Content type</th>'
            .'<th class="px-2 py-1">Content code</th>'
            .'<th class="px-2 py-1">Sampling site</th>'
            .'<th class="px-2 py-1">Date collected</th>'
            .'</tr></thead>';

        return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
    }

    private function poolContentsDetailsHtml(Experiments $e, string $samplesType): string
    {
        $maxVisible = 5;
        $contents = $this->poolContents($e)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $samplesType)->values();
        if ($contents->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $id = 'exp-'.$e->id.'-pool-details-'.class_basename($samplesType);
        $rowsAll = [];
        $thead = '';

        if ($samplesType === HumanSamples::class) {
            $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Patient code</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $patientCode = (string) (data_get($pc, 'samples.humans.code') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleHref = $sampleCode ? '/samples/humans/'.rawurlencode($sampleCode) : '#';
                $patientHref = $patientCode ? '/humans/'.rawurlencode($patientCode) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($sampleCode ? '<a href="'.e($sampleHref).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($patientCode ? '<a href="'.e($patientHref).'" class="text-blue-600 hover:text-blue-800">'.e($patientCode).'</a>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();
        } elseif ($samplesType === AnimalSamples::class) {
            $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Animal code</th>'
                .'<th class="px-2 py-1">Species</th>'
                .'<th class="px-2 py-1">Sample type</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $animalCode = (string) (data_get($pc, 'samples.animals.code') ?? '');
                $species = (string) (data_get($pc, 'samples.animals.animal_species.name_common') ?? '');
                $sampleType = (string) (data_get($pc, 'samples.sample_types.name') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleHref = $sampleCode ? '/samples/animals/'.rawurlencode($sampleCode) : '#';
                $animalHref = $animalCode ? '/animals/'.rawurlencode($animalCode) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($sampleCode ? '<a href="'.e($sampleHref).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($animalCode ? '<a href="'.e($animalHref).'" class="text-blue-600 hover:text-blue-800">'.e($animalCode).'</a>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($species ? e($species) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($sampleType ? e($sampleType) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();
        } elseif ($samplesType === EnvironmentSamples::class) {
            $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Sample type</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $code = (string) (data_get($pc, 'samples.code') ?? '');
                $type = (string) (data_get($pc, 'samples.environment_sample_types.name') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $href = $code ? '/samples/environment/'.rawurlencode($code) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($code ? '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800">'.e($code).'</a>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($type ? e($type) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();
        } elseif ($samplesType === ParasiteSamples::class) {
            $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Tick species</th>'
                .'<th class="px-2 py-1">Tick sex</th>'
                .'<th class="px-2 py-1">Tick stage</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $species = (string) (data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? '');
                $sex = (string) (data_get($pc, 'samples.parasites.sex') ?? '');
                $stage = (string) (data_get($pc, 'samples.parasites.stage') ?? '');
                $site = (string) (data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.parasites.parasites_origin.date_collected') ?? data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $href = $sampleCode ? '/samples/parasites/'.rawurlencode($sampleCode) : '#';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($sampleCode ? '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($species ? e($species) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($sex ? e($sex) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($stage ? e($stage) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();
        } elseif ($samplesType === NucleicAcids::class) {
            $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
                .'<th class="px-2 py-1">Nucleic code</th>'
                .'<th class="px-2 py-1">Type</th>'
                .'<th class="px-2 py-1">Content code</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $code = (string) (data_get($pc, 'samples.code') ?? '');
                $type = (string) (data_get($pc, 'samples.type') ?? '');
                $contentCode = (string) (data_get($pc, 'samples.nucleic_content.code') ?? '');
                $primary = $this->poolContentPrimarySiteAndDate($pc);
                $site = (string) ($primary['site'] ?? '');
                $date = $primary['date'];
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $href = $code ? '/samples/nucleic/'.rawurlencode($code) : '#';
                $contentType = (string) (data_get($pc, 'samples.nucleic_content_type') ?? '');
                $contentHref = match ($contentType) {
                    HumanSamples::class => $contentCode ? '/samples/humans/'.rawurlencode($contentCode) : null,
                    AnimalSamples::class => $contentCode ? '/samples/animals/'.rawurlencode($contentCode) : null,
                    EnvironmentSamples::class => $contentCode ? '/samples/environment/'.rawurlencode($contentCode) : null,
                    ParasiteSamples::class => $contentCode ? '/samples/parasites/'.rawurlencode($contentCode) : null,
                    Cultures::class => $contentCode ? '/samples/cultures/'.rawurlencode($contentCode) : null,
                    Pools::class => $contentCode ? '/samples/pools/'.rawurlencode($contentCode) : null,
                    Experiments::class => $contentCode ? '/experiments/'.rawurlencode($contentCode) : null,
                    default => null,
                };
                $contentCell = $contentCode
                    ? ($contentHref ? '<a href="'.e($contentHref).'" class="text-blue-600 hover:text-blue-800">'.e($contentCode).'</a>' : e($contentCode))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($code ? '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800">'.e($code).'</a>' : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($type ? e($type) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$contentCell.'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();
        } else {
            return '<span class="text-gray-500">N/A</span>';
        }

        $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
        $rowsHidden = array_slice($rowsAll, $maxVisible);

        return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
    }

    /**
     * @return array{
     *   tableId: string,
     *   subtitle: string,
     *   showPhoto: bool,
     *   showProjectColumnInGuestMode: bool,
     *   with: array<int, string>,
     *   scope?: callable(Builder): Builder,
     *   filters?: array<int, callable(Builder, self): Builder>,
     *   extraColumns: array<int, array<string, mixed>>,
     *   fileName: string,
     *   csvHeaders: array<int, string>,
     *   csvRow: callable(Experiments): array<int, mixed>
     * }
     */
    public function selectedTableConfig(): array
    {
        $link = function (?string $href, ?string $label): string {
            if (! $href || ! $label) {
                return '<span class="text-gray-500">N/A</span>';
            }

            $isSampleProfileLink = (bool) preg_match('#^/(samples|humans|animals|parasites)/#', (string) $href);
            if ($this->isGuestMode() && $isSampleProfileLink) {
                return e($label);
            }

            return '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">'.e($label).'</a>';
        };

        $linkToExperiment = fn (?string $code): string => $link($code ? "/experiments/{$code}" : null, $code);
        $linkToNucleic = fn (?string $code): string => $link($code ? "/samples/nucleic/{$code}" : null, $code);
        $linkToParasiteSample = fn (?string $code): string => $link($code ? "/samples/parasites/{$code}" : null, $code);
        $linkToCulture = fn (?string $code): string => $link($code ? "/samples/cultures/{$code}" : null, $code);
        $linkToPool = fn (?string $code): string => $link($code ? "/samples/pools/{$code}" : null, $code);
        $linkToHumanSample = fn (?string $code): string => $link($code ? "/samples/humans/{$code}" : null, $code);
        $linkToHuman = fn (?string $code): string => $link($code ? "/humans/{$code}" : null, $code);
        $linkToAnimalSample = fn (?string $code): string => $link($code ? "/samples/animals/{$code}" : null, $code);
        $linkToAnimal = fn (?string $code): string => $link($code ? "/animals/{$code}" : null, $code);
        $linkToEnvironmentSample = fn (?string $code): string => $link($code ? "/samples/environment/{$code}" : null, $code);
        $linkToParasite = fn (?string $code): string => $link($code ? "/parasites/{$code}" : null, $code);

        $contentBadge = function (Experiments $experiment): string {
            $type = $experiment->experiments_content_type;

            return match ($type) {
                HumanSamples::class => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-pink-100 to-pink-200 text-pink-800 shadow-sm">Human Sample</span>',
                AnimalSamples::class => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 shadow-sm">Animal Sample</span>',
                EnvironmentSamples::class => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800 shadow-sm">Environmental Sample</span>',
                ParasiteSamples::class => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">Parasite Sample</span>',
                NucleicAcids::class => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">Nucleic Acid</span>',
                Cultures::class => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">Culture</span>',
                Pools::class => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-cyan-100 to-cyan-200 text-cyan-800 shadow-sm">Pool</span>',
                default => '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 shadow-sm">Other</span>',
            };
        };

        $contentLink = function (Experiments $experiment) use (

            $linkToAnimalSample,
            $linkToCulture,
            $linkToEnvironmentSample,
            $linkToHumanSample,
            $linkToNucleic,
            $linkToParasiteSample,
            $linkToPool
        ): string {
            $code = $experiment->experiments_content?->code;
            if (! $code) {
                return '<span class="text-gray-500">N/A</span>';
            }

            return match ($experiment->experiments_content_type) {
                HumanSamples::class => $linkToHumanSample($code),
                AnimalSamples::class => $linkToAnimalSample($code),
                EnvironmentSamples::class => $linkToEnvironmentSample($code),
                ParasiteSamples::class => $linkToParasiteSample($code),
                NucleicAcids::class => $linkToNucleic($code),
                Cultures::class => $linkToCulture($code),
                Pools::class => $linkToPool($code),
                default => e($code),
            };
        };

        $tubeAliasesHtml = function (mixed $content): string {
            if (! $content) {
                return '';
            }

            $tubes = data_get($content, 'tubes');
            if (! $tubes) {
                return '';
            }

            $aliases = collect($tubes)
                ->pluck('alias_code')
                ->filter(fn ($v) => filled($v))
                ->unique()
                ->values();

            if ($aliases->isEmpty()) {
                return '';
            }

            $badges = $aliases->map(function (string $alias): string {
                return '<span class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1 inline-block">Alias: '.e($alias).'</span>';
            })->implode('');

            return '<div class="flex flex-col">'.$badges.'</div>';
        };

        $withTubeAliases = function (string $primaryHtml, mixed $content) use ($tubeAliasesHtml): string {
            return $primaryHtml.$tubeAliasesHtml($content);
        };

        $dateYmd = fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : 'N/A';

        $nucleicContentTypeLabel = function (?string $type): string {
            return match ($type) {
                HumanSamples::class => 'Human Sample',
                AnimalSamples::class => 'Animal Sample',
                EnvironmentSamples::class => 'Environment Sample',
                ParasiteSamples::class => 'Parasite Sample',
                Experiments::class => 'Experiment',
                Cultures::class => 'Culture',
                Pools::class => 'Pool',
                default => 'Unknown',
            };
        };

        $nucleicContentLink = function (Experiments $experiment) use ($nucleicContentTypeLabel): string {
            $code = data_get($experiment, 'experiments_content.nucleic_content.code');
            if (! $code) {
                return '<span class="text-gray-500">N/A</span>';
            }

            $type = data_get($experiment, 'experiments_content.nucleic_content_type');
            $route = match ($type) {
                HumanSamples::class => "/samples/humans/{$code}",
                AnimalSamples::class => "/samples/animals/{$code}",
                EnvironmentSamples::class => "/samples/environment/{$code}",
                ParasiteSamples::class => "/samples/parasites/{$code}",
                Cultures::class => "/samples/cultures/{$code}",
                Pools::class => "/samples/pools/{$code}",
                default => null,
            };

            $label = e($code);
            if (! $route) {
                return $label.' <span class="text-gray-400 text-xs">('.e($nucleicContentTypeLabel($type)).')</span>';
            }

            return '<a href="'.e($route).'" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">'.$label.'</a>';
        };

        $poolSampleLink = function (Experiments $experiment) use (
            $link,
            $linkToAnimalSample,
            $linkToCulture,
            $linkToEnvironmentSample,
            $linkToHumanSample,
            $linkToNucleic,
            $linkToParasiteSample
        ): string {
            $code = data_get($experiment, 'experiments_content.nucleic_content.pool_contents.0.samples.code');
            $type = data_get($experiment, 'experiments_content.nucleic_content.pool_contents.0.samples_type');

            if (! $code || ! $type) {
                return '<span class="text-gray-500">N/A</span>';
            }

            return match ($type) {
                HumanSamples::class => $linkToHumanSample($code),
                AnimalSamples::class => $linkToAnimalSample($code),
                EnvironmentSamples::class => $linkToEnvironmentSample($code),
                ParasiteSamples::class => $linkToParasiteSample($code),
                NucleicAcids::class => $linkToNucleic($code),
                Cultures::class => $linkToCulture($code),
                Pools::class => $link("/samples/pools/{$code}", $code),
                default => e($code),
            };
        };

        return match ($this->selectedTable) {
            'experiment_human_table' => [
                'tableId' => 'experiment_human_table',
                'subtitle' => 'conducted on human samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.sample_types'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [HumanSamples::class]),
                'filters' => [
                    function (Builder $q, self $c) {
                        $human = data_get($c->originFilters, 'humanSampleCode');
                        if ($human) {
                            $q->whereHasMorph('experiments_content', [HumanSamples::class], function (Builder $sq) use ($c, $human) {
                                $sq->where('code', 'like', '%'.$human.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $human));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $sampleType = data_get($c->originFilters, 'sampleType');
                        if ($sampleType) {
                            $q->whereHasMorph('experiments_content', [HumanSamples::class], fn ($sq) => $sq->whereHas('sample_types', fn ($tq) => $tq->where('name', 'like', '%'.$sampleType.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [HumanSamples::class], function ($sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_collected', '>=', $start);
                                } else {
                                    $sq->where('date_collected', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'extraColumns' => [
                    ['label' => 'Human sample code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToHumanSample(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.humanSampleCode'],
                    ['label' => 'Sample type', 'valuePath' => 'experiments_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd($e->experiments_content?->date_collected),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'fileName' => 'experiments.human.csv',
                'csvHeaders' => ['Experiment code', 'Human sample code', 'Sample type', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        $e->experiments_content?->code ?? 'N/A',
                        data_get($e, 'experiments_content.sample_types.name', 'N/A'),
                        $dateYmd($e->experiments_content?->date_collected),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_animal_table' => [
                'tableId' => 'experiment_animal_table',
                'subtitle' => 'conducted on animal samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.animals.animal_species', 'experiments_content.sample_types', 'experiments_content.sampling_sites'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [AnimalSamples::class]),
                'filters' => [
                    function (Builder $q, self $c) {
                        $animal = data_get($c->originFilters, 'animalSampleCode');
                        if ($animal) {
                            $q->whereHasMorph('experiments_content', [AnimalSamples::class], function (Builder $sq) use ($c, $animal) {
                                $sq->where('code', 'like', '%'.$animal.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $animal));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $species = data_get($c->originFilters, 'animalSpecies');
                        if ($species) {
                            $q->whereHasMorph('experiments_content', [AnimalSamples::class], fn ($sq) => $sq->whereHas('animals.animal_species', fn ($aq) => $aq->where('name_common', 'like', '%'.$species.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $sampleType = data_get($c->originFilters, 'sampleType');
                        if ($sampleType) {
                            $q->whereHasMorph('experiments_content', [AnimalSamples::class], fn ($sq) => $sq->whereHas('sample_types', fn ($tq) => $tq->where('name', 'like', '%'.$sampleType.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $site = data_get($c->originFilters, 'samplingSite');
                        if ($site) {
                            $q->whereHasMorph('experiments_content', [AnimalSamples::class], fn ($sq) => $sq->whereHas('sampling_sites', fn ($tq) => $tq->where('name', 'like', '%'.$site.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [AnimalSamples::class], function ($sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_collected', '>=', $start);
                                } else {
                                    $sq->where('date_collected', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'extraColumns' => [
                    ['label' => 'Animal code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToAnimalSample(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.animalSampleCode'],
                    ['label' => 'Animal species', 'valuePath' => 'experiments_content.animals.animal_species.name_common', 'filterModel' => 'originFilters.animalSpecies'],
                    ['label' => 'Sample type', 'valuePath' => 'experiments_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd($e->experiments_content?->date_collected),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'fileName' => 'experiments.animal.csv',
                'csvHeaders' => ['Experiment code', 'Animal code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        $e->experiments_content?->code ?? 'N/A',
                        data_get($e, 'experiments_content.animals.animal_species.name_common', 'N/A'),
                        data_get($e, 'experiments_content.sample_types.name', 'N/A'),
                        data_get($e, 'experiments_content.sampling_sites.name', 'N/A'),
                        $dateYmd($e->experiments_content?->date_collected),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_environment_table' => [
                'tableId' => 'experiment_environment_table',
                'subtitle' => 'conducted on environment samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.environment_sample_types', 'experiments_content.sampling_sites'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [EnvironmentSamples::class]),
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'environmentSampleCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [EnvironmentSamples::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'environmentType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [EnvironmentSamples::class], fn ($sq) => $sq->whereHas('environment_sample_types', fn ($tq) => $tq->where('name', 'like', '%'.$type.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $site = data_get($c->originFilters, 'samplingSite');
                        if ($site) {
                            $q->whereHasMorph('experiments_content', [EnvironmentSamples::class], fn ($sq) => $sq->whereHas('sampling_sites', fn ($tq) => $tq->where('name', 'like', '%'.$site.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [EnvironmentSamples::class], function ($sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_collected', '>=', $start);
                                } else {
                                    $sq->where('date_collected', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'extraColumns' => [
                    ['label' => 'Environment code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToEnvironmentSample(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.environmentSampleCode'],
                    ['label' => 'Environment type', 'valuePath' => 'experiments_content.environment_sample_types.name', 'filterModel' => 'originFilters.environmentType'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd($e->experiments_content?->date_collected),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'fileName' => 'experiments.environment.csv',
                'csvHeaders' => ['Experiment code', 'Environment code', 'Environment type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        $e->experiments_content?->code ?? 'N/A',
                        data_get($e, 'experiments_content.environment_sample_types.name', 'N/A'),
                        data_get($e, 'experiments_content.sampling_sites.name', 'N/A'),
                        $dateYmd($e->experiments_content?->date_collected),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_parasite_table' => [
                'tableId' => 'experiment_parasite_table',
                'subtitle' => 'conducted on parasite samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.parasites',
                    'experiments_content.parasites.parasite_species',
                    'experiments_content.parasites.parasites_origin.sampling_sites',
                    'experiments_content.parasite_sample_types',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [ParasiteSamples::class]),
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'parasiteCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], function (Builder $sq) use ($c, $code) {
                                $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('code', 'like', '%'.$code.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $species = data_get($c->originFilters, 'parasiteSpecies');
                        if ($species) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasite_species', fn ($psq) => $psq->where('name_scientific', 'like', '%'.$species.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'parasiteSampleType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasite_sample_types', fn ($tq) => $tq->where('name', 'like', '%'.$type.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $site = data_get($c->originFilters, 'samplingSite');
                        if ($site) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.sampling_sites', fn ($tq) => $tq->where('name', 'like', '%'.$site.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], function ($sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_collected', '>=', $start);
                                } else {
                                    $sq->where('date_collected', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'extraColumns' => [
                    ['label' => 'Parasite code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToParasite(data_get($e, 'experiments_content.parasites.code')), $e->experiments_content), 'filterModel' => 'originFilters.parasiteCode'],
                    ['label' => 'Parasite species', 'valuePath' => 'experiments_content.parasites.parasite_species.name_scientific', 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Sample type', 'valuePath' => 'experiments_content.parasite_sample_types.name', 'filterModel' => 'originFilters.parasiteSampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd($e->experiments_content?->date_collected),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'fileName' => 'experiments.parasite.csv',
                'csvHeaders' => ['Experiment code', 'Parasite code', 'Parasite species', 'Sample type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.parasites.code', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasite_species.name_scientific', 'N/A'),
                        data_get($e, 'experiments_content.parasite_sample_types.name', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd($e->experiments_content?->date_collected),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_parasite_human_table' => [
                'tableId' => 'experiment_parasite_human_table',
                'subtitle' => 'conducted on parasites from human samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.parasites',
                    'experiments_content.parasites.parasite_species',
                    'experiments_content.parasites.parasites_origin',
                    'experiments_content.parasites.parasites_origin.humans',
                    'experiments_content.parasites.parasites_origin.sample_types',
                    'experiments_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('parasites_origin_type', HumanSamples::class))),
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'parasiteCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], function (Builder $sq) use ($c, $code) {
                                $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('code', 'like', '%'.$code.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $species = data_get($c->originFilters, 'parasiteSpecies');
                        if ($species) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasite_species', fn ($psq) => $psq->where('name_scientific', 'like', '%'.$species.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $stage = data_get($c->originFilters, 'stage');
                        if ($stage) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('stage', 'like', '%'.$stage.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $state = data_get($c->originFilters, 'state');
                        if ($state) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('state', 'like', '%'.$state.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $human = data_get($c->originFilters, 'humanCode');
                        if ($human) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->whereHasMorph('parasites_origin', [HumanSamples::class], fn ($hq) => $hq->where('code', 'like', '%'.$human.'%'))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $name = data_get($c->originFilters, 'humanName');
                        if ($name) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->whereHasMorph('parasites_origin', [HumanSamples::class], fn ($hq) => $hq->whereHas('humans', fn ($personQ) => $personQ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$name.'%')))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $site = data_get($c->originFilters, 'samplingSite');
                        if ($site) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->whereHasMorph('parasites_origin', [HumanSamples::class], fn ($hq) => $hq->whereHas('sampling_sites', fn ($tq) => $tq->where('name', 'like', '%'.$site.'%')))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', function ($pq) use ($start, $end) {
                                $pq->whereHasMorph('parasites_origin', [HumanSamples::class], function ($hq) use ($start, $end) {
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
                ],
                'extraColumns' => [
                    ['label' => 'Parasite code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToParasite(data_get($e, 'experiments_content.parasites.code')), $e->experiments_content), 'filterModel' => 'originFilters.parasiteCode'],
                    ['label' => 'Parasite species', 'valuePath' => 'experiments_content.parasites.parasite_species.name_scientific', 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Stage', 'valuePath' => 'experiments_content.parasites.stage', 'filterModel' => 'originFilters.stage'],
                    ['label' => 'State', 'valuePath' => 'experiments_content.parasites.state', 'filterModel' => 'originFilters.state'],
                    ['label' => 'Human code', 'html' => true, 'value' => fn (Experiments $e) => $linkToHumanSample(data_get($e, 'experiments_content.parasites.parasites_origin.code')), 'filterModel' => 'originFilters.humanCode'],
                    [
                        'label' => 'Human name',
                        'value' => fn (Experiments $e) => trim((data_get($e, 'experiments_content.parasites.parasites_origin.humans.first_name') ?? '').' '.(data_get($e, 'experiments_content.parasites.parasites_origin.humans.last_name') ?? '')) ?: null,
                        'filterModel' => 'originFilters.humanName',
                    ],
                    ['label' => 'Sample type', 'valuePath' => 'experiments_content.parasites.parasites_origin.sample_types.name'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'fileName' => 'experiments.parasite.human.csv',
                'csvHeaders' => ['Experiment code', 'Parasite code', 'Parasite species', 'Stage', 'State', 'Human code', 'Human name', 'Sample type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.parasites.code', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasite_species.name_scientific', 'N/A'),
                        data_get($e, 'experiments_content.parasites.stage', 'N/A'),
                        data_get($e, 'experiments_content.parasites.state', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.code', 'N/A'),
                        trim((data_get($e, 'experiments_content.parasites.parasites_origin.humans.first_name') ?? '').' '.(data_get($e, 'experiments_content.parasites.parasites_origin.humans.last_name') ?? '')) ?: 'N/A',
                        data_get($e, 'experiments_content.parasites.parasites_origin.sample_types.name', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.parasites.parasites_origin.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_parasite_animal_table' => [
                'tableId' => 'experiment_parasite_animal_table',
                'subtitle' => 'conducted on parasites from animal samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.parasites',
                    'experiments_content.parasites.parasite_species',
                    'experiments_content.parasites.parasites_origin',
                    'experiments_content.parasites.parasites_origin.animals',
                    'experiments_content.parasites.parasites_origin.animals.animal_species',
                    'experiments_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('parasites_origin_type', AnimalSamples::class))),
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'parasiteCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], function (Builder $sq) use ($c, $code) {
                                $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('code', 'like', '%'.$code.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $species = data_get($c->originFilters, 'parasiteSpecies');
                        if ($species) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasite_species', fn ($psq) => $psq->where('name_scientific', 'like', '%'.$species.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $stage = data_get($c->originFilters, 'stage');
                        if ($stage) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('stage', 'like', '%'.$stage.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $state = data_get($c->originFilters, 'state');
                        if ($state) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('state', 'like', '%'.$state.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $animal = data_get($c->originFilters, 'animalCode');
                        if ($animal) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.animals', fn ($aq) => $aq->where('code', 'like', '%'.$animal.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $species = data_get($c->originFilters, 'animalSpecies');
                        if ($species) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.animals.animal_species', fn ($aq) => $aq->where('name_common', 'like', '%'.$species.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $site = data_get($c->originFilters, 'samplingSite');
                        if ($site) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites.parasites_origin.sampling_sites', fn ($tq) => $tq->where('name', 'like', '%'.$site.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], function ($sq) use ($start, $end) {
                                $sq->whereHas('parasites.parasites_origin', function ($oq) use ($start, $end) {
                                    if ($start && $end) {
                                        $oq->whereBetween('date_collected', [$start, $end]);
                                    } elseif ($start) {
                                        $oq->where('date_collected', '>=', $start);
                                    } else {
                                        $oq->where('date_collected', '<=', $end);
                                    }
                                });
                            });
                        }

                        return $q;
                    },
                ],
                'extraColumns' => [
                    ['label' => 'Parasite code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToParasite(data_get($e, 'experiments_content.parasites.code')), $e->experiments_content), 'filterModel' => 'originFilters.parasiteCode'],
                    ['label' => 'Parasite species', 'valuePath' => 'experiments_content.parasites.parasite_species.name_scientific', 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Stage', 'valuePath' => 'experiments_content.parasites.stage', 'filterModel' => 'originFilters.stage'],
                    ['label' => 'State', 'valuePath' => 'experiments_content.parasites.state', 'filterModel' => 'originFilters.state'],
                    ['label' => 'Animal code', 'html' => true, 'value' => fn (Experiments $e) => $linkToAnimal(data_get($e, 'experiments_content.parasites.parasites_origin.animals.code')), 'filterModel' => 'originFilters.animalCode'],
                    ['label' => 'Animal species', 'valuePath' => 'experiments_content.parasites.parasites_origin.animals.animal_species.name_common', 'filterModel' => 'originFilters.animalSpecies'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'fileName' => 'experiments.parasite.animal.csv',
                'csvHeaders' => ['Experiment code', 'Parasite code', 'Parasite species', 'Stage', 'State', 'Animal code', 'Animal species', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.parasites.code', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasite_species.name_scientific', 'N/A'),
                        data_get($e, 'experiments_content.parasites.stage', 'N/A'),
                        data_get($e, 'experiments_content.parasites.state', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.animals.code', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.animals.animal_species.name_common', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.parasites.parasites_origin.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->title ?? '').' '.($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_parasite_environment_table' => [
                'tableId' => 'experiment_parasite_environment_table',
                'subtitle' => 'conducted on parasites from environment samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.parasites',
                    'experiments_content.parasites.parasite_species',
                    'experiments_content.parasites.parasites_origin',
                    'experiments_content.parasites.parasites_origin.environment_sample_types',
                    'experiments_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn ($sq) => $sq->whereHas('parasites', fn ($pq) => $pq->where('parasites_origin_type', EnvironmentSamples::class))),
                'extraColumns' => [
                    ['label' => 'Parasite code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToParasite(data_get($e, 'experiments_content.parasites.code')), $e->experiments_content), 'filterModel' => 'originFilters.parasiteCode'],
                    ['label' => 'Parasite species', 'valuePath' => 'experiments_content.parasites.parasite_species.name_scientific', 'filterModel' => 'originFilters.parasiteSpecies'],
                    ['label' => 'Environment code', 'html' => true, 'value' => fn (Experiments $e) => $linkToEnvironmentSample(data_get($e, 'experiments_content.parasites.parasites_origin.code')), 'filterModel' => 'originFilters.environmentCode'],
                    ['label' => 'Stage', 'valuePath' => 'experiments_content.parasites.stage', 'filterModel' => 'originFilters.stage'],
                    ['label' => 'State', 'valuePath' => 'experiments_content.parasites.state', 'filterModel' => 'originFilters.state'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.parasites.parasites_origin.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'parasiteCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], function (Builder $sq) use ($c, $code) {
                                $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('code', 'like', '%'.$code.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $species = data_get($c->originFilters, 'parasiteSpecies');
                        if ($species) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn (Builder $sq) => $sq->whereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_scientific', 'like', '%'.$species.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $envCode = data_get($c->originFilters, 'environmentCode');
                        if ($envCode) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->whereHasMorph('parasites_origin', [EnvironmentSamples::class], fn (Builder $eq) => $eq->where('code', 'like', '%'.$envCode.'%'))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $stage = data_get($c->originFilters, 'stage');
                        if ($stage) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('stage', 'like', '%'.$stage.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $state = data_get($c->originFilters, 'state');
                        if ($state) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('state', 'like', '%'.$state.'%')));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $site = data_get($c->originFilters, 'samplingSite');
                        if ($site) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->whereHasMorph('parasites_origin', [EnvironmentSamples::class], fn (Builder $eq) => $eq->whereHas('sampling_sites', fn (Builder $tq) => $tq->where('name', 'like', '%'.$site.'%')))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [ParasiteSamples::class], fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->whereHasMorph('parasites_origin', [EnvironmentSamples::class], function (Builder $eq) use ($start, $end) {
                                if ($start && $end) {
                                    $eq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $eq->where('date_collected', '>=', $start);
                                } else {
                                    $eq->where('date_collected', '<=', $end);
                                }
                            })));
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.parasite.environment.csv',
                'csvHeaders' => ['Experiment code', 'Parasite code', 'Parasite species', 'Environment sample code', 'Stage', 'State', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.parasites.code', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasite_species.name_scientific', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.code', 'N/A'),
                        data_get($e, 'experiments_content.parasites.stage', 'N/A'),
                        data_get($e, 'experiments_content.parasites.state', 'N/A'),
                        data_get($e, 'experiments_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.parasites.parasites_origin.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->title ?? '').' '.($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_nucleic_table' => [
                'tableId' => 'experiment_nucleic_table',
                'subtitle' => 'conducted on nucleic acids',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.nucleic_content'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [NucleicAcids::class]),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToNucleic(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.nucleicCode'],
                    ['label' => 'Nucleic type', 'valuePath' => 'experiments_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    ['label' => 'Nucleic content code', 'html' => true, 'value' => $nucleicContentLink, 'filterModel' => 'originFilters.nucleicContentCode'],
                    [
                        'label' => 'Nucleic content type',
                        'value' => fn (Experiments $e) => $nucleicContentTypeLabel(data_get($e, 'experiments_content.nucleic_content_type')),
                    ],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.extractedStart',
                        'filterModelEnd' => 'originFilters.extractedEnd',
                    ],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'nucleicCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'nucleicType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn ($sq) => $sq->where('type', 'like', '%'.$type.'%'));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'nucleicContentCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $code) {
                                $sq->whereHasMorph(
                                    'nucleic_content',
                                    [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Cultures::class, Pools::class],
                                    fn (Builder $cq) => $cq->where('code', 'like', '%'.$code.'%')
                                )->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'extractedStart');
                        $end = data_get($c->originFilters, 'extractedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function ($sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_extracted', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_extracted', '>=', $start);
                                } else {
                                    $sq->where('date_extracted', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.nucleic.csv',
                'csvHeaders' => ['Experiment code', 'Nucleic acid code', 'Nucleic type', 'Date extracted', 'Nucleic content code', 'Nucleic content type', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd, $nucleicContentTypeLabel) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        data_get($e, 'experiments_content.nucleic_content.code', 'N/A'),
                        $nucleicContentTypeLabel(data_get($e, 'experiments_content.nucleic_content_type')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_nucleic_human_table' => [
                'tableId' => 'experiment_nucleic_human_table',
                'subtitle' => 'conducted on nucleic acids from human samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.nucleic_content', 'experiments_content.nucleic_content.humans'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [HumanSamples::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToNucleic(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.nucleicCode'],
                    ['label' => 'Nucleic type', 'valuePath' => 'experiments_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.extractedStart',
                        'filterModelEnd' => 'originFilters.extractedEnd',
                    ],
                    ['label' => 'Human sample code', 'html' => true, 'value' => fn (Experiments $e) => $linkToHumanSample(data_get($e, 'experiments_content.nucleic_content.code')), 'filterModel' => 'originFilters.humanSampleCode'],
                    ['label' => 'Human code', 'html' => true, 'value' => fn (Experiments $e) => $linkToHuman(data_get($e, 'experiments_content.nucleic_content.humans.code')), 'filterModel' => 'originFilters.humanCode'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'nucleicCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'nucleicType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->where('type', 'like', '%'.$type.'%'));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $humanSampleCode = data_get($c->originFilters, 'humanSampleCode');
                        if ($humanSampleCode) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $humanSampleCode) {
                                $sq->whereHasMorph('nucleic_content', [HumanSamples::class], fn (Builder $hq) => $hq->where('code', 'like', '%'.$humanSampleCode.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $humanSampleCode));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $humanCode = data_get($c->originFilters, 'humanCode');
                        if ($humanCode) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->whereHasMorph('nucleic_content', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('humans', fn (Builder $personQ) => $personQ->where('code', 'like', '%'.$humanCode.'%'))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                $sq->whereHasMorph('nucleic_content', [HumanSamples::class], function (Builder $hq) use ($start, $end) {
                                    if ($start && $end) {
                                        $hq->whereBetween('date_collected', [$start, $end]);
                                    } elseif ($start) {
                                        $hq->where('date_collected', '>=', $start);
                                    } else {
                                        $hq->where('date_collected', '<=', $end);
                                    }
                                });
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'extractedStart');
                        $end = data_get($c->originFilters, 'extractedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_extracted', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_extracted', '>=', $start);
                                } else {
                                    $sq->where('date_extracted', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.nucleic.human.csv',
                'csvHeaders' => ['Experiment code', 'Nucleic acid code', 'Nucleic type', 'Date extracted', 'Human sample code', 'Human code', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        data_get($e, 'experiments_content.nucleic_content.code', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.humans.code', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_nucleic_animal_table' => [
                'tableId' => 'experiment_nucleic_animal_table',
                'subtitle' => 'conducted on nucleic acids from animal samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.nucleic_content',
                    'experiments_content.nucleic_content.animals.animal_species',
                    'experiments_content.nucleic_content.sample_types',
                    'experiments_content.nucleic_content.sampling_sites',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToNucleic(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.nucleicCode'],
                    ['label' => 'Nucleic type', 'valuePath' => 'experiments_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.extractedStart',
                        'filterModelEnd' => 'originFilters.extractedEnd',
                    ],
                    ['label' => 'Animal sample code', 'html' => true, 'value' => fn (Experiments $e) => $linkToAnimalSample(data_get($e, 'experiments_content.nucleic_content.code')), 'filterModel' => 'originFilters.animalSampleCode'],
                    ['label' => 'Animal species', 'valuePath' => 'experiments_content.nucleic_content.animals.animal_species.name_common', 'filterModel' => 'originFilters.animalSpecies'],
                    ['label' => 'Sample type', 'valuePath' => 'experiments_content.nucleic_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.nucleic_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'nucleicCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'nucleicType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->where('type', 'like', '%'.$type.'%'));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $animalSampleCode = data_get($c->originFilters, 'animalSampleCode');
                        if ($animalSampleCode) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $animalSampleCode) {
                                $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn (Builder $aq) => $aq->where('code', 'like', '%'.$animalSampleCode.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $animalSampleCode));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $species = data_get($c->originFilters, 'animalSpecies');
                        if ($species) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('animals.animal_species', fn (Builder $spq) => $spq->where('name_common', 'like', '%'.$species.'%'))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $sampleType = data_get($c->originFilters, 'sampleType');
                        if ($sampleType) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('sample_types', fn (Builder $tq) => $tq->where('name', 'like', '%'.$sampleType.'%'))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $site = data_get($c->originFilters, 'samplingSite');
                        if ($site) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('sampling_sites', fn (Builder $tq) => $tq->where('name', 'like', '%'.$site.'%'))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                $sq->whereHasMorph('nucleic_content', [AnimalSamples::class], function (Builder $aq) use ($start, $end) {
                                    if ($start && $end) {
                                        $aq->whereBetween('date_collected', [$start, $end]);
                                    } elseif ($start) {
                                        $aq->where('date_collected', '>=', $start);
                                    } else {
                                        $aq->where('date_collected', '<=', $end);
                                    }
                                });
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'extractedStart');
                        $end = data_get($c->originFilters, 'extractedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_extracted', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_extracted', '>=', $start);
                                } else {
                                    $sq->where('date_extracted', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.nucleic.animal.csv',
                'csvHeaders' => ['Experiment code', 'Nucleic acid code', 'Nucleic type', 'Date extracted', 'Animal sample code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        data_get($e, 'experiments_content.nucleic_content.code', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.animals.animal_species.name_common', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.sample_types.name', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_nucleic_environment_table' => [
                'tableId' => 'experiment_nucleic_environment_table',
                'subtitle' => 'conducted on nucleic acids from environment samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.nucleic_content', 'experiments_content.nucleic_content.environment_sample_types'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToNucleic(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.nucleicCode'],
                    ['label' => 'Nucleic type', 'valuePath' => 'experiments_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.extractedStart',
                        'filterModelEnd' => 'originFilters.extractedEnd',
                    ],
                    ['label' => 'Environment sample code', 'html' => true, 'value' => fn (Experiments $e) => $linkToEnvironmentSample(data_get($e, 'experiments_content.nucleic_content.code')), 'filterModel' => 'originFilters.environmentSampleCode'],
                    ['label' => 'Environment type', 'valuePath' => 'experiments_content.nucleic_content.environment_sample_types.name', 'filterModel' => 'originFilters.environmentType'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'nucleicCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'nucleicType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->where('type', 'like', '%'.$type.'%'));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $envCode = data_get($c->originFilters, 'environmentSampleCode');
                        if ($envCode) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $envCode) {
                                $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class], fn (Builder $eq) => $eq->where('code', 'like', '%'.$envCode.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $envCode));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $envType = data_get($c->originFilters, 'environmentType');
                        if ($envType) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class], fn (Builder $eq) => $eq->whereHas('environment_sample_types', fn (Builder $tq) => $tq->where('name', 'like', '%'.$envType.'%'))));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                $sq->whereHasMorph('nucleic_content', [EnvironmentSamples::class], function (Builder $eq) use ($start, $end) {
                                    if ($start && $end) {
                                        $eq->whereBetween('date_collected', [$start, $end]);
                                    } elseif ($start) {
                                        $eq->where('date_collected', '>=', $start);
                                    } else {
                                        $eq->where('date_collected', '<=', $end);
                                    }
                                });
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'extractedStart');
                        $end = data_get($c->originFilters, 'extractedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_extracted', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_extracted', '>=', $start);
                                } else {
                                    $sq->where('date_extracted', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.nucleic.environment.csv',
                'csvHeaders' => ['Experiment code', 'Nucleic acid code', 'Nucleic type', 'Date extracted', 'Environment sample code', 'Environment type', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        data_get($e, 'experiments_content.nucleic_content.code', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.environment_sample_types.name', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_nucleic_culture_table' => [
                'tableId' => 'experiment_nucleic_culture_table',
                'subtitle' => 'conducted on nucleic acids from culture samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.nucleic_content'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [Cultures::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'html' => true, 'value' => fn (Experiments $e) => $linkToNucleic(data_get($e, 'experiments_content.code'))],
                    ['label' => 'Nucleic type', 'valuePath' => 'experiments_content.type'],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                    ],
                    ['label' => 'Culture code', 'html' => true, 'value' => fn (Experiments $e) => $linkToCulture(data_get($e, 'experiments_content.nucleic_content.code'))],
                    ['label' => 'Culture type', 'valuePath' => 'experiments_content.nucleic_content.type'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                    ],
                ],
                'fileName' => 'experiments.nucleic.culture.csv',
                'csvHeaders' => ['Experiment code', 'Nucleic acid code', 'Nucleic type', 'Date extracted', 'Culture code', 'Culture type', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        data_get($e, 'experiments_content.nucleic_content.code', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_nucleic_pool_table' => [
                'tableId' => 'experiment_nucleic_pool_table',
                'subtitle' => 'conducted on nucleic acids from pools',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.nucleic_content', 'experiments_content.nucleic_content.pool_contents', 'experiments_content.nucleic_content.pool_contents.samples'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [Pools::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToNucleic(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.nucleicCode'],
                    ['label' => 'Nucleic type', 'valuePath' => 'experiments_content.type', 'filterModel' => 'originFilters.nucleicType'],
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $linkToPool(data_get($e, 'experiments_content.nucleic_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolContentsCollectedRangeForPoolModel(data_get($e, 'experiments_content.nucleic_content')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (Experiments $e) => $this->poolContentsDetailsCombinedHtmlForPoolModel(
                            data_get($e, 'experiments_content.nucleic_content'),
                            'exp-'.$e->id.'-nucleic-pool-contents'
                        ),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.extractedStart',
                        'filterModelEnd' => 'originFilters.extractedEnd',
                    ],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'nucleicCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'nucleicType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn (Builder $sq) => $sq->where('type', 'like', '%'.$type.'%'));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $poolCode = data_get($c->originFilters, 'poolCode');
                        if ($poolCode) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $poolCode) {
                                $sq->whereHasMorph('nucleic_content', [Pools::class], fn (Builder $pq) => $pq->where('code', 'like', '%'.$poolCode.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $poolCode));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $search = data_get($c->originFilters, 'contentSearch');
                        if ($search) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($c, $search) {
                                $sq->whereHasMorph('nucleic_content', [Pools::class], function (Builder $pq) use ($c, $search) {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($c, $search) {
                                        $pcq->whereHasMorph('samples', [
                                            HumanSamples::class,
                                            AnimalSamples::class,
                                            EnvironmentSamples::class,
                                            ParasiteSamples::class,
                                            NucleicAcids::class,
                                            Cultures::class,
                                            Pools::class,
                                        ], function (Builder $samplesQ) use ($c, $search) {
                                            $samplesQ->where('code', 'like', '%'.$search.'%')
                                                ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $search));
                                        });
                                    });
                                })->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $search));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                $sq->whereHasMorph('nucleic_content', [Pools::class], function (Builder $pq) use ($start, $end) {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($start, $end) {
                                        $pcq->whereHasMorph('samples', [
                                            HumanSamples::class,
                                            AnimalSamples::class,
                                            EnvironmentSamples::class,
                                            ParasiteSamples::class,
                                            NucleicAcids::class,
                                            Cultures::class,
                                        ], function (Builder $samplesQ) use ($start, $end) {
                                            if ($start && $end) {
                                                $samplesQ->whereBetween('date_collected', [$start, $end]);
                                            } elseif ($start) {
                                                $samplesQ->where('date_collected', '>=', $start);
                                            } else {
                                                $samplesQ->where('date_collected', '<=', $end);
                                            }
                                        });
                                    });
                                });
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'extractedStart');
                        $end = data_get($c->originFilters, 'extractedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [NucleicAcids::class], function (Builder $sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_extracted', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_extracted', '>=', $start);
                                } else {
                                    $sq->where('date_extracted', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.nucleic.pool.csv',
                'csvHeaders' => ['Experiment code', 'Nucleic code', 'Nucleic type', 'Pool code', 'Content type', 'Content code', 'Sampling site', 'Date collected', 'Date extracted', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    /** @var Pools|null $pool */
                    $pool = data_get($e, 'experiments_content.nucleic_content');
                    $contents = collect(data_get($pool, 'pool_contents', []))->filter(fn ($pc) => data_get($pc, 'samples') !== null)->values();

                    $base = [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        data_get($pool, 'code', 'N/A'),
                    ];

                    $tail = [
                        $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
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
            ],

            'experiment_nucleic_parasite_table' => [
                'tableId' => 'experiment_nucleic_parasite_table',
                'subtitle' => 'conducted on nucleic acids from parasite samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.nucleic_content',
                    'experiments_content.nucleic_content.parasites',
                    'experiments_content.nucleic_content.parasites.parasite_species',
                    'experiments_content.nucleic_content.parasite_sample_types',
                    'experiments_content.nucleic_content.parasites.parasites_origin.sampling_sites',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [NucleicAcids::class], fn ($sq) => $sq->whereHasMorph('nucleic_content', [ParasiteSamples::class])),
                'extraColumns' => [
                    ['label' => 'Nucleic acid code', 'html' => true, 'value' => fn (Experiments $e) => $linkToNucleic(data_get($e, 'experiments_content.code'))],
                    ['label' => 'Nucleic type', 'valuePath' => 'experiments_content.type'],
                    [
                        'label' => 'Date extracted',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                    ],
                    ['label' => 'Parasite sample code', 'html' => true, 'value' => fn (Experiments $e) => $linkToParasiteSample(data_get($e, 'experiments_content.nucleic_content.code'))],
                    ['label' => 'Parasite species', 'valuePath' => 'experiments_content.nucleic_content.parasites.parasite_species.name_scientific'],
                    ['label' => 'Sample type', 'valuePath' => 'experiments_content.nucleic_content.parasite_sample_types.name'],
                    ['label' => 'Sampling site', 'valuePath' => 'experiments_content.nucleic_content.parasites.parasites_origin.sampling_sites.name'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                    ],
                ],
                'fileName' => 'experiments.nucleic.parasite.csv',
                'csvHeaders' => ['Experiment code', 'Nucleic acid code', 'Nucleic type', 'Date extracted', 'Parasite sample code', 'Parasite species', 'Sample type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_extracted')),
                        data_get($e, 'experiments_content.nucleic_content.code', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.parasites.parasite_species.name_scientific', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.parasite_sample_types.name', 'N/A'),
                        data_get($e, 'experiments_content.nucleic_content.parasites.parasites_origin.sampling_sites.name', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.nucleic_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_culture_table' => [
                'tableId' => 'experiment_culture_table',
                'subtitle' => 'conducted on cultures',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Cultures::class]),
                'extraColumns' => [
                    ['label' => 'Culture code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToCulture(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.cultureCode'],
                    ['label' => 'Culture type', 'valuePath' => 'experiments_content.type', 'filterModel' => 'originFilters.cultureType'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'cultureCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'cultureType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], fn (Builder $sq) => $sq->where('type', 'like', '%'.$type.'%'));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], function (Builder $sq) use ($start, $end) {
                                if ($start && $end) {
                                    $sq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $sq->where('date_collected', '>=', $start);
                                } else {
                                    $sq->where('date_collected', '<=', $end);
                                }
                            });
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.culture.csv',
                'csvHeaders' => ['Experiment code', 'Culture code', 'Culture type', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_culture_human_table' => [
                'tableId' => 'experiment_culture_human_table',
                'subtitle' => 'conducted on cultures from human samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.cultures_content', 'experiments_content.cultures_content.humans'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [HumanSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'html' => true, 'value' => fn (Experiments $e) => $linkToCulture(data_get($e, 'experiments_content.code'))],
                    ['label' => 'Culture type', 'valuePath' => 'experiments_content.type'],
                    ['label' => 'Human code', 'html' => true, 'value' => fn (Experiments $e) => $linkToHuman(data_get($e, 'experiments_content.cultures_content.humans.code'))],
                    [
                        'label' => 'Human name',
                        'value' => fn (Experiments $e) => trim((data_get($e, 'experiments_content.cultures_content.humans.first_name') ?? '').' '.(data_get($e, 'experiments_content.cultures_content.humans.last_name') ?? '')) ?: 'N/A',
                    ],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_collected')),
                    ],
                ],
                'fileName' => 'experiments.culture.human.csv',
                'csvHeaders' => ['Experiment code', 'Culture code', 'Culture type', 'Human code', 'Human name', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        data_get($e, 'experiments_content.cultures_content.humans.code', 'N/A'),
                        trim((data_get($e, 'experiments_content.cultures_content.humans.first_name') ?? '').' '.(data_get($e, 'experiments_content.cultures_content.humans.last_name') ?? '')) ?: 'N/A',
                        $dateYmd(data_get($e, 'experiments_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_culture_animal_table' => [
                'tableId' => 'experiment_culture_animal_table',
                'subtitle' => 'conducted on cultures from animal samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.cultures_content', 'experiments_content.cultures_content.animals.animal_species'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [AnimalSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'html' => true, 'value' => fn (Experiments $e) => $linkToCulture(data_get($e, 'experiments_content.code'))],
                    ['label' => 'Culture type', 'valuePath' => 'experiments_content.type'],
                    ['label' => 'Animal code', 'html' => true, 'value' => fn (Experiments $e) => $linkToAnimal(data_get($e, 'experiments_content.cultures_content.animals.code'))],
                    ['label' => 'Animal species', 'valuePath' => 'experiments_content.cultures_content.animals.animal_species.name_common'],
                    ['label' => 'Date collected', 'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_collected'))],
                ],
                'fileName' => 'experiments.culture.animal.csv',
                'csvHeaders' => ['Experiment code', 'Culture code', 'Culture type', 'Animal code', 'Animal species', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        data_get($e, 'experiments_content.cultures_content.animals.code', 'N/A'),
                        data_get($e, 'experiments_content.cultures_content.animals.animal_species.name_common', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_culture_environment_table' => [
                'tableId' => 'experiment_culture_environment_table',
                'subtitle' => 'conducted on cultures from environment samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.cultures_content', 'experiments_content.cultures_content.environment_sample_types'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [EnvironmentSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'html' => true, 'value' => fn (Experiments $e) => $linkToCulture(data_get($e, 'experiments_content.code'))],
                    ['label' => 'Culture type', 'valuePath' => 'experiments_content.type'],
                    ['label' => 'Environment code', 'html' => true, 'value' => fn (Experiments $e) => $linkToEnvironmentSample(data_get($e, 'experiments_content.cultures_content.code'))],
                    ['label' => 'Environment type', 'valuePath' => 'experiments_content.cultures_content.environment_sample_types.name'],
                    ['label' => 'Date collected', 'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_collected'))],
                ],
                'fileName' => 'experiments.culture.environment.csv',
                'csvHeaders' => ['Experiment code', 'Culture code', 'Culture type', 'Environment code', 'Environment type', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        data_get($e, 'experiments_content.cultures_content.code', 'N/A'),
                        data_get($e, 'experiments_content.cultures_content.environment_sample_types.name', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_culture_parasite_table' => [
                'tableId' => 'experiment_culture_parasite_table',
                'subtitle' => 'conducted on cultures from parasite samples',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.cultures_content',
                    'experiments_content.cultures_content.parasites.parasite_species',
                    'experiments_content.cultures_content.parasites.parasites_origin',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [ParasiteSamples::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'html' => true, 'value' => fn (Experiments $e) => $linkToCulture(data_get($e, 'experiments_content.code'))],
                    ['label' => 'Culture type', 'valuePath' => 'experiments_content.type'],
                    ['label' => 'Parasite code', 'html' => true, 'value' => fn (Experiments $e) => $linkToParasiteSample(data_get($e, 'experiments_content.cultures_content.code'))],
                    ['label' => 'Parasite species', 'valuePath' => 'experiments_content.cultures_content.parasites.parasite_species.name_scientific'],
                    ['label' => 'Date collected', 'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.cultures_content.parasites.parasites_origin.date_collected'))],
                ],
                'fileName' => 'experiments.culture.parasite.csv',
                'csvHeaders' => ['Experiment code', 'Culture code', 'Culture type', 'Parasite code', 'Parasite species', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        data_get($e, 'experiments_content.cultures_content.code', 'N/A'),
                        data_get($e, 'experiments_content.cultures_content.parasites.parasite_species.name_scientific', 'N/A'),
                        $dateYmd(data_get($e, 'experiments_content.cultures_content.parasites.parasites_origin.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_culture_pool_table' => [
                'tableId' => 'experiment_culture_pool_table',
                'subtitle' => 'conducted on cultures from pools',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.cultures_content', 'experiments_content.cultures_content.pool_contents', 'experiments_content.cultures_content.pool_contents.samples'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Cultures::class], fn ($sq) => $sq->whereHasMorph('cultures_content', [Pools::class])),
                'extraColumns' => [
                    ['label' => 'Culture code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToCulture(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.cultureCode'],
                    ['label' => 'Culture type', 'valuePath' => 'experiments_content.type', 'filterModel' => 'originFilters.cultureType'],
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $linkToPool(data_get($e, 'experiments_content.cultures_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolContentsCollectedRangeForPoolModel(data_get($e, 'experiments_content.cultures_content')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'value' => fn (Experiments $e) => $this->poolContentsDetailsCombinedHtmlForPoolModel(
                            data_get($e, 'experiments_content.cultures_content'),
                            'exp-'.$e->id.'-culture-pool-contents'
                        ),
                        'html' => true,
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                    ['label' => 'Date collected (culture)', 'value' => fn (Experiments $e) => $dateYmd(data_get($e, 'experiments_content.date_collected'))],
                ],
                'filters' => [
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'cultureCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'cultureType');
                        if ($type) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], fn (Builder $sq) => $sq->where('type', 'like', '%'.$type.'%'));
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $poolCode = data_get($c->originFilters, 'poolCode');
                        if ($poolCode) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], function (Builder $sq) use ($c, $poolCode) {
                                $sq->whereHasMorph('cultures_content', [Pools::class], fn (Builder $pq) => $pq->where('code', 'like', '%'.$poolCode.'%'))
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $poolCode));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $search = data_get($c->originFilters, 'contentSearch');
                        if ($search) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], function (Builder $sq) use ($c, $search) {
                                $sq->whereHasMorph('cultures_content', [Pools::class], function (Builder $pq) use ($c, $search) {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($c, $search) {
                                        $pcq->whereHasMorph('samples', [
                                            HumanSamples::class,
                                            AnimalSamples::class,
                                            EnvironmentSamples::class,
                                            ParasiteSamples::class,
                                            NucleicAcids::class,
                                            Cultures::class,
                                            Pools::class,
                                        ], function (Builder $samplesQ) use ($c, $search) {
                                            $samplesQ->where('code', 'like', '%'.$search.'%')
                                                ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $search));
                                        });
                                    });
                                })->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $search));
                            });
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $start = data_get($c->originFilters, 'collectedStart');
                        $end = data_get($c->originFilters, 'collectedEnd');
                        if ($start || $end) {
                            $q->whereHasMorph('experiments_content', [Cultures::class], function (Builder $sq) use ($start, $end) {
                                $sq->whereHasMorph('cultures_content', [Pools::class], function (Builder $pq) use ($start, $end) {
                                    $pq->whereHas('pool_contents', function (Builder $pcq) use ($start, $end) {
                                        $pcq->whereHasMorph('samples', [
                                            HumanSamples::class,
                                            AnimalSamples::class,
                                            EnvironmentSamples::class,
                                            ParasiteSamples::class,
                                            NucleicAcids::class,
                                            Cultures::class,
                                        ], function (Builder $samplesQ) use ($start, $end) {
                                            if ($start && $end) {
                                                $samplesQ->whereBetween('date_collected', [$start, $end]);
                                            } elseif ($start) {
                                                $samplesQ->where('date_collected', '>=', $start);
                                            } else {
                                                $samplesQ->where('date_collected', '<=', $end);
                                            }
                                        });
                                    });
                                });
                            });
                        }

                        return $q;
                    },
                ],
                'fileName' => 'experiments.culture.pool.csv',
                'csvHeaders' => ['Experiment code', 'Culture code', 'Culture type', 'Pool code', 'Content type', 'Content code', 'Sampling site', 'Date collected', 'Date collected (culture)', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    /** @var Pools|null $pool */
                    $pool = data_get($e, 'experiments_content.cultures_content');
                    $contents = collect(data_get($pool, 'pool_contents', []))->filter(fn ($pc) => data_get($pc, 'samples') !== null)->values();

                    $base = [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        data_get($e, 'experiments_content.type', 'N/A'),
                        data_get($pool, 'code', 'N/A'),
                    ];

                    $tail = [
                        $dateYmd(data_get($e, 'experiments_content.date_collected')),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
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
            ],

            'experiment_pool_table' => [
                'tableId' => 'experiment_pool_table',
                'subtitle' => 'conducted on pools',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => ['experiments_content', 'experiments_content.pool_contents', 'experiments_content.pool_contents.samples'],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Pools::class]),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($linkToPool(data_get($e, 'experiments_content.code')), $e->experiments_content), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Contents type(s)',
                        'value' => fn (Experiments $e) => $this->poolContentTypesLabel($e),
                        'filterModel' => 'originFilters.poolContentType',
                    ],
                    [
                        'label' => 'Contents count',
                        'value' => fn (Experiments $e) => (string) $this->poolContents($e)->count(),
                    ],
                    [
                        'label' => 'Contents code(s)',
                        'html' => true,
                        'value' => fn (Experiments $e) => $this->expandableLinksHtml('exp-'.$e->id.'-pool-codes', $this->poolContentCodeLinkItems($e)),
                        'filterModel' => 'originFilters.poolContentCode',
                    ],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolCollectedRange($e),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                ],
                'filters' => [
                    fn (Builder $q, self $c) => ($c->originFilters['poolCode'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], function (Builder $sq) use ($c) {
                            $search = (string) $c->originFilters['poolCode'];

                            $sq->where('code', 'like', '%'.$search.'%')
                                ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $search));
                        })
                        : $q,
                    fn (Builder $q, self $c) => ($c->originFilters['poolContentType'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], function (Builder $sq) use ($c) {
                            $search = (string) $c->originFilters['poolContentType'];
                            $sq->whereHas('pool_contents', function (Builder $pcq) use ($search) {
                                $pcq->where(function (Builder $w) use ($search) {
                                    foreach ([HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class, NucleicAcids::class] as $type) {
                                        $label = match ($type) {
                                            HumanSamples::class => 'Human samples',
                                            AnimalSamples::class => 'Animal samples',
                                            EnvironmentSamples::class => 'Environmental samples',
                                            ParasiteSamples::class => 'Parasite samples',
                                            NucleicAcids::class => 'Nucleic acids',
                                            default => class_basename($type),
                                        };

                                        if (stripos($label, $search) !== false || stripos(class_basename($type), $search) !== false) {
                                            $w->orWhere('samples_type', $type);
                                        }
                                    }
                                });
                            });
                        })
                        : $q,
                    fn (Builder $q, self $c) => ($c->originFilters['poolContentCode'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], function (Builder $sq) use ($c) {
                            $search = (string) $c->originFilters['poolContentCode'];

                            $sq->whereHas('pool_contents', fn (Builder $pcq) => $pcq->whereHasMorph('samples', [
                                HumanSamples::class,
                                AnimalSamples::class,
                                EnvironmentSamples::class,
                                ParasiteSamples::class,
                                NucleicAcids::class,
                            ], function (Builder $cq) use ($c, $search) {
                                $cq->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $search));
                            }));
                        })
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('experiments_content', [Pools::class], function (Builder $sq) use ($c) {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;

                            $sq->whereHas('pool_contents', function (Builder $pcq) use ($start, $end) {
                                $pcq->where(function (Builder $w) use ($start, $end) {
                                    $w->whereHasMorph('samples', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class], function (Builder $cq) use ($start, $end) {
                                        if ($start && $end) {
                                            $cq->whereBetween('date_collected', [$start, $end]);
                                        } elseif ($start) {
                                            $cq->where('date_collected', '>=', $start);
                                        } else {
                                            $cq->where('date_collected', '<=', $end);
                                        }
                                    });

                                    $w->orWhereHasMorph('samples', [NucleicAcids::class], function (Builder $nq) use ($start, $end) {
                                        $nq->whereHasMorph('nucleic_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class], function (Builder $cq) use ($start, $end) {
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
                        })
                        : $q,
                ],
                'fileName' => 'experiments.pool.csv',
                'csvHeaders' => ['Experiment code', 'Pool code', 'Contents type(s)', 'Contents count', 'Contents code(s)', 'Collected date(s)', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    return [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                        $this->poolContentTypesLabel($e),
                        $this->poolContents($e)->count(),
                        collect($this->poolContentCodeLinkItems($e))->pluck('label')->implode('; '),
                        $this->poolCollectedRange($e),
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],

            'experiment_pool_human_table' => [
                'tableId' => 'experiment_pool_human_table',
                'subtitle' => 'conducted on pools (human content)',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.pool_contents',
                    'experiments_content.pool_contents.samples',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pq) => $pq->where('samples_type', HumanSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $linkToPool(data_get($e, 'experiments_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolCollectedRangeByType($e, HumanSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Experiments $e) => $this->poolContentsDetailsHtml($e, HumanSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    fn (Builder $q, self $c) => ($c->originFilters['poolCode'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$c->originFilters['poolCode'].'%'))
                        : $q,
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [HumanSamples::class], function (Builder $hq) use ($c) {
                            $search = (string) $c->originFilters['contentSearch'];
                            $hq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            });
                        })))
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [HumanSamples::class], function (Builder $hq) use ($c) {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;
                            if ($start && $end) {
                                $hq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $hq->where('date_collected', '>=', $start);
                            } else {
                                $hq->where('date_collected', '<=', $end);
                            }
                        })))
                        : $q,
                ],
                'fileName' => 'experiments.pool.human.csv',
                'csvHeaders' => ['Experiment code', 'Pool code', 'Human sample code', 'Patient code', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    $contents = $this->poolContents($e)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === HumanSamples::class)->values();

                    $base = [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                    ];

                    $tail = [
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
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
            ],

            'experiment_pool_animal_table' => [
                'tableId' => 'experiment_pool_animal_table',
                'subtitle' => 'conducted on pools (animal content)',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.pool_contents',
                    'experiments_content.pool_contents.samples',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pq) => $pq->where('samples_type', AnimalSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $linkToPool(data_get($e, 'experiments_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolCollectedRangeByType($e, AnimalSamples::class),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Experiments $e) => $this->poolContentsDetailsHtml($e, AnimalSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    fn (Builder $q, self $c) => ($c->originFilters['poolCode'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$c->originFilters['poolCode'].'%'))
                        : $q,
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [AnimalSamples::class], function (Builder $aq) use ($c) {
                            $search = (string) $c->originFilters['contentSearch'];
                            $aq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('animals', fn (Builder $anq) => $anq->where('code', 'like', '%'.$search.'%'))
                                    ->orWhereHas('animals.animal_species', fn (Builder $asq) => $asq->where('name_common', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sample_types', fn (Builder $stq) => $stq->where('name', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            });
                        })))
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [AnimalSamples::class], function (Builder $aq) use ($c) {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;
                            if ($start && $end) {
                                $aq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $aq->where('date_collected', '>=', $start);
                            } else {
                                $aq->where('date_collected', '<=', $end);
                            }
                        })))
                        : $q,
                ],
                'fileName' => 'experiments.pool.animal.csv',
                'csvHeaders' => ['Experiment code', 'Pool code', 'Animal sample code', 'Animal code', 'Animal species', 'Sample type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    $contents = $this->poolContents($e)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === AnimalSamples::class)->values();

                    $base = [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                    ];

                    $tail = [
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
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
            ],

            'experiment_pool_environment_table' => [
                'tableId' => 'experiment_pool_environment_table',
                'subtitle' => 'conducted on pools (environment content)',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.pool_contents',
                    'experiments_content.pool_contents.samples',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pq) => $pq->where('samples_type', EnvironmentSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $linkToPool(data_get($e, 'experiments_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolCollectedRange($e),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Experiments $e) => $this->poolContentsDetailsHtml($e, EnvironmentSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    fn (Builder $q, self $c) => ($c->originFilters['poolCode'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$c->originFilters['poolCode'].'%'))
                        : $q,
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [EnvironmentSamples::class], function (Builder $eq) use ($c) {
                            $search = (string) $c->originFilters['contentSearch'];
                            $eq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('environment_sample_types', fn (Builder $tq) => $tq->where('name', 'like', '%'.$search.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'));
                            });
                        })))
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [EnvironmentSamples::class], function (Builder $eq) use ($c) {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;
                            if ($start && $end) {
                                $eq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $eq->where('date_collected', '>=', $start);
                            } else {
                                $eq->where('date_collected', '<=', $end);
                            }
                        })))
                        : $q,
                ],
                'fileName' => 'experiments.pool.environment.csv',
                'csvHeaders' => ['Experiment code', 'Pool code', 'Environment code', 'Environment type', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    $contents = $this->poolContents($e)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === EnvironmentSamples::class)->values();

                    $base = [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                    ];

                    $tail = [
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
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
            ],

            'experiment_pool_parasite_table' => [
                'tableId' => 'experiment_pool_parasite_table',
                'subtitle' => 'conducted on pools (parasite content)',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.pool_contents',
                    'experiments_content.pool_contents.samples',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pq) => $pq->where('samples_type', ParasiteSamples::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $linkToPool(data_get($e, 'experiments_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolCollectedRange($e),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Experiments $e) => $this->poolContentsDetailsHtml($e, ParasiteSamples::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    fn (Builder $q, self $c) => ($c->originFilters['poolCode'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$c->originFilters['poolCode'].'%'))
                        : $q,
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [ParasiteSamples::class], function (Builder $pq) use ($c) {
                            $search = (string) $c->originFilters['contentSearch'];
                            $pq->where(function (Builder $w) use ($search) {
                                $w->where('code', 'like', '%'.$search.'%')
                                    ->orWhereHas('parasites.parasite_species', fn (Builder $psq) => $psq->where('name_common', 'like', '%'.$search.'%')->orWhere('name_scientific', 'like', '%'.$search.'%'))
                                    ->orWhereHas('parasites', fn (Builder $parq) => $parq->where('sex', 'like', '%'.$search.'%')->orWhere('stage', 'like', '%'.$search.'%'))
                                    ->orWhereHas('parasites', fn (Builder $parq) => $parq->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'))));
                            });
                        })))
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [ParasiteSamples::class], function (Builder $pq) use ($c) {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;
                            if ($start && $end) {
                                $pq->whereBetween('date_collected', [$start, $end]);
                            } elseif ($start) {
                                $pq->where('date_collected', '>=', $start);
                            } else {
                                $pq->where('date_collected', '<=', $end);
                            }
                        })))
                        : $q,
                ],
                'fileName' => 'experiments.pool.parasite.csv',
                'csvHeaders' => ['Experiment code', 'Pool code', 'Parasite sample code', 'Tick species', 'Tick sex', 'Tick stage', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    $contents = $this->poolContents($e)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === ParasiteSamples::class)->values();

                    $base = [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                    ];

                    $tail = [
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];

                    if ($contents->isEmpty()) {
                        return [array_merge($base, ['N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'], $tail)];
                    }

                    return $contents->map(function ($pc) use ($base, $tail): array {
                        $sampleCode = data_get($pc, 'samples.code') ?? 'N/A';
                        $species = data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A';
                        $sex = data_get($pc, 'samples.parasites.sex') ?? 'N/A';
                        $stage = data_get($pc, 'samples.parasites.stage') ?? 'N/A';
                        $primary = $this->poolContentPrimarySiteAndDate($pc);
                        $site = $primary['site'] ?? 'N/A';
                        $date = $primary['date'] ? (string) Carbon::parse($primary['date'])->format('Y-m-d') : 'N/A';

                        return array_merge($base, [$sampleCode, $species, $sex, $stage, $site, $date], $tail);
                    })->all();
                },
            ],

            'experiment_pool_nucleic_table' => [
                'tableId' => 'experiment_pool_nucleic_table',
                'subtitle' => 'conducted on pools (nucleic acid content)',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => false,
                'with' => [
                    'experiments_content',
                    'experiments_content.pool_contents',
                    'experiments_content.pool_contents.samples',
                ],
                'scope' => fn (Builder $q) => $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pq) => $pq->where('samples_type', NucleicAcids::class))),
                'extraColumns' => [
                    ['label' => 'Pool code', 'html' => true, 'value' => fn (Experiments $e) => $linkToPool(data_get($e, 'experiments_content.code')), 'filterModel' => 'originFilters.poolCode'],
                    [
                        'label' => 'Collected date(s)',
                        'value' => fn (Experiments $e) => $this->poolCollectedRange($e),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Contents details',
                        'html' => true,
                        'value' => fn (Experiments $e) => $this->poolContentsDetailsHtml($e, NucleicAcids::class),
                        'filterModel' => 'originFilters.contentSearch',
                    ],
                ],
                'filters' => [
                    fn (Builder $q, self $c) => ($c->originFilters['poolCode'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->where('code', 'like', '%'.$c->originFilters['poolCode'].'%'))
                        : $q,
                    fn (Builder $q, self $c) => filled($c->originFilters['contentSearch'] ?? null)
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [NucleicAcids::class], function (Builder $nq) use ($c) {
                            $search = (string) $c->originFilters['contentSearch'];
                            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

                            if ($isSqlite) {
                                // SQLite struggles with very deep nested EXISTS/OR trees (parser stack overflow).
                                // Use a shallow, but still useful, filter: nucleic code/type, content code, direct sampling site,
                                // and basic parasite species/sex/stage.
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
                                    ->orWhereHasMorph('nucleic_content', [ParasiteSamples::class], function (Builder $psq) use ($search) {
                                        $psq->where('code', 'like', '%'.$search.'%')
                                            ->orWhereHas('parasites', function (Builder $pq) use ($search) {
                                                $pq->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%')));
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
                                                    ->orWhereHas('parasites', fn (Builder $pq) => $pq->whereHasMorph('parasites_origin', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ssq) => $ssq->where('name', 'like', '%'.$search.'%'))));
                                            });
                                    });
                            });
                        })))
                        : $q,
                    fn (Builder $q, self $c) => (($c->originFilters['collectedStart'] ?? null) || ($c->originFilters['collectedEnd'] ?? null))
                        ? $q->whereHasMorph('experiments_content', [Pools::class], fn ($sq) => $sq->whereHas('pool_contents', fn ($pcq) => $pcq->whereHasMorph('samples', [NucleicAcids::class], function (Builder $nq) use ($c) {
                            $start = $c->originFilters['collectedStart'] ?? null;
                            $end = $c->originFilters['collectedEnd'] ?? null;
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
                        })))
                        : $q,
                ],
                'fileName' => 'experiments.pool.nucleic.csv',
                'csvHeaders' => ['Experiment code', 'Pool code', 'Nucleic code', 'Nucleic type', 'Content type', 'Content code', 'Sampling site', 'Date collected', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    $contents = $this->poolContents($e)->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === NucleicAcids::class)->values();

                    $base = [
                        $e->code ?? 'N/A',
                        data_get($e, 'experiments_content.code', 'N/A'),
                    ];

                    $tail = [
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
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
            ],

            default => [
                'tableId' => 'experiments_table',
                'subtitle' => '',
                'showPhoto' => true,
                'showProjectColumnInGuestMode' => true,
                'with' => [],
                'filters' => [
                    function (Builder $q, self $c) {
                        $type = data_get($c->originFilters, 'contentType');
                        if ($type) {
                            $q->where('experiments_content_type', 'like', '%'.$type.'%');
                        }

                        return $q;
                    },
                    function (Builder $q, self $c) {
                        $code = data_get($c->originFilters, 'contentCode');
                        if ($code) {
                            $q->whereHasMorph('experiments_content', [
                                HumanSamples::class,
                                AnimalSamples::class,
                                EnvironmentSamples::class,
                                ParasiteSamples::class,
                                NucleicAcids::class,
                                Cultures::class,
                                Pools::class,
                            ], function (Builder $sq) use ($c, $code) {
                                $sq->where('code', 'like', '%'.$code.'%')
                                    ->orWhereHas('tubes', fn (Builder $tq) => $c->applyTubeCodeOrAliasFilter($tq, $code));
                            });
                        }

                        return $q;
                    },
                ],
                'extraColumns' => [
                    ['label' => 'Content code', 'html' => true, 'value' => fn (Experiments $e) => $withTubeAliases($contentLink($e), $e->experiments_content), 'filterModel' => 'originFilters.contentCode', 'filterPlaceholder' => 'Filter'],
                    ['label' => 'Content type', 'html' => true, 'value' => $contentBadge, 'filterModel' => 'originFilters.contentType', 'filterPlaceholder' => 'Filter'],
                ],
                'fileName' => 'experiments.csv',
                'csvHeaders' => ['Experiment code', 'Content Type', 'Content code', 'Protocol', 'Technique Type', 'Pathogen', 'Outcome (discrete)', 'Outcome (quantitative)', 'Date tested', 'Performed by', 'Performed at'],
                'csvRow' => function (Experiments $e) use ($dateYmd) {
                    $contentType = match ($e->experiments_content_type) {
                        HumanSamples::class => 'Human Sample',
                        AnimalSamples::class => 'Animal Sample',
                        EnvironmentSamples::class => 'Environmental Sample',
                        ParasiteSamples::class => 'Parasite Sample',
                        NucleicAcids::class => 'Nucleic Acid',
                        Cultures::class => 'Culture',
                        Pools::class => 'Pool',
                        default => 'Unknown',
                    };

                    return [
                        $e->code ?? 'N/A',
                        $contentType,
                        $e->experiments_content?->code ?? 'N/A',
                        $e->protocols?->name ?? 'N/A',
                        data_get($e, 'protocols.techniques.type', 'N/A'),
                        $e->pathogens?->species ?? 'N/A',
                        $e->outcome_discrete ?? 'N/A',
                        $e->outcome_quant ?? 'N/A',
                        $dateYmd($e->date_tested),
                        trim(($e->people?->title ?? '').' '.($e->people?->first_name ?? '').' '.($e->people?->last_name ?? '')) ?: 'N/A',
                        $e->laboratories?->name ?? 'N/A',
                    ];
                },
            ],
        };
    }

    public function render()
    {
        $service = app(ExperimentsService::class);
        $additionalData = $service->listsForExperimentsIndex();

        // Lists for datalists / validation
        $this->exp_protocols = $additionalData['exp_protocols'] ?? collect();
        $this->pathogens = $additionalData['pathogens'] ?? collect();
        $this->laboratories_by_country = $additionalData['laboratories_by_country'] ?? [];
        $this->people = $additionalData['people'] ?? collect();

        $experiments = $this->buildBaseQueryForSelectedTable()->paginate($this->perPage, pageName: 'articles-page');

        $viewData = array_merge($additionalData, [
            'experiments' => $experiments,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $this->canEdit(),
        ]);

        return view('livewire.experiments-index', $viewData);
    }
}
