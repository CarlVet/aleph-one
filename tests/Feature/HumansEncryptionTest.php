<?php

namespace Tests\Feature;

use App\Models\Humans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HumansEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_fields_are_stored_encrypted_at_rest(): void
    {
        $human = Humans::factory()->create([
            'national_id' => '8001015009087',
            'alternate_phone' => '+27 82 555 0100',
            'alternate_email' => 'secret@example.org',
        ]);

        $raw = DB::table('humans')->where('id', $human->id)->first();

        foreach (['national_id' => '8001015009087', 'alternate_phone' => '+27 82 555 0100', 'alternate_email' => 'secret@example.org'] as $column => $plaintext) {
            $this->assertNotSame($plaintext, $raw->{$column}, "{$column} must not be stored in plaintext");
            $this->assertSame($plaintext, Crypt::decryptString($raw->{$column}), "{$column} must decrypt back to the original value");
        }
    }

    public function test_model_transparently_decrypts_sensitive_fields(): void
    {
        $human = Humans::factory()->create(['national_id' => '8001015009087']);

        $this->assertSame('8001015009087', $human->fresh()->national_id);
    }

    public function test_national_id_blind_index_enables_equality_lookup(): void
    {
        $human = Humans::factory()->create(['national_id' => '8001015009087']);

        $raw = DB::table('humans')->where('id', $human->id)->first();
        $this->assertSame(Humans::blindIndex('8001015009087'), $raw->national_id_hash);

        $found = Humans::where('national_id_hash', Humans::blindIndex('8001015009087'))->first();
        $this->assertNotNull($found);
        $this->assertSame($human->id, $found->id);
    }

    public function test_blind_index_is_null_for_empty_national_id(): void
    {
        $this->assertNull(Humans::blindIndex(null));
        $this->assertNull(Humans::blindIndex('   '));
    }
}
