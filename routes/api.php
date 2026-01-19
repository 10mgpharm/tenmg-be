<?php

use App\Http\Controllers\API\Storefront\FincraWebhookController;
use App\Http\Controllers\API\Storefront\TenmgWebhookController;
use App\Http\Controllers\Integration\VendorEcommerceTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Integration routes
    Route::post('integration/vendor/ecommerce-transactions', VendorEcommerceTransactionController::class)
        ->name('integration.vendor.ecommerce-transactions')
        ->middleware('integration.vendor.ecommerce-transaction');

    // Require all route files
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/public.php';
    require __DIR__.'/api/shared.php';
    require __DIR__.'/api/supplier.php';
    require __DIR__.'/api/vendor.php';
    require __DIR__.'/api/admin.php';
    require __DIR__.'/api/lender.php';
    require __DIR__.'/api/storefront.php';
    require __DIR__.'/api/client.php';

});

// Webhook routes (outside v1 prefix)
Route::post('/fincra/webhook', [FincraWebhookController::class, 'verifyFincraPaymentWebHook']);
Route::post('/tenmg/webhook', [TenmgWebhookController::class, 'verifyTenmgCreditPaymentWebHook']);
