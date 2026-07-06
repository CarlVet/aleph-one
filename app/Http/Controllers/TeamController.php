<?php

namespace App\Http\Controllers;

use App\Models\Countries;
use App\Models\Departments;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SubProject;
use App\Models\User;
use App\Support\ProjectPermission;
use App\Support\TeamPageData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    public function index()
    {
        if (! session()->has('selected_project_id')) {
            return redirect()->route('profile.projects')
                ->with('error', 'Please select a project to view the team.');
        }

        $project = Projects::with([
            'people.users',
            'people.organizations',
            'subProjects.people',
        ])->findOrFail(session('selected_project_id'));

        $teamMembers = $project->people->groupBy(function ($person) {
            if ($person->pivot && isset($person->pivot->role)) {
                return $person->pivot->role;
            }

            return 'Member';
        });

        $user = Auth::user();
        $canEditTeam = false;
        if ($user && $user->people) {
            $pivot = $user->people->projects()
                ->where('projects.id', $project->id)
                ->withPivot('permission')
                ->first()?->pivot;
            if ($pivot && $pivot->permission === 'admin') {
                $canEditTeam = true;
            }
        }

        $teamPageData = TeamPageData::forProject($project->people, $user?->people?->id);

        return view('team', [
            'project' => $project,
            'teamMembers' => $teamMembers,
            'organization_types' => [
                'Government Agency' => 'Government Agency',
                'Research Institute' => 'Research Institute',
                'University' => 'University',
                'Non-Profit Organization' => 'Non-Profit Organization',
                'Private Company' => 'Private Company',
                'Zoo' => 'Zoo',
                'Wildlife Sanctuary' => 'Wildlife Sanctuary',
                'Veterinary Clinic' => 'Veterinary Clinic',
                'Laboratory' => 'Laboratory',
                'Conservation Organization' => 'Conservation Organization',
                'National Park' => 'National Park',
                'Game Reserve' => 'Game Reserve',
                'Museum' => 'Museum',
                'Hospital' => 'Hospital',
                'Pharmaceutical Company' => 'Pharmaceutical Company',
                'Biotechnology Company' => 'Biotechnology Company',
            ],
            'countries' => Countries::all(),
            'organizations' => Organizations::all(),
            'departments' => Departments::all(),
            'canEditTeam' => $canEditTeam,
            'modulePermissionOptions' => ProjectPermission::moduleOptions(),
            'subProjects' => $project->subProjects,
            'permissionStyles' => $teamPageData['permissionStyles'],
            'allMembers' => $teamPageData['allMembers'],
            'permissionFolders' => $teamPageData['permissionFolders'],
            'roleFolders' => $teamPageData['roleFolders'],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $project = Projects::findOrFail(session('selected_project_id'));
        if (! $this->currentUserCanEditTeam($user, $project)) {
            return redirect()->back()->with('error', 'You do not have permission to add or edit team members.');
        }

        $request->validate([
            'role' => 'required|string|max:255',
            'permission' => 'required|string|in:viewer,editor,admin',
            'module_permissions' => 'nullable|array',
            'sub_project_ids' => 'nullable|array',
            'sub_project_ids.*' => 'integer|exists:sub_projects,id',
            'date_joined' => 'required|date',
        ]);

        if ($request->filled('person_id')) {
            $person = People::findOrFail($request->input('person_id'));
        } else {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_birth' => 'nullable|date',
                'departments_id' => 'nullable|exists:departments,id',
                'organizations_id' => 'required|exists:organizations,id',
                'email' => 'nullable|email|unique:people,email',
                'role' => 'required|string|max:255',
            ]);

            try {
                $person = People::create([
                    'title' => $validated['title'] ?? null,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'date_birth' => $validated['date_birth'] ?? null,
                    'departments_id' => $validated['departments_id'] ?? null,
                    'organizations_id' => $validated['organizations_id'],
                    'email' => $validated['email'] ?? null,
                    'job' => $validated['job'] ?? null,
                    'pic_path' => $validated['pic_path'] ?? null,
                    'orcid' => $validated['orcid'] ?? null,
                ]);
            } catch (\Exception $e) {
                return redirect()->back()->withInput()->with('error', 'An error occurred while adding the team member.');
            }
        }

        $wasAlreadyMember = $project->people()
            ->where('people.id', $person->id)
            ->exists();

        $permission = (string) $request->input('permission');
        $modulePermissions = ProjectPermission::encodeModulePermissionsForStorage(
            $permission,
            (array) $request->input('module_permissions', [])
        );

        $project->people()->syncWithoutDetaching([
            $person->id => [
                'role' => $request->input('role'),
                'permission' => $permission,
                'module_permissions' => $modulePermissions,
                'date_joined' => $request->input('date_joined'),
            ],
        ]);

        $this->syncMemberSubProjects($project, (int) $person->id, (array) $request->input('sub_project_ids', []));

        if (! $wasAlreadyMember) {
            $this->notifyProjectMemberAdded($project, $person, (string) $request->input('role'));
        }

        return back()->with('success', 'Team member added successfully.');
    }

    public function updateRole(Request $request, $personId)
    {
        $user = Auth::user();
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        if (! $this->currentUserCanEditTeam($user, $project)) {
            return back()->with('error', 'You do not have permission to update roles.');
        }

        $role = $request->input('edit_role');
        $project->people()->updateExistingPivot($personId, ['role' => $role]);

        return back()->with('success', 'Role updated successfully.');
    }

    public function updatePermission(Request $request, $personId)
    {
        $user = Auth::user();
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        if (! $this->currentUserCanEditTeam($user, $project)) {
            return back()->with('error', 'You do not have permission to update permissions.');
        }

        $permission = (string) $request->input('permission');

        if ($request->has('module_permissions')) {
            $modulePermissions = ProjectPermission::encodeModulePermissionsForStorage(
                $permission,
                (array) $request->input('module_permissions', [])
            );
        } else {
            $modulePermissions = json_encode([]);
        }

        $project->people()->updateExistingPivot($personId, [
            'permission' => $permission,
            'module_permissions' => $modulePermissions,
        ]);

        return back()->with('success', 'Permission updated successfully.');
    }

    public function updateModulePermissions(Request $request, $personId)
    {
        $user = Auth::user();
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        if (! $this->currentUserCanEditTeam($user, $project)) {
            return back()->with('error', 'You do not have permission to update module permissions.');
        }

        $member = $project->people()
            ->where('people.id', $personId)
            ->withPivot('permission')
            ->firstOrFail();

        $permission = (string) ($member->pivot->permission ?? 'viewer');
        $modulePermissions = ProjectPermission::encodeModulePermissionsForStorage(
            $permission,
            (array) $request->input('module_permissions', [])
        );

        $project->people()->updateExistingPivot($personId, [
            'module_permissions' => $modulePermissions,
        ]);

        return back()->with('success', 'Module permissions updated successfully.');
    }

    public function updateSubProjects(Request $request, $personId)
    {
        $user = Auth::user();
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        if (! $this->currentUserCanEditTeam($user, $project)) {
            return back()->with('error', 'You do not have permission to update sub-project assignments.');
        }

        $validated = $request->validate([
            'sub_project_ids' => 'nullable|array',
            'sub_project_ids.*' => 'integer|exists:sub_projects,id',
        ]);

        $this->syncMemberSubProjects($project, (int) $personId, (array) ($validated['sub_project_ids'] ?? []));

        return back()->with('success', 'Sub-project assignments updated successfully.');
    }

    public function updateDateJoined(Request $request, $personId)
    {
        $user = Auth::user();
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        if (! $this->currentUserCanEditTeam($user, $project)) {
            return back()->with('error', 'You do not have permission to update date joined.');
        }

        $dateJoined = $request->input('date_joined');
        $project->people()->updateExistingPivot($personId, ['date_joined' => $dateJoined]);

        return back()->with('success', 'Date joined updated successfully.');
    }

    public function detach(Request $request, $personId)
    {
        $user = Auth::user();
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        $pivot = $user?->people?->projects()->where('projects.id', $projectId)->withPivot('permission')->first()?->pivot;
        if (! $pivot || $pivot->permission !== 'admin' || $user->people->id == $personId) {
            return back()->with('error', 'You do not have permission to remove this member.');
        }

        $project->people()->detach($personId);

        SubProject::query()
            ->where('project_id', $project->id)
            ->whereHas('people', fn ($query) => $query->where('people.id', $personId))
            ->get()
            ->each(function (SubProject $subProject) use ($personId): void {
                $subProject->people()->detach($personId);
            });

        return back()->with('success', 'Team member removed from project.');
    }

    public function checkEmail(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email === '') {
            return response()->json(['found' => false]);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $person = People::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $person && $user?->people) {
            $person = $user->people;
        }

        if ($person) {
            return response()->json([
                'found' => true,
                'person' => [
                    'id' => $person->id,
                    'title' => $person->title,
                    'first_name' => $person->first_name,
                    'last_name' => $person->last_name,
                    'email' => $person->email ?? $user?->email,
                ],
                'user' => $user,
            ]);
        }

        return response()->json(['found' => false]);
    }

    private function currentUserCanEditTeam($user, Projects $project): bool
    {
        if (! $user || ! $user->people) {
            return false;
        }

        $pivot = $user->people->projects()
            ->where('projects.id', $project->id)
            ->withPivot('permission')
            ->first()?->pivot;

        return $pivot && $pivot->permission === 'admin';
    }

    /**
     * @param  array<int, mixed>  $subProjectIds
     */
    private function syncMemberSubProjects(Projects $project, int $personId, array $subProjectIds): void
    {
        $eligibleSubProjectIds = SubProject::query()
            ->where('project_id', $project->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $requestedIds = collect($subProjectIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => in_array($id, $eligibleSubProjectIds, true))
            ->unique()
            ->values()
            ->all();

        foreach ($eligibleSubProjectIds as $subProjectId) {
            $subProject = SubProject::query()->find($subProjectId);
            if (! $subProject) {
                continue;
            }

            if (in_array($subProjectId, $requestedIds, true)) {
                $subProject->people()->syncWithoutDetaching([$personId]);
            } else {
                $subProject->people()->detach($personId);
            }
        }
    }

    private function notifyProjectMemberAdded(Projects $project, People $person, string $role): void
    {
        $targetUser = $person->users;

        if (! $targetUser || $targetUser->id === Auth::id()) {
            return;
        }

        NotificationController::createForUser(
            $targetUser,
            'project_invitation',
            'Added to project team',
            'You were added to project "'.($project->title ?: $project->code).'" as '.$role.'.',
            route('profile.projects'),
            $project->id
        );
    }
}
