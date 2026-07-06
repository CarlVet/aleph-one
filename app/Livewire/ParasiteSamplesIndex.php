<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\ParasiteSamplesForm;
use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSampleObservation;
use App\Models\ParasiteSamplePhoto;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use App\Models\TubeRequests;
use App\Models\Tubes;
use App\Services\ParasiteSamplesService;
use App\Support\ParasitePhotoStorage;
use App\Support\ParasiteSampleObservationRecorder;
use App\Support\ProjectPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ParasiteSamplesIndex extends PlainComponent
{
    use ExportsTable;
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

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
            'parasite_species' => fn ($q, $dir) => $this->orderByRelation($q, ['parasites', 'parasite_species'], 'name_scientific', $dir),
            'sample_type' => fn ($q, $dir) => $this->orderByRelation($q, ['parasite_sample_types'], 'name', $dir),
            'stage' => fn ($q, $dir) => $this->orderByRelation($q, ['parasites'], 'stage', $dir),
            'sex' => fn ($q, $dir) => $this->orderByRelation($q, ['parasites'], 'sex', $dir),
            'state' => fn ($q, $dir) => $this->orderByRelation($q, ['parasites'], 'state', $dir),
            'identified_by' => fn ($q, $dir) => $this->orderByRelation($q, ['parasites', 'people'], 'last_name', $dir),
            'scientist' => fn ($q, $dir) => $this->orderByRelation($q, ['parasites', 'people'], 'last_name', $dir),
            'processed_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'last_name', $dir),
            'date_identified' => fn ($q, $dir) => $this->orderByRelation($q, ['parasites'], 'date_identified', $dir),
            'date_processed' => 'date_processed',
        ];
    }

    public function mount(): void
    {
        // Allow guest-only routes to land directly on a specific tab
        $routeName = request()->route()?->getName();

        $this->selectedTable = match ($routeName) {
            'guest.parasite-samples.human' => 'parasite_human_table',
            'guest.parasite-samples.animal' => 'parasite_animal_table',
            'guest.parasite-samples.environment' => 'parasite_environment_table',
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

    public function canMutateSampleRecord(?int $ownerPeopleId): bool
    {
        if ($this->isGuestMode()) {
            return false;
        }

        return $this->userCanMutateOwnedRecord($ownerPeopleId, 'parasite_samples');
    }

    public $photo;

    public $bulkPhoto;

    public $uploadingPhotoId = null;

    public $uploadErrors = [];

    public $currentPhotoId = null;

    public array $selectedParasiteSamples = [];

    public bool $selectAllFiltered = false;

    public $isEditing = false;

    public string $selectedTable = 'parasite_samples_table';

    // Options for select inputs
    public $sampleTypes;

    public $sexOptions = ['Male', 'Female', 'Unknown', 'NA'];

    public $stageOptions = ['Adult', 'Pupa', 'Larva', 'Egg', 'Unknown', 'NA'];

    public $stateOptions = ['Engorged', 'Partially engorged', 'Not engorged', 'NA'];

    public $parasiteSpecies;

    public $animalSamples;

    public $humanSamples;

    public $environmentSamples;

    // Filters
    public ParasiteSamplesForm $form;

    public $codeFilter;

    public $tubeCodeFilter;

    public $parasiteSpeciesFilter;

    public $sampleTypeFilter;

    public $photoFilter;

    public $stageFilter;

    public $sexFilter;

    public $stateFilter;

    public $startDate;

    public $endDate;

    public $processedStartDate;

    public $processedEndDate;

    public $scientistFilter;

    public $processedByFilter;

    public $animalSpeciesFilter;

    public $animalCodeFilter;

    public $samplingSiteFilter;

    public $animalDateCollectedStart;

    public $animalDateCollectedEnd;

    public $humanSampleCodeFilter;

    public $humanPatientCodeFilter;

    public $humanDateCollectedStart;

    public $humanDateCollectedEnd;

    public $humanSamplingSiteFilter;

    public $environmentSampleCodeFilter;

    public $environmentDateCollectedStart;

    public $environmentDateCollectedEnd;

    public $environmentSamplingSiteFilter;

    public $subProjectCodeFilter;

    // Tube request modal properties
    public $showTubeRequestModal = false;

    public $selectedTubeId;

    public $selectedTube;

    public $targetProjectId;

    public $requestMessage = '';

    public $userProjects = [];

    public $sourceProject;

    public ?int $photoPreviewSampleId = null;

    public ?string $photoPreviewUrl = null;

    public ?string $photoPreviewCode = null;

    public bool $photoPreviewCanDelete = false;

    /** @var array<int, array<string, mixed>> */
    public array $photoPreviewPhotos = [];

    public int $photoPreviewIndex = 0;

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('parasite_samples')) {
            session()->flash('error', 'You do not have permission to edit samples in this project.');

            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function updateField($sampleId, $field, $value)
    {
        $sample = ParasiteSamples::find($sampleId);
        if (! $sample || ! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples')) {
            session()->flash('error', 'You do not have permission to edit this record.');

            return;
        }

        // Validate the value against allowed options
        $isValid = match ($field) {
            'species' => $this->parasiteSpecies->contains('name_scientific', $value),
            'sex' => in_array($value, $this->sexOptions),
            'stage' => in_array($value, $this->stageOptions),
            'state' => in_array($value, $this->stateOptions),
            'sample_type' => $this->sampleTypes->contains('name', $value),
            'code' => true,
            'animal_id' => $this->animalSamples->contains('code', $value),
            'human_id' => $this->humanSamples->contains('code', $value),
            'environment_id' => $this->environmentSamples->contains('code', $value),
            default => true
        };

        if (! $isValid) {
            session()->flash('error', "Invalid value for {$field}. Please select from the available options.");

            return;
        }

        $this->form->updateField($sampleId, $field, $value);
    }

    public function delete(ParasiteSamples $sample)
    {
        if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples')) {
            session()->flash('error', 'You do not have permission to delete this record.');

            return;
        }

        $sample->delete();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Sample deleted successfully.',
        ]);
    }

    public function removeTube($tubeId)
    {
        if (! $this->userCanWriteModule('tubes')) {
            session()->flash('error', 'You do not have permission to remove tubes in this project.');

            return;
        }

        $tube = Tubes::find($tubeId);

        if ($tube) {
            // Delete the tube entirely
            $tube->delete();

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Success',
                'text' => 'Tube deleted successfully!',
            ]);
        } else {
            session()->flash('error', 'Tube not found!');
        }
    }

    public function openTubeRequestModal($tubeId)
    {
        $this->resetValidation();
        $this->reset(['targetProjectId', 'requestMessage']);

        $this->selectedTubeId = $tubeId;
        $this->selectedTube = Tubes::with(['tubes_content', 'projects'])->find($tubeId);

        if ($this->selectedTube) {
            $this->sourceProject = $this->selectedTube->projects;
        }

        // Load user projects (excluding the source project)
        $user = Auth::user();
        if ($user && $user->people) {
            $this->userProjects = $user->people->projects()
                ->where('projects.id', '!=', $this->sourceProject->id)
                ->get();
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
            session()->flash('error', 'User not found.');

            return;
        }

        if (! $this->selectedTubeId || ! $this->selectedTube) {
            session()->flash('error', 'Tube information is missing.');

            return;
        }

        // Check if there's already a pending request for this tube by this user
        $existingRequest = TubeRequests::where('tubes_id', $this->selectedTubeId)
            ->where('requester_id', $user->people->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            session()->flash('error', 'You already have a pending request for this tube.');

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
            session()->flash('error', 'Failed to submit request: '.$e->getMessage());
        }
    }

    public function toggleTableMode()
    {
        $this->selectedTable = ! $this->selectedTable;
    }

    public function updatedSelectedTable(): void
    {
        $this->selectedParasiteSamples = [];
        $this->selectAllFiltered = false;
        $this->reset('bulkPhoto');
        $this->resetPage('articles-page');
    }

    public function updatedBulkPhoto(): void
    {
        if ($this->bulkPhoto) {
            $this->uploadPhotoToSelected();
        }
    }

    public function updating($field)
    {
        if (
            is_string($field)
            && (
                str_starts_with($field, 'selectedParasiteSamples')
                || $field === 'selectAllFiltered'
                || str_starts_with($field, 'bulkPhoto')
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
            $this->selectedParasiteSamples = [];

            return;
        }

        $query = $this->buildBaseQueryForSelectedTable();
        $query = $this->applyFilters($query);

        if (! $this->isGuestMode()) {
            $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
            if ($currentPeopleId <= 0) {
                $this->selectedParasiteSamples = [];

                return;
            }

            $query->where('people_id', $currentPeopleId);
        }

        $ids = $query
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $this->selectedParasiteSamples = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    public function updatedPhotoFilter($value): void
    {
        if ($value === 'broken' && ! $this->canFilterBrokenPhotos()) {
            $this->photoFilter = '';
        }
    }

    protected function applyFilters($query)
    {
        if ($this->codeFilter) {
            $query->where('code', 'like', '%'.$this->codeFilter.'%');
        }
        if ($this->tubeCodeFilter) {
            $query->whereHas('tubes', function ($q) {
                $q->where(DB::raw("CONCAT(code, ' ', alias_code)"), 'like', '%'.$this->tubeCodeFilter.'%');
            });
        }
        if ($this->parasiteSpeciesFilter) {
            $query->whereHas('parasites', function ($query) {
                $query->whereHas('parasite_species', function ($q) {
                    $q->where('name_scientific', 'like', '%'.$this->parasiteSpeciesFilter.'%');
                });
            });
        }
        if ($this->sampleTypeFilter) {
            $query->whereHas('parasite_sample_types', function ($q) {
                $q->where('name', 'like', '%'.$this->sampleTypeFilter.'%');
            });
        }
        if ($this->photoFilter === 'has') {
            $query->where(function (Builder $photoQuery): void {
                $photoQuery->whereHas('observations.photo')
                    ->orWhere(function (Builder $legacyQuery): void {
                        $legacyQuery->whereNotNull('photo_path')
                            ->where('photo_path', '<>', '');
                    });
            });
        } elseif ($this->photoFilter === 'none') {
            $query->whereDoesntHave('observations.photo')
                ->where(function (Builder $legacyQuery): void {
                    $legacyQuery->whereNull('photo_path')
                        ->orWhere('photo_path', '=', '');
                });
        } elseif ($this->photoFilter === 'broken' && $this->canFilterBrokenPhotos()) {
            $missingPaths = ParasiteSamplePhoto::query()
                ->select('photo_path')
                ->whereNotNull('photo_path')
                ->where('photo_path', '<>', '')
                ->distinct()
                ->pluck('photo_path')
                ->merge(
                    ParasiteSamples::query()
                        ->select('photo_path')
                        ->whereNotNull('photo_path')
                        ->where('photo_path', '<>', '')
                        ->distinct()
                        ->pluck('photo_path')
                )
                ->unique()
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->filter(fn (string $path) => ! Storage::disk('local')->exists($path))
                ->values();

            if ($missingPaths->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function (Builder $brokenQuery) use ($missingPaths): void {
                    $brokenQuery->whereIn('photo_path', $missingPaths->all())
                        ->orWhereHas('photos', function (Builder $photosQuery) use ($missingPaths): void {
                            $photosQuery->whereIn('photo_path', $missingPaths->all());
                        });
                });
            }
        }
        if ($this->selectedTable === 'parasite_human_table' && $this->humanSampleCodeFilter) {
            $value = $this->humanSampleCodeFilter;
            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [HumanSamples::class], function ($originQuery) use ($value) {
                    $originQuery->where('code', 'like', '%'.$value.'%');
                });
            });
        }
        if ($this->selectedTable === 'parasite_human_table' && $this->humanPatientCodeFilter) {
            $value = $this->humanPatientCodeFilter;
            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [HumanSamples::class], function ($originQuery) use ($value) {
                    $originQuery->whereHas('humans', function ($humanQuery) use ($value) {
                        $humanQuery->where('code', 'like', '%'.$value.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'parasite_human_table' && ($this->humanDateCollectedStart || $this->humanDateCollectedEnd)) {
            $start = $this->humanDateCollectedStart;
            $end = $this->humanDateCollectedEnd;
            $query->whereHas('parasites', function ($parasiteQuery) use ($start, $end) {
                $parasiteQuery->whereHasMorph('parasites_origin', [HumanSamples::class], function ($originQuery) use ($start, $end) {
                    if ($start && $end) {
                        $originQuery->whereBetween('date_collected', [$start, $end]);

                        return;
                    }
                    if ($start) {
                        $originQuery->whereDate('date_collected', '>=', $start);
                    }
                    if ($end) {
                        $originQuery->whereDate('date_collected', '<=', $end);
                    }
                });
            });
        }
        if ($this->selectedTable === 'parasite_human_table' && $this->humanSamplingSiteFilter) {
            $value = $this->humanSamplingSiteFilter;
            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [HumanSamples::class], function ($originQuery) use ($value) {
                    $originQuery->whereHas('sampling_sites', function ($siteQuery) use ($value) {
                        $siteQuery->where('name', 'like', '%'.$value.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'parasite_environment_table' && $this->environmentSampleCodeFilter) {
            $value = $this->environmentSampleCodeFilter;
            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [EnvironmentSamples::class], function ($originQuery) use ($value) {
                    $originQuery->where('code', 'like', '%'.$value.'%');
                });
            });
        }
        if ($this->selectedTable === 'parasite_environment_table' && ($this->environmentDateCollectedStart || $this->environmentDateCollectedEnd)) {
            $start = $this->environmentDateCollectedStart;
            $end = $this->environmentDateCollectedEnd;
            $query->whereHas('parasites', function ($parasiteQuery) use ($start, $end) {
                $parasiteQuery->whereHasMorph('parasites_origin', [EnvironmentSamples::class], function ($originQuery) use ($start, $end) {
                    if ($start && $end) {
                        $originQuery->whereBetween('date_collected', [$start, $end]);

                        return;
                    }
                    if ($start) {
                        $originQuery->whereDate('date_collected', '>=', $start);
                    }
                    if ($end) {
                        $originQuery->whereDate('date_collected', '<=', $end);
                    }
                });
            });
        }
        if ($this->selectedTable === 'parasite_environment_table' && $this->environmentSamplingSiteFilter) {
            $value = $this->environmentSamplingSiteFilter;
            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [EnvironmentSamples::class], function ($originQuery) use ($value) {
                    $originQuery->whereHas('sampling_sites', function ($siteQuery) use ($value) {
                        $siteQuery->where('name', 'like', '%'.$value.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'parasite_animal_table' && $this->animalSpeciesFilter) {
            $value = $this->animalSpeciesFilter;

            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [AnimalSamples::class], function ($originQuery) use ($value) {
                    $originQuery->whereHas('animals.animal_species', function ($speciesQuery) use ($value) {
                        $speciesQuery->where(function ($q) use ($value) {
                            $q->where('name_common', 'like', '%'.$value.'%')
                                ->orWhere('name_scientific', 'like', '%'.$value.'%');
                        });
                    });
                });
            });
        }
        if ($this->selectedTable === 'parasite_animal_table' && $this->animalCodeFilter) {
            $value = $this->animalCodeFilter;

            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [AnimalSamples::class], function ($originQuery) use ($value) {
                    $originQuery->whereHas('animals', function ($animalQuery) use ($value) {
                        $animalQuery->where(function ($q) use ($value) {
                            $q->where('code', 'like', '%'.$value.'%')
                                ->orWhere('field_label', 'like', '%'.$value.'%');
                        });
                    });
                });
            });
        }
        if ($this->selectedTable === 'parasite_animal_table' && $this->samplingSiteFilter) {
            $value = $this->samplingSiteFilter;

            $query->whereHas('parasites', function ($parasiteQuery) use ($value) {
                $parasiteQuery->whereHasMorph('parasites_origin', [AnimalSamples::class], function ($originQuery) use ($value) {
                    $originQuery->whereHas('sampling_sites', function ($siteQuery) use ($value) {
                        $siteQuery->where('name', 'like', '%'.$value.'%');
                    });
                });
            });
        }
        if ($this->selectedTable === 'parasite_animal_table' && ($this->animalDateCollectedStart || $this->animalDateCollectedEnd)) {
            $start = $this->animalDateCollectedStart;
            $end = $this->animalDateCollectedEnd;

            $query->whereHas('parasites', function ($parasiteQuery) use ($start, $end) {
                $parasiteQuery->whereHasMorph('parasites_origin', [AnimalSamples::class], function ($originQuery) use ($start, $end) {
                    if ($start && $end) {
                        $originQuery->whereBetween('date_collected', [$start, $end]);

                        return;
                    }

                    if ($start) {
                        $originQuery->whereDate('date_collected', '>=', $start);
                    }

                    if ($end) {
                        $originQuery->whereDate('date_collected', '<=', $end);
                    }
                });
            });
        }
        if ($this->sexFilter) {
            $query->whereHas('parasites', function ($q) {
                $q->where('sex', 'like', '%'.$this->sexFilter.'%');
            });
        }
        if ($this->stateFilter) {
            $query->whereHas('parasites', function ($q) {
                $q->where('state', 'like', '%'.$this->stateFilter.'%');
            });
        }
        if ($this->stageFilter) {
            $query->whereHas('parasites', function ($q) {
                $q->where('stage', 'like', '%'.$this->stageFilter.'%');
            });
        }
        if ($this->startDate && $this->endDate) {
            $query->whereHas('parasites', function ($q) {
                $q->whereBetween('date_identified', [$this->startDate, $this->endDate]);
            });
        } elseif ($this->startDate) {
            $query->whereHas('parasites', function ($q) {
                $q->whereDate('date_identified', '>=', $this->startDate);
            });
        } elseif ($this->endDate) {
            $query->whereHas('parasites', function ($q) {
                $q->whereDate('date_identified', '<=', $this->endDate);
            });
        }
        if ($this->processedStartDate && $this->processedEndDate) {
            $query->whereBetween('date_processed', [$this->processedStartDate, $this->processedEndDate]);
        } elseif ($this->processedStartDate) {
            $query->whereDate('date_processed', '>=', $this->processedStartDate);
        } elseif ($this->processedEndDate) {
            $query->whereDate('date_processed', '<=', $this->processedEndDate);
        }
        if ($this->scientistFilter) {
            $query->whereHas('parasites', function ($query) {
                $query->whereHas('people', function ($q) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$this->scientistFilter.'%');
                });
            });
        }
        if ($this->processedByFilter) {
            $query->whereHas('people', function ($q) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$this->processedByFilter.'%');
            });
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHas('subProjectAssignment.subProject', function ($q) {
                $q->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
            });
        }

        return $query;
    }

    /**
     * @return array{morph: array<int, class-string>, csvFile: string, originCodeHeader: string, includeOriginType: bool, extraWith: array<int, string>}
     */
    protected function selectedTableConfig(): array
    {
        return match ($this->selectedTable) {
            'parasite_human_table' => [
                'morph' => [HumanSamples::class],
                'csvFile' => 'human_parasite_samples.csv',
                'originCodeHeader' => 'Human sample code',
                'includeOriginType' => false,
                'extraWith' => [
                    'parasites.parasites_origin.humans',
                    'parasites.parasites_origin.sampling_sites',
                    'parasites.parasites_origin.people',
                ],
            ],
            'parasite_animal_table' => [
                'morph' => [AnimalSamples::class],
                'csvFile' => 'animal_parasite_samples.csv',
                'originCodeHeader' => 'Animal sample code',
                'includeOriginType' => false,
                'extraWith' => [
                    'parasites.parasites_origin.animals',
                    'parasites.parasites_origin.animals.animal_species',
                    'parasites.parasites_origin.sampling_sites',
                    'parasites.parasites_origin.people',
                ],
            ],
            'parasite_environment_table' => [
                'morph' => [EnvironmentSamples::class],
                'csvFile' => 'environment_parasite_samples.csv',
                'originCodeHeader' => 'Environment sample code',
                'includeOriginType' => false,
                'extraWith' => [
                    'parasites.parasites_origin.environment_sample_types',
                    'parasites.parasites_origin.sampling_sites',
                    'parasites.parasites_origin.people',
                ],
            ],
            default => [
                'morph' => [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class],
                'csvFile' => 'parasite_samples.csv',
                'originCodeHeader' => 'Parasite origin code',
                'includeOriginType' => true,
                'extraWith' => [
                    // pools are only used in the "All" table
                    'pools.pools',
                    'pools.pools.tubes',
                ],
            ],
        };
    }

    protected function buildBaseQueryForSelectedTable(): Builder
    {
        $config = $this->selectedTableConfig();

        $withStrings = array_values(array_unique(array_merge([
            'parasites',
            'parasites.parasite_species',
            'parasites.parasites_origin',
            'observations.photo',
            'observations.people',
            'latestObservation.photo',
            'latestPhoto',
            'photos',
            'parasite_sample_types',
            'people',
            'parasites.people',
            'projects',
            'tubes.projects',
            'subProjectAssignment.subProject',
        ], $config['extraWith'])));

        $with = array_merge($withStrings, [
            'tubes' => function ($tubeQuery) {
                if ($this->isGuestMode()) {
                    $tubeQuery->where('is_private', false);
                }
            },
        ]);

        $query = ParasiteSamples::whereHas('parasites', function ($query) use ($config) {
            $query->whereHasMorph('parasites_origin', $config['morph']);
        })->with($with);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where(function ($q) {
                $q->where('projects_id', $this->projectId)
                    ->orWhereHas('tubes', function ($tubeQuery) {
                        $tubeQuery->where('projects_id', $this->projectId);
                    });
            });
        }

        return $query;
    }

    public function export(string $format = 'csv')
    {
        $config = $this->selectedTableConfig();
        $selectedTable = $this->selectedTable;

        $query = $this->buildBaseQueryForSelectedTable();
        $query = $this->applyFilters($query);
        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);
        $parasite_samples = $query->get();

        $headers = [
            'Sample code',
            'Sub-project',
            $config['originCodeHeader'],
        ];
        if ($config['includeOriginType']) {
            $headers[] = 'Parasite origin type';
        }
        if ($selectedTable === 'parasite_animal_table') {
            $headers[] = 'Animal species';
            $headers[] = 'Sampling site';
            $headers[] = 'Collection date';
        }
        if ($selectedTable === 'parasite_human_table') {
            $headers[] = 'Patient code';
            $headers[] = 'Collection date';
            $headers[] = 'Sampling site';
        }
        if ($selectedTable === 'parasite_environment_table') {
            $headers[] = 'Collection date';
            $headers[] = 'Sampling site';
        }
        $headers = array_merge($headers, [
            'Parasite species',
            'Sex',
            'Stage',
            'Repletion state',
            'Sample type',
            'Date identified',
            'Date processed',
            'Identified by',
            'Processed by',
        ]);

        $rows = $parasite_samples->map(function ($sample) use ($config, $selectedTable) {
            $row = [
                $sample->code,
                data_get($sample, 'subProjectAssignment.subProject.code') ?? 'N/A',
                $sample->parasites->parasites_origin->code,
            ];
            if ($config['includeOriginType']) {
                $row[] = match ($sample->parasites->parasites_origin_type) {
                    'App\Models\AnimalSamples' => 'Animal Sample',
                    'App\Models\HumanSamples' => 'Human Sample',
                    'App\Models\EnvironmentSamples' => 'Environmental Sample',
                    default => $sample->parasites->parasites_origin_type,
                };
            }
            if ($selectedTable === 'parasite_animal_table') {
                $species = $sample->parasites?->parasites_origin?->animals?->animal_species;
                $common = $species?->name_common ?? $species?->name ?? null;
                $scientific = $species?->name_scientific ?? null;

                $value = $common ?: 'N/A';
                if ($scientific) {
                    $value .= ' ('.$scientific.')';
                }

                $row[] = $value;

                $row[] = $sample->parasites?->parasites_origin?->sampling_sites?->name ?? 'N/A';

                $dateCollected = $sample->parasites?->parasites_origin?->date_collected;
                $row[] = $dateCollected ? $dateCollected->format('Y-m-d') : 'N/A';
            }
            if ($selectedTable === 'parasite_human_table') {
                $row[] = $sample->parasites?->parasites_origin?->humans?->code ?? 'N/A';
                $dateCollected = $sample->parasites?->parasites_origin?->date_collected;
                $row[] = $dateCollected ? $dateCollected->format('Y-m-d') : 'N/A';
                $row[] = $sample->parasites?->parasites_origin?->sampling_sites?->name ?? 'N/A';
            }
            if ($selectedTable === 'parasite_environment_table') {
                $dateCollected = $sample->parasites?->parasites_origin?->date_collected;
                $row[] = $dateCollected ? $dateCollected->format('Y-m-d') : 'N/A';
                $row[] = $sample->parasites?->parasites_origin?->sampling_sites?->name ?? 'N/A';
            }
            $row = array_merge($row, [
                $sample->parasites->parasite_species->name_scientific,
                $sample->parasites->sex,
                $sample->parasites->stage,
                $sample->parasites->state,
                $sample->parasite_sample_types->name,
                $sample->parasites->date_identified?->format('Y-m-d') ?? 'N/A',
                $sample->date_processed?->format('Y-m-d') ?? 'N/A',
                trim(($sample->parasites->people->title ?? '').' '.($sample->parasites->people->first_name ?? '').' '.($sample->parasites->people->last_name ?? '')) ?: 'N/A',
                trim(($sample->people->title ?? '').' '.($sample->people->first_name ?? '').' '.($sample->people->last_name ?? '')) ?: 'N/A',
            ]);

            return $row;
        });

        return $this->exportTable(Str::replaceLast('.csv', '', $config['csvFile']), $headers, $rows, $format);
    }

    public function uploadPhoto($sampleId)
    {
        try {
            // Set the current photo ID
            $this->currentPhotoId = $sampleId;

            if (! $this->photo) {
                $this->uploadErrors[$sampleId] = 'No photo was selected';

                return;
            }

            // Check file size before validation
            $maxSize = 50 * 1024 * 1024; // 50MB in bytes
            if ($this->photo->getSize() > $maxSize) {
                $this->uploadErrors[$sampleId] = 'File size exceeds 50MB limit';

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
                $this->uploadErrors[$sampleId] = $e->validator->errors()->first('photo');

                return;
            }

            $sample = ParasiteSamples::query()->find($sampleId);

            if (! $sample) {
                $this->uploadErrors[$sampleId] = 'Parasite sample not found';

                return;
            }

            if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples')) {
                $this->uploadErrors[$sampleId] = 'You can only upload photos for records you registered.';

                return;
            }

            $this->uploadingPhotoId = $sampleId;

            $photoPath = $this->photo->store('parasite-sample-photos', 'local');
            ParasiteSampleObservationRecorder::createWithPhoto(
                sample: $sample,
                photoPath: $photoPath,
                observedAt: now()->toDateString(),
                notes: null,
                peopleId: ProjectPermission::currentRegistrarPeopleId(Auth::user()),
            );
            $sample->syncCoverPhotoPath();

            $this->reset(['photo', 'uploadingPhotoId', 'currentPhotoId']);
            unset($this->uploadErrors[$sampleId]);

            $this->form->refreshData();

            $this->dispatch('photo-uploaded', sampleId: $sampleId);

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Success',
                'text' => 'Photo uploaded successfully.',
            ]);

        } catch (\Exception $e) {
            $this->uploadErrors[$sampleId] = 'Failed to upload photo: '.$e->getMessage();
            $this->reset(['photo', 'uploadingPhotoId', 'currentPhotoId']);
        }
    }

    public function openPhotoPreview(int $sampleId): void
    {
        $sample = ParasiteSamples::query()
            ->with(['observations.photo', 'observations.people', 'photos'])
            ->find($sampleId);

        if (! $sample) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Not found',
                'text' => 'Parasite sample not found.',
            ]);

            return;
        }

        ParasiteSampleObservationRecorder::ensureLegacyPhotoRecord($sample);
        $sample->load(['observations.photo', 'observations.people']);

        $observations = $sample->observations
            ->filter(fn (ParasiteSampleObservation $observation) => $observation->photo
                && $observation->photo->photo_path
                && Storage::disk('local')->exists($observation->photo->photo_path))
            ->values();

        if ($observations->isEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Missing file',
                'text' => 'No photos are available for this parasite sample.',
            ]);

            return;
        }

        $this->photoPreviewPhotos = $observations->map(function (ParasiteSampleObservation $observation) {
            $person = $observation->people;
            $observer = $person
                ? trim(($person->title ?? '').' '.($person->first_name ?? '').' '.($person->last_name ?? ''))
                : null;

            return [
                'id' => (int) $observation->id,
                'url' => Storage::url($observation->photo->photo_path),
                'path' => $observation->photo->photo_path,
                'observed_at' => $observation->observed_at?->format('Y-m-d'),
                'notes' => $observation->notes,
                'observer' => $observer,
            ];
        })->all();

        $this->photoPreviewIndex = 0;
        $this->photoPreviewSampleId = (int) $sample->id;
        $this->photoPreviewUrl = $this->photoPreviewPhotos[0]['url'] ?? null;
        $this->photoPreviewCode = (string) ($sample->code ?? '');
        $this->photoPreviewCanDelete = ! $this->isGuestMode()
            && $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples');
    }

    public function showPhotoPreviewAt(int $index): void
    {
        if (! isset($this->photoPreviewPhotos[$index])) {
            return;
        }

        $this->photoPreviewIndex = $index;
        $this->photoPreviewUrl = $this->photoPreviewPhotos[$index]['url'] ?? null;
    }

    public function nextPhotoPreview(): void
    {
        if ($this->photoPreviewPhotos === []) {
            return;
        }

        $next = ($this->photoPreviewIndex + 1) % count($this->photoPreviewPhotos);
        $this->showPhotoPreviewAt($next);
    }

    public function previousPhotoPreview(): void
    {
        if ($this->photoPreviewPhotos === []) {
            return;
        }

        $count = count($this->photoPreviewPhotos);
        $prev = ($this->photoPreviewIndex - 1 + $count) % $count;
        $this->showPhotoPreviewAt($prev);
    }

    public function closePhotoPreview(): void
    {
        $this->photoPreviewSampleId = null;
        $this->photoPreviewUrl = null;
        $this->photoPreviewCode = null;
        $this->photoPreviewCanDelete = false;
        $this->photoPreviewPhotos = [];
        $this->photoPreviewIndex = 0;
    }

    public function deletePreviewPhoto(): void
    {
        if (! $this->photoPreviewSampleId) {
            return;
        }

        $sample = ParasiteSamples::query()->find($this->photoPreviewSampleId);

        if (! $sample) {
            $this->closePhotoPreview();

            return;
        }

        if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only delete photos from records you registered.',
            ]);

            return;
        }

        $current = $this->photoPreviewPhotos[$this->photoPreviewIndex] ?? null;
        $observationId = (int) ($current['id'] ?? 0);

        if ($observationId > 0) {
            $observation = ParasiteSampleObservation::query()->with('photo')->find($observationId);

            if ($observation) {
                ParasiteSampleObservationRecorder::deleteObservation($observation);
                $sample->syncCoverPhotoPath();
            }
        } else {
            $path = trim((string) ($sample->photo_path ?? ''));
            $sample->update(['photo_path' => null]);
            ParasitePhotoStorage::deleteFileIfUnreferenced($path);
        }

        $this->closePhotoPreview();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Photo deleted successfully.',
        ]);
    }

    public function clearBrokenPhotoPath(int $sampleId): void
    {
        $sample = ParasiteSamples::query()->find($sampleId);

        if (! $sample) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Not found',
                'text' => 'Parasite sample not found.',
            ]);

            return;
        }

        if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only clear paths on records you registered.',
            ]);

            return;
        }

        $sample->update(['photo_path' => null]);
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Broken photo path cleared for this sample.',
        ]);
    }

    public function uploadPhotoToSelected(): void
    {
        if ($this->isGuestMode() || ! $this->userCanWriteModule('parasite_samples')) {
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

        $selectedIds = collect($this->selectedParasiteSamples)
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
                'text' => 'Please check at least one parasite sample before uploading.',
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
                'title' => 'Validation error',
                'text' => $e->validator->errors()->first('bulkPhoto'),
            ]);

            return;
        }

        $uploaded = 0;
        $skipped = 0;
        $sharedPath = null;

        ParasiteSamples::query()
            ->whereIn('id', $selectedIds->all())
            ->get()
            ->each(function (ParasiteSamples $sample) use (&$uploaded, &$skipped, &$sharedPath): void {
                if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples')) {
                    $skipped++;

                    return;
                }

                if ($sharedPath === null) {
                    $sharedPath = $this->storeBulkPhotoOnce($this->bulkPhoto, 'parasite-sample-photos', 'parasite_sample_bulk');
                    if (! $sharedPath) {
                        $skipped++;

                        return;
                    }
                }

                try {
                    ParasiteSampleObservationRecorder::createWithPhoto(
                        sample: $sample,
                        photoPath: $sharedPath,
                        observedAt: now()->toDateString(),
                        notes: null,
                        peopleId: ProjectPermission::currentRegistrarPeopleId(Auth::user()),
                    );
                    $sample->syncCoverPhotoPath();
                } catch (\Exception) {
                    $skipped++;

                    return;
                }

                $uploaded++;
            });

        $this->reset('bulkPhoto');
        $this->selectedParasiteSamples = [];
        $message = $uploaded > 0
            ? "Uploaded photo to {$uploaded} sample(s).".($skipped > 0 ? " Skipped {$skipped} item(s)." : '')
            : 'No uploads completed.';
        $this->dispatch('swal', [
            'icon' => $uploaded > 0 ? 'success' : 'warning',
            'title' => $uploaded > 0 ? 'Done' : 'No uploads completed',
            'text' => $message,
        ]);
    }

    public function deleteSelected(): void
    {
        if ($this->isGuestMode() || ! $this->userCanWriteModule('parasite_samples')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You do not have permission to delete samples in this list.',
            ]);

            return;
        }

        $selectedIds = collect($this->selectedParasiteSamples)
            ->filter(fn ($checked) => (bool) $checked)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($selectedIds->isEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No items selected',
                'text' => 'Please check at least one parasite sample before deleting.',
            ]);

            return;
        }

        $deleted = 0;
        $skipped = 0;

        ParasiteSamples::query()
            ->whereIn('id', $selectedIds->all())
            ->get()
            ->each(function (ParasiteSamples $sample) use (&$deleted, &$skipped): void {
                if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'parasite_samples')) {
                    $skipped++;

                    return;
                }

                $sample->delete();
                $deleted++;
            });

        $this->selectedParasiteSamples = [];
        $message = $deleted > 0
            ? "Deleted {$deleted} sample(s).".($skipped > 0 ? " Skipped {$skipped} item(s)." : '')
            : 'No deletions completed.';
        $this->dispatch('swal', [
            'icon' => $deleted > 0 ? 'success' : 'warning',
            'title' => $deleted > 0 ? 'Done' : 'No deletions completed',
            'text' => $message,
        ]);
    }

    private function storePhotoForParasite(Parasites $parasite, $file): ?string
    {
        if (! $file) {
            return null;
        }

        $photoPath = $file->store('parasite-photos', 'local');
        ParasiteObservationRecorder::createWithPhoto(
            parasite: $parasite,
            photoPath: $photoPath,
            observedAt: now()->toDateString(),
            notes: null,
            peopleId: ProjectPermission::currentRegistrarPeopleId(Auth::user()),
        );
        $parasite->syncCoverPhotoPath();

        return $photoPath;
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

    public function render()
    {
        $service = app(ParasiteSamplesService::class);

        // Get options for select inputs
        $this->sampleTypes = ParasiteSampleTypes::all();
        $this->parasiteSpecies = ParasiteSpecies::all();
        $this->animalSamples = AnimalSamples::all();
        $this->humanSamples = HumanSamples::all();
        $this->environmentSamples = EnvironmentSamples::all();

        $query = $this->buildBaseQueryForSelectedTable();
        $query = $this->applyFilters($query);
        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);
        $parasite_samples = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('parasite_samples');
        $viewData = array_merge($service->assign(), [
            'parasite_samples' => $parasite_samples,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'sampleTypes' => $this->sampleTypes,
            'sexOptions' => $this->sexOptions,
            'stageOptions' => $this->stageOptions,
            'stateOptions' => $this->stateOptions,
            'parasiteSpecies' => $this->parasiteSpecies,
            'animalSamples' => $this->animalSamples,
            'humanSamples' => $this->humanSamples,
            'environmentSamples' => $this->environmentSamples,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
        ]);

        return view('livewire.parasite-samples-index', $viewData);
    }
}
