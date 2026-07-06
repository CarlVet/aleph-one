<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AnnouncementsController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $now = now();
        $announcements = Announcement::query()
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->when(! $user, function ($q) {
                $q->whereIn('visibility', ['all', 'guest']);
            }, function ($q) {
                $q->whereIn('visibility', ['all', 'authenticated']);
            })
            ->orderByDesc('created_at')
            ->take(30)
            ->get([
                'id',
                'type',
                'title',
                'message',
                'git_commit_hash',
                'git_commit_message',
                'starts_at',
                'ends_at',
                'visibility',
                'created_at',
            ]);

        $readAt = $user?->announcements_read_at;

        $payload = $announcements->map(function (Announcement $announcement) use ($readAt) {
            $read = null;
            if ($readAt !== null) {
                $read = $announcement->created_at->lte($readAt);
            }

            return [
                'id' => $announcement->id,
                'type' => $announcement->type,
                'title' => $announcement->title,
                'message' => $announcement->message,
                'git_commit_hash' => $announcement->git_commit_hash,
                'git_commit_message' => $announcement->git_commit_message,
                'starts_at' => $announcement->starts_at?->toISOString(),
                'ends_at' => $announcement->ends_at?->toISOString(),
                'visibility' => $announcement->visibility,
                'created_at' => $announcement->created_at?->toISOString(),
                'read' => $read,
            ];
        })->values();

        return response()->json($payload);
    }

    public function markAllRead(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $user->announcements_read_at = now();
        $user->save();

        return response()->json(['success' => true]);
    }
}
