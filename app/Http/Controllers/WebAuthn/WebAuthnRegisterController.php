<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

use function response;

class WebAuthnRegisterController
{
    /**
     * Returns a challenge to be verified by the user device.
     */
    public function options(AttestationRequest $request): Responsable
    {
        return $request
            ->fastRegistration()
//            ->userless()
//            ->allowDuplicates()
            ->toCreate();
    }

    /**
     * Registers a device for further WebAuthn authentication. Completing a
     * passkey ceremony proves possession of a second factor, so the current
     * session is marked as having satisfied two-factor authentication.
     */
    public function register(AttestedRequest $request): Response
    {
        $request->save();

        $request->session()->put('two_factor_passed', true);

        return response()->noContent();
    }
}
