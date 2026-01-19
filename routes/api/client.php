<?php

use App\Http\Controllers\API\Bank\BankController;
use App\Http\Controllers\API\Credit\ClientController;
use App\Http\Controllers\API\Credit\LoanApplicationController;
use App\Http\Controllers\API\Credit\LoanPreferenceController as CreditLoanPreferenceController;
use App\Http\Controllers\API\Credit\LoanRepaymentController;
use App\Http\Controllers\API\Credit\MonoCustomerController;
use App\Http\Controllers\API\Credit\TransactionHistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('client')->group(function () {

    // [BNPL] get customers
    Route::get('/customers', [ClientController::class, 'getCustomers'])
        ->middleware('clientAuth')
        ->name('client.customers');

    // [BNPL] Match lender with vendor customer
    // Supports both modes:
    // 1. With request_id: fetches stored payload from Tenmg credit request
    // 2. Without request_id: accepts full payload directly
    // Protected by clientAuth so we can resolve the vendor business from API keys
    Route::post('/loan/match', [CreditLoanPreferenceController::class, 'match'])
        ->middleware('clientAuth')
        ->name('loan.match');

    Route::prefix('credit')->group(function () {
        Route::post('/check-credit-worthiness', [TransactionHistoryController::class, 'testMonoCreditWorthiness'])
            ->middleware('clientAuth')
            ->name('client.credit.check-credit-worthiness');

        // Initiate Mono GSM mandate after credit check
        Route::post('/initiate-mandate', [TransactionHistoryController::class, 'initiateMandate'])
            ->middleware('clientAuth')
            ->name('client.credit.initiate-mandate');

        // Verify Mono mandate status
        Route::get('/verify-mandate/{mandate_id}', [TransactionHistoryController::class, 'verifyMandate'])
            ->middleware('clientAuth')
            ->name('client.credit.verify-mandate');

        // Update Mono mandate status
        Route::match(['put', 'patch'], '/update-mandate-status/{mandate_id}', [TransactionHistoryController::class, 'updateMandateStatus'])
            ->middleware('clientAuth')
            ->name('client.credit.update-mandate-status');

        // Update lender match status (also updates related mandate status)
        Route::match(['put', 'patch'], '/update-match-status/{borrower_reference}', [TransactionHistoryController::class, 'updateMatchStatus'])
            ->middleware('clientAuth')
            ->name('client.credit.update-match-status');

        Route::prefix('tenmg')->group(function () {
            Route::post('/initiate', [\App\Http\Controllers\API\Credit\TenmgCreditController::class, 'initiate'])
                ->middleware('clientAuth')
                ->name('client.credit.tenmg.initiate');

            Route::get('/requests/{request_id}', [\App\Http\Controllers\API\Credit\TenmgCreditController::class, 'show'])
                ->middleware('clientAuth')
                ->name('client.credit.tenmg.show');
        });
    });

    // [BNPL] get banks
    Route::prefix('banks')->group(function () {
        Route::match(['post', 'patch'], '/', [BankController::class, 'store'])
            ->middleware(['auth:api'])
            ->name('client.bank.store');

        Route::get('/', [BankController::class, 'getBankList'])
            ->middleware(['auth:api'])
            ->name('client.bank.list');

        Route::get('/default/{customer:identifier}', [BankController::class, 'getDefaultBank'])
            ->middleware(['auth:api'])
            ->name('client.bank.default');

        Route::post('/account/verify', [BankController::class, 'verifyBankAccount'])
            ->middleware(['auth:api'])
            ->name('client.bank.verify');
    });

    Route::prefix('applications')->group(function () {

        // [BNPL] start application
        Route::post('/start', [ClientController::class, 'startApplication'])
            ->middleware('clientAuth')
            ->name('client.applications.start');

        // [BNPL] get application config
        Route::get('/config/{reference}', [LoanApplicationController::class, 'verifyApplicationLink'])
            ->middleware(['clientAuth'])
            ->name('client.applications.config');

        // [BNPL] create customer mandate
        Route::post('/mandate/create-mandate', [LoanApplicationController::class, 'generateMandateForCustomerClient'])
            ->middleware(['auth:api'])
            ->name('client.applications.mandate.create-mandate');

        // [BNPL] verify customer mandate
        Route::get('/mandate/verify/{reference}', [LoanApplicationController::class, 'verifyMandateStatus'])
            ->middleware(['auth:api'])
            ->name('client.applications.mandate.verify');

        // verify payment for 10mg credit
        Route::get('/payment/verify/{reference}', [LoanApplicationController::class, 'verifyLoanApplicationStatus'])
            ->middleware(['clientAuth'])
            ->name('client.applications.payment.verify');

        // [BNPL] get application status for order confirmation
        Route::post('/status', [LoanApplicationController::class, 'getApplicationStatus'])
            ->middleware('clientAuth')
            ->name('client.applications.status');

        // [BNPL] cancel application
        Route::get('/{reference}/cancel', [LoanApplicationController::class, 'cancelApplication'])
            ->middleware('clientAuth')
            ->name('client.applications.cancel');
    });

    Route::prefix('repayment')->group(function () {
        Route::get('/verify/{reference}', [LoanRepaymentController::class, 'verifyRepaymentLink'])
            ->middleware(['auth:api'])
            ->name('client.repayment.config');

        Route::post('/', [LoanRepaymentController::class, 'initiateRepayment'])
            ->middleware(['auth:api'])
            ->name('client.repayment');

        Route::get('/cancel-payment/{paymentRef}', [LoanRepaymentController::class, 'cancelPayment'])
            ->middleware(['auth:api'])
            ->name('client.repayment.cancel-payment');

        Route::get('/verify-payment/{reference}', [LoanRepaymentController::class, 'verifyFincraPayment'])
            ->middleware(['auth:api'])
            ->name('client.repayment.verify-payment');

    });
});

// Public Mono Customer Management (No authentication required)
Route::prefix('client')->group(function () {
    Route::prefix('mono-customers')->group(function () {
        // List all Mono customers (Public - no auth required)
        Route::get('/', [MonoCustomerController::class, 'list'])
            ->name('client.credit.mono-customers.list');

        // Retrieve a Mono customer by ID (Public - no auth required)
        Route::get('/{id}', [MonoCustomerController::class, 'retrieve'])
            ->name('client.credit.mono-customers.retrieve');

        // Delete a Mono customer by ID (Public - no auth required)
        Route::delete('/{id}', [MonoCustomerController::class, 'delete'])
            ->name('client.credit.mono-customers.delete');
    });
});
