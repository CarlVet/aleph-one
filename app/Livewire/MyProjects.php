<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Models\Projects;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('My Projects')]
class MyProjects extends PlainComponent
{
    use WithColumnSorting;
    use WithPagination;

    /**
     * @return array<string, string|callable>
     */
    protected function sortMap(): array
    {
        return [
            'code' => 'projects.code',
            'type' => 'projects.type',
            'title' => 'projects.title',
            'role' => fn ($q, $dir) => $q->orderByPivot('role', $dir),
            'date_started' => 'projects.date_started',
            'date_end_intended' => 'projects.date_end_intended',
            'date_end' => 'projects.date_end',
        ];
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public string $code = '';

    public string $type = '';

    public string $title = '';

    public string $role = '';

    public string $funding = '';

    public string $collaborator = '';

    public string $startDateFrom = '';

    public string $startDateTo = '';

    public string $intendedEndFrom = '';

    public string $intendedEndTo = '';

    public string $officialEndFrom = '';

    public string $officialEndTo = '';

    public function perPageOptions(): array
    {
        return [5, 10, 20];
    }

    public function openFundingModal(int $projectId): void
    {
        $user = Auth::user();
        $person = $user?->people;

        if (! $user || ! $person) {
            abort(403);
        }

        /** @var Projects|null $project */
        $project = $person->projects()
            ->where('projects.id', $projectId)
            ->with('fundings')
            ->first();

        if (! $project) {
            return;
        }

        $fundings = ($project->fundings ?? collect())
            ->map(fn ($f) => [
                'label' => $f->source ?? 'Funding',
                'url' => route('fundings.profile', $f),
            ])
            ->values()
            ->all();

        $this->dispatch('fundings-modal', fundings: $fundings);
    }

    public function openCollaboratorModal(int $projectId): void
    {
        $user = Auth::user();
        $person = $user?->people;

        if (! $user || ! $person) {
            abort(403);
        }

        /** @var Projects|null $project */
        $project = $person->projects()
            ->where('projects.id', $projectId)
            ->with('people')
            ->first();

        if (! $project) {
            return;
        }

        $collaborators = ($project->people ?? collect())
            ->map(fn ($c) => [
                'label' => $c->first_name.' '.$c->last_name,
                'url' => route('profile.show', $c->id),
                'logo' => $this->getPeopleLogoUrl($c),
            ])
            ->values()
            ->all();
        $this->dispatch('collaborators-modal', collaborators: $collaborators);
    }

    private function getPeopleLogoUrl($person): ?string
    {
        $picPath = trim($person->pic_path ?? '');
        if ($picPath === '') {
            return null;
        }

        // External URL
        if (
            str_starts_with($picPath, 'http://') ||
            str_starts_with($picPath, 'https://')
        ) {
            return $picPath;
        }

        // Public storage paths
        if (str_starts_with($picPath, 'storage/')) {
            return asset($picPath);
        }

        if (str_starts_with($picPath, '/storage/')) {
            return $picPath;
        }

        if (
            str_starts_with($picPath, 'profile-photos/') ||
            str_starts_with($picPath, 'people-photos/') ||
            str_starts_with($picPath, 'uploads/')
        ) {
            return Storage::url($picPath);
        }

        if (file_exists(public_path($picPath))) {
            return asset($picPath);
        }

        if (Storage::disk('local')->exists($picPath)) {
            return Storage::url($picPath);
        }

        return null;
    }

    public function clearFilters(): void
    {
        $this->code = '';
        $this->type = '';
        $this->title = '';
        $this->role = '';
        $this->funding = '';
        $this->collaborator = '';
        $this->startDateFrom = '';
        $this->startDateTo = '';
        $this->intendedEndFrom = '';
        $this->intendedEndTo = '';
        $this->officialEndFrom = '';
        $this->officialEndTo = '';

        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingCode(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingType(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingTitle(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingRole(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingFunding(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingCollaborator(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingStartDateFrom(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingStartDateTo(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingIntendedEndFrom(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingIntendedEndTo(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingOfficialEndFrom(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingOfficialEndTo(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatingPerPage(): void
    {
        $this->resetPage('allPage');
        $this->resetPage('activePage');
        $this->resetPage('completedWithActiveSubProjectsPage');
        $this->resetPage('completedPage');
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, $this->perPageOptions(), true)) {
            $this->perPage = 10;
        }
    }

    /**
     * @return array{all:LengthAwarePaginator,active:LengthAwarePaginator,completedWithActiveSubProjects:LengthAwarePaginator,completed:LengthAwarePaginator,projectCount:int,membershipProjectCount:int}
     */
    private function projectsData(): array
    {
        $user = Auth::user();
        $person = $user?->people;

        if (! $user || ! $person) {
            abort(403);
        }

        $base = $person->projects()
            ->with([
                'people' => function ($query) {
                    $query->select('people.*')
                        ->with('users:id,email,people_id')
                        ->withPivot('role', 'date_joined', 'permission');
                },
                'fundings',
                'fundings.recipient',
                'subProjects',
                'subProjects.people',
            ])
            ->withPivot('role', 'date_joined', 'permission');

        $collaborator = trim($this->collaborator);
        if ($collaborator !== '') {
            $like = '%'.strtolower($collaborator).'%';
            $base->whereHas('people', function ($q) use ($like): void {
                $q->where(function ($qq) use ($like): void {
                    $qq->whereRaw("lower(trim(concat(first_name, ' ', last_name))) like ?", [$like])
                        ->orWhereRaw('lower(trim(first_name)) like ?', [$like])
                        ->orWhereRaw('lower(trim(last_name)) like ?', [$like])
                        ->orWhereHas('users', function ($u) use ($like): void {
                            $u->whereRaw('lower(trim(email)) like ?', [$like]);
                        });
                });
            });
        }

        $startFrom = trim($this->startDateFrom);
        if ($this->isValidDate($startFrom)) {
            $base->whereDate('projects.date_started', '>=', $startFrom);
        }

        $startTo = trim($this->startDateTo);
        if ($this->isValidDate($startTo)) {
            $base->whereDate('projects.date_started', '<=', $startTo);
        }

        $code = trim($this->code);
        if ($code !== '') {
            $base->where('projects.code', 'like', '%'.$code.'%');
        }

        $type = trim($this->type);
        if ($type !== '') {
            $base->where('projects.type', 'like', '%'.$type.'%');
        }

        $title = trim($this->title);
        if ($title !== '') {
            $base->where('projects.title', 'like', '%'.$title.'%');
        }

        $role = trim($this->role);
        if ($role !== '') {
            $base->wherePivot('role', 'like', '%'.$role.'%');
        }

        $funding = trim($this->funding);
        if ($funding !== '') {
            $base->whereHas('fundings', function ($q) use ($funding) {
                $q->where('fundings.source', 'like', '%'.$funding.'%')
                    ->orWhere('fundings.reference', 'like', '%'.$funding.'%');
            });
        }

        $allQuery = (clone $base)
            ->when($this->isValidDate(trim($this->officialEndFrom)), function ($q): void {
                $q->whereDate('projects.date_end', '>=', trim($this->officialEndFrom));
            })
            ->when($this->isValidDate(trim($this->officialEndTo)), function ($q): void {
                $q->whereDate('projects.date_end', '<=', trim($this->officialEndTo));
            });
        $this->applySorting($allQuery, $this->sortMap(), ['projects.date_started', 'desc']);
        $all = $allQuery->paginate($this->perPage, ['projects.*'], 'allPage');

        $activeQuery = (clone $base)
            ->where(function ($q) {
                $q->where('projects.status', 'active')
                    ->orWhere(function ($qq) {
                        $qq->whereNull('projects.date_end')
                            ->where(function ($qqq) {
                                $qqq->whereNull('projects.status')
                                    ->orWhere('projects.status', '!=', 'completed');
                            });
                    });
            })
            ->when($this->isValidDate(trim($this->intendedEndFrom)), function ($q): void {
                $q->whereDate('projects.date_end_intended', '>=', trim($this->intendedEndFrom));
            })
            ->when($this->isValidDate(trim($this->intendedEndTo)), function ($q): void {
                $q->whereDate('projects.date_end_intended', '<=', trim($this->intendedEndTo));
            });
        $this->applySorting($activeQuery, $this->sortMap(), ['projects.date_started', 'desc']);
        $active = $activeQuery->paginate($this->perPage, ['projects.*'], 'activePage');

        $completedWithActiveSubProjectsQuery = (clone $base)
            ->where(function ($q) {
                $q->where('projects.status', 'completed')
                    ->orWhere(function ($qq) {
                        $qq->whereNotNull('projects.date_end')
                            ->where(function ($qqq) {
                                $qqq->whereNull('projects.status')
                                    ->orWhere('projects.status', '!=', 'active');
                            });
                    });
            })
            ->when($this->isValidDate(trim($this->officialEndFrom)), function ($q): void {
                $q->whereDate('projects.date_end', '>=', trim($this->officialEndFrom));
            })
            ->when($this->isValidDate(trim($this->officialEndTo)), function ($q): void {
                $q->whereDate('projects.date_end', '<=', trim($this->officialEndTo));
            })
            ->whereHas('subProjects', function ($q) {
                $q->where('status', 'active')
                    ->orWhere(function ($qq) {
                        $qq->whereNull('date_end')
                            ->where(function ($qqq) {
                                $qqq->whereNull('status')
                                    ->orWhere('status', '!=', 'completed');
                            });
                    });
            });
        $this->applySorting($completedWithActiveSubProjectsQuery, $this->sortMap(), ['projects.date_end', 'desc']);
        $completedWithActiveSubProjects = $completedWithActiveSubProjectsQuery->paginate($this->perPage, ['projects.*'], 'completedWithActiveSubProjectsPage');

        $completedQuery = (clone $base)
            ->where(function ($q) {
                $q->where('projects.status', 'completed')
                    ->orWhere(function ($qq) {
                        $qq->whereNotNull('projects.date_end')
                            ->where(function ($qqq) {
                                $qqq->whereNull('projects.status')
                                    ->orWhere('projects.status', '!=', 'active');
                            });
                    });
            })
            ->when($this->isValidDate(trim($this->officialEndFrom)), function ($q): void {
                $q->whereDate('projects.date_end', '>=', trim($this->officialEndFrom));
            })
            ->when($this->isValidDate(trim($this->officialEndTo)), function ($q): void {
                $q->whereDate('projects.date_end', '<=', trim($this->officialEndTo));
            })
            ->whereDoesntHave('subProjects', function ($q) {
                $q->where('status', 'active')
                    ->orWhere(function ($qq) {
                        $qq->whereNull('date_end')
                            ->where(function ($qqq) {
                                $qqq->whereNull('status')
                                    ->orWhere('status', '!=', 'completed');
                            });
                    });
            });
        $this->applySorting($completedQuery, $this->sortMap(), ['projects.date_end', 'desc']);
        $completed = $completedQuery->paginate($this->perPage, ['projects.*'], 'completedPage');

        $count = (int) ((clone $base)->count());
        $membershipProjectCount = (int) $person->projects()->count();

        return [
            'all' => $all,
            'active' => $active,
            'completedWithActiveSubProjects' => $completedWithActiveSubProjects,
            'completed' => $completed,
            'projectCount' => $count,
            'membershipProjectCount' => $membershipProjectCount,
        ];
    }

    private function isValidDate(string $value): bool
    {
        return $value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    public function render()
    {
        $user = Auth::user();
        $person = $user?->people;

        if (! $user || ! $person) {
            abort(403);
        }

        $data = $this->projectsData();

        $selectedProjectId = session('selected_project_id');
        $selectedProjectCode = $selectedProjectId
            ? Projects::query()->whereKey($selectedProjectId)->value('code')
            : null;

        return view('livewire.my-projects', [
            'person' => $person,
            'allProjects' => $data['all'],
            'activeProjects' => $data['active'],
            'completedWithActiveSubProjects' => $data['completedWithActiveSubProjects'],
            'completedProjects' => $data['completed'],
            'projectCount' => $data['projectCount'],
            'membershipProjectCount' => $data['membershipProjectCount'],
            'selectedProjectCode' => $selectedProjectCode,
        ]);
    }
}
