<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;

use function response;

class WebAuthnLoginController
{
    /**
     * Returns the challenge to assertion.
     */
    public function options(AssertionRequest $request): Responsable
    {
        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Log the user in. A passkey assertion is itself a strong second factor,
     * so the session is marked as having satisfied two-factor authentication.
     */
    public function login(AssertedRequest $request): Response
    {
        $user = $request->login();

        if ($user) {
            $request->session()->put('two_factor_passed', true);
        }

        return response()->noContent($user ? 204 : 422);
    }
}
