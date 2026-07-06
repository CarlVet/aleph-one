<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class TwoFactorController extends Controller
{
    /**
     * The login-time second-factor screen. Reached via the
     * EnsureTwoFactorSatisfied middleware when a session still needs to prove a
     * second factor.
     */
    public function prompt(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if (! AdminAccess::mustProveTwoFactor($user) || $request->session()->get('two_factor_passed')) {
            return redirect('/');
        }

        $canPostpone = $this->canPostpone($user);

        return view('auth.two-factor-prompt', [
            'hasTotp' => $user->hasConfirmedTwoFactor(),
            'hasPasskey' => $user->hasPasskeys(),
            'canPostpone' => $canPostpone,
            'daysLeft' => $canPostpone ? $this->graceDaysLeft($user) : null,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $throttleKey = 'two-factor:'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        if ($this->passesChallenge($request, $user)) {
            RateLimiter::clear($throttleKey);
            $request->session()->put('two_factor_passed', true);

            return redirect('/');
        }

        RateLimiter::hit($throttleKey, 60);

        throw ValidationException::withMessages([
            'code' => 'The provided two-factor authentication code was invalid.',
        ]);
    }

    public function postpone(Request $request): RedirectResponse
    {
        if ($this->canPostpone($request->user())) {
            return redirect('/');
        }

        return redirect()->route('two-factor.prompt');
    }

    /**
     * Postponing is only meaningful for an admin still within the grace window
     * who has not yet configured any second factor.
     */
    private function canPostpone(User $user): bool
    {
        return AdminAccess::requiresTwoFactor($user)
            && $user->inTwoFactorGrace()
            && ! $user->hasSatisfiedTwoFactor();
    }

    private function passesChallenge(Request $request, User $user): bool
    {
        if ($code = $request->input('recovery_code')) {
            return $user->useRecoveryCode($code);
        }

        if (($code = $request->input('code')) && $user->hasConfirmedTwoFactor()) {
            return app(TwoFactorAuthenticationProvider::class)
                ->verify(decrypt($user->two_factor_secret), $code);
        }

        return false;
    }

    private function graceDaysLeft(User $user): int
    {
        if ($user->two_factor_grace_until === null) {
            return 14;
        }

        return max(0, (int) ceil(now()->floatDiffInDays($user->two_factor_grace_until, false)));
    }

    /**
     * Begin enrollment: generate a secret and recovery codes. The user must
     * then confirm with a code before the authenticator factor becomes active.
     */
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $enable($request->user());

        return back();
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();
        $confirm($user, $request->input('code'));

        // Confirming requires a valid TOTP code, which proves the second factor
        // for the current session.
        $request->session()->put('two_factor_passed', true);

        // Generate hashed recovery codes now and flash the plaintext so it can
        // be shown exactly once.
        return back()->with('recovery_codes', $user->generateRecoveryCodes());
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at && AdminAccess::requiresTwoFactor($user) && ! $user->hasPasskeys()) {
            return back()->with('error', 'Removing this would leave your account without two-factor authentication. Add a passkey first, then disable the authenticator app.');
        }

        $disable($user);

        return back();
    }

    public function recoveryCodes(Request $request): RedirectResponse
    {
        return back()->with('recovery_codes', $request->user()->generateRecoveryCodes());
    }
}
