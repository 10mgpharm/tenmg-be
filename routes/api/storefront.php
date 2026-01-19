<?php

use App\Http\Controllers\API\Storefront\BrandController as StorefrontBrandController;
use App\Http\Controllers\API\Storefront\CartController;
use App\Http\Controllers\API\Storefront\CategoryController as StorefrontCategoryController;
use App\Http\Controllers\API\Storefront\EcommerceProductRatingController;
use App\Http\Controllers\API\Storefront\EcommerceProductReviewController;
use App\Http\Controllers\API\Storefront\MeasurementController as StorefrontMeasurementController;
use App\Http\Controllers\API\Storefront\MedicationTypeController as StorefrontMedicationTypeController;
use App\Http\Controllers\API\Storefront\OrdersController;
use App\Http\Controllers\API\Storefront\PresentationController as StorefrontPresentationController;
use App\Http\Controllers\API\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\API\Storefront\ShippingAddressController as StorefrontShippingAddressController;
use App\Http\Controllers\API\Storefront\ShoppingListController;
use App\Http\Controllers\API\Storefront\StorefrontController;
use App\Http\Controllers\API\Storefront\WishListController;
use App\Http\Controllers\BusinessSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'scope:full'])->group(function () {
    Route::prefix('storefront')->middleware('store.visitor.count')->name('storefront.')->group(function () {

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [BusinessSettingController::class, 'show']);

            // Update business information
            Route::match(['post', 'patch'], 'business-information', [BusinessSettingController::class, 'businessInformation']);

            // Update business account license number, expiry date and cac doc
            Route::patch('license/withdraw', [BusinessSettingController::class, 'withdraw']);
            Route::match(['post', 'patch'], 'license', [BusinessSettingController::class, 'license']);
            Route::get('license', [BusinessSettingController::class, 'getBusinessStatus']);
        });

        Route::get('/', StorefrontController::class);
        Route::get('brands', [StorefrontBrandController::class, 'search']);
        Route::get('presentations', [StorefrontPresentationController::class, 'search']);
        Route::get('measurements', [StorefrontMeasurementController::class, 'search']);
        Route::get('medication-types', [StorefrontMedicationTypeController::class, 'search']);
        Route::get('/categories/search', [StorefrontCategoryController::class, 'search']);
        Route::get('/categories/{category:slug}', [StorefrontCategoryController::class, 'products']);

        Route::get('/products/search', [StorefrontProductController::class, 'search']);
        Route::get('/products/{product}', [StorefrontProductController::class, 'show']);

        Route::get('shipping-addresses/search', [StorefrontShippingAddressController::class, 'search']);
        Route::post('shipping-addresses/set-default', [StorefrontShippingAddressController::class, 'setDefaultAddress']);
        Route::apiResource('shipping-addresses', StorefrontShippingAddressController::class);

        Route::post('/add-remove-cart-items', [CartController::class, 'addRemoveItemToCart']);
        Route::post('/sync-cart', [CartController::class, 'syncCartItems']);
        Route::get('/get-user-cart', [CartController::class, 'getUserCart']);
        Route::post('/clear-cart', [CartController::class, 'clearUserCart']);
        Route::post('/buy-now', [CartController::class, 'buyNow']);
        Route::post('/checkout', [OrdersController::class, 'checkout']);
        Route::get('/get-payment-methods', [OrdersController::class, 'getPaymentMethods']);
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrdersController::class, 'getOrders']);
            Route::get('/{id}', [OrdersController::class, 'getOrderDetails']);
            Route::post('/coupon/verify', [OrdersController::class, 'couponVerify']);

        });
        Route::prefix('wishlist')->name('wishlist.')->group(function () {
            Route::get('/', [WishListController::class, 'getWhishList']);
            Route::post('add-wishlist', [WishListController::class, 'addWishList']);
            Route::delete('remove-product/{id}', [WishListController::class, 'removeProductFromWishList']);
        });
        Route::prefix('shopping-list')->name('shopping-list.')->group(function () {
            Route::get('/', [ShoppingListController::class, 'getShoppingList']);
            Route::post('add-shopping-list', [ShoppingListController::class, 'addShoppingList']);
            Route::delete('remove-item/{id}', [ShoppingListController::class, 'removeItemFromSHoppingList']);
        });
        Route::prefix('payment')->name('payment.')->group(function () {
            Route::get('/verify/{ref}', [OrdersController::class, 'verifyFincraPayment']);
            Route::get('/cancel/{ref}', [OrdersController::class, 'cancelPayment']);
            Route::get('/last-payment-status', [OrdersController::class, 'lastPaymentStatus']);
        });

        Route::get('reviews/unreviewed', [EcommerceProductReviewController::class, 'unreviewed']);
        Route::apiResource('reviews', EcommerceProductReviewController::class);
        Route::apiResource('ratings', EcommerceProductRatingController::class);
    });
});
