<?php

namespace App\Http\Controllers;

use App\Enums\ParasiteStatus;
use App\Http\Requests\UpdateParasiteStatusRequest;
use App\Models\Parasites;
use App\Support\ProjectPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ParasiteStatusController extends Controller
{
    public function update(UpdateParasiteStatusRequest $request, Parasites $parasite): JsonResponse
    {
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();

        if ($parasite->projects_id !== $projectId) {
            return response()->json(['message' => 'You are not authorized to update this parasite.'], 403);
        }

        if (! $user || ! ProjectPermission::canEditOrDelete($user, $projectId, (int) $parasite->people_id, 'parasite_samples')) {
            return response()->json(['message' => 'You do not have permission to update this parasite status.'], 403);
        }

        $status = ParasiteStatus::from($request->validated('status'));
        $parasite->update(['status' => $status]);

        return response()->json([
            'status' => $status->value,
            'label' => $status->label(),
            'badge_classes' => $status->badgeClasses(),
        ]);
    }
}
