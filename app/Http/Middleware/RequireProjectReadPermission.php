<?php

namespace App\Http\Middleware;

use App\Support\ProjectPermission;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireProjectReadPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $person = $user->people;

        if (! $person) {
            return $this->deny($request);
        }

        $projectId = $this->resolveProjectId($request);

        if ($projectId === null) {
            return $next($request);
        }

        $project = $person->projects()
            ->withPivot('permission', 'module_permissions')
            ->where('projects.id', $projectId)
            ->first();

        if (! $project || ! $project->pivot) {
            return $this->deny($request);
        }

        $module = ProjectPermission::detectModule($request);

        if ($module !== null && ! ProjectPermission::canView($user, $projectId, $module)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function resolveProjectId(Request $request): ?int
    {
        $routeProject = $request->route('project');

        if ($routeProject) {
            if (is_object($routeProject) && isset($routeProject->id)) {
                return (int) $routeProject->id;
            }

            if (is_numeric($routeProject)) {
                return (int) $routeProject;
            }
        }

        $selectedProjectId = session('selected_project_id');

        return $selectedProjectId === null ? null : (int) $selectedProjectId;
    }

    private function deny(Request $request): Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'You do not have permission to view this area in the selected project.',
            ], 403);
        }

        abort(403, 'You do not have permission to view this area in the selected project.');
    }
}
