<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_an_unrelated_persons_profile(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($viewer)
            ->get('/profile/'.$other->people_id)
            ->assertForbidden();
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'rate@example.test',
            'password' => Hash::make('correct-horse'),
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'rate@example.test', 'password' => 'wrong']);
        }

        $response = $this->post('/login', ['email' => 'rate@example.test', 'password' => 'wrong']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }
}
