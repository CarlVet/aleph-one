<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\NucleicAcidsForm;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Protocols;
use App\Models\TubeRequests;
use App\Models\Tubes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('Nucleic Acids Index')]
class NucleicAcidsIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithPagination;

    public NucleicAcidsForm $form;

    protected function sortingPageName(): ?string
    {
        return 'articles-page';
    }

    /**
     * The list model is Tubes; nucleic-acid columns are reached through the
     * polymorphic tubes_content relation, so they are sorted via correlated
     * subqueries against the nucleic_acids table.
     */
    private function nucleicContentSort(string $column, ?array $join = null)
    {
        $sub = NucleicAcids::query()->whereColumn('nucleic_acids.id', 'tubes.tubes_content_id');

        if ($join !== null) {
            [$table, $foreignKey] = $join;
            $sub->join($table, 'nucleic_acids.'.$foreignKey, '=', $table.'.id');
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
            'state' => 'preservant',
            'nucleic_type' => fn ($q, $dir) => $q->orderBy($this->nucleicContentSort('nucleic_acids.type'), $dir),
            'protocol' => fn ($q, $dir) => $q->orderBy($this->nucleicContentSort('protocols.name', ['protocols', 'protocols_id']), $dir),
            'extractor' => fn ($q, $dir) => $q->orderBy($this->nucleicContentSort('people.last_name', ['people', 'people_id']), $dir),
            'extracted_at' => fn ($q, $dir) => $q->orderBy($this->nucleicContentSort('laboratories.name', ['laboratories', 'laboratories_id']), $dir),
            'date_extracted' => fn ($q, $dir) => $q->orderBy($this->nucleicContentSort('nucleic_acids.date_extracted'), $dir),
            'volume' => fn ($q, $dir) => $q->orderBy($this->nucleicContentSort('nucleic_acids.volume'), $dir),
        ];
    }

    public $tubeIdFilter;

    public $stateFilter;

    public $nucleicIdFilter;

    public $typeFilter;

    public $protocolFilter;

    public $extractorFilter;

    public $extractedAtFilter;

    public $startDate;

    public $endDate;

    public $volumeFilter;

    public $contentTypeFilter;

    public $contentIdFilter;

    public $subProjectCodeFilter;

    // Human-specific filters
    public $sampleTypeFilter;

    public $samplingSiteFilter;

    public $collectionStartFilter;

    public $collectionEndFilter;

    // Animal-specific filters
    public $speciesFilter;

    public $siteFilter;

    public array $selectedNucleicTubes = [];

    public bool $selectAllFiltered = false;

    // Tube request modal properties
    public $showTubeRequestModal = false;

    public $selectedTubeId;

    public $selectedTube;

    public $targetProjectId;

    public $requestMessage = '';

    public $userProjects = [];

    public $sourceProject;

    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function mount(): void
    {
        // Allow guest-only routes to land directly on a specific tab
        $routeName = request()->route()?->getName();

        $this->selectedTable = match ($routeName) {
            'guest.nucleic-acids.human' => 'human_samples_table',
            'guest.nucleic-acids.animal' => 'animal_samples_table',
            'guest.nucleic-acids.environment' => 'environment_samples_table',
            'guest.nucleic-acids.parasite' => 'parasite_samples_table',
            'guest.nucleic-acids.culture' => 'culture_samples_table',
            'guest.nucleic-acids.pool' => 'pool_samples_table',
            default => $this->selectedTable,
        };
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

    public function updateField($sampleId, $field, $value)
    {
        $tube = Tubes::find($sampleId);
        $ownerPeopleId = (int) optional(optional($tube)->tubes_content)->people_id;
        if (! $tube || ! $this->userCanMutateOwnedRecord($ownerPeopleId, 'nucleic_acids')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only edit records you registered.',
            ]);

            return;
        }

        $result = $this->form->updateField($sampleId, $field, $value);

        $this->dispatch('swal', [
            'icon' => $result['ok'] ? 'success' : 'error',
            'title' => $result['ok'] ? 'Success' : 'Error',
            'text' => $result['message'],
        ]);
    }

    public function delete(Tubes $tube)
    {
        $ownerPeopleId = (int) optional($tube->tubes_content)->people_id;
        if (! $this->userCanMutateOwnedRecord($ownerPeopleId, 'nucleic_acids')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only delete records you registered.',
            ]);

            return;
        }

        $tube->delete();

        $this->form->refreshData();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Nucleic tube deleted successfully!',
        ]);
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedNucleicTubes)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No selection',
                'text' => 'Please select at least one tube.',
            ]);

            return;
        }

        $tubes = Tubes::query()->with('tubes_content')->whereIn('id', $selectedIds->all())->get();
        $deleted = 0;

        foreach ($tubes as $tube) {
            $ownerPeopleId = (int) optional($tube->tubes_content)->people_id;
            if (! $this->userCanMutateOwnedRecord($ownerPeopleId, 'nucleic_acids')) {
                continue;
            }

            $tube->delete();
            $deleted++;
        }

        $this->selectedNucleicTubes = [];

        $this->dispatch('swal', [
            'icon' => $deleted > 0 ? 'success' : 'error',
            'title' => $deleted > 0 ? 'Success' : 'Nothing deleted',
            'text' => $deleted > 0
                ? "{$deleted} selected tube(s) deleted successfully!"
                : 'No selected tubes could be deleted.',
        ]);
    }

    public $isEditing = false; // To track editing state

    // Toggle the editing mode
    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('nucleic_acids')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You do not have permission to edit nucleic-acid records.',
            ]);

            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public string $selectedTable = 'nucleic_acids_table'; // To track table state

    // Toggle the table mode
    public function toggleTableMode()
    {
        $this->selectedTable = ! $this->selectedTable;
    }

    public function openTubeRequestModal($tubeId)
    {
        $this->resetValidation();
        $this->reset(['targetProjectId', 'requestMessage']);

        $this->selectedTubeId = $tubeId;
        $this->selectedTube = Tubes::with(['tubes_content', 'projects'])->find($tubeId);

        if ($this->selectedTube) {
            $this->sourceProject = $this->selectedTube->projects;

            // Load user projects (excluding the source project)
            $user = Auth::user();
            if ($user && $user->people && $this->sourceProject) {
                $this->userProjects = $user->people->projects()
                    ->where('projects.id', '!=', $this->sourceProject->id)
                    ->get();
            }
        }

        $this->showTubeRequestModal = true;
    }

    public function closeTubeRequestModal()
    {
        $this->showTubeRequestModal = false;
        $this->reset(['selectedTubeId', 'selectedTube', 'targetProjectId', 'requestMessage', 'sourceProject', 'userProjects']);
    }

    public function submitTubeRequest()
    {
        $this->validate([
            'targetProjectId' => 'required|exists:projects,id',
            'requestMessage' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        if (! $user || ! $user->people) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'User not found.',
            ]);

            return;
        }

        if (! $this->selectedTubeId || ! $this->selectedTube) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Tube information is missing.',
            ]);

            return;
        }

        // Check if there's already a pending request for this tube by this user
        $existingRequest = TubeRequests::where('tubes_id', $this->selectedTubeId)
            ->where('requester_id', $user->people->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'You already have a pending request for this tube.',
            ]);

            return;
        }

        try {
            TubeRequests::create([
                'tubes_id' => $this->selectedTubeId,
                'requester_id' => $user->people->id,
                'source_project_id' => $this->sourceProject->id,
                'target_project_id' => $this->targetProjectId,
                'status' => 'pending',
                'request_message' => $this->requestMessage,
            ]);

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Success',
                'text' => 'Tube request submitted successfully! The principal investigator will be notified.',
            ]);
            $this->closeTubeRequestModal();
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Failed to submit request: '.$e->getMessage(),
            ]);
        }
    }

    public function updating($field)
    {
        if (
            is_string($field)
            && (
                str_starts_with($field, 'selectedNucleicTubes')
                || $field === 'selectAllFiltered'
            )
        ) {
            return;
        }

        // Reset pagination whenever a filter changes
        $this->resetPage('articles-page');
    }

    public function updatedSelectedTable(): void
    {
        $this->selectedNucleicTubes = [];
        $this->selectAllFiltered = false;
        $this->resetPage('articles-page');
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedNucleicTubes = [];

            return;
        }

        $query = $this->applyFilters($this->buildBaseQueryForSelectedTable());

        if (! $this->isGuestMode()) {
            $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
            if ($currentPeopleId <= 0) {
                $this->selectedNucleicTubes = [];

                return;
            }

            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($nucleicQuery) use ($currentPeopleId) {
                $nucleicQuery->where('people_id', $currentPeopleId);
            });
        }

        $ids = $query
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $this->selectedNucleicTubes = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    protected function applyFilters($query)
    {
        // Apply filters dynamically if they exist
        if ($this->tubeIdFilter) {
            $query->where(DB::raw("CONCAT(code, ' ', alias_code)"), 'like', '%'.$this->tubeIdFilter.'%');
        }
        if ($this->stateFilter) {
            $query->where('preservant', 'like', '%'.$this->stateFilter.'%');
        }
        if ($this->nucleicIdFilter) {
            $query->whereHas('tubes_content', function ($q) {
                $q->where('tubes_content_id', 'like', '%'.$this->nucleicIdFilter.'%');
            });
        }
        if ($this->typeFilter) {
            $query->whereHas('tubes_content', function ($q) {
                $q->where('type', 'like', '%'.$this->typeFilter.'%');
            });
        }
        if ($this->protocolFilter) {
            $query->whereHasMorph(
                'tubes_content',  // Polymorphic relation
                [NucleicAcids::class],  // Only check tubes containing nucleic acids
                function ($query) {
                    $query->whereHas('protocols', function ($q) {
                        $q->where('name', 'like', '%'.$this->protocolFilter.'%');
                    });
                }
            );
        }
        if ($this->extractorFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($nucleicQuery) {
                $nucleicQuery->whereHas('people', function ($peopleQuery) {
                    $peopleQuery->where('first_name', 'like', '%'.$this->extractorFilter.'%')
                        ->orWhere('last_name', 'like', '%'.$this->extractorFilter.'%')
                        ->orWhere('email', 'like', '%'.$this->extractorFilter.'%');
                });
            });
        }
        if ($this->extractedAtFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($nucleicQuery) {
                $nucleicQuery->whereHas('laboratories', function ($laboratoryQuery) {
                    $laboratoryQuery->where('name', 'like', '%'.$this->extractedAtFilter.'%');
                });
            });
        }
        if ($this->startDate && $this->endDate) {
            $query->whereHas('tubes_content', function ($q) {
                $q->whereBetween('date_extracted', [$this->startDate, $this->endDate]);
            });
        } elseif ($this->startDate) {
            $query->whereHas('tubes_content', function ($q) {
                $q->where('date_extracted', '>=', $this->startDate);
            });
        } elseif ($this->endDate) {
            $query->whereHas('tubes_content', function ($q) {
                $q->where('date_extracted', '<=', $this->endDate);
            });
        }

        if ($this->volumeFilter) {
            $query->whereHas('tubes_content', function ($q) {
                $q->where('volume', 'like', '%'.$this->volumeFilter.'%');
            });
        }

        if ($this->contentTypeFilter) {
            $query->whereHas('tubes_content', function ($q) {
                $q->where('nucleic_content_type', 'like', '%'.$this->contentTypeFilter.'%');
            });
        }

        if ($this->contentIdFilter) {
            $query->whereHasMorph(
                'tubes_content',
                [NucleicAcids::class],
                function ($q) {
                    $q->whereHasMorph(
                        'nucleic_content',
                        $this->selectedTableConfig()['morph'],
                        function ($query) {
                            $query->where('code', 'like', '%'.$this->contentIdFilter.'%');
                        }
                    );
                }
            );
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) {
                $q->whereHas('subProjectAssignment.subProject', function ($subProjectQuery) {
                    $subProjectQuery->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
                });
            });
        }

        // Human-specific filters
        if ($this->selectedTable === 'human_samples_table' && $this->sampleTypeFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) {
                $q->whereHasMorph('nucleic_content', [HumanSamples::class], function ($q) {
                    $q->whereHas('sample_types', function ($q) {
                        $q->where('name', 'like', '%'.$this->sampleTypeFilter.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'human_samples_table' && $this->samplingSiteFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) {
                $q->whereHasMorph('nucleic_content', [HumanSamples::class], function ($q) {
                    $q->whereHas('sampling_sites', function ($q) {
                        $q->where('name', 'like', '%'.$this->samplingSiteFilter.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'human_samples_table' && ($this->collectionStartFilter || $this->collectionEndFilter)) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) {
                $q->whereHasMorph('nucleic_content', [HumanSamples::class], function ($q) {
                    if ($this->collectionStartFilter && $this->collectionEndFilter) {
                        $q->whereBetween('date_collected', [$this->collectionStartFilter, $this->collectionEndFilter]);

                        return;
                    }
                    if ($this->collectionStartFilter) {
                        $q->where('date_collected', '>=', $this->collectionStartFilter);
                    }
                    if ($this->collectionEndFilter) {
                        $q->where('date_collected', '<=', $this->collectionEndFilter);
                    }
                });
            });
        }

        // Animal-specific filters
        if ($this->selectedTable === 'animal_samples_table' && $this->speciesFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) {
                $q->whereHasMorph('nucleic_content', [AnimalSamples::class], function ($q) {
                    $q->whereHas('animals.animal_species', function ($q) {
                        $q->where('name_common', 'like', '%'.$this->speciesFilter.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'animal_samples_table' && $this->siteFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) {
                $q->whereHasMorph('nucleic_content', [AnimalSamples::class], function ($q) {
                    $q->whereHas('sampling_sites', function ($q) {
                        $q->where('name', 'like', '%'.$this->siteFilter.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'animal_samples_table' && $this->sampleTypeFilter) {
            $query->whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) {
                $q->whereHasMorph('nucleic_content', [AnimalSamples::class], function ($q) {
                    $q->whereHas('sample_types', function ($q) {
                        $q->where('name', 'like', '%'.$this->sampleTypeFilter.'%');
                    });
                });
            });
        }

        return $query;
    }

    /**
     * @return array{morph: array<int, class-string>, csvFile: string, includeContentType: bool, extraWith: array<int, string>}
     */
    protected function selectedTableConfig(): array
    {
        return match ($this->selectedTable) {
            'human_samples_table' => [
                'morph' => [HumanSamples::class],
                'csvFile' => 'nucleic.human.tubes.csv',
                'includeContentType' => false,
                'extraWith' => [
                    'tubes_content.nucleic_content.humans',
                    'tubes_content.nucleic_content.sampling_sites',
                    'tubes_content.nucleic_content.sample_types',
                ],
            ],
            'animal_samples_table' => [
                'morph' => [AnimalSamples::class],
                'csvFile' => 'nucleic.animal.tubes.csv',
                'includeContentType' => false,
                'extraWith' => [
                    'tubes_content.nucleic_content.animals',
                    'tubes_content.nucleic_content.animals.animal_species',
                    'tubes_content.nucleic_content.sampling_sites',
                    'tubes_content.nucleic_content.sample_types',
                ],
            ],
            'environment_samples_table' => [
                'morph' => [EnvironmentSamples::class],
                'csvFile' => 'nucleic.environment.tubes.csv',
                'includeContentType' => false,
                'extraWith' => [
                    'tubes_content.nucleic_content.sampling_sites',
                    'tubes_content.nucleic_content.environment_sample_types',
                ],
            ],
            'parasite_samples_table' => [
                'morph' => [ParasiteSamples::class],
                'csvFile' => 'parasite.nucleic.tubes.csv',
                'includeContentType' => false,
                'extraWith' => [],
            ],
            'culture_samples_table' => [
                'morph' => [Cultures::class],
                'csvFile' => 'culture.nucleic.tubes.csv',
                'includeContentType' => false,
                'extraWith' => [],
            ],
            'pool_samples_table' => [
                'morph' => [Pools::class],
                'csvFile' => 'pool.nucleic.tubes.csv',
                'includeContentType' => false,
                'extraWith' => [
                    'tubes_content.nucleic_content.pool_contents',
                ],
            ],
            default => [
                'morph' => [AnimalSamples::class, HumanSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Experiments::class, Cultures::class, Pools::class],
                'csvFile' => 'nucleic.tubes.csv',
                'includeContentType' => true,
                'extraWith' => [],
            ],
        };
    }

    protected function buildBaseQueryForSelectedTable(): Builder
    {
        $config = $this->selectedTableConfig();

        $with = array_values(array_unique(array_merge([
            'tubes_content',
            'tubes_content.subProjectAssignment.subProject',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.laboratories',
            'tubes_content.projects',
            'tubes_content.nucleic_content',
            'projects',
        ], $config['extraWith'])));

        $query = Tubes::whereHasMorph('tubes_content', [NucleicAcids::class], function ($q) use ($config) {
            $q->whereHasMorph('nucleic_content', $config['morph']);
        })->with($with);

        if ($this->isGuestMode()) {
            $query->where('is_private', false);
        } else {
            $query->where('projects_id', $this->projectId);
        }

        return $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);
    }

    public function export()
    {
        $config = $this->selectedTableConfig();
        $fileName = $config['csvFile'];

        $query = $this->applyFilters($this->buildBaseQueryForSelectedTable());

        $selectedTable = $this->selectedTable;

        $callback = function () use ($query, $selectedTable) {
            $file = fopen('php://output', 'w');

            if ($selectedTable === 'human_samples_table') {
                fputcsv($file, ['Tube code', 'Sub-project', 'Nucleic acid code', 'Nucleic acid type', 'Extraction protocol', 'Extracted by', 'Extracted at', 'Date extracted', 'Elution type', 'Volume', 'Patient code', 'Sampling site', 'Sample type']);
            } elseif ($selectedTable === 'animal_samples_table') {
                fputcsv($file, ['Tube code', 'Alias code', 'Sub-project', 'Nucleic acid code', 'Nucleic acid type', 'Extraction protocol', 'Extracted by', 'Extracted at', 'Date extracted', 'Elution type', 'Volume', 'Sample code', 'Species', 'Sampling site', 'Sample type']);
            } elseif ($selectedTable === 'environment_samples_table') {
                fputcsv($file, ['Tube code', 'Alias code', 'Sub-project', 'Nucleic acid code', 'Nucleic acid type', 'Extraction protocol', 'Extracted by', 'Extracted at', 'Date extracted', 'Elution type', 'Volume', 'Sample code', 'Sampling site', 'Sample type']);
            } else {
                fputcsv($file, ['Nucleic tube code', 'Sub-project', 'Content type', 'Content code', 'Elution type', 'Nucleic acid type', 'Extraction protocol', 'Extracted by', 'Extracted at', 'Date extracted', 'Volume']);
            }

            $query->chunk(500, function ($tubes) use ($file, $selectedTable) {
                foreach ($tubes as $tube) {
                    if ($selectedTable === 'human_samples_table') {
                        fputcsv($file, [
                            $tube->code ?? 'N/A',
                            data_get($tube, 'tubes_content.subProjectAssignment.subProject.code') ?? 'N/A',
                            $tube->tubes_content?->code ?? 'N/A',
                            $tube->tubes_content?->type ?? 'N/A',
                            $tube->tubes_content?->protocols?->name ?? 'N/A',
                            trim((string) (($tube->tubes_content?->people?->title ? $tube->tubes_content?->people?->title.' ' : '').($tube->tubes_content?->people?->first_name ?? '').' '.($tube->tubes_content?->people?->last_name ?? ''))) ?: 'N/A',
                            $tube->tubes_content?->laboratories?->name ?? 'N/A',
                            $tube->tubes_content?->date_extracted ? Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : 'N/A',
                            $tube->preservant ?? 'N/A',
                            $tube->tubes_content?->volume ?? 'N/A',
                            data_get($tube, 'tubes_content.nucleic_content.humans.code', 'N/A'),
                            data_get($tube, 'tubes_content.nucleic_content.sampling_sites.name', 'N/A'),
                            data_get($tube, 'tubes_content.nucleic_content.sample_types.name', 'N/A'),
                        ]);

                        continue;
                    }

                    if ($selectedTable === 'animal_samples_table') {
                        fputcsv($file, [
                            $tube->code ?? 'N/A',
                            $tube->alias_code ?? 'N/A',
                            data_get($tube, 'tubes_content.subProjectAssignment.subProject.code') ?? 'N/A',
                            $tube->tubes_content?->code ?? 'N/A',
                            $tube->tubes_content?->type ?? 'N/A',
                            $tube->tubes_content?->protocols?->name ?? 'N/A',
                            trim((string) (($tube->tubes_content?->people?->title ? $tube->tubes_content?->people?->title.' ' : '').($tube->tubes_content?->people?->first_name ?? '').' '.($tube->tubes_content?->people?->last_name ?? ''))) ?: 'N/A',
                            $tube->tubes_content?->laboratories?->name ?? 'N/A',
                            $tube->tubes_content?->date_extracted ? Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : 'N/A',
                            $tube->preservant ?? 'N/A',
                            $tube->tubes_content?->volume ?? 'N/A',
                            data_get($tube, 'tubes_content.nucleic_content.code', 'N/A'),
                            data_get($tube, 'tubes_content.nucleic_content.animals.animal_species.name_common', 'N/A'),
                            data_get($tube, 'tubes_content.nucleic_content.sampling_sites.name', 'N/A'),
                            data_get($tube, 'tubes_content.nucleic_content.sample_types.name', 'N/A'),
                        ]);

                        continue;
                    }

                    if ($selectedTable === 'environment_samples_table') {
                        fputcsv($file, [
                            $tube->code ?? 'N/A',
                            $tube->alias_code ?? 'N/A',
                            data_get($tube, 'tubes_content.subProjectAssignment.subProject.code') ?? 'N/A',
                            $tube->tubes_content?->code ?? 'N/A',
                            $tube->tubes_content?->type ?? 'N/A',
                            $tube->tubes_content?->protocols?->name ?? 'N/A',
                            trim((string) (($tube->tubes_content?->people?->title ? $tube->tubes_content?->people?->title.' ' : '').($tube->tubes_content?->people?->first_name ?? '').' '.($tube->tubes_content?->people?->last_name ?? ''))) ?: 'N/A',
                            $tube->tubes_content?->laboratories?->name ?? 'N/A',
                            $tube->tubes_content?->date_extracted ? Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : 'N/A',
                            $tube->preservant ?? 'N/A',
                            $tube->tubes_content?->volume ?? 'N/A',
                            data_get($tube, 'tubes_content.nucleic_content.code', 'N/A'),
                            data_get($tube, 'tubes_content.nucleic_content.sampling_sites.name', 'N/A'),
                            data_get($tube, 'tubes_content.nucleic_content.environment_sample_types.name', 'N/A'),
                        ]);

                        continue;
                    }

                    $contentType = match ($tube->tubes_content?->nucleic_content_type) {
                        'App\Models\HumanSamples' => 'Human Sample',
                        'App\Models\AnimalSamples' => 'Animal Sample',
                        'App\Models\EnvironmentSamples' => 'Environment Sample',
                        'App\Models\ParasiteSamples' => 'Parasite Sample',
                        'App\Models\Experiments' => 'Experiment',
                        'App\Models\Cultures' => 'Culture',
                        'App\Models\Pools' => 'Pool',
                        default => 'Unknown',
                    };

                    fputcsv($file, [
                        $tube->code ?? 'N/A',
                        data_get($tube, 'tubes_content.subProjectAssignment.subProject.code') ?? 'N/A',
                        $contentType,
                        data_get($tube, 'tubes_content.nucleic_content.code', 'N/A'),
                        $tube->preservant ?? 'N/A',
                        $tube->tubes_content?->type ?? 'N/A',
                        $tube->tubes_content?->protocols?->name ?? 'N/A',
                        trim((string) (($tube->tubes_content?->people?->title ? $tube->tubes_content?->people?->title.' ' : '').($tube->tubes_content?->people?->first_name ?? '').' '.($tube->tubes_content?->people?->last_name ?? ''))) ?: 'N/A',
                        $tube->tubes_content?->laboratories?->name ?? 'N/A',
                        $tube->tubes_content?->date_extracted ? Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : 'N/A',
                        $tube->tubes_content?->volume ?? 'N/A',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->streamDownload($callback, $fileName);
    }

    public function render()
    {
        $preservants = ['Glycerol', 'Elution buffer', 'Formaline', 'Water', 'Ethanol', 'PBS', 'RNAlater'];
        $query = $this->buildBaseQueryForSelectedTable();

        $query = $this->applyFilters($query);

        $nucleic_tubes = $query->paginate($this->perPage, pageName: 'articles-page');

        $nucleicTypeQuery = NucleicAcids::query()->select('type')->whereNotNull('type');
        if ($this->isGuestMode()) {
            $nucleicTypeQuery->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $nucleicTypeQuery->where('projects_id', $this->projectId);
        }

        $nucleic_acids = $nucleicTypeQuery
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->map(fn ($type) => ['type' => $type])
            ->values();

        $nucleic_methods_available = Protocols::query()
            ->whereHas('techniques', function ($q) {
                $q->where('type', 'Nucleic Acids Extraction and Purification');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $laboratories_available = Laboratories::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        // Permission logic (copied from ParasiteSamplesIndex)
        $project = null;
        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('nucleic_acids');

        $viewData = [
            'nucleic_tubes' => $nucleic_tubes,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'isGuestMode' => $this->projectId === null,
            'canEdit' => $canEdit,
            'preservants' => $preservants,
            'nucleic_acids' => $nucleic_acids,
            'nucleic_methods_available' => $nucleic_methods_available,
            'laboratories_available' => $laboratories_available,
        ];

        return view('livewire.nucleic-acids-index', $viewData);
    }
}
