# Wallet System Documentation

This document provides a comprehensive guide to the wallet system implementation, including all database tables, relationships, and how to create virtual accounts.

## Table of Contents

1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Core Tables](#core-tables)
4. [Supporting Tables](#supporting-tables)
5. [Creating a Virtual Account](#creating-a-virtual-account)
6. [Database Relationships](#database-relationships)
7. [API Endpoints](#api-endpoints)
8. [Integration Guide](#integration-guide)

---

## Overview

The wallet system allows users to:
- Create multiple wallets in different currencies
- Hold balances in various currencies
- Create virtual bank accounts linked to wallets (for NGN)
- Transfer funds between wallets
- Track all transactions with a complete audit trail

### Key Features

- **Multi-currency support**: Wallets can be created for any currency (NGN, USD, etc.)
- **Multiple wallet types**: main, secondary, savings, community_savings, virtual_card
- **Virtual accounts**: Dedicated virtual bank accounts for receiving payments (NGN only)
- **Transaction tracking**: Complete ledger system for all balance changes
- **Provider integration**: Supports multiple virtual account providers (SafeHaven, Fincra, Nomba)

---

## Database Schema

### Core Tables

#### 1. `wallets`

Stores user wallets with balances.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique wallet identifier |
| `customer_id` | UUID (nullable) | References `users.id` |
| `business_id` | UUID (nullable) | References `businesses.id` |
| `wallet_type` | ENUM | 'main', 'secondary', 'savings', 'community_savings', 'virtual_card' |
| `currency_id` | UUID | References `currencies.id` |
| `balance` | DECIMAL(15,2) | Current wallet balance (default: 0) |
| `wallet_name` | STRING (nullable) | Optional custom name for wallet |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Constraints:**
- One `main` wallet per currency per user
- Foreign keys: `customer_id` → `users.id`, `business_id` → `businesses.id`, `currency_id` → `currencies.id`

**Indexes:**
- `(customer_id, currency_id)`
- `(business_id, currency_id)`
- `wallet_type`
- `currency_id`

---

#### 2. `virtual_accounts`

Stores virtual bank accounts linked to wallets.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique virtual account identifier |
| `entity_id` | UUID | References `users.id` or `businesses.id` |
| `entity_type` | STRING | 'customer' or 'business' |
| `entity_label` | STRING (nullable) | 'customer' or 'business' |
| `currency_id` | UUID (nullable) | References `currencies.id` |
| `wallet_id` | UUID (nullable) | References `wallets.id` |
| `type` | ENUM | 'individual' or 'corporate' |
| `provider` | UUID | References `service_providers.id` |
| `provider_reference` | STRING (nullable) | Reference from provider API |
| `provider_status` | STRING (nullable) | Status from provider |
| `account_name` | STRING (nullable) | Account holder name |
| `bank_name` | STRING (nullable) | Bank name |
| `account_number` | STRING (nullable) | Virtual account number |
| `account_type` | STRING (nullable) | Account type |
| `bank_code` | STRING (nullable) | Bank code |
| `routing_number` | STRING (nullable) | Routing number (international) |
| `country_code` | STRING(3) (nullable) | ISO country code |
| `iban` | STRING (nullable) | IBAN (international) |
| `check_number` | STRING (nullable) | Check number |
| `sort_code` | STRING (nullable) | Sort code |
| `bank_swift_code` | STRING (nullable) | SWIFT code |
| `addressable_in` | STRING (nullable) | Currency code |
| `bank_address` | TEXT (nullable) | Bank address |
| `status` | STRING | 'active', 'inactive', 'suspended', 'pending', etc. |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Constraints:**
- Foreign keys: `currency_id` → `currencies.id`, `provider` → `service_providers.id`

**Indexes:**
- `(entity_id, entity_type)`
- `currency_id`
- `provider`
- `status`
- `account_number`
- `wallet_id`

**Important Notes:**
- One virtual account per wallet
- Only available for NGN wallets (by default)
- Requires BVN verification for SafeHaven and Fincra providers
- Nomba provider does not require BVN

---

#### 3. `currencies`

Stores currency information and provider configurations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique currency identifier |
| `classification` | ENUM | 'fiat' or 'crypto' |
| `name` | STRING(125) | Currency name (e.g., "Nigerian Naira") |
| `code` | STRING(10) (nullable) | Currency code (e.g., "NGN") |
| `symbol` | STRING(10) (nullable) | Currency symbol (e.g., "₦") |
| `slug` | STRING(10) (nullable) | URL-friendly identifier (e.g., "nigerian-naira") |
| `decimal_places` | TINYINT (nullable) | Number of decimal places |
| `icon` | STRING(255) (nullable) | Icon URL |
| `description` | TEXT (nullable) | Currency description |
| `tier_1_limits` | JSON (nullable) | Tier 1 transaction limits |
| `tier_2_limits` | JSON (nullable) | Tier 2 transaction limits |
| `tier_3_limits` | JSON (nullable) | Tier 3 transaction limits |
| `country_code` | STRING(3) (nullable) | ISO country code |
| `virtual_account_provider` | UUID (nullable) | References `service_providers.id` |
| `temp_virtual_account_provider` | UUID (nullable) | Temporary provider |
| `virtual_card_provider` | UUID (nullable) | Virtual card provider |
| `bank_transfer_collection_provider` | UUID (nullable) | Bank transfer collection provider |
| `mobile_money_collection_provider` | UUID (nullable) | Mobile money collection provider |
| `bank_transfer_payout_provider` | UUID (nullable) | Bank transfer payout provider |
| `mobile_money_payout_provider` | UUID (nullable) | Mobile money payout provider |
| `bill_payment_provider` | UUID (nullable) | Bill payment provider |
| `status` | ENUM | 'active' or 'inactive' |
| `is_active` | BOOLEAN | Active status flag |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Key Field:**
- `virtual_account_provider`: Must be set for a currency to support virtual accounts

---

#### 4. `service_providers`

Stores payment service provider information.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique provider identifier |
| `name` | STRING | Provider name (e.g., "SafeHaven", "Fincra", "Nomba") |
| `slug` | STRING (unique) | URL-friendly identifier |
| `description` | STRING (nullable) | Provider description |
| `config` | JSON (nullable) | Provider configuration |
| `metadata` | JSON (nullable) | Additional metadata |
| `is_bill_payment_provider` | BOOLEAN | Can process bill payments |
| `is_bvn_verification_provider` | BOOLEAN | Can verify BVN |
| `is_virtual_account_provider` | BOOLEAN | Can create virtual accounts |
| `is_virtual_card_provider` | BOOLEAN | Can create virtual cards |
| `is_physical_card_provider` | BOOLEAN | Can create physical cards |
| `is_checkout_provider` | BOOLEAN | Can process checkouts |
| `is_bank_payout_provider` | BOOLEAN | Can process bank payouts |
| `is_mobile_money_payout_provider` | BOOLEAN | Can process mobile money payouts |
| `is_identity_verification_provider` | BOOLEAN | Can verify identity |
| `currencies_supported` | JSON (nullable) | Supported currencies |
| `status` | ENUM | 'active' or 'inactive' |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Supported Virtual Account Providers:**
- **SafeHaven**: Requires BVN verification
- **Fincra**: Requires BVN verification
- **Nomba**: BVN optional

---

### Supporting Tables

#### 5. `bvn_logs`

Stores BVN (Bank Verification Number) verification records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique BVN log identifier |
| `customer_id` | UUID (nullable) | References `users.id` |
| `bvn` | TEXT | BVN number (encrypted) |
| `bvn_hash` | STRING (nullable) | Hashed BVN |
| `verification_provider` | UUID (nullable) | References `service_providers.id` |
| `verification_provider_id` | UUID (nullable) | Provider-specific ID |
| `verification_provider_reference` | STRING (nullable) | Provider reference |
| `bvn_information` | JSON (nullable) | BVN data (name, DOB, etc.) |
| `status` | STRING(25) (nullable) | 'verified', 'pending', 'failed' |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Required for:**
- SafeHaven virtual accounts
- Fincra virtual accounts
- Optional for Nomba virtual accounts

---

#### 6. `transactions`

Stores all wallet transactions (debits and credits).

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique transaction identifier |
| `entity_id` | UUID | References `users.id` or `businesses.id` |
| `entity_type` | STRING | 'customer' or 'business' |
| `entity_label` | STRING (nullable) | 'customer' or 'business' |
| `wallet_id` | UUID | References `wallets.id` |
| `currency_id` | UUID | References `currencies.id` |
| `transaction_category` | ENUM | 'debit' or 'credit' |
| `transaction_type` | STRING | 'deposit', 'withdrawal', 'wallet_transfer', 'conversion', etc. |
| `transaction_method` | STRING (nullable) | 'card', 'bank_transfer', 'checkout', etc. |
| `transaction_reference` | UUID | Reference to source table (deposits, withdrawals, etc.) |
| `transaction_narration` | STRING (nullable) | Transaction description |
| `transaction_description` | STRING (nullable) | Detailed description |
| `amount` | DECIMAL(15,2) | Transaction amount |
| `processor` | UUID | References `service_providers.id` |
| `processor_reference` | STRING (nullable) | Provider reference |
| `beneficiary_id` | UUID (nullable) | Beneficiary identifier |
| `status` | STRING | 'pending', 'failed', 'successful', 'flagged' |
| `balance_before` | DECIMAL(15,2) | Wallet balance before transaction |
| `balance_after` | DECIMAL(15,2) | Wallet balance after transaction |
| `transaction_data` | JSON (nullable) | Additional transaction data |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

---

#### 7. `wallet_ledger`

Audit trail for all wallet balance changes.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique ledger entry identifier |
| `wallet_id` | UUID | References `wallets.id` |
| `transaction_id` | UUID (nullable) | References `transactions.id` |
| `transaction_reference` | STRING | Transaction reference |
| `transaction_type` | ENUM | 'credit' or 'debit' |
| `amount` | DECIMAL(19,4) | Transaction amount |
| `balance_before` | DECIMAL(19,4) | Balance before transaction |
| `balance_after` | DECIMAL(19,4) | Balance after transaction |
| `created_at` | TIMESTAMP | Entry timestamp |

**Purpose:**
- Complete audit trail
- Balance reconciliation
- Transaction history

---

#### 8. `wallet_transfers`

Stores wallet-to-wallet transfers.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique transfer identifier |
| `reference` | STRING | Transfer reference |
| `entity_id` | UUID | References `users.id` or `businesses.id` |
| `entity_type` | STRING | 'customer' or 'business' |
| `entity_label` | STRING (nullable) | 'customer' or 'business' |
| `transfer_type` | ENUM | 'internal' or 'external' |
| `currency_id` | UUID | References `currencies.id` |
| `source_wallet` | UUID | References `wallets.id` |
| `destination_wallet` | UUID | References `wallets.id` |
| `amount` | DECIMAL(19,4) | Transfer amount |
| `fee` | DECIMAL(19,4) (nullable) | Transfer fee |
| `narration` | STRING (nullable) | Transfer description |
| `recipient_entity_id` | UUID (nullable) | Recipient entity ID |
| `recipient_entity_type` | STRING (nullable) | Recipient entity type |
| `status` | ENUM | 'pending', 'successful', 'failed', 'rejected', 'cancelled', 'reversed', 'flagged' |
| `is_transaction_logged` | BOOLEAN | Whether logged in transactions table |
| `admin_notes` | TEXT (nullable) | Admin notes |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

---

#### 9. `deposits`

Stores deposit records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique deposit identifier |
| `reference` | STRING | Deposit reference |
| `entity_id` | UUID | References `users.id` or `businesses.id` |
| `entity_type` | STRING | 'customer' or 'business' |
| `entity_label` | STRING (nullable) | 'customer' or 'business' |
| `currency_id` | UUID | References `currencies.id` |
| `wallet_id` | UUID (nullable) | References `wallets.id` |
| `deposit_method` | STRING (nullable) | 'card', 'bank_transfer', 'virtual_account', 'manual' |
| `amount` | DECIMAL(15,2) | Deposit amount |
| `fee` | DECIMAL(15,2) (nullable) | Deposit fee |
| `processor` | UUID | References `service_providers.id` |
| `processor_reference` | STRING (nullable) | Provider reference |
| `processor_status` | STRING (nullable) | Provider status |
| `raw_provider_response` | JSON (nullable) | Raw provider response |
| `transaction_details` | JSON (nullable) | Transaction details |
| `proof_of_deposit` | STRING (nullable) | Proof document path |
| `proof_of_payment` | STRING (nullable) | Payment proof path |
| `payer_name` | STRING (nullable) | Payer name |
| `payer_account_number` | STRING (nullable) | Payer account number |
| `payer_bank_name` | STRING (nullable) | Payer bank name |
| `payer_bank_code` | STRING (nullable) | Payer bank code |
| `payer_narration` | STRING (nullable) | Payer narration |
| `sender_account_name` | STRING (nullable) | Sender account name |
| `sender_account_number` | STRING (nullable) | Sender account number |
| `sender_bank` | STRING (nullable) | Sender bank |
| `status` | ENUM | 'pending', 'successful', 'failed', 'rejected', 'cancelled', 'flagged' |
| `is_transaction_logged` | BOOLEAN | Whether logged in transactions table |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

---

#### 10. `withdrawals`

Stores withdrawal records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID (PK) | Unique withdrawal identifier |
| `reference` | STRING | Withdrawal reference |
| `entity_id` | UUID | References `users.id` or `businesses.id` |
| `entity_type` | STRING | 'customer' or 'business' |
| `entity_label` | STRING (nullable) | 'customer' or 'business' |
| `currency_id` | UUID | References `currencies.id` |
| `wallet_id` | UUID (nullable) | References `wallets.id` |
| `method` | STRING (nullable) | 'bank_transfer', 'mobile_money', 'manual' |
| `amount` | DECIMAL(15,2) | Withdrawal amount |
| `fee` | DECIMAL(15,2) (nullable) | Withdrawal fee |
| `narration` | STRING (nullable) | Withdrawal description |
| `recipient_name` | STRING (nullable) | Recipient name |
| `recipient_bank` | STRING (nullable) | Recipient bank |
| `recipient_bank_code` | STRING (nullable) | Recipient bank code |
| `recipient_account_number` | STRING (nullable) | Recipient account number |
| `recipient_account_type` | STRING (nullable) | Account type |
| `recipient_routing_number` | STRING (nullable) | Routing number |
| `recipient_swift_code` | STRING (nullable) | SWIFT code |
| `recipient_sort_code` | STRING (nullable) | Sort code |
| `recipient_bank_address` | TEXT (nullable) | Bank address |
| `recipient_country_code` | STRING(3) (nullable) | Country code |
| `recipient_additional_info` | TEXT (nullable) | Additional info |
| `recipient_email` | STRING (nullable) | Recipient email |
| `recipient_phone_number` | STRING (nullable) | Recipient phone |
| `recipient_street_address` | TEXT (nullable) | Street address |
| `recipient_city` | STRING (nullable) | City |
| `recipient_postal_code` | STRING (nullable) | Postal code |
| `processor` | UUID | References `service_providers.id` |
| `processor_reference` | STRING (nullable) | Provider reference |
| `processor_status` | STRING (nullable) | Provider status |
| `proof_of_withdrawal` | STRING (nullable) | Proof document path |
| `invoice_number` | STRING (nullable) | Invoice number |
| `invoice_date` | DATE (nullable) | Invoice date |
| `invoice_file_path` | STRING (nullable) | Invoice file path |
| `status` | ENUM | 'pending', 'successful', 'failed', 'rejected', 'cancelled', 'reversed', 'flagged' |
| `is_transaction_logged` | BOOLEAN | Whether logged in transactions table |
| `admin_notes` | TEXT (nullable) | Admin notes |
| `metadata` | JSON (nullable) | Additional metadata |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

---

## Creating a Virtual Account

### Prerequisites

1. **User must exist** in `users` table
2. **Wallet must exist** in `wallets` table
3. **Currency must have virtual account provider** configured:
   - `currencies.virtual_account_provider` must reference a valid `service_providers.id`
   - Provider must have `is_virtual_account_provider = true`
4. **BVN verification** (for SafeHaven and Fincra):
   - User must have a record in `bvn_logs`
   - `bvn_logs.status` must be 'verified'
   - `bvn_logs.verification_provider_reference` is used for SafeHaven
5. **Currency restriction**: Typically only NGN wallets support virtual accounts

### Step-by-Step Process

#### 1. Verify Wallet Ownership

```php
$wallet = Wallet::findOrFail($walletId);
$user = auth()->user();

// Verify ownership
if ($wallet->customer_id !== $user->id) {
    throw new UnauthorizedException('Unauthorized access to wallet');
}
```

#### 2. Check Currency and Provider

```php
// Load currency with provider
$wallet->load('currencyInfo', 'currencyInfo.virtualAccountProvider');

// Check if currency supports virtual accounts
if (!$wallet->currencyInfo->virtual_account_provider) {
    throw new Exception('Virtual accounts not supported for this currency');
}

// Check if virtual account already exists
if ($wallet->virtualAccount()->exists()) {
    throw new Exception('Virtual account already exists for this wallet');
}

// Check currency (typically NGN only)
if (strtolower($wallet->currencyInfo->code) !== 'ngn') {
    throw new Exception('Virtual accounts are only available for NGN wallets');
}
```

#### 3. Get BVN Log (if required)

```php
$providerSlug = strtolower($wallet->currencyInfo->virtualAccountProvider->slug ?? '');
$requiresBvn = in_array($providerSlug, ['safehaven', 'fincra']);

$bvnLog = null;
if ($requiresBvn) {
    $bvnLog = BvnLog::where('customer_id', $user->id)
        ->where('status', 'verified')
        ->latest()
        ->first();
    
    if (!$bvnLog) {
        throw new Exception('BVN verification required');
    }
}
```

#### 4. Create Virtual Account via Service

```php
use App\Services\VirtualAccountService;

$virtualAccountService = app(VirtualAccountService::class);
$virtualAccount = $virtualAccountService->createVirtualAccount($wallet, $user, $bvnLog);

if (!$virtualAccount) {
    throw new Exception('Failed to create virtual account');
}
```

#### 5. Service Implementation Flow

The `VirtualAccountService` will:

1. **Resolve provider** from currency configuration
2. **Route to provider-specific method**:
   - `createSafeHavenVirtualAccount()` - Requires BVN
   - `createFincraVirtualAccount()` - Requires BVN
   - `createNombaVirtualAccount()` - BVN optional
3. **Create VirtualAccount model** instance
4. **Call provider API** to create account
5. **Update model** with provider response data:
   - `account_number`
   - `account_name`
   - `bank_name`
   - `bank_code`
   - `provider_reference`
   - `provider_status`
6. **Save and return** the virtual account

### Provider-Specific Details

#### SafeHaven
- **Requires**: BVN verification
- **Uses**: `bvnLog->verification_provider_reference` as identity ID
- **Returns**: Account number, bank name (SafeHaven Microfinance Bank), bank code (090286)

#### Fincra
- **Requires**: BVN verification
- **Uses**: BVN information for account name (first_name, last_name from BVN data)
- **Returns**: Account number, bank name, bank code

#### Nomba
- **BVN**: Optional
- **Uses**: Customer name (first_name + last_name) or username
- **Returns**: Account number, bank details

### API Endpoint

```
POST /api/wallets/{wallet_id}/virtual-account
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "bvn": true  // Optional: defaults to true, set to false only for Nomba
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Virtual account created successfully",
  "data": {
    "id": "wallet-uuid",
    "customer_id": "user-uuid",
    "wallet_type": "main",
    "currency_id": "currency-uuid",
    "balance": 0.00,
    "virtual_account": {
      "id": "virtual-account-uuid",
      "wallet_id": "wallet-uuid",
      "account_name": "John Doe",
      "bank_name": "Providus Bank",
      "account_number": "0123456789",
      "bank_code": "101",
      "status": "active",
      "provider": "provider-uuid",
      "provider_reference": "PRV-REF-123456",
      "provider_status": "active"
    }
  }
}
```

**Error Responses:**

- **400**: Virtual account already exists
- **400**: BVN verification required
- **400**: Invalid currency (not NGN)
- **403**: Unauthorized access
- **500**: Failed to create virtual account

---

## Database Relationships

### Entity Relationship Diagram

```
┌─────────────┐
│    users    │
│  (customers)│
└──────┬──────┘
       │
       │ hasMany
       │
┌──────▼──────┐         ┌──────────────┐
│   wallets   │────────▶│  currencies  │
└──────┬──────┘         └──────┬───────┘
       │                       │
       │ hasOne                │ belongsTo
       │                       │
┌──────▼──────────┐    ┌──────▼──────────────┐
│virtual_accounts │    │ service_providers   │
└─────────────────┘    └─────────────────────┘
       │
       │ belongsTo
       │
┌──────▼──────┐
│  bvn_logs   │
└─────────────┘
```

### Relationship Details

#### Users → Wallets
- **Type**: One-to-Many
- **Foreign Key**: `wallets.customer_id` → `users.id`
- **Cascade**: ON DELETE CASCADE

#### Wallets → Virtual Accounts
- **Type**: One-to-One
- **Foreign Key**: `virtual_accounts.wallet_id` → `wallets.id`
- **Constraint**: One virtual account per wallet

#### Wallets → Currencies
- **Type**: Many-to-One
- **Foreign Key**: `wallets.currency_id` → `currencies.id`
- **Cascade**: ON DELETE RESTRICT

#### Currencies → Service Providers
- **Type**: Many-to-One (for virtual_account_provider)
- **Foreign Key**: `currencies.virtual_account_provider` → `service_providers.id`
- **Cascade**: ON DELETE SET NULL

#### Virtual Accounts → Service Providers
- **Type**: Many-to-One
- **Foreign Key**: `virtual_accounts.provider` → `service_providers.id`
- **Cascade**: ON DELETE RESTRICT

#### Users → BVN Logs
- **Type**: One-to-Many
- **Foreign Key**: `bvn_logs.customer_id` → `users.id`

#### BVN Logs → Service Providers
- **Type**: Many-to-One
- **Foreign Key**: `bvn_logs.verification_provider` → `service_providers.id`

#### Wallets → Transactions
- **Type**: One-to-Many
- **Foreign Key**: `transactions.wallet_id` → `wallets.id`
- **Cascade**: ON DELETE RESTRICT

#### Wallets → Wallet Ledger
- **Type**: One-to-Many
- **Foreign Key**: `wallet_ledger.wallet_id` → `wallets.id`
- **Cascade**: ON DELETE CASCADE

---

## API Endpoints

### Wallet Endpoints

#### List All Wallets
```
GET /api/wallets
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": "wallet-uuid",
      "customer_id": "user-uuid",
      "wallet_type": "main",
      "currency_id": "currency-uuid",
      "balance": 125000.50,
      "wallet_name": "Primary NGN Wallet",
      "currency_info": {
        "code": "NGN",
        "symbol": "₦",
        "name": "Nigerian Naira"
      },
      "virtual_account": {
        "account_number": "0123456789",
        "bank_name": "Providus Bank",
        "account_name": "John Doe"
      }
    }
  ]
}
```

#### Create Wallet
```
POST /api/wallets
```

**Request Body:**
```json
{
  "currency": "nigerian-naira",
  "wallet_type": "main",
  "wallet_name": "My Primary Wallet"
}
```

#### Create Virtual Account
```
POST /api/wallets/{wallet_id}/virtual-account
```

See [Creating a Virtual Account](#creating-a-virtual-account) section for details.

---

## Integration Guide

### Step 1: Database Setup

1. **Run migrations** in this order:
   - `service_providers`
   - `currencies`
   - `users` (or your user table)
   - `bvn_logs`
   - `wallets`
   - `virtual_accounts`
   - `transactions`
   - `wallet_ledger`
   - `wallet_transfers`
   - `deposits`
   - `withdrawals`

2. **Seed service providers**:
   ```php
   // Example: SafeHaven provider
   ServiceProvider::create([
       'name' => 'SafeHaven',
       'slug' => 'safehaven',
       'is_virtual_account_provider' => true,
       'is_bvn_verification_provider' => true,
       'status' => 'active'
   ]);
   ```

3. **Configure currency**:
   ```php
   // Example: NGN currency
   Currency::where('code', 'NGN')->update([
       'virtual_account_provider' => $safeHavenProviderId
   ]);
   ```

### Step 2: Create Wallet

```php
use App\Models\Transaction\Wallet;
use App\Models\Administration\Currency;

// Get currency
$currency = Currency::where('slug', 'nigerian-naira')->first();

// Create wallet
$wallet = Wallet::create([
    'customer_id' => $user->id,
    'currency_id' => $currency->id,
    'wallet_type' => 'main',
    'balance' => 0,
    'wallet_name' => 'Primary NGN Wallet'
]);
```

### Step 3: Verify BVN (if required)

```php
use App\Models\Kyc\BvnLog;

// Create BVN log after verification
$bvnLog = BvnLog::create([
    'customer_id' => $user->id,
    'bvn' => encrypt($bvn),
    'verification_provider' => $verificationProviderId,
    'verification_provider_reference' => $providerReference,
    'bvn_information' => $bvnData,
    'status' => 'verified'
]);
```

### Step 4: Create Virtual Account

```php
use App\Services\VirtualAccountService;

$virtualAccountService = app(VirtualAccountService::class);
$virtualAccount = $virtualAccountService->createVirtualAccount(
    $wallet,
    $user,
    $bvnLog // null for Nomba
);
```

### Step 5: Handle Provider Integration

You'll need to implement provider-specific services:

- `SafeHavenService`: Methods to create sub-accounts
- `FincraService`: Methods to create virtual accounts
- `NombaService`: Methods to create virtual accounts

Each service should:
1. Make API calls to the provider
2. Handle responses
3. Return standardized format

---

## Important Notes

1. **One Main Wallet Per Currency**: Users can only have one `main` wallet per currency
2. **One Virtual Account Per Wallet**: Each wallet can have at most one virtual account
3. **Currency Restriction**: Virtual accounts are typically only available for NGN
4. **BVN Requirement**: SafeHaven and Fincra require verified BVN; Nomba does not
5. **Provider Configuration**: Currency must have `virtual_account_provider` set
6. **Balance Management**: Always use transactions when updating wallet balances
7. **Audit Trail**: All balance changes are logged in `wallet_ledger`
8. **Transaction Logging**: Deposits and withdrawals should create corresponding transaction records

---

## Support

For questions or issues:
1. Check the error messages in API responses
2. Verify database constraints and foreign keys
3. Ensure service providers are properly configured
4. Verify BVN status for providers that require it

---

## License

This documentation is part of the wallet system implementation.
