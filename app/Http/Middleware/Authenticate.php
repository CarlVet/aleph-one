<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards): Response
    {
        $this->authenticate($request, $guards);

        $user = Auth::user();
        $isVerified = (bool) ($user?->email_verified ?? false) || ($user?->email_verified_at !== null);

        if ($user && ! $isVerified && ! $this->isAllowedForUnverified($request)) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('pending_verification_user_id', $user->id);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please verify your email before continuing.',
                ], 403);
            }

            return redirect('/verify-email')->withErrors([
                'email' => 'Please verify your email before continuing.',
            ]);
        }

        return $next($request);
    }

    private function isAllowedForUnverified(Request $request): bool
    {
        // Let unverified users sign out.
        if ($request->is('logout')) {
            return true;
        }

        return false;
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
