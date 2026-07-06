<?php

namespace App\Http\Controllers\WebAuthn;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PasskeyController extends Controller
{
    /**
     * Remove one of the authenticated user's passkeys. For users who are
     * required to use two-factor authentication, the last remaining factor
     * cannot be removed, so they never end up locked out without a second factor.
     */
    public function destroy(string $credential): RedirectResponse
    {
        $user = Auth::user();
        $enabledPasskeys = $user->webAuthnCredentials()->whereEnabled()->count();

        if ($enabledPasskeys <= 1 && AdminAccess::requiresTwoFactor($user) && ! $user->hasConfirmedTwoFactor()) {
            return back()->with('error', 'Removing this would leave your account without two-factor authentication. Set up an authenticator app or add another passkey first.');
        }

        $user->webAuthnCredentials()
            ->whereKey($credential)
            ->delete();

        return back()->with('status', 'Passkey removed.');
    }
}
