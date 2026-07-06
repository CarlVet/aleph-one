<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Mail\PublicationReviewDecisionEmail;
use App\Models\Projects;
use App\Models\PublicationReviewRequest;
use App\Support\AdminAccess;
use App\Support\PublicationReviewRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PublicationReviewAdminController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $selectedProjectId = session('selected_project_id');
        $selectedProjectId = $selectedProjectId !== null ? (int) $selectedProjectId : null;
        $canSeeAllProjects = AdminAccess::hasGlobalAdminAccess($user);
        $status = (string) $request->query('status', 'pending');
        $projectFilter = $canSeeAllProjects ? (int) $request->integer('project') : ($selectedProjectId ?? 0);

        $query = PublicationReviewRequest::query()
            ->with(['project', 'requester.people', 'reviewer.people'])
            ->withCount('items')
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('submitted_at');

        if (! $canSeeAllProjects) {
            abort_if($selectedProjectId === null, 403);
            $query->where('projects_id', $selectedProjectId);
        } elseif ($projectFilter > 0) {
            $query->where('projects_id', $projectFilter);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('admin.publication-reviews.index', [
            'reviewRequests' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'statusOptions' => $this->statusOptions(),
            'projects' => $canSeeAllProjects ? Projects::query()->orderBy('code')->get() : collect(),
            'projectFilter' => $projectFilter,
            'canSeeAllProjects' => $canSeeAllProjects,
        ]);
    }

    public function show(PublicationReviewRequest $publicationReviewRequest): View
    {
        $this->authorizeRequest($publicationReviewRequest);

        $publicationReviewRequest->load([
            'project',
            'requester.people',
            'reviewer.people',
            'items.reviewable',
        ]);

        return view('admin.publication-reviews.show', [
            'reviewRequest' => $publicationReviewRequest,
            'statusOptions' => $this->statusOptions(),
            'statusBadges' => $this->statusBadges(),
        ]);
    }

    public function decide(Request $request, PublicationReviewRequest $publicationReviewRequest): RedirectResponse
    {
        $this->authorizeRequest($publicationReviewRequest);

        if ($publicationReviewRequest->status !== 'pending') {
            return redirect()
                ->route('admin.publication-reviews.show', $publicationReviewRequest)
                ->with('swal', [
                    'icon' => 'error',
                    'title' => 'Review unavailable',
                    'text' => 'Only pending requests can be reviewed.',
                ]);
        }

        $validated = $request->validate([
            'decision' => 'required|string|in:approved,changes_requested,rejected',
            'reviewer_message' => 'nullable|string|max:5000',
        ]);

        $reviewer = Auth::user();
        $publishedCount = 0;

        DB::transaction(function () use ($publicationReviewRequest, $validated, $reviewer, &$publishedCount): void {
            if ($validated['decision'] === 'approved') {
                $publishedCount = PublicationReviewRegistry::publishApprovedItems($publicationReviewRequest);
            }

            $publicationReviewRequest->update([
                'status' => $validated['decision'],
                'reviewer_message' => trim((string) ($validated['reviewer_message'] ?? '')) ?: null,
                'reviewer_user_id' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);
        });

        $publicationReviewRequest->loadMissing('project', 'requester.people');

        $requester = $publicationReviewRequest->requester;
        if ($requester) {
            $statusLabel = $this->statusOptions()[$publicationReviewRequest->status] ?? ucfirst(str_replace('_', ' ', $publicationReviewRequest->status));

            $message = match ($publicationReviewRequest->status) {
                'approved' => 'Your publication request was approved'.($publishedCount > 0 ? " and {$publishedCount} item(s) are now public." : '.'),
                'changes_requested' => 'Your publication request needs modifications before it can be approved.',
                'rejected' => 'Your publication request was rejected.',
                default => 'Your publication request was reviewed.',
            };

            if ($publicationReviewRequest->reviewer_message) {
                $message .= ' Admin message: '.$publicationReviewRequest->reviewer_message;
            }

            NotificationController::createForUser(
                $requester,
                'publication_review_'.$publicationReviewRequest->status,
                'Publication review update',
                $message,
                '/publish',
                $publicationReviewRequest->projects_id
            );

            if ($requester->email) {
                Mail::to($requester->email)->send(new PublicationReviewDecisionEmail(
                    $publicationReviewRequest,
                    $requester->people?->first_name ?? '',
                    $statusLabel
                ));
            }
        }

        return redirect()
            ->route('admin.publication-reviews.show', $publicationReviewRequest)
            ->with('swal', [
                'icon' => match ($publicationReviewRequest->status) {
                    'approved' => 'success',
                    'changes_requested' => 'info',
                    'rejected' => 'error',
                    default => 'success',
                },
                'title' => match ($publicationReviewRequest->status) {
                    'approved' => 'Request approved',
                    'changes_requested' => 'Modifications requested',
                    'rejected' => 'Request rejected',
                    default => 'Request updated',
                },
                'text' => match ($publicationReviewRequest->status) {
                    'approved' => 'The selected data were approved for publication.',
                    'changes_requested' => 'The requester was notified that modifications are required before publication.',
                    'rejected' => 'The requester was notified that this publication request was rejected.',
                    default => 'The publication request was updated.',
                },
            ]);
    }

    private function authorizeRequest(PublicationReviewRequest $publicationReviewRequest): void
    {
        $user = Auth::user();
        $selectedProjectId = session('selected_project_id');
        $selectedProjectId = $selectedProjectId !== null ? (int) $selectedProjectId : null;

        if (AdminAccess::hasGlobalAdminAccess($user)) {
            return;
        }

        if ($selectedProjectId === null || ! AdminAccess::hasProjectAdminAccess($user, $selectedProjectId)) {
            abort(403);
        }

        abort_if($publicationReviewRequest->projects_id !== $selectedProjectId, 403);
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'changes_requested' => 'Changes requested',
            'rejected' => 'Rejected',
            'all' => 'All',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statusBadges(): array
    {
        return [
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-green-100 text-green-800',
            'changes_requested' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];
    }
}
