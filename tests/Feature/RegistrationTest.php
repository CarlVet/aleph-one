<?php

namespace Tests\Feature;

use App\Mail\VerificationEmail;
use App\Models\People;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_shows_friendly_error_for_duplicate_email_even_with_different_case(): void
    {
        Mail::fake();

        $person = People::query()->create([
            'first_name' => 'Existing',
            'last_name' => 'User',
            'email' => 'friend@example.com',
        ]);

        User::query()->create([
            'people_id' => $person->id,
            'email' => 'friend@example.com',
            'password' => bcrypt('password'),
            'permission' => 'Guest',
        ]);

        $token = 'registration-test-token';

        $response = $this->withSession(['_token' => $token])->post('/register', [
            '_token' => $token,
            'title' => 'Dr.',
            'first_name' => 'New',
            'last_name' => 'Registrant',
            'email' => 'Friend@Example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'job' => 'Veterinarian',
            'accept_legal' => '1',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'A user with this email already exists. Please use a different email or try logging in.',
        ]);

        Mail::assertNothingSent();
        $this->assertSame(1, User::query()->count());
    }

    public function test_registration_stores_email_on_new_person_record(): void
    {
        Mail::fake();

        $token = 'registration-new-person-token';

        $response = $this->withSession(['_token' => $token])->post('/register', [
            '_token' => $token,
            'title' => 'Mr.',
            'first_name' => 'Giorgio Pietro',
            'last_name' => 'Biondetti',
            'email' => 'gpbiondetti@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'job' => 'Data',
            'accept_legal' => '1',
        ]);

        $response->assertRedirect('/verify-email');

        $person = People::query()->sole();
        $user = User::query()->sole();

        $this->assertSame('gpbiondetti@example.com', $person->email);
        $this->assertSame($person->id, $user->people_id);

        Mail::assertSent(VerificationEmail::class);
    }

    public function test_registration_does_not_require_consent_when_no_legal_documents_configured(): void
    {
        Mail::fake();

        config([
            'legal.terms_url' => null,
            'legal.privacy_url' => null,
        ]);

        $token = 'registration-no-legal-token';

        $response = $this->withSession(['_token' => $token])->post('/register', [
            '_token' => $token,
            'title' => 'Mr.',
            'first_name' => 'No',
            'last_name' => 'Consent',
            'email' => 'no.consent@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'job' => 'Data',
        ]);

        $response->assertRedirect('/verify-email');

        $user = User::query()->sole();
        $this->assertNull($user->terms_accepted_at);
        $this->assertNull($user->privacy_accepted_at);
    }

    public function test_registration_requires_consent_when_legal_documents_configured(): void
    {
        Mail::fake();

        config([
            'legal.terms_url' => 'https://aleph-one.com/terms',
            'legal.privacy_url' => 'https://aleph-one.com/privacy',
        ]);

        $token = 'registration-legal-required-token';

        $response = $this->withSession(['_token' => $token])->post('/register', [
            '_token' => $token,
            'title' => 'Mr.',
            'first_name' => 'Needs',
            'last_name' => 'Consent',
            'email' => 'needs.consent@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'job' => 'Data',
        ]);

        $response->assertSessionHasErrors('accept_legal');
        $this->assertSame(0, User::query()->count());
        Mail::assertNothingSent();
    }

    public function test_registration_records_accepted_document_versions(): void
    {
        Mail::fake();

        config([
            'legal.terms_url' => 'https://aleph-one.com/terms',
            'legal.privacy_url' => 'https://aleph-one.com/privacy',
            'legal.terms_version' => '2026-07-06',
            'legal.privacy_version' => '2026-07-06',
        ]);

        $token = 'registration-legal-accepted-token';

        $response = $this->withSession(['_token' => $token])->post('/register', [
            '_token' => $token,
            'title' => 'Mr.',
            'first_name' => 'Agrees',
            'last_name' => 'Fully',
            'email' => 'agrees.fully@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'job' => 'Data',
            'accept_legal' => '1',
        ]);

        $response->assertRedirect('/verify-email');

        $user = User::query()->sole();
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame('2026-07-06', $user->terms_version);
        $this->assertSame('2026-07-06', $user->privacy_version);
    }

    public function test_registration_reuses_existing_person_with_matching_email(): void
    {
        Mail::fake();

        $person = People::query()->create([
            'title' => 'Mr.',
            'first_name' => 'Existing',
            'last_name' => 'Person',
            'email' => 'existing.person@example.com',
            'job' => 'Researcher',
        ]);

        $token = 'registration-existing-person-token';

        $response = $this->withSession(['_token' => $token])->post('/register', [
            '_token' => $token,
            'title' => 'Mr.',
            'first_name' => 'Existing',
            'last_name' => 'Person',
            'email' => 'existing.person@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'job' => 'Researcher',
            'accept_legal' => '1',
        ]);

        $response->assertRedirect('/verify-email');

        $this->assertSame(1, People::query()->count());
        $this->assertDatabaseHas('users', [
            'email' => 'existing.person@example.com',
            'people_id' => $person->id,
        ]);

        Mail::assertSent(VerificationEmail::class);
    }
}
