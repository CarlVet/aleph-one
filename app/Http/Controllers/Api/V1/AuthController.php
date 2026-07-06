<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TokenRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Exchange email + password for a personal access token (Bearer).
     */
    public function issueToken(TokenRequest $request): JsonResponse
    {
        $user = User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower($request->string('email')->trim()->value())])
            ->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($request->string('device_name')->value())->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * The authenticated token's user.
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Revoke the token used for the current request.
     */
    public function revokeToken(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
