<?php

use App\Http\Controllers\Admin\AppNotificationController;
use App\Http\Controllers\API\Admin\AdminWalletController;
use App\Http\Controllers\API\Admin\AuditLogController;
use App\Http\Controllers\API\Admin\BusinessLicenseController;
use App\Http\Controllers\API\Admin\CarouselImageController;
use App\Http\Controllers\API\Admin\CurrencyController as AdminCurrencyController;
use App\Http\Controllers\API\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\API\Admin\EcommerceBrandController as AdminEcommerceBrandController;
use App\Http\Controllers\API\Admin\EcommerceCategoryController as AdminEcommerceCategoryController;
use App\Http\Controllers\API\Admin\EcommerceMeasurementController as AdminEcommerceMeasurementController;
use App\Http\Controllers\API\Admin\EcommerceOrderController;
use App\Http\Controllers\API\Admin\EcommercePresentationController as AdminEcommercePresentationController;
use App\Http\Controllers\API\Admin\EcommerceProductController as AdminEcommerceProductController;
use App\Http\Controllers\API\Admin\EcommerceWalletController as AdminEcommerceWalletController;
use App\Http\Controllers\API\Admin\FaqController;
use App\Http\Controllers\API\Admin\LenderKycController;
use App\Http\Controllers\API\Admin\MedicationTypeController as AdminMedicationTypeController;
use App\Http\Controllers\API\Admin\ProductInsightsController as AdminProductInsightsController;
use App\Http\Controllers\API\Admin\ServiceProviderController as AdminServiceProviderController;
use App\Http\Controllers\API\Admin\SettingConfigController;
use App\Http\Controllers\API\Admin\ShippingFeeController;
use App\Http\Controllers\API\Admin\UsersController;
use App\Http\Controllers\API\Admin\WithdrawFundController as AdminWithdrawFundController;
use App\Http\Controllers\API\Credit\ApiKeyController;
use App\Http\Controllers\API\Credit\LoanApplicationController;
use App\Http\Controllers\API\Credit\LoanController;
use App\Http\Controllers\API\Credit\LoanRepaymentController;
use App\Http\Controllers\API\Credit\TransactionHistoryController;
use App\Http\Controllers\API\EcommerceDiscountController;
use App\Http\Controllers\API\Storefront\ShoppingListController;
use App\Http\Controllers\API\Supplier\AddBankAccountController;
use App\Http\Controllers\API\Supplier\EcommerceTransactionController;
use App\Http\Controllers\API\Supplier\GetBankAccountController;
use App\Http\Controllers\API\Supplier\UpdateBankAccountController;
use App\Http\Controllers\API\Vendor\VendorWalletController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\InviteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'scope:full'])->group(function () {
    Route::prefix('admin')->name('admin.')->middleware(['roleCheck:admin'])->group(function () {
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('invite/team-members', [InviteController::class, 'members'])->name('invite.team-members');
            Route::apiResource('invite', InviteController::class);

            Route::apiResource('medication-types', AdminMedicationTypeController::class);
            Route::get('medication-types/{medication_type:id}/medication-variations', [AdminMedicationTypeController::class, 'getVariationsByMedicationType']);

            Route::apiResource('notification', AppNotificationController::class);
            Route::apiResource('categories', AdminEcommerceCategoryController::class);

            Route::get('measurements/dropdown', [AdminEcommerceMeasurementController::class, 'getDropdown']);
            Route::get('presentations/dropdown', [AdminEcommercePresentationController::class, 'getDropdown']);

            Route::get('measurements/search', [AdminEcommerceMeasurementController::class, 'search']);
            Route::apiResource('measurements', AdminEcommerceMeasurementController::class);
            Route::get('presentations/search', [AdminEcommercePresentationController::class, 'search']);
            Route::apiResource('presentations', AdminEcommercePresentationController::class);

            Route::get('products/search', [AdminEcommerceProductController::class, 'search']);

            Route::get('products', [AdminEcommerceProductController::class, 'index']);
            Route::get('products/{product}', [AdminEcommerceProductController::class, 'show']);
            Route::post('products', [AdminEcommerceProductController::class, 'store']);
            Route::match(['PUT', 'PATCH', 'POST'], 'products/{product}', [AdminEcommerceProductController::class, 'update']);
            Route::delete('products/{product}', [AdminEcommerceProductController::class, 'destroy']);

            Route::apiResource('brands', AdminEcommerceBrandController::class);
            Route::apiResource('faqs', FaqController::class);
            Route::get('audit-logs', [AuditLogController::class, 'index']);
            Route::get('audit-logs/search', [AuditLogController::class, 'search']);
            Route::get('shipping-fee', [ShippingFeeController::class, 'index']);
            Route::post('shipping-fee', [ShippingFeeController::class, 'store']);
            Route::match(['patch', 'put'], 'shipping-fee', [ShippingFeeController::class, 'update']);

            Route::get('/', [BusinessSettingController::class, 'show']);
            // Update business information
            Route::match(['post', 'patch'], 'business-information', [BusinessSettingController::class, 'businessInformation']);

            Route::prefix('config')->group(function () {
                Route::get('/', [SettingConfigController::class, 'getAllSettings']);
                Route::post('/', [SettingConfigController::class, 'updateSettingsConfig']);

            });

            Route::prefix('api-manage')->group(function () {
                Route::get('/', [ApiKeyController::class, 'getVendorsWithAccess']);
                Route::post('/', [ApiKeyController::class, 'revokeApiKey']);

            });

        });

        // Service providers management (admin only)
        Route::get('service-providers', [AdminServiceProviderController::class, 'index']);
        Route::get('service-providers/{serviceProvider}', [AdminServiceProviderController::class, 'show']);
        Route::match(['put', 'patch'], 'service-providers/{serviceProvider}', [AdminServiceProviderController::class, 'update']);

        // Currencies management (admin only)
        Route::get('currencies', [AdminCurrencyController::class, 'index']);
        Route::get('currencies/{currency}', [AdminCurrencyController::class, 'show']);
        Route::match(['put', 'patch'], 'currencies/{currency}', [AdminCurrencyController::class, 'update']);

        Route::get('discounts/count', [EcommerceDiscountController::class, 'count']);
        Route::get('discounts/search', [EcommerceDiscountController::class, 'search']);
        Route::apiResource('discounts', EcommerceDiscountController::class);
        Route::patch('users/{user}/status', [UsersController::class, 'status']);
        Route::apiResource('users', UsersController::class);

        Route::get('business/licenses', [BusinessLicenseController::class, 'index']);
        Route::match(['put', 'patch'], 'business/licenses/{business}/status', [BusinessLicenseController::class, 'update']);

        Route::prefix('system-setup')->name('system-setup.')->group(function () {
            Route::get('storefront-images/search', [CarouselImageController::class, 'search']);
            Route::apiResource('storefront-images', CarouselImageController::class);
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('get-orders', [EcommerceOrderController::class, 'getOrderByStatus']);
            Route::get('get-orders-status-count', [EcommerceOrderController::class, 'getOrderByStatusCount']);
            Route::post('change-order-status', [EcommerceOrderController::class, 'changeOrderStatus']);
            Route::get('get-order-details/{id}', [EcommerceOrderController::class, 'getOrderDetails']);
        });

        Route::prefix('shopping-list')->name('shopping-list.')->group(function () {
            Route::get('/', [ShoppingListController::class, 'getShoppingListAdmin']);
            Route::post('add-shopping-list', [ShoppingListController::class, 'addShoppingList']);
            Route::delete('remove-item/{id}', [ShoppingListController::class, 'removeItemFromSHoppingList']);
        });

        Route::prefix('loan-application')->name('loan-application.')->group(function () {
            Route::get('/', [LoanApplicationController::class, 'index'])->name('admin.applications');
            Route::get('/{reference}', [LoanApplicationController::class, 'getLoanApplicationByReference'])->name('admin.applications.getLoanApplicationByReference');
        });

        Route::prefix('loan')->name('loan.')->group(function () {
            Route::get('/', [LoanController::class, 'getLoanList'])->name('admin.loan.getAllLoans');
            Route::get('/detail/{id}', [LoanController::class, 'getLoanDetails'])->name('admin.loan.getLoanDetails');
            Route::get('/stats', [LoanController::class, 'getLoanStats'])->name('admin.loan.getLoanStats');
            Route::get('/loan-status-count', [LoanController::class, 'getLoanStatusCount'])->name('admin.loan.getLoanStatusCount');

        });

        Route::prefix('loan-repayment')->name('loan-repayment.')->group(function () {
            Route::get('/', [LoanRepaymentController::class, 'getListOfLoanRepayments'])->name('admin.loan-repayment.getListOfLoanRepayments');
            Route::get('/test-repayment-mail/{loanRef}', [LoanRepaymentController::class, 'sentTestRepayPaymentMail'])->name('admin.repayment.test-repayment-mail');

        });

        Route::prefix('lender-kyc')->name('lender-kyc.')->group(function () {
            Route::get('/', [LenderKycController::class, 'index'])->name('index');
            Route::get('/stats', [LenderKycController::class, 'stats'])->name('stats');
            Route::get('/{session}', [LenderKycController::class, 'show'])->name('show');
            Route::patch('/{session}/status', [LenderKycController::class, 'updateStatus'])->name('update-status');
            Route::post('/complete-tier', [LenderKycController::class, 'completeTier'])->name('complete-tier');
        });

        Route::get('insights/filters', [AdminProductInsightsController::class, 'filters']);
        Route::get('insights', [AdminProductInsightsController::class, 'insights']);

        Route::prefix('txn_history')->group(function () {

            // download uploaded transaction history file
            Route::post('/download/{txnEvaluationId}', [TransactionHistoryController::class, 'downloadTransactionHistory'])
                ->name('admin.txn_history.download');

            Route::get('creditscore-breakdown/{txnEvaluationId}', [TransactionHistoryController::class, 'creditScoreBreakDown'])->name('admin.txn_history.creditScoreBreakDown');

            Route::get('/{customerId}', [TransactionHistoryController::class, 'index'])->name('admin.txn_history');

            Route::post('/view', [TransactionHistoryController::class, 'viewTransactionHistory'])
                ->name('admin.txn_history.view');

        });

        Route::get('/wallet-product', AdminEcommerceWalletController::class);
        Route::prefix('wallet')->group(function () {
            Route::get('bank-account', GetBankAccountController::class);
            Route::patch('add-bank-account/{bank_account}', UpdateBankAccountController::class);
            Route::post('add-bank-account', AddBankAccountController::class);
            Route::get('/', [AdminWalletController::class, 'getWalletStats']);
            Route::get('/transactions', [AdminWalletController::class, 'getTransactions']);
            Route::get('/admin-transactions', [AdminWalletController::class, 'getAdminTransactions']);
            Route::get('/payouts', [AdminWalletController::class, 'getPayOutTransactions']);
            Route::prefix('user')->group(function () {
                Route::get('/{businessId}', [AdminWalletController::class, 'getWalletUserStats']);
                Route::get('/lender/transactions', [TransactionHistoryController::class, 'getCreditTransactionHistories']);
                Route::get('/vendor/transactions', [VendorWalletController::class, 'getTransactions']);
                Route::get('supplier/transactions', EcommerceTransactionController::class);
            });
        });

        Route::get('dashboard', AdminDashboardController::class);
        Route::post('withdraw-funds', AdminWithdrawFundController::class);
    });
});
