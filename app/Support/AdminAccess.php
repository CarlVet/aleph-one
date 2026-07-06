<?php

namespace App\Support;

use App\Models\Projects;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminAccess
{
    public static function normalizePermission(?string $permission): string
    {
        return strtolower(trim((string) $permission));
    }

    public static function hasGlobalAdminAccess(?User $user): bool
    {
        return self::normalizePermission($user?->permission) === 'admin';
    }

    public static function hasProjectAdminAccess(?User $user, ?int $projectId): bool
    {
        if (! $user || ! $user->people || $projectId === null) {
            return false;
        }

        $pivot = $user->people->projects()
            ->where('projects.id', $projectId)
            ->withPivot('permission')
            ->first()?->pivot;

        return self::normalizePermission($pivot?->permission) === 'admin';
    }

    public static function canAccessAdminArea(?User $user, ?int $projectId): bool
    {
        return self::hasGlobalAdminAccess($user) || self::hasProjectAdminAccess($user, $projectId);
    }

    /**
     * Whether two-factor authentication is mandatory for this user:
     * global admins and anyone who is an admin on at least one project.
     */
    public static function requiresTwoFactor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (self::hasGlobalAdminAccess($user)) {
            return true;
        }

        if (! $user->people) {
            return false;
        }

        return $user->people->projects()
            ->wherePivot('permission', 'admin')
            ->exists();
    }

    /**
     * Whether the user must complete a second factor this session: mandatory
     * users (admins / project admins) and anyone who has enabled an
     * authenticator app voluntarily.
     */
    public static function mustProveTwoFactor(?User $user): bool
    {
        return self::requiresTwoFactor($user) || ($user && $user->hasConfirmedTwoFactor());
    }

    /**
     * @return Collection<int, User>
     */
    public static function adminsForProject(int $projectId): Collection
    {
        $globalAdminIds = User::query()
            ->whereRaw('lower(permission) = ?', ['admin'])
            ->pluck('id')
            ->all();

        $project = Projects::query()
            ->with(['people.users'])
            ->find($projectId);

        $projectAdminIds = [];
        if ($project) {
            $projectAdminIds = $project->people
                ->filter(fn ($person) => self::normalizePermission($person->pivot?->permission) === 'admin')
                ->pluck('users.id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $adminIds = array_values(array_unique(array_merge($globalAdminIds, $projectAdminIds)));

        if ($adminIds === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $adminIds)
            ->get();
    }
}
