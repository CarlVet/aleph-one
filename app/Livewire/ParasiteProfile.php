<?php

namespace App\Livewire;

use App\Enums\ParasiteStatus;
use App\Livewire\Concerns\ManagesParasiteObservationPhotos;
use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteObservation;
use App\Models\ParasiteObservationComment;
use App\Models\Parasites;
use App\Models\ParasiteSampleObservation;
use App\Models\ParasiteSampleObservationComment;
use App\Services\SampleExperimentsAggregator;
use App\Support\ParasiteObservationRecorder;
use App\Support\ParasiteSampleObservationRecorder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class ParasiteProfile extends PlainComponent
{
    use ManagesParasiteObservationPhotos;
    use WithFileUploads;

    protected ?Parasites $parasite = null;

    public $code;

    public $canView = true;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    public ?string $editingField = null;

    public $editingValues = [
        'stage' => '',
        'sex' => '',
        'state' => '',
        'status' => '',
        'date_identified' => '',
    ];

    /**
     * @var array<int, string>
     */
    public array $sexOptions = ['Male', 'Female', 'Unknown', 'NA'];

    /**
     * @var array<int, string>
     */
    public array $stageOptions = ['Adult', 'Pupa', 'Larva', 'Nymph', 'Egg', 'Unknown', 'NA'];

    /**
     * @var array<int, string>
     */
    public array $stateOptions = ['Engorged', 'Partially engorged', 'Not engorged', 'NA'];

    public function startEdit(string $field): void
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this parasite.');

            return;
        }

        if ($field === 'date_identified') {
            $this->editingValues[$field] = $this->formatDateForInput($this->parasite->date_identified);
        } elseif ($field === 'status') {
            $this->editingValues[$field] = $this->parasite->status?->value ?? 'intact';
        } else {
            $this->editingValues[$field] = (string) ($this->parasite->$field ?? '');
        }

        $this->editingField = $field;
    }

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->parasite = $this->loadParasiteWithRelationships();
        $this->syncEditingObservationFields();
    }

    public function hydrate(): void
    {
        if (! $this->canView || $this->code === null) {
            return;
        }

        $this->parasite ??= Parasites::query()->where('code', $this->code)->firstOrFail();
    }

    public function updatedPhoto(): void
    {
        // Selecting a file should not reload the full parasite graph.
    }

    private function loadedParasite(): Parasites
    {
        if ($this->parasite instanceof Parasites && $this->parasite->relationLoaded('parasite_samples')) {
            return $this->parasite;
        }

        $this->parasite = $this->loadParasiteWithRelationships();

        return $this->parasite;
    }

    private function checkAuthorization()
    {
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this parasite profile.';

            return;
        }

        $parasite = Parasites::where('code', $this->code)->first();

        if (! $parasite) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Parasite not found.';

            return;
        }

        if ($parasite->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this parasite because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($parasite->people_id ?? 0), 'parasite_samples');

        $this->canView = true;
    }

    protected function observationParasite(): ?Parasites
    {
        return $this->parasite;
    }

    protected function reloadObservationContext(): void
    {
        $this->parasite = $this->loadParasiteWithRelationships();
    }

    protected function canEditObservationPhotos(): bool
    {
        return $this->canEdit;
    }

    public function syncEditingObservationFields(): void
    {
        $observations = $this->aggregatedGalleryObservations($this->loadedParasite());

        if ($observations->isEmpty()) {
            $this->editingObservationId = null;
            $this->editingPhotoObservedAt = null;
            $this->editingPhotoNotes = null;
            $this->editingObserverPeopleId = null;

            return;
        }

        $index = min(max(0, $this->activePhotoIndex), $observations->count() - 1);
        $observation = $observations[$index];

        if ($observation instanceof ParasiteSampleObservation) {
            $this->editingObservationId = null;
            $this->editingPhotoObservedAt = null;
            $this->editingPhotoNotes = null;
            $this->editingObserverPeopleId = null;

            return;
        }

        $this->editingObservationId = $observation->id;
        $this->editingPhotoObservedAt = $observation->observed_at?->format('Y-m-d');
        $this->editingPhotoNotes = $observation->notes ?? '';
        $this->editingObserverPeopleId = $observation->people_id;
    }

    public function showPhotoAt(int $index): void
    {
        $count = $this->aggregatedGalleryObservations($this->loadedParasite())->count();
        if ($index < 0 || $index >= $count) {
            return;
        }

        $this->activePhotoIndex = $index;
        $this->syncEditingObservationFields();
    }

    public function nextPhoto(): void
    {
        $count = $this->aggregatedGalleryObservations($this->loadedParasite())->count();
        if ($count === 0) {
            return;
        }

        $this->activePhotoIndex = ($this->activePhotoIndex + 1) % $count;
        $this->syncEditingObservationFields();
    }

    public function previousPhoto(): void
    {
        $count = $this->aggregatedGalleryObservations($this->loadedParasite())->count();
        if ($count === 0) {
            return;
        }

        $this->activePhotoIndex = ($this->activePhotoIndex - 1 + $count) % $count;
        $this->syncEditingObservationFields();
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRulesForField(string $field): array
    {
        return match ($field) {
            'stage' => ['editingValues.stage' => ['nullable', 'string', Rule::in($this->stageOptions)]],
            'sex' => ['editingValues.sex' => ['nullable', 'string', Rule::in($this->sexOptions)]],
            'state' => ['editingValues.state' => ['nullable', 'string', Rule::in($this->stateOptions)]],
            'status' => ['editingValues.status' => ['required', 'string', Rule::in(ParasiteStatus::values())]],
            'date_identified' => ['editingValues.date_identified' => 'nullable|date'],
            default => [],
        };
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this parasite.');

            return;
        }

        try {
            $this->validate($this->validationRulesForField($field));

            $value = $this->editingValues[$field] ?: null;
            if ($field === 'status' && $value !== null) {
                $value = ParasiteStatus::from($value);
            }

            $this->parasite->update([$field => $value]);
            $this->parasite = $this->loadParasiteWithRelationships();

            $this->editingValues[$field] = '';
            $this->editingField = null;
            $this->dispatch('show-success', message: ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
            session()->flash('message', ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->toArray();
            $errorMessage = implode("\n", $messages);
            $this->dispatch('show-error', message: $errorMessage);
            session()->flash('error', $errorMessage);
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
            session()->flash('error', 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
        }
    }

    public function cancelEdit($field): void
    {
        $this->editingValues[$field] = '';
        $this->editingField = null;
    }

    public function deleteParasite()
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->parasite->delete();

            session()->flash('message', 'Parasite deleted successfully!');

            return redirect('/parasites/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete parasite: '.$e->getMessage());
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.parasite-profile', [
                'canView' => $this->canView,
                'sampleExperiments' => collect(),
                'unauthorizedMessage' => $this->unauthorizedMessage,
                'isGuestMode' => $this->isGuestMode(),
            ]);
        }

        $parasite = $this->loadParasiteWithRelationships();

        return view('livewire.parasite-profile', [
            'parasite' => $parasite,
            'galleryObservations' => $this->aggregatedGalleryObservations($parasite),
            'statusOptions' => ParasiteStatus::options(),
            'sexOptions' => $this->sexOptions,
            'stageOptions' => $this->stageOptions,
            'stateOptions' => $this->stateOptions,
            'sampleExperiments' => app(SampleExperimentsAggregator::class)
                ->forNodes(
                    collect([$parasite])->merge($parasite->parasite_samples),
                    session('selected_project_id')
                ),
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'canEditPhotoDates' => $this->canEditObservationPhotoDates(),
            'canCommentOnPhotos' => $this->canCommentOnObservationPhotos(),
            'observerPeople' => $this->observerPeopleForProject(),
            'photo' => $this->photo,
            'uploadingPhoto' => $this->uploadingPhoto,
            'uploadError' => $this->uploadError,
            'activePhotoIndex' => $this->activePhotoIndex,
            'showReplyForm' => $this->showReplyForm,
            'editingField' => $this->editingField,
            'isGuestMode' => $this->isGuestMode(),
        ]);
    }

    private function loadParasiteWithRelationships(): Parasites
    {
        $projectId = session('selected_project_id');

        $parasite = Parasites::with([
            'parasite_species',
            'locations',
            'parasite_samples' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
                $query->with([
                    'parasite_sample_types',
                    'people',
                    'laboratories',
                    'projects',
                    'tubes.projects',
                    'observations' => function ($observationQuery): void {
                        $observationQuery->with([
                            'photo',
                            'people',
                            'comments.user.people',
                            'comments.replies.user.people',
                        ]);
                    },
                ]);
            },
            'parasites_origin' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites', 'people'],
                    HumanSamples::class => ['humans', 'sample_types', 'sampling_sites', 'people'],
                    EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites', 'people'],
                ]);
            },
            'experiments' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'experiments.protocols',
            'experiments.protocols.techniques',
            'experiments.pathogens',
            'experiments.people',
            'experiments.laboratories',
            'experiments.projects',
            'experiments.experiments_content',
            'nucleic_acids' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'nucleic_acids.people',
            'nucleic_acids.laboratories',
            'nucleic_acids.projects',
            'cultures' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'cultures.people',
            'cultures.laboratories',
            'cultures.projects',
            'pools' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->whereHas('pools', function ($q) use ($projectId) {
                        $q->where('projects_id', $projectId);
                    });
                }
            },
            'pools.pools',
            'pools.pools.people',
            'pools.pools.laboratories',
            'pools.pools.projects',
            'tubes' => function ($query) use ($projectId) {
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'tubes.people',
            'tubes.projects',
            'tubes.tube_positions',
            'tubes.tube_positions.boxes',
            'photos',
            'observations' => function ($query): void {
                $query->with($this->observationRelationEagerLoads());
            },
        ])->where('code', $this->code)->firstOrFail();

        $this->ensureLegacyParasitePhotoRecords($parasite);

        foreach ($parasite->parasite_samples as $sample) {
            ParasiteSampleObservationRecorder::ensureLegacyPhotoRecord($sample);
        }

        return $parasite;
    }

    private function aggregatedGalleryObservations(?Parasites $parasite = null): Collection
    {
        $parasite ??= $this->loadedParasite();

        if (! $parasite) {
            return collect();
        }

        $this->loadGalleryRelations($parasite);

        $items = $parasite->observations->values();

        foreach ($parasite->parasite_samples as $sample) {
            foreach ($sample->observations as $observation) {
                $label = $sample->code.' · '.($sample->parasite_sample_types->name ?? 'Sample');
                $observation->setAttribute('source_label', $label);
                $items->push($observation);
            }
        }

        return $items
            ->sort(function ($left, $right): int {
                $leftTime = $left->observed_at?->timestamp ?? 0;
                $rightTime = $right->observed_at?->timestamp ?? 0;

                if ($leftTime !== $rightTime) {
                    return $rightTime <=> $leftTime;
                }

                return (int) $right->id <=> (int) $left->id;
            })
            ->values();
    }

    private function ensureParasiteRelationsLoaded(): void
    {
        if (! $this->parasite) {
            return;
        }

        $this->loadGalleryRelations($this->loadedParasite());
    }

    private function loadGalleryRelations(Parasites $parasite): void
    {
        $parasite->loadMissing([
            'observations' => function ($query): void {
                $query->with($this->observationRelationEagerLoads());
            },
            'parasite_samples.parasite_sample_types',
            'parasite_samples.observations' => function ($query): void {
                $query->with([
                    'photo',
                    'people',
                    'comments.user.people',
                    'comments.replies.user.people',
                ]);
            },
        ]);
    }

    public function addObservationComment(int $observationId, ?int $parentId = null): void
    {
        if (! $this->canCommentOnObservationPhotos()) {
            session()->flash('error', 'You must be signed in to comment on photos.');

            return;
        }

        $this->ensureParasiteRelationsLoaded();

        $observation = $this->aggregatedGalleryObservations($this->loadedParasite())
            ->first(fn ($item) => (int) $item->id === $observationId);

        if ($observation instanceof ParasiteSampleObservation) {
            $this->addSampleGalleryObservationComment($observationId, $parentId);

            return;
        }

        $parasite = $this->observationParasite();
        if (! $parasite) {
            return;
        }

        $body = $parentId !== null
            ? trim((string) ($this->replyPhotoComments[$parentId] ?? ''))
            : trim((string) ($this->newPhotoComments[$observationId] ?? ''));

        if ($body === '') {
            session()->flash('error', 'Comment cannot be empty.');

            return;
        }

        if (mb_strlen($body) > 5000) {
            session()->flash('error', 'Comment is too long (max 5000 characters).');

            return;
        }

        $parasiteObservation = ParasiteObservation::query()
            ->where('parasites_id', $parasite->id)
            ->whereKey($observationId)
            ->first();

        if (! $parasiteObservation) {
            return;
        }

        if ($parentId !== null) {
            $parentComment = ParasiteObservationComment::query()
                ->where('parasite_observations_id', $observationId)
                ->whereKey($parentId)
                ->whereNull('parent_id')
                ->first();

            if (! $parentComment) {
                return;
            }
        }

        ParasiteObservationComment::query()->create([
            'parasite_observations_id' => $observationId,
            'users_id' => Auth::id(),
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        if ($parentId !== null) {
            unset($this->replyPhotoComments[$parentId]);
            $this->showReplyForm[$parentId] = false;
        } else {
            unset($this->newPhotoComments[$observationId]);
        }

        $this->reloadObservationContext();
        $this->syncEditingObservationFields();
        session()->flash('message', 'Comment posted successfully!');
        $this->dispatch('show-success', message: 'Comment posted successfully!');
    }

    private function addSampleGalleryObservationComment(int $observationId, ?int $parentId = null): void
    {
        $body = $parentId !== null
            ? trim((string) ($this->replyPhotoComments[$parentId] ?? ''))
            : trim((string) ($this->newPhotoComments[$observationId] ?? ''));

        if ($body === '') {
            session()->flash('error', 'Comment cannot be empty.');

            return;
        }

        if (mb_strlen($body) > 5000) {
            session()->flash('error', 'Comment is too long (max 5000 characters).');

            return;
        }

        $sampleObservation = ParasiteSampleObservation::query()
            ->whereKey($observationId)
            ->whereIn('parasite_samples_id', $this->loadedParasite()->parasite_samples->pluck('id'))
            ->first();

        if (! $sampleObservation) {
            return;
        }

        if ($parentId !== null) {
            $parentComment = ParasiteSampleObservationComment::query()
                ->where('parasite_sample_observations_id', $observationId)
                ->whereKey($parentId)
                ->whereNull('parent_id')
                ->first();

            if (! $parentComment) {
                return;
            }
        }

        ParasiteSampleObservationComment::query()->create([
            'parasite_sample_observations_id' => $observationId,
            'users_id' => Auth::id(),
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        if ($parentId !== null) {
            unset($this->replyPhotoComments[$parentId]);
            $this->showReplyForm[$parentId] = false;
        } else {
            unset($this->newPhotoComments[$observationId]);
        }

        $this->reloadObservationContext();
        $this->syncEditingObservationFields();
        session()->flash('message', 'Comment posted successfully!');
        $this->dispatch('show-success', message: 'Comment posted successfully!');
    }

    public function deleteObservation(int $observationId): void
    {
        if (! $this->canEditObservationPhotos()) {
            session()->flash('error', 'You do not have permission to delete observations.');

            return;
        }

        $parasite = $this->observationParasite();
        if (! $parasite) {
            return;
        }

        try {
            $observation = ParasiteObservation::query()
                ->where('parasites_id', $parasite->id)
                ->with('photo')
                ->whereKey($observationId)
                ->first();

            if (! $observation) {
                return;
            }

            ParasiteObservationRecorder::deleteObservation($observation);
            $parasite->syncCoverPhotoPath();
            $this->reloadObservationContext();
            $this->activePhotoIndex = min($this->activePhotoIndex, max(0, $this->aggregatedGalleryObservations($this->loadedParasite())->count() - 1));
            $this->syncEditingObservationFields();
            session()->flash('message', 'Observation deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete observation: '.$e->getMessage());
        }
    }

    private function formatDateForInput(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse((string) $value)->format('Y-m-d');
    }
}
