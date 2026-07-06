<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\CulturesForm;
use App\Models\AnimalSamples;
use App\Models\CultureObservation;
use App\Models\CulturePhoto;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\TubeRequests;
use App\Models\Tubes;
use App\Services\CulturesService;
use App\Support\CultureObservationRecorder;
use App\Support\ProjectPermission;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CulturesIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    public CulturesForm $form;

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
            'culture_code' => 'code',
            'alias_code' => 'alias_code',
            'parent_code' => fn ($q, $dir) => $this->orderByRelation($q, ['parent'], 'code', $dir),
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'culture_type' => 'type',
            'medium' => 'medium',
            'athmosphere' => 'athmosphere',
            'incubation_temp' => 'incubation_temp',
            'date_cultured' => 'date_cultured',
            'cultured_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'first_name', $dir),
            'cultured_at' => fn ($q, $dir) => $this->orderByRelation($q, ['laboratories'], 'name', $dir),
        ];
    }

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
            'guest.cultures.human' => 'culture_human_table',
            'guest.cultures.animal' => 'culture_animal_table',
            'guest.cultures.environment' => 'culture_environment_table',
            'guest.cultures.parasite' => 'culture_parasite_table',
            'guest.cultures.parasite.human' => 'culture_parasite_human_table',
            'guest.cultures.parasite.animal' => 'culture_parasite_animal_table',
            'guest.cultures.parasite.environment' => 'culture_parasite_environment_table',
            'guest.cultures.pool' => 'culture_pool_table',
            'guest.cultures.pool.human' => 'culture_pool_human_table',
            'guest.cultures.pool.animal' => 'culture_pool_animal_table',
            'guest.cultures.pool.environment' => 'culture_pool_environment_table',
            'guest.cultures.pool.parasite' => 'culture_pool_parasite_table',
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

    public function canEditCulturedBy(): bool
    {
        if ($this->isGuestMode() || $this->projectId === null) {
            return false;
        }

        $user = Auth::user();

        return $user && ProjectPermission::canAssignRegistrar($user, (int) $this->projectId);
    }

    public function canEditCulturedAt(?int $ownerPeopleId): bool
    {
        if ($this->isGuestMode() || $this->projectId === null) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (ProjectPermission::canAssignRegistrar($user, (int) $this->projectId)) {
            return true;
        }

        return $this->userCanMutateOwnedRecord($ownerPeopleId, 'cultures');
    }

    public function canMutateCultureRecord(?int $ownerPeopleId): bool
    {
        if ($this->isGuestMode()) {
            return false;
        }

        return $this->userCanMutateOwnedRecord($ownerPeopleId, 'cultures');
    }

    public function updateField($cultureId, $field, $value)
    {
        $culture = Cultures::find($cultureId);
        if (! $culture) {
            return;
        }

        if ($field === 'people_id') {
            if (! $this->canEditCulturedBy()) {
                session()->flash('error', 'Only project admins can edit cultured by.');

                return;
            }
        } elseif ($field === 'laboratories_id') {
            if (! $this->canEditCulturedAt((int) $culture->people_id)) {
                session()->flash('error', 'You do not have permission to edit cultured at for this record.');

                return;
            }
        } elseif (! $this->userCanMutateOwnedRecord((int) $culture->people_id, 'cultures')) {
            session()->flash('error', 'You can only edit records you registered.');

            return;
        }

        $this->form->updateField($cultureId, $field, $value);
    }

    public function delete(Cultures $culture)
    {
        if (! $this->userCanMutateOwnedRecord((int) $culture->people_id, 'cultures')) {
            session()->flash('error', 'You can only delete records you registered.');

            return;
        }

        $culture->delete();
        $this->form->refreshData();
    }

    public $isEditing = false;

    public string $selectedTable = 'cultures_table';

    // Filters
    public $cultureIdFilter;

    public $parentCodeFilter;

    public $aliasCodeFilter;

    public $contentCodeFilter;

    public $cultureTypeFilter;

    public $mediumFilter;

    public $athmosphereFilter;

    public $startDate;

    public $endDate;

    public $scientistFilter;

    public $placeFilter;

    public ?int $photoPreviewCultureId = null;

    public ?string $photoPreviewUrl = null;

    public ?string $photoPreviewCode = null;

    public bool $photoPreviewCanDelete = false;

    /** @var list<array{id:int,url:string,observed_at:?string,notes:?string,observer:?string}> */
    public array $photoPreviewPhotos = [];

    public int $photoPreviewIndex = 0;

    // Content-specific filters (vary by selected table)
    public $samplingSiteFilter;

    public $sampleTypeFilter;

    public $photoFilter;

    public $discardedFilter;

    public $humanCodeFilter;

    public $animalCodeFilter;

    public $speciesFilter;

    public $environmentSampleTypeFilter;

    public $parasiteSpeciesFilter;

    public $parasiteOriginCodeFilter;

    public $contentsDetailsFilter;

    public $subProjectCodeFilter;

    public $associatedTubesFilter;

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
        if (! $this->userCanWriteModule('cultures')) {
            session()->flash('error', 'You do not have permission to edit cultures in this project.');

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

    public function updating($field)
    {
        if (
            is_string($field)
            && (
                str_starts_with($field, 'selectedCultures')
                || $field === 'selectAllFiltered'
            )
        ) {
            return;
        }

        $this->resetPage('articles-page');
    }

    public function updatedPhotoFilter($value): void
    {
        if ($value === 'broken' && ! $this->canFilterBrokenPhotos()) {
            $this->photoFilter = '';
        }
    }

    public function updatedSelectedTable(): void
    {
        $this->resetPage('articles-page');
        $this->reset(['photo', 'uploadingPhoto', 'uploadError']);
        $this->selectedCultures = [];
        $this->selectAllFiltered = false;
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedCultures = [];

            return;
        }

        $query = Cultures::query()->orderBy('created_at', 'desc');

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

            $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
            if ($currentPeopleId <= 0) {
                $this->selectedCultures = [];

                return;
            }

            $query->where('people_id', $currentPeopleId);
        }

        $query = $this->applySelectedTableScope($query);
        $query = $this->applyFilters($query);

        $ids = $query
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $this->selectedCultures = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function selectedWith(): array
    {
        return match ($this->selectedTable) {
            'culture_human_table' => [
                'cultures_content.humans',
                'cultures_content.sample_types',
                'cultures_content.sampling_sites',
            ],
            'culture_animal_table' => [
                'cultures_content.animals',
                'cultures_content.animals.animal_species',
                'cultures_content.sampling_sites',
            ],
            'culture_environment_table' => [
                'cultures_content.environment_sample_types',
                'cultures_content.sampling_sites',
            ],
            'culture_parasite_table' => [
                'cultures_content.parasites',
                'cultures_content.parasites.parasite_species',
                'cultures_content.parasites.parasites_origin',
                'cultures_content.parasites.parasites_origin.sampling_sites',
            ],
            'culture_parasite_human_table' => [
                'cultures_content.parasites',
                'cultures_content.parasites.parasite_species',
                'cultures_content.parasites.parasites_origin',
                'cultures_content.parasites.parasites_origin.humans',
                'cultures_content.parasites.parasites_origin.sampling_sites',
            ],
            'culture_parasite_animal_table' => [
                'cultures_content.parasites',
                'cultures_content.parasites.parasite_species',
                'cultures_content.parasites.parasites_origin',
                'cultures_content.parasites.parasites_origin.animals',
                'cultures_content.parasites.parasites_origin.animals.animal_species',
                'cultures_content.parasites.parasites_origin.sampling_sites',
            ],
            'culture_parasite_environment_table' => [
                'cultures_content.parasites',
                'cultures_content.parasites.parasite_species',
                'cultures_content.parasites.parasites_origin',
                'cultures_content.parasites.parasites_origin.environment_sample_types',
                'cultures_content.parasites.parasites_origin.sampling_sites',
            ],
            'culture_pool_table',
            'culture_pool_human_table',
            'culture_pool_animal_table',
            'culture_pool_environment_table',
            'culture_pool_parasite_table' => [
                'cultures_content.pool_contents',
                // samples is morphTo; eager load via morphWith (below)
            ],
            default => [],
        };
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
            $originCode = (string) (data_get($pc, 'samples.parasites.human_samples.code')
                ?? data_get($pc, 'samples.parasites.animal_samples.code')
                ?? data_get($pc, 'samples.parasites.environment_samples.code')
                ?? '');
            $originSite = (string) (data_get($pc, 'samples.parasites.human_samples.sampling_sites.name')
                ?? data_get($pc, 'samples.parasites.animal_samples.sampling_sites.name')
                ?? data_get($pc, 'samples.parasites.environment_samples.sampling_sites.name')
                ?? '');
            $originDate = data_get($pc, 'samples.parasites.human_samples.date_collected')
                ?? data_get($pc, 'samples.parasites.animal_samples.date_collected')
                ?? data_get($pc, 'samples.parasites.environment_samples.date_collected');

            return [
                'site' => $originSite ?: null,
                'date' => $originDate ?? data_get($pc, 'samples.date_collected'),
            ];
        }

        return ['site' => null, 'date' => null];
    }

    private function poolContentDetailsString(mixed $pc): string
    {
        $samplesType = (string) (data_get($pc, 'samples_type') ?? '');

        return match ($samplesType) {
            HumanSamples::class => 'Human: '.((string) (data_get($pc, 'samples.humans.code') ?? 'N/A'))
                .' • '.((string) (data_get($pc, 'samples.sample_types.name') ?? 'N/A')),
            AnimalSamples::class => (function () use ($pc): string {
                $common = (string) (data_get($pc, 'samples.animals.animal_species.name_common') ?? '');
                $scientific = (string) (data_get($pc, 'samples.animals.animal_species.name_scientific') ?? '');
                $species = trim($common) ?: trim($scientific);
                if ($common && $scientific) {
                    $species = $common.' ('.$scientific.')';
                }

                return 'Animal: '.((string) (data_get($pc, 'samples.animals.code') ?? 'N/A')).' • '.($species ?: 'N/A');
            })(),
            EnvironmentSamples::class => 'Environment: '.((string) (data_get($pc, 'samples.environment_sample_types.name') ?? 'N/A')),
            ParasiteSamples::class => 'Parasite: '.((string) (data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? 'N/A'))
                .' • '.((string) (data_get($pc, 'samples.parasites.sex') ?? 'N/A'))
                .' • '.((string) (data_get($pc, 'samples.parasites.stage') ?? 'N/A'))
                .' • origin '.((string) (data_get($pc, 'samples.parasites.human_samples.code')
                    ?? data_get($pc, 'samples.parasites.animal_samples.code')
                    ?? data_get($pc, 'samples.parasites.environment_samples.code')
                    ?? 'N/A')),
            default => 'N/A',
        };
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
                $details = $this->poolContentDetailsString($pc);

                $typeLabel = $samplesType ? str_replace('App\\Models\\', '', $samplesType) : 'N/A';

                $href = match ($samplesType) {
                    HumanSamples::class => $code ? '/samples/humans/'.rawurlencode($code) : null,
                    AnimalSamples::class => $code ? '/samples/animals/'.rawurlencode($code) : null,
                    EnvironmentSamples::class => $code ? '/samples/environment/'.rawurlencode($code) : null,
                    ParasiteSamples::class => $code ? '/samples/parasites/'.rawurlencode($code) : null,
                    default => null,
                };

                $codeCell = $code
                    ? (! $this->isGuestMode() && $href ? '<a href="'.e($href).'" class="text-blue-600 hover:text-blue-800">'.e($code).'</a>' : e($code))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1">'.($typeLabel ? e($typeLabel) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$codeCell.'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($date ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($details !== 'N/A' ? e($details) : '<span class="text-gray-500">N/A</span>').'</td>'
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
            .'<th class="px-2 py-1">Details</th>'
            .'</tr></thead>';

        return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
    }

    private function poolContentsDetailsHtmlForPoolModel(Pools $pool, string $samplesType, string $id): string
    {
        $contents = collect($pool->pool_contents ?? [])
            ->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $samplesType)
            ->filter(fn ($pc) => data_get($pc, 'samples') !== null)
            ->values();

        if ($contents->isEmpty()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $maxVisible = 5;
        $rowsAll = [];
        $thead = '';

        if ($samplesType === HumanSamples::class) {
            $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Patient code</th>'
                .'<th class="px-2 py-1">Sample type</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $patientCode = (string) (data_get($pc, 'samples.humans.code') ?? '');
                $sampleType = (string) (data_get($pc, 'samples.sample_types.name') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleHref = $sampleCode ? '/samples/humans/'.rawurlencode($sampleCode) : '#';
                $patientHref = $patientCode ? '/humans/'.rawurlencode($patientCode) : '#';

                $sampleCodeCell = $sampleCode
                    ? (! $this->isGuestMode() ? '<a href="'.e($sampleHref).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : e($sampleCode))
                    : '<span class="text-gray-500">N/A</span>';
                $patientCodeCell = $patientCode
                    ? (! $this->isGuestMode() ? '<a href="'.e($patientHref).'" class="text-blue-600 hover:text-blue-800">'.e($patientCode).'</a>' : e($patientCode))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$sampleCodeCell.'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$patientCodeCell.'</td>'
                    .'<td class="px-2 py-1">'.($sampleType ? e($sampleType) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();
        } elseif ($samplesType === AnimalSamples::class) {
            $thead = '<thead class="bg-gray-100 text-gray-700"><tr>'
                .'<th class="px-2 py-1">Sample code</th>'
                .'<th class="px-2 py-1">Animal code</th>'
                .'<th class="px-2 py-1">Species</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $animalCode = (string) (data_get($pc, 'samples.animals.code') ?? '');
                $common = (string) (data_get($pc, 'samples.animals.animal_species.name_common') ?? '');
                $scientific = (string) (data_get($pc, 'samples.animals.animal_species.name_scientific') ?? '');
                $species = trim($common) ?: trim($scientific);
                if ($common && $scientific) {
                    $species = $common.' ('.$scientific.')';
                }
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleHref = $sampleCode ? '/samples/animals/'.rawurlencode($sampleCode) : '#';
                $animalHref = $animalCode ? '/animals/'.rawurlencode($animalCode) : '#';

                $sampleCodeCell = $sampleCode
                    ? (! $this->isGuestMode() ? '<a href="'.e($sampleHref).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : e($sampleCode))
                    : '<span class="text-gray-500">N/A</span>';
                $animalCodeCell = $animalCode
                    ? (! $this->isGuestMode() ? '<a href="'.e($animalHref).'" class="text-blue-600 hover:text-blue-800">'.e($animalCode).'</a>' : e($animalCode))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$sampleCodeCell.'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$animalCodeCell.'</td>'
                    .'<td class="px-2 py-1">'.($species ? e($species) : '<span class="text-gray-500">N/A</span>').'</td>'
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
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $type = (string) (data_get($pc, 'samples.environment_sample_types.name') ?? '');
                $site = (string) (data_get($pc, 'samples.sampling_sites.name') ?? '');
                $date = data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleHref = $sampleCode ? '/samples/environment/'.rawurlencode($sampleCode) : '#';

                $sampleCodeCell = $sampleCode
                    ? (! $this->isGuestMode() ? '<a href="'.e($sampleHref).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : e($sampleCode))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$sampleCodeCell.'</td>'
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
                .'<th class="px-2 py-1">Origin sample</th>'
                .'<th class="px-2 py-1">Sampling site</th>'
                .'<th class="px-2 py-1">Date collected</th>'
                .'</tr></thead>';

            $rowsAll = $contents->map(function ($pc): string {
                $sampleCode = (string) (data_get($pc, 'samples.code') ?? '');
                $species = (string) (data_get($pc, 'samples.parasites.parasite_species.name_scientific') ?? '');
                $sex = (string) (data_get($pc, 'samples.parasites.sex') ?? '');
                $stage = (string) (data_get($pc, 'samples.parasites.stage') ?? '');
                $originCode = (string) (data_get($pc, 'samples.parasites.human_samples.code')
                    ?? data_get($pc, 'samples.parasites.animal_samples.code')
                    ?? data_get($pc, 'samples.parasites.environment_samples.code')
                    ?? '');
                $site = (string) (data_get($pc, 'samples.parasites.human_samples.sampling_sites.name')
                    ?? data_get($pc, 'samples.parasites.animal_samples.sampling_sites.name')
                    ?? data_get($pc, 'samples.parasites.environment_samples.sampling_sites.name')
                    ?? '');
                $date = data_get($pc, 'samples.parasites.human_samples.date_collected')
                    ?? data_get($pc, 'samples.parasites.animal_samples.date_collected')
                    ?? data_get($pc, 'samples.parasites.environment_samples.date_collected')
                    ?? data_get($pc, 'samples.date_collected');
                $dateYmd = $date ? (string) Carbon::parse($date)->format('Y-m-d') : '';

                $sampleHref = $sampleCode ? '/samples/parasites/'.rawurlencode($sampleCode) : '#';

                $sampleCodeCell = $sampleCode
                    ? (! $this->isGuestMode() ? '<a href="'.e($sampleHref).'" class="text-blue-600 hover:text-blue-800">'.e($sampleCode).'</a>' : e($sampleCode))
                    : '<span class="text-gray-500">N/A</span>';

                return '<tr class="border-t border-gray-200">'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.$sampleCodeCell.'</td>'
                    .'<td class="px-2 py-1">'.($species ? e($species) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($sex ? e($sex) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($stage ? e($stage) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($originCode ? e($originCode) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1">'.($site ? e($site) : '<span class="text-gray-500">N/A</span>').'</td>'
                    .'<td class="px-2 py-1 whitespace-nowrap">'.($dateYmd ?: '<span class="text-gray-500">N/A</span>').'</td>'
                    .'</tr>';
            })->values()->all();
        } else {
            return $this->poolContentsDetailsCombinedHtmlForPoolModel($pool, $id);
        }

        if (empty($rowsAll)) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $rowsVisible = array_slice($rowsAll, 0, $maxVisible);
        $rowsHidden = array_slice($rowsAll, $maxVisible);

        return $this->collapsibleSubtableHtml($id, $thead, $rowsVisible, $rowsHidden);
    }

    public function poolContentsDetailsHtmlForCulture(Cultures $culture): string
    {
        if (! $this->isPoolTable()) {
            return '<span class="text-gray-500">N/A</span>';
        }

        /** @var Pools|null $pool */
        $pool = data_get($culture, 'cultures_content');
        if (! $pool) {
            return '<span class="text-gray-500">N/A</span>';
        }

        $derivedType = $this->poolDerivedSamplesType();
        $id = 'culture-'.$culture->id.'-pool-details'.($derivedType ? '-'.class_basename($derivedType) : '');

        return $derivedType
            ? $this->poolContentsDetailsHtmlForPoolModel($pool, $derivedType, $id)
            : $this->poolContentsDetailsCombinedHtmlForPoolModel($pool, $id);
    }

    private function isPoolTable(): bool
    {
        return in_array($this->selectedTable, [
            'culture_pool_table',
            'culture_pool_human_table',
            'culture_pool_animal_table',
            'culture_pool_environment_table',
            'culture_pool_parasite_table',
        ], true);
    }

    /**
     * @return class-string|null
     */
    private function poolDerivedSamplesType(): ?string
    {
        return match ($this->selectedTable) {
            'culture_pool_human_table' => HumanSamples::class,
            'culture_pool_animal_table' => AnimalSamples::class,
            'culture_pool_environment_table' => EnvironmentSamples::class,
            'culture_pool_parasite_table' => ParasiteSamples::class,
            default => null,
        };
    }

    private function applySelectedTableScope(Builder $query): Builder
    {
        return match ($this->selectedTable) {
            'culture_human_table' => $query->where('cultures_content_type', HumanSamples::class),
            'culture_animal_table' => $query->where('cultures_content_type', AnimalSamples::class),
            'culture_environment_table' => $query->where('cultures_content_type', EnvironmentSamples::class),
            'culture_parasite_table' => $query->where('cultures_content_type', ParasiteSamples::class),
            'culture_pool_table' => $query->where('cultures_content_type', Pools::class),

            'culture_parasite_human_table' => $query->where('cultures_content_type', ParasiteSamples::class)
                ->whereHasMorph('cultures_content', ParasiteSamples::class, function (Builder $q) {
                    $q->whereHas('parasites', function (Builder $pq) {
                        $pq->whereHasMorph('parasites_origin', [HumanSamples::class]);
                    });
                }),
            'culture_parasite_animal_table' => $query->where('cultures_content_type', ParasiteSamples::class)
                ->whereHasMorph('cultures_content', ParasiteSamples::class, function (Builder $q) {
                    $q->whereHas('parasites', function (Builder $pq) {
                        $pq->whereHasMorph('parasites_origin', [AnimalSamples::class]);
                    });
                }),
            'culture_parasite_environment_table' => $query->where('cultures_content_type', ParasiteSamples::class)
                ->whereHasMorph('cultures_content', ParasiteSamples::class, function (Builder $q) {
                    $q->whereHas('parasites', function (Builder $pq) {
                        $pq->whereHasMorph('parasites_origin', [EnvironmentSamples::class]);
                    });
                }),

            'culture_pool_human_table' => $query->where('cultures_content_type', Pools::class)
                ->whereHasMorph('cultures_content', Pools::class, function (Builder $q) {
                    $q->whereHas('pool_contents', fn (Builder $pc) => $pc->where('samples_type', HumanSamples::class));
                }),
            'culture_pool_animal_table' => $query->where('cultures_content_type', Pools::class)
                ->whereHasMorph('cultures_content', Pools::class, function (Builder $q) {
                    $q->whereHas('pool_contents', fn (Builder $pc) => $pc->where('samples_type', AnimalSamples::class));
                }),
            'culture_pool_environment_table' => $query->where('cultures_content_type', Pools::class)
                ->whereHasMorph('cultures_content', Pools::class, function (Builder $q) {
                    $q->whereHas('pool_contents', fn (Builder $pc) => $pc->where('samples_type', EnvironmentSamples::class));
                }),
            'culture_pool_parasite_table' => $query->where('cultures_content_type', Pools::class)
                ->whereHasMorph('cultures_content', Pools::class, function (Builder $q) {
                    $q->whereHas('pool_contents', fn (Builder $pc) => $pc->where('samples_type', ParasiteSamples::class));
                }),

            default => $query,
        };
    }

    protected function applyFilters($query)
    {
        if ($this->cultureIdFilter) {
            $query->where('code', 'like', '%'.$this->cultureIdFilter.'%');
        }
        if ($this->parentCodeFilter) {
            $query->whereHas('parent', function ($q) {
                $q->where('code', 'like', '%'.$this->parentCodeFilter.'%');
            });
        }
        if ($this->aliasCodeFilter) {
            $query->where('alias_code', 'like', '%'.$this->aliasCodeFilter.'%');
        }
        if ($this->cultureTypeFilter) {
            $query->where('type', 'like', '%'.$this->cultureTypeFilter.'%');
        }
        if ($this->contentCodeFilter) {
            $query->whereHasMorph(
                'cultures_content',
                [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Pools::class],
                fn (Builder $q) => $q->where('code', 'like', '%'.$this->contentCodeFilter.'%')
            );
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHas('subProjectAssignment.subProject', function (Builder $q) {
                $q->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
            });
        }
        if ($this->mediumFilter) {
            $query->where('medium', 'like', '%'.$this->mediumFilter.'%');
        }
        if ($this->athmosphereFilter) {
            $query->where('athmosphere', 'like', '%'.$this->athmosphereFilter.'%');
        }
        if ($this->associatedTubesFilter) {
            $search = '%'.$this->associatedTubesFilter.'%';
            $query->whereHas('tubes', function (Builder $tubeQuery) use ($search): void {
                $tubeQuery->where('code', 'like', $search)
                    ->orWhere('alias_code', 'like', $search)
                    ->orWhere('tube_type', 'like', $search);
            });
        }
        if ($this->photoFilter === 'has') {
            $query->where(function (Builder $photoQuery): void {
                $photoQuery->where(function (Builder $legacyQuery): void {
                    $legacyQuery->whereNotNull('photo_path')
                        ->where('photo_path', '<>', '');
                })->orWhereHas('photos');
            });
        } elseif ($this->photoFilter === 'none') {
            $query->where(function (Builder $legacyQuery): void {
                $legacyQuery->whereNull('photo_path')
                    ->orWhere('photo_path', '');
            })->whereDoesntHave('photos');
        } elseif ($this->photoFilter === 'broken' && $this->canFilterBrokenPhotos()) {
            $legacyMissingPaths = (clone $query)
                ->select('photo_path')
                ->whereNotNull('photo_path')
                ->where('photo_path', '<>', '')
                ->distinct()
                ->pluck('photo_path')
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->filter(fn (string $path) => ! Storage::disk('local')->exists($path))
                ->values();

            $brokenCultureIds = CulturePhoto::query()
                ->whereIn('cultures_id', (clone $query)->select('cultures.id'))
                ->get(['cultures_id', 'photo_path'])
                ->filter(fn (CulturePhoto $photo) => $photo->photo_path !== '' && ! Storage::disk('local')->exists($photo->photo_path))
                ->pluck('cultures_id')
                ->unique()
                ->values();

            if ($legacyMissingPaths->isEmpty() && $brokenCultureIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function (Builder $brokenQuery) use ($legacyMissingPaths, $brokenCultureIds): void {
                    if ($legacyMissingPaths->isNotEmpty()) {
                        $brokenQuery->whereIn('photo_path', $legacyMissingPaths->all());
                    }

                    if ($brokenCultureIds->isNotEmpty()) {
                        $brokenQuery->orWhereIn('id', $brokenCultureIds->all());
                    }
                });
            }
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_cultured', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_cultured', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_cultured', '<=', $this->endDate);
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
        if ($this->discardedFilter === 'yes') {
            $query->where('is_discarded', true);
        } elseif ($this->discardedFilter === 'no') {
            $query->where('is_discarded', false);
        }

        // Content-specific filters
        if ($this->selectedTable === 'culture_human_table') {
            if ($this->humanCodeFilter) {
                $query->whereHasMorph('cultures_content', HumanSamples::class, function (Builder $q) {
                    $q->whereHas('humans', fn (Builder $hq) => $hq->where('code', 'like', '%'.$this->humanCodeFilter.'%'));
                });
            }
            if ($this->sampleTypeFilter) {
                $query->whereHasMorph('cultures_content', HumanSamples::class, function (Builder $q) {
                    $q->whereHas('sample_types', fn (Builder $st) => $st->where('name', 'like', '%'.$this->sampleTypeFilter.'%'));
                });
            }
            if ($this->samplingSiteFilter) {
                $query->whereHasMorph('cultures_content', HumanSamples::class, function (Builder $q) {
                    $q->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$this->samplingSiteFilter.'%'));
                });
            }
        }

        if ($this->selectedTable === 'culture_animal_table') {
            if ($this->animalCodeFilter) {
                $query->whereHasMorph('cultures_content', AnimalSamples::class, function (Builder $q) {
                    $q->whereHas('animals', fn (Builder $aq) => $aq->where('code', 'like', '%'.$this->animalCodeFilter.'%'));
                });
            }
            if ($this->speciesFilter) {
                $query->whereHasMorph('cultures_content', AnimalSamples::class, function (Builder $q) {
                    $q->whereHas('animals.animal_species', function (Builder $sq) {
                        $v = '%'.$this->speciesFilter.'%';
                        $sq->where('name_common', 'like', $v)->orWhere('name_scientific', 'like', $v);
                    });
                });
            }
            if ($this->samplingSiteFilter) {
                $query->whereHasMorph('cultures_content', AnimalSamples::class, function (Builder $q) {
                    $q->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$this->samplingSiteFilter.'%'));
                });
            }
        }

        if ($this->selectedTable === 'culture_environment_table') {
            if ($this->environmentSampleTypeFilter) {
                $query->whereHasMorph('cultures_content', EnvironmentSamples::class, function (Builder $q) {
                    $q->whereHas('environment_sample_types', fn (Builder $et) => $et->where('name', 'like', '%'.$this->environmentSampleTypeFilter.'%'));
                });
            }
            if ($this->samplingSiteFilter) {
                $query->whereHasMorph('cultures_content', EnvironmentSamples::class, function (Builder $q) {
                    $q->whereHas('sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', '%'.$this->samplingSiteFilter.'%'));
                });
            }
        }

        if (in_array($this->selectedTable, ['culture_parasite_table', 'culture_parasite_human_table', 'culture_parasite_animal_table', 'culture_parasite_environment_table'], true)) {
            if ($this->parasiteSpeciesFilter) {
                $query->whereHasMorph('cultures_content', ParasiteSamples::class, function (Builder $q) {
                    $q->whereHas('parasites.parasite_species', fn (Builder $ps) => $ps->where('name_scientific', 'like', '%'.$this->parasiteSpeciesFilter.'%'));
                });
            }
            if ($this->parasiteOriginCodeFilter) {
                $query->whereHasMorph('cultures_content', ParasiteSamples::class, function (Builder $q) {
                    $search = '%'.$this->parasiteOriginCodeFilter.'%';
                    $q->whereHas('parasites.human_samples', fn (Builder $oq) => $oq->where('code', 'like', $search))
                        ->orWhereHas('parasites.animal_samples', fn (Builder $oq) => $oq->where('code', 'like', $search))
                        ->orWhereHas('parasites.environment_samples', fn (Builder $oq) => $oq->where('code', 'like', $search));
                });
            }
            if ($this->samplingSiteFilter) {
                $query->whereHasMorph('cultures_content', ParasiteSamples::class, function (Builder $q) {
                    $search = '%'.$this->samplingSiteFilter.'%';
                    $q->whereHas('parasites.human_samples.sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', $search))
                        ->orWhereHas('parasites.animal_samples.sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', $search))
                        ->orWhereHas('parasites.environment_samples.sampling_sites', fn (Builder $ss) => $ss->where('name', 'like', $search));
                });
            }
        }

        if ($this->isPoolTable() && filled($this->contentsDetailsFilter)) {
            $search = (string) $this->contentsDetailsFilter;
            $targetType = $this->poolDerivedSamplesType();

            // SQLite can blow up with deeply nested ORs; keep it simple there.
            if (config('database.default') === 'sqlite') {
                $query->whereHasMorph('cultures_content', Pools::class, function (Builder $q) use ($search, $targetType) {
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
                    });
                });

                return $query;
            }

            $query->whereHasMorph('cultures_content', Pools::class, function (Builder $q) use ($search, $targetType) {
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
                });
            });
        }

        return $query;
    }

    public function export()
    {
        $fileName = match ($this->selectedTable) {
            'culture_human_table' => 'human_cultures.csv',
            'culture_animal_table' => 'animal_cultures.csv',
            'culture_environment_table' => 'environment_cultures.csv',
            'culture_parasite_table' => 'parasite_cultures.csv',
            'culture_parasite_human_table' => 'parasite_human_cultures.csv',
            'culture_parasite_animal_table' => 'parasite_animal_cultures.csv',
            'culture_parasite_environment_table' => 'parasite_environment_cultures.csv',
            'culture_pool_table' => 'pool_cultures.csv',
            'culture_pool_human_table' => 'pool_human_cultures.csv',
            'culture_pool_animal_table' => 'pool_animal_cultures.csv',
            'culture_pool_environment_table' => 'pool_environment_cultures.csv',
            'culture_pool_parasite_table' => 'pool_parasite_cultures.csv',
            default => 'cultures.csv',
        };

        $query = Cultures::with(array_values(array_unique(array_merge([
            'cultures_content',
            'parent',
            'people',
            'laboratories',
            'tubes',
            'photos',
            'observations.photo',
            'observations.people',
            'latestObservation.photo',
            'latestPhoto',
            'projects',
            'subProjectAssignment.subProject',
        ], $this->selectedWith()))));

        if ($this->isPoolTable()) {
            $query->with([
                'cultures_content.pool_contents.samples' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => ['humans', 'sample_types', 'sampling_sites'],
                        AnimalSamples::class => ['animals', 'animals.animal_species', 'sampling_sites'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                        ParasiteSamples::class => ['parasites.parasite_species', 'parasites.parasites_origin', 'parasites.parasites_origin.sampling_sites'],
                    ]);
                },
            ]);
        }

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public cultures
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            // In project mode, show samples from the selected project
            $query->where(function ($q) {
                $q->where('projects_id', $this->projectId)
                    ->orWhereHas('tubes', function ($tubeQuery) {
                        $tubeQuery->where('projects_id', $this->projectId);
                    });
            });
        }

        $query = $this->applySelectedTableScope($query);
        $query = $this->applyFilters($query);

        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $cultures = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $selectedTable = $this->selectedTable;
        $poolDerivedType = $this->poolDerivedSamplesType();
        $isPoolTable = $this->isPoolTable();

        $callback = function () use ($cultures, $selectedTable, $isPoolTable, $poolDerivedType) {
            $file = fopen('php://output', 'w');
            $header = ['Culture code', 'Parent culture code', 'Content Type', 'Content code', 'Sub-project'];
            if ($selectedTable === 'culture_human_table') {
                $header = array_merge($header, ['Human code', 'Sample type', 'Sampling site']);
            }
            if ($selectedTable === 'culture_animal_table') {
                $header = array_merge($header, ['Animal code', 'Species', 'Sampling site']);
            }
            if ($selectedTable === 'culture_environment_table') {
                $header = array_merge($header, ['Environment type', 'Sampling site']);
            }
            if (in_array($selectedTable, ['culture_parasite_table', 'culture_parasite_human_table', 'culture_parasite_animal_table', 'culture_parasite_environment_table'], true)) {
                $header = array_merge($header, ['Parasite species', 'Origin sample code', 'Origin sampling site']);
            }
            if ($isPoolTable) {
                // Disaggregated pool export: one row per pool content item
                $header = array_merge($header, [
                    'Pool content type',
                    'Pool content code',
                    'Pool content sampling site',
                    'Pool content details',
                ]);
            }
            $header = array_merge($header, ['Type', 'Medium', 'Athmosphere', 'Incubation Temperature', 'Date cultured', 'Cultured by', 'Cultured at']);
            fputcsv($file, $header);

            foreach ($cultures as $culture) {
                $contentType = match ($culture->cultures_content_type) {
                    'App\Models\HumanSamples' => 'Human Sample',
                    'App\Models\AnimalSamples' => 'Animal Sample',
                    'App\Models\EnvironmentSamples' => 'Environment Sample',
                    'App\Models\ParasiteSamples' => 'Parasite Sample',
                    'App\Models\Pools' => 'Pools',
                };

                $row = [
                    $culture->code,
                    $culture->parent?->code ?? 'Primary culture',
                    $contentType,
                    data_get($culture, 'cultures_content.code') ?? 'N/A',
                    data_get($culture, 'subProjectAssignment.subProject.code') ?? 'N/A',
                ];

                if ($selectedTable === 'culture_human_table') {
                    $row[] = data_get($culture, 'cultures_content.humans.code') ?? 'N/A';
                    $row[] = data_get($culture, 'cultures_content.sample_types.name') ?? 'N/A';
                    $row[] = data_get($culture, 'cultures_content.sampling_sites.name') ?? 'N/A';
                }
                if ($selectedTable === 'culture_animal_table') {
                    $row[] = data_get($culture, 'cultures_content.animals.code') ?? 'N/A';
                    $common = (string) (data_get($culture, 'cultures_content.animals.animal_species.name_common') ?? '');
                    $scientific = (string) (data_get($culture, 'cultures_content.animals.animal_species.name_scientific') ?? '');
                    $row[] = $common && $scientific ? ($common.' ('.$scientific.')') : (trim($common) ?: (trim($scientific) ?: 'N/A'));
                    $row[] = data_get($culture, 'cultures_content.sampling_sites.name') ?? 'N/A';
                }
                if ($selectedTable === 'culture_environment_table') {
                    $row[] = data_get($culture, 'cultures_content.environment_sample_types.name') ?? 'N/A';
                    $row[] = data_get($culture, 'cultures_content.sampling_sites.name') ?? 'N/A';
                }
                if (in_array($selectedTable, ['culture_parasite_table', 'culture_parasite_human_table', 'culture_parasite_animal_table', 'culture_parasite_environment_table'], true)) {
                    $row[] = data_get($culture, 'cultures_content.parasites.parasite_species.name_scientific') ?? 'N/A';
                    $row[] = data_get($culture, 'cultures_content.parasites.parasites_origin.code') ?? 'N/A';
                    $row[] = data_get($culture, 'cultures_content.parasites.parasites_origin.sampling_sites.name') ?? 'N/A';
                }
                if (! $isPoolTable) {
                    $row = array_merge($row, [
                        $culture->type ?? 'N/A',
                        $culture->medium ?? 'N/A',
                        $culture->athmosphere ?? 'N/A',
                        $culture->incubation_temp ?? 'N/A',
                        $culture->date_cultured ?? 'N/A',
                        trim(($culture->people?->first_name ?? '').' '.($culture->people?->last_name ?? '')) ?: 'N/A',
                        $culture->laboratories?->name ?? 'N/A',
                    ]);

                    fputcsv($file, $row);

                    continue;
                }

                $poolContents = collect(data_get($culture, 'cultures_content.pool_contents') ?? [])
                    ->filter(fn ($pc) => data_get($pc, 'samples') !== null)
                    ->when($poolDerivedType, fn ($c) => $c->filter(fn ($pc) => (string) data_get($pc, 'samples_type') === $poolDerivedType))
                    ->values();

                if ($poolContents->isEmpty()) {
                    $poolRow = array_merge($row, ['N/A', 'N/A', 'N/A', 'N/A']);
                    $poolRow = array_merge($poolRow, [
                        $culture->type ?? 'N/A',
                        $culture->medium ?? 'N/A',
                        $culture->athmosphere ?? 'N/A',
                        $culture->incubation_temp ?? 'N/A',
                        $culture->date_cultured ?? 'N/A',
                        trim(($culture->people?->first_name ?? '').' '.($culture->people?->last_name ?? '')) ?: 'N/A',
                        $culture->laboratories?->name ?? 'N/A',
                    ]);
                    fputcsv($file, $poolRow);

                    continue;
                }

                foreach ($poolContents as $pc) {
                    $samplesType = (string) (data_get($pc, 'samples_type') ?? '');
                    $sample = data_get($pc, 'samples');
                    $sampleCode = (string) (data_get($sample, 'code') ?? 'N/A');

                    $samplingSite = match ($samplesType) {
                        HumanSamples::class,
                        AnimalSamples::class,
                        EnvironmentSamples::class => (string) (data_get($sample, 'sampling_sites.name') ?? 'N/A'),
                        ParasiteSamples::class => (string) (data_get($sample, 'parasites.parasites_origin.sampling_sites.name') ?? 'N/A'),
                        default => 'N/A',
                    };

                    $details = match ($samplesType) {
                        HumanSamples::class => 'Human: '.((string) (data_get($sample, 'humans.code') ?? 'N/A')).' • '.((string) (data_get($sample, 'sample_types.name') ?? 'N/A')),
                        AnimalSamples::class => 'Animal: '.((string) (data_get($sample, 'animals.code') ?? 'N/A')).' • '.((string) (data_get($sample, 'animals.animal_species.name_scientific') ?? 'N/A')),
                        EnvironmentSamples::class => 'Environment: '.((string) (data_get($sample, 'environment_sample_types.name') ?? 'N/A')),
                        ParasiteSamples::class => 'Parasite: '.((string) (data_get($sample, 'parasites.parasite_species.name_scientific') ?? 'N/A')).' • origin '.((string) (data_get($sample, 'parasites.parasites_origin.code') ?? 'N/A')),
                        default => 'N/A',
                    };

                    $poolRow = array_merge($row, [
                        match (class_basename($samplesType)) {
                            'HumanSamples' => 'Human sample',
                            'AnimalSamples' => 'Animal sample',
                            'EnvironmentSamples' => 'Environment sample',
                            'ParasiteSamples' => 'Parasite sample',
                            default => class_basename($samplesType) ?: 'N/A',
                        },
                        $sampleCode,
                        $samplingSite,
                        $details,
                    ]);

                    $poolRow = array_merge($poolRow, [
                        $culture->type ?? 'N/A',
                        $culture->medium ?? 'N/A',
                        $culture->athmosphere ?? 'N/A',
                        $culture->incubation_temp ?? 'N/A',
                        $culture->date_cultured ?? 'N/A',
                        trim(($culture->people?->first_name ?? '').' '.($culture->people?->last_name ?? '')) ?: 'N/A',
                        $culture->laboratories?->name ?? 'N/A',
                    ]);

                    fputcsv($file, $poolRow);
                }

            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $service = app(CulturesService::class);

        $query = Cultures::with(array_values(array_unique(array_merge([
            'cultures_content',
            'parent',
            'people',
            'laboratories',
            'tubes',
            'photos',
            'observations.photo',
            'observations.people',
            'latestObservation.photo',
            'latestPhoto',
            'projects',
            'subProjectAssignment.subProject',
        ], $this->selectedWith()))));

        if ($this->isPoolTable()) {
            $query->with([
                'cultures_content.pool_contents.samples' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        HumanSamples::class => ['humans', 'sample_types', 'sampling_sites'],
                        AnimalSamples::class => ['animals', 'animals.animal_species', 'sampling_sites'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                        ParasiteSamples::class => ['parasites.parasite_species', 'parasites.parasites_origin', 'parasites.parasites_origin.sampling_sites'],
                    ]);
                },
            ]);
        }

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public cultures
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            // In project mode, show samples from the selected project
            $query->where(function ($q) {
                $q->where('projects_id', $this->projectId)
                    ->orWhereHas('tubes', function ($tubeQuery) {
                        $tubeQuery->where('projects_id', $this->projectId);
                    });
            });
        }

        $query = $this->applySelectedTableScope($query);
        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $cultures = $query->paginate($this->perPage, pageName: 'articles-page');

        // Permission logic (copied from NucleicAcidsIndex)
        $project = null;
        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('cultures');

        $viewData = array_merge($service->assign(), [
            'cultures' => $cultures,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
            'canEditCulturedBy' => $this->canEditCulturedBy(),
        ]);

        return view('livewire.cultures-index', $viewData);
    }

    public $photo = [];

    public $uploadingPhoto = [];

    public $uploadError = [];

    public array $selectedCultures = [];

    public bool $selectAllFiltered = false;

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedCultures)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one culture.');

            return;
        }

        $cultures = Cultures::query()->whereIn('id', $selectedIds->all())->get();
        $deleted = 0;

        foreach ($cultures as $culture) {
            if (! $this->userCanMutateOwnedRecord((int) $culture->people_id, 'cultures')) {
                continue;
            }

            $culture->delete();
            $deleted++;
        }

        $this->selectedCultures = [];

        if ($deleted === 0) {
            session()->flash('error', 'You are not allowed to delete these culture(s) because you have not registered them.');

            return;
        }

        $skipped = $selectedIds->count() - $deleted;
        $message = "{$deleted} selected culture(s) deleted successfully.";
        if ($skipped > 0) {
            $message .= " {$skipped} culture(s) were not deleted because you have not registered them.";
        }

        session()->flash('success', $message);
    }

    public function uploadPhoto($cultureId)
    {
        if ($this->isGuestMode() || ! $this->userCanWriteModule('cultures')) {
            $this->uploadError[$cultureId] = 'You do not have permission to upload photos in this project.';

            return;
        }

        if (! isset($this->photo[$cultureId]) || ! $this->photo[$cultureId]) {
            $this->uploadError[$cultureId] = 'No file selected.';

            return;
        }
        $file = $this->photo[$cultureId];
        if ($file->getSize() > 52428800) {
            $this->uploadError[$cultureId] = 'File size exceeds 50MB limit.';
            $this->photo[$cultureId] = null;

            return;
        }
        $this->validate([
            'photo.'.$cultureId => 'required|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200',
        ], [
            'photo.'.$cultureId.'.mimes' => 'Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.',
        ]);
        $this->uploadingPhoto[$cultureId] = true;
        $this->uploadError[$cultureId] = null;
        try {
            $culture = Cultures::findOrFail($cultureId);
            if (! $this->canMutateCultureRecord((int) $culture->people_id)) {
                $this->uploadingPhoto[$cultureId] = false;
                $this->uploadError[$cultureId] = 'You can only upload photos to records you registered.';

                return;
            }

            $photoPath = $file->store('culture-photos', 'local');
            CultureObservationRecorder::createWithPhoto(
                culture: $culture,
                photoPath: $photoPath,
                observedAt: now()->toDateString(),
                notes: null,
                peopleId: ProjectPermission::currentRegistrarPeopleId(Auth::user()),
            );
            $culture->syncCoverPhotoPath();
            $this->photo[$cultureId] = null;
            $this->uploadingPhoto[$cultureId] = false;
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Success',
                'text' => 'Photo uploaded successfully!',
            ]);
        } catch (\Exception $e) {
            $this->uploadingPhoto[$cultureId] = false;
            $this->uploadError[$cultureId] = 'Failed to upload photo: '.$e->getMessage();
        }
    }

    public function deletePhoto($cultureId)
    {
        try {
            $culture = Cultures::findOrFail($cultureId);
            if (! $this->userCanMutateOwnedRecord((int) $culture->people_id, 'cultures')) {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Permission denied',
                    'text' => 'You can only delete photos from records you registered.',
                ]);

                return;
            }
            if ($culture->photo_path) {
                $path = (string) $culture->photo_path;
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
                Cultures::query()
                    ->where('photo_path', $path)
                    ->update(['photo_path' => null]);
                $this->dispatch('swal', [
                    'icon' => 'success',
                    'title' => 'Success',
                    'text' => 'Photo deleted successfully!',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Delete failed',
                'text' => 'Failed to delete photo: '.$e->getMessage(),
            ]);
        }
    }

    public function openPhotoPreview(int $cultureId): void
    {
        $culture = Cultures::query()->with(['observations.photo', 'observations.people', 'photos'])->find($cultureId);
        if (! $culture) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Not found',
                'text' => 'Culture not found.',
            ]);

            return;
        }

        CultureObservationRecorder::ensureLegacyPhotoRecord($culture);
        $culture->load(['observations.photo', 'observations.people']);

        $observations = $culture->observations
            ->filter(fn (CultureObservation $observation) => $observation->photo
                && $observation->photo->photo_path
                && Storage::disk('local')->exists($observation->photo->photo_path))
            ->values();

        if ($observations->isEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Missing file',
                'text' => 'No photos are available for this culture.',
            ]);

            return;
        }

        $this->photoPreviewPhotos = $observations->map(function (CultureObservation $observation) {
            $person = $observation->people;
            $observer = $person
                ? trim(($person->title ?? '').' '.($person->first_name ?? '').' '.($person->last_name ?? ''))
                : null;

            return [
                'id' => (int) $observation->id,
                'url' => Storage::url($observation->photo->photo_path),
                'observed_at' => $observation->observed_at?->format('Y-m-d'),
                'notes' => $observation->notes,
                'observer' => $observer,
            ];
        })->all();

        $this->photoPreviewIndex = 0;
        $this->photoPreviewCultureId = (int) $culture->id;
        $this->photoPreviewUrl = $this->photoPreviewPhotos[0]['url'] ?? null;
        $this->photoPreviewCode = (string) ($culture->code ?? '');
        $this->photoPreviewCanDelete = ! $this->isGuestMode()
            && $this->userCanMutateOwnedRecord((int) $culture->people_id, 'cultures');
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
        $this->photoPreviewCultureId = null;
        $this->photoPreviewUrl = null;
        $this->photoPreviewCode = null;
        $this->photoPreviewCanDelete = false;
        $this->photoPreviewPhotos = [];
        $this->photoPreviewIndex = 0;
    }

    public function deletePreviewPhoto(): void
    {
        if (! $this->photoPreviewCultureId) {
            return;
        }

        $culture = Cultures::query()->find($this->photoPreviewCultureId);
        if (! $culture) {
            $this->closePhotoPreview();

            return;
        }

        if (! $this->userCanMutateOwnedRecord((int) $culture->people_id, 'cultures')) {
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
            $observation = CultureObservation::query()->with('photo')->find($observationId);
            $photoPath = $observation?->photo?->photo_path;
            if ($photoPath && Storage::disk('local')->exists($photoPath)) {
                Storage::disk('local')->delete($photoPath);
            }
            $observation?->delete();
            $culture->syncCoverPhotoPath();
        } else {
            $path = (string) ($culture->photo_path ?? '');
            if ($path !== '' && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
            $culture->update(['photo_path' => null]);
        }

        $this->closePhotoPreview();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Photo deleted successfully.',
        ]);
    }

    public function clearBrokenPhotoPath(int $cultureId): void
    {
        $culture = Cultures::query()->find($cultureId);
        if (! $culture) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Not found',
                'text' => 'Culture not found.',
            ]);

            return;
        }

        if (! $this->userCanMutateOwnedRecord((int) $culture->people_id, 'cultures')) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Permission denied',
                'text' => 'You can only clear paths on records you registered.',
            ]);

            return;
        }

        $culture->update(['photo_path' => null]);
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Broken photo path cleared for this culture.',
        ]);
    }
}
