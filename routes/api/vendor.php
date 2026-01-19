<?php

use App\Http\Controllers\API\Credit\ApiKeyController;
use App\Http\Controllers\API\Credit\CustomerController;
use App\Http\Controllers\API\Credit\LoanApplicationController;
use App\Http\Controllers\API\Credit\LoanController;
use App\Http\Controllers\API\Credit\LoanOfferController;
use App\Http\Controllers\API\Credit\LoanRepaymentController;
use App\Http\Controllers\API\Credit\MonoCustomerController;
use App\Http\Controllers\API\Credit\TransactionHistoryController;
use App\Http\Controllers\API\Supplier\AddBankAccountController;
use App\Http\Controllers\API\Supplier\GetBankAccountController;
use App\Http\Controllers\API\Supplier\UpdateBankAccountController;
use App\Http\Controllers\API\Vendor\AuditLogController as VendorAuditLogController;
use App\Http\Controllers\API\Vendor\UsersController as VendorUsersController;
use App\Http\Controllers\API\Vendor\VendorApiAuditLogController;
use App\Http\Controllers\API\Vendor\VendorDashboardController;
use App\Http\Controllers\API\Vendor\VendorWalletController;
use App\Http\Controllers\API\Wallet\WalletController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\InviteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'scope:full'])->group(function () {
    Route::prefix('vendor')->middleware(['roleCheck:vendor'])->group(function () {

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [BusinessSettingController::class, 'show']);

            // Update business information
            Route::match(['post', 'patch'], 'business-information', [BusinessSettingController::class, 'businessInformation']);

            // Update business account license number, expiry date and cac doc
            Route::patch('license/withdraw', [BusinessSettingController::class, 'withdraw']);
            Route::match(['post', 'patch'], 'license', [BusinessSettingController::class, 'license']);
            Route::get('license', [BusinessSettingController::class, 'getBusinessStatus']);

            // Invites/Team members
            Route::get('invite/team-members', [InviteController::class, 'members'])->name('invite.team-members');
            Route::apiResource('invite', InviteController::class);

            Route::patch('users/{user}/status', [VendorUsersController::class, 'status']);
            Route::apiResource('users', VendorUsersController::class);
        });

        Route::prefix('customers')->group(function () {
            // List customers with pagination and filtering
            Route::get('/', [CustomerController::class, 'index'])->name('customers.index');

            // List all customers
            Route::get('/get-all', [CustomerController::class, 'getAllCustomers'])->name('customers.getAllCustomers');

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

        Route::prefix('txn_history')->group(function () {
            // Upload transaction history file (min of 6 months)
            Route::post('/upload', [TransactionHistoryController::class, 'uploadTransactionHistory'])
                ->name('vendor.txn_history.upload');

            // download uploaded transaction history file
            Route::post('/download/{txnEvaluationId}', [TransactionHistoryController::class, 'downloadTransactionHistory'])
                ->name('vendor.txn_history.download');

            // view uploaded transaction history
            Route::post('/view', [TransactionHistoryController::class, 'viewTransactionHistory'])
                ->name('vendor.txn_history.view');

            // Evaluate existing uploaded file
            Route::post('/evaluate', [TransactionHistoryController::class, 'evaluateTransactionHistory'])
                ->name('vendor.txn_history.evaluate');

            // Create customer, upload txn history, and evaluate in one go
            Route::post('/upload_and_evaluate', [TransactionHistoryController::class, 'uploadAndEvaluate'])
                ->name('vendor.txn_history.upload_and_evaluate');

            Route::get('get-all-txn', [TransactionHistoryController::class, 'listAllTransactions'])->name('vendor.txn_history.listAllTransactions');

            Route::get('creditscore-breakdown/{txnEvaluationId}', [TransactionHistoryController::class, 'creditScoreBreakDown'])->name('vendor.txn_history.creditScoreBreakDown');

            Route::get('get-all-creditscore', [TransactionHistoryController::class, 'listAllCreditScore'])->name('vendor.txn_history.listAllCreditScore');

            Route::get('/{customerId}', [TransactionHistoryController::class, 'index'])->name('vendor.txn_history');

        });

        // Mono Customer Management
        Route::prefix('credit')->group(function () {
            Route::prefix('mono-customers')->group(function () {
                // List all Mono customers
                Route::get('/', [MonoCustomerController::class, 'list'])
                    ->name('vendor.credit.mono-customers.list');

                // Retrieve a Mono customer by ID
                Route::get('/{id}', [MonoCustomerController::class, 'retrieve'])
                    ->name('vendor.credit.mono-customers.retrieve');

                // Delete a Mono customer by ID
                Route::delete('/{id}', [MonoCustomerController::class, 'delete'])
                    ->name('vendor.credit.mono-customers.delete');
            });
        });

        // Loan Application
        Route::prefix('loan-applications')->group(function () {

            // Submit New Application via Dashboard
            Route::post('/', [LoanApplicationController::class, 'store'])->name('vendor.applications.create');

            // View All Loan Applications
            Route::get('/', [LoanApplicationController::class, 'index'])->name('vendor.applications');

            // Submit Loan Application link
            Route::post('/send-application-link', [
                LoanApplicationController::class,
                'sendApplicationLink',
            ])->name('vendor.applications.apply');

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

        Route::post('/direct-debit/mandate/generate', [LoanOfferController::class, 'generateMandateForCustomer'])->name('mandate.generate')->withoutMiddleware(['roleCheck:vendor', 'scope:full']);
        Route::post('/direct-debit/mandate/verify', [LoanOfferController::class, 'verifyMandateForCustomer'])->name('mandate.verify');

        // Loan
        Route::prefix('loans')->group(function () {
            Route::get('/', [LoanController::class, 'getLoanList'])->name('loans.getAll');
            Route::get('/{id}', [LoanController::class, 'getLoanById'])->name('loans.getById');
            Route::post('/{id}/disbursed', [LoanController::class, 'disbursed'])->name('loans.disbursed');
            Route::get('/view/stats', [LoanController::class, 'getLoanStats'])->name('vendor.loan.getLoanStats');
            Route::get('/stats/loan-status-count', [LoanController::class, 'getLoanStatusCount'])->name('vendor.loan.getLoanStatusCount');

            Route::prefix('repayments')->group(function () {
                Route::post('/{id}/repay', [LoanController::class, 'repayLoan'])->name('loans.repay');
                Route::post('/{id}/liquidate', [LoanController::class, 'liquidateLoan'])->name('loans.liquidate');
            });
        });

        Route::prefix('loan-repayment')->name('loan-repayment.')->group(function () {
            Route::get('/', [LoanRepaymentController::class, 'getListOfLoanRepayments'])->name('vendor.loan-repayment.getListOfLoanRepayments');

        });

        Route::prefix('api_keys')->group(function () {
            // Get api key
            Route::get('/', [ApiKeyController::class, 'index'])->name('apikeys.index');

            // Generate api keys
            Route::patch('/', [ApiKeyController::class, 'update'])->name('apikeys.update');

            // Generate api keys
            Route::post('generate', [ApiKeyController::class, 'regenerateKey'])->name('apikeys.generate');
        });

        Route::prefix('wallet')->group(function () {
            Route::get('bank-account', GetBankAccountController::class);
            Route::patch('add-bank-account/{bank_account}', UpdateBankAccountController::class);
            Route::post('add-bank-account', AddBankAccountController::class);
            // Wallet stats
            Route::get('/stats', [VendorWalletController::class, 'getWalletStats']);
            Route::get('/transactions', [VendorWalletController::class, 'getTransactions']);
            // Wallets (creates if missing, otherwise returns existing) (GET)
            Route::get('/', [WalletController::class, 'createVendorWallets']);
        });

        Route::get('audit-logs', [VendorAuditLogController::class, 'index']);
        Route::get('audit-logs/search', [VendorAuditLogController::class, 'search']);

        Route::prefix('withdrawals')->group(function () {
            Route::post('/init', [VendorWalletController::class, 'initWithdrawals']);
            Route::post('/withdraw-funds', [VendorWalletController::class, 'withdrawFunds']);
        });

        Route::prefix('api-logs')->group(function () {
            Route::get('/', [VendorApiAuditLogController::class, 'getApiLogs']);
            Route::get('/webhook', [VendorApiAuditLogController::class, 'getWebHookLogs']);
        });

        Route::prefix('dashboard')->group(function () {
            Route::get('/', [VendorDashboardController::class, 'getDashboardStats'])->name('vendor.dashboard.getDashboardStats');
            Route::get('/graph-stats', [VendorDashboardController::class, 'getGraphStats'])->name('vendor.dashboard.getGraphStats');
        });
    });
});
