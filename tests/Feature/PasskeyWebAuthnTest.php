<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laragear\WebAuthn\Models\WebAuthnCredential;
use Tests\TestCase;

class PasskeyWebAuthnTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_request_a_registration_challenge(): void
    {
        $this->postJson('/webauthn/register/options')->assertUnauthorized();
    }

    public function test_authenticated_user_receives_an_attestation_challenge(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/webauthn/register/options')
            ->assertOk()
            ->assertJsonStructure(['challenge', 'rp' => ['id'], 'user', 'pubKeyCredParams']);
    }

    public function test_login_challenge_is_issued_without_leaking_account_existence(): void
    {
        $this->postJson('/webauthn/login/options', ['email' => 'unknown@example.org'])
            ->assertOk()
            ->assertJsonStructure(['challenge']);
    }

    public function test_user_can_remove_a_passkey(): void
    {
        $user = User::factory()->create();
        $credential = $this->fakeCredentialFor($user);

        $this->actingAs($user)
            ->delete(route('webauthn.passkeys.destroy', $credential->getKey()))
            ->assertRedirect();

        $this->assertDatabaseMissing('webauthn_credentials', ['id' => $credential->getKey()]);
    }

    public function test_a_user_cannot_remove_another_users_passkey(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $credential = $this->fakeCredentialFor($owner);

        $this->actingAs($attacker)
            ->delete(route('webauthn.passkeys.destroy', $credential->getKey()))
            ->assertRedirect();

        $this->assertDatabaseHas('webauthn_credentials', ['id' => $credential->getKey()]);
    }

    private function fakeCredentialFor(User $user): WebAuthnCredential
    {
        return $user->webAuthnCredentials()->forceCreate([
            'id' => 'credential-'.$user->id,
            'user_id' => '11111111-1111-1111-1111-111111111111',
            'alias' => 'Test Passkey',
            'counter' => 0,
            'rp_id' => 'aleph-one.test',
            'origin' => 'https://aleph-one.test',
            'attestation_format' => 'none',
            'public_key' => 'test-public-key',
        ]);
    }
}
