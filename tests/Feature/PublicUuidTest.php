<?php

namespace Tests\Feature;

use App\Models\AnimalSamples;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Projects;
use App\Models\Sequences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicUuidTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{class-string, string}>
     */
    public static function keyEntities(): array
    {
        return [
            [Projects::class, 'projects'],
            [AnimalSamples::class, 'animal_samples'],
            [HumanSamples::class, 'human_samples'],
            [Experiments::class, 'experiments'],
            [Sequences::class, 'sequences'],
        ];
    }

    public function test_creating_a_record_assigns_a_public_uuid(): void
    {
        $project = Projects::factory()->create();

        $this->assertNotNull($project->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $project->uuid);
    }

    public function test_uuid_is_stable_across_updates(): void
    {
        $project = Projects::factory()->create();
        $original = $project->uuid;

        $project->touch();

        $this->assertSame($original, $project->fresh()->uuid);
    }

    public function test_uuids_are_unique_across_records(): void
    {
        $uuids = Projects::factory()->count(5)->create()->pluck('uuid');

        $this->assertCount(5, $uuids->unique());
    }

    /**
     * @param  class-string  $model
     */
    #[DataProvider('keyEntities')]
    public function test_every_key_entity_applies_the_trait_and_has_the_column(string $model, string $table): void
    {
        $this->assertContains(
            HasPublicUuid::class,
            class_uses_recursive($model),
            $model.' should apply the HasPublicUuid trait'
        );

        $this->assertTrue(
            Schema::hasColumn($table, 'uuid'),
            "Table {$table} should have a uuid column"
        );
    }
}
