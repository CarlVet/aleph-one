<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorSatisfied
{
    /**
     * Paths that must stay reachable so a user can complete the challenge,
     * enrol a factor or sign out without being redirected back to the prompt.
     *
     * @var list<string>
     */
    private array $allowed = [
        'login',
        'login/2fa',
        'login/2fa/*',
        'logout',
        'settings',
        'settings/*',
        'verify-email',
        'resend-verification',
        'webauthn/*',
        'storage/*',
        'forgot-password',
        'reset-password/*',
    ];

    /**
     * Require a second factor for admins, project admins and anyone who has
     * enabled an authenticator app. The challenge is shown at login (see
     * SessionController). An admin who has not yet set up any factor may keep
     * working during a 14-day grace window (so they can enrol without being
     * locked out); once it closes, or immediately for anyone who already has a
     * factor, every protected page redirects back to the challenge until they
     * pass. A no-op for guests and for users who need not prove a second factor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! AdminAccess::mustProveTwoFactor($user)) {
            return $next($request);
        }

        if ($request->session()->get('two_factor_passed')) {
            return $next($request);
        }

        if (AdminAccess::requiresTwoFactor($user) && $user->two_factor_grace_until === null) {
            $user->forceFill(['two_factor_grace_until' => now()->addDays(14)])->save();
        }

        if ($this->canPostpone($user)) {
            return $next($request);
        }

        if ($request->is(...$this->allowed)) {
            return $next($request);
        }

        return redirect()->route('two-factor.prompt');
    }

    /**
     * An admin may postpone the challenge only while still within the grace
     * window AND without any second factor configured yet — there is nothing to
     * postpone once a factor exists.
     */
    private function canPostpone($user): bool
    {
        return AdminAccess::requiresTwoFactor($user)
            && $user->inTwoFactorGrace()
            && ! $user->hasSatisfiedTwoFactor();
    }
}
