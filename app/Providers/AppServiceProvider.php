<?php

namespace App\Providers;

use App\Listeners\SignupEmailVerifiedListener;
use App\Models\PassportAuthCode;
use App\Models\PassportClient;
use App\Models\PassportPersonalAccessClient;
use App\Models\PassportRefreshToken;
use App\Models\PassportToken;
use App\Repositories\CustomerRepository;
use App\Repositories\Interfaces\ICustomerRepository;
use App\Services\ActivityLogService;
use App\Services\AffordabilityService;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\Interfaces\IActivityLogService;
use App\Services\Interfaces\IAffordabilityService;
use App\Services\Interfaces\IAuthService;
use App\Services\Interfaces\ICustomerService;
use App\Services\Interfaces\IRuleEngineService;
use App\Services\Interfaces\ITxnHistoryService;
use App\Services\RuleEngineService;
use App\Services\TransactionHistoryService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // List Repositories bindings
        $this->app->bind(ICustomerRepository::class, CustomerRepository::class);

        // List Service bindings
        $this->app->bind(IAuthService::class, AuthService::class);
        $this->app->bind(abstract: ICustomerService::class, concrete: function ($app) {
            return new CustomerService(
                customerRepository: $app->make(ICustomerRepository::class),
                attachmentService: $app->make(AttachmentService::class),
                authService: $app->make(AuthService::class),
                activityLogService: $app->make(ActivityLogService::class),
            );
        }, shared: true);

        $this->app->bind(IActivityLogService::class, ActivityLogService::class);
        $this->app->bind(ITxnHistoryService::class, TransactionHistoryService::class);
        $this->app->bind(IRuleEngineService::class, RuleEngineService::class);
        $this->app->bind(IAffordabilityService::class, AffordabilityService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // configure passport token expiration
        Passport::tokensExpireIn(now()->addHours(1));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // configure custom models for passport
        Passport::useTokenModel(PassportToken::class);
        Passport::useRefreshTokenModel(PassportRefreshToken::class);
        Passport::useAuthCodeModel(PassportAuthCode::class);
        Passport::useClientModel(PassportClient::class);
        Passport::usePersonalAccessClientModel(PassportPersonalAccessClient::class);
        Passport::tokensCan([
            'temp' => 'Temporal access token',
            'full' => 'Full access token',
        ]);

        // register events and listeners here
        Event::listen(
            Verified::class,
            SignupEmailVerifiedListener::class,
        );
    }
}
