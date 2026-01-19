<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BusinessType;
use App\Enums\VirtualAccountType;
use App\Models\Business;
use App\Models\LenderKycSession;
use App\Models\LenderMonoProfile;
use App\Models\ServiceProvider;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Services\Interfaces\IVirtualAccountService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VirtualAccountService implements IVirtualAccountService
{
    public function __construct(
        protected SafeHavenService $safeHavenService,
        protected FincraService $fincraService
    ) {}

    /**
     * Create a virtual account using the provider configured on the currency.
     * SafeHaven and Fincra require KYC verification.
     * Defaults to Fincra if no provider is configured.
     */
    public function createVirtualAccount(
        Wallet $wallet,
        Business $business,
        ?LenderKycSession $kycSession = null
    ): ?VirtualAccount {
        try {
            // Ensure currency info is loaded
            if (! $wallet->relationLoaded('currency')) {
                $wallet->load('currency');
            }

            // Validate currency info exists
            if (! $wallet->currency) {
                throw new \Exception('Wallet currency information not found');
            }

            // Resolve provider from currency configuration
            $providerSlug = $this->resolveProviderSlugFromCurrency($wallet);

            // Default to Fincra if no provider is configured
            if (! $providerSlug) {
                $providerSlug = config('services.fincra.database_slug', 'fincra');
                Log::info('No provider configured for currency, defaulting to Fincra', [
                    'currency_id' => $wallet->currency->id,
                    'currency_code' => $wallet->currency->code,
                ]);
            }

            $fincraSlug = config('services.fincra.database_slug', 'fincra');
            $safeHavenSlug = config('services.safehaven.database_slug', 'safehaven');

            if ($providerSlug === $fincraSlug) {
                if (! $kycSession) {
                    throw new \Exception('Fincra virtual accounts require KYC verification');
                }

                return $this->createFincraVirtualAccount($wallet, $business, $kycSession);
            }

            if ($providerSlug === $safeHavenSlug) {
                // Check if SafeHaven is properly configured before using it
                if (! $this->safeHavenService->isConfigured()) {
                    Log::warning('SafeHaven provider configured but not properly set up, falling back to Fincra', [
                        'wallet_id' => $wallet->id,
                        'currency_id' => $wallet->currency->id,
                    ]);
                    // Fall back to Fincra
                    if (! $kycSession) {
                        throw new \Exception('Fincra virtual accounts require KYC verification');
                    }

                    return $this->createFincraVirtualAccount($wallet, $business, $kycSession);
                }

                // SafeHaven requires KYC verification
                if (! $kycSession) {
                    throw new \Exception('SafeHaven requires KYC verification');
                }

                return $this->createSafeHavenVirtualAccount($wallet, $business, $kycSession);
            }

            // Unknown / unsupported provider configured – default to Fincra
            Log::warning('Unsupported virtual account provider configured, defaulting to Fincra', [
                'provider_slug' => $providerSlug,
                'wallet_id' => $wallet->id,
            ]);

            if (! $kycSession) {
                throw new \Exception('Virtual account creation requires KYC verification');
            }

            return $this->createFincraVirtualAccount($wallet, $business, $kycSession);
        } catch (\Exception $e) {
            Log::error('Failed to create virtual account: '.$e->getMessage(), [
                'business_id' => $business->id,
                'wallet_id' => $wallet->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Resolve provider slug from the wallet's currency configuration.
     * Prefers 'virtual_account_provider', can be extended to consider temp provider.
     */
    protected function resolveProviderSlugFromCurrency(Wallet $wallet): ?string
    {
        $currency = $wallet->currency; // already ensured loaded
        if (! $currency) {
            return null;
        }

        if (! empty($currency->virtual_account_provider)) {
            $sp = ServiceProvider::find($currency->virtual_account_provider);

            return $sp?->slug;
        }

        // Optional: consider temp provider if configured
        if (! empty($currency->temp_virtual_account_provider)) {
            $sp = ServiceProvider::find($currency->temp_virtual_account_provider);

            return $sp?->slug;
        }

        return null;
    }

    /**
     * Extract BVN from KYC session or Mono profile
     */
    protected function extractBvnFromKyc(LenderKycSession $kycSession): ?string
    {
        // Check if BVN is in the KYC session meta
        $meta = $kycSession->meta ?? [];
        if (isset($meta['bvn'])) {
            return $meta['bvn'];
        }

        // Check if there's a Mono profile with identity
        if ($kycSession->lender_mono_profile_id) {
            $monoProfile = LenderMonoProfile::find($kycSession->lender_mono_profile_id);
            if ($monoProfile && $monoProfile->identity_type === 'bvn') {
                // Note: identity_hash is hashed, so we can't extract the actual BVN
                // This would need to be stored separately or retrieved from the provider
                return null;
            }
        }

        return null;
    }

    /**
     * Get business owner details for virtual account creation (individual accounts)
     * Extracts firstName and lastName from the owner's name
     */
    protected function getBusinessOwnerDetails(Business $business): array
    {
        $owner = $business->owner;
        if (! $owner) {
            throw new \Exception('Business owner not found');
        }

        // Get owner's full name (from 'fullname' field during registration, stored in User.name)
        $fullName = $owner->name ?? 'Business';

        // Split name into firstName and lastName
        // Using limit of 2 means: first word = firstName, all remaining words = lastName
        // Examples:
        // "Damilola Esan" → firstName: "Damilola", lastName: "Esan"
        // "Damilola Esan Johnson" → firstName: "Damilola", lastName: "Esan Johnson"
        // "Consode Dev" → firstName: "Consode", lastName: "Dev"
        $nameParts = explode(' ', trim($fullName), 2);
        $firstName = $nameParts[0] ?? 'Business';
        $lastName = $nameParts[1] ?? ''; // If only one word, lastName is empty

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $owner->email ?? $business->email ?? '',
            'phone' => $owner->phone ?? $business->phone ?? '',
        ];
    }

    /**
     * Get business details for corporate virtual account creation
     * businessName: Business.name (from 'name' field during registration)
     * bvnName: User.name (from 'fullname' field during registration - owner's name on BVN)
     */
    protected function getCorporateBusinessDetails(Business $business): array
    {
        $owner = $business->owner;

        // businessName comes from Business.name (from 'name' field during registration)
        // e.g., "Consode Drug Store"
        $businessName = $business->name ?? 'Business';

        // bvnName comes from User.name (from 'fullname' field during registration)
        // This is the owner's full name that's on the BVN
        // e.g., "Consode Dev" or "Damilola Esan"
        $bvnName = $owner?->name ?? $businessName;

        return [
            'businessName' => $businessName,
            'bvnName' => $bvnName, // Owner's full name on BVN
            'email' => $business->email ?? $owner?->email ?? '',
            'phone' => $business->phone ?? $owner?->phone ?? '',
        ];
    }

    /**
     * Determine account type based on business type and lender_type
     */
    protected function determineAccountType(Business $business): VirtualAccountType
    {
        // Vendors always use Corporate
        if ($business->type === BusinessType::VENDOR->value) {
            return VirtualAccountType::CORPORATE;
        }

        // For lenders, check lender_type
        if ($business->type === BusinessType::LENDER->value) {
            if ($business->lender_type === 'individual') {
                return VirtualAccountType::INDIVIDUAL;
            }

            // lender_type === 'business' or null (defaults to business)
            return VirtualAccountType::CORPORATE;
        }

        // Default to Corporate for other business types
        return VirtualAccountType::CORPORATE;
    }

    /**
     * Create virtual account using SafeHaven
     */
    protected function createSafeHavenVirtualAccount(
        Wallet $wallet,
        Business $business,
        LenderKycSession $kycSession
    ): ?VirtualAccount {
        try {
            $virtualAccount = new VirtualAccount([
                'business_id' => $business->id,
                'wallet_id' => $wallet->id,
                'currency_id' => $wallet->currency->id,
                'type' => VirtualAccountType::INDIVIDUAL,
                'provider' => $this->safeHavenService->getDatabaseProviderId(),
                'status' => 'active',
            ]);

            $virtualAccount->id = (string) Str::uuid();

            $ownerDetails = $this->getBusinessOwnerDetails($business);

            // Get identity reference from KYC session
            $identityId = $kycSession->prove_id ?? $kycSession->reference ?? $virtualAccount->id;

            $response = $this->safeHavenService->createSubAccount(
                phoneNumber: $ownerDetails['phone'],
                emailAddress: $ownerDetails['email'],
                externalReference: $virtualAccount->id,
                identityType: 'vID',
                identityId: $identityId
            );

            if (! $response['success']) {
                Log::error('Failed to create SafeHaven virtual account: '.($response['message'] ?? 'Unknown error'), [
                    'business_id' => $business->id,
                    'wallet_id' => $wallet->id,
                    'response' => $response,
                ]);

                return null;
            }

            $virtualAccount->provider_reference = $response['data']['_id'] ?? null;
            $virtualAccount->provider_status = 'active';
            $virtualAccount->account_name = $response['data']['accountName'] ?? $ownerDetails['firstName'];
            $virtualAccount->account_number = $response['data']['accountNumber'] ?? null;
            $virtualAccount->bank_name = 'SafeHaven Microfinance Bank';
            $virtualAccount->bank_code = '090286';

            $virtualAccount->save();

            return $virtualAccount;
        } catch (\Exception $e) {
            Log::error('Failed to create SafeHaven virtual account: '.$e->getMessage(), [
                'business_id' => $business->id,
                'wallet_id' => $wallet->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Create virtual account using Fincra
     */
    protected function createFincraVirtualAccount(
        Wallet $wallet,
        Business $business,
        LenderKycSession $kycSession
    ): ?VirtualAccount {
        try {
            // Determine account type based on business type and lender_type
            $accountType = $this->determineAccountType($business);

            $virtualAccount = new VirtualAccount([
                'business_id' => $business->id,
                'wallet_id' => $wallet->id,
                'currency_id' => $wallet->currency->id,
                'type' => $accountType,
                'provider' => $this->fincraService->getDatabaseProviderId(),
                'status' => 'active',
            ]);

            $virtualAccount->id = (string) Str::uuid();

            // Extract BVN from KYC session
            $bvn = $this->extractBvnFromKyc($kycSession);
            if (! $bvn) {
                // Try to get from meta or other sources
                $meta = $kycSession->meta ?? [];
                $bvn = $meta['bvn'] ?? null;

                if (! $bvn) {
                    throw new \Exception('BVN not found in KYC session');
                }
            }

            // Prepare parameters based on account type
            $accountTypeString = $accountType->value;
            $channel = $accountType === VirtualAccountType::CORPORATE ? 'wema' : 'globus';
            $currency = strtolower($wallet->currency->code ?? 'ngn');

            if ($accountType === VirtualAccountType::CORPORATE) {
                // Corporate account
                $corporateDetails = $this->getCorporateBusinessDetails($business);

                $response = $this->fincraService->createVirtualAccount(
                    accountType: $accountTypeString,
                    bvn: $bvn,
                    externalReference: $virtualAccount->id,
                    channel: $channel,
                    currency: $currency,
                    businessName: $corporateDetails['businessName'],
                    bvnName: $corporateDetails['bvnName'],
                    email: $corporateDetails['email'] ?: null
                );
            } else {
                // Individual account
                $ownerDetails = $this->getBusinessOwnerDetails($business);

                $response = $this->fincraService->createVirtualAccount(
                    accountType: $accountTypeString,
                    bvn: $bvn,
                    externalReference: $virtualAccount->id,
                    channel: $channel,
                    currency: $currency,
                    firstName: $ownerDetails['firstName'],
                    lastName: $ownerDetails['lastName'],
                    email: $ownerDetails['email'] ?: null
                );
            }

            if (! $response['success']) {
                Log::error('Failed to create Fincra virtual account: '.($response['message'] ?? 'Unknown error'), [
                    'business_id' => $business->id,
                    'wallet_id' => $wallet->id,
                    'response' => $response,
                ]);

                return null;
            }

            $accountInfo = $response['data']['accountInformation'] ?? null;

            if (! $accountInfo) {
                Log::error('Missing account information in Fincra response', [
                    'business_id' => $business->id,
                    'wallet_id' => $wallet->id,
                    'response' => $response,
                ]);

                return null;
            }

            // Get appropriate name for account based on type
            $accountName = $accountInfo['accountName'] ?? null;
            if (! $accountName) {
                if ($accountType === VirtualAccountType::CORPORATE) {
                    $corporateDetails = $this->getCorporateBusinessDetails($business);
                    $accountName = $corporateDetails['businessName'];
                } else {
                    $ownerDetails = $this->getBusinessOwnerDetails($business);
                    $accountName = $ownerDetails['firstName'];
                }
            }

            $virtualAccount->provider_reference = $response['data']['_id'] ?? null;
            $virtualAccount->provider_status = $response['data']['status'] ?? 'active';
            $virtualAccount->account_name = $accountName;
            $virtualAccount->bank_name = $accountInfo['bankName'] ?? null;
            $virtualAccount->account_number = $accountInfo['accountNumber'] ?? null;
            $virtualAccount->bank_code = $accountInfo['bankCode'] ?? null;

            $virtualAccount->save();

            return $virtualAccount;
        } catch (\Exception $e) {
            Log::error('Failed to create Fincra virtual account: '.$e->getMessage(), [
                'business_id' => $business->id,
                'wallet_id' => $wallet->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get a virtual account by ID
     */
    public function getVirtualAccount(string $virtualAccountId): ?VirtualAccount
    {
        return VirtualAccount::find($virtualAccountId);
    }

    /**
     * List virtual accounts with optional filters
     */
    public function listVirtualAccounts(
        ?Business $business = null,
        ?Wallet $wallet = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator|Collection {
        $query = VirtualAccount::query();

        if ($business) {
            $query->where('business_id', $business->id);
        }

        if ($wallet) {
            $query->where('wallet_id', $wallet->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Update virtual account status
     */
    public function updateVirtualAccountStatus(VirtualAccount $virtualAccount, string $status): VirtualAccount
    {
        $virtualAccount->status = $status;
        $virtualAccount->save();

        return $virtualAccount;
    }
}
