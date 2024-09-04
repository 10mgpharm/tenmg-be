<?php

use App\Http\Controllers\Auth\AuthenticatedController;
use App\Http\Controllers\Auth\SignupUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors', 'json.response']], function () {

    Route::prefix('v1')->group(function () {

        // public routes
        Route::prefix('auth')->group(function () {

            Route::middleware('guest')->group(function () {
                Route::post('/signup', [SignupUserController::class, 'store'])
                    ->name('signup');

                Route::post('/signin', [AuthenticatedController::class, 'store'])
                    ->name('singin');
            });

            Route::middleware(['auth:api', 'scope:temp'])->group(function () {
                Route::post('/verify-email', VerifyEmailController::class)
                    ->name('verification.verify');
            });


            // protected routes
            Route::middleware(['auth:api', 'scope:full'])->group(function () {

                Route::post('/signout', [AuthenticatedController::class, 'destroy'])
                    ->name('signout');
            });
        });
    });
});
