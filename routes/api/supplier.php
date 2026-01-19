<?php

use App\Http\Controllers\API\Admin\EcommerceOrderController;
use App\Http\Controllers\API\Admin\MedicationTypeController as AdminMedicationTypeController;
use App\Http\Controllers\API\Supplier\AddBankAccountController;
use App\Http\Controllers\API\Supplier\EcommercePendingPayoutController;
use App\Http\Controllers\API\Supplier\EcommerceProductController as SupplierEcommerceProductController;
use App\Http\Controllers\API\Supplier\EcommerceStoreAddressController;
use App\Http\Controllers\API\Supplier\EcommerceTransactionController;
use App\Http\Controllers\API\Supplier\EcommerceWalletController;
use App\Http\Controllers\API\Supplier\GetBankAccountController;
use App\Http\Controllers\API\Supplier\ProductInsightsController as SupplierProductInsightsController;
use App\Http\Controllers\API\Supplier\UpdateBankAccountController;
use App\Http\Controllers\API\WithdrawFundController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\Supplier\DashboardController as SupplierDashboardController;
use App\Http\Controllers\Supplier\EcommerceBrandController as SupplierEcommerceBrandController;
use App\Http\Controllers\Supplier\EcommerceCategoryController as SupplierEcommerceCategoryController;
use App\Http\Controllers\Supplier\EcommerceMeasurementController as SupplierEcommerceMeasurementController;
use App\Http\Controllers\Supplier\EcommerceMedicationTypeController as SupplierEcommerceMedicationTypeController;
use App\Http\Controllers\Supplier\EcommerceOrderController as SupplierEcommerceOrderController;
use App\Http\Controllers\Supplier\EcommercePresentationController as SupplierEcommercePresentationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supplier Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'scope:full'])->group(function () {
    Route::prefix('supplier')->middleware(['roleCheck:supplier'])->group(function () {
        Route::get('dashboard', SupplierDashboardController::class);

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [BusinessSettingController::class, 'show']);

            // Update business information
            Route::match(['post', 'patch'], 'business-information', [BusinessSettingController::class, 'businessInformation']);

            // Update business account license number, expiry date and cac doc
            Route::patch('license/withdraw', [BusinessSettingController::class, 'withdraw']);
            Route::match(['post', 'patch'], 'license', [BusinessSettingController::class, 'license']);
            Route::get('license', [BusinessSettingController::class, 'getBusinessStatus']);

            Route::get('medication-types/{medication_type:id}/medication-variations', [AdminMedicationTypeController::class, 'getVariationsByMedicationType']);
        });

        Route::get('products/search', [SupplierEcommerceProductController::class, 'search']);

        Route::get('products', [SupplierEcommerceProductController::class, 'index']);
        Route::get('products/{product}', [SupplierEcommerceProductController::class, 'show']);
        Route::post('products', [SupplierEcommerceProductController::class, 'store']);
        Route::match(['PUT', 'PATCH', 'POST'], 'products/{product}', [SupplierEcommerceProductController::class, 'update']);
        Route::delete('products/{product}', [SupplierEcommerceProductController::class, 'destroy']);

        Route::get('brands', SupplierEcommerceBrandController::class);
        Route::get('categories', SupplierEcommerceCategoryController::class);
        Route::get('medication-types', SupplierEcommerceMedicationTypeController::class);
        Route::get('measurements', SupplierEcommerceMeasurementController::class);
        Route::get('presentations', SupplierEcommercePresentationController::class);

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [SupplierEcommerceOrderController::class, 'getOrderByStatusSuppliers']);
            Route::get('get-order-details/{id}', [SupplierEcommerceOrderController::class, 'getOrderDetailsSuppliers']);
            Route::get('get-orders-status-count', [EcommerceOrderController::class, 'getOrderByStatusCount']);
        });

        Route::get('insights/filters', [SupplierProductInsightsController::class, 'filters']);
        Route::get('insights', [SupplierProductInsightsController::class, 'insights']);

        Route::get('wallet', EcommerceWalletController::class);
        Route::get('wallet/transactions', EcommerceTransactionController::class);
        Route::get('wallet/pending-payout', EcommercePendingPayoutController::class);
        Route::get('wallet/bank-account', GetBankAccountController::class);
        Route::patch('wallet/add-bank-account/{bank_account}', UpdateBankAccountController::class);
        Route::post('wallet/add-bank-account', AddBankAccountController::class);
        Route::post('withdraw-funds', WithdrawFundController::class);

        Route::apiResource('store-addresses', EcommerceStoreAddressController::class);
    });
});
