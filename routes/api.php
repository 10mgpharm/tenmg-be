<?php

use App\Http\Controllers\API\Auth\AuthenticatedController;
use App\Http\Controllers\API\Auth\PasswordController;
use App\Http\Controllers\API\Auth\SignupUserController;
use App\Http\Controllers\API\Auth\VerifyEmailController;
use App\Http\Controllers\API\Credit\CustomerController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors', 'json.response']], function () {

    Route::prefix('v1')->group(function () {

        // public routes
        Route::prefix('auth')->group(function () {
            Route::post('/signup', [SignupUserController::class, 'store'])
                ->name('signup');

            Route::post('/signin', [AuthenticatedController::class, 'store'])
                ->name('signin');

            Route::post('/forgot-password', [PasswordController::class, 'forgot'])
                ->name('password.forgot');

            Route::post('/reset-password', [PasswordController::class, 'reset'])
                ->name('password.reset');

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

        Route::prefix('/customers', function () {
            // List customers with pagination and filtering
            Route::get('/', [CustomerController::class, 'index'])->name('customers.index');

            // Create a new customer
            Route::post('/', [CustomerController::class, 'store'])->name('customers.store');

            // Get a specific customer's details
            Route::get('/{id}', [CustomerController::class, 'show'])->name('customers.show');

            // Update a customer's details
            Route::put('/{id}', [CustomerController::class, 'update'])->name('customers.update');

            // Soft delete a customer
            Route::delete('/{id}',
                [CustomerController::class, 'destroy']
            )->name('customers.destroy');

            // Enable or disable a customer
            Route::patch('/{id}', [CustomerController::class, 'toggleActive'])->name('customers.toggleActive');

            // Export customers to Excel
            Route::get('/export', [CustomerController::class, 'export'])->name('customers.export');

            // Import customers from Excel
            Route::post('/import', [CustomerController::class, 'import'])->name('customers.import');
        });
    });
});
