<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\CultureObservation;
use App\Models\CultureObservationComment;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Support\CultureObservationRecorder;
use App\Support\ProjectPermission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class CultureProfile extends PlainComponent
{
    use WithFileUploads;

    public $culture;

    public $code;

    public $photo;

    public ?string $photoObservedAt = null;

    public ?string $photoNotes = null;

    public ?int $photoObserverPeopleId = null;

    public ?int $editingObservationId = null;

    public ?string $editingPhotoObservedAt = null;

    public ?string $editingPhotoNotes = null;

    public ?int $editingObserverPeopleId = null;

    public int $activePhotoIndex = 0;

    /** @var array<int, string> */
    public array $newPhotoComments = [];

    /** @var array<int, string> */
    public array $replyPhotoComments = [];

    /** @var array<int, bool> */
    public array $showReplyForm = [];

    public $uploadingPhoto = false;

    public $uploadError = null;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    // Inline editing properties
    public $editingValues = [
        'medium' => '',
        'type' => '',
        'incubation_temp' => '',
        'athmosphere' => '',
        'date_cultured' => '',
    ];

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->culture = $this->loadCulture();
        $this->syncEditingObservationFields();
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this culture profile.';

            return;
        }

        // Load the culture to check if it belongs to the selected project
        $culture = Cultures::where('code', $this->code)->first();

        if (! $culture) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Culture not found.';

            return;
        }

        // Check if the culture belongs to the selected project
        if ($culture->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this culture because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($culture->people_id ?? 0), 'cultures');

        $this->canView = true;
    }

    public function canEditPhotoDates(): bool
    {
        if (! $this->culture) {
            return false;
        }

        $projectId = $this->selectedProjectId();
        $user = Auth::user();

        if ($projectId === null || ! $user) {
            return false;
        }

        if (ProjectPermission::canAssignRegistrar($user, $projectId)) {
            return true;
        }

        return $this->userCanMutateOwnedRecord((int) ($this->culture->people_id ?? 0), 'cultures');
    }

    public function canCommentOnPhotos(): bool
    {
        return Auth::check() && $this->canView;
    }

    public function syncEditingObservationFields(): void
    {
        if (! $this->culture) {
            $this->editingObservationId = null;
            $this->editingPhotoObservedAt = null;
            $this->editingPhotoNotes = null;
            $this->editingObserverPeopleId = null;

            return;
        }

        $observations = $this->culture->observations;
        if ($observations->isEmpty()) {
            $this->editingObservationId = null;
            $this->editingPhotoObservedAt = null;
            $this->editingPhotoNotes = null;
            $this->editingObserverPeopleId = null;

            return;
        }

        $index = min(max(0, $this->activePhotoIndex), $observations->count() - 1);
        $observation = $observations[$index];
        $this->editingObservationId = $observation->id;
        $this->editingPhotoObservedAt = $observation->observed_at?->format('Y-m-d');
        $this->editingPhotoNotes = $observation->notes ?? '';
        $this->editingObserverPeopleId = $observation->people_id;
    }

    public function updatedEditingPhotoObservedAt(?string $value): void
    {
        if ($this->editingObservationId === null || ! $this->canEditPhotoDates()) {
            return;
        }

        $this->updateObservationDate($this->editingObservationId, $value);
    }

    public function updatedEditingPhotoNotes(?string $value): void
    {
        if ($this->editingObservationId === null || ! $this->canEditPhotoDates()) {
            return;
        }

        $this->updateObservationNotes($this->editingObservationId, $value);
    }

    public function updatedEditingObserverPeopleId(?int $value): void
    {
        if ($this->editingObservationId === null || ! $this->canEditPhotoDates()) {
            return;
        }

        $this->updateObservationObserver($this->editingObservationId, $value);
    }

    /**
     * @return array<int, string>
     */
    private function observationRelationEagerLoads(): array
    {
        return [
            'people',
            'photo',
            'comments.user.people',
            'comments.replies.user.people',
        ];
    }

    private function ensureLegacyPhotoRecords(Cultures $culture): void
    {
        CultureObservationRecorder::ensureLegacyPhotoRecord($culture);

        $culture->load([
            'observations' => function ($query): void {
                $query->with($this->observationRelationEagerLoads());
            },
        ]);
    }

    private function loadCulture()
    {
        $projectId = session('selected_project_id');

        $culture = Cultures::whereHasMorph(
            'cultures_content',
            [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class,
                ParasiteSamples::class, Pools::class])
            ->with([
                'people',
                'laboratories',
                'laboratories.countries',
                'projects',
                'nucleic_acids' => function ($query) use ($projectId) {
                    if ($projectId) {
                        $query->where('projects_id', $projectId);
                    }
                },
                'nucleic_acids.people',
                'nucleic_acids.laboratories',
                'experiments' => function ($query) use ($projectId) {
                    if ($projectId) {
                        $query->where('projects_id', $projectId);
                    }
                },
                'experiments.protocols.techniques',
                'experiments.pathogens',
                'experiments.people',
                'experiments.laboratories',
                'tubes' => function ($query) use ($projectId) {
                    if ($projectId) {
                        $query->where('projects_id', $projectId);
                    }
                },
                'tubes.tube_positions',
                'tubes.tube_positions.boxes',
                'photos',
                'observations' => function ($query): void {
                    $query->with($this->observationRelationEagerLoads());
                },
                'cultures_content',
            ])->where('code', $this->code)->firstOrFail();

        $this->ensureLegacyPhotoRecords($culture);

        return $culture;
    }

    public function uploadPhoto()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to upload photos to this culture profile.');

            return;
        }

        if (! $this->photo) {
            $this->uploadError = 'Please select a photo first.';
            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        if ($this->photo && $this->photo->getSize() > 52428800) {
            $this->uploadError = 'File size exceeds 50MB limit.';
            $this->photo = null;

            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }
        $this->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200',
            'photoObservedAt' => 'nullable|date',
            'photoNotes' => 'nullable|string|max:2000',
            'photoObserverPeopleId' => 'nullable|integer|exists:people,id',
        ], [
            'photo.mimes' => 'Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.',
        ]);
        $this->uploadingPhoto = true;
        $this->uploadError = null;
        try {
            $photoPath = $this->photo->store('culture-photos', 'local');
            CultureObservationRecorder::createWithPhoto(
                culture: $this->culture,
                photoPath: $photoPath,
                observedAt: $this->photoObservedAt,
                notes: $this->photoNotes,
                peopleId: $this->photoObserverPeopleId ?: ProjectPermission::currentRegistrarPeopleId(Auth::user()),
            );
            $this->culture->syncCoverPhotoPath();
            $this->culture = $this->loadCulture();
            $this->activePhotoIndex = 0;

            $this->photo = null;
            $this->photoObservedAt = null;
            $this->photoNotes = null;
            $this->photoObserverPeopleId = null;
            $this->uploadingPhoto = false;
            $this->syncEditingObservationFields();
            session()->flash('message', 'Photo uploaded successfully!');
            $this->dispatch('show-success', message: 'Photo uploaded successfully!');
            $this->dispatch('photo-uploaded');
        } catch (\Exception $e) {
            $this->uploadingPhoto = false;
            $this->uploadError = 'Failed to upload photo: '.$e->getMessage();
            $this->dispatch('show-error', message: $this->uploadError);
        }
    }

    public function updateObservationDate(int $observationId, ?string $observedAt): void
    {
        if (! $this->canEditPhotoDates()) {
            session()->flash('error', 'You do not have permission to edit observation dates for this culture.');

            return;
        }

        if ($observedAt !== null && $observedAt !== '') {
            validator(['observed_at' => $observedAt], [
                'observed_at' => 'date',
            ])->validate();
        }

        try {
            $observation = CultureObservation::query()
                ->where('cultures_id', $this->culture->id)
                ->whereKey($observationId)
                ->first();

            if (! $observation) {
                return;
            }

            $normalizedObservedAt = $observedAt !== null && $observedAt !== '' ? $observedAt : null;
            $currentObservedAt = $observation->observed_at?->format('Y-m-d');

            if ($currentObservedAt === $normalizedObservedAt) {
                return;
            }

            $observation->update([
                'observed_at' => $normalizedObservedAt,
            ]);

            $this->culture->syncCoverPhotoPath();
            $this->culture = $this->loadCulture();
            $this->syncEditingObservationFields();
            session()->flash('message', 'Observation date updated successfully!');
            $this->dispatch('show-success', message: 'Observation date updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update observation date: '.$e->getMessage());
            $this->dispatch('show-error', message: 'Failed to update observation date: '.$e->getMessage());
        }
    }

    public function updateObservationNotes(int $observationId, ?string $notes): void
    {
        if (! $this->canEditPhotoDates()) {
            session()->flash('error', 'You do not have permission to edit observation notes for this culture.');

            return;
        }

        validator(['notes' => $notes], [
            'notes' => 'nullable|string|max:2000',
        ])->validate();

        try {
            $observation = CultureObservation::query()
                ->where('cultures_id', $this->culture->id)
                ->whereKey($observationId)
                ->first();

            if (! $observation) {
                return;
            }

            $normalizedNotes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

            if ($observation->notes === $normalizedNotes) {
                return;
            }

            $observation->update([
                'notes' => $normalizedNotes,
            ]);

            $this->culture = $this->loadCulture();
            $this->syncEditingObservationFields();
            session()->flash('message', 'Observation notes updated successfully!');
            $this->dispatch('show-success', message: 'Observation notes updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update observation notes: '.$e->getMessage());
            $this->dispatch('show-error', message: 'Failed to update observation notes: '.$e->getMessage());
        }
    }

    public function updateObservationObserver(int $observationId, ?int $peopleId): void
    {
        if (! $this->canEditPhotoDates()) {
            session()->flash('error', 'You do not have permission to edit observation details for this culture.');

            return;
        }

        validator(['people_id' => $peopleId], [
            'people_id' => 'nullable|integer|exists:people,id',
        ])->validate();

        try {
            $observation = CultureObservation::query()
                ->where('cultures_id', $this->culture->id)
                ->whereKey($observationId)
                ->first();

            if (! $observation) {
                return;
            }

            if ((int) ($observation->people_id ?? 0) === (int) ($peopleId ?? 0)) {
                return;
            }

            $observation->update([
                'people_id' => $peopleId,
            ]);

            $this->culture = $this->loadCulture();
            $this->syncEditingObservationFields();
            session()->flash('message', 'Observer updated successfully!');
            $this->dispatch('show-success', message: 'Observer updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update observer: '.$e->getMessage());
            $this->dispatch('show-error', message: 'Failed to update observer: '.$e->getMessage());
        }
    }

    public function addObservationComment(int $observationId, ?int $parentId = null): void
    {
        if (! $this->canCommentOnPhotos()) {
            session()->flash('error', 'You must be signed in to comment on photos.');

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

        $observation = CultureObservation::query()
            ->where('cultures_id', $this->culture->id)
            ->whereKey($observationId)
            ->first();

        if (! $observation) {
            return;
        }

        if ($parentId !== null) {
            $parentComment = CultureObservationComment::query()
                ->where('culture_observations_id', $observationId)
                ->whereKey($parentId)
                ->whereNull('parent_id')
                ->first();

            if (! $parentComment) {
                return;
            }
        }

        CultureObservationComment::query()->create([
            'culture_observations_id' => $observationId,
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

        $this->culture = $this->loadCulture();
        $this->syncEditingObservationFields();
        session()->flash('message', 'Comment posted successfully!');
        $this->dispatch('show-success', message: 'Comment posted successfully!');
    }

    public function toggleReplyForm(int $commentId): void
    {
        $this->showReplyForm[$commentId] = ! ($this->showReplyForm[$commentId] ?? false);
    }

    public function updateDiscarded(bool $isDiscarded, ?string $dateDiscarded = null): void
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to update discard status for this culture.');

            return;
        }

        if ($isDiscarded) {
            validator(['date_discarded' => $dateDiscarded ?: now()->toDateString()], [
                'date_discarded' => 'required|date',
            ])->validate();
        }

        $this->culture->update([
            'is_discarded' => $isDiscarded,
            'date_discarded' => $isDiscarded ? ($dateDiscarded ?: now()->toDateString()) : null,
        ]);

        $this->culture = $this->loadCulture();
        session()->flash('message', $isDiscarded ? 'Culture marked as discarded.' : 'Culture marked as active.');
        $this->dispatch('show-success', message: $isDiscarded ? 'Culture marked as discarded.' : 'Culture marked as active.');
    }

    public function deleteObservation(int $observationId): void
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete observations from this culture profile.');

            return;
        }

        try {
            $observation = CultureObservation::query()
                ->where('cultures_id', $this->culture->id)
                ->with('photo')
                ->whereKey($observationId)
                ->first();

            if (! $observation) {
                return;
            }

            $photoPath = $observation->photo?->photo_path;
            if ($photoPath && Storage::disk('local')->exists($photoPath)) {
                Storage::disk('local')->delete($photoPath);
            }

            $observation->delete();
            $this->culture->syncCoverPhotoPath();
            $this->culture = $this->loadCulture();
            $this->activePhotoIndex = min($this->activePhotoIndex, max(0, $this->culture->observations->count() - 1));
            $this->syncEditingObservationFields();
            session()->flash('message', 'Observation deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete observation: '.$e->getMessage());
        }
    }

    public function showPhotoAt(int $index): void
    {
        if ($index < 0 || $index >= $this->culture->observations->count()) {
            return;
        }

        $this->activePhotoIndex = $index;
        $this->culture = $this->loadCulture();
        $this->syncEditingObservationFields();
    }

    public function nextPhoto(): void
    {
        $count = $this->culture->observations->count();
        if ($count === 0) {
            return;
        }

        $this->activePhotoIndex = ($this->activePhotoIndex + 1) % $count;
        $this->culture = $this->loadCulture();
        $this->syncEditingObservationFields();
    }

    public function previousPhoto(): void
    {
        $count = $this->culture->observations->count();
        if ($count === 0) {
            return;
        }

        $this->activePhotoIndex = ($this->activePhotoIndex - 1 + $count) % $count;
        $this->culture = $this->loadCulture();
        $this->syncEditingObservationFields();
    }

    public function updatedPhoto()
    {
        // Force reload the culture data when photo is selected to prevent lazy loading errors
        if ($this->photo) {
            $this->culture = $this->loadCulture();
        }
    }

    public function cancelPhotoSelection()
    {
        $this->photo = null;
        $this->photoObservedAt = null;
        $this->photoNotes = null;
        $this->photoObserverPeopleId = null;
        $this->uploadError = null;
        $this->dispatch('photo-cancelled');
    }

    // Inline editing methods
    public function startEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this culture profile.');

            return;
        }

        // Ensure the culture is loaded with all relationships before editing
        $this->culture = $this->loadCulture();
        $value = $this->culture->$field;
        if ($field === 'date_cultured' && $value) {
            $value = Carbon::parse($value)->format('Y-m-d');
        }
        $this->editingValues[$field] = $value;
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this culture profile.');

            return;
        }

        try {
            $this->validate([
                'editingValues.medium' => 'nullable|string|max:255',
                'editingValues.type' => 'nullable|string|max:255',
                'editingValues.incubation_temp' => 'nullable|integer|min:0|max:100',
                'editingValues.athmosphere' => 'nullable|string|max:255',
                'editingValues.date_cultured' => 'nullable|date',
            ]);

            $this->culture->update([$field => $this->editingValues[$field]]);

            // Re-query the culture with all relationships
            $this->culture = $this->loadCulture();

            $this->editingValues[$field] = '';
            $this->dispatch('save-edit', field: $field);
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

    public function cancelEdit($field)
    {
        // Ensure the culture is loaded with all relationships when canceling
        $this->culture = $this->loadCulture();
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function deleteCulture()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this culture.');

            return;
        }

        try {
            $this->culture->delete();
            session()->flash('message', 'Culture deleted successfully!');

            return redirect('/samples/cultures/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete culture: '.$e->getMessage());
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.culture-profile', [
                'culture' => null,
                'photo' => null,
                'uploadingPhoto' => false,
                'uploadError' => null,
                'canView' => false,
                'canEdit' => false,
                'canEditPhotoDates' => false,
                'canCommentOnPhotos' => false,
                'observerPeople' => collect(),
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        // Get existing values for datalists
        $this->culture = $this->loadCulture();

        $existingMediums = Cultures::whereNotNull('medium')
            ->where('medium', '!=', '')
            ->pluck('medium')
            ->unique()
            ->values()
            ->toArray();

        $existingTypes = Cultures::whereNotNull('type')
            ->where('type', '!=', '')
            ->pluck('type')
            ->unique()
            ->values()
            ->toArray();

        $existingAtmospheres = Cultures::whereNotNull('athmosphere')
            ->where('athmosphere', '!=', '')
            ->pluck('athmosphere')
            ->unique()
            ->values()
            ->toArray();

        $projectId = $this->selectedProjectId();
        $observerPeople = $projectId
            ? Projects::query()->with('people')->find($projectId)?->people ?? collect()
            : collect();

        return view('livewire.culture-profile', [
            'culture' => $this->culture,
            'photo' => $this->photo,
            'uploadingPhoto' => $this->uploadingPhoto,
            'uploadError' => $this->uploadError,
            'existingMediums' => $existingMediums,
            'existingTypes' => $existingTypes,
            'existingAtmospheres' => $existingAtmospheres,
            'observerPeople' => $observerPeople,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'canEditPhotoDates' => $this->canEditPhotoDates(),
            'canCommentOnPhotos' => $this->canCommentOnPhotos(),
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
