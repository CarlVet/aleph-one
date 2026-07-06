<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Pools;
use App\Models\TubeRequests;
use App\Models\Tubes;
use App\Services\TubesService;
use App\Support\ProjectPermission;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TubesList extends PlainComponent
{
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    protected $projectId;

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
            'tube_code' => 'code',
            'alias_code' => 'alias_code',
            'content_type' => 'tubes_content_type',
            'tube_type' => 'tube_type',
            'purpose' => 'purpose',
            'preservant' => 'preservant',
            'amount' => 'amount',
            'date_processed' => 'date_processed',
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'project' => fn ($q, $dir) => $this->orderByRelation($q, ['projects'], 'code', $dir),
        ];
    }

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

    public function updateField($tubeId, $field, $value)
    {
        try {
            $tube = Tubes::findOrFail($tubeId);
            if (! $this->userCanMutateOwnedRecord((int) $tube->people_id, 'tubes')) {
                session()->flash('error', 'You can only edit records you registered.');

                return;
            }

            $tube->update([$field => $value]);
            session()->flash('message', 'Tube updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update tube: '.$e->getMessage());
        }
    }

    public function delete(Tubes $tube)
    {
        try {
            if (! $this->userCanMutateOwnedRecord((int) $tube->people_id, 'tubes')) {
                session()->flash('error', 'You can only delete records you registered.');

                return;
            }

            $tube->delete();
            session()->flash('message', 'Tube deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete tube: '.$e->getMessage());
        }
    }

    public array $selectedTubes = [];

    public bool $selectAllFiltered = false;

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedTubes)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one tube.');

            return;
        }

        $tubes = Tubes::query()->whereIn('id', $selectedIds->all())->get();
        $deleted = 0;

        foreach ($tubes as $tube) {
            if (! $this->userCanMutateOwnedRecord((int) $tube->people_id, 'tubes')) {
                continue;
            }

            $tube->delete();
            $deleted++;
        }

        $this->selectedTubes = [];
        session()->flash(
            $deleted > 0 ? 'message' : 'error',
            $deleted > 0 ? "{$deleted} selected tube(s) deleted successfully." : 'No selected tubes could be deleted.'
        );
    }

    public $isEditing = false;

    public string $selectedTable = 'tubes_table';

    // Filters
    public $tubeCodeFilter;

    public $aliasCodeFilter;

    public $tubeTypeFilter;

    public $purposeFilter;

    public $preservantFilter;

    public $startDate;

    public $endDate;

    public $contentTypeFilter;

    public $subProjectCodeFilter;
    // Removed stateFilter

    /**
     * Table-specific filters (used by derived tables).
     *
     * @var array<string, mixed>
     */
    public array $originFilters = [];

    // Tube request modal properties
    public $showTubeRequestModal = false;

    public $selectedTubeId;

    public $selectedTube;

    public $targetProjectId;

    public $requestMessage = '';

    public $userProjects = [];

    public $sourceProject;

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('tubes')) {
            session()->flash('error', 'You do not have permission to edit tube records.');

            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

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

            session()->flash('success', 'Tube request submitted successfully! The principal investigator will be notified.');
            $this->closeTubeRequestModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit request: '.$e->getMessage());
        }
    }

    public function updating($field)
    {
        if (
            is_string($field)
            && (
                str_starts_with($field, 'selectedTubes')
                || $field === 'selectAllFiltered'
            )
        ) {
            return;
        }

        $this->resetPage('articles-page');
    }

    public function updatedSelectedTable(): void
    {
        $this->resetPage('articles-page');
        $this->originFilters = [];
        $this->selectedTubes = [];
        $this->selectAllFiltered = false;
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedTubes = [];

            return;
        }

        $query = Tubes::query()->orderBy('created_at', 'desc');

        if ($this->isGuestMode()) {
            $query->where('is_private', false);
        } else {
            $query->where('projects_id', $this->projectId);

            $user = Auth::user();
            if (! $user) {
                $this->selectedTubes = [];

                return;
            }

            $membership = ProjectPermission::membership($user, (int) $this->projectId);
            $isAdmin = strtolower(trim((string) ($membership['permission'] ?? ''))) === 'admin';

            if (! $isAdmin) {
                $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                if ($currentPeopleId <= 0) {
                    $this->selectedTubes = [];

                    return;
                }

                $query->where('people_id', $currentPeopleId);
            }
        }

        $query = $this->applySelectedTableScope($query);
        $query = $this->applyFilters($query);

        $ids = $query
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $this->selectedTubes = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    private function isPoolTable(): bool
    {
        return in_array($this->selectedTable, [
            'tube_pool_table',
            'tube_pool_human_table',
            'tube_pool_animal_table',
            'tube_pool_environment_table',
            'tube_pool_parasite_table',
            'tube_pool_nucleic_table',
            'tube_pool_culture_table',
        ], true);
    }

    /**
     * @return class-string|null
     */
    private function poolDerivedSamplesType(): ?string
    {
        return match ($this->selectedTable) {
            'tube_pool_human_table' => HumanSamples::class,
            'tube_pool_animal_table' => AnimalSamples::class,
            'tube_pool_environment_table' => EnvironmentSamples::class,
            'tube_pool_parasite_table' => ParasiteSamples::class,
            'tube_pool_nucleic_table' => NucleicAcids::class,
            'tube_pool_culture_table' => Cultures::class,
            default => null,
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function tableConfigs(): array
    {
        return [
            'tubes_table' => [
                'tableId' => 'tubes_table',
                'subtitle' => null,
                'with' => ['tubes_content', 'projects'],
                'scope' => fn (Builder $q) => $q,
                'fileName' => 'tubes.csv',
                'extraColumns' => [],
            ],

            'tube_human_table' => [
                'tableId' => 'tube_human_table',
                'subtitle' => 'containing human samples',
                'with' => ['tubes_content', 'tubes_content.humans', 'tubes_content.sample_types', 'tubes_content.sampling_sites', 'tubes_content.people', 'projects'],
                'scope' => fn (Builder $q) => $q->where('tubes_content_type', HumanSamples::class),
                'fileName' => 'tubes.human.csv',
                'extraColumns' => [
                    ['label' => 'Sample code', 'html' => true, 'value' => fn (Tubes $t) => $this->linkToContent($t), 'filterModel' => 'originFilters.sampleCode'],
                    [
                        'label' => 'Patient',
                        'html' => true,
                        'value' => function (Tubes $t): string {
                            $code = (string) data_get($t, 'tubes_content.humans.code');

                            return $this->linkToCode($code !== '' ? '/humans/'.rawurlencode($code) : null, $code);
                        },
                        'filterModel' => 'originFilters.patientCode',
                    ],
                    ['label' => 'Sample type', 'valuePath' => 'tubes_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Collected by',
                        'personPath' => 'tubes_content.people',
                        'filterModel' => 'originFilters.collectedBy',
                    ],
                ],
            ],

            'tube_animal_table' => [
                'tableId' => 'tube_animal_table',
                'subtitle' => 'containing animal samples',
                'with' => ['tubes_content', 'tubes_content.animals', 'tubes_content.animals.animal_species', 'tubes_content.sampling_sites', 'tubes_content.people', 'projects'],
                'scope' => fn (Builder $q) => $q->where('tubes_content_type', AnimalSamples::class),
                'fileName' => 'tubes.animal.csv',
                'extraColumns' => [
                    ['label' => 'Sample code', 'html' => true, 'value' => fn (Tubes $t) => $this->linkToContent($t), 'filterModel' => 'originFilters.sampleCode'],
                    [
                        'label' => 'Animal',
                        'html' => true,
                        'value' => function (Tubes $t): string {
                            $code = (string) data_get($t, 'tubes_content.animals.code');

                            return $this->linkToCode($code !== '' ? '/animals/'.rawurlencode($code) : null, $code);
                        },
                        'filterModel' => 'originFilters.animalCode',
                    ],
                    [
                        'label' => 'Species',
                        'html' => true,
                        'value' => function (Tubes $t): string {
                            $tubeContent = $t->getRelations()['tubes_content'] ?? null;
                            $animal = $tubeContent?->getRelations()['animals'] ?? null;
                            $species = $animal?->getRelations()['animal_species'] ?? null;

                            return $this->animalSpeciesHtml($species);
                        },
                        'filterModel' => 'originFilters.species',
                    ],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Collected by',
                        'personPath' => 'tubes_content.people',
                        'filterModel' => 'originFilters.collectedBy',
                    ],
                ],
            ],

            'tube_environment_table' => [
                'tableId' => 'tube_environment_table',
                'subtitle' => 'containing environment samples',
                'with' => ['tubes_content', 'tubes_content.environment_sample_types', 'tubes_content.sampling_sites', 'tubes_content.people', 'projects'],
                'scope' => fn (Builder $q) => $q->where('tubes_content_type', EnvironmentSamples::class),
                'fileName' => 'tubes.environment.csv',
                'extraColumns' => [
                    ['label' => 'Sample code', 'html' => true, 'value' => fn (Tubes $t) => $this->linkToContent($t), 'filterModel' => 'originFilters.sampleCode'],
                    ['label' => 'Sample type', 'valuePath' => 'tubes_content.environment_sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                    ['label' => 'Sampling site', 'valuePath' => 'tubes_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                    [
                        'label' => 'Date collected',
                        'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.date_collected')),
                        'filterType' => 'date_range',
                        'filterModelStart' => 'originFilters.collectedStart',
                        'filterModelEnd' => 'originFilters.collectedEnd',
                    ],
                    [
                        'label' => 'Collected by',
                        'personPath' => 'tubes_content.people',
                        'filterModel' => 'originFilters.collectedBy',
                    ],
                ],
            ],

            // Parasites + derived by origin type
            'tube_parasite_table' => [
                'tableId' => 'tube_parasite_table',
                'subtitle' => 'containing parasite samples',
                'with' => [
                    'tubes_content',
                    'tubes_content.parasites',
                    'tubes_content.parasites.parasite_species',
                    'tubes_content.parasite_sample_types',
                    'tubes_content.parasites.parasites_origin',
                    'tubes_content.parasites.people',
                    'projects',
                ],
                'scope' => fn (Builder $q) => $q->where('tubes_content_type', ParasiteSamples::class),
                'fileName' => 'tubes.parasite.csv',
                'extraColumns' => $this->parasiteExtraColumns(),
            ],
            'tube_parasite_human_table' => [
                'tableId' => 'tube_parasite_human_table',
                'subtitle' => 'containing parasite samples collected from human samples',
                'with' => [
                    'tubes_content',
                    'tubes_content.parasites',
                    'tubes_content.parasites.parasite_species',
                    'tubes_content.parasite_sample_types',
                    'tubes_content.parasites.parasites_origin',
                    'tubes_content.parasites.people',
                    'projects',
                ],
                'scope' => fn (Builder $q) => $q
                    ->where('tubes_content_type', ParasiteSamples::class)
                    ->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('parasites_origin_type', HumanSamples::class))),
                'fileName' => 'tubes.parasite.human.csv',
                'extraColumns' => array_merge($this->parasiteExtraColumns(), $this->parasiteHumanOriginExtraColumns()),
            ],
            'tube_parasite_animal_table' => [
                'tableId' => 'tube_parasite_animal_table',
                'subtitle' => 'containing parasite samples collected from animal samples',
                'with' => [
                    'tubes_content',
                    'tubes_content.parasites',
                    'tubes_content.parasites.parasite_species',
                    'tubes_content.parasite_sample_types',
                    'tubes_content.parasites.parasites_origin',
                    'tubes_content.parasites.people',
                    'projects',
                ],
                'scope' => fn (Builder $q) => $q
                    ->where('tubes_content_type', ParasiteSamples::class)
                    ->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('parasites_origin_type', AnimalSamples::class))),
                'fileName' => 'tubes.parasite.animal.csv',
                'extraColumns' => array_merge($this->parasiteExtraColumns(), $this->parasiteAnimalOriginExtraColumns()),
            ],
            'tube_parasite_environment_table' => [
                'tableId' => 'tube_parasite_environment_table',
                'subtitle' => 'containing parasite samples collected from environment samples',
                'with' => [
                    'tubes_content',
                    'tubes_content.parasites',
                    'tubes_content.parasites.parasite_species',
                    'tubes_content.parasite_sample_types',
                    'tubes_content.parasites.parasites_origin',
                    'tubes_content.parasites.people',
                    'projects',
                ],
                'scope' => fn (Builder $q) => $q
                    ->where('tubes_content_type', ParasiteSamples::class)
                    ->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $sq) => $sq->whereHas('parasites', fn (Builder $pq) => $pq->where('parasites_origin_type', EnvironmentSamples::class))),
                'fileName' => 'tubes.parasite.environment.csv',
                'extraColumns' => array_merge($this->parasiteExtraColumns(), $this->parasiteEnvironmentOriginExtraColumns()),
            ],

            // Nucleic + derived by nucleic_content_type
            'tube_nucleic_table' => [
                'tableId' => 'tube_nucleic_table',
                'subtitle' => 'containing nucleic acids',
                'with' => [
                    'tubes_content',
                    'tubes_content.nucleic_content',
                    'tubes_content.protocols',
                    'tubes_content.people',
                    'tubes_content.laboratories',
                    'projects',
                ],
                'scope' => fn (Builder $q) => $q->where('tubes_content_type', NucleicAcids::class),
                'fileName' => 'tubes.nucleic.csv',
                'extraColumns' => $this->nucleicExtraColumns(),
            ],
            'tube_nucleic_human_table' => $this->nucleicDerivedConfig('tube_nucleic_human_table', HumanSamples::class, 'human samples', 'tubes.nucleic.human.csv'),
            'tube_nucleic_animal_table' => $this->nucleicDerivedConfig('tube_nucleic_animal_table', AnimalSamples::class, 'animal samples', 'tubes.nucleic.animal.csv'),
            'tube_nucleic_environment_table' => $this->nucleicDerivedConfig('tube_nucleic_environment_table', EnvironmentSamples::class, 'environment samples', 'tubes.nucleic.environment.csv'),
            'tube_nucleic_parasite_table' => $this->nucleicDerivedConfig('tube_nucleic_parasite_table', ParasiteSamples::class, 'parasite samples', 'tubes.nucleic.parasite.csv'),
            'tube_nucleic_culture_table' => $this->nucleicDerivedConfig('tube_nucleic_culture_table', Cultures::class, 'cultures', 'tubes.nucleic.culture.csv'),
            'tube_nucleic_pool_table' => $this->nucleicDerivedConfig('tube_nucleic_pool_table', Pools::class, 'pools', 'tubes.nucleic.pool.csv'),

            // Cultures + derived by cultures_content_type
            'tube_culture_table' => [
                'tableId' => 'tube_culture_table',
                'subtitle' => 'containing cultures',
                'with' => [
                    'tubes_content',
                    'tubes_content.cultures_content',
                    'tubes_content.people',
                    'tubes_content.laboratories',
                    'projects',
                ],
                'scope' => fn (Builder $q) => $q->where('tubes_content_type', Cultures::class),
                'fileName' => 'tubes.culture.csv',
                'extraColumns' => $this->cultureExtraColumns(),
            ],
            'tube_culture_human_table' => $this->cultureDerivedConfig('tube_culture_human_table', HumanSamples::class, 'human samples', 'tubes.culture.human.csv'),
            'tube_culture_animal_table' => $this->cultureDerivedConfig('tube_culture_animal_table', AnimalSamples::class, 'animal samples', 'tubes.culture.animal.csv'),
            'tube_culture_environment_table' => $this->cultureDerivedConfig('tube_culture_environment_table', EnvironmentSamples::class, 'environment samples', 'tubes.culture.environment.csv'),
            'tube_culture_parasite_table' => $this->cultureDerivedConfig('tube_culture_parasite_table', ParasiteSamples::class, 'parasite samples', 'tubes.culture.parasite.csv'),
            'tube_culture_pool_table' => $this->cultureDerivedConfig('tube_culture_pool_table', Pools::class, 'pools', 'tubes.culture.pool.csv'),

            // Pools + derived by pool_contents.samples_type (includes subtable)
            'tube_pool_table' => $this->poolTableConfig('tube_pool_table', null, 'pooled samples', 'tubes.pool.csv'),
            'tube_pool_human_table' => $this->poolTableConfig('tube_pool_human_table', HumanSamples::class, 'pooled from human samples', 'tubes.pool.human.csv'),
            'tube_pool_animal_table' => $this->poolTableConfig('tube_pool_animal_table', AnimalSamples::class, 'pooled from animal samples', 'tubes.pool.animal.csv'),
            'tube_pool_environment_table' => $this->poolTableConfig('tube_pool_environment_table', EnvironmentSamples::class, 'pooled from environment samples', 'tubes.pool.environment.csv'),
            'tube_pool_parasite_table' => $this->poolTableConfig('tube_pool_parasite_table', ParasiteSamples::class, 'pooled from parasite samples', 'tubes.pool.parasite.csv'),
            'tube_pool_nucleic_table' => $this->poolTableConfig('tube_pool_nucleic_table', NucleicAcids::class, 'pooled from nucleic acids', 'tubes.pool.nucleic.csv'),
            'tube_pool_culture_table' => $this->poolTableConfig('tube_pool_culture_table', Cultures::class, 'pooled from cultures', 'tubes.pool.culture.csv'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function selectedTableConfig(): array
    {
        $configs = $this->tableConfigs();

        return $configs[$this->selectedTable] ?? $configs['tubes_table'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parasiteExtraColumns(): array
    {
        return [
            ['label' => 'Sample code', 'html' => true, 'value' => fn (Tubes $t) => $this->linkToContent($t), 'filterModel' => 'originFilters.sampleCode'],
            [
                'label' => 'Tick species',
                'html' => true,
                'value' => function (Tubes $t): string {
                    $tubeContent = $t->getRelations()['tubes_content'] ?? null;
                    $parasites = $tubeContent?->getRelations()['parasites'] ?? null;
                    $species = $parasites?->getRelations()['parasite_species'] ?? null;
                    $name = (string) (data_get($species, 'name_scientific') ?? '');

                    return $name
                        ? '<span class="text-gray-900 font-medium italic">'.e($name).'</span>'
                        : '<span class="text-gray-500">N/A</span>';
                },
                'filterModel' => 'originFilters.tickSpecies',
            ],
            ['label' => 'Tick sex', 'valuePath' => 'tubes_content.parasites.sex', 'filterModel' => 'originFilters.tickSex'],
            ['label' => 'Tick stage', 'valuePath' => 'tubes_content.parasites.stage', 'filterModel' => 'originFilters.tickStage'],
            [
                'label' => 'Sampling site',
                'value' => fn (Tubes $t) => (string) (data_get($t, 'tubes_content.parasites.parasites_origin.sampling_sites.name') ?? 'N/A'),
                'filterModel' => 'originFilters.samplingSite',
            ],
            [
                'label' => 'Date collected',
                'value' => fn (Tubes $t) => $this->dateYmd(
                    data_get($t, 'tubes_content.parasites.parasites_origin.date_collected')
                ),
                'filterType' => 'date_range',
                'filterModelStart' => 'originFilters.collectedStart',
                'filterModelEnd' => 'originFilters.collectedEnd',
            ],
            [
                'label' => 'Date identified',
                'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.parasites.date_identified')),
                'filterType' => 'date_range',
                'filterModelStart' => 'originFilters.identifiedStart',
                'filterModelEnd' => 'originFilters.identifiedEnd',
            ],
            [
                'label' => 'Identified by',
                'personPath' => 'tubes_content.parasites.people',
                'filterModel' => 'originFilters.identifiedBy',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parasiteHumanOriginExtraColumns(): array
    {
        return [
            [
                'label' => 'Human code',
                'html' => true,
                'value' => function (Tubes $t): string {
                    $code = (string) data_get($t, 'tubes_content.parasites.parasites_origin.humans.code');

                    return $this->linkToCode($code !== '' ? '/humans/'.rawurlencode($code) : null, $code);
                },
                'filterModel' => 'originFilters.humanCode',
            ],
            [
                'label' => 'Human name',
                'value' => fn (Tubes $t) => trim((string) data_get($t, 'tubes_content.parasites.parasites_origin.humans.first_name').' '.(string) data_get($t, 'tubes_content.parasites.parasites_origin.humans.last_name')) ?: 'N/A',
                'filterModel' => 'originFilters.humanName',
            ],
            ['label' => 'Sample type', 'valuePath' => 'tubes_content.parasites.parasites_origin.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parasiteAnimalOriginExtraColumns(): array
    {
        return [
            [
                'label' => 'Animal code',
                'html' => true,
                'value' => function (Tubes $t): string {
                    $code = (string) data_get($t, 'tubes_content.parasites.parasites_origin.animals.code');

                    return $this->linkToCode($code !== '' ? '/animals/'.rawurlencode($code) : null, $code);
                },
                'filterModel' => 'originFilters.animalCode',
            ],
            [
                'label' => 'Animal species',
                'html' => true,
                'value' => fn (Tubes $t) => $this->animalSpeciesHtml(data_get($t, 'tubes_content.parasites.parasites_origin.animals.animal_species')),
                'filterModel' => 'originFilters.animalSpecies',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parasiteEnvironmentOriginExtraColumns(): array
    {
        return [
            ['label' => 'Environment type', 'valuePath' => 'tubes_content.parasites.parasites_origin.environment_sample_types.name', 'filterModel' => 'originFilters.environmentType'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function nucleicExtraColumns(): array
    {
        return [
            ['label' => 'Nucleic code', 'html' => true, 'value' => fn (Tubes $t) => $this->linkToContent($t), 'filterModel' => 'originFilters.nucleicCode'],
            ['label' => 'Nucleic type', 'valuePath' => 'tubes_content.type', 'filterModel' => 'originFilters.nucleicType'],
            [
                'label' => 'Extracted from',
                'html' => true,
                'value' => fn (Tubes $t) => $this->linkToSampleByType(
                    (string) data_get($t, 'tubes_content.nucleic_content_type'),
                    (string) data_get($t, 'tubes_content.nucleic_content.code')
                ),
                'filterModel' => 'originFilters.extractedFrom',
            ],
            [
                'label' => 'Date extracted',
                'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.date_extracted')),
                'filterType' => 'date_range',
                'filterModelStart' => 'originFilters.extractedStart',
                'filterModelEnd' => 'originFilters.extractedEnd',
            ],
            ['label' => 'Protocol', 'valuePath' => 'tubes_content.protocols.name', 'filterModel' => 'originFilters.protocol'],
            [
                'label' => 'Extracted by',
                'personPath' => 'tubes_content.people',
                'filterModel' => 'originFilters.extractedBy',
            ],
            ['label' => 'Extracted at', 'valuePath' => 'tubes_content.laboratories.name', 'filterModel' => 'originFilters.extractedAt'],
        ];
    }

    /**
     * @param  class-string  $type
     * @return array<int, array<string, mixed>>
     */
    private function nucleicDerivedExtraColumns(string $type): array
    {
        // In derived tables, "Extracted from" duplicates the derived-specific content code column.
        $base = array_values(array_filter(
            $this->nucleicExtraColumns(),
            fn (array $c): bool => (string) ($c['label'] ?? '') !== 'Extracted from'
        ));

        return match ($type) {
            HumanSamples::class => array_merge($base, [
                [
                    'label' => 'Human sample code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.nucleic_content.code');

                        return $this->linkToCode($code !== '' ? '/samples/humans/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.humanSampleCode',
                ],
                ['label' => 'Human code', 'valuePath' => 'tubes_content.nucleic_content.humans.code', 'filterModel' => 'originFilters.humanCode'],
                [
                    'label' => 'Date collected',
                    'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.nucleic_content.date_collected')),
                    'filterType' => 'date_range',
                    'filterModelStart' => 'originFilters.collectedStart',
                    'filterModelEnd' => 'originFilters.collectedEnd',
                ],
            ]),
            AnimalSamples::class => array_merge($base, [
                [
                    'label' => 'Animal sample code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.nucleic_content.code');

                        return $this->linkToCode($code !== '' ? '/samples/animals/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.animalSampleCode',
                ],
                [
                    'label' => 'Animal species',
                    'html' => true,
                    'value' => fn (Tubes $t) => $this->animalSpeciesHtml(data_get($t, 'tubes_content.nucleic_content.animals.animal_species')),
                    'filterModel' => 'originFilters.animalSpecies',
                ],
                ['label' => 'Sample type', 'valuePath' => 'tubes_content.nucleic_content.sample_types.name', 'filterModel' => 'originFilters.sampleType'],
                ['label' => 'Sampling site', 'valuePath' => 'tubes_content.nucleic_content.sampling_sites.name', 'filterModel' => 'originFilters.samplingSite'],
                [
                    'label' => 'Date collected',
                    'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.nucleic_content.date_collected')),
                    'filterType' => 'date_range',
                    'filterModelStart' => 'originFilters.collectedStart',
                    'filterModelEnd' => 'originFilters.collectedEnd',
                ],
            ]),
            EnvironmentSamples::class => array_merge($base, [
                [
                    'label' => 'Environment sample code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.nucleic_content.code');

                        return $this->linkToCode($code !== '' ? '/samples/environment/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.environmentSampleCode',
                ],
                ['label' => 'Environment type', 'valuePath' => 'tubes_content.nucleic_content.environment_sample_types.name', 'filterModel' => 'originFilters.environmentType'],
                [
                    'label' => 'Date collected',
                    'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.nucleic_content.date_collected')),
                    'filterType' => 'date_range',
                    'filterModelStart' => 'originFilters.collectedStart',
                    'filterModelEnd' => 'originFilters.collectedEnd',
                ],
            ]),
            Cultures::class => array_merge($base, [
                [
                    'label' => 'Culture code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.nucleic_content.code');

                        return $this->linkToCode($code !== '' ? '/samples/cultures/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.cultureCode',
                ],
                ['label' => 'Culture type', 'valuePath' => 'tubes_content.nucleic_content.type', 'filterModel' => 'originFilters.cultureType'],
            ]),
            default => $base,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cultureExtraColumns(): array
    {
        return [
            ['label' => 'Culture code', 'html' => true, 'value' => fn (Tubes $t) => $this->linkToContent($t), 'filterModel' => 'originFilters.cultureCode'],
            ['label' => 'Culture type', 'valuePath' => 'tubes_content.type', 'filterModel' => 'originFilters.cultureType'],
            ['label' => 'Medium', 'valuePath' => 'tubes_content.medium', 'filterModel' => 'originFilters.medium'],
            ['label' => 'Athmosphere', 'valuePath' => 'tubes_content.athmosphere', 'filterModel' => 'originFilters.athmosphere'],
            ['label' => 'Incubation temp', 'valuePath' => 'tubes_content.incubation_temp', 'filterModel' => 'originFilters.incubationTemp'],
            [
                'label' => 'Date cultured',
                'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.date_cultured')),
                'filterType' => 'date_range',
                'filterModelStart' => 'originFilters.culturedStart',
                'filterModelEnd' => 'originFilters.culturedEnd',
            ],
            [
                'label' => 'Source sample',
                'html' => true,
                'value' => fn (Tubes $t) => $this->linkToSampleByType(
                    (string) data_get($t, 'tubes_content.cultures_content_type'),
                    (string) data_get($t, 'tubes_content.cultures_content.code')
                ),
                'filterModel' => 'originFilters.sourceCode',
            ],
            [
                'label' => 'Cultured by',
                'personPath' => 'tubes_content.people',
                'filterModel' => 'originFilters.culturedBy',
            ],
            ['label' => 'Cultured at', 'valuePath' => 'tubes_content.laboratories.name', 'filterModel' => 'originFilters.culturedAt'],
        ];
    }

    /**
     * @param  class-string  $type
     * @return array<int, array<string, mixed>>
     */
    private function cultureDerivedExtraColumns(string $type): array
    {
        $base = $this->cultureExtraColumns();

        return match ($type) {
            HumanSamples::class => array_merge($base, [
                [
                    'label' => 'Human code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.cultures_content.humans.code');

                        return $this->linkToCode($code !== '' ? '/humans/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.humanCode',
                ],
                [
                    'label' => 'Human name',
                    'value' => fn (Tubes $t) => trim((string) data_get($t, 'tubes_content.cultures_content.humans.first_name').' '.(string) data_get($t, 'tubes_content.cultures_content.humans.last_name')) ?: 'N/A',
                    'filterModel' => 'originFilters.humanName',
                ],
            ]),
            AnimalSamples::class => array_merge($base, [
                [
                    'label' => 'Animal code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.cultures_content.animals.code');

                        return $this->linkToCode($code !== '' ? '/animals/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.animalCode',
                ],
                [
                    'label' => 'Animal species',
                    'html' => true,
                    'value' => fn (Tubes $t) => $this->animalSpeciesHtml(data_get($t, 'tubes_content.cultures_content.animals.animal_species')),
                    'filterModel' => 'originFilters.animalSpecies',
                ],
            ]),
            EnvironmentSamples::class => array_merge($base, [
                [
                    'label' => 'Environment code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.cultures_content.code');

                        return $this->linkToCode($code !== '' ? '/samples/environment/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.environmentSampleCode',
                ],
                ['label' => 'Environment type', 'valuePath' => 'tubes_content.cultures_content.environment_sample_types.name', 'filterModel' => 'originFilters.environmentType'],
            ]),
            ParasiteSamples::class => array_merge($base, [
                [
                    'label' => 'Parasite sample code',
                    'html' => true,
                    'value' => function (Tubes $t): string {
                        $code = (string) data_get($t, 'tubes_content.cultures_content.code');

                        return $this->linkToCode($code !== '' ? '/samples/parasites/'.rawurlencode($code) : null, $code);
                    },
                    'filterModel' => 'originFilters.sampleCode',
                ],
                [
                    'label' => 'Parasite species',
                    'html' => true,
                    'value' => fn (Tubes $t) => ($v = (string) data_get($t, 'tubes_content.cultures_content.parasites.parasite_species.name_scientific'))
                        ? '<span class="italic">'.e($v).'</span>'
                        : '<span class="text-gray-500">N/A</span>',
                    'filterModel' => 'originFilters.tickSpecies',
                ],
            ]),
            default => $base,
        };
    }

    /**
     * @param  class-string  $type
     * @return array<string, mixed>
     */
    private function nucleicDerivedConfig(string $tableId, string $type, string $label, string $fileName): array
    {
        $derivedWith = match ($type) {
            HumanSamples::class => ['tubes_content.nucleic_content.humans'],
            AnimalSamples::class => [
                'tubes_content.nucleic_content.animals.animal_species',
                'tubes_content.nucleic_content.sample_types',
                'tubes_content.nucleic_content.sampling_sites',
            ],
            EnvironmentSamples::class => ['tubes_content.nucleic_content.environment_sample_types'],
            ParasiteSamples::class => [
                'tubes_content.nucleic_content.parasites.parasite_species',
                'tubes_content.nucleic_content.parasites.parasites_origin',
            ],
            Pools::class => [
                'tubes_content.nucleic_content.pool_contents',
                'tubes_content.nucleic_content.pool_contents.samples',
            ],
            default => [],
        };

        return [
            'tableId' => $tableId,
            'subtitle' => 'containing nucleic acids extracted from '.$label,
            'with' => [
                'tubes_content',
                'tubes_content.nucleic_content',
                'tubes_content.protocols',
                'tubes_content.people',
                'tubes_content.laboratories',
                'projects',
                ...$derivedWith,
            ],
            'scope' => fn (Builder $q) => $q
                ->where('tubes_content_type', NucleicAcids::class)
                ->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $sq) => $sq->where('nucleic_content_type', $type)),
            'fileName' => $fileName,
            'extraColumns' => $this->nucleicDerivedExtraColumns($type),
        ];
    }

    /**
     * @param  class-string  $type
     * @return array<string, mixed>
     */
    private function cultureDerivedConfig(string $tableId, string $type, string $label, string $fileName): array
    {
        $derivedWith = match ($type) {
            HumanSamples::class => ['tubes_content.cultures_content.humans'],
            AnimalSamples::class => ['tubes_content.cultures_content.animals.animal_species'],
            EnvironmentSamples::class => ['tubes_content.cultures_content.environment_sample_types'],
            ParasiteSamples::class => [
                'tubes_content.cultures_content.parasites.parasite_species',
                'tubes_content.cultures_content.parasites.parasites_origin',
            ],
            Pools::class => [
                'tubes_content.cultures_content.pool_contents',
                'tubes_content.cultures_content.pool_contents.samples',
            ],
            default => [],
        };

        return [
            'tableId' => $tableId,
            'subtitle' => 'containing cultures obtained from '.$label,
            'with' => [
                'tubes_content',
                'tubes_content.cultures_content',
                'tubes_content.people',
                'tubes_content.laboratories',
                'projects',
                ...$derivedWith,
            ],
            'scope' => fn (Builder $q) => $q
                ->where('tubes_content_type', Cultures::class)
                ->whereHasMorph('tubes_content', Cultures::class, fn (Builder $sq) => $sq->where('cultures_content_type', $type)),
            'fileName' => $fileName,
            'extraColumns' => $this->cultureDerivedExtraColumns($type),
        ];
    }

    /**
     * @param  class-string|null  $derivedType
     * @return array<string, mixed>
     */
    private function poolTableConfig(string $tableId, ?string $derivedType, string $subtitle, string $fileName): array
    {
        return [
            'tableId' => $tableId,
            'subtitle' => $subtitle,
            'with' => [
                'tubes_content',
                'tubes_content.pool_contents',
                // samples eager loaded via morphWith below
                'tubes_content.people',
                'tubes_content.laboratories',
                'projects',
            ],
            'scope' => function (Builder $q) use ($derivedType): Builder {
                $q->where('tubes_content_type', Pools::class);

                if ($derivedType) {
                    $q->whereHasMorph('tubes_content', Pools::class, function (Builder $pq) use ($derivedType) {
                        $pq->whereHas('pool_contents', fn (Builder $pc) => $pc->where('samples_type', $derivedType));
                    });
                }

                return $q;
            },
            'fileName' => $fileName,
            'extraColumns' => [
                ['label' => 'Pool code', 'html' => true, 'value' => fn (Tubes $t) => $this->linkToContent($t), 'filterModel' => 'originFilters.poolCode'],
                ['label' => 'Nr pooled', 'valuePath' => 'tubes_content.nr_pooled', 'filterModel' => 'originFilters.nrPooled'],
                [
                    'label' => 'Date pooled',
                    'value' => fn (Tubes $t) => $this->dateYmd(data_get($t, 'tubes_content.date_pooled')),
                    'filterType' => 'date_range',
                    'filterModelStart' => 'originFilters.pooledStart',
                    'filterModelEnd' => 'originFilters.pooledEnd',
                ],
                [
                    'label' => 'Contents details',
                    'value' => fn (Tubes $t) => $this->poolContentsDetailsHtmlForTube($t),
                    'html' => true,
                    'filterModel' => 'originFilters.contentSearch',
                ],
                ['label' => 'Pooled at', 'valuePath' => 'tubes_content.laboratories.name', 'filterModel' => 'originFilters.pooledAt'],
                [
                    'label' => 'Pooled by',
                    'personPath' => 'tubes_content.people',
                    'filterModel' => 'originFilters.pooledBy',
                ],
            ],
        ];
    }

    private function dateYmd(mixed $value): string
    {
        if (! $value) {
            return 'N/A';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return 'N/A';
        }
    }

    private function animalSpeciesLabel(mixed $species): string
    {
        $common = (string) data_get($species, 'name_common');
        $scientific = (string) data_get($species, 'name_scientific');

        if ($common && $scientific) {
            return $common.' ('.$scientific.')';
        }

        return trim($common) ?: (trim($scientific) ?: 'N/A');
    }

    private function animalSpeciesHtml(mixed $species): string
    {
        $common = trim((string) data_get($species, 'name_common'));
        $scientific = trim((string) data_get($species, 'name_scientific'));

        if (! $common && ! $scientific) {
            return '<span class="text-gray-500">N/A</span>';
        }

        if ($common && $scientific) {
            return '<span class="text-gray-900 font-medium">'.e($common).'</span>'
                .' <span class="text-gray-500">(<span class="italic">'.e($scientific).'</span>)</span>';
        }

        if ($scientific) {
            return '<span class="text-gray-900 font-medium italic">'.e($scientific).'</span>';
        }

        return '<span class="text-gray-900 font-medium">'.e($common).'</span>';
    }

    private function personWithAvatarHtml(?People $person): string
    {
        if (! $person) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $title = trim((string) ($person->title ?? ''));
        $name = trim((string) ($person->name ?? ''));
        $label = trim($title.' '.$name) ?: 'N/A';

        $photoPath = trim((string) (data_get($person, 'pic_path')
            ?? data_get($person, 'photo_path')
            ?? data_get($person, 'photo')
            ?? data_get($person, 'profile_photo_path')
            ?? ''));

        $photoUrl = null;
        if ($photoPath !== '') {
            $photoUrl = str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://')
                ? $photoPath
                : Storage::url($photoPath);
        }

        $initials = strtoupper(mb_substr((string) ($person->first_name ?? ''), 0, 1).mb_substr((string) ($person->last_name ?? ''), 0, 1));
        $initials = $initials ?: strtoupper(mb_substr($name ?: 'N', 0, 1));

        $avatar = $photoUrl
            ? '<img src="'.e($photoUrl).'" alt="'.e($label).'" class="w-7 h-7 rounded-full object-cover border border-gray-200 shadow-sm" />'
            : '<div class="w-7 h-7 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">'
                .e($initials)
                .'</div>';

        return '<div class="flex items-center gap-2">'
            .$avatar
            .'<span class="text-gray-900 font-medium">'.e($label).'</span>'
            .'</div>';
    }

    private function isParasiteTable(): bool
    {
        return $this->selectedTable === 'tube_parasite_table'
            || str_starts_with((string) $this->selectedTable, 'tube_parasite_');
    }

    private function linkToTube(Tubes $tube): string
    {
        $code = (string) ($tube->code ?? '');
        $href = '/bank/tubes/'.rawurlencode($code);

        if (! $code) {
            return '<span class="text-gray-500">N/A</span>';
        }

        if ($this->isGuestMode()) {
            return e($code);
        }

        return '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">'.e($code).'</a>';
    }

    private function linkToCode(?string $href, ?string $label): string
    {
        $label = (string) ($label ?? '');
        if ($label === '') {
            return '<span class="text-gray-500">N/A</span>';
        }

        if (! $href) {
            return e($label);
        }

        if ($this->isGuestMode()) {
            return e($label);
        }

        return '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">'.e($label).'</a>';
    }

    /**
     * @param  class-string|null  $type
     */
    private function linkToSampleByType(?string $type, ?string $code): string
    {
        $code = (string) ($code ?? '');
        if ($code === '') {
            return '<span class="text-gray-500">N/A</span>';
        }

        $href = match ($type) {
            HumanSamples::class => '/samples/humans/'.rawurlencode($code),
            AnimalSamples::class => '/samples/animals/'.rawurlencode($code),
            EnvironmentSamples::class => '/samples/environment/'.rawurlencode($code),
            ParasiteSamples::class => '/samples/parasites/'.rawurlencode($code),
            NucleicAcids::class => '/samples/nucleic/'.rawurlencode($code),
            Cultures::class => '/samples/cultures/'.rawurlencode($code),
            Pools::class => '/samples/pools/'.rawurlencode($code),
            default => null,
        };

        return $this->linkToCode($href, $code);
    }

    public function linkToContent(Tubes $tube): string
    {
        $code = (string) (data_get($tube, 'tubes_content.code') ?? '');
        if (! $code) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $type = (string) ($tube->tubes_content_type ?? '');
        $href = match ($type) {
            HumanSamples::class => '/samples/humans/'.rawurlencode($code),
            AnimalSamples::class => '/samples/animals/'.rawurlencode($code),
            EnvironmentSamples::class => '/samples/environment/'.rawurlencode($code),
            ParasiteSamples::class => '/samples/parasites/'.rawurlencode($code),
            NucleicAcids::class => '/samples/nucleic/'.rawurlencode($code),
            Cultures::class => '/samples/cultures/'.rawurlencode($code),
            Pools::class => '/samples/pools/'.rawurlencode($code),
            default => null,
        };

        if (! $href || $this->isGuestMode()) {
            return e($code);
        }

        return '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">'.e($code).'</a>';
    }

    private function applySelectedTableScope(Builder $query): Builder
    {
        $config = $this->selectedTableConfig();
        $scope = $config['scope'] ?? null;

        return is_callable($scope) ? $scope($query) : $query;
    }

    protected function applyFilters($query)
    {
        /** @var Builder $query */
        if ($this->tubeCodeFilter) {
            $query->where('code', 'like', '%'.$this->tubeCodeFilter.'%');
        }
        if ($this->aliasCodeFilter) {
            $query->where('alias_code', 'like', '%'.$this->aliasCodeFilter.'%');
        }
        if ($this->tubeTypeFilter) {
            $query->where('tube_type', 'like', '%'.$this->tubeTypeFilter.'%');
        }
        if ($this->purposeFilter) {
            $query->where('purpose', 'like', '%'.$this->purposeFilter.'%');
        }
        if ($this->preservantFilter) {
            $query->where('preservant', 'like', '%'.$this->preservantFilter.'%');
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_processed', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_processed', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_processed', '<=', $this->endDate);
        }
        if ($this->contentTypeFilter) {
            $query->where('tubes_content_type', 'like', '%'.$this->contentTypeFilter.'%');
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHas('subProjectAssignment.subProject', function ($q) {
                $q->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
            });
        }

        // Origin filters (based on selected table)
        $search = (string) data_get($this->originFilters, 'sampleCode', '');
        if ($search !== '') {
            $query->whereHas('tubes_content', fn (Builder $q) => $q->where('code', 'like', '%'.$search.'%'));
        }

        // Human tubes
        if ($this->selectedTable === 'tube_human_table') {
            if ($v = (string) data_get($this->originFilters, 'patientCode', '')) {
                $query->whereHasMorph('tubes_content', HumanSamples::class, fn (Builder $q) => $q->whereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'sampleType', '')) {
                $query->whereHasMorph('tubes_content', HumanSamples::class, fn (Builder $q) => $q->whereHas('sample_types', fn (Builder $st) => $st->where('name', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'samplingSite', '')) {
                $query->whereHasMorph('tubes_content', HumanSamples::class, fn (Builder $q) => $q->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$v.'%')));
            }
            if (data_get($this->originFilters, 'collectedStart') || data_get($this->originFilters, 'collectedEnd')) {
                $start = data_get($this->originFilters, 'collectedStart');
                $end = data_get($this->originFilters, 'collectedEnd');
                $query->whereHasMorph('tubes_content', HumanSamples::class, function (Builder $q) use ($start, $end) {
                    if ($start && $end) {
                        $q->whereBetween('date_collected', [$start, $end]);
                    } elseif ($start) {
                        $q->where('date_collected', '>=', $start);
                    } elseif ($end) {
                        $q->where('date_collected', '<=', $end);
                    }
                });
            }
            if ($v = (string) data_get($this->originFilters, 'collectedBy', '')) {
                $query->whereHasMorph('tubes_content', HumanSamples::class, fn (Builder $q) => $q->whereHas('people', fn (Builder $p) => $p->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')));
            }
        }

        // Animal tubes
        if ($this->selectedTable === 'tube_animal_table') {
            if ($v = (string) data_get($this->originFilters, 'animalCode', '')) {
                $query->whereHasMorph('tubes_content', AnimalSamples::class, fn (Builder $q) => $q->whereHas('animals', fn (Builder $a) => $a->where('code', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'species', '')) {
                $query->whereHasMorph('tubes_content', AnimalSamples::class, fn (Builder $q) => $q->whereHas('animals.animal_species', function (Builder $sp) use ($v) {
                    $sp->where('name_common', 'like', '%'.$v.'%')->orWhere('name_scientific', 'like', '%'.$v.'%');
                }));
            }
            if ($v = (string) data_get($this->originFilters, 'samplingSite', '')) {
                $query->whereHasMorph('tubes_content', AnimalSamples::class, fn (Builder $q) => $q->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$v.'%')));
            }
            if (data_get($this->originFilters, 'collectedStart') || data_get($this->originFilters, 'collectedEnd')) {
                $start = data_get($this->originFilters, 'collectedStart');
                $end = data_get($this->originFilters, 'collectedEnd');
                $query->whereHasMorph('tubes_content', AnimalSamples::class, function (Builder $q) use ($start, $end) {
                    if ($start && $end) {
                        $q->whereBetween('date_collected', [$start, $end]);
                    } elseif ($start) {
                        $q->where('date_collected', '>=', $start);
                    } elseif ($end) {
                        $q->where('date_collected', '<=', $end);
                    }
                });
            }
            if ($v = (string) data_get($this->originFilters, 'collectedBy', '')) {
                $query->whereHasMorph('tubes_content', AnimalSamples::class, fn (Builder $q) => $q->whereHas('people', fn (Builder $p) => $p->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')));
            }
        }

        // Environment tubes
        if ($this->selectedTable === 'tube_environment_table') {
            if ($v = (string) data_get($this->originFilters, 'sampleType', '')) {
                $query->whereHasMorph('tubes_content', EnvironmentSamples::class, fn (Builder $q) => $q->whereHas('environment_sample_types', fn (Builder $st) => $st->where('name', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'samplingSite', '')) {
                $query->whereHasMorph('tubes_content', EnvironmentSamples::class, fn (Builder $q) => $q->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$v.'%')));
            }
            if (data_get($this->originFilters, 'collectedStart') || data_get($this->originFilters, 'collectedEnd')) {
                $start = data_get($this->originFilters, 'collectedStart');
                $end = data_get($this->originFilters, 'collectedEnd');
                $query->whereHasMorph('tubes_content', EnvironmentSamples::class, function (Builder $q) use ($start, $end) {
                    if ($start && $end) {
                        $q->whereBetween('date_collected', [$start, $end]);
                    } elseif ($start) {
                        $q->where('date_collected', '>=', $start);
                    } elseif ($end) {
                        $q->where('date_collected', '<=', $end);
                    }
                });
            }
            if ($v = (string) data_get($this->originFilters, 'collectedBy', '')) {
                $query->whereHasMorph('tubes_content', EnvironmentSamples::class, fn (Builder $q) => $q->whereHas('people', fn (Builder $p) => $p->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')));
            }
        }

        // Parasite filters (applied for parasite + derived tables)
        if (str_starts_with($this->selectedTable, 'tube_parasite_') || $this->selectedTable === 'tube_parasite_table') {
            if ($v = (string) data_get($this->originFilters, 'tickSpecies', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites.parasite_species', fn (Builder $ps) => $ps->where('name_scientific', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'tickSex', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->where('sex', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'tickStage', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->where('stage', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'samplingSite', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, function (Builder $q) use ($v) {
                    $q->whereHas('parasites', function (Builder $pq) use ($v) {
                        $pq->whereHasMorph(
                            'parasites_origin',
                            [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class],
                            fn (Builder $oq) => $oq->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$v.'%'))
                        );
                    });
                });
            }

            if ($v = (string) data_get($this->originFilters, 'humanCode', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->whereHasMorph('parasites_origin', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$v.'%')))));
            }
            if ($v = (string) data_get($this->originFilters, 'humanName', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->whereHasMorph('parasites_origin', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('humans', fn (Builder $h) => $h->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')))));
            }
            if ($v = (string) data_get($this->originFilters, 'sampleType', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->whereHasMorph('parasites_origin', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('sample_types', fn (Builder $st) => $st->where('name', 'like', '%'.$v.'%')))));
            }
            if ($v = (string) data_get($this->originFilters, 'animalCode', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->whereHasMorph('parasites_origin', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('animals', fn (Builder $a) => $a->where('code', 'like', '%'.$v.'%')))));
            }
            if ($v = (string) data_get($this->originFilters, 'animalSpecies', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->whereHasMorph('parasites_origin', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('animals.animal_species', fn (Builder $sp) => $sp->where('name_common', 'like', '%'.$v.'%')->orWhere('name_scientific', 'like', '%'.$v.'%')))));
            }
            if ($v = (string) data_get($this->originFilters, 'environmentType', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites', fn (Builder $p) => $p->whereHasMorph('parasites_origin', [EnvironmentSamples::class], fn (Builder $eq) => $eq->whereHas('environment_sample_types', fn (Builder $et) => $et->where('name', 'like', '%'.$v.'%')))));
            }

            if (data_get($this->originFilters, 'collectedStart') || data_get($this->originFilters, 'collectedEnd')) {
                $start = data_get($this->originFilters, 'collectedStart');
                $end = data_get($this->originFilters, 'collectedEnd');
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, function (Builder $q) use ($start, $end) {
                    $q->whereHas('parasites', function (Builder $pq) use ($start, $end) {
                        $pq->whereHasMorph(
                            'parasites_origin',
                            [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class],
                            function (Builder $oq) use ($start, $end) {
                                if ($start && $end) {
                                    $oq->whereBetween('date_collected', [$start, $end]);
                                } elseif ($start) {
                                    $oq->where('date_collected', '>=', $start);
                                } elseif ($end) {
                                    $oq->where('date_collected', '<=', $end);
                                }
                            }
                        );
                    });
                });
            }

            if (data_get($this->originFilters, 'identifiedStart') || data_get($this->originFilters, 'identifiedEnd')) {
                $start = data_get($this->originFilters, 'identifiedStart');
                $end = data_get($this->originFilters, 'identifiedEnd');
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, function (Builder $q) use ($start, $end) {
                    $q->whereHas('parasites', function (Builder $p) use ($start, $end) {
                        if ($start && $end) {
                            $p->whereBetween('date_identified', [$start, $end]);
                        } elseif ($start) {
                            $p->where('date_identified', '>=', $start);
                        } elseif ($end) {
                            $p->where('date_identified', '<=', $end);
                        }
                    });
                });
            }

            if ($v = (string) data_get($this->originFilters, 'identifiedBy', '')) {
                $query->whereHasMorph('tubes_content', ParasiteSamples::class, fn (Builder $q) => $q->whereHas('parasites.people', fn (Builder $p) => $p->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')));
            }
        }

        // Nucleic derived filters (extra derived-only columns)
        if (str_starts_with((string) $this->selectedTable, 'tube_nucleic_') || $this->selectedTable === 'tube_nucleic_table') {
            if ($v = (string) data_get($this->originFilters, 'humanSampleCode', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [HumanSamples::class], fn (Builder $hq) => $hq->where('code', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'humanCode', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'humanName', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('humans', fn (Builder $h) => $h->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'animalSampleCode', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [AnimalSamples::class], fn (Builder $aq) => $aq->where('code', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'animalSpecies', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('animals.animal_species', fn (Builder $sp) => $sp->where('name_common', 'like', '%'.$v.'%')->orWhere('name_scientific', 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'sampleType', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph(
                    'nucleic_content',
                    [HumanSamples::class, AnimalSamples::class],
                    fn (Builder $sq) => $sq->whereHas('sample_types', fn (Builder $st) => $st->where('name', 'like', '%'.$v.'%'))
                ));
            }
            if ($v = (string) data_get($this->originFilters, 'samplingSite', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph(
                    'nucleic_content',
                    [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class],
                    fn (Builder $sq) => $sq->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$v.'%'))
                ));
            }
            if ($v = (string) data_get($this->originFilters, 'environmentSampleCode', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [EnvironmentSamples::class], fn (Builder $eq) => $eq->where('code', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'environmentType', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [EnvironmentSamples::class], fn (Builder $eq) => $eq->whereHas('environment_sample_types', fn (Builder $et) => $et->where('name', 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'cultureCode', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [Cultures::class], fn (Builder $cq) => $cq->where('code', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'cultureType', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [Cultures::class], fn (Builder $cq) => $cq->where('type', 'like', '%'.$v.'%')));
            }

            if (data_get($this->originFilters, 'collectedStart') || data_get($this->originFilters, 'collectedEnd')) {
                $start = data_get($this->originFilters, 'collectedStart');
                $end = data_get($this->originFilters, 'collectedEnd');
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph(
                    'nucleic_content',
                    [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class],
                    function (Builder $sq) use ($start, $end) {
                        if ($start && $end) {
                            $sq->whereBetween('date_collected', [$start, $end]);
                        } elseif ($start) {
                            $sq->where('date_collected', '>=', $start);
                        } elseif ($end) {
                            $sq->where('date_collected', '<=', $end);
                        }
                    }
                ));
            }
        }

        // Culture derived filters (extra derived-only columns)
        if (str_starts_with((string) $this->selectedTable, 'tube_culture_') || $this->selectedTable === 'tube_culture_table') {
            if ($v = (string) data_get($this->originFilters, 'humanCode', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph('cultures_content', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'humanName', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph('cultures_content', [HumanSamples::class], fn (Builder $hq) => $hq->whereHas('humans', fn (Builder $h) => $h->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'animalCode', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph('cultures_content', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('animals', fn (Builder $a) => $a->where('code', 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'animalSpecies', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph('cultures_content', [AnimalSamples::class], fn (Builder $aq) => $aq->whereHas('animals.animal_species', fn (Builder $sp) => $sp->where('name_common', 'like', '%'.$v.'%')->orWhere('name_scientific', 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'environmentSampleCode', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph('cultures_content', [EnvironmentSamples::class], fn (Builder $eq) => $eq->where('code', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'environmentType', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph('cultures_content', [EnvironmentSamples::class], fn (Builder $eq) => $eq->whereHas('environment_sample_types', fn (Builder $et) => $et->where('name', 'like', '%'.$v.'%'))));
            }
            if ($v = (string) data_get($this->originFilters, 'tickSpecies', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph('cultures_content', [ParasiteSamples::class], fn (Builder $pq) => $pq->whereHas('parasites.parasite_species', fn (Builder $ps) => $ps->where('name_scientific', 'like', '%'.$v.'%'))));
            }
        }

        // Nucleic filters (applied for nucleic + derived)
        if (str_starts_with($this->selectedTable, 'tube_nucleic_') || $this->selectedTable === 'tube_nucleic_table') {
            if ($v = (string) data_get($this->originFilters, 'nucleicType', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->where('type', 'like', '%'.$v.'%'));
            }
            if ($v = (string) data_get($this->originFilters, 'extractedFrom', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHasMorph('nucleic_content', [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Cultures::class, Pools::class], fn (Builder $cq) => $cq->where('code', 'like', '%'.$v.'%')));
            }
            if (data_get($this->originFilters, 'extractedStart') || data_get($this->originFilters, 'extractedEnd')) {
                $start = data_get($this->originFilters, 'extractedStart');
                $end = data_get($this->originFilters, 'extractedEnd');
                $query->whereHasMorph('tubes_content', NucleicAcids::class, function (Builder $q) use ($start, $end) {
                    if ($start && $end) {
                        $q->whereBetween('date_extracted', [$start, $end]);
                    } elseif ($start) {
                        $q->where('date_extracted', '>=', $start);
                    } elseif ($end) {
                        $q->where('date_extracted', '<=', $end);
                    }
                });
            }
            if ($v = (string) data_get($this->originFilters, 'protocol', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHas('protocols', fn (Builder $p) => $p->where('name', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'extractedBy', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHas('people', fn (Builder $p) => $p->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'extractedAt', '')) {
                $query->whereHasMorph('tubes_content', NucleicAcids::class, fn (Builder $q) => $q->whereHas('laboratories', fn (Builder $l) => $l->where('name', 'like', '%'.$v.'%')));
            }
        }

        // Culture filters (applied for culture + derived)
        if (str_starts_with($this->selectedTable, 'tube_culture_') || $this->selectedTable === 'tube_culture_table') {
            if ($v = (string) data_get($this->originFilters, 'cultureType', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->where('type', 'like', '%'.$v.'%'));
            }
            if ($v = (string) data_get($this->originFilters, 'medium', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->where('medium', 'like', '%'.$v.'%'));
            }
            if ($v = (string) data_get($this->originFilters, 'athmosphere', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->where('athmosphere', 'like', '%'.$v.'%'));
            }
            if ($v = (string) data_get($this->originFilters, 'incubationTemp', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->where('incubation_temp', 'like', '%'.$v.'%'));
            }
            if (data_get($this->originFilters, 'culturedStart') || data_get($this->originFilters, 'culturedEnd')) {
                $start = data_get($this->originFilters, 'culturedStart');
                $end = data_get($this->originFilters, 'culturedEnd');
                $query->whereHasMorph('tubes_content', Cultures::class, function (Builder $q) use ($start, $end) {
                    if ($start && $end) {
                        $q->whereBetween('date_cultured', [$start, $end]);
                    } elseif ($start) {
                        $q->where('date_cultured', '>=', $start);
                    } elseif ($end) {
                        $q->where('date_cultured', '<=', $end);
                    }
                });
            }
            if ($v = (string) data_get($this->originFilters, 'sourceCode', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHasMorph(
                    'cultures_content',
                    [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Pools::class],
                    fn (Builder $cq) => $cq->where('code', 'like', '%'.$v.'%')
                ));
            }
            if ($v = (string) data_get($this->originFilters, 'culturedBy', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHas('people', fn (Builder $p) => $p->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'culturedAt', '')) {
                $query->whereHasMorph('tubes_content', Cultures::class, fn (Builder $q) => $q->whereHas('laboratories', fn (Builder $l) => $l->where('name', 'like', '%'.$v.'%')));
            }
        }

        // Pool filters (applied for pool + derived)
        if ($this->isPoolTable()) {
            if ($v = (string) data_get($this->originFilters, 'poolCode', '')) {
                $query->whereHasMorph('tubes_content', Pools::class, fn (Builder $q) => $q->where('code', 'like', '%'.$v.'%'));
            }
            if ($v = (string) data_get($this->originFilters, 'nrPooled', '')) {
                $query->whereHasMorph('tubes_content', Pools::class, fn (Builder $q) => $q->where('nr_pooled', 'like', '%'.$v.'%'));
            }

            if (data_get($this->originFilters, 'pooledStart') || data_get($this->originFilters, 'pooledEnd')) {
                $start = data_get($this->originFilters, 'pooledStart');
                $end = data_get($this->originFilters, 'pooledEnd');
                $query->whereHasMorph('tubes_content', Pools::class, function (Builder $q) use ($start, $end) {
                    if ($start && $end) {
                        $q->whereBetween('date_pooled', [$start, $end]);
                    } elseif ($start) {
                        $q->where('date_pooled', '>=', $start);
                    } elseif ($end) {
                        $q->where('date_pooled', '<=', $end);
                    }
                });
            }

            if (filled(data_get($this->originFilters, 'contentSearch'))) {
                $search = (string) data_get($this->originFilters, 'contentSearch');
                $targetType = $this->poolDerivedSamplesType();

                $query->whereHasMorph('tubes_content', Pools::class, function (Builder $q) use ($search, $targetType) {
                    $q->whereHas('pool_contents', function (Builder $pc) use ($search, $targetType) {
                        if ($targetType) {
                            $pc->where('samples_type', $targetType);
                        }

                        $pc->whereHasMorph('samples', [HumanSamples::class], function (Builder $hq) use ($search) {
                            $hq->where('code', 'like', '%'.$search.'%')
                                ->orWhere('date_collected', 'like', '%'.$search.'%')
                                ->orWhereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$search.'%'))
                                ->orWhereHas('sample_types', fn (Builder $st) => $st->where('name', 'like', '%'.$search.'%'))
                                ->orWhereHas('humans', fn (Builder $h) => $h->where('code', 'like', '%'.$search.'%'));
                        });

                        $pc->orWhereHasMorph('samples', [AnimalSamples::class], function (Builder $aq) use ($search) {
                            $aq->where('code', 'like', '%'.$search.'%')
                                ->orWhere('date_collected', 'like', '%'.$search.'%')
                                ->orWhereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$search.'%'))
                                ->orWhereHas('animals', fn (Builder $a) => $a->where('code', 'like', '%'.$search.'%'))
                                ->orWhereHas('animals.animal_species', function (Builder $sp) use ($search) {
                                    $sp->where('name_common', 'like', '%'.$search.'%')
                                        ->orWhere('name_scientific', 'like', '%'.$search.'%');
                                });
                        });

                        $pc->orWhereHasMorph('samples', [EnvironmentSamples::class], function (Builder $eq) use ($search) {
                            $eq->where('code', 'like', '%'.$search.'%')
                                ->orWhere('date_collected', 'like', '%'.$search.'%')
                                ->orWhereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$search.'%'))
                                ->orWhereHas('environment_sample_types', fn (Builder $et) => $et->where('name', 'like', '%'.$search.'%'));
                        });

                        $pc->orWhereHasMorph('samples', [ParasiteSamples::class], function (Builder $pq) use ($search) {
                            $pq->where('code', 'like', '%'.$search.'%')
                                ->orWhere('date_collected', 'like', '%'.$search.'%')
                                ->orWhereHas('parasites', function (Builder $p) use ($search) {
                                    $p->where('sex', 'like', '%'.$search.'%')
                                        ->orWhere('stage', 'like', '%'.$search.'%');
                                })
                                ->orWhereHas('parasites.parasite_species', fn (Builder $ps) => $ps->where('name_scientific', 'like', '%'.$search.'%'))
                                ->orWhereHas('parasites.human_samples', function (Builder $o) use ($search) {
                                    $o->where('code', 'like', '%'.$search.'%')
                                        ->orWhere('date_collected', 'like', '%'.$search.'%')
                                        ->orWhereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$search.'%'));
                                })
                                ->orWhereHas('parasites.animal_samples', function (Builder $o) use ($search) {
                                    $o->where('code', 'like', '%'.$search.'%')
                                        ->orWhere('date_collected', 'like', '%'.$search.'%')
                                        ->orWhereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$search.'%'));
                                })
                                ->orWhereHas('parasites.environment_samples', function (Builder $o) use ($search) {
                                    $o->where('code', 'like', '%'.$search.'%')
                                        ->orWhere('date_collected', 'like', '%'.$search.'%')
                                        ->orWhereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$search.'%'));
                                });
                        });

                        $pc->orWhereHasMorph('samples', [NucleicAcids::class], fn (Builder $nq) => $nq->where('code', 'like', '%'.$search.'%')->orWhere('type', 'like', '%'.$search.'%'));
                        $pc->orWhereHasMorph('samples', [Cultures::class], fn (Builder $cq) => $cq->where('code', 'like', '%'.$search.'%')->orWhere('type', 'like', '%'.$search.'%'));
                    });
                });
            }

            if ($v = (string) data_get($this->originFilters, 'pooledAt', '')) {
                $query->whereHasMorph('tubes_content', Pools::class, fn (Builder $q) => $q->whereHas('laboratories', fn (Builder $l) => $l->where('name', 'like', '%'.$v.'%')));
            }
            if ($v = (string) data_get($this->originFilters, 'pooledBy', '')) {
                $query->whereHasMorph('tubes_content', Pools::class, fn (Builder $q) => $q->whereHas('people', fn (Builder $p) => $p->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$v.'%')));
            }
        }

        // Removed stateFilter
        return $query;
    }

    private function poolContentsDetailsHtmlForTube(Tubes $tube): string
    {
        if (! $this->isPoolTable()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        /** @var Pools|null $pool */
        $pool = data_get($tube, 'tubes_content');
        if (! $pool) {
            return '<span class="text-gray-500">N/A</span>';
        }

        // Render a compact, expandable subtable like other indexes
        $contents = collect(data_get($pool, 'pool_contents') ?? [])->filter(fn ($pc) => data_get($pc, 'samples') !== null)->values();
        if ($contents->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $targetType = $this->poolDerivedSamplesType();
        if ($targetType) {
            $contents = $contents->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $targetType)->values();
        }

        if ($contents->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $rowsAll = $contents->map(function ($pc): string {
            $samplesType = (string) (data_get($pc, 'samples_type') ?? '');
            $typeLabel = $samplesType ? str_replace('App\\Models\\', '', $samplesType) : 'N/A';
            $code = (string) (data_get($pc, 'samples.code') ?? '');
            $href = match ($samplesType) {
                HumanSamples::class => $code ? '/samples/humans/'.rawurlencode($code) : null,
                AnimalSamples::class => $code ? '/samples/animals/'.rawurlencode($code) : null,
                EnvironmentSamples::class => $code ? '/samples/environment/'.rawurlencode($code) : null,
                ParasiteSamples::class => $code ? '/samples/parasites/'.rawurlencode($code) : null,
                NucleicAcids::class => $code ? '/samples/nucleic/'.rawurlencode($code) : null,
                Cultures::class => $code ? '/samples/cultures/'.rawurlencode($code) : null,
                default => null,
            };
            $codeCell = $code
                ? ($href ? '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800">'.e($code).'</a>' : e($code))
                : '<span class="text-gray-500">N/A</span>';

            $site = '';
            $date = '';
            if (in_array($samplesType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = $this->dateYmd(data_get($pc, 'samples.date_collected'));
            } elseif ($samplesType === ParasiteSamples::class) {
                // Parasite samples: sampling site/date are inherited from the parasite origin (human/animal/environment sample)
                $site = (string) (data_get($pc, 'samples.parasites.parasites_origin.sampling_sites.name') ?? '');
                $date = $this->dateYmd(
                    data_get($pc, 'samples.parasites.parasites_origin.date_collected')
                    ?? data_get($pc, 'samples.date_collected')
                );
            } elseif ($samplesType === NucleicAcids::class) {
                // Nucleic acids: inherit sampling site/date from the ultimate nucleic_content
                $site = (string) (
                    data_get($pc, 'samples.nucleic_content.sampling_sites.name')
                    ?? data_get($pc, 'samples.nucleic_content.parasites.parasites_origin.sampling_sites.name')
                    ?? data_get($pc, 'samples.nucleic_content.cultures_content.sampling_sites.name')
                    ?? data_get($pc, 'samples.nucleic_content.cultures_content.parasites.parasites_origin.sampling_sites.name')
                    ?? ''
                );
                $date = $this->dateYmd(
                    data_get($pc, 'samples.nucleic_content.date_collected')
                    ?? data_get($pc, 'samples.nucleic_content.parasites.parasites_origin.date_collected')
                    ?? data_get($pc, 'samples.nucleic_content.cultures_content.date_collected')
                    ?? data_get($pc, 'samples.nucleic_content.cultures_content.parasites.parasites_origin.date_collected')
                    ?? data_get($pc, 'samples.date_collected')
                );
            } elseif ($samplesType === Cultures::class) {
                // Cultures: inherit sampling site/date from the ultimate cultures_content
                $site = (string) (
                    data_get($pc, 'samples.cultures_content.sampling_sites.name')
                    ?? data_get($pc, 'samples.cultures_content.parasites.parasites_origin.sampling_sites.name')
                    ?? ''
                );
                $date = $this->dateYmd(
                    data_get($pc, 'samples.cultures_content.date_collected')
                    ?? data_get($pc, 'samples.cultures_content.parasites.parasites_origin.date_collected')
                    ?? data_get($pc, 'samples.date_collected')
                );
            } else {
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = $this->dateYmd(data_get($pc, 'samples.date_collected'));
            }

            $details = match ($samplesType) {
                HumanSamples::class => '<span class="text-gray-900 font-medium">Human</span>: '
                    .e((string) (data_get($pc, 'samples.humans.code') ?? 'N/A'))
                    .' <span class="text-gray-500">•</span> '
                    .e((string) (data_get($pc, 'samples.sample_types.name') ?? 'N/A')),
                AnimalSamples::class => '<span class="text-gray-900 font-medium">Animal</span>: '
                    .e((string) (data_get($pc, 'samples.animals.code') ?? 'N/A'))
                    .' <span class="text-gray-500">•</span> '
                    .$this->animalSpeciesHtml(data_get($pc, 'samples.animals.animal_species')),
                EnvironmentSamples::class => '<span class="text-gray-900 font-medium">Environment</span>: '
                    .e((string) (data_get($pc, 'samples.environment_sample_types.name') ?? 'N/A')),
                ParasiteSamples::class => '<span class="text-gray-900 font-medium">Parasite</span>: '
                    .'<span class="italic">'.e((string) (data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A')).'</span>'
                    .' <span class="text-gray-500">•</span> '
                    .e((string) (data_get($pc, 'samples.parasites.sex') ?? 'N/A'))
                    .' <span class="text-gray-500">•</span> '
                    .e((string) (data_get($pc, 'samples.parasites.stage') ?? 'N/A')),
                NucleicAcids::class => '<span class="text-gray-900 font-medium">Nucleic</span>: '
                    .e((string) (data_get($pc, 'samples.type') ?? 'N/A')),
                Cultures::class => '<span class="text-gray-900 font-medium">Culture</span>: '
                    .e((string) (data_get($pc, 'samples.type') ?? 'N/A')),
                default => '<span class="text-gray-500">N/A</span>',
            };

            return '<tr class="border-t border-gray-200">'
                .'<td class="px-2 py-1">'.e($typeLabel).'</td>'
                .'<td class="px-2 py-1 whitespace-nowrap">'.$codeCell.'</td>'
                .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                .'<td class="px-2 py-1 whitespace-nowrap">'.($date ?: '<span class="text-gray-500">N/A</span>').'</td>'
                .'<td class="px-2 py-1">'.$details.'</td>'
                .'</tr>';
        })->values()->all();

        $maxVisible = 5;
        $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
        $rowsHidden = array_slice($rowsAll, $maxVisible);
        $safeId = 'pool-'.$tube->id.'-contents';
        $total = count($rowsAll);

        $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
            .'<th class="px-2 py-1">Content type</th>'
            .'<th class="px-2 py-1">Content code</th>'
            .'<th class="px-2 py-1">Sampling site</th>'
            .'<th class="px-2 py-1">Date collected</th>'
            .'<th class="px-2 py-1">Details</th>'
            .'</tr></thead>';

        $html = '<div x-data="{ open: false }" class="space-y-2">';
        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full text-xs text-left border border-gray-200 rounded-lg overflow-hidden">';
        $html .= $thead;
        $html .= '<tbody class="bg-white">'.implode('', $rowsVisible).'</tbody>';
        if (! empty($rowsHidden)) {
            $html .= '<tbody id="'.e($safeId).'" x-show="open" x-cloak class="bg-white">'.implode('', $rowsHidden).'</tbody>';
        }
        $html .= '</table>';
        $html .= '</div>';
        if (! empty($rowsHidden)) {
            $html .= '<button type="button" class="text-xs text-gray-600 hover:text-gray-800 underline" x-on:click="open = !open" aria-controls="'.e($safeId).'">';
            $html .= '<span x-show="!open">Show all ('.$total.')</span>';
            $html .= '<span x-show="open" x-cloak>Hide</span>';
            $html .= '</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function export()
    {
        $config = $this->selectedTableConfig();
        $fileName = (string) ($config['fileName'] ?? 'tubes.csv');

        $query = Tubes::query();
        $with = (array) ($config['with'] ?? []);
        $with[] = 'subProjectAssignment.subProject';

        // Parasite tube tables already eager-load their required origin relations
        // via the per-table `with` config. Don't eager-load nested relations on
        // `parasites_origin` globally, otherwise Laravel will try to load
        // relations like `humans` on non-human origin models (e.g. AnimalSamples).

        if ($this->isPoolTable()) {
            $query->with([
                'tubes_content.pool_contents.samples' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => ['humans', 'sample_types', 'sampling_sites'],
                        AnimalSamples::class => ['animals', 'animals.animal_species', 'sampling_sites'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                        ParasiteSamples::class => [
                            'parasites.parasite_species',
                            'parasites.parasites_origin',
                            'parasites.people',
                        ],
                        NucleicAcids::class => [
                            'nucleic_content' => function (MorphTo $nested): void {
                                $nested->morphWith([
                                    HumanSamples::class => ['sampling_sites'],
                                    AnimalSamples::class => ['sampling_sites'],
                                    EnvironmentSamples::class => ['sampling_sites'],
                                    ParasiteSamples::class => [
                                        'parasites.parasites_origin',
                                        'parasites.parasites_origin.sampling_sites',
                                    ],
                                    Cultures::class => [
                                        'cultures_content' => function (MorphTo $culturesContent): void {
                                            $culturesContent->morphWith([
                                                HumanSamples::class => ['sampling_sites'],
                                                AnimalSamples::class => ['sampling_sites'],
                                                EnvironmentSamples::class => ['sampling_sites'],
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
                                    Pools::class => [
                                        'pool_contents',
                                        'pool_contents.samples',
                                    ],
                                ]);
                            },
                        ],
                        Cultures::class => [
                            'cultures_content' => function (MorphTo $nested): void {
                                $nested->morphWith([
                                    HumanSamples::class => ['sampling_sites'],
                                    AnimalSamples::class => ['sampling_sites'],
                                    EnvironmentSamples::class => ['sampling_sites'],
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

        $query->with(array_values(array_unique($with)));

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public tubes
            $query->where('is_private', false);
        } else {
            // In project mode, show tubes from the selected project
            $query->where('projects_id', $this->projectId);
        }

        $query = $this->applySelectedTableScope($query);
        $query = $this->applyFilters($query);

        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $tubes = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $selectedTable = $this->selectedTable;
        $poolDerivedType = $this->poolDerivedSamplesType();
        $isPool = $this->isPoolTable();

        $extraColumns = (array) ($this->selectedTableConfig()['extraColumns'] ?? []);

        $callback = function () use ($tubes, $isPool, $poolDerivedType, $extraColumns) {
            $file = fopen('php://output', 'w');
            if ($isPool) {
                fputcsv($file, [
                    'Tube code',
                    'Alias code',
                    'Sub-project',
                    'Preservant',
                    'Pool code',
                    'Nr pooled',
                    'Date pooled',
                    'Pool content type',
                    'Pool content code',
                    'Pool content sampling site',
                    'Pool content date collected',
                    'Pool content details',
                    'Pooled at',
                    'Pooled by',
                ]);
            } else {
                $headers = [
                    'Tube code',
                    'Alias code',
                    'Content type',
                    'Content code',
                    'Sub-project',
                ];

                foreach ($extraColumns as $col) {
                    $headers[] = (string) ($col['label'] ?? 'Extra');
                }

                $headers = array_merge($headers, [
                    'Tube type',
                    'Purpose',
                    'Preservant',
                    'Amount',
                    'Amount unit',
                    'Date processed',
                    'Project',
                ]);

                fputcsv($file, $headers);
            }

            foreach ($tubes as $tube) {
                if (! $isPool) {
                    $contentType = match ($tube->tubes_content_type) {
                        HumanSamples::class => 'Human Sample',
                        AnimalSamples::class => 'Animal Sample',
                        EnvironmentSamples::class => 'Environment Sample',
                        ParasiteSamples::class => 'Parasite Sample',
                        Cultures::class => 'Culture',
                        Pools::class => 'Pool',
                        NucleicAcids::class => 'Nucleic Acid',
                        default => 'Unknown',
                    };

                    $row = [
                        $tube->code,
                        $tube->alias_code ?? 'N/A',
                        $contentType,
                        data_get($tube, 'tubes_content.code') ?? 'N/A',
                        data_get($tube, 'subProjectAssignment.subProject.code') ?? 'N/A',
                    ];

                    foreach ($extraColumns as $col) {
                        if (! empty($col['personPath'])) {
                            $p = data_get($tube, (string) $col['personPath']);
                            $row[] = $p ? trim((string) ($p->title ?? '').' '.(string) ($p->first_name ?? '').' '.(string) ($p->last_name ?? '')) : 'N/A';

                            continue;
                        }

                        $raw = isset($col['value'])
                            ? call_user_func($col['value'], $tube)
                            : data_get($tube, $col['valuePath'] ?? '');

                        $raw = $raw === null || $raw === '' ? 'N/A' : $raw;
                        $row[] = is_string($raw) ? trim(strip_tags($raw)) : (string) $raw;
                    }

                    $row = array_merge($row, [
                        $tube->tube_type ?? 'N/A',
                        $tube->purpose ?? 'N/A',
                        $tube->preservant ?? 'N/A',
                        $tube->amount ?? 'N/A',
                        $tube->amount_unit ?? 'N/A',
                        $tube->date_processed ? $tube->date_processed->format('Y-m-d') : 'N/A',
                        $tube->projects?->code ?? 'N/A',
                    ]);

                    fputcsv($file, $row);

                    continue;
                }

                /** @var Pools|null $pool */
                $pool = data_get($tube, 'tubes_content');
                $contents = collect(data_get($pool, 'pool_contents') ?? [])
                    ->filter(fn ($pc) => data_get($pc, 'samples') !== null)
                    ->when($poolDerivedType, fn ($c) => $c->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $poolDerivedType))
                    ->values();

                if ($contents->isEmpty()) {
                    fputcsv($file, [
                        $tube->code,
                        $tube->alias_code ?? 'N/A',
                        data_get($tube, 'subProjectAssignment.subProject.code') ?? 'N/A',
                        $tube->preservant ?? 'N/A',
                        data_get($pool, 'code') ?? 'N/A',
                        data_get($pool, 'nr_pooled') ?? 'N/A',
                        $this->dateYmd(data_get($pool, 'date_pooled')),
                        'N/A',
                        'N/A',
                        'N/A',
                        'N/A',
                        'N/A',
                        data_get($pool, 'laboratories.name') ?? 'N/A',
                        trim((data_get($pool, 'people.first_name') ?? '').' '.(data_get($pool, 'people.last_name') ?? '')) ?: 'N/A',
                    ]);

                    continue;
                }

                foreach ($contents as $pc) {
                    $samplesType = (string) (data_get($pc, 'samples_type') ?? '');
                    $typeLabel = $samplesType ? str_replace('App\\Models\\', '', $samplesType) : 'N/A';
                    $sample = data_get($pc, 'samples');
                    $sampleCode = (string) (data_get($sample, 'code') ?? 'N/A');

                    $site = match ($samplesType) {
                        HumanSamples::class,
                        AnimalSamples::class,
                        EnvironmentSamples::class => (string) (data_get($sample, 'sampling_sites.name') ?? 'N/A'),
                        ParasiteSamples::class => (string) (data_get($sample, 'parasites.parasites_origin.sampling_sites.name') ?? 'N/A'),
                        default => (string) (data_get($sample, 'sampling_sites.name') ?? 'N/A'),
                    };

                    $date = match ($samplesType) {
                        ParasiteSamples::class => $this->dateYmd(data_get($sample, 'parasites.parasites_origin.date_collected') ?? data_get($sample, 'date_collected')),
                        default => $this->dateYmd(data_get($sample, 'date_collected')),
                    };

                    $details = match ($samplesType) {
                        HumanSamples::class => 'Human: '.((string) (data_get($sample, 'humans.code') ?? 'N/A')).' • '.((string) (data_get($sample, 'sample_types.name') ?? 'N/A')),
                        AnimalSamples::class => 'Animal: '.((string) (data_get($sample, 'animals.code') ?? 'N/A')).' • '.$this->animalSpeciesLabel(data_get($sample, 'animals.animal_species')),
                        EnvironmentSamples::class => 'Environment: '.((string) (data_get($sample, 'environment_sample_types.name') ?? 'N/A')),
                        ParasiteSamples::class => 'Parasite: '.((string) (data_get($sample, 'parasites.parasite_species.name_scientific') ?? 'N/A'))
                            .' • '.((string) (data_get($sample, 'parasites.sex') ?? 'N/A'))
                            .' • '.((string) (data_get($sample, 'parasites.stage') ?? 'N/A')),
                        NucleicAcids::class => 'Nucleic: '.((string) (data_get($sample, 'type') ?? 'N/A')),
                        Cultures::class => 'Culture: '.((string) (data_get($sample, 'type') ?? 'N/A')),
                        default => 'N/A',
                    };

                    fputcsv($file, [
                        $tube->code,
                        $tube->alias_code ?? 'N/A',
                        data_get($tube, 'subProjectAssignment.subProject.code') ?? 'N/A',
                        $tube->preservant ?? 'N/A',
                        data_get($pool, 'code') ?? 'N/A',
                        data_get($pool, 'nr_pooled') ?? 'N/A',
                        $this->dateYmd(data_get($pool, 'date_pooled')),
                        $typeLabel,
                        $sampleCode,
                        $site,
                        $date,
                        $details,
                        data_get($pool, 'laboratories.name') ?? 'N/A',
                        trim((data_get($pool, 'people.first_name') ?? '').' '.(data_get($pool, 'people.last_name') ?? '')) ?: 'N/A',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $service = app(TubesService::class);

        $config = $this->selectedTableConfig();
        $with = (array) ($config['with'] ?? ['tubes_content', 'projects']);
        $with[] = 'subProjectAssignment.subProject';

        $query = Tubes::query()->with(array_values(array_unique($with)));

        // Parasite tube tables already eager-load their required origin relations
        // via the per-table `with` config. Don't eager-load nested relations on
        // `parasites_origin` globally, otherwise Laravel will try to load
        // relations like `humans` on non-human origin models (e.g. AnimalSamples).

        if ($this->isPoolTable()) {
            $query->with([
                'tubes_content.pool_contents.samples' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => ['humans', 'sample_types', 'sampling_sites'],
                        AnimalSamples::class => ['animals', 'animals.animal_species', 'sampling_sites'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                        ParasiteSamples::class => [
                            'parasites.parasite_species',
                            'parasites.parasites_origin',
                            'parasites.people',
                        ],
                        NucleicAcids::class => [
                            'nucleic_content' => function (MorphTo $nested): void {
                                $nested->morphWith([
                                    HumanSamples::class => ['sampling_sites'],
                                    AnimalSamples::class => ['sampling_sites'],
                                    EnvironmentSamples::class => ['sampling_sites'],
                                    ParasiteSamples::class => [
                                        'parasites.parasites_origin',
                                        'parasites.parasites_origin.sampling_sites',
                                    ],
                                    Cultures::class => [
                                        'cultures_content' => function (MorphTo $culturesContent): void {
                                            $culturesContent->morphWith([
                                                HumanSamples::class => ['sampling_sites'],
                                                AnimalSamples::class => ['sampling_sites'],
                                                EnvironmentSamples::class => ['sampling_sites'],
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
                                    Pools::class => [
                                        'pool_contents',
                                        'pool_contents.samples',
                                    ],
                                ]);
                            },
                        ],
                        Cultures::class => [
                            'cultures_content' => function (MorphTo $nested): void {
                                $nested->morphWith([
                                    HumanSamples::class => ['sampling_sites'],
                                    AnimalSamples::class => ['sampling_sites'],
                                    EnvironmentSamples::class => ['sampling_sites'],
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

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public tubes
            $query->where('is_private', false);
        } else {
            // In project mode, show tubes from the selected project
            $query->where('projects_id', $this->projectId);
        }

        $query = $this->applySelectedTableScope($query);
        $query = $this->applyFilters($query);

        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $tubes = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('tubes');

        $viewData = array_merge($service->assign(), [
            'tubes' => $tubes,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
            'tableConfig' => $config,
        ]);

        return view('livewire.tubes-list', $viewData);
    }
}
