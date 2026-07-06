<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnnouncementsAdminController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::query()
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $visibilityRules = $this->parseVisibilityRules($request->input('visibility_rules'));

        Announcement::query()->create([
            'type' => $request->string('type'),
            'title' => $request->string('title'),
            'message' => $request->string('message'),
            'starts_at' => $request->input('starts_at'),
            'ends_at' => $request->input('ends_at'),
            'visibility' => $request->string('visibility'),
            'visibility_rules' => $visibilityRules,
            'git_commit_hash' => $request->input('git_commit_hash'),
            'git_commit_message' => $request->input('git_commit_message'),
            'created_by_user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement created.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $visibilityRules = $this->parseVisibilityRules($request->input('visibility_rules'));

        $announcement->update([
            'type' => $request->string('type'),
            'title' => $request->string('title'),
            'message' => $request->string('message'),
            'starts_at' => $request->input('starts_at'),
            'ends_at' => $request->input('ends_at'),
            'visibility' => $request->string('visibility'),
            'visibility_rules' => $visibilityRules,
            'git_commit_hash' => $request->input('git_commit_hash'),
            'git_commit_message' => $request->input('git_commit_message'),
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement deleted.');
    }

    private function parseVisibilityRules(?string $raw): ?array
    {
        $raw = is_string($raw) ? trim($raw) : '';
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
