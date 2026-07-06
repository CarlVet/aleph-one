<?php

namespace Tests\Feature;

use App\Models\Countries;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLookupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_admin_can_create_a_global_lookup_entry(): void
    {
        [$user] = $this->createUserInProject('admin', 'Admin');
        $token = 'test-token';

        $response = $this->actingAs($user)
            ->withSession(['_token' => $token])
            ->post(route('admin.lookups.store', 'countries'), [
                'name' => 'Peru',
                '_token' => $token,
            ]);

        $response->assertRedirect(route('admin.lookups.show', 'countries'));

        $this->assertDatabaseHas('countries', [
            'name' => 'Peru',
        ]);
    }

    public function test_non_admin_cannot_access_lookup_admin_routes(): void
    {
        [$user] = $this->createUserInProject('editor');

        $this->actingAs($user)
            ->get(route('admin.lookups.index'))
            ->assertForbidden();
    }

    public function test_linked_lookup_delete_is_blocked(): void
    {
        [$user] = $this->createUserInProject('admin', 'Admin');
        $country = Countries::create([
            'name' => 'Colombia',
        ]);

        Organizations::create([
            'name' => 'Instituto Nacional',
            'type' => 'government',
            'countries_id' => $country->id,
        ]);

        $token = 'test-token';
        $response = $this->actingAs($user)
            ->withSession(['_token' => $token])
            ->delete(route('admin.lookups.destroy', ['lookup' => 'countries', 'id' => $country->id]), [
                '_token' => $token,
            ]);

        $response->assertRedirect(route('admin.lookups.show', 'countries'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Colombia',
        ]);
    }

    public function test_linked_lookup_page_shows_preview_of_related_records(): void
    {
        [$user] = $this->createUserInProject('admin', 'Admin');
        $country = Countries::create([
            'name' => 'Brazil',
        ]);

        Organizations::create([
            'name' => 'Fiocruz',
            'type' => 'government',
            'countries_id' => $country->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.lookups.edit', ['lookup' => 'countries', 'id' => $country->id]))
            ->assertOk()
            ->assertSee('Fiocruz')
            ->assertSee('organizations');
    }

    public function test_selected_project_admin_can_access_lookup_admin_routes(): void
    {
        [$user, $project] = $this->createUserInProject('Admin', 'viewer');

        $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->get(route('admin.lookups.index'))
            ->assertOk();
    }

    private function createUserInProject(string $permission, ?string $globalPermission = null): array
    {
        $person = People::create([
            'first_name' => 'Test',
            'last_name' => ucfirst($permission),
            'email' => $permission.'.lookup@example.test',
        ]);

        $user = User::create([
            'people_id' => $person->id,
            'email' => $permission.'.lookup@example.test',
            'password' => 'password',
            'permission' => $globalPermission ?? $permission,
            'email_verified_at' => now(),
        ]);

        $project = Projects::create([
            'code' => 'ADM-'.strtoupper(substr($permission, 0, 1)).rand(100, 999),
            'type' => 'Research',
            'title' => 'Admin lookup '.$permission,
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
