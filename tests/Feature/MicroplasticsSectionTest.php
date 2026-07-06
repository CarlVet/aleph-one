<?php

namespace Tests\Feature;

use App\Models\Countries;
use App\Models\Humans;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\Microplastics;
use App\Models\MpsTypes;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\Protocols;
use App\Models\SampleTypes;
use App\Models\Techniques;
use App\Models\Tubes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MicroplasticsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_registration_creates_microplastics_record_without_output_tube(): void
    {
        [$user, $project, $person] = $this->createUserInProject('admin');

        $technique = Techniques::query()->create([
            'name' => 'Microplastics identification',
            'type' => 'Microplastics identification',
        ]);
        $protocol = Protocols::query()->create([
            'code' => $project->code.'-PR-1',
            'name' => 'Nile red staining',
            'techniques_id' => $technique->id,
            'users_id' => $user->id,
        ]);
        $mpsType = MpsTypes::query()->firstOrCreate(['name' => 'Polyamide']);

        $sourceSample = $this->createHumanSample($project, $person, 'HS-1');

        $sourceTube = Tubes::query()->create([
            'code' => $sourceSample->code.'-1',
            'alias_code' => null,
            'projects_id' => $project->id,
            'tubes_content_type' => HumanSamples::class,
            'tubes_content_id' => $sourceSample->id,
            'tube_type' => '1.5ml/2ml tube',
            'purpose' => 'for storage',
            'date_processed' => '2026-04-03',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->from('/samples/microplastics/create')
            ->post(route('microplastics.store'), [
                'register_mode' => 'form',
                'project_id_snapshot' => $project->id,
                'model' => 'Human samples',
                'human_tube_id' => [$sourceTube->id],
                'sample_weight' => 2.5,
                'r_coeff' => 0.81,
                'mps_type' => [$mpsType->name],
                'm_feret' => 55.4,
                'identification_date' => '2026-04-15',
                'protocol' => $protocol->name,
                'microplastics_lab' => 'Microplastics Lab',
                'identifier' => $person->id,
                'source_measurement_mode' => 'separate_measurements',
            ]);

        $response->assertRedirect('/samples/microplastics/create');

        $this->assertDatabaseHas('microplastics', [
            'microplastics_content_type' => HumanSamples::class,
            'microplastics_content_id' => $sourceSample->id,
            'sample_weight' => 2.500,
            'r_coeff' => 0.8100,
            'mps_types_id' => $mpsType->id,
            'm_feret' => 55.400,
            'identification_date' => '2026-04-15 00:00:00',
            'is_private' => 1,
            'people_id' => $person->id,
            'projects_id' => $project->id,
        ]);

        $this->assertDatabaseMissing('tubes', [
            'tubes_content_type' => Microplastics::class,
            'projects_id' => $project->id,
        ]);
    }

    public function test_form_registration_can_pool_tubes_from_same_source_sample(): void
    {
        [$user, $project, $person] = $this->createUserInProject('admin');

        $technique = Techniques::query()->create([
            'name' => 'Microplastics identification',
            'type' => 'Microplastics identification',
        ]);
        $protocol = Protocols::query()->create([
            'code' => $project->code.'-PR-2',
            'name' => 'Visual microscopy pooled',
            'techniques_id' => $technique->id,
            'users_id' => $user->id,
        ]);
        $typeOne = MpsTypes::query()->firstOrCreate(['name' => 'Polyamide']);
        $typeTwo = MpsTypes::query()->firstOrCreate(['name' => 'Polystyrene']);

        $sourceSample = $this->createHumanSample($project, $person, 'HS-P');

        $tubeOne = Tubes::query()->create([
            'code' => $sourceSample->code.'-1',
            'alias_code' => null,
            'projects_id' => $project->id,
            'tubes_content_type' => HumanSamples::class,
            'tubes_content_id' => $sourceSample->id,
            'tube_type' => '1.5ml/2ml tube',
            'purpose' => 'for storage',
            'date_processed' => '2026-04-03',
        ]);
        $tubeTwo = Tubes::query()->create([
            'code' => $sourceSample->code.'-2',
            'alias_code' => null,
            'projects_id' => $project->id,
            'tubes_content_type' => HumanSamples::class,
            'tubes_content_id' => $sourceSample->id,
            'tube_type' => '1.5ml/2ml tube',
            'purpose' => 'for storage',
            'date_processed' => '2026-04-03',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->from('/samples/microplastics/create')
            ->post(route('microplastics.store'), [
                'register_mode' => 'form',
                'project_id_snapshot' => $project->id,
                'model' => 'Human samples',
                'human_tube_id' => [$tubeOne->id, $tubeTwo->id],
                'sample_weight' => 5.2,
                'r_coeff' => 0.44,
                'mps_type' => [$typeOne->name, $typeTwo->name],
                'm_feret' => 41.8,
                'identification_date' => '2026-04-16',
                'protocol' => $protocol->name,
                'microplastics_lab' => 'Microplastics Lab',
                'identifier' => $person->id,
                'source_measurement_mode' => 'pooled',
            ]);

        $response->assertRedirect('/samples/microplastics/create');

        $this->assertDatabaseCount('microplastics', 2);
        $this->assertDatabaseHas('microplastics', [
            'microplastics_content_type' => HumanSamples::class,
            'microplastics_content_id' => $sourceSample->id,
            'mps_types_id' => $typeOne->id,
        ]);
        $this->assertDatabaseHas('microplastics', [
            'microplastics_content_type' => HumanSamples::class,
            'microplastics_content_id' => $sourceSample->id,
            'mps_types_id' => $typeTwo->id,
        ]);
    }

    public function test_table_registration_creates_multiple_records_in_single_request(): void
    {
        [$user, $project, $person] = $this->createUserInProject('admin');

        Techniques::query()->create([
            'name' => 'Microplastics identification',
            'type' => 'Microplastics identification',
        ]);

        $protocol = Protocols::query()->create([
            'code' => $project->code.'-PR-1',
            'name' => 'Visual microscopy',
            'techniques_id' => Techniques::query()->first()->id,
            'users_id' => $user->id,
        ]);
        $typeOne = MpsTypes::query()->firstOrCreate(['name' => 'Polyethylene']);
        $typeTwo = MpsTypes::query()->firstOrCreate(['name' => 'Polystyrene']);

        $sampleOne = $this->createHumanSample($project, $person, 'HS-1');
        $sampleTwo = $this->createHumanSample($project, $person, 'HS-2');

        $tubeOne = Tubes::query()->create([
            'code' => $sampleOne->code.'-1',
            'alias_code' => null,
            'projects_id' => $project->id,
            'tubes_content_type' => HumanSamples::class,
            'tubes_content_id' => $sampleOne->id,
            'tube_type' => '1.5ml/2ml tube',
            'purpose' => 'for storage',
            'date_processed' => '2026-04-03',
        ]);
        $tubeTwo = Tubes::query()->create([
            'code' => $sampleTwo->code.'-1',
            'alias_code' => null,
            'projects_id' => $project->id,
            'tubes_content_type' => HumanSamples::class,
            'tubes_content_id' => $sampleTwo->id,
            'tube_type' => '1.5ml/2ml tube',
            'purpose' => 'for storage',
            'date_processed' => '2026-04-03',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->from('/samples/microplastics/create')
            ->post(route('microplastics.store'), [
                'register_mode' => 'table',
                'table_rows' => [
                    [
                        'tube_id' => $tubeOne->id,
                        'protocol_name' => $protocol->name,
                        'mps_type' => $typeOne->name,
                        'sample_weight' => 1.2,
                        'r_coeff' => 0.5,
                        'm_feret' => 11,
                        'identification_date' => '2026-04-17',
                        'laboratory' => 'Lab A',
                        'identified_by' => $person->id,
                    ],
                    [
                        'tube_id' => $tubeTwo->id,
                        'protocol_name' => $protocol->name,
                        'mps_type' => $typeTwo->name,
                        'sample_weight' => 3.4,
                        'r_coeff' => 0.7,
                        'm_feret' => 22,
                        'identification_date' => '2026-04-18',
                        'laboratory' => 'Lab A',
                        'identified_by' => $person->id,
                    ],
                ],
            ]);

        $response->assertRedirect('/samples/microplastics/create');

        $this->assertDatabaseCount('microplastics', 2);
        $this->assertDatabaseHas('microplastics', [
            'microplastics_content_type' => HumanSamples::class,
            'microplastics_content_id' => $sampleOne->id,
            'mps_types_id' => $typeOne->id,
        ]);
        $this->assertDatabaseHas('microplastics', [
            'microplastics_content_type' => HumanSamples::class,
            'microplastics_content_id' => $sampleTwo->id,
            'mps_types_id' => $typeTwo->id,
        ]);
    }

    public function test_table_registration_can_create_new_mps_type(): void
    {
        [$user, $project, $person] = $this->createUserInProject('admin');

        $technique = Techniques::query()->create([
            'name' => 'Microplastics identification',
            'type' => 'Microplastics identification',
        ]);

        $protocol = Protocols::query()->create([
            'code' => $project->code.'-PR-3',
            'name' => 'FTIR screening',
            'techniques_id' => $technique->id,
            'users_id' => $user->id,
        ]);

        $sample = $this->createHumanSample($project, $person, 'HS-NEW');
        $tube = Tubes::query()->create([
            'code' => $sample->code.'-1',
            'alias_code' => null,
            'projects_id' => $project->id,
            'tubes_content_type' => HumanSamples::class,
            'tubes_content_id' => $sample->id,
            'tube_type' => '1.5ml/2ml tube',
            'purpose' => 'for storage',
            'date_processed' => '2026-04-03',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_project_id' => $project->id])
            ->from('/samples/microplastics/create')
            ->post(route('microplastics.store'), [
                'register_mode' => 'table',
                'table_rows' => [
                    [
                        'tube_id' => $tube->id,
                        'protocol_name' => $protocol->name,
                        'mps_type' => 'Custom polymer',
                        'sample_weight' => 1.8,
                        'r_coeff' => 0.65,
                        'm_feret' => 18,
                        'identification_date' => '2026-04-19',
                        'laboratory' => 'Lab B',
                        'identified_by' => $person->id,
                    ],
                ],
            ]);

        $response->assertRedirect('/samples/microplastics/create');

        $this->assertDatabaseHas('mps_types', [
            'name' => 'Custom polymer',
        ]);
        $this->assertDatabaseHas('microplastics', [
            'microplastics_content_type' => HumanSamples::class,
            'microplastics_content_id' => $sample->id,
            'identification_date' => '2026-04-19 00:00:00',
            'is_private' => 1,
        ]);
    }

    private function createUserInProject(string $permission): array
    {
        $person = People::create([
            'first_name' => 'Micro',
            'last_name' => ucfirst($permission),
            'email' => $permission.'.microplastics@example.test',
        ]);

        $user = User::create([
            'people_id' => $person->id,
            'email' => $permission.'.microplastics@example.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $project = Projects::create([
            'code' => 'MPL-'.strtoupper(substr($permission, 0, 1)).rand(100, 999),
            'type' => 'Research',
            'title' => 'Microplastics '.$permission,
            'status' => 'active',
        ]);

        $project->people()->attach($person->id, [
            'role' => 'Team member',
            'permission' => $permission,
            'date_joined' => now()->toDateString(),
        ]);

        return [$user, $project, $person];
    }

    private function createHumanSample(Projects $project, People $person, string $suffix): HumanSamples
    {
        $country = Countries::query()->first() ?? Countries::query()->create(['name' => 'South Africa']);
        $organization = Organizations::query()->first() ?? Organizations::query()->create([
            'name' => 'Test Organization',
            'type' => 'Research Institute',
            'countries_id' => $country->id,
        ]);
        $laboratory = Laboratories::query()->first() ?? Laboratories::query()->create([
            'name' => 'Test Laboratory',
            'organizations_id' => $organization->id,
            'countries_id' => $country->id,
            'address' => '1 Test Street',
        ]);
        $location = Locations::query()->first() ?? Locations::query()->create([
            'name' => 'Test Shelf',
            'laboratories_id' => $laboratory->id,
        ]);
        $sampleType = SampleTypes::query()->first() ?? SampleTypes::query()->create(['name' => 'Blood']);
        $human = Humans::query()->create([
            'code' => $project->code.'-HU-'.$suffix,
            'first_name' => 'Sample',
            'last_name' => $suffix,
            'countries_id' => $country->id,
            'projects_id' => $project->id,
        ]);

        return HumanSamples::query()->create([
            'code' => $project->code.'-'.$suffix,
            'humans_id' => $human->id,
            'sample_types_id' => $sampleType->id,
            'date_collected' => '2026-04-03',
            'people_id' => $person->id,
            'sampling_sites_id' => null,
            'area' => null,
            'latitude' => null,
            'longitude' => null,
            'sample_purpose' => 'research',
            'locations_id' => $location->id,
            'storage_state' => 'RNAlater',
            'processed' => true,
            'projects_id' => $project->id,
        ]);
    }
}
