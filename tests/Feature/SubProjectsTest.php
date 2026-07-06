<?php

namespace Tests\Feature;

use App\Models\People;
use App\Models\Projects;
use App\Models\SubProject;
use App\Models\SubProjectAssignment;
use App\Models\User;
use App\Support\SubProjectFlag;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_sub_project(): void
    {
        [$user, $project] = $this->createUserInProject('admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->post(route('sub-projects.store'), [
                'project_id' => $project->id,
                'code' => 'SP-01',
                'name' => 'Pilot site',
                'title' => 'Pilot site cohort',
                'date_started' => '2026-01-10',
                'date_end_intended' => '2026-12-31',
                'description' => 'Initial nested track',
                'people_ids' => [$user->people_id],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sub_projects', [
            'project_id' => $project->id,
            'code' => 'SP-01',
            'name' => 'Pilot site',
            'title' => 'Pilot site cohort',
            'date_started' => '2026-01-10',
            'date_end_intended' => '2026-12-31',
        ]);
        $this->assertDatabaseHas('sub_project_people', [
            'people_id' => $user->people_id,
        ]);
    }

    public function test_editor_cannot_create_sub_project(): void
    {
        [$user, $project] = $this->createUserInProject('editor');

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->post(route('sub-projects.store'), [
                'project_id' => $project->id,
                'code' => 'SP-01',
                'name' => 'Blocked',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('sub_projects', [
            'project_id' => $project->id,
            'code' => 'SP-01',
        ]);
    }

    public function test_admin_can_reuse_the_same_sub_project_type_name(): void
    {
        [$user, $project] = $this->createUserInProject('admin');

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->post(route('sub-projects.store'), [
                'project_id' => $project->id,
                'code' => 'SP-ONE',
                'name' => 'Research assignment',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->post(route('sub-projects.store'), [
                'project_id' => $project->id,
                'code' => 'SP-TWO',
                'name' => 'Research assignment',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sub_projects', [
            'project_id' => $project->id,
            'code' => 'SP-ONE',
            'name' => 'Research assignment',
        ]);
        $this->assertDatabaseHas('sub_projects', [
            'project_id' => $project->id,
            'code' => 'SP-TWO',
            'name' => 'Research assignment',
        ]);
    }

    public function test_registrar_only_sees_sub_projects_where_it_is_member(): void
    {
        [$user, $project] = $this->createUserInProject('editor');
        $anotherPerson = People::create([
            'first_name' => 'Other',
            'last_name' => 'Member',
            'email' => 'other.member@example.test',
        ]);

        $allowed = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-A',
            'name' => 'Allowed',
            'status' => 'active',
        ]);
        $blocked = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-B',
            'name' => 'Blocked',
            'status' => 'active',
        ]);

        $allowed->people()->sync([$user->people_id]);
        $blocked->people()->sync([$anotherPerson->id]);

        $options = SubProjectFlag::optionsForUser($user, (int) $project->id);

        $this->assertTrue($options->contains('id', $allowed->id));
        $this->assertFalse($options->contains('id', $blocked->id));
    }

    public function test_polymorphic_assignment_is_unique_per_assignable_pair(): void
    {
        [$user, $project] = $this->createUserInProject('admin');

        $subProject = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-U',
            'name' => 'Unique',
            'status' => 'active',
        ]);

        SubProjectAssignment::create([
            'sub_project_id' => $subProject->id,
            'assignable_type' => 'App\\Models\\VirtualAssignable',
            'assignable_id' => 123,
        ]);

        $this->expectException(QueryException::class);

        SubProjectAssignment::create([
            'sub_project_id' => $subProject->id,
            'assignable_type' => 'App\\Models\\VirtualAssignable',
            'assignable_id' => 123,
        ]);
    }

    public function test_admin_can_mark_sub_project_complete_with_date_end(): void
    {
        [$user, $project] = $this->createUserInProject('admin');

        $subProject = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-COMP',
            'name' => 'Research assignment',
            'status' => 'active',
            'date_started' => '2026-01-01',
        ]);

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->postJson(route('sub-projects.complete', $subProject), [
                'date_end' => '2026-02-01',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('sub_projects', [
            'id' => $subProject->id,
            'status' => 'completed',
            'date_end' => '2026-02-01',
        ]);
    }

    public function test_admin_can_delete_sub_project(): void
    {
        [$user, $project] = $this->createUserInProject('admin');

        $subProject = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-DEL',
            'name' => 'Delete me',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->delete(route('sub-projects.destroy', $subProject))
            ->assertRedirect();

        $this->assertDatabaseMissing('sub_projects', [
            'id' => $subProject->id,
        ]);
    }

    public function test_editor_cannot_delete_sub_project(): void
    {
        [$user, $project] = $this->createUserInProject('editor');

        $subProject = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-KEEP',
            'name' => 'Protected',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->delete(route('sub-projects.destroy', $subProject))
            ->assertForbidden();

        $this->assertDatabaseHas('sub_projects', [
            'id' => $subProject->id,
        ]);
    }

    private function createUserInProject(string $permission): array
    {
        $person = People::create([
            'first_name' => 'Test',
            'last_name' => ucfirst($permission),
            'email' => $permission.'.user@example.test',
        ]);

        $user = User::create([
            'people_id' => $person->id,
            'email' => $permission.'.user@example.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $project = Projects::create([
            'code' => 'PRJ-'.strtoupper(substr($permission, 0, 1)).rand(100, 999),
            'type' => 'Research',
            'title' => 'Project '.$permission,
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
