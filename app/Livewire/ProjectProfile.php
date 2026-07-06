<?php

namespace App\Livewire;

use App\Models\Projects;
use App\Services\ProjectMetricsService;
use App\Support\ProjectPermission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Title('Project Profile')]
class ProjectProfile extends PlainComponent
{
    public Projects $project;

    public string $code;

    public bool $canEdit = false;

    public bool $canManageSubProjects = false;

    public bool $canDeleteProject = false;

    /**
     * @var array<int, string>
     */
    public array $subProjectTypeOptions = [];

    /**
     * @var array<string, mixed>
     */
    public array $metrics = [];

    public function mount(string $code, ProjectMetricsService $metricsService): void
    {
        $this->code = $code;

        $this->project = Projects::query()
            ->with([
                'people',
                'people.users:id,email,people_id',
                'fundings',
                'fundings.recipient',
                'subProjects',
                'subProjects.people',
            ])
            ->where('code', $code)
            ->firstOrFail();

        $user = Auth::user();
        $person = $user?->people;

        $this->canEdit = (bool) ($user && $person && $this->project->people()
            ->where('people_id', $person->id)
            ->where(function ($q) {
                $q->whereIn('role', ['Principal Investigator', 'Supervisor'])
                    ->orWhere('permission', 'admin');
            })
            ->exists());
        $this->canManageSubProjects = (bool) ($user && ProjectPermission::canManageSubProjects($user, (int) $this->project->id));
        $this->canDeleteProject = (bool) ($user && ProjectPermission::canDeleteProject($user, (int) $this->project->id));
        $this->subProjectTypeOptions = $this->project->subProjects
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->metrics = $metricsService->getMetricsForProject($this->project);
    }

    public function render()
    {
        return view('livewire.project-profile', [
            'project' => $this->project,
            'canEdit' => $this->canEdit,
            'canManageSubProjects' => $this->canManageSubProjects,
            'canDeleteProject' => $this->canDeleteProject,
            'subProjectTypeOptions' => $this->subProjectTypeOptions,
            'metrics' => $this->metrics,
        ]);
    }
}
