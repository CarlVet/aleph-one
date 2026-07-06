<?php

namespace Tests\Feature;

use App\Models\People;
use App\Models\Projects;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_two_factor_is_required_for_global_and_project_admins_only(): void
    {
        $globalAdmin = User::factory()->create(['permission' => 'admin']);
        $projectAdmin = $this->userInProjectWithPermission('admin');
        $editor = $this->userInProjectWithPermission('editor');
        $guest = User::factory()->create(['permission' => 'Guest']);

        $this->assertTrue(AdminAccess::requiresTwoFactor($globalAdmin));
        $this->assertTrue(AdminAccess::requiresTwoFactor($projectAdmin));
        $this->assertFalse(AdminAccess::requiresTwoFactor($editor));
        $this->assertFalse(AdminAccess::requiresTwoFactor($guest));
    }

    public function test_user_can_enable_and_confirm_an_authenticator_app(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('two_factor_passed', true);

        $this->assertTrue($user->refresh()->hasConfirmedTwoFactor());
    }

    public function test_login_redirects_to_the_two_factor_prompt(): void
    {
        $user = $this->userWithConfirmedTotp();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.prompt'));
    }

    public function test_admin_login_redirects_to_the_two_factor_prompt(): void
    {
        $admin = User::factory()->create(['permission' => 'admin']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.prompt'));
    }

    public function test_prompt_verify_logs_the_user_through_with_a_valid_code(): void
    {
        $user = $this->userWithConfirmedTotp();
        $this->actingAs($user);

        $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect('/');

        $this->assertTrue(session('two_factor_passed'));
        $this->get('/')->assertOk();
    }

    public function test_prompt_verify_accepts_a_recovery_code(): void
    {
        $user = $this->userWithConfirmedTotp();
        $recoveryCode = $user->generateRecoveryCodes()[0];

        $this->actingAs($user)
            ->post(route('two-factor.verify'), ['recovery_code' => $recoveryCode])
            ->assertRedirect('/')
            ->assertSessionHas('two_factor_passed', true);
    }

    public function test_prompt_verify_rejects_an_invalid_code(): void
    {
        $user = $this->userWithConfirmedTotp();

        $this->actingAs($user)
            ->post(route('two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse((bool) session('two_factor_passed'));
        $this->actingAs($user)->get('/')->assertRedirect(route('two-factor.prompt'));
    }

    public function test_admin_can_work_during_the_grace_window(): void
    {
        $admin = User::factory()->create([
            'permission' => 'admin',
            'two_factor_grace_until' => now()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.lookups.index'))
            ->assertOk();
    }

    public function test_admin_can_postpone_during_the_grace_window(): void
    {
        $admin = User::factory()->create([
            'permission' => 'admin',
            'two_factor_grace_until' => now()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->post(route('two-factor.postpone'))
            ->assertRedirect('/');
    }

    public function test_admin_is_blocked_after_grace_has_expired(): void
    {
        $admin = User::factory()->create([
            'permission' => 'admin',
            'two_factor_grace_until' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.lookups.index'))
            ->assertRedirect(route('two-factor.prompt'));
    }

    public function test_admin_cannot_postpone_after_grace_has_expired(): void
    {
        $admin = User::factory()->create([
            'permission' => 'admin',
            'two_factor_grace_until' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('two-factor.postpone'))
            ->assertRedirect(route('two-factor.prompt'));
    }

    public function test_admin_with_passed_session_is_not_blocked(): void
    {
        $admin = User::factory()->create([
            'permission' => 'admin',
            'two_factor_grace_until' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('admin.lookups.index'))
            ->assertOk();
    }

    public function test_passkey_admin_is_offered_the_passkey_and_cannot_postpone(): void
    {
        $admin = User::factory()->create([
            'permission' => 'admin',
            'two_factor_grace_until' => now()->addDays(10),
        ]);
        $this->givePasskey($admin);

        $this->assertTrue($admin->refresh()->hasSatisfiedTwoFactor());

        // Already has a factor: the prompt offers the passkey but no "postpone".
        $this->actingAs($admin)
            ->get(route('two-factor.prompt'))
            ->assertOk()
            ->assertSee('Sign in with a passkey', false)
            ->assertDontSee('Remind me later', false);
    }

    public function test_admin_with_a_factor_is_enforced_even_during_grace(): void
    {
        $admin = User::factory()->create([
            'permission' => 'admin',
            'two_factor_grace_until' => now()->addDays(10),
        ]);
        $this->givePasskey($admin);

        // No grace permissiveness once a factor exists.
        $this->actingAs($admin)
            ->get(route('admin.lookups.index'))
            ->assertRedirect(route('two-factor.prompt'));

        $this->actingAs($admin)
            ->post(route('two-factor.postpone'))
            ->assertRedirect(route('two-factor.prompt'));
    }

    public function test_voluntary_totp_user_is_challenged_even_without_admin_role(): void
    {
        $user = $this->userWithConfirmedTotp();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('two-factor.prompt'));
    }

    public function test_non_admin_without_two_factor_is_never_prompted(): void
    {
        $user = User::factory()->create(['permission' => 'Guest']);

        $this->actingAs($user)->get('/')->assertOk();
    }

    public function test_settings_renders_qr_during_enrolment(): void
    {
        $user = User::factory()->create();
        app(EnableTwoFactorAuthentication::class)($user);

        $this->actingAs($user)
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertSee('Scan this QR code', false)
            ->assertSee('<svg', false);
    }

    public function test_settings_shows_enabled_state_once_confirmed(): void
    {
        $user = $this->userWithConfirmedTotp();

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertSee('Authenticator app is enabled.', false);
    }

    public function test_recovery_codes_are_hidden_until_confirmed(): void
    {
        $user = User::factory()->create();
        app(EnableTwoFactorAuthentication::class)($user);

        $this->actingAs($user)
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertDontSee('Save your recovery codes');
    }

    public function test_confirmation_flashes_recovery_codes_to_be_shown_once(): void
    {
        $user = User::factory()->create();
        app(EnableTwoFactorAuthentication::class)($user);
        $code = (new Google2FA)->getCurrentOtp(decrypt($user->refresh()->two_factor_secret));

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertSessionHas('recovery_codes');
    }

    public function test_recovery_codes_are_shown_once_right_after_generation(): void
    {
        $user = $this->userWithConfirmedTotp();
        $codes = $user->generateRecoveryCodes();

        $this->actingAs($user)
            ->withSession(['recovery_codes' => $codes])
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertSee($codes[0]);
    }

    public function test_recovery_codes_are_hidden_on_a_normal_settings_visit(): void
    {
        $user = $this->userWithConfirmedTotp();
        $firstCode = $user->generateRecoveryCodes()[0];

        $this->actingAs($user)
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertDontSee($firstCode)
            ->assertSee('unused recovery codes');
    }

    public function test_admin_cannot_remove_their_only_passkey(): void
    {
        $admin = User::factory()->create(['permission' => 'admin']);
        $this->givePasskey($admin);

        $this->actingAs($admin)
            ->delete(route('webauthn.passkeys.destroy', 'credential-'.$admin->id))
            ->assertSessionHas('error');

        $this->assertTrue($admin->refresh()->hasPasskeys());
    }

    public function test_admin_can_remove_a_passkey_when_totp_also_exists(): void
    {
        $admin = $this->userWithConfirmedTotp();
        $admin->forceFill(['permission' => 'admin'])->save();
        $this->givePasskey($admin);

        $this->actingAs($admin)
            ->delete(route('webauthn.passkeys.destroy', 'credential-'.$admin->id));

        $this->assertFalse($admin->refresh()->hasPasskeys());
    }

    public function test_non_admin_can_remove_their_only_passkey(): void
    {
        $user = User::factory()->create(['permission' => 'Guest']);
        $this->givePasskey($user);

        $this->actingAs($user)
            ->delete(route('webauthn.passkeys.destroy', 'credential-'.$user->id));

        $this->assertFalse($user->refresh()->hasPasskeys());
    }

    public function test_admin_cannot_disable_their_only_authenticator_app(): void
    {
        $admin = $this->userWithConfirmedTotp();
        $admin->forceFill(['permission' => 'admin'])->save();

        $this->actingAs($admin)
            ->post(route('two-factor.disable'))
            ->assertSessionHas('error');

        $this->assertTrue($admin->refresh()->hasConfirmedTwoFactor());
    }

    public function test_admin_can_disable_authenticator_app_when_a_passkey_exists(): void
    {
        $admin = $this->userWithConfirmedTotp();
        $admin->forceFill(['permission' => 'admin'])->save();
        $this->givePasskey($admin);

        $this->actingAs($admin)->post(route('two-factor.disable'));

        $this->assertFalse($admin->refresh()->hasConfirmedTwoFactor());
    }

    public function test_recovery_codes_are_stored_hashed_and_are_single_use(): void
    {
        $user = $this->userWithConfirmedTotp();
        $codes = $user->generateRecoveryCodes();
        $user->refresh();

        // The plaintext is never persisted; only one-way hashes are stored.
        $this->assertStringNotContainsString($codes[0], (string) $user->two_factor_recovery_codes);
        $this->assertSame(8, $user->recoveryCodesCount());

        // A code works once and is then consumed.
        $this->assertTrue($user->useRecoveryCode($codes[0]));
        $this->assertFalse($user->fresh()->useRecoveryCode($codes[0]));
        $this->assertSame(7, $user->fresh()->recoveryCodesCount());
    }

    private function userWithConfirmedTotp(): User
    {
        $user = User::factory()->create();

        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user->refresh();
    }

    private function givePasskey(User $user): void
    {
        $user->webAuthnCredentials()->forceCreate([
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

    private function userInProjectWithPermission(string $permission): User
    {
        $person = People::create([
            'first_name' => 'Test',
            'last_name' => ucfirst($permission),
            'email' => $permission.'.2fa@example.test',
        ]);

        $user = User::factory()->create([
            'people_id' => $person->id,
            'email' => $permission.'.2fa@example.test',
            'permission' => 'Guest',
        ]);

        $project = Projects::create([
            'code' => '2FA-'.strtoupper(substr($permission, 0, 1)).rand(100, 999),
            'type' => 'Research',
            'title' => '2FA '.$permission,
            'status' => 'active',
        ]);

        $project->people()->attach($person->id, [
            'role' => 'Team member',
            'permission' => $permission,
            'date_joined' => now()->toDateString(),
        ]);

        return $user;
    }
}
