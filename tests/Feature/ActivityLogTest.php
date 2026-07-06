<?php

namespace Tests\Feature;

use App\Models\Humans;
use App\Models\ProjectsPeople;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_to_tracked_models_are_logged(): void
    {
        $human = Humans::factory()->create();
        $human->update(['occupation' => 'Veterinarian']);

        $activity = Activity::query()->where('subject_type', Humans::class)->where('subject_id', $human->id)->latest('id')->first();

        $this->assertNotNull($activity, 'An activity entry should be recorded for a tracked model');
        $this->assertSame('updated', $activity->event);
        $this->assertSame('Veterinarian', $activity->changes()['attributes']['occupation'] ?? null);
    }

    public function test_encrypted_fields_never_appear_in_the_activity_log(): void
    {
        $human = Humans::factory()->create(['national_id' => '8001015009087']);
        $human->update(['national_id' => '9002025009088', 'occupation' => 'Nurse']);

        $properties = Activity::query()->where('subject_type', Humans::class)->get()
            ->flatMap(fn (Activity $a): array => array_keys($a->changes()['attributes'] ?? []))
            ->all();

        foreach (['national_id', 'national_id_hash', 'alternate_phone', 'alternate_email'] as $sensitive) {
            $this->assertNotContains($sensitive, $properties, "{$sensitive} must never be written to the activity log");
        }

        $raw = json_encode(Activity::query()->where('subject_type', Humans::class)->get()->pluck('properties'));
        $this->assertStringNotContainsString('8001015009087', $raw);
        $this->assertStringNotContainsString('9002025009088', $raw);
    }

    public function test_project_membership_permission_grants_are_audited(): void
    {
        ProjectsPeople::factory()->create(['permission' => 'admin']);

        $activity = Activity::query()->where('subject_type', ProjectsPeople::class)->latest('id')->first();

        $this->assertNotNull($activity, 'Granting project membership should be audited');
        $this->assertSame('created', $activity->event);
        $this->assertSame('admin', $activity->changes()['attributes']['permission'] ?? null);
    }
}
