<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileServingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_a_stored_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/ethics.pdf', 'CONFIDENTIAL');

        $this->get('/storage/documents/ethics.pdf')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_download_a_stored_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/ethics.pdf', 'CONFIDENTIAL');

        $response = $this->actingAs(User::factory()->create())
            ->get('/storage/documents/ethics.pdf');

        $response->assertOk();
        $this->assertSame('CONFIDENTIAL', $response->streamedContent());
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/storage/..%2F..%2F.env')
            ->assertNotFound();
    }

    public function test_missing_file_returns_not_found(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->get('/storage/documents/nope.pdf')
            ->assertNotFound();
    }
}
