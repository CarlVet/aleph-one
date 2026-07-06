<?php

namespace Tests\Feature;

use App\Livewire\PublishData;
use App\Mail\PublicationReviewDecisionEmail;
use App\Models\EnvironmentSamples;
use App\Models\EnvironmentSampleTypes;
use App\Models\People;
use App\Models\Projects;
use App\Models\PublicationReviewRequest;
use App\Models\Tubes;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PublicationReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_user_submission_creates_publication_review_request_instead_of_publishing_directly(): void
    {
        $project = $this->createProject('PUB-REQ');
        [$requester] = $this->createUserForProject($project, 'editor', 'viewer', 'requester');
        [$admin] = $this->createUserForProject($project, 'Admin', 'viewer', 'admin');
        $tube = $this->createPrivateTube($project, $requester->people, 'PUB-REQ-TB-1');

        $this->actingAs($requester);
        $this->withSession(['selected_project_id' => $project->id]);

        Livewire::test(PublishData::class)
            ->set('dataType', 'tubes')
            ->set('selectedItems', [$tube->id])
            ->set('submissionMessage', 'Please review this record.')
            ->call('submitSelectedForReview');

        $reviewRequest = PublicationReviewRequest::query()->first();

        $this->assertNotNull($reviewRequest);
        $this->assertSame('pending', $reviewRequest->status);
        $this->assertSame('tubes', $reviewRequest->data_type);
        $this->assertSame($requester->id, $reviewRequest->requester_user_id);
        $this->assertDatabaseHas('publication_review_request_items', [
            'publication_review_request_id' => $reviewRequest->id,
            'reviewable_type' => Tubes::class,
            'reviewable_id' => $tube->id,
            'code' => 'PUB-REQ-TB-1',
        ]);
        $this->assertDatabaseHas('tubes', [
            'id' => $tube->id,
            'is_private' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'publication_review_submitted',
            'projects_id' => $project->id,
        ]);
    }

    public function test_admin_can_approve_publication_review_request_and_requester_is_notified(): void
    {
        Mail::fake();

        $project = $this->createProject('PUB-APP');
        [$requester] = $this->createUserForProject($project, 'editor', 'viewer', 'requester');
        [$admin] = $this->createUserForProject($project, 'Admin', 'viewer', 'admin');
        $tube = $this->createPrivateTube($project, $requester->people, 'PUB-APP-TB-1');

        $reviewRequest = PublicationReviewRequest::query()->create([
            'projects_id' => $project->id,
            'requester_user_id' => $requester->id,
            'data_type' => 'tubes',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $reviewRequest->items()->create([
            'reviewable_type' => Tubes::class,
            'reviewable_id' => $tube->id,
            'code' => $tube->code,
            'summary' => 'for direct testing',
        ]);

        $token = 'test-token';
        $this->actingAs($admin)
            ->withSession(['selected_project_id' => $project->id, '_token' => $token])
            ->post(route('admin.publication-reviews.decide', $reviewRequest), [
                'decision' => 'approved',
                'reviewer_message' => 'Everything looks good.',
                '_token' => $token,
            ])
            ->assertRedirect(route('admin.publication-reviews.show', $reviewRequest));

        $this->assertDatabaseHas('publication_review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'approved',
            'reviewer_user_id' => $admin->id,
            'reviewer_message' => 'Everything looks good.',
        ]);
        $this->assertDatabaseHas('tubes', [
            'id' => $tube->id,
            'is_private' => false,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'type' => 'publication_review_approved',
            'projects_id' => $project->id,
        ]);

        Mail::assertSent(PublicationReviewDecisionEmail::class, function (PublicationReviewDecisionEmail $mail) use ($requester): bool {
            return $mail->hasTo($requester->email);
        });
    }

    public function test_user_can_load_changes_requested_submission_for_resubmission(): void
    {
        $project = $this->createProject('PUB-RESUB');
        [$requester] = $this->createUserForProject($project, 'editor', 'viewer', 'requester');
        $tube = $this->createPrivateTube($project, $requester->people, 'PUB-RESUB-TB-1');

        $reviewRequest = PublicationReviewRequest::query()->create([
            'projects_id' => $project->id,
            'requester_user_id' => $requester->id,
            'data_type' => 'tubes',
            'status' => 'changes_requested',
            'reviewer_message' => 'Please update the metadata and resubmit.',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);

        $reviewRequest->items()->create([
            'reviewable_type' => Tubes::class,
            'reviewable_id' => $tube->id,
            'code' => $tube->code,
            'summary' => 'for resubmission testing',
        ]);

        $this->actingAs($requester);
        $this->withSession(['selected_project_id' => $project->id]);

        Livewire::test(PublishData::class)
            ->call('loadRequestForResubmission', $reviewRequest->id)
            ->assertSet('dataType', 'tubes')
            ->assertSet('selectedItems', [$tube->id]);
    }

    public function test_guest_mode_user_sees_project_mode_required_page_for_publish_route(): void
    {
        $project = $this->createProject('PUB-GUEST');
        [$requester] = $this->createUserForProject($project, 'editor', 'viewer', 'requester');

        $this->actingAs($requester)
            ->get('/publish')
            ->assertForbidden()
            ->assertSee('Select a project first')
            ->assertSee('Go to My Projects');
    }

    private function createProject(string $code): Projects
    {
        return Projects::query()->create([
            'code' => $code,
            'type' => 'Research',
            'title' => 'Publication workflow '.$code,
            'status' => 'active',
        ]);
    }

    /**
     * @return array{0: User, 1: People}
     */
    private function createUserForProject(Projects $project, string $projectPermission, string $globalPermission, string $prefix): array
    {
        $person = People::query()->create([
            'first_name' => ucfirst($prefix),
            'last_name' => ucfirst($projectPermission),
            'email' => $prefix.'-'.$project->code.'@example.test',
        ]);

        $user = User::query()->create([
            'people_id' => $person->id,
            'email' => $person->email,
            'password' => 'password',
            'permission' => $globalPermission,
            'email_verified_at' => now(),
        ]);

        $project->people()->attach($person->id, [
            'role' => 'Team member',
            'permission' => $projectPermission,
            'date_joined' => now()->toDateString(),
        ]);

        return [$user, $person];
    }

    private function createPrivateTube(Projects $project, People $person, string $tubeCode): Tubes
    {
        $sampleType = EnvironmentSampleTypes::query()->create([
            'name' => 'Water',
            'category' => 'Liquid',
        ]);

        $environmentSample = EnvironmentSamples::query()->create([
            'code' => $tubeCode.'-ENV',
            'environment_sample_types_id' => $sampleType->id,
            'date_collected' => now()->toDateString(),
            'people_id' => $person->id,
            'projects_id' => $project->id,
        ]);

        return Tubes::query()->create([
            'code' => $tubeCode,
            'tubes_content_type' => EnvironmentSamples::class,
            'tubes_content_id' => $environmentSample->id,
            'purpose' => 'for direct testing',
            'projects_id' => $project->id,
            'is_private' => true,
        ]);
    }
}
