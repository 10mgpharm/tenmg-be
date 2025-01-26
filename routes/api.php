<?php

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\API\Account\AccountController;
use App\Http\Controllers\API\Account\NotificationController as AccountNotificationController;
use App\Http\Controllers\API\Account\PasswordUpdateController;
use App\Http\Controllers\API\Account\TwoFactorAuthenticationController;
use App\Http\Controllers\API\Admin\AuditLogController;
use App\Http\Controllers\API\Admin\BusinessLicenseController;
use App\Http\Controllers\API\Admin\CarouselImageController;
use App\Http\Controllers\API\Admin\EcommerceBrandController as AdminEcommerceBrandController;
use App\Http\Controllers\API\Admin\EcommerceCategoryController as AdminEcommerceCategoryController;
use App\Http\Controllers\API\Admin\EcommerceMeasurementController as AdminEcommerceMeasurementController;
use App\Http\Controllers\API\Admin\EcommerceOrderController;
use App\Http\Controllers\API\Admin\EcommercePresentationController as AdminEcommercePresentationController;
use App\Http\Controllers\API\Admin\EcommerceProductController as AdminEcommerceProductController;
use App\Http\Controllers\API\Admin\FaqController;
use App\Http\Controllers\API\Admin\MedicationTypeController as AdminMedicationTypeController;
use App\Http\Controllers\API\Admin\UsersController;
use App\Http\Controllers\API\Auth\AuthenticatedController;
use App\Http\Controllers\API\Auth\PasswordController;
use App\Http\Controllers\API\Auth\SignupUserController;
use App\Http\Controllers\API\Auth\VerifyEmailController;
use App\Http\Controllers\API\Credit\CustomerController;
use App\Http\Controllers\API\Credit\LoanApplicationController;
use App\Http\Controllers\API\Credit\LoanController;
use App\Http\Controllers\API\Credit\LoanOfferController;
use App\Http\Controllers\API\Credit\TransactionHistoryController;
use App\Http\Controllers\API\EcommerceDiscountController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ResendOtpController;
use App\Http\Controllers\API\Storefront\CartController;
use App\Http\Controllers\API\Storefront\CategoryController as StorefrontCategoryController;
use App\Http\Controllers\API\Storefront\FaqController as StorefrontFaqController;
use App\Http\Controllers\API\Storefront\OrdersController;
use App\Http\Controllers\API\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\API\Storefront\ShippingAddressController as StorefrontShippingAddressController;
use App\Http\Controllers\API\Storefront\ShoppingListController;
use App\Http\Controllers\API\Storefront\StorefrontController;
use App\Http\Controllers\API\Supplier\EcommerceProductController as SupplierEcommerceProductController;
use App\Http\Controllers\API\Webhooks\PaystackWebhookController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\API\Storefront\WishListController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\Supplier\DashboardController as SupplierDashboardController;
use App\Http\Controllers\Supplier\EcommerceBrandController as SupplierEcommerceBrandController;
use App\Http\Controllers\Supplier\EcommerceCategoryController as SupplierEcommerceCategoryController;
use App\Http\Controllers\Supplier\EcommerceMeasurementController as SupplierEcommerceMeasurementController;
use App\Http\Controllers\Supplier\EcommerceMedicationTypeController as SupplierEcommerceMedicationTypeController;
use App\Http\Controllers\Supplier\EcommerceOrderController as SupplierEcommerceOrderController;
use App\Http\Controllers\Supplier\EcommercePresentationController as SupplierEcommercePresentationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // public routes
    Route::prefix('auth')->name('auth.')->group(function () {
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

        Route::middleware(['auth:api', 'scope:full'])->group(function () {
            Route::post('/verify-email', VerifyEmailController::class)
                ->name('verification.verify');

            Route::post('/reset-password', [PasswordController::class, 'reset'])
                ->name('password.reset');

            Route::post('/signup/complete', [SignupUserController::class, 'complete'])
                ->name('signup.complete');

            Route::post('/signout', [AuthenticatedController::class, 'destroy'])
                ->name('signout');
        });

        Route::post('/resend-otp', ResendOtpController::class)
            ->name('resend.otp')->middleware('throttle:5,1');

        Route::get('invite/view', [InviteController::class, 'view'])->name('invite.view');
        Route::post('invite/accept', [InviteController::class, 'accept'])->name('invite.accept');
    });

    Route::prefix('storefront')->name('storefront.')->group(function () {
        Route::get('images', [CarouselImageController::class, 'index']);
        Route::get('faqs', [StorefrontFaqController::class, 'index']);
    });

    // Protected routes
    Route::middleware(['auth:api', 'scope:full'])->group(function () {

        Route::post('/resend-otp', ResendOtpController::class)
            ->name('resend.otp.auth')->middleware('throttle:5,1');

        // Account/Profile management
        Route::prefix('account')->group(function () {
            Route::patch('password', PasswordUpdateController::class);
            Route::match(['post', 'patch'], 'profile', [AccountController::class, 'profile']);
            Route::get('/profile', [ProfileController::class, 'show']);

            // 2FA
            Route::prefix('2fa')
                ->controller(TwoFactorAuthenticationController::class)
                ->group(function () {
                    Route::get('setup', 'setup');
                    Route::post('reset', 'reset');
                    Route::post('toggle', 'toggle');  // Toggle 2FA (enable/disable)
                    Route::post('verify', 'verify');
                });

            Route::prefix('notifications')->group(function () {
                Route::get('/', [AccountNotificationController::class, 'index']);
                Route::patch('subscriptions', [AccountNotificationController::class, 'subscriptions']);
                Route::patch('{notification}/subscription', [AccountNotificationController::class, 'subscription']);
            });
        });

        // SUPPLIER specific routes
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
            });
        });

        // VENDOR specific routes
        Route::prefix('vendor')->middleware(['roleCheck:vendor'])->group(function () {

            // Route::get('/', action: [ProfileController::class, 'show']);

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

                //download uploaded transaction history file
                Route::post('/download/{txnEvaluationId}', [TransactionHistoryController::class, 'downloadTransactionHistory'])
                    ->name('vendor.txn_history.download');

                //view uploaded transaction history
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

                Route::get('/verify-application-link/{reference}', [
                    LoanApplicationController::class,
                    'verifyApplicationLink',
                ])->name('vendor.applications.verify')->withoutMiddleware(['roleCheck:vendor', 'scope:full']);

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
                Route::get('/', [LoanController::class, 'getAllLoans'])->name('loans.getAll')->middleware('admin');
                Route::get('/{id}', [LoanController::class, 'getLoanById'])->name('loans.getById');
                Route::post('/{id}/disbursed', [LoanController::class, 'disbursed'])->name('loans.disbursed');

                Route::prefix('repayments')->group(function () {
                    Route::post('/{id}/repay', [LoanController::class, 'repayLoan'])->name('loans.repay');
                    Route::post('/{id}/liquidate', [LoanController::class, 'liquidateLoan'])->name('loans.liquidate');
                });
            });

        });

        // ADMIN specific routes
        Route::prefix('admin')->name('admin.')->middleware(['roleCheck:admin'])->group(function () {
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('invite/team-members', [InviteController::class, 'members'])->name('invite.team-members');
                Route::apiResource('invite', InviteController::class);

                Route::apiResource('medication-types', AdminMedicationTypeController::class);
                Route::get('medication-types/{medication_type:id}/medication-variations', [AdminMedicationTypeController::class, 'getVariationsByMedicationType']);

                Route::apiResource('notification', NotificationController::class);
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
            });

            Route::get('discounts/search', [EcommerceDiscountController::class, 'search']);
            Route::apiResource('discounts', EcommerceDiscountController::class);
            Route::patch('users/{user}/status', [UsersController::class, 'status']);
            Route::apiResource('users', UsersController::class);

            Route::get('business/licenses', [BusinessLicenseController::class, 'index']);
            Route::match(['put', 'patch'], 'business/licenses/{business}/status', [BusinessLicenseController::class, 'update']);

            Route::prefix('system-setup')->name('system-setup.')->group(function () {
                Route::get('storefront-images/search', [CarouselImageController::class, 'search']);
            });

            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('get-orders', [EcommerceOrderController::class, 'getOrderByStatus']);
                Route::post('change-order-status', [EcommerceOrderController::class, 'changeOrderStatus']);
                Route::get('get-order-details/{id}', [EcommerceOrderController::class, 'getOrderDetails']);
            });
        });

        // STOREFRONTS specific routes
        Route::prefix('storefront')->name('storefront.')->group(function () {

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
            Route::get('/categories/search', [StorefrontCategoryController::class, 'search']);
            Route::get('/categories/{category:slug}', [StorefrontCategoryController::class, 'products']);

            Route::get('/products/search', [StorefrontProductController::class, 'search']);
            Route::get('/products/{product}', [StorefrontProductController::class, 'show']);

            Route::get('shipping-addresses/search', [StorefrontShippingAddressController::class, 'search']);
            Route::apiResource('shipping-addresses', StorefrontShippingAddressController::class);

            Route::post('/add-remove-cart-items', [CartController::class, 'addRemoveItemToCart']);
            Route::post('/sync-cart', [CartController::class, 'syncCartItems']);
            Route::get('/get-user-cart', [CartController::class, 'getUserCart']);
            Route::post('/buy-now', [CartController::class, 'buyNow']);
            Route::post('/checkout', [OrdersController::class, 'checkout']);
            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [OrdersController::class, 'getOrders']);
                Route::get('/{id}', [OrdersController::class, 'getOrderDetails']);
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
        });

    });

    Route::post('/webhooks/vendor/direct-debit/mandate', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack.direct_debit');
});
