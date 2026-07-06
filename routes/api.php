<?php

use App\Http\Controllers\Api\V1\AnimalSampleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExperimentController;
use App\Http\Controllers\Api\V1\HumanSampleController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\SequenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
| Versioned, token-authenticated REST API (Laravel Sanctum). Every resource
| is scoped to the projects the authenticated user belongs to. New versions
| live under their own prefix (v2, ...) so v1 stays stable for integrators.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Exchange credentials for a personal access token.
    Route::post('auth/token', [AuthController::class, 'issueToken'])->name('auth.token');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user'])->name('user');
        Route::delete('auth/token', [AuthController::class, 'revokeToken'])->name('auth.revoke');

        Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);
        Route::apiResource('animal-samples', AnimalSampleController::class)->only(['index', 'show']);
        Route::apiResource('human-samples', HumanSampleController::class)->only(['index', 'show']);
        Route::apiResource('experiments', ExperimentController::class)->only(['index', 'show']);
        Route::apiResource('sequences', SequenceController::class)->only(['index', 'show']);
    });
});
