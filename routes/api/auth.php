<?php

use App\Http\Controllers\API\Auth\AuthenticatedController;
use App\Http\Controllers\API\Auth\PasswordController;
use App\Http\Controllers\API\Auth\SignupUserController;
use App\Http\Controllers\API\Auth\VerifyEmailController;
use App\Http\Controllers\API\ResendOtpController;
use App\Http\Controllers\InviteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/signup', [SignupUserController::class, 'store'])
        ->name('signup');

    Route::post('/signin', [AuthenticatedController::class, 'store'])
        ->name('signin');

    Route::get('/email', [AuthenticatedController::class, 'emailExist'])
        ->name('email.check');

    Route::post('/google', [AuthenticatedController::class, 'google'])
        ->middleware('auth.provider')
        ->name('google.signin');

    Route::post('/forgot-password', [PasswordController::class, 'forgot'])
        ->name('password.forgot');

    Route::middleware(['auth:api', 'scope:full'])->group(function () {
        Route::post('/verify-email', VerifyEmailController::class)
            ->name('verification.verify');

        Route::post('/reset-password', [PasswordController::class, 'reset'])
            ->name('password.reset');

        Route::post('/signup/complete', [SignupUserController::class, 'complete'])
            ->name('signup.complete');

        Route::post('/signout', [AuthenticatedController::class, 'destroy'])
            ->name('signout');
    });

    Route::post('/resend-otp', ResendOtpController::class)
        ->name('resend.otp')->middleware('throttle:5,1');

    Route::get('invite/view', [InviteController::class, 'view'])->name('invite.view');
    Route::post('invite/accept', [InviteController::class, 'accept'])->name('invite.accept');
});
