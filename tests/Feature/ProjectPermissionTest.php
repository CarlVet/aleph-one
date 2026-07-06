<?php

namespace Tests\Feature;

use App\Models\People;
use App\Models\Projects;
use App\Models\SubProject;
use App\Models\User;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_with_restricted_view_cannot_access_module_list(): void
    {
        [$user, $project] = $this->createUserInProject('editor', [
            'human_samples' => ['view' => false, 'edit' => false],
            'animal_samples' => ['view' => true, 'edit' => true],
        ]);

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->get('/samples/humans/list')
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->get('/samples/animals/list')
            ->assertOk();
    }

    public function test_viewer_with_edit_on_single_module_can_create_in_that_module_only(): void
    {
        [$user, $project] = $this->createUserInProject('viewer', [
            'parasite_samples' => ['view' => true, 'edit' => true],
            'human_samples' => ['view' => true, 'edit' => false],
        ]);

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->get('/samples/parasites/create')
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->get('/samples/humans/create')
            ->assertForbidden();
    }

    public function test_legacy_viewer_module_list_grants_edit_but_not_hidden_view(): void
    {
        [$user, $project] = $this->createUserInProject('viewer', null, ['animal_samples']);

        $this->assertTrue(ProjectPermission::canView($user, $project->id, 'animal_samples'));
        $this->assertTrue(ProjectPermission::canWrite($user, $project->id, 'animal_samples'));
        $this->assertTrue(ProjectPermission::canView($user, $project->id, 'human_samples'));
        $this->assertFalse(ProjectPermission::canWrite($user, $project->id, 'human_samples'));
    }

    public function test_member_with_sub_projects_must_select_one_when_creating(): void
    {
        [$user, $project] = $this->createUserInProject('editor');

        $subProject = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-A',
            'name' => 'Cohort A',
        ]);
        $subProject->people()->sync([(int) $user->people_id]);

        $this->assertFalse(SubProjectFlag::isSelectableByUser($user, $project->id, null));
        $this->assertTrue(SubProjectFlag::isSelectableByUser($user, $project->id, $subProject->id));
    }

    public function test_admin_can_register_in_main_project_without_sub_project_selection(): void
    {
        [$admin, $project] = $this->createUserInProject('admin');

        $subProject = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-ADMIN',
            'name' => 'Admin cohort',
        ]);
        $subProject->people()->sync([(int) $admin->people_id]);

        $this->assertFalse(SubProjectFlag::requiresSelection($admin, $project->id));
        $this->assertNull(SubProjectFlag::defaultSelectionForUser($admin, $project->id));
        $this->assertTrue(SubProjectFlag::isSelectableByUser($admin, $project->id, null));
        $this->assertTrue(SubProjectFlag::isSelectableByUser($admin, $project->id, $subProject->id));
    }

    public function test_admin_can_update_member_sub_projects_from_team_page(): void
    {
        [$admin, $project] = $this->createUserInProject('admin');
        $member = People::factory()->create();
        $project->people()->attach($member->id, [
            'role' => 'Researcher',
            'permission' => 'editor',
            'module_permissions' => json_encode([]),
            'date_joined' => now()->toDateString(),
        ]);

        $subProject = SubProject::create([
            'project_id' => $project->id,
            'code' => 'SP-TEAM',
            'name' => 'Team cohort',
        ]);

        $this->actingAs($admin)
            ->withSession(['selected_project_id' => $project->id])
            ->post(route('team.updateSubProjects', $member->id), [
                'sub_project_ids' => [$subProject->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sub_project_people', [
            'sub_project_id' => $subProject->id,
            'people_id' => $member->id,
        ]);
    }

    /**
     * @param  array<string, array{view: bool, edit: bool}>|null  $moduleMatrix
     * @param  array<int, string>|null  $legacyModules
     * @return array{0: User, 1: Projects}
     */
    private function createUserInProject(string $permission, ?array $moduleMatrix = null, ?array $legacyModules = null): array
    {
        $user = User::factory()->create();
        $person = People::factory()->create();
        $user->people_id = $person->id;
        $user->save();

        $project = Projects::factory()->create();

        $storedModules = json_encode([]);
        if ($legacyModules !== null) {
            $storedModules = json_encode($legacyModules);
        } elseif ($moduleMatrix !== null) {
            $storedModules = ProjectPermission::encodeModulePermissionsForStorage($permission, $moduleMatrix);
        }

        $project->people()->attach($person->id, [
            'role' => 'Researcher',
            'permission' => $permission,
            'module_permissions' => $storedModules,
            'date_joined' => now()->toDateString(),
        ]);

        return [$user, $project];
    }
}
