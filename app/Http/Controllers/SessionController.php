<?php

namespace App\Http\Controllers;

use App\Mail\VerificationEmail;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($attributes['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::validate($attributes)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Credentials do not match.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::getLastAttempted();

        if (! $this->isVerified($user)) {
            $this->resendVerificationCode($user);

            $request->session()->put('pending_verification_user_id', $user->id);

            return redirect('/verify-email')->withErrors([
                'email' => 'Please verify your email before logging in.',
            ]);
        }

        return $this->completeLogin($request, $user);
    }

    /**
     * Establish the session after a correct password and send users who must
     * use a second factor to the challenge screen. During the 14-day grace
     * window an admin may postpone it from that screen; after the window the
     * EnsureTwoFactorSatisfied middleware keeps redirecting them back until they
     * pass. Logging in with a passkey or passing the challenge sets the
     * `two_factor_passed` flag.
     */
    private function completeLogin(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);

        $request->session()->regenerate();
        $request->session()->put('two_factor_passed', false);

        if (AdminAccess::requiresTwoFactor($user) && $user->two_factor_grace_until === null) {
            $user->forceFill(['two_factor_grace_until' => now()->addDays(14)])->save();
        }

        if (AdminAccess::mustProveTwoFactor($user)) {
            return redirect()->route('two-factor.prompt');
        }

        return redirect('/');
    }

    private function isVerified(User $user): bool
    {
        return (bool) $user->email_verified || $user->email_verified_at !== null;
    }

    private function resendVerificationCode(User $user): void
    {
        if ($this->isVerified($user)) {
            return;
        }

        // Throttle resends to prevent email spam on repeated login attempts.
        $throttleKey = 'verification-code-sent:'.$user->id;
        if (Cache::has($throttleKey)) {
            return;
        }

        $verificationCode = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);

        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        Cache::put($throttleKey, true, now()->addSeconds(60));

        try {
            Mail::to($user->email)->send(new VerificationEmail($verificationCode, $user->people?->first_name ?? ''));
        } catch (\Throwable $e) {
            Log::error('Failed to resend verification email on login: '.$e->getMessage());
        }
    }

    public function destroy()
    {
        Auth::logout();

        return redirect('/login');
    }
}
