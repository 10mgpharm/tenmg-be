<?php

use App\Enums\InAppNotificationType;
use App\Http\Controllers\API\Account\AccountController;
use App\Http\Controllers\API\Account\AppNotificationController as AccountAppNotificationController;
use App\Http\Controllers\API\Account\CountUnreadNotificationController;
use App\Http\Controllers\API\Account\MarkAllAsReadController;
use App\Http\Controllers\API\Account\NotificationController;
use App\Http\Controllers\API\Account\PasswordUpdateController;
use App\Http\Controllers\API\Account\TwoFactorAuthenticationController;
use App\Http\Controllers\API\Account\UpdateFcmTokenController;
use App\Http\Controllers\API\Account\UserPermissionController;
use App\Http\Controllers\API\Credit\LoanApplicationController;
use App\Http\Controllers\API\Job\JobApplicationController;
use App\Http\Controllers\API\Job\JobController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\PayoutController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ResendOtpController;
use App\Http\Controllers\API\VirtualAccount\VirtualAccountController;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared Protected Routes (Available to all authenticated users)
|--------------------------------------------------------------------------
*/

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

        Route::match(['put', 'patch'], 'notifications/mark-all-read', MarkAllAsReadController::class);
        Route::get('count-unread-notifications', CountUnreadNotificationController::class);
        Route::apiResource('notifications', NotificationController::class);

        Route::prefix('app-notifications')->group(function () {
            Route::get('/', [AccountAppNotificationController::class, 'index']);
            Route::patch('subscriptions', [AccountAppNotificationController::class, 'subscriptions']);
            Route::patch('{notification}/subscription', [AccountAppNotificationController::class, 'subscription']);
        });

        Route::get('messages/start-conversation', [MessageController::class, 'startConversation']);
        Route::get('messages/unread-count', [MessageController::class, 'unreadCount']);
        Route::match(['PUT', 'PATCH'], 'messages/mark-as-read/{message}', [MessageController::class, 'markAsRead']);
        Route::apiResource('messages', MessageController::class);
        Route::post('/fcm-token', UpdateFcmTokenController::class);
        Route::post('/test-notification', function (Request $request) {
            (new InAppNotificationService)
                ->forUser($request->user())
                ->notify(InAppNotificationType::NEW_MESSAGE);
        });

        Route::get('permissions', UserPermissionController::class);
    });

    Route::prefix('virtual-account')->group(function () {
        // Create (if missing) or return virtual account for primary NGN wallet (no wallet_id needed)
        Route::get('/', [VirtualAccountController::class, 'getOrCreate']);

        // Create (if missing) or return virtual account by wallet ID
        Route::get('/wallet/{walletId}', [VirtualAccountController::class, 'getOrCreateByWallet']);
    });

    Route::prefix('bank')->group(function () {
        Route::get('/list', [PayoutController::class, 'listBanks']);
        Route::post('/verify-account', [PayoutController::class, 'verifyAccount']);
    });

    Route::prefix('payouts')->group(function () {
        Route::post('/withdraw', [PayoutController::class, 'payout']);
    });

    Route::prefix('jobs')->group(function () {
        Route::post('/', [JobController::class, 'store']);
        Route::match(['PUT', 'PATCH'], '{job}', [JobController::class, 'update']);
        Route::delete('{job}', [JobController::class, 'destroy']);
    });

    Route::prefix('job-applications')->group(function () {
        Route::get('/', [JobApplicationController::class, 'index'])->name('job-applications.index');
        Route::get('{jobApplication}', [JobApplicationController::class, 'show'])->name('job-applications.show');
    });

    Route::prefix('test')->group(function () {
        Route::post('/mandate/debit-mandate-test/{applicationId}', [LoanApplicationController::class, 'debitCustomerMandate']);
    });

});
