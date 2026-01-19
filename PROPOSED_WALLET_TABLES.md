# Proposed Standard Wallet System Tables

## Overview

This document shows the proposed table structures for the standard wallet system, adapted for **vendors, lenders, and admin (tenmg) only**. 

**Excluded:**
- ❌ `bvn_logs` (you'll use your KYC system)
- ❌ `wallet_transfers` (not needed)

**Adaptations:**
- ✅ Uses `UUID` for all ID fields
- ✅ Only `business_id` (no `customer_id`) - for vendors, lenders, admin only
- ✅ Wallet types specific to your business needs

---

## Core Tables

### 1. `service_providers`

**Purpose:** Stores payment service provider information (Fincra, SafeHaven, Nomba, etc.)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Unique provider identifier |
| `name` | VARCHAR(255) | NOT NULL | Provider name (e.g., "Fincra", "SafeHaven", "Nomba") |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | URL-friendly identifier |
| `description` | VARCHAR(255) | NULLABLE | Provider description |
| `config` | JSON | NULLABLE | Provider configuration (API keys, etc.) |
| `metadata` | JSON | NULLABLE | Additional metadata |
| `is_bvn_verification_provider` | BOOLEAN | DEFAULT false | Can verify BVN (you'll use your KYC) |
| `is_virtual_account_provider` | BOOLEAN | DEFAULT false | Can create virtual accounts |
| `is_virtual_card_provider` | BOOLEAN | DEFAULT false | Can create virtual cards |
| `is_physical_card_provider` | BOOLEAN | DEFAULT false | Can create physical cards |
| `is_checkout_provider` | BOOLEAN | DEFAULT false | Can process checkouts |
| `is_bank_payout_provider` | BOOLEAN | DEFAULT false | Can process bank payouts |
| `is_mobile_money_payout_provider` | BOOLEAN | DEFAULT false | Can process mobile money payouts |
| `is_identity_verification_provider` | BOOLEAN | DEFAULT false | Can verify identity |
| `currencies_supported` | JSON | NULLABLE | Supported currencies (e.g., ["NGN"]) |
| `status` | ENUM | DEFAULT 'active' | 'active' or 'inactive' |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Last update timestamp |

**Indexes:**
- Primary key on `id`
- Unique index on `slug`
- Index on `status`

---

### 2. `currencies`

**Purpose:** Stores currency information and provider configurations

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Unique currency identifier |
| `classification` | ENUM | NOT NULL | 'fiat' or 'crypto' |
| `name` | VARCHAR(125) | NOT NULL | Currency name (e.g., "Nigerian Naira") |
| `code` | VARCHAR(10) | NULLABLE, UNIQUE | Currency code (e.g., "NGN") |
| `symbol` | VARCHAR(10) | NULLABLE | Currency symbol (e.g., "₦") |
| `slug` | VARCHAR(10) | NULLABLE, UNIQUE | URL-friendly identifier (e.g., "nigerian-naira") |
| `decimal_places` | TINYINT | NULLABLE | Number of decimal places (default: 2) |
| `icon` | VARCHAR(255) | NULLABLE | Icon URL |
| `description` | TEXT | NULLABLE | Currency description |
| `tier_1_limits` | JSON | NULLABLE | Tier 1 transaction limits |
| `tier_2_limits` | JSON | NULLABLE | Tier 2 transaction limits |
| `tier_3_limits` | JSON | NULLABLE | Tier 3 transaction limits |
| `country_code` | VARCHAR(3) | NULLABLE | ISO country code (e.g., "NGA") |
| `virtual_account_provider` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | References `service_providers.id` |
| `temp_virtual_account_provider` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | Temporary provider |
| `virtual_card_provider` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | Virtual card provider |
| `bank_transfer_collection_provider` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | Bank transfer collection provider |
| `mobile_money_collection_provider` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | Mobile money collection provider |
| `bank_transfer_payout_provider` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | Bank transfer payout provider |
| `mobile_money_payout_provider` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | Mobile money payout provider |
| `status` | ENUM | DEFAULT 'active' | 'active' or 'inactive' |
| `is_active` | BOOLEAN | DEFAULT true | Active status flag |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Last update timestamp |

**Key Field:**
- `virtual_account_provider`: Must be set for a currency to support virtual accounts

**Indexes:**
- Primary key on `id`
- Unique index on `code`
- Unique index on `slug`
- Foreign key indexes on provider fields

---

### 3. `wallets`

**Purpose:** Unified wallet table for vendors, lenders, and admin

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Unique wallet identifier |
| `business_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, NOT NULL, ON DELETE CASCADE | Reference to business (vendor, lender, or admin) |
| `wallet_type` | ENUM | NOT NULL | Wallet type (see below) |
| `currency_id` | UUID | FOREIGN KEY → `currencies.id`, NOT NULL | Reference to currency |
| `balance` | DECIMAL(18,2) | DEFAULT 0 | Current wallet balance |
| `wallet_name` | VARCHAR(255) | NULLABLE | Optional custom name for wallet |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Last update timestamp |

**Wallet Types:**
- `vendor_payout` - Vendor wallet for receiving installment payments
- `vendor_credit_voucher` - Vendor wallet for credit vouchers given out
- `lender_investment` - Lender wallet for investment funds (earns interest)
- `lender_deposit` - Lender wallet for deposited capital (used to fund loans)
- `lender_ledger` - Lender wallet tracking outstanding loan amounts
- `admin_main` - Admin (10MG) main wallet for commissions and interest

**Constraints:**
- One wallet per `(business_id, wallet_type, currency_id)` combination
- Foreign keys: `business_id` → `businesses.id`, `currency_id` → `currencies.id`

**Indexes:**
- Primary key on `id`
- Unique index on `(business_id, wallet_type, currency_id)`
- Foreign key index on `business_id`
- Foreign key index on `currency_id`
- Index on `wallet_type`

---

### 4. `virtual_accounts`

**Purpose:** Stores virtual bank accounts linked to wallets (for receiving payments)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Unique virtual account identifier |
| `business_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, NOT NULL, ON DELETE CASCADE | Reference to business |
| `currency_id` | UUID | FOREIGN KEY → `currencies.id`, NULLABLE | Reference to currency |
| `wallet_id` | UUID | FOREIGN KEY → `wallets.id`, NULLABLE, UNIQUE | Reference to wallet (one per wallet) |
| `type` | ENUM | NOT NULL | 'individual' or 'corporate' |
| `provider` | UUID | FOREIGN KEY → `service_providers.id`, NOT NULL | Reference to service provider |
| `provider_reference` | VARCHAR(255) | NULLABLE | Reference from provider API |
| `provider_status` | VARCHAR(255) | NULLABLE | Status from provider |
| `account_name` | VARCHAR(255) | NULLABLE | Account holder name |
| `bank_name` | VARCHAR(255) | NULLABLE | Bank name |
| `account_number` | VARCHAR(255) | NULLABLE, UNIQUE | Virtual account number |
| `account_type` | VARCHAR(255) | NULLABLE | Account type |
| `bank_code` | VARCHAR(255) | NULLABLE | Bank code |
| `routing_number` | VARCHAR(255) | NULLABLE | Routing number (international) |
| `country_code` | VARCHAR(3) | NULLABLE | ISO country code |
| `iban` | VARCHAR(255) | NULLABLE | IBAN (international) |
| `check_number` | VARCHAR(255) | NULLABLE | Check number |
| `sort_code` | VARCHAR(255) | NULLABLE | Sort code |
| `bank_swift_code` | VARCHAR(255) | NULLABLE | SWIFT code |
| `addressable_in` | VARCHAR(10) | NULLABLE | Currency code |
| `bank_address` | TEXT | NULLABLE | Bank address |
| `status` | VARCHAR(255) | DEFAULT 'pending' | 'active', 'inactive', 'suspended', 'pending', etc. |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Last update timestamp |

**Constraints:**
- One virtual account per wallet
- Foreign keys: `currency_id` → `currencies.id`, `provider` → `service_providers.id`, `wallet_id` → `wallets.id`

**Indexes:**
- Primary key on `id`
- Unique index on `wallet_id`
- Unique index on `account_number`
- Foreign key indexes on `business_id`, `currency_id`, `provider`, `wallet_id`
- Index on `status`

**Important Notes:**
- One virtual account per wallet
- Only available for NGN wallets (by default)
- KYC verification will be handled by your existing KYC system

---

### 5. `transactions`

**Purpose:** Unified transaction table for all wallet transactions (debits and credits)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Unique transaction identifier |
| `business_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, NOT NULL, ON DELETE CASCADE | Reference to business |
| `wallet_id` | UUID | FOREIGN KEY → `wallets.id`, NOT NULL | Reference to wallet |
| `currency_id` | UUID | FOREIGN KEY → `currencies.id`, NOT NULL | Reference to currency |
| `transaction_category` | ENUM | NOT NULL | 'debit' or 'credit' |
| `transaction_type` | VARCHAR(255) | NOT NULL | Transaction type (see below) |
| `transaction_method` | VARCHAR(255) | NULLABLE | Payment method: 'card', 'bank_transfer', 'virtual_account', 'checkout', 'fincra', etc. |
| `transaction_reference` | VARCHAR(255) | NULLABLE | Reference to source (deposits, withdrawals, loan_id, etc.) |
| `transaction_narration` | VARCHAR(255) | NULLABLE | Transaction description |
| `transaction_description` | TEXT | NULLABLE | Detailed description |
| `amount` | DECIMAL(18,2) | NOT NULL | Transaction amount |
| `processor` | UUID | FOREIGN KEY → `service_providers.id`, NULLABLE | Reference to service provider |
| `processor_reference` | VARCHAR(255) | NULLABLE | Provider reference |
| `beneficiary_id` | UUID | NULLABLE | Beneficiary identifier |
| `status` | VARCHAR(255) | DEFAULT 'pending' | 'pending', 'successful', 'failed', 'flagged', 'cancelled' |
| `balance_before` | DECIMAL(18,2) | NOT NULL | Wallet balance before transaction |
| `balance_after` | DECIMAL(18,2) | NOT NULL | Wallet balance after transaction |
| `transaction_data` | JSON | NULLABLE | Additional transaction data (gateway response, etc.) |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Last update timestamp |

**Transaction Types:**
- `deposit` - Deposit to wallet
- `withdrawal` - Withdrawal from wallet
- `loan_disbursement` - Loan amount disbursed
- `loan_repayment` - Loan repayment received
- `loan_repayment_interest` - Interest from loan repayment
- `loan_repayment_principal` - Principal repayment
- `vendor_payout` - Vendor payout from credit voucher
- `vendor_credit_voucher` - Credit voucher issued
- `lender_deposit` - Lender deposit to deposit wallet
- `lender_investment_return` - Return to investment wallet
- `admin_commission` - Platform commission
- `admin_interest` - Platform interest from loans
- `order_payment` - Payment from ecommerce order
- `order_commission` - Commission from order

**Indexes:**
- Primary key on `id`
- Foreign key indexes on `business_id`, `wallet_id`, `currency_id`, `processor`
- Index on `transaction_category`
- Index on `transaction_type`
- Index on `status`
- Index on `transaction_reference`

---

### 6. `wallet_ledger`

**Purpose:** Complete audit trail for all wallet balance changes

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Unique ledger entry identifier |
| `wallet_id` | UUID | FOREIGN KEY → `wallets.id`, NOT NULL, ON DELETE CASCADE | Reference to wallet |
| `transaction_id` | UUID | FOREIGN KEY → `transactions.id`, NULLABLE | Reference to transaction |
| `transaction_reference` | VARCHAR(255) | NOT NULL | Transaction reference (from transactions table or external) |
| `transaction_type` | ENUM | NOT NULL | 'credit' or 'debit' |
| `amount` | DECIMAL(19,4) | NOT NULL | Transaction amount |
| `balance_before` | DECIMAL(19,4) | NOT NULL | Balance before transaction |
| `balance_after` | DECIMAL(19,4) | NOT NULL | Balance after transaction |
| `created_at` | TIMESTAMP | NULLABLE | Entry timestamp |

**Purpose:**
- Complete audit trail
- Balance reconciliation
- Transaction history
- Compliance and reporting

**Indexes:**
- Primary key on `id`
- Foreign key index on `wallet_id`
- Foreign key index on `transaction_id`
- Index on `transaction_reference`
- Index on `created_at`

---

## Table Relationships

```
businesses
├── wallets (one-to-many)
│   ├── virtual_accounts (one-to-one)
│   ├── transactions (one-to-many)
│   └── wallet_ledger (one-to-many)
│
├── currencies (one-to-many wallets)
│   └── service_providers (one-to-many currencies)
```

---

## Migration Order

1. `service_providers` - Create first (referenced by currencies)
2. `currencies` - Create second (referenced by wallets)
3. `wallets` - Create third (references businesses and currencies)
4. `virtual_accounts` - Create fourth (references wallets)
5. `transactions` - Create fifth (references wallets)
6. `wallet_ledger` - Create last (references wallets and transactions)

---

## Key Differences from Current System

### Current System (Fragmented)
- `ecommerce_wallets` (suppliers)
- `credit_vendor_wallets` (vendors)
- `credit_lenders_wallets` (lenders)
- `ten_mg_wallets` (admin)
- `ecommerce_transactions`
- `credit_transaction_histories`
- `tenmg_transaction_histories`

### Proposed System (Unified)
- ✅ Single `wallets` table for all business types
- ✅ Single `transactions` table for all transactions
- ✅ `wallet_ledger` for complete audit trail
- ✅ `virtual_accounts` support
- ✅ Multi-currency ready
- ✅ Multi-provider support

---

## Wallet Type Mapping

| Current Table | Current Type | Proposed Wallet Type |
|--------------|--------------|---------------------|
| `credit_vendor_wallets` | `payout` | `vendor_payout` |
| `credit_vendor_wallets` | `credit_voucher` | `vendor_credit_voucher` |
| `credit_lenders_wallets` | `investment` | `lender_investment` |
| `credit_lenders_wallets` | `deposit` | `lender_deposit` |
| `credit_lenders_wallets` | `ledger` | `lender_ledger` |
| `ten_mg_wallets` | (single wallet) | `admin_main` |

---

## Next Steps

1. **Review this document** - Check if the table structures meet your needs
2. **Migration creation** - I'll create the migration files once you approve
3. **Data migration** - Plan migration of existing data to new structure
4. **Model updates** - Update Eloquent models
5. **Service updates** - Update wallet services to use new structure

---

**Ready for Review** - Please check the table structures and let me know if you want any changes before I create the migration files!
