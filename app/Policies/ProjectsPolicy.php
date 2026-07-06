<?php

namespace App\Policies;

use App\Models\Projects;
use App\Models\User;
use App\Support\ProjectPermission;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectsPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Projects $project)
    {
        // Get the user's person record
        $person = $user->people;

        if (! $person) {
            return false;
        }

        // Allow edit if the user is a PI/Supervisor OR has admin permission on the project.
        return $project->people()
            ->where('people_id', $person->id)
            ->where(function ($q) {
                $q->whereIn('role', ['Principal Investigator', 'Supervisor'])
                    ->orWhere('permission', 'admin');
            })
            ->exists();
    }

    public function delete(User $user, Projects $project): bool
    {
        return ProjectPermission::canDeleteProject($user, (int) $project->id);
    }
}
