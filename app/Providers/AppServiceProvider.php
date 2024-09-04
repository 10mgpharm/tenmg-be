<?php

namespace App\Providers;

use App\Models\PassportAuthCode;
use App\Models\PassportClient;
use App\Models\PassportPersonalAccessClient;
use App\Models\PassportRefreshToken;
use App\Models\PassportToken;
use App\Repositories\CustomerRepository;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\Interfaces\CustomerServiceInterface;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(abstract: CustomerServiceInterface::class, concrete: function ($app) {
            return new CustomerService(
                customerRepository: $app->make(CustomerRepositoryInterface::class),
                attachmentService: $app->make(AttachmentService::class),
                authService: $app->make(AuthService::class)
            );
        }, shared: true);
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
    }
}
