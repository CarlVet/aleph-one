<?php

namespace App\Livewire;

use App\Http\Controllers\NotificationController;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\MetaAnimal;
use App\Models\MetaEnvironment;
use App\Models\MetaHuman;
use App\Models\MetaParasite;
use App\Models\Microplastics;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\PublicationReviewRequest;
use App\Models\Sequences;
use App\Models\Tubes;
use App\Support\AdminAccess;
use App\Support\PublicationReviewRegistry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class PublishData extends PlainComponent
{
    use WithPagination;

    public string $dataType = '';

    public string $literatureType = 'animal';

    public bool $selectAll = false;

    /** @var array<int, int> */
    public array $selectedItems = [];

    /** @var array<int, int> */
    public array $currentPageIds = [];

    public int $perPage = 50;

    public string $codeFilter = '';

    // Type-specific column filters
    public string $tubeContentTypeFilter = '';

    public string $tubeContentDetailsFilter = '';

    public string $tubePurposeFilter = '';

    public string $experimentProtocolFilter = '';

    public string $experimentPathogenFilter = '';

    public string $experimentDateTestedFilter = '';

    public string $experimentOutcomeFilter = '';

    public string $experimentContentDetailsFilter = '';

    public string $literatureSampleTypeFilter = '';

    public string $literatureSpeciesFilter = '';

    public string $literaturePathogenFilter = '';

    public string $literatureRefKeyFilter = '';

    public string $literatureCountryFilter = '';

    public string $submissionMessage = '';

    private function resetSubmissionFilters(): void
    {
        $this->codeFilter = '';
        $this->tubeContentTypeFilter = '';
        $this->tubeContentDetailsFilter = '';
        $this->tubePurposeFilter = '';
        $this->experimentProtocolFilter = '';
        $this->experimentPathogenFilter = '';
        $this->experimentDateTestedFilter = '';
        $this->experimentOutcomeFilter = '';
        $this->experimentContentDetailsFilter = '';
        $this->literatureSampleTypeFilter = '';
        $this->literatureSpeciesFilter = '';
        $this->literaturePathogenFilter = '';
        $this->literatureRefKeyFilter = '';
        $this->literatureCountryFilter = '';
        $this->selectAll = false;
        $this->resetPage();
    }

    public function updating(string $name): void
    {
        if (
            $name === 'dataType'
            || $name === 'literatureType'
            || $name === 'perPage'
            || str_ends_with($name, 'Filter')
        ) {
            $this->resetPage();
            $this->selectAll = false;
            $this->selectedItems = [];
        }
    }

    public function updatedSelectAll(): void
    {
        $this->toggleSelectAllCurrentPage();
    }

    public function updatedSelectedItems(): void
    {
        if ($this->currentPageIds === []) {
            $this->selectAll = false;

            return;
        }

        $this->selectAll = count(array_diff($this->currentPageIds, $this->selectedItems)) === 0;
    }

    public function updatedPerPage(): void
    {
        $allowed = [10, 50, 100, 200, 500, 1000];
        if (! in_array($this->perPage, $allowed, true)) {
            $this->perPage = 50;
        }
    }

    private function toggleSelectAllCurrentPage(): void
    {
        if (! $this->userCanEditSelectedProject()) {
            $this->selectAll = false;

            return;
        }

        if ($this->selectAll) {
            $this->selectedItems = array_values(array_unique(array_merge($this->selectedItems, $this->currentPageIds)));
        } else {
            $this->selectedItems = array_values(array_diff($this->selectedItems, $this->currentPageIds));
        }
    }

    public function submitSelectedForReview(): void
    {
        if (! $this->userCanEditSelectedProject()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Review request not submitted',
                'text' => 'You do not have permission to submit data for publication review.',
            ]);

            return;
        }

        if (empty($this->selectedItems)) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No data selected',
                'text' => 'Please select at least one item to submit for review.',
            ]);

            return;
        }

        $projectId = (int) session('selected_project_id');
        if (! $projectId) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Project mode required',
                'text' => 'Please select a project before submitting data for review.',
            ]);

            return;
        }

        $modelClass = PublicationReviewRegistry::modelClass($this->dataType, $this->activeLiteratureType());
        if ($modelClass === null) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Data type missing',
                'text' => 'Please choose a valid data type.',
            ]);

            return;
        }

        $selectedIds = array_values(array_unique(array_map('intval', $this->selectedItems)));
        $pendingIds = $this->pendingReviewItemIds();
        $selectedIds = array_values(array_diff($selectedIds, $pendingIds));

        if ($selectedIds === []) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Already pending review',
                'text' => 'The selected items are already pending review by the data reviewers.',
            ]);

            return;
        }

        $records = $this->itemsQuery($projectId)
            ->whereIn('id', $selectedIds)
            ->get();

        if ($records->isEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Nothing to submit',
                'text' => 'No eligible private items were found for submission.',
            ]);

            return;
        }

        $user = Auth::user();
        if (! $user) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'User not available',
                'text' => 'Unable to identify the current user.',
            ]);

            return;
        }

        $reviewRequest = DB::transaction(function () use ($projectId, $user, $records): PublicationReviewRequest {
            $reviewRequest = PublicationReviewRequest::query()->create([
                'projects_id' => $projectId,
                'requester_user_id' => $user->id,
                'data_type' => $this->dataType,
                'literature_type' => $this->activeLiteratureType(),
                'status' => 'pending',
                'requester_message' => trim($this->submissionMessage) !== '' ? trim($this->submissionMessage) : null,
                'submitted_at' => now(),
            ]);

            $reviewRequest->items()->createMany(PublicationReviewRegistry::snapshots($records));

            return $reviewRequest;
        });

        $adminUsers = AdminAccess::adminsForProject($projectId)
            ->reject(fn ($adminUser) => (int) $adminUser->id === (int) $user->id)
            ->values();

        if ($adminUsers->isNotEmpty()) {
            $requesterLabel = $user->people?->name ?: $user->email;
            NotificationController::createForUsers(
                $adminUsers,
                'publication_review_submitted',
                'New publication review request',
                $requesterLabel.' submitted '.number_format($records->count()).' '.PublicationReviewRegistry::label($this->dataType, $this->activeLiteratureType()).' item(s) for publication review.',
                route('admin.publication-reviews.show', $reviewRequest),
                $projectId
            );
        }

        $this->selectAll = false;
        $this->selectedItems = [];
        $this->submissionMessage = '';
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Review request submitted',
            'text' => 'Your publication request was submitted to the data reviewers.',
        ]);
    }

    public function getDataTypeInfo()
    {
        switch ($this->dataType) {
            case 'tubes':
                return [
                    'title' => 'Tubes',
                    'description' => 'Storage tubes containing various sample types including animal samples, environmental samples, parasite samples, cultures, pools, and nucleic acids.',
                    'icon' => 'fas fa-vial',
                    'color' => 'blue',
                ];
            case 'experiments':
                return [
                    'title' => 'Experiments',
                    'description' => 'Laboratory experiments and tests performed on samples, including protocols, pathogens, and outcomes.',
                    'icon' => 'fas fa-flask',
                    'color' => 'purple',
                ];
            case 'literature':
                return [
                    'title' => 'Literature',
                    'description' => 'Published research data including animal, human, environmental, and parasite literature with associated studies and findings.',
                    'icon' => 'fas fa-book',
                    'color' => 'emerald',
                ];
            case 'sequences':
                return [
                    'title' => 'Sequences',
                    'description' => 'Nucleic acid sequencing records and accession metadata ready to publish.',
                    'icon' => 'fas fa-dna',
                    'color' => 'indigo',
                ];
            case 'microplastics':
                return [
                    'title' => 'Microplastics',
                    'description' => 'Microplastics identification records ready to publish publicly.',
                    'icon' => 'fas fa-recycle',
                    'color' => 'teal',
                ];
            default:
                return [
                    'title' => 'Select Data Type',
                    'description' => 'Choose the type of data you want to publish.',
                    'icon' => 'fas fa-database',
                    'color' => 'gray',
                ];
        }
    }

    public function render()
    {
        $projectId = (int) session('selected_project_id');
        $recentReviewRequests = collect();

        $items = null;
        if ($projectId && $this->dataType !== '') {
            $items = $this->itemsQuery($projectId)->paginate($this->perPage);
            $this->currentPageIds = $items->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        } else {
            $this->currentPageIds = [];
        }

        if ($projectId && Auth::check()) {
            $recentReviewRequests = PublicationReviewRequest::query()
                ->where('projects_id', $projectId)
                ->where('requester_user_id', Auth::id())
                ->withCount('items')
                ->latest('submitted_at')
                ->take(8)
                ->get();
        }

        return view('livewire.publish-data', [
            'dataTypeInfo' => $this->getDataTypeInfo(),
            'items' => $items,
            'recentReviewRequests' => $recentReviewRequests,
        ]);
    }

    public function getCanPublishProperty(): bool
    {
        return $this->userCanEditSelectedProject();
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selectedItems);
    }

    public function loadRequestForResubmission(int $reviewRequestId): void
    {
        $projectId = (int) session('selected_project_id');
        $userId = Auth::id();

        if (! $projectId || ! $userId) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Project mode required',
                'text' => 'Please select a project before resubmitting data.',
            ]);

            return;
        }

        $reviewRequest = PublicationReviewRequest::query()
            ->with('items')
            ->where('projects_id', $projectId)
            ->where('requester_user_id', $userId)
            ->whereKey($reviewRequestId)
            ->first();

        if (! $reviewRequest || ! in_array($reviewRequest->status, ['changes_requested', 'rejected'], true)) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Resubmission unavailable',
                'text' => 'This publication request cannot be loaded for resubmission.',
            ]);

            return;
        }

        $this->dataType = $reviewRequest->data_type;
        if ($reviewRequest->data_type === 'literature' && $reviewRequest->literature_type) {
            $this->literatureType = $reviewRequest->literature_type;
        }

        $this->resetSubmissionFilters();

        $itemIds = $reviewRequest->items
            ->pluck('reviewable_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $eligibleIds = $this->itemsQuery($projectId)
            ->whereIn('id', $itemIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($eligibleIds === []) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No eligible items',
                'text' => 'None of the records from that request are currently eligible for resubmission.',
            ]);

            return;
        }

        $this->selectedItems = $eligibleIds;
        $this->submissionMessage = '';

        $suffix = count($eligibleIds) !== count($itemIds)
            ? ' Only the records that are still private and not already pending review were loaded.'
            : '';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Resubmission loaded',
            'text' => 'The requested records were loaded so you can resubmit them after updating the data.'.$suffix,
        ]);
    }

    private function activeLiteratureType(): ?string
    {
        return $this->dataType === 'literature' ? $this->literatureType : null;
    }

    /**
     * @return array<int, int>
     */
    private function pendingReviewItemIds(): array
    {
        $projectId = (int) session('selected_project_id');
        if (! $projectId || $this->dataType === '') {
            return [];
        }

        return PublicationReviewRegistry::pendingIds($projectId, $this->dataType, $this->activeLiteratureType());
    }

    private function excludePendingReviewItems(Builder $query, string $dataType, ?string $literatureType = null): Builder
    {
        $projectId = (int) session('selected_project_id');
        if (! $projectId) {
            return $query;
        }

        $pendingIds = PublicationReviewRegistry::pendingIds($projectId, $dataType, $literatureType);
        if ($pendingIds === []) {
            return $query;
        }

        return $query->whereNotIn('id', $pendingIds);
    }

    private function itemsQuery(int $projectId): Builder
    {
        return match ($this->dataType) {
            'tubes' => $this->tubesQuery($projectId),
            'experiments' => $this->experimentsQuery($projectId),
            'literature' => $this->literatureQuery($projectId),
            'sequences' => $this->sequencesQuery($projectId),
            'microplastics' => $this->microplasticsQuery($projectId),
            default => Tubes::query()->whereRaw('1=0'),
        };
    }

    private function microplasticsQuery(int $projectId): Builder
    {
        return $this->excludePendingReviewItems(Microplastics::query()
            ->where('projects_id', $projectId)
            ->where('is_private', true)
            ->when($this->codeFilter !== '', fn (Builder $q) => $q->where('code', 'like', '%'.$this->codeFilter.'%'))
            ->with(['mps_types', 'protocols', 'people']), 'microplastics');
    }

    private function sequencesQuery(int $projectId): Builder
    {
        return $this->excludePendingReviewItems(Sequences::query()
            ->where('projects_id', $projectId)
            ->where('is_private', true)
            ->when($this->codeFilter !== '', fn (Builder $q) => $q->where('code', 'like', '%'.$this->codeFilter.'%'))
            ->with(['nucleic_acids', 'people', 'laboratories']), 'sequences');
    }

    private function tubesQuery(int $projectId): Builder
    {
        return $this->excludePendingReviewItems(Tubes::query()
            ->where('projects_id', $projectId)
            ->where('is_private', true)
            ->when($this->codeFilter !== '', fn (Builder $q) => $q->where('code', 'like', '%'.$this->codeFilter.'%'))
            ->when($this->tubePurposeFilter !== '', fn (Builder $q) => $q->where('purpose', 'like', '%'.$this->tubePurposeFilter.'%'))
            ->when($this->tubeContentTypeFilter !== '', function (Builder $q) {
                $needle = trim($this->tubeContentTypeFilter);
                if ($needle !== '') {
                    $q->where('tubes_content_type', 'like', '%'.$needle.'%');
                }
            })
            ->when($this->tubeContentDetailsFilter !== '', function (Builder $q): void {
                $needle = trim($this->tubeContentDetailsFilter);
                if ($needle === '') {
                    return;
                }

                $q->whereHasMorph(
                    'tubes_content',
                    [
                        AnimalSamples::class,
                        HumanSamples::class,
                        ParasiteSamples::class,
                        EnvironmentSamples::class,
                        NucleicAcids::class,
                        Cultures::class,
                        Pools::class,
                    ],
                    function (Builder $contentQuery, string $type) use ($needle): void {
                        if ($type === AnimalSamples::class) {
                            $contentQuery->where(function (Builder $animalContentQuery) use ($needle): void {
                                $animalContentQuery
                                    ->whereHas('animals', function (Builder $animalsQuery) use ($needle): void {
                                        $animalsQuery
                                            ->where('sex', 'like', '%'.$needle.'%')
                                            ->orWhere('age', 'like', '%'.$needle.'%')
                                            ->orWhereHas('animal_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_common', 'like', '%'.$needle.'%'));
                                    })
                                    ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                            });

                            return;
                        }

                        if ($type === HumanSamples::class) {
                            $contentQuery->where(function (Builder $humanContentQuery) use ($needle): void {
                                $humanContentQuery
                                    ->whereHas('humans', function (Builder $humansQuery) use ($needle): void {
                                        $humansQuery
                                            ->where('occupation', 'like', '%'.$needle.'%')
                                            ->orWhere('sex', 'like', '%'.$needle.'%')
                                            ->orWhereHas('countries', fn (Builder $countryQuery) => $countryQuery->where('name', 'like', '%'.$needle.'%'));
                                    })
                                    ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                            });

                            return;
                        }

                        if ($type === ParasiteSamples::class) {
                            $contentQuery->whereHas('parasites', function (Builder $parasitesQuery) use ($needle): void {
                                $parasitesQuery
                                    ->where('sex', 'like', '%'.$needle.'%')
                                    ->orWhere('stage', 'like', '%'.$needle.'%')
                                    ->orWhere('state', 'like', '%'.$needle.'%')
                                    ->orWhereHas('parasite_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_scientific', 'like', '%'.$needle.'%'));
                            });

                            return;
                        }

                        if ($type === EnvironmentSamples::class) {
                            $contentQuery->where(function (Builder $environmentContentQuery) use ($needle): void {
                                $environmentContentQuery
                                    ->where('area', 'like', '%'.$needle.'%')
                                    ->orWhereHas('environment_sample_types', fn (Builder $sampleTypeQuery) => $sampleTypeQuery->where('name', 'like', '%'.$needle.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                            });

                            return;
                        }

                        if ($type === NucleicAcids::class) {
                            $contentQuery
                                ->where('type', 'like', '%'.$needle.'%')
                                ->orWhere('code', 'like', '%'.$needle.'%')
                                ->orWhereHasMorph('nucleic_content', [HumanSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery
                                        ->whereHas('humans', fn (Builder $humansQuery) => $humansQuery->where('occupation', 'like', '%'.$needle.'%')->orWhere('sex', 'like', '%'.$needle.'%')->orWhereHas('countries', fn (Builder $countryQuery) => $countryQuery->where('name', 'like', '%'.$needle.'%')))
                                        ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                                })
                                ->orWhereHasMorph('nucleic_content', [AnimalSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery
                                        ->whereHas('animals', fn (Builder $animalsQuery) => $animalsQuery->where('sex', 'like', '%'.$needle.'%')->orWhere('age', 'like', '%'.$needle.'%')->orWhereHas('animal_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_common', 'like', '%'.$needle.'%')))
                                        ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                                })
                                ->orWhereHasMorph('nucleic_content', [EnvironmentSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery
                                        ->where('area', 'like', '%'.$needle.'%')
                                        ->orWhereHas('environment_sample_types', fn (Builder $sampleTypeQuery) => $sampleTypeQuery->where('name', 'like', '%'.$needle.'%'))
                                        ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                                })
                                ->orWhereHasMorph('nucleic_content', [ParasiteSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery->whereHas('parasites', fn (Builder $parasitesQuery) => $parasitesQuery->where('sex', 'like', '%'.$needle.'%')->orWhere('stage', 'like', '%'.$needle.'%')->orWhere('state', 'like', '%'.$needle.'%')->orWhereHas('parasite_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_scientific', 'like', '%'.$needle.'%')));
                                })
                                ->orWhereHasMorph('nucleic_content', [Cultures::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery->where('medium', 'like', '%'.$needle.'%')
                                        ->orWhere('type', 'like', '%'.$needle.'%')
                                        ->orWhere('step', 'like', '%'.$needle.'%');
                                })
                                ->orWhereHasMorph('nucleic_content', [Pools::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery->where('nr_pooled', 'like', '%'.$needle.'%');
                                });

                            return;
                        }

                        if ($type === Cultures::class) {
                            $contentQuery->where(function (Builder $cultureContentQuery) use ($needle): void {
                                $cultureContentQuery
                                    ->where('medium', 'like', '%'.$needle.'%')
                                    ->orWhere('type', 'like', '%'.$needle.'%')
                                    ->orWhere('step', 'like', '%'.$needle.'%');
                            });

                            return;
                        }

                        if ($type === Pools::class) {
                            $contentQuery->where('nr_pooled', 'like', '%'.$needle.'%');
                        }
                    }
                );
            })
            ->with([
                'tubes_content' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        AnimalSamples::class => ['animals.animal_species', 'sampling_sites'],
                        HumanSamples::class => ['humans.countries', 'sampling_sites'],
                        ParasiteSamples::class => ['parasites.parasite_species'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                        NucleicAcids::class => [
                            'nucleic_content' => function (MorphTo $nucleicContentMorphTo): void {
                                $nucleicContentMorphTo->morphWith([
                                    HumanSamples::class => ['humans.countries', 'sampling_sites'],
                                    AnimalSamples::class => ['animals.animal_species', 'sampling_sites'],
                                    EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                                    ParasiteSamples::class => ['parasites.parasite_species'],
                                    Cultures::class => ['cultures_content'],
                                    Pools::class => [],
                                ]);
                            },
                        ],
                        Cultures::class => ['cultures_content'],
                        Pools::class => [],
                    ]);
                },
            ]), 'tubes');
    }

    private function experimentsQuery(int $projectId): Builder
    {
        return $this->excludePendingReviewItems(Experiments::query()
            ->where('projects_id', $projectId)
            ->where('is_private', true)
            ->when($this->codeFilter !== '', fn (Builder $q) => $q->where('code', 'like', '%'.$this->codeFilter.'%'))
            ->when($this->experimentProtocolFilter !== '', function (Builder $q) {
                $q->whereHas('protocols', fn (Builder $p) => $p->where('name', 'like', '%'.$this->experimentProtocolFilter.'%'));
            })
            ->when($this->experimentPathogenFilter !== '', function (Builder $q) {
                $needle = trim($this->experimentPathogenFilter);
                $q->whereHas('pathogens', function (Builder $p) use ($needle) {
                    $p->where('species', 'like', '%'.$needle.'%');
                });
            })
            ->when($this->experimentDateTestedFilter !== '', function (Builder $q) {
                $q->whereDate('date_tested', $this->experimentDateTestedFilter);
            })
            ->when($this->experimentOutcomeFilter !== '', function (Builder $q) {
                $needle = strtolower(trim($this->experimentOutcomeFilter));
                $q->where(function (Builder $outcomeQuery) use ($needle) {
                    if (in_array($needle, ['positive', 'pos', '+', '1', 'true', 'yes'], true)) {
                        $outcomeQuery->where('outcome_binary', true);

                        return;
                    }

                    if (in_array($needle, ['negative', 'neg', '-', '0', 'false', 'no'], true)) {
                        $outcomeQuery->where('outcome_binary', false);

                        return;
                    }

                    $outcomeQuery
                        ->where('outcome_discrete', 'like', '%'.$this->experimentOutcomeFilter.'%')
                        ->orWhere('outcome_quant', 'like', '%'.$this->experimentOutcomeFilter.'%');
                });
            })
            ->when($this->experimentContentDetailsFilter !== '', function (Builder $q): void {
                $needle = trim($this->experimentContentDetailsFilter);
                if ($needle === '') {
                    return;
                }

                $q->whereHasMorph(
                    'experiments_content',
                    [
                        AnimalSamples::class,
                        HumanSamples::class,
                        ParasiteSamples::class,
                        EnvironmentSamples::class,
                        NucleicAcids::class,
                        Cultures::class,
                        Pools::class,
                    ],
                    function (Builder $contentQuery, string $type) use ($needle): void {
                        if ($type === AnimalSamples::class) {
                            $contentQuery->where(function (Builder $animalContentQuery) use ($needle): void {
                                $animalContentQuery
                                    ->where('code', 'like', '%'.$needle.'%')
                                    ->orWhereHas('animals', fn (Builder $animalsQuery) => $animalsQuery->where('sex', 'like', '%'.$needle.'%')->orWhere('age', 'like', '%'.$needle.'%')->orWhereHas('animal_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_common', 'like', '%'.$needle.'%')))
                                    ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                            });

                            return;
                        }

                        if ($type === HumanSamples::class) {
                            $contentQuery->where(function (Builder $humanContentQuery) use ($needle): void {
                                $humanContentQuery
                                    ->where('code', 'like', '%'.$needle.'%')
                                    ->orWhereHas('humans', fn (Builder $humansQuery) => $humansQuery->where('occupation', 'like', '%'.$needle.'%')->orWhere('sex', 'like', '%'.$needle.'%')->orWhereHas('countries', fn (Builder $countryQuery) => $countryQuery->where('name', 'like', '%'.$needle.'%')))
                                    ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                            });

                            return;
                        }

                        if ($type === ParasiteSamples::class) {
                            $contentQuery->where(function (Builder $parasiteContentQuery) use ($needle): void {
                                $parasiteContentQuery
                                    ->where('code', 'like', '%'.$needle.'%')
                                    ->orWhereHas('parasites', fn (Builder $parasitesQuery) => $parasitesQuery->where('sex', 'like', '%'.$needle.'%')->orWhere('stage', 'like', '%'.$needle.'%')->orWhere('state', 'like', '%'.$needle.'%')->orWhereHas('parasite_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_scientific', 'like', '%'.$needle.'%')));
                            });

                            return;
                        }

                        if ($type === EnvironmentSamples::class) {
                            $contentQuery->where(function (Builder $environmentContentQuery) use ($needle): void {
                                $environmentContentQuery
                                    ->where('code', 'like', '%'.$needle.'%')
                                    ->orWhere('area', 'like', '%'.$needle.'%')
                                    ->orWhereHas('environment_sample_types', fn (Builder $sampleTypeQuery) => $sampleTypeQuery->where('name', 'like', '%'.$needle.'%'))
                                    ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                            });

                            return;
                        }

                        if ($type === NucleicAcids::class) {
                            $contentQuery
                                ->where('type', 'like', '%'.$needle.'%')
                                ->orWhere('code', 'like', '%'.$needle.'%')
                                ->orWhereHasMorph('nucleic_content', [HumanSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery
                                        ->whereHas('humans', fn (Builder $humansQuery) => $humansQuery->where('occupation', 'like', '%'.$needle.'%')->orWhere('sex', 'like', '%'.$needle.'%')->orWhereHas('countries', fn (Builder $countryQuery) => $countryQuery->where('name', 'like', '%'.$needle.'%')))
                                        ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                                })
                                ->orWhereHasMorph('nucleic_content', [AnimalSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery
                                        ->whereHas('animals', fn (Builder $animalsQuery) => $animalsQuery->where('sex', 'like', '%'.$needle.'%')->orWhere('age', 'like', '%'.$needle.'%')->orWhereHas('animal_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_common', 'like', '%'.$needle.'%')))
                                        ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                                })
                                ->orWhereHasMorph('nucleic_content', [EnvironmentSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery
                                        ->where('area', 'like', '%'.$needle.'%')
                                        ->orWhereHas('environment_sample_types', fn (Builder $sampleTypeQuery) => $sampleTypeQuery->where('name', 'like', '%'.$needle.'%'))
                                        ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$needle.'%'));
                                })
                                ->orWhereHasMorph('nucleic_content', [ParasiteSamples::class], function (Builder $originQuery) use ($needle): void {
                                    $originQuery->whereHas('parasites', fn (Builder $parasitesQuery) => $parasitesQuery->where('sex', 'like', '%'.$needle.'%')->orWhere('stage', 'like', '%'.$needle.'%')->orWhere('state', 'like', '%'.$needle.'%')->orWhereHas('parasite_species', fn (Builder $speciesQuery) => $speciesQuery->where('name_scientific', 'like', '%'.$needle.'%')));
                                })
                                ->orWhereHasMorph('nucleic_content', [Cultures::class], fn (Builder $originQuery) => $originQuery->where('medium', 'like', '%'.$needle.'%')->orWhere('type', 'like', '%'.$needle.'%')->orWhere('step', 'like', '%'.$needle.'%'))
                                ->orWhereHasMorph('nucleic_content', [Pools::class], fn (Builder $originQuery) => $originQuery->where('nr_pooled', 'like', '%'.$needle.'%'));

                            return;
                        }

                        if ($type === Cultures::class) {
                            $contentQuery->where(function (Builder $cultureContentQuery) use ($needle): void {
                                $cultureContentQuery
                                    ->where('code', 'like', '%'.$needle.'%')
                                    ->orWhere('medium', 'like', '%'.$needle.'%')
                                    ->orWhere('type', 'like', '%'.$needle.'%')
                                    ->orWhere('step', 'like', '%'.$needle.'%');
                            });

                            return;
                        }

                        if ($type === Pools::class) {
                            $contentQuery->where('code', 'like', '%'.$needle.'%')
                                ->orWhere('nr_pooled', 'like', '%'.$needle.'%');
                        }
                    }
                );
            })
            ->with([
                'protocols',
                'pathogens',
                'experiments_content' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        AnimalSamples::class => ['animals.animal_species', 'sampling_sites'],
                        HumanSamples::class => ['humans.countries', 'sampling_sites'],
                        ParasiteSamples::class => ['parasites.parasite_species'],
                        EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                        NucleicAcids::class => [
                            'nucleic_content' => function (MorphTo $nucleicContentMorphTo): void {
                                $nucleicContentMorphTo->morphWith([
                                    HumanSamples::class => ['humans.countries', 'sampling_sites'],
                                    AnimalSamples::class => ['animals.animal_species', 'sampling_sites'],
                                    EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites'],
                                    ParasiteSamples::class => ['parasites.parasite_species'],
                                    Cultures::class => ['cultures_content'],
                                    Pools::class => [],
                                ]);
                            },
                        ],
                        Cultures::class => ['cultures_content'],
                        Pools::class => [],
                    ]);
                },
            ]), 'experiments');
    }

    private function literatureQuery(int $projectId): Builder
    {
        return match ($this->literatureType) {
            'animal' => $this->excludePendingReviewItems(MetaAnimal::query()
                ->where('projects_id', $projectId)
                ->where('is_private', true)
                ->when($this->literatureRefKeyFilter !== '', fn (Builder $q) => $q->whereHas('studies', fn (Builder $s) => $s->where('ref_key', 'like', '%'.$this->literatureRefKeyFilter.'%')))
                ->when($this->literatureSampleTypeFilter !== '', fn (Builder $q) => $q->whereHas('sample_types', fn (Builder $s) => $s->where('name', 'like', '%'.$this->literatureSampleTypeFilter.'%')))
                ->when($this->literatureSpeciesFilter !== '', fn (Builder $q) => $q->whereHas('animal_species', fn (Builder $s) => $s->where('name_common', 'like', '%'.$this->literatureSpeciesFilter.'%')))
                ->when($this->literaturePathogenFilter !== '', fn (Builder $q) => $q->whereHas('pathogens', fn (Builder $p) => $p->where('species', 'like', '%'.$this->literaturePathogenFilter.'%')))
                ->when($this->literatureCountryFilter !== '', fn (Builder $q) => $q->whereHas('countries', fn (Builder $c) => $c->where('name', 'like', '%'.$this->literatureCountryFilter.'%')))
                ->with(['animal_species', 'sample_types', 'pathogens', 'studies', 'countries']), 'literature', 'animal'),
            'human' => $this->excludePendingReviewItems(MetaHuman::query()
                ->where('projects_id', $projectId)
                ->where('is_private', true)
                ->when($this->literatureRefKeyFilter !== '', fn (Builder $q) => $q->whereHas('studies', fn (Builder $s) => $s->where('ref_key', 'like', '%'.$this->literatureRefKeyFilter.'%')))
                ->when($this->literatureSampleTypeFilter !== '', fn (Builder $q) => $q->whereHas('sample_types', fn (Builder $s) => $s->where('name', 'like', '%'.$this->literatureSampleTypeFilter.'%')))
                ->when($this->literaturePathogenFilter !== '', fn (Builder $q) => $q->whereHas('pathogens', fn (Builder $p) => $p->where('species', 'like', '%'.$this->literaturePathogenFilter.'%')))
                ->when($this->literatureCountryFilter !== '', fn (Builder $q) => $q->whereHas('countries', fn (Builder $c) => $c->where('name', 'like', '%'.$this->literatureCountryFilter.'%')))
                ->with(['sample_types', 'pathogens', 'studies', 'countries']), 'literature', 'human'),
            'environment' => $this->excludePendingReviewItems(MetaEnvironment::query()
                ->where('projects_id', $projectId)
                ->where('is_private', true)
                ->when($this->literatureRefKeyFilter !== '', fn (Builder $q) => $q->whereHas('studies', fn (Builder $s) => $s->where('ref_key', 'like', '%'.$this->literatureRefKeyFilter.'%')))
                ->when($this->literatureSampleTypeFilter !== '', fn (Builder $q) => $q->whereHas('environment_sample_types', fn (Builder $s) => $s->where('name', 'like', '%'.$this->literatureSampleTypeFilter.'%')))
                ->when($this->literaturePathogenFilter !== '', fn (Builder $q) => $q->whereHas('pathogens', fn (Builder $p) => $p->where('species', 'like', '%'.$this->literaturePathogenFilter.'%')))
                ->when($this->literatureCountryFilter !== '', fn (Builder $q) => $q->whereHas('countries', fn (Builder $c) => $c->where('name', 'like', '%'.$this->literatureCountryFilter.'%')))
                ->with(['environment_sample_types', 'pathogens', 'studies', 'countries']), 'literature', 'environment'),
            'parasite' => $this->excludePendingReviewItems(MetaParasite::query()
                ->where('projects_id', $projectId)
                ->where('is_private', true)
                ->when($this->literatureRefKeyFilter !== '', fn (Builder $q) => $q->whereHas('studies', fn (Builder $s) => $s->where('ref_key', 'like', '%'.$this->literatureRefKeyFilter.'%')))
                ->when($this->literatureSampleTypeFilter !== '', fn (Builder $q) => $q->whereHas('parasite_sample_types', fn (Builder $s) => $s->where('name', 'like', '%'.$this->literatureSampleTypeFilter.'%')))
                ->when($this->literatureSpeciesFilter !== '', fn (Builder $q) => $q->whereHas('parasite_species', fn (Builder $s) => $s->where('name_scientific', 'like', '%'.$this->literatureSpeciesFilter.'%')))
                ->when($this->literaturePathogenFilter !== '', fn (Builder $q) => $q->whereHas('pathogens', fn (Builder $p) => $p->where('species', 'like', '%'.$this->literaturePathogenFilter.'%')))
                ->when($this->literatureCountryFilter !== '', fn (Builder $q) => $q->whereHas('countries', fn (Builder $c) => $c->where('name', 'like', '%'.$this->literatureCountryFilter.'%')))
                ->with(['parasite_species', 'parasite_sample_types', 'pathogens', 'studies', 'countries']), 'literature', 'parasite'),
            default => MetaAnimal::query()->whereRaw('1=0'),
        };
    }

    /**
     * @return array{columns: array<int, array{label: string, value: string}>}
     */
    public function experimentContentDetails(Experiments $experiment): array
    {
        $content = $experiment->experiments_content;
        $contentType = (string) $experiment->experiments_content_type;

        if (! $content) {
            return ['columns' => [['label' => 'Details', 'value' => 'N/A']]];
        }

        if ($contentType === AnimalSamples::class) {
            return ['columns' => [
                ['label' => 'Type', 'value' => 'Animal sample'],
                ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
                ['label' => 'Species', 'value' => (string) (data_get($content, 'animals.animal_species.name_common') ?? 'N/A')],
                ['label' => 'Sex', 'value' => (string) (data_get($content, 'animals.sex') ?? 'N/A')],
                ['label' => 'Age', 'value' => (string) (data_get($content, 'animals.age') ?? 'N/A')],
                ['label' => 'Sampling site', 'value' => (string) (data_get($content, 'sampling_sites.name') ?? 'N/A')],
            ]];
        }

        if ($contentType === HumanSamples::class) {
            return ['columns' => [
                ['label' => 'Type', 'value' => 'Human sample'],
                ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
                ['label' => 'Occupation', 'value' => (string) (data_get($content, 'humans.occupation') ?? 'N/A')],
                ['label' => 'Country', 'value' => (string) (data_get($content, 'humans.countries.name') ?? 'N/A')],
                ['label' => 'Sex', 'value' => (string) (data_get($content, 'humans.sex') ?? 'N/A')],
                ['label' => 'Age', 'value' => $this->humanAge((string) data_get($content, 'humans.date_of_birth'))],
                ['label' => 'Sampling site', 'value' => (string) (data_get($content, 'sampling_sites.name') ?? 'N/A')],
            ]];
        }

        if ($contentType === ParasiteSamples::class) {
            return ['columns' => [
                ['label' => 'Type', 'value' => 'Parasite sample'],
                ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
                ['label' => 'Species', 'value' => (string) (data_get($content, 'parasites.parasite_species.name_scientific') ?? 'N/A')],
                ['label' => 'Sex', 'value' => (string) (data_get($content, 'parasites.sex') ?? 'N/A')],
                ['label' => 'Stage', 'value' => (string) (data_get($content, 'parasites.stage') ?? 'N/A')],
                ['label' => 'State', 'value' => (string) (data_get($content, 'parasites.state') ?? 'N/A')],
            ]];
        }

        if ($contentType === EnvironmentSamples::class) {
            return ['columns' => [
                ['label' => 'Type', 'value' => 'Environment sample'],
                ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
                ['label' => 'Sample type', 'value' => (string) (data_get($content, 'environment_sample_types.name') ?? 'N/A')],
                ['label' => 'Sampling site', 'value' => (string) (data_get($content, 'sampling_sites.name') ?? 'N/A')],
                ['label' => 'Area', 'value' => (string) (data_get($content, 'area') ?? 'N/A')],
                ['label' => 'Date collected', 'value' => $this->formatDateYmd(data_get($content, 'date_collected'))],
            ]];
        }

        if ($contentType === NucleicAcids::class) {
            $originType = (string) (data_get($content, 'nucleic_content_type') ?? '');
            $columns = [
                ['label' => 'Type', 'value' => 'Nucleic acid'],
                ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
                ['label' => 'Nucleic type', 'value' => (string) (data_get($content, 'type') ?? 'N/A')],
                ['label' => 'Date extracted', 'value' => $this->formatDateYmd(data_get($content, 'date_extracted'))],
                ['label' => 'Origin type', 'value' => class_basename($originType) ?: 'N/A'],
                ['label' => 'Origin code', 'value' => (string) (data_get($content, 'nucleic_content.code') ?? 'N/A')],
            ];

            if ($originType === HumanSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin occupation', 'value' => (string) (data_get($content, 'nucleic_content.humans.occupation') ?? 'N/A')],
                    ['label' => 'Origin country', 'value' => (string) (data_get($content, 'nucleic_content.humans.countries.name') ?? 'N/A')],
                    ['label' => 'Origin sex', 'value' => (string) (data_get($content, 'nucleic_content.humans.sex') ?? 'N/A')],
                    ['label' => 'Origin age', 'value' => $this->humanAge((string) data_get($content, 'nucleic_content.humans.date_of_birth'))],
                    ['label' => 'Origin site', 'value' => (string) (data_get($content, 'nucleic_content.sampling_sites.name') ?? 'N/A')],
                ]);
            } elseif ($originType === AnimalSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin species', 'value' => (string) (data_get($content, 'nucleic_content.animals.animal_species.name_common') ?? 'N/A')],
                    ['label' => 'Origin sex', 'value' => (string) (data_get($content, 'nucleic_content.animals.sex') ?? 'N/A')],
                    ['label' => 'Origin age', 'value' => (string) (data_get($content, 'nucleic_content.animals.age') ?? 'N/A')],
                    ['label' => 'Origin site', 'value' => (string) (data_get($content, 'nucleic_content.sampling_sites.name') ?? 'N/A')],
                ]);
            } elseif ($originType === EnvironmentSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin sample type', 'value' => (string) (data_get($content, 'nucleic_content.environment_sample_types.name') ?? 'N/A')],
                    ['label' => 'Origin site', 'value' => (string) (data_get($content, 'nucleic_content.sampling_sites.name') ?? 'N/A')],
                    ['label' => 'Origin area', 'value' => (string) (data_get($content, 'nucleic_content.area') ?? 'N/A')],
                    ['label' => 'Origin date', 'value' => $this->formatDateYmd(data_get($content, 'nucleic_content.date_collected'))],
                ]);
            } elseif ($originType === ParasiteSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin species', 'value' => (string) (data_get($content, 'nucleic_content.parasites.parasite_species.name_scientific') ?? 'N/A')],
                    ['label' => 'Origin sex', 'value' => (string) (data_get($content, 'nucleic_content.parasites.sex') ?? 'N/A')],
                    ['label' => 'Origin stage', 'value' => (string) (data_get($content, 'nucleic_content.parasites.stage') ?? 'N/A')],
                    ['label' => 'Origin state', 'value' => (string) (data_get($content, 'nucleic_content.parasites.state') ?? 'N/A')],
                ]);
            } elseif ($originType === Cultures::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin medium', 'value' => (string) (data_get($content, 'nucleic_content.medium') ?? 'N/A')],
                    ['label' => 'Origin culture type', 'value' => (string) (data_get($content, 'nucleic_content.type') ?? 'N/A')],
                    ['label' => 'Origin culture date', 'value' => $this->formatDateYmd(data_get($content, 'nucleic_content.date_cultured'))],
                ]);
            } elseif ($originType === Pools::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin nr pooled', 'value' => (string) (data_get($content, 'nucleic_content.nr_pooled') ?? 'N/A')],
                    ['label' => 'Origin date pooled', 'value' => $this->formatDateYmd(data_get($content, 'nucleic_content.date_pooled'))],
                ]);
            }

            return ['columns' => $columns];
        }

        if ($contentType === Cultures::class) {
            return ['columns' => [
                ['label' => 'Type', 'value' => 'Culture'],
                ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
                ['label' => 'Medium', 'value' => (string) (data_get($content, 'medium') ?? 'N/A')],
                ['label' => 'Culture type', 'value' => (string) (data_get($content, 'type') ?? 'N/A')],
                ['label' => 'Step', 'value' => (string) (data_get($content, 'step') ?? 'N/A')],
                ['label' => 'Date cultured', 'value' => $this->formatDateYmd(data_get($content, 'date_cultured'))],
            ]];
        }

        if ($contentType === Pools::class) {
            return ['columns' => [
                ['label' => 'Type', 'value' => 'Pool'],
                ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
                ['label' => 'Nr pooled', 'value' => (string) (data_get($content, 'nr_pooled') ?? 'N/A')],
                ['label' => 'Date pooled', 'value' => $this->formatDateYmd(data_get($content, 'date_pooled'))],
            ]];
        }

        return ['columns' => [
            ['label' => 'Type', 'value' => class_basename($contentType) ?: 'Other'],
            ['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')],
        ]];
    }

    /**
     * @return array{type: string, columns: array<int, array{label: string, value: string}>}
     */
    public function tubeContentDetails(Tubes $tube): array
    {
        $contentType = (string) $tube->tubes_content_type;
        $content = $tube->tubes_content;

        if (! $content) {
            return [
                'type' => 'Unknown',
                'columns' => [['label' => 'Details', 'value' => 'N/A']],
            ];
        }

        if ($contentType === AnimalSamples::class) {
            return [
                'type' => 'Animal sample',
                'columns' => [
                    ['label' => 'Species', 'value' => (string) (data_get($content, 'animals.animal_species.name_common') ?? 'N/A')],
                    ['label' => 'Sex', 'value' => (string) (data_get($content, 'animals.sex') ?? 'N/A')],
                    ['label' => 'Age', 'value' => (string) (data_get($content, 'animals.age') ?? 'N/A')],
                    ['label' => 'Sampling site', 'value' => (string) (data_get($content, 'sampling_sites.name') ?? 'N/A')],
                ],
            ];
        }

        if ($contentType === HumanSamples::class) {
            return [
                'type' => 'Human sample',
                'columns' => [
                    ['label' => 'Occupation', 'value' => (string) (data_get($content, 'humans.occupation') ?? 'N/A')],
                    ['label' => 'Country', 'value' => (string) (data_get($content, 'humans.countries.name') ?? 'N/A')],
                    ['label' => 'Sex', 'value' => (string) (data_get($content, 'humans.sex') ?? 'N/A')],
                    ['label' => 'Age', 'value' => $this->humanAge((string) data_get($content, 'humans.date_of_birth'))],
                    ['label' => 'Sampling site', 'value' => (string) (data_get($content, 'sampling_sites.name') ?? 'N/A')],
                ],
            ];
        }

        if ($contentType === ParasiteSamples::class) {
            return [
                'type' => 'Parasite sample',
                'columns' => [
                    ['label' => 'Species', 'value' => (string) (data_get($content, 'parasites.parasite_species.name_scientific') ?? 'N/A')],
                    ['label' => 'Sex', 'value' => (string) (data_get($content, 'parasites.sex') ?? 'N/A')],
                    ['label' => 'Stage', 'value' => (string) (data_get($content, 'parasites.stage') ?? 'N/A')],
                    ['label' => 'State', 'value' => (string) (data_get($content, 'parasites.state') ?? 'N/A')],
                ],
            ];
        }

        if ($contentType === EnvironmentSamples::class) {
            return [
                'type' => 'Environment sample',
                'columns' => [
                    ['label' => 'Sample type', 'value' => (string) (data_get($content, 'environment_sample_types.name') ?? 'N/A')],
                    ['label' => 'Sampling site', 'value' => (string) (data_get($content, 'sampling_sites.name') ?? 'N/A')],
                    ['label' => 'Area', 'value' => (string) (data_get($content, 'area') ?? 'N/A')],
                    ['label' => 'Date collected', 'value' => $this->formatDateYmd(data_get($content, 'date_collected'))],
                ],
            ];
        }

        if ($contentType === NucleicAcids::class) {
            $originType = (string) (data_get($content, 'nucleic_content_type') ?? '');
            $columns = [
                ['label' => 'Type', 'value' => (string) (data_get($content, 'type') ?? 'N/A')],
                ['label' => 'Date extracted', 'value' => $this->formatDateYmd(data_get($content, 'date_extracted'))],
                ['label' => 'Origin type', 'value' => class_basename($originType) ?: 'N/A'],
                ['label' => 'Origin code', 'value' => (string) (data_get($content, 'nucleic_content.code') ?? 'N/A')],
            ];

            if ($originType === HumanSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin occupation', 'value' => (string) (data_get($content, 'nucleic_content.humans.occupation') ?? 'N/A')],
                    ['label' => 'Origin country', 'value' => (string) (data_get($content, 'nucleic_content.humans.countries.name') ?? 'N/A')],
                    ['label' => 'Origin sex', 'value' => (string) (data_get($content, 'nucleic_content.humans.sex') ?? 'N/A')],
                    ['label' => 'Origin age', 'value' => $this->humanAge((string) data_get($content, 'nucleic_content.humans.date_of_birth'))],
                    ['label' => 'Origin site', 'value' => (string) (data_get($content, 'nucleic_content.sampling_sites.name') ?? 'N/A')],
                ]);
            } elseif ($originType === AnimalSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin species', 'value' => (string) (data_get($content, 'nucleic_content.animals.animal_species.name_common') ?? 'N/A')],
                    ['label' => 'Origin sex', 'value' => (string) (data_get($content, 'nucleic_content.animals.sex') ?? 'N/A')],
                    ['label' => 'Origin age', 'value' => (string) (data_get($content, 'nucleic_content.animals.age') ?? 'N/A')],
                    ['label' => 'Origin site', 'value' => (string) (data_get($content, 'nucleic_content.sampling_sites.name') ?? 'N/A')],
                ]);
            } elseif ($originType === EnvironmentSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin sample type', 'value' => (string) (data_get($content, 'nucleic_content.environment_sample_types.name') ?? 'N/A')],
                    ['label' => 'Origin site', 'value' => (string) (data_get($content, 'nucleic_content.sampling_sites.name') ?? 'N/A')],
                    ['label' => 'Origin area', 'value' => (string) (data_get($content, 'nucleic_content.area') ?? 'N/A')],
                    ['label' => 'Origin date', 'value' => $this->formatDateYmd(data_get($content, 'nucleic_content.date_collected'))],
                ]);
            } elseif ($originType === ParasiteSamples::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin species', 'value' => (string) (data_get($content, 'nucleic_content.parasites.parasite_species.name_scientific') ?? 'N/A')],
                    ['label' => 'Origin sex', 'value' => (string) (data_get($content, 'nucleic_content.parasites.sex') ?? 'N/A')],
                    ['label' => 'Origin stage', 'value' => (string) (data_get($content, 'nucleic_content.parasites.stage') ?? 'N/A')],
                    ['label' => 'Origin state', 'value' => (string) (data_get($content, 'nucleic_content.parasites.state') ?? 'N/A')],
                ]);
            } elseif ($originType === Cultures::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin medium', 'value' => (string) (data_get($content, 'nucleic_content.medium') ?? 'N/A')],
                    ['label' => 'Origin culture type', 'value' => (string) (data_get($content, 'nucleic_content.type') ?? 'N/A')],
                    ['label' => 'Origin culture date', 'value' => $this->formatDateYmd(data_get($content, 'nucleic_content.date_cultured'))],
                ]);
            } elseif ($originType === Pools::class) {
                $columns = array_merge($columns, [
                    ['label' => 'Origin nr pooled', 'value' => (string) (data_get($content, 'nucleic_content.nr_pooled') ?? 'N/A')],
                    ['label' => 'Origin date pooled', 'value' => $this->formatDateYmd(data_get($content, 'nucleic_content.date_pooled'))],
                ]);
            }

            return [
                'type' => 'Nucleic acid',
                'columns' => $columns,
            ];
        }

        if ($contentType === Cultures::class) {
            return [
                'type' => 'Culture',
                'columns' => [
                    ['label' => 'Medium', 'value' => (string) (data_get($content, 'medium') ?? 'N/A')],
                    ['label' => 'Type', 'value' => (string) (data_get($content, 'type') ?? 'N/A')],
                    ['label' => 'Step', 'value' => (string) (data_get($content, 'step') ?? 'N/A')],
                    ['label' => 'Date cultured', 'value' => $this->formatDateYmd(data_get($content, 'date_cultured'))],
                ],
            ];
        }

        if ($contentType === Pools::class) {
            return [
                'type' => 'Pool',
                'columns' => [
                    ['label' => 'Nr pooled', 'value' => (string) (data_get($content, 'nr_pooled') ?? 'N/A')],
                    ['label' => 'Date pooled', 'value' => $this->formatDateYmd(data_get($content, 'date_pooled'))],
                ],
            ];
        }

        return [
            'type' => class_basename($contentType) ?: 'Other',
            'columns' => [['label' => 'Code', 'value' => (string) (data_get($content, 'code') ?? 'N/A')]],
        ];
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

    private function publishSelectedLiterature(int $projectId): int
    {
        return match ($this->literatureType) {
            'animal' => MetaAnimal::query()
                ->where('projects_id', $projectId)
                ->whereIn('id', $this->selectedItems)
                ->update(['is_private' => false]),
            'human' => MetaHuman::query()
                ->where('projects_id', $projectId)
                ->whereIn('id', $this->selectedItems)
                ->update(['is_private' => false]),
            'environment' => MetaEnvironment::query()
                ->where('projects_id', $projectId)
                ->whereIn('id', $this->selectedItems)
                ->update(['is_private' => false]),
            'parasite' => MetaParasite::query()
                ->where('projects_id', $projectId)
                ->whereIn('id', $this->selectedItems)
                ->update(['is_private' => false]),
            default => 0,
        };
    }
}
