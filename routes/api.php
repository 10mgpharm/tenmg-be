<?php

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
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // public routes
    Route::prefix('auth')->group(function () {
        Route::post('/signup', [SignupUserController::class, 'store'])
            ->name('signup');

        Route::post('/signin', [AuthenticatedController::class, 'store'])
            ->name('signin');

        Route::get('/email', [AuthenticatedController::class, 'emailExist'])
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
    });

    // Protected routes
    Route::middleware(['auth:api', 'scope:full'])->group(function () {

        Route::post('/resend-otp', ResendOtpController::class)
            ->name('resend.otp')->middleware('throttle:5,1');

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

            // Loan Application

            // Submit New Application via Dashboard
            Route::post('/applications', [LoanApplicationController::class, 'store']);

            // Submit Loan Application from E-commerce Site
            Route::post('/application/apply', [LoanApplicationController::class, 'applyFromEcommerce'])->withoutMiddleware(['auth:api', 'scope:full']);

            // Retrieve Vendor Customizations
            Route::get('/application/customisations', [LoanApplicationController::class, 'getCustomisations']);

            // Delete Loan Application
            Route::delete('/applications/{id}', [LoanApplicationController::class, 'destroy'])->middleware('admin');

            // View All Loan Applications
            Route::get('/applications', [LoanApplicationController::class, 'index'])->name('vendor.applications');

            // Filter Loan Applications
            Route::get('/applications/filter', [LoanApplicationController::class, 'filter']);
            Route::get('/applications/{reference}', [LoanApplicationController::class, 'getLoanApplicationByReference']);

            // View Loan Application Details
            Route::get('/applications/{id}', [LoanApplicationController::class, 'show']);

            // Approve/Reject Loan Application (10mg Admins Only)
            Route::post('/applications/{applicationId}/review', [LoanApplicationController::class, 'review'])->middleware('admin');

            // Enable/Disable Loan Application
            Route::patch('/applications/{id}', [
                LoanApplicationController::class,
                'toggleActive',
            ])->middleware('admin');

            // View All Applications for a Specific Customer
            Route::get('/applications/{customerId}', [LoanApplicationController::class, 'getCustomerApplications'])->middleware('admin');

            // Loan Offer
            // Create a new loan offer
            Route::post('/offers', [LoanOfferController::class, 'createOffer'])->name('offers.create')->middleware('admin');

            // Customer accepts or rejects a loan offer
            Route::post('/offers/{offerReference}', [LoanOfferController::class, 'handleOfferAction'])->name('offers.handleAction');

            // Get all loan offers (with filters and pagination)
            Route::get('/offers', [LoanOfferController::class, 'getAllOffers'])->name('offers.getAll')->middleware('admin');

            // Get a specific loan offer by ID
            Route::get('/offers/{id}', [LoanOfferController::class, 'getOfferById'])->name('offers.getById');

            // Delete an offer (only if it's open)
            Route::delete('/offers/{id}', [LoanOfferController::class, 'deleteOffer'])->name('offers.delete')->middleware('admin');

            // Enable or disable a loan offer (activate or deactivate)
            Route::patch('/offers/{id}', [LoanOfferController::class, 'toggleOfferStatus'])->name('offers.toggleStatus')->middleware('admin');

            // Get all offers for a specific customer
            Route::get('/offers/{customerId}/customer', [LoanOfferController::class, 'getOffersByCustomer'])->name('offers.getByCustomer');

            Route::post('/direct-debit/mandate/generate', [LoanOfferController::class, 'generateMandateForCustomer'])->name('mandate.generate');
            Route::post('/direct-debit/mandate/verify', [LoanOfferController::class, 'verifyMandateForCustomer'])->name('mandate.verify');

            // Loan
            Route::get('/loans', [
                LoanController::class,
                'getAllLoans',
            ])->name('loans.getAll')->middleware('admin');
            Route::get('/loans/{id}', [LoanController::class, 'getLoanById'])->name('loans.getById');
            Route::post('/loans/{id}/disbursed', [LoanController::class, 'disbursed'])->name('loans.disbursed');
        });

        Route::get('/{businessType}/{id}', [ProfileController::class, 'show']);
    });

    Route::post('/webhooks/vendor/direct-debit/mandate', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack.direct_debit');
});
