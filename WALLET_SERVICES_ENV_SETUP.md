# Wallet Services Environment Variables Setup

This document lists all required environment variables for the wallet services (Fincra and SafeHaven) ported from Bitstac.

## Required Environment Variables

### Fincra Configuration

Add these to your `.env` file:

```env
# Fincra API Configuration
FINCRA_API_KEY=your_fincra_api_key_here
FINCRA_BUSINESS_ID=your_fincra_business_id_here
FINCRA_BASE_URL=https://api.fincra.com
# Or for sandbox: https://sandboxapi.fincra.com

# Optional Fincra Settings (with defaults)
FINCRA_TIMEOUT=30
FINCRA_RETRIES=3
FINCRA_WEBHOOK_SECRET=your_fincra_webhook_secret_here
```

**Where to get these:**
- `FINCRA_API_KEY`: Your Fincra API key from the Fincra dashboard
- `FINCRA_BUSINESS_ID`: Your Fincra business ID from the dashboard
- `FINCRA_BASE_URL`: 
  - Production: `https://api.fincra.com`
  - Sandbox: `https://sandboxapi.fincra.com`
- `FINCRA_WEBHOOK_SECRET`: Secret for validating Fincra webhooks (if using webhooks)

### SafeHaven Configuration

Add these to your `.env` file:

```env
# SafeHaven API Configuration
SAFEHAVEN_BASE_URL=https://api.sandbox.safehavenmfb.com
# Or for production: https://api.safehavenmfb.com
SAFEHAVEN_CLIENT_ID=your_safehaven_client_id_here
SAFEHAVEN_CLIENT_ASSERTION=your_safehaven_client_assertion_here

# Optional SafeHaven Settings (with defaults)
SAFEHAVEN_TIMEOUT=30
SAFEHAVEN_RETRIES=3

# SafeHaven Account Numbers (required for operations)
# For Sandbox:
SAFEHAVEN_ACCOUNT_MAIN=your_main_account_number
SAFEHAVEN_ACCOUNT_OPERATIONS=your_operations_account_number
SAFEHAVEN_ACCOUNT_DEPOSIT=your_deposit_account_number

# For Production (different account numbers):
# SAFEHAVEN_ACCOUNT_MAIN=your_production_main_account
# SAFEHAVEN_ACCOUNT_OPERATIONS=your_production_operations_account
# SAFEHAVEN_ACCOUNT_DEPOSIT=your_production_deposit_account
```

**Where to get these:**
- `SAFEHAVEN_BASE_URL`: 
  - Sandbox: `https://api.sandbox.safehavenmfb.com`
  - Production: `https://api.safehavenmfb.com`
- `SAFEHAVEN_CLIENT_ID`: Your SafeHaven client ID
- `SAFEHAVEN_CLIENT_ASSERTION`: Your SafeHaven client assertion (JWT token)
- Account numbers: Provided by SafeHaven for your business accounts

## Configuration File

The configuration is stored in `config/services.php`. The structure matches Bitstac's configuration:

```php
'fincra' => [
    'api_key' => env('FINCRA_API_KEY'),
    'business_id' => env('FINCRA_BUSINESS_ID'),
    'base_url' => env('FINCRA_BASE_URL'),
    'database_slug' => 'fincra',  // Used to find ServiceProvider in database
    'timeout' => env('FINCRA_TIMEOUT', 30),
    'retries' => env('FINCRA_RETRIES', 3),
    'webhook_secret' => env('FINCRA_WEBHOOK_SECRET'),
],

'safehaven' => [
    'base_url' => env('SAFEHAVEN_BASE_URL'),
    'client_id' => env('SAFEHAVEN_CLIENT_ID'),
    'client_assertion' => env('SAFEHAVEN_CLIENT_ASSERTION'),
    'timeout' => env('SAFEHAVEN_TIMEOUT', 30),
    'retries' => env('SAFEHAVEN_RETRIES', 3),
    'accounts' => [...],  // Auto-configured based on base_url
    'database_slug' => 'safehaven',  // Used to find ServiceProvider in database
],
```

## Default Provider

**Fincra is the default provider** for virtual account creation. If no provider is configured on a currency, the system will automatically use Fincra.

## Service Provider Setup

Before using the wallet services, you must:

1. **Create ServiceProvider records in the database** (via admin):
   - Create a ServiceProvider with `slug = 'fincra'`
   - Create a ServiceProvider with `slug = 'safehaven'`
   - Set appropriate flags (`is_virtual_account_provider = true`, etc.)

2. **Create Currency records** (via admin):
   - For each currency, set `virtual_account_provider` to the Fincra ServiceProvider ID (or SafeHaven if preferred)
   - If not set, Fincra will be used as default

## Important Notes

1. **KYC Requirement**: Both Fincra and SafeHaven require KYC verification (LenderKycSession) before creating virtual accounts
2. **Database Slug**: The `database_slug` in config must match the `slug` field in the `service_providers` table
3. **Business ID**: Fincra requires a business ID that identifies your business in their system
4. **Client Assertion**: SafeHaven uses JWT-based authentication with client assertion
5. **Account Numbers**: SafeHaven requires account numbers for operations, deposits, etc.

## Testing

To test the configuration:

1. Ensure all environment variables are set
2. Create ServiceProvider records in database
3. Create Currency records with provider assignments
4. Test wallet creation
5. Test virtual account creation (requires KYC session)

## Migration from Bitstac

All credential access points have been migrated:
- ✅ FincraService uses `config('services.fincra.*')`
- ✅ SafeHavenService uses `config('services.safehaven.*')`
- ✅ VirtualAccountService resolves providers from currency configuration
- ✅ Default provider is Fincra (matches Bitstac pattern)
- ✅ All timeout and retry settings are configurable
