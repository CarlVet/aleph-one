<?php

namespace Tests\Feature;

use App\Models\People;
use App\Models\Projects;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTeamNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_user_gets_project_notification_when_added_to_team(): void
    {
        $project = Projects::query()->create([
            'code' => 'TEAM-NOTIF-1',
            'type' => 'Research',
            'title' => 'Team notification project',
            'status' => 'active',
        ]);

        [$adminUser, $adminPerson] = $this->createUserWithPerson('admin@example.com');
        [$memberUser, $memberPerson] = $this->createUserWithPerson('member@example.com');

        $project->people()->attach($adminPerson->id, [
            'role' => 'Principal Investigator',
            'permission' => 'admin',
            'date_joined' => now()->toDateString(),
        ]);

        $token = 'project-team-notification-token';

        $this->actingAs($adminUser)
            ->withSession([
                'selected_project_id' => $project->id,
                '_token' => $token,
            ])
            ->post(route('team.store'), [
                '_token' => $token,
                'person_id' => $memberPerson->id,
                'role' => 'Data manager',
                'permission' => 'viewer',
                'date_joined' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $memberUser->id,
            'type' => 'project_invitation',
            'projects_id' => $project->id,
        ]);

        $this->actingAs($memberUser)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'project_invitation',
                'user_id' => $memberUser->id,
                'projects_id' => $project->id,
            ]);
    }

    /**
     * @return array{0: User, 1: People}
     */
    private function createUserWithPerson(string $email): array
    {
        $person = People::query()->create([
            'first_name' => ucfirst(strtok($email, '@')),
            'last_name' => 'User',
            'email' => $email,
        ]);

        $user = User::query()->create([
            'people_id' => $person->id,
            'email' => $email,
            'password' => bcrypt('password'),
            'permission' => 'Guest',
        ]);

        return [$user, $person];
    }
}
