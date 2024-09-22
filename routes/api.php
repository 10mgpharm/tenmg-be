<?php

use App\Http\Controllers\API\Auth\AuthenticatedController;
use App\Http\Controllers\API\Auth\PasswordController;
use App\Http\Controllers\API\Auth\SignupUserController;
use App\Http\Controllers\API\Auth\VerifyEmailController;
use App\Http\Controllers\API\Credit\CustomerController;
use App\Http\Controllers\API\Credit\TransactionHistoryController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ResendOtpController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Controllers\PasswordUpdateController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors', 'json.response']], function () {

    Route::prefix('v1')->group(function () {

        // public routes
        Route::prefix('auth')->group(function () {
            Route::post('/signup', [SignupUserController::class, 'store'])
                ->name('signup');

            Route::post('/signin', [AuthenticatedController::class, 'store'])
                ->name('signin');

            Route::middleware('auth.provider')->post('/google', [AuthenticatedController::class, 'google'])
                ->name('email.check');

            Route::post('/forgot-password', [PasswordController::class, 'forgot'])
                ->name('password.forgot');

            Route::post('/reset-password', [PasswordController::class, 'reset'])
                ->name('password.reset');

            Route::middleware(['auth:api', 'scope:full'])->group(function () {
                Route::post('/verify-email', VerifyEmailController::class)
                    ->name('verification.verify');

                Route::post('/signup/complete', [SignupUserController::class, 'complete'])
                    ->name('signup.complete');

                Route::post('/signout', [AuthenticatedController::class, 'destroy'])
                    ->name('signout');
            });

            Route::post('/resend-otp', ResendOtpController::class)
                ->name('resend.otp')->middleware('throttle:5,1');
        });


        // Protected routes
        Route::middleware(['auth:api', 'scope:full'])->group(function () {

            Route::post('/resend-otp', ResendOtpController::class)
                ->name('resend.otp')->middleware('throttle:5,1');

            // Business specific operations
            Route::prefix('business')->group(function () {

                Route::prefix('settings')->controller(BusinessSettingController::class)
                    ->group(function () {
                        Route::get('/', 'show');

                        // Update business personal information
                        Route::patch('personal-information', 'personalInformation');

                        // Update business account license number, expiry date and cac doc
                        Route::patch('account-setup', 'accountSetup');
                    });
            });

            // Account specific operations
            Route::prefix('account')->group(function () {
                Route::prefix('settings')->group(function () {

                    // Update authenticated user's password
                    Route::patch('password', PasswordUpdateController::class);

                    // 2FA 
                    Route::prefix('2fa')->controller(TwoFactorAuthenticationController::class)
                    ->group(function () {
                        Route::get('setup', 'setup');
                        Route::post('verify', 'verify');
                        Route::post('reset', 'reset');
                    });
                });
            });

            Route::get('/{businessType}/{id}', [ProfileController::class, 'show']);

            Route::prefix('customers')->group(function () {
                // List customers with pagination and filtering
                Route::get('/', [CustomerController::class, 'index'])->name('customers.index');

                // Create a new customer
                Route::post('/', [CustomerController::class, 'store'])->name('customers.store');

                // Export customers to Excel
                Route::get('/export', [CustomerController::class, 'export'])->name('customers.export');

                // Import customers from Excel
                Route::post('/import', [CustomerController::class, 'import'])->name('customers.import');

                // Get a specific customer's details
                Route::get('/{id}', [CustomerController::class, 'show'])->name('customers.show');

                // Update a customer's details
                Route::put('/{id}', [CustomerController::class, 'update'])->name('customers.update');

                // Soft delete a customer
                Route::delete(
                    '/{id}',
                    [CustomerController::class, 'destroy']
                )->name('customers.destroy');

                // Enable or disable a customer
                Route::patch('/{id}', [CustomerController::class, 'toggleActive'])->name('customers.toggleActive');
            });

            Route::prefix('vendor')->group(function () {
                // Upload transaction history file (min of 6 months)
                Route::post('/txn_history/upload', [TransactionHistoryController::class, 'uploadTransactionHistory'])
                    ->name('vendor.txn_history.upload');

                // Evaluate existing uploaded file
                Route::post('/txn_history/evaluate', [TransactionHistoryController::class, 'evaluateTransactionHistory'])
                    ->name('vendor.txn_history.evaluate');

                // Create customer, upload txn history, and evaluate in one go
                Route::post('/txn_history/upload_and_evaluate', [TransactionHistoryController::class, 'uploadAndEvaluate'])
                    ->name('vendor.txn_history.upload_and_evaluate');
            });
        });
    });
});
