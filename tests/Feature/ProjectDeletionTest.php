<?php

namespace Tests\Feature;

use App\Models\People;
use App\Models\Projects;
use App\Models\SubProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_project(): void
    {
        [$user, $project] = $this->createUserInProject('admin');

        SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-01',
            'name' => 'Pilot',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('profile.projects'));

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
        $this->assertDatabaseMissing('sub_projects', [
            'project_id' => $project->id,
        ]);
    }

    public function test_editor_cannot_delete_project(): void
    {
        [$user, $project] = $this->createUserInProject('editor');

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_deleting_project_clears_selected_project_session(): void
    {
        [$user, $project] = $this->createUserInProject('admin');

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('profile.projects'));

        $this->assertNull(session('selected_project_id'));
    }

    /**
     * @return array{0: User, 1: Projects}
     */
    private function createUserInProject(string $permission): array
    {
        $person = People::create([
            'first_name' => 'Test',
            'last_name' => ucfirst($permission),
            'email' => $permission.'.delete@example.test',
        ]);

        $user = User::create([
            'people_id' => $person->id,
            'email' => $permission.'.delete@example.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $project = Projects::create([
            'code' => 'PRJ-DEL-'.strtoupper(substr($permission, 0, 1)).rand(100, 999),
            'type' => 'Research',
            'title' => 'Delete project '.$permission,
            'status' => 'active',
        ]);

        $project->people()->attach($person->id, [
            'role' => 'Team member',
            'permission' => $permission,
            'date_joined' => now()->toDateString(),
        ]);

        return [$user, $project];
    }
}
