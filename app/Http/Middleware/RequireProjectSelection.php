<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireProjectSelection
{
    public function handle(Request $request, Closure $next)
    {
        // If no project is selected, allow access to guest mode
        if (! session()->has('selected_project_id') || session('selected_project_id') === null) {
            // Allow access to guest mode routes
            if ($this->isGuestModeRoute($request)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This route requires a selected project. Please choose a project first.',
                    'redirect' => route('profile.projects'),
                ], 403);
            }

            return response()->view('errors.project-mode-required', [
                'redirectUrl' => route('profile.projects'),
                'requestedPath' => '/'.ltrim($request->path(), '/'),
            ], 403);
        }

        // Get the user's associated projects
        $user = Auth::user();
        $person = $user->people;
        $userProjectIds = $person->projects()->pluck('projects.id')->toArray();

        // Check if the selected project is in the user's projects
        if (! in_array(session('selected_project_id'), $userProjectIds)) {
            // Clear the invalid project selection
            session()->forget('selected_project_id');

            // If this route is allowed in guest mode, proceed after clearing selection.
            if ($this->isGuestModeRoute($request)) {
                return $next($request);
            }

            return redirect()->route('profile.projects')
                ->with('error', 'You do not have access to the selected project.');
        }

        return $next($request);
    }

    private function isGuestModeRoute(Request $request): bool
    {
        if (! $request->isMethod('get')) {
            return false;
        }

        $guestModeRoutes = [
            'experiments.list',
            'experiments.dashboard.map-points',
            'animals.list',
            'humans.list',
            'environments.list',
            'parasites.list',
            'nucleic.list',
            'cultures.list',
            'pools.list',
            'guest.experiments',
            'guest.animal-samples',
            'guest.human-samples',
            'guest.parasite-samples',
            'guest.nucleic-acids',
            'guest.sequences',
            'guest.cultures',
            'guest.pools',
            'guest.parasites',
            'guest.meta.animal',
            'guest.meta.human',
            'guest.meta.environment',
            'guest.meta.parasite',
            'guest.experiments.human',
            'guest.experiments.animal',
            'guest.experiments.environment',
            'guest.experiments.parasite',
            'guest.experiments.culture',
            'guest.experiments.pool',
            'guest.experiments.nucleic',
            'guest.parasite-samples.human',
            'guest.parasite-samples.animal',
            'guest.parasite-samples.environment',
            'guest.nucleic-acids.human',
            'guest.nucleic-acids.animal',
            'guest.nucleic-acids.environment',
            'guest.nucleic-acids.parasite',
            'guest.nucleic-acids.culture',
            'guest.nucleic-acids.pool',
            'guest.cultures.human',
            'guest.cultures.animal',
            'guest.cultures.environment',
            'guest.cultures.parasite',
            'guest.cultures.pool',
            'guest.sequences.human',
            'guest.sequences.animal',
            'guest.sequences.environment',
            'guest.sequences.parasite',
            'guest.sequences.culture',
            'guest.sequences.pool',
        ];

        // Also check if the route path matches guest mode patterns
        $path = $request->path();
        $guestModePaths = [
            'guest/experiments',
            'guest/animal-samples',
            'guest/human-samples',
            'guest/parasite-samples',
            'guest/nucleic-acids',
            'guest/sequences',
            'guest/cultures',
            'guest/pools',
            'guest/parasites',
            'guest/meta/animal',
            'guest/meta/human',
            'guest/meta/environment',
            'guest/meta/parasite',
            // Regular list/dashboard routes accessible in guest mode
            'samples/animals/list',
            'samples/humans/list',
            'samples/parasites/list',
            'samples/nucleic/list',
            'samples/nucleic/sequences/list',
            'samples/cultures/list',
            'samples/pools/list',
            'samples/process/list',
            'experiments/list',
            'meta/dashboard',
            'experiments/dashboard',
            'experiments/dashboard/map-points',
            'samples/animals/dashboard',
            'samples/humans/dashboard',
            'samples/parasites/dashboard',
            'samples/environment/dashboard',
            'samples/nucleic/dashboard',
            'samples/cultures/dashboard',
            'samples/animals/health/dashboard',
            'samples/animals/medication/dashboard',
            'samples/animals/vaccination/dashboard',
            'samples/pools/dashboard',
            'meta/list/animal',
            'meta/list/human',
            'meta/list/environment',
            'meta/list/parasite',
        ];

        return in_array($request->route()?->getName(), $guestModeRoutes, true)
            || in_array($path, $guestModePaths, true)
            || str_starts_with($path, 'guest/')
            || (str_starts_with($path, 'meta/list/'))
            || (str_starts_with($path, 'samples/') && (str_ends_with($path, '/list') || str_contains($path, '/dashboard')))
            || ($path === 'experiments/list')
            || ($path === 'experiments/dashboard/map-points');
    }
}
