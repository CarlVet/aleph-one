<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Fortify is used only as a two-factor authentication backend (TOTP secret
     * generation, QR codes, recovery codes). Authentication, registration and
     * the login challenge are handled by the application's own controllers, so
     * none of Fortify's routes are registered.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    public function boot(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
