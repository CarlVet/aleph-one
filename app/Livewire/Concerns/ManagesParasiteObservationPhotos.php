<?php

namespace App\Livewire\Concerns;

use App\Models\ParasiteObservation;
use App\Models\ParasiteObservationComment;
use App\Models\Parasites;
use App\Models\People;
use App\Models\Projects;
use App\Support\ParasiteObservationRecorder;
use App\Support\ProjectPermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait ManagesParasiteObservationPhotos
{
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

    abstract protected function observationParasite(): ?Parasites;

    abstract protected function reloadObservationContext(): void;

    abstract protected function canEditObservationPhotos(): bool;

    public function canEditObservationPhotoDates(): bool
    {
        $parasite = $this->observationParasite();
        if (! $parasite) {
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

        return $this->userCanMutateOwnedRecord((int) ($parasite->people_id ?? 0), 'parasite_samples');
    }

    public function canCommentOnObservationPhotos(): bool
    {
        return Auth::check() && ($this->canView ?? true);
    }

    /**
     * @return array<int, string>
     */
    protected function observationRelationEagerLoads(): array
    {
        return [
            'people',
            'photo',
            'comments.user.people',
            'comments.replies.user.people',
        ];
    }

    protected function ensureLegacyParasitePhotoRecords(Parasites $parasite): void
    {
        ParasiteObservationRecorder::ensureLegacyPhotoRecord($parasite);

        $parasite->load([
            'observations' => function ($query): void {
                $query->with($this->observationRelationEagerLoads());
            },
        ]);
    }

    public function syncEditingObservationFields(): void
    {
        $parasite = $this->observationParasite();
        if (! $parasite) {
            $this->editingObservationId = null;
            $this->editingPhotoObservedAt = null;
            $this->editingPhotoNotes = null;
            $this->editingObserverPeopleId = null;

            return;
        }

        $observations = $parasite->observations;
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
        if ($this->editingObservationId === null || ! $this->canEditObservationPhotoDates()) {
            return;
        }

        $this->updateObservationDate($this->editingObservationId, $value);
    }

    public function updatedEditingPhotoNotes(?string $value): void
    {
        if ($this->editingObservationId === null || ! $this->canEditObservationPhotoDates()) {
            return;
        }

        $this->updateObservationNotes($this->editingObservationId, $value);
    }

    public function updatedEditingObserverPeopleId(?int $value): void
    {
        if ($this->editingObservationId === null || ! $this->canEditObservationPhotoDates()) {
            return;
        }

        $this->updateObservationObserver($this->editingObservationId, $value);
    }

    public function uploadPhoto(): void
    {
        if (! $this->canEditObservationPhotos()) {
            session()->flash('error', 'You do not have permission to upload photos.');

            return;
        }

        $parasite = $this->observationParasite();
        if (! $parasite) {
            return;
        }

        if (! $this->photo) {
            $this->uploadError = 'Please select a photo first.';
            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        if ($this->photo->getSize() > 52428800) {
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
            $photoPath = $this->photo->store('parasite-photos', 'local');
            ParasiteObservationRecorder::createWithPhoto(
                parasite: $parasite,
                photoPath: $photoPath,
                observedAt: $this->photoObservedAt,
                notes: $this->photoNotes,
                peopleId: $this->photoObserverPeopleId ?: ProjectPermission::currentRegistrarPeopleId(Auth::user()),
            );
            $parasite->syncCoverPhotoPath();
            $this->reloadObservationContext();
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
        if (! $this->canEditObservationPhotoDates()) {
            session()->flash('error', 'You do not have permission to edit observation dates.');

            return;
        }

        $parasite = $this->observationParasite();
        if (! $parasite) {
            return;
        }

        if ($observedAt !== null && $observedAt !== '') {
            validator(['observed_at' => $observedAt], [
                'observed_at' => 'date',
            ])->validate();
        }

        try {
            $observation = ParasiteObservation::query()
                ->where('parasites_id', $parasite->id)
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

            $parasite->syncCoverPhotoPath();
            $this->reloadObservationContext();
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
        if (! $this->canEditObservationPhotoDates()) {
            session()->flash('error', 'You do not have permission to edit observation notes.');

            return;
        }

        $parasite = $this->observationParasite();
        if (! $parasite) {
            return;
        }

        validator(['notes' => $notes], [
            'notes' => 'nullable|string|max:2000',
        ])->validate();

        try {
            $observation = ParasiteObservation::query()
                ->where('parasites_id', $parasite->id)
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

            $this->reloadObservationContext();
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
        if (! $this->canEditObservationPhotoDates()) {
            session()->flash('error', 'You do not have permission to edit observation details.');

            return;
        }

        $parasite = $this->observationParasite();
        if (! $parasite) {
            return;
        }

        validator(['people_id' => $peopleId], [
            'people_id' => 'nullable|integer|exists:people,id',
        ])->validate();

        try {
            $observation = ParasiteObservation::query()
                ->where('parasites_id', $parasite->id)
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

            $this->reloadObservationContext();
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
        if (! $this->canCommentOnObservationPhotos()) {
            session()->flash('error', 'You must be signed in to comment on photos.');

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

        $observation = ParasiteObservation::query()
            ->where('parasites_id', $parasite->id)
            ->whereKey($observationId)
            ->first();

        if (! $observation) {
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

    public function toggleReplyForm(int $commentId): void
    {
        $this->showReplyForm[$commentId] = ! ($this->showReplyForm[$commentId] ?? false);
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
            $this->activePhotoIndex = min($this->activePhotoIndex, max(0, ($parasite->observations->count()) - 1));
            $this->syncEditingObservationFields();
            session()->flash('message', 'Observation deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete observation: '.$e->getMessage());
        }
    }

    public function showPhotoAt(int $index): void
    {
        $parasite = $this->observationParasite();
        if (! $parasite || $index < 0 || $index >= $parasite->observations->count()) {
            return;
        }

        $this->activePhotoIndex = $index;
        $this->reloadObservationContext();
        $this->syncEditingObservationFields();
    }

    public function nextPhoto(): void
    {
        $parasite = $this->observationParasite();
        $count = $parasite?->observations->count() ?? 0;
        if ($count === 0) {
            return;
        }

        $this->activePhotoIndex = ($this->activePhotoIndex + 1) % $count;
        $this->reloadObservationContext();
        $this->syncEditingObservationFields();
    }

    public function previousPhoto(): void
    {
        $parasite = $this->observationParasite();
        $count = $parasite?->observations->count() ?? 0;
        if ($count === 0) {
            return;
        }

        $this->activePhotoIndex = ($this->activePhotoIndex - 1 + $count) % $count;
        $this->reloadObservationContext();
        $this->syncEditingObservationFields();
    }

    public function updatedPhoto(): void
    {
        if ($this->photo) {
            $this->reloadObservationContext();
        }
    }

    public function cancelPhotoSelection(): void
    {
        $this->photo = null;
        $this->photoObservedAt = null;
        $this->photoNotes = null;
        $this->photoObserverPeopleId = null;
        $this->uploadError = null;
        $this->dispatch('photo-cancelled');
    }

    /**
     * @return Collection<int, People>
     */
    protected function observerPeopleForProject(): Collection
    {
        $projectId = $this->selectedProjectId();

        return $projectId
            ? Projects::query()->with('people')->find($projectId)?->people ?? collect()
            : collect();
    }
}
