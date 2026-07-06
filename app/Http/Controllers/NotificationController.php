<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Projects;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = $this->visibleNotificationsQuery()
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json($notifications);
    }

    public function markAllRead()
    {
        $this->visibleNotificationsQuery()
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['success' => true]);
    }

    public function markRead(Notification $notification)
    {
        if ($this->canAccessNotification($notification)) {
            $notification->update(['read' => true]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 403);
    }

    public static function create(string $type, string $title, string $message, ?string $link = null, int|string|null $projectId = null): ?bool
    {
        $projectId = $projectId ?? session('selected_project_id');

        if (is_string($projectId) && $projectId !== '' && ! ctype_digit($projectId)) {
            $projectId = Projects::query()->where('code', $projectId)->value('id');
        }

        if (is_string($projectId) && ctype_digit($projectId)) {
            $projectId = (int) $projectId;
        }

        if (! $projectId) {
            return null;
        }

        // Get all people associated with the project with users relationship eager loaded
        $project = Projects::with(['people.users'])->find($projectId);
        if (! $project) {
            return null;
        }

        $notifications = [];
        foreach ($project->people as $person) {
            if ($person->users) {
                $notifications[] = [
                    'user_id' => $person->users->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'link' => $link,
                    'read' => false,
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Create notifications in bulk
        if (! empty($notifications)) {
            Notification::insert($notifications);
        }

        return true;
    }

    /**
     * @param  iterable<int, User>  $users
     */
    public static function createForUsers(iterable $users, string $type, string $title, string $message, ?string $link = null, int|string|null $projectId = null): ?bool
    {
        $projectId = $projectId ?? session('selected_project_id');

        if (is_string($projectId) && $projectId !== '' && ! ctype_digit($projectId)) {
            $projectId = Projects::query()->where('code', $projectId)->value('id');
        }

        if (is_string($projectId) && ctype_digit($projectId)) {
            $projectId = (int) $projectId;
        }

        if (! $projectId) {
            return null;
        }

        $notifications = [];
        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $notifications[] = [
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'read' => false,
                'projects_id' => $projectId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($notifications !== []) {
            Notification::insert($notifications);
        }

        return true;
    }

    public static function createForUser(User $user, string $type, string $title, string $message, ?string $link = null, int|string|null $projectId = null): ?bool
    {
        return self::createForUsers([$user], $type, $title, $message, $link, $projectId);
    }

    private function visibleNotificationsQuery(): Builder
    {
        $projectId = session('selected_project_id');

        return Notification::query()
            ->where('user_id', Auth::id())
            ->where(function (Builder $query) use ($projectId) {
                $query->where('type', 'project_invitation');

                if ($projectId) {
                    $query->orWhere('projects_id', $projectId);
                }
            });
    }

    private function canAccessNotification(Notification $notification): bool
    {
        if ($notification->user_id !== Auth::id()) {
            return false;
        }

        if ($notification->type === 'project_invitation') {
            return true;
        }

        return $notification->projects_id === session('selected_project_id');
    }
}
