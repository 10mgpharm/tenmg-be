<?php

use App\Http\Controllers\API\Account\AccountController;
use App\Http\Controllers\API\Account\PasswordUpdateController;
use App\Http\Controllers\API\Account\TwoFactorAuthenticationController;
use App\Http\Controllers\API\Auth\AuthenticatedController;
use App\Http\Controllers\API\Auth\PasswordController;
use App\Http\Controllers\API\Auth\SignupUserController;
use App\Http\Controllers\API\Auth\VerifyEmailController;
use App\Http\Controllers\API\Credit\CustomerController;
use App\Http\Controllers\API\Credit\LoanApplicationController;
use App\Http\Controllers\API\Credit\LoanController;
use App\Http\Controllers\API\Credit\LoanOfferController;
use App\Http\Controllers\API\Credit\TransactionHistoryController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ResendOtpController;
use App\Http\Controllers\API\Webhooks\PaystackWebhookController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\InviteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // public routes
    Route::prefix('auth')->name('guest.')->group(function () {
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

        Route::get('invite/view', [InviteController::class, 'view'])->name('invite.view');
        Route::post('invite/accept', [InviteController::class, 'accept'])->name('invite.accept')->middleware('signed');
        Route::post('invite/reject', [InviteController::class, 'reject'])->name('invite.reject')->middleware('signed');
    });

    // Account specific operations
    Route::prefix('account')->name('account.')->middleware(['auth:api'])->group(function () {
        Route::prefix('settings')->name('settings.')->group(function () {

            Route::middleware('scope:full')->group(function () {

                // Update authenticated user's password
                Route::patch('password', PasswordUpdateController::class);

                // Update authenticated user's profile
                Route::match(['post', 'patch'], 'profile', [AccountController::class, 'profile']);

                // 2FA
                Route::prefix('2fa')->controller(TwoFactorAuthenticationController::class)
                    ->group(function () {
                        Route::get('setup', 'setup');
                        Route::post('reset', 'reset');
                        Route::post('toggle', 'toggle');  // Toggle 2FA (enable/disable)
                    });
            });

            // 2FA
            Route::post('2fa/verify', [TwoFactorAuthenticationController::class, 'verify'])
                ->middleware(['scope:full,temp']);
        });
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
                    Route::match(['post', 'patch'], 'personal-information', 'personalInformation');

                    // Update business account license number, expiry date and cac doc
                    Route::match(['post', 'patch'], 'license', 'accountSetup');
                });
        });

        // supplier specific operations
        Route::prefix('supplier')->group(function () {
            Route::get('/{id}', [ProfileController::class, 'show']);
        });

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

            Route::get('/{id}', [ProfileController::class, 'show']);

            Route::prefix('business')->name('vendor.business.')->group(function () {
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('invite/team-members', [InviteController::class, 'members'])->name('invite.team-members');
                    Route::apiResource('invite', InviteController::class);
                });
            });

            // Upload transaction history file (min of 6 months)
            Route::post('/txn_history/upload', [TransactionHistoryController::class, 'uploadTransactionHistory'])
                ->name('vendor.txn_history.upload');

            // Evaluate existing uploaded file
            Route::post('/txn_history/evaluate', [TransactionHistoryController::class, 'evaluateTransactionHistory'])
                ->name('vendor.txn_history.evaluate');

            // Create customer, upload txn history, and evaluate in one go
            Route::post('/txn_history/upload_and_evaluate', [TransactionHistoryController::class, 'uploadAndEvaluate'])
                ->name('vendor.txn_history.upload_and_evaluate');

            // Loan Application
            Route::prefix('loan-applications')->group(function () {

                // Submit New Application via Dashboard
                Route::post('/', [LoanApplicationController::class, 'store'])->name('vendor.applications.create');

                // View All Loan Applications
                Route::get('/', [LoanApplicationController::class, 'index'])->name('vendor.applications');

                // Submit Loan Application from E-commerce Site
                Route::post('/apply', [
                    LoanApplicationController::class,
                    'applyFromEcommerce',
                ])->name('vendor.applications.apply')->withoutMiddleware(['auth:api', 'scope:full']);

                // Retrieve Vendor Customizations
                Route::get('/customisations', [LoanApplicationController::class, 'getCustomisations'])->name('vendor.applications.customisations');

                // Filter Loan Applications
                Route::get('/filter', [
                    LoanApplicationController::class,
                    'filter',
                ])->name('vendor.applications.filter');

                // Enable/Disable Loan Application
                Route::patch(
                    '/{id}',
                    [LoanApplicationController::class, 'toggleActive']
                )->name('vendor.applications.toggleActive')->middleware('admin');

                Route::get('/{reference}', [LoanApplicationController::class, 'getLoanApplicationByReference'])->name('vendor.applications.getLoanApplicationByReference');

                // View All Applications for a Specific Customer
                Route::get('/customer/{customerId}', [LoanApplicationController::class, 'getCustomerApplications'])->name('vendor.applications.getByCustomer')->middleware('admin');

                // View Loan Application Details
                Route::get('/view/{id}', [LoanApplicationController::class, 'show'])->name('vendor.applications.view');

                // Delete Loan Application
                Route::delete('/{id}', [
                    LoanApplicationController::class,
                    'destroy',
                ])->middleware('admin');

                // Approve/Reject Loan Application (10mg Admins Only)
                Route::post('/{applicationId}/review', [LoanApplicationController::class, 'review'])->name('vendor.applications.review')->middleware('admin');
            });

            // Loan Offer
            Route::prefix('offers')->group(function () {
                // Create a new loan offer
                Route::post('/', [LoanOfferController::class, 'createOffer'])->name('offers.create')->middleware('admin');

                // Customer accepts or rejects a loan offer
                Route::post('/{offerReference}', [LoanOfferController::class, 'handleOfferAction'])->name('offers.handleAction');

                // Get all loan offers (with filters and pagination)
                Route::get('/', [LoanOfferController::class, 'getAllOffers'])->name('offers.getAll')->middleware('admin');

                // Get a specific loan offer by ID
                Route::get('/{id}', [LoanOfferController::class, 'getOfferById'])->name('offers.getById');

                // Delete an offer (only if it's open)
                Route::delete('/{id}', [LoanOfferController::class, 'deleteOffer'])->name('offers.delete')->middleware('admin');

                // Enable or disable a loan offer (activate or deactivate)
                Route::patch('/{id}', [LoanOfferController::class, 'toggleOfferStatus'])->name('offers.toggleStatus')->middleware('admin');

                // Get all offers for a specific customer
                Route::get('/{customerId}/customer', [LoanOfferController::class, 'getOffersByCustomer'])->name('offers.getByCustomer');
            });

            Route::post('/direct-debit/mandate/generate', [LoanOfferController::class, 'generateMandateForCustomer'])->name('mandate.generate');
            Route::post('/direct-debit/mandate/verify', [LoanOfferController::class, 'verifyMandateForCustomer'])->name('mandate.verify');

            // Loan
            Route::prefix('loans')->group(function () {
                Route::get('/', [LoanController::class, 'getAllLoans'])->name('loans.getAll')->middleware('admin');
                Route::get('/{id}', [LoanController::class, 'getLoanById'])->name('loans.getById');
                Route::post('/{id}/disbursed', [LoanController::class, 'disbursed'])->name('loans.disbursed');
            });

            Route::get('/{businessType}/{id}', [ProfileController::class, 'show']);
        });

    });

    Route::post('/webhooks/vendor/direct-debit/mandate', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack.direct_debit');
});
