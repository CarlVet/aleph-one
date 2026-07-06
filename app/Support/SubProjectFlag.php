<?php

namespace App\Support;

use App\Models\SubProject;
use App\Models\SubProjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SubProjectFlag
{
    private static function isProjectAdmin(?User $user, int $projectId): bool
    {
        return $user !== null && ProjectPermission::canAssignRegistrar($user, $projectId);
    }

    public static function optionsForUser(?User $user, int $projectId): Collection
    {
        if (! $user || ! $user->people) {
            return collect();
        }

        if (self::isProjectAdmin($user, $projectId)) {
            return SubProject::query()
                ->where('project_id', $projectId)
                ->orderBy('code')
                ->get();
        }

        return SubProject::query()
            ->where('project_id', $projectId)
            ->whereHas('people', function ($query) use ($user) {
                $query->where('people.id', $user->people->id);
            })
            ->orderBy('code')
            ->get();
    }

    public static function requiresSelection(?User $user, int $projectId): bool
    {
        if (self::isProjectAdmin($user, $projectId)) {
            return false;
        }

        return self::optionsForUser($user, $projectId)->isNotEmpty();
    }

    public static function defaultSelectionForUser(?User $user, int $projectId): ?int
    {
        if (self::isProjectAdmin($user, $projectId)) {
            return null;
        }

        $options = self::optionsForUser($user, $projectId);

        if ($options->isEmpty()) {
            return null;
        }

        return (int) $options->first()->id;
    }

    public static function isSelectableByUser(?User $user, int $projectId, ?int $subProjectId): bool
    {
        if (! $user || ! $user->people) {
            return $subProjectId === null;
        }

        if (self::isProjectAdmin($user, $projectId)) {
            if (! $subProjectId) {
                return true;
            }

            return SubProject::query()
                ->where('project_id', $projectId)
                ->whereKey($subProjectId)
                ->exists();
        }

        $assignedSubProjects = self::optionsForUser($user, $projectId);

        if ($assignedSubProjects->isNotEmpty()) {
            if (! $subProjectId) {
                return false;
            }

            return $assignedSubProjects->contains('id', $subProjectId);
        }

        if (! $subProjectId) {
            return true;
        }

        return SubProject::query()
            ->where('project_id', $projectId)
            ->whereKey($subProjectId)
            ->exists();
    }

    public static function assign(Model $model, ?int $subProjectId): void
    {
        $type = $model::class;
        $id = (int) $model->id;

        if (! $subProjectId) {
            SubProjectAssignment::query()
                ->where('assignable_type', $type)
                ->where('assignable_id', $id)
                ->delete();

            return;
        }

        SubProjectAssignment::query()->updateOrCreate(
            [
                'assignable_type' => $type,
                'assignable_id' => $id,
            ],
            [
                'sub_project_id' => $subProjectId,
            ]
        );
    }
}
