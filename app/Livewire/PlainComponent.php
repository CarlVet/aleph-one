<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPaginatedIndex;
use App\Support\ProjectPermission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.plain')]
class PlainComponent extends Component
{
    use WithPaginatedIndex;

    /**
     * Some browser tooling attempts `JSON.stringify($wire)` which triggers a `$wire.toJSON()` call.
     * Livewire interprets that as a component method call, so we provide a harmless implementation.
     *
     * @return array<string, mixed>
     */
    public function toJSON(): array
    {
        return [];
    }

    protected function selectedProjectId(): ?int
    {
        $id = session('selected_project_id');

        return $id !== null ? (int) $id : null;
    }

    public function currentPeopleId(): ?int
    {
        $user = Auth::user();

        return $user && $user->people ? (int) $user->people->id : null;
    }

    public function isGuestMode(): bool
    {
        return $this->selectedProjectId() === null;
    }

    protected function userCanEditSelectedProject(): bool
    {
        $projectId = $this->selectedProjectId();
        if ($projectId === null) {
            return false;
        }

        $user = Auth::user();
        if (! $user || ! $user->people) {
            return false;
        }

        $project = $user->people->projects()
            ->where('projects.id', $projectId)
            ->withPivot('permission')
            ->first();

        if (! $project || ! $project->pivot) {
            return false;
        }

        return ProjectPermission::canWrite($user, $projectId, null);
    }

    protected function userCanViewModule(string $module): bool
    {
        $projectId = $this->selectedProjectId();
        $user = Auth::user();

        if ($projectId === null || ! $user) {
            return false;
        }

        return ProjectPermission::canView($user, $projectId, $module);
    }

    protected function userCanWriteModule(string $module): bool
    {
        $projectId = $this->selectedProjectId();
        $user = Auth::user();

        if ($projectId === null || ! $user) {
            return false;
        }

        return ProjectPermission::canWrite($user, $projectId, $module);
    }

    protected function userCanMutateOwnedRecord(?int $ownerPeopleId, string $module): bool
    {
        $projectId = $this->selectedProjectId();
        $user = Auth::user();

        if ($projectId === null || ! $user) {
            return false;
        }

        return ProjectPermission::canEditOrDelete($user, $projectId, $ownerPeopleId, $module);
    }

    public function render()
    {
        return view('livewire.plain-component');
    }
}
