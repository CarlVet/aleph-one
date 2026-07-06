<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;
use Laragear\WebAuthn\WebAuthnData;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanResetPassword, WebAuthnAuthenticatable
{
    use CanResetPasswordTrait, HasApiTokens, HasFactory, Notifiable, TracksChanges, TwoFactorAuthenticatable, WebAuthnAuthentication;

    protected $guarded = [];

    /** @var list<string> */
    protected array $activityLogExcept = ['password', 'remember_token', 'verification_code', 'verification_code_expires_at'];

    protected $fillable = [
        'people_id',
        'email',
        'password',
        'permission',
        'verification_code',
        'verification_code_expires_at',
        'email_verified',
        'email_verified_at',
        'terms_accepted_at',
        'terms_version',
        'privacy_accepted_at',
        'privacy_version',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'email_verified' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_grace_until' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
        ];
    }

    /**
     * Whether the user has a confirmed authenticator-app (TOTP) second factor.
     */
    public function hasConfirmedTwoFactor(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Generate a fresh set of one-time recovery codes, storing only their
     * one-way hashes (like passwords) and returning the plaintext so it can be
     * shown to the user once.
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < $count; $i++) {
            $code = Str::random(10).'-'.Str::random(10);
            $plain[] = $code;
            $hashed[] = Hash::make($code);
        }

        $this->forceFill(['two_factor_recovery_codes' => json_encode($hashed)])->save();

        return $plain;
    }

    /**
     * Number of unused recovery codes still stored.
     */
    public function recoveryCodesCount(): int
    {
        return count($this->storedRecoveryCodeHashes());
    }

    /**
     * Consume a recovery code: if it matches one of the stored hashes, remove
     * that code so it cannot be reused.
     */
    public function useRecoveryCode(string $code): bool
    {
        $hashes = $this->storedRecoveryCodeHashes();

        foreach ($hashes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$index]);
                $this->forceFill(['two_factor_recovery_codes' => json_encode(array_values($hashes))])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function storedRecoveryCodeHashes(): array
    {
        if (! $this->two_factor_recovery_codes) {
            return [];
        }

        $decoded = json_decode($this->two_factor_recovery_codes, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Whether the user has at least one active passkey.
     */
    public function hasPasskeys(): bool
    {
        return $this->webAuthnCredentials()->whereEnabled()->exists();
    }

    /**
     * Whether the user satisfies the second-factor requirement with any method.
     */
    public function hasSatisfiedTwoFactor(): bool
    {
        return $this->hasConfirmedTwoFactor() || $this->hasPasskeys();
    }

    /**
     * Whether the user is still within the two-factor roll-out grace window,
     * during which the second factor may be postponed at login.
     */
    public function inTwoFactorGrace(): bool
    {
        return $this->two_factor_grace_until === null
            || now()->lessThanOrEqualTo($this->two_factor_grace_until);
    }

    /**
     * Identity shown to the authenticator when registering a passkey.
     * The User has no `name` column (it lives on the related People record),
     * so the default trait implementation is overridden here.
     */
    public function webAuthnData(): WebAuthnData
    {
        $person = $this->people;
        $displayName = $person ? trim($person->first_name.' '.$person->last_name) : '';

        return WebAuthnData::make($this->email, $displayName !== '' ? $displayName : $this->email);
    }

    public function animal_samples()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Projects::class, 'projects_people', 'people_id', 'projects_id')
            ->wherePivot('people_id', $this->people_id);
    }
}
