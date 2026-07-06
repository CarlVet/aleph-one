<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Models\SubProject;
use App\Support\ProjectPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubProjectsController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $projectId = (int) $request->input('project_id');
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sub_projects', 'code')->where(fn ($query) => $query->where('project_id', $projectId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:active,completed,archived'],
            'date_started' => ['nullable', 'date'],
            'date_end_intended' => ['nullable', 'date', 'after_or_equal:date_started'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_started'],
            'people_ids' => ['nullable', 'array'],
            'people_ids.*' => ['integer', 'exists:people,id'],
        ]);

        $user = Auth::user();
        $projectId = (int) $data['project_id'];
        if (! $user || ! ProjectPermission::canAssignRegistrar($user, $projectId)) {
            abort(403);
        }

        $project = Projects::query()
            ->with('people:id')
            ->findOrFail($projectId);

        $subProject = SubProject::create([
            'project_id' => $projectId,
            'code' => trim((string) $data['code']),
            'name' => trim((string) $data['name']),
            'title' => isset($data['title']) ? trim((string) $data['title']) : null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'date_started' => $data['date_started'] ?? null,
            'date_end_intended' => $data['date_end_intended'] ?? null,
            'date_end' => $data['date_end'] ?? null,
        ]);

        $eligiblePeopleIds = $project->people->pluck('id')->all();
        $requestedPeopleIds = collect($data['people_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
        $validPeopleIds = array_values(array_intersect($requestedPeopleIds, $eligiblePeopleIds));
        if (empty($validPeopleIds) && $user->people) {
            $validPeopleIds = [(int) $user->people->id];
        }
        if (! empty($validPeopleIds)) {
            $subProject->people()->sync($validPeopleIds);
        }

        return back()->with('success', 'Sub-project created successfully.');
    }

    public function update(Request $request, SubProject $subProject): RedirectResponse
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sub_projects', 'code')
                    ->where(fn ($query) => $query->where('project_id', (int) $subProject->project_id))
                    ->ignore($subProject->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:active,completed,archived'],
            'date_started' => ['nullable', 'date'],
            'date_end_intended' => ['nullable', 'date', 'after_or_equal:date_started'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_started'],
            'people_ids' => ['nullable', 'array'],
            'people_ids.*' => ['integer', 'exists:people,id'],
        ]);

        $user = Auth::user();
        if (! $user || ! ProjectPermission::canAssignRegistrar($user, (int) $subProject->project_id)) {
            abort(403);
        }

        $project = Projects::query()
            ->with('people:id')
            ->findOrFail((int) $subProject->project_id);

        $subProject->update([
            'code' => trim((string) $data['code']),
            'name' => trim((string) $data['name']),
            'title' => isset($data['title']) ? trim((string) $data['title']) : null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'date_started' => $data['date_started'] ?? null,
            'date_end_intended' => $data['date_end_intended'] ?? null,
            'date_end' => $data['date_end'] ?? null,
        ]);

        $eligiblePeopleIds = $project->people->pluck('id')->all();
        $requestedPeopleIds = collect($data['people_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
        $validPeopleIds = array_values(array_intersect($requestedPeopleIds, $eligiblePeopleIds));
        $subProject->people()->sync($validPeopleIds);

        return back()->with('success', 'Sub-project updated successfully.');
    }

    public function markComplete(Request $request, SubProject $subProject): JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! ProjectPermission::canAssignRegistrar($user, (int) $subProject->project_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'date_end' => ['required', 'date'],
        ]);

        $dateEnd = (string) $validated['date_end'];
        if ($subProject->date_started && $dateEnd < $subProject->date_started) {
            return response()->json([
                'success' => false,
                'message' => 'Sub-project end date cannot be before the start date.',
            ], 422);
        }

        $subProject->update([
            'status' => 'completed',
            'date_end' => $dateEnd,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sub-project marked complete.',
        ]);
    }

    public function checkCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $user = Auth::user();
        $projectId = (int) $validated['project_id'];
        if (! $user || ! ProjectPermission::canAssignRegistrar($user, $projectId)) {
            abort(403);
        }

        $code = trim((string) ($validated['code'] ?? ''));
        if ($code === '') {
            return response()->json([
                'available' => true,
                'message' => null,
            ]);
        }

        $isTaken = SubProject::query()
            ->where('project_id', $projectId)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->exists();

        return response()->json([
            'available' => ! $isTaken,
            'message' => $isTaken ? 'This sub-project code is already being used in this project.' : null,
        ]);
    }

    public function destroy(SubProject $subProject): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! ProjectPermission::canDeleteProject($user, (int) $subProject->project_id)) {
            abort(403);
        }

        $subProject->delete();

        return back()->with('success', 'Sub-project deleted successfully.');
    }
}
