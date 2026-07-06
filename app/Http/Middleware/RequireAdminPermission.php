<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $selectedProjectId = session('selected_project_id');
        $projectId = $selectedProjectId !== null ? (int) $selectedProjectId : null;

        if (! AdminAccess::canAccessAdminArea($user, $projectId)) {
            abort(403);
        }

        return $next($request);
    }
}
