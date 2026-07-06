<?php

namespace Tests\Feature;

use App\Livewire\AnimalsIndex;
use App\Livewire\HumanSamplesIndex;
use App\Livewire\MicroplasticsIndex;
use App\Models\People;
use App\Models\Projects;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TableExportTest extends TestCase
{
    use RefreshDatabase;

    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function test_animals_index_exports_csv_and_xlsx(): void
    {
        $this->actingInProject();

        Livewire::test(AnimalsIndex::class)
            ->call('export', 'csv')
            ->assertFileDownloaded('animals.csv', null, 'text/csv');

        Livewire::test(AnimalsIndex::class)
            ->call('export', 'xlsx')
            ->assertFileDownloaded('animals.xlsx', null, self::XLSX_MIME);
    }

    public function test_human_samples_index_exports_csv_and_xlsx(): void
    {
        $this->actingInProject();

        Livewire::test(HumanSamplesIndex::class)
            ->call('export', 'csv')
            ->assertFileDownloaded('human_samples.csv', null, 'text/csv');

        Livewire::test(HumanSamplesIndex::class)
            ->call('export', 'xlsx')
            ->assertFileDownloaded('human_samples.xlsx', null, self::XLSX_MIME);
    }

    public function test_microplastics_index_exports_csv_and_xlsx(): void
    {
        $this->actingInProject();

        // Filename carries a timestamp, so assert on type only.
        Livewire::test(MicroplasticsIndex::class)
            ->call('export', 'csv')
            ->assertFileDownloaded(null, null, 'text/csv');

        Livewire::test(MicroplasticsIndex::class)
            ->call('export', 'xlsx')
            ->assertFileDownloaded(null, null, self::XLSX_MIME);
    }

    public function test_unknown_format_defaults_to_csv(): void
    {
        $this->actingInProject();

        Livewire::test(AnimalsIndex::class)
            ->call('export', 'exe')
            ->assertFileDownloaded('animals.csv', null, 'text/csv');
    }

    private function actingInProject(): void
    {
        $person = People::create([
            'first_name' => 'Export',
            'last_name' => 'Tester',
            'email' => 'export.tester@example.test',
        ]);

        $user = User::create([
            'people_id' => $person->id,
            'email' => 'export.tester@example.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $project = Projects::create([
            'code' => 'EXP-'.rand(100, 999),
            'type' => 'Research',
            'title' => 'Export project',
            'status' => 'active',
        ]);

        $project->people()->attach($person->id, [
            'role' => 'Team member',
            'permission' => 'admin',
            'date_joined' => now()->toDateString(),
        ]);

        $this->actingAs($user)->withSession(['selected_project_id' => $project->id]);
    }
}
