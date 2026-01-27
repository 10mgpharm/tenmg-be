<?php

use App\Http\Controllers\API\Admin\CarouselImageController;
use App\Http\Controllers\API\Job\JobApplicationController;
use App\Http\Controllers\API\Job\JobController;
use App\Http\Controllers\API\Storefront\FaqController as StorefrontFaqController;
use App\Http\Controllers\API\Storefront\FincraWebhookController;
use App\Http\Controllers\API\Storefront\TenmgWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::prefix('storefront')->middleware('store.visitor.count')->name('storefront.')->group(function () {
    Route::get('images', [CarouselImageController::class, 'index']);
    Route::get('faqs', [StorefrontFaqController::class, 'index']);
});

Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
Route::post('jobs/applications', [JobApplicationController::class, 'store'])->name('jobs.applications.store');

// Public webhooks
Route::post('/webhooks/vendor/direct-debit/mandate', [\App\Http\Controllers\API\Webhooks\PaystackWebhookController::class, 'handle'])->name('webhooks.paystack.direct_debit');
// Mono webhook using Spatie WebhookClient
Route::webhooks('/webhooks/mono/prove', 'mono');
// Keep old route temporarily for backward compatibility during transition
// Route::post('/webhooks/mono/prove', [\App\Http\Controllers\API\Webhooks\MonoProveWebhookController::class, 'handle'])->name('webhooks.mono.prove');
Route::post('/fincra/webhook', [FincraWebhookController::class, 'verifyFincraPaymentWebHook']);
Route::post('/tenmg/webhook', [TenmgWebhookController::class, 'verifyTenmgCreditPaymentWebHook']);
