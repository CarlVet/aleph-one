<?php

namespace Tests\Feature;

use App\Models\Animals;
use App\Models\People;
use App\Models\Projects;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalHealthRegistrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_medication_registration_creates_one_row_per_animal_and_medication(): void
    {
        [$user, $project] = $this->createUserInProject('admin');
        $animals = Animals::factory()->count(2)->create([
            'projects_id' => $project->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->postJson(route('animalmedication.store'), [
                'animal_id' => $animals->pluck('id')->all(),
                'medication_name' => ['Amoxicillin', 'Metronidazole'],
                'dosage' => '10mg/kg twice daily',
                'start_date' => '2026-04-03',
                'end_date' => '2026-04-10',
                'notes' => 'Shared medication note',
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => '4 animal medication record(s) registered successfully!',
        ]);

        $this->assertDatabaseCount('animal_medications', 4);

        foreach ($animals as $animal) {
            $this->assertDatabaseHas('animal_medications', [
                'animals_id' => $animal->id,
                'medication_name' => 'Amoxicillin',
                'dosage' => '10mg/kg twice daily',
            ]);
            $this->assertDatabaseHas('animal_medications', [
                'animals_id' => $animal->id,
                'medication_name' => 'Metronidazole',
                'dosage' => '10mg/kg twice daily',
            ]);
        }
    }

    public function test_vaccination_registration_creates_one_row_per_animal_and_vaccine(): void
    {
        [$user, $project] = $this->createUserInProject('admin');
        $animals = Animals::factory()->count(2)->create([
            'projects_id' => $project->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->postJson(route('animalvaccination.store'), [
                'animal_id' => $animals->pluck('id')->all(),
                'vaccine_name' => ['Rabies Vaccine', 'FVRCP Vaccine'],
                'vaccine_type' => 'Core',
                'date_administered' => '2026-04-03',
                'next_due_date' => '2027-04-03',
                'notes' => 'Shared vaccination note',
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => '4 animal vaccination record(s) registered successfully!',
        ]);

        $this->assertDatabaseCount('animal_vaccinations', 4);

        foreach ($animals as $animal) {
            $this->assertDatabaseHas('animal_vaccinations', [
                'animals_id' => $animal->id,
                'vaccine_name' => 'Rabies Vaccine',
                'vaccine_type' => 'Core',
            ]);
            $this->assertDatabaseHas('animal_vaccinations', [
                'animals_id' => $animal->id,
                'vaccine_name' => 'FVRCP Vaccine',
                'vaccine_type' => 'Core',
            ]);
        }
    }

    public function test_health_registration_creates_rows_for_each_selected_animal(): void
    {
        [$user, $project] = $this->createUserInProject('admin');
        $animals = Animals::factory()->count(2)->create([
            'projects_id' => $project->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->postJson(route('animalhealth.store'), [
                'animal_id' => $animals->pluck('id')->all(),
                'health_status' => 'Sick',
                'check_date' => '2026-04-03',
                'check_type' => 'Routine',
                'clinical_signs' => ['Fever', 'Lethargy'],
                'lesions' => ['Skin rash'],
                'alive' => 1,
                'notes' => 'Shared health note',
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => '4 animal health record(s) registered successfully!',
        ]);

        $this->assertDatabaseCount('animal_health', 4);

        foreach ($animals as $animal) {
            $this->assertDatabaseHas('animal_health', [
                'animals_id' => $animal->id,
                'health_status' => 'Sick',
                'check_type' => 'Routine',
            ]);
        }
    }

    private function createUserInProject(string $permission): array
    {
        $person = People::create([
            'first_name' => 'Test',
            'last_name' => ucfirst($permission),
            'email' => $permission.'.animal-health@example.test',
        ]);

        $user = User::create([
            'people_id' => $person->id,
            'email' => $permission.'.animal-health@example.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $project = Projects::create([
            'code' => 'ANH-'.strtoupper(substr($permission, 0, 1)).rand(100, 999),
            'type' => 'Research',
            'title' => 'Animal health project '.$permission,
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
