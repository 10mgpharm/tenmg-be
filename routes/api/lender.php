<?php

use App\Http\Controllers\API\Credit\LoanApplicationController;
use App\Http\Controllers\API\Credit\LoanController;
use App\Http\Controllers\API\Credit\TransactionHistoryController;
use App\Http\Controllers\API\Lender\LenderDashboardController;
use App\Http\Controllers\API\Lender\LoanPreferenceController;
use App\Http\Controllers\API\Storefront\OrdersController;
use App\Http\Controllers\API\Wallet\WalletController;
use App\Http\Controllers\BusinessSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Lender Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'scope:full'])->group(function () {
    Route::prefix('lender')->name('lender.')->middleware(['roleCheck:lender'])->group(function () {

        Route::prefix('wallet')->name('wallet.')->group(function () {
            // Wallets (creates if missing, otherwise returns existing) (GET)
            Route::get('/', [WalletController::class, 'createLenderWallets']);
        });

        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [LenderDashboardController::class, 'getDashboardStats']);
            Route::get('chart-stats', [LenderDashboardController::class, 'getChartStats']);
            Route::get('/generate-statement', [LenderDashboardController::class, 'generateStatement']);
            Route::post('/withdraw-funds', [LenderDashboardController::class, 'withdrawFunds']);
            Route::post('/transfer', [LenderDashboardController::class, 'transferToDepositWallet']);
        });

        Route::prefix('deposit')->name('deposit.')->group(function () {
            Route::post('/', [LenderDashboardController::class, 'initializeDeposit']);
            Route::get('/{reference}', [OrdersController::class, 'verifyFincraPayment']);
            Route::post('/cancel/{reference}', [LenderDashboardController::class, 'cancelDepositPayment']);
        });

        Route::prefix('kyc')->name('kyc.')->group(function () {
            Route::post('/prove/initiate', [\App\Http\Controllers\API\Credit\LenderProveController::class, 'initiate'])
                ->name('prove.initiate');
            Route::get('/prove/customers/{reference}', [\App\Http\Controllers\API\Credit\LenderProveController::class, 'fetchCustomerDetails'])
                ->name('prove.fetch-customer-details');
            Route::patch('/prove/sessions/{reference}/status', [\App\Http\Controllers\API\Credit\LenderProveController::class, 'updateSessionStatus'])
                ->name('prove.update-session-status');
        });

        Route::prefix('credit')->name('credit.')->group(function () {
            Route::prefix('bvn-lookup')->name('bvn-lookup.')->group(function () {
                Route::post('/initiate', [\App\Http\Controllers\API\Credit\LenderBvnLookupController::class, 'initiate'])
                    ->name('initiate');
                Route::post('/verify', [\App\Http\Controllers\API\Credit\LenderBvnLookupController::class, 'verify'])
                    ->name('verify');
                Route::post('/details', [\App\Http\Controllers\API\Credit\LenderBvnLookupController::class, 'fetchDetails'])
                    ->name('details');
                Route::get('/{session_id}', [\App\Http\Controllers\API\Credit\LenderBvnLookupController::class, 'show'])
                    ->name('show');
            });
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [BusinessSettingController::class, 'show']);
            Route::match(['post', 'patch'], 'business-account', [BusinessSettingController::class, 'businessBankAccount']);

            // Update business information
            Route::match(['post', 'patch'], 'business-information', [BusinessSettingController::class, 'businessInformation']);

            // Update business account license number, expiry date and cac doc
            Route::patch('license/withdraw', [BusinessSettingController::class, 'withdraw']);
            Route::match(['post', 'patch'], 'license', [BusinessSettingController::class, 'license']);
            Route::get('license', [BusinessSettingController::class, 'getBusinessStatus']);

            // loan preferences
            Route::patch('update-loan-preferences', [LoanPreferenceController::class, 'createUpdateLoanPreference']);
            Route::get('get-loan-preferences', [LoanPreferenceController::class, 'getLoanPreference']);
            Route::get('get-loan-preferences-prefill', [LoanPreferenceController::class, 'getLoanPreferencePrefill']);
            Route::patch('update-auto-accept-status', [LoanPreferenceController::class, 'updateAutoAcceptStatus']);
        });

        Route::prefix('loan-applications')->name('loan-applications.')->group(function () {
            Route::get('/', [LoanApplicationController::class, 'getLoanApplicationLenders']);
            Route::get('/loan-stats', [LoanApplicationController::class, 'getLoanApplicationStats']);
            Route::get('/view/{reference}', [LoanApplicationController::class, 'getLoanApplicationByReferenceResourced']);
            Route::post('/', [LoanApplicationController::class, 'approveLoanApplicationManually']);
        });

        Route::prefix('loan')->name('loan.')->group(function () {
            Route::get('/', [LoanController::class, 'getLoanList'])->name('lender.loan.getAllLoans');
            Route::get('/detail/{id}', [LoanController::class, 'getLoanDetails'])->name('lender.loan.getLoanDetails');
            Route::get('/stats', [LoanController::class, 'getLoanStats'])->name('lender.loan.getLoanStats');
            Route::get('/loan-status-count', [LoanController::class, 'getLoanStatusCount'])->name('lender.loan.getLoanStatusCount');

        });

        Route::prefix('txn_history')->group(function () {

            // download uploaded transaction history file
            Route::post('/download/{txnEvaluationId}', [TransactionHistoryController::class, 'downloadTransactionHistory'])
                ->name('lender.txn_history.download');

            Route::get('creditscore-breakdown/{txnEvaluationId}', [TransactionHistoryController::class, 'creditScoreBreakDown'])->name('lender.txn_history.creditScoreBreakDown');

            Route::get('/{customerId}', [TransactionHistoryController::class, 'index'])->name('lender.txn_history');

            Route::post('/view', [TransactionHistoryController::class, 'viewTransactionHistory'])
                ->name('lender.txn_history.view');

        });

        Route::prefix('earnings')->name('earnings.')->group(function () {

            Route::get('/', [LoanController::class, 'getEarningHistory']);
            Route::get('/stats', [LoanController::class, 'getEarnings']);

        });

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/stats', [TransactionHistoryController::class, 'getTransactionStats']);
            Route::get('/', [TransactionHistoryController::class, 'getCreditTransactionHistories']);
        });

    });
});
