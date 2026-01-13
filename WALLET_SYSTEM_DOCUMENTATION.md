# Wallet System Documentation

## Overview

The 10MG platform implements a comprehensive multi-wallet system that manages financial transactions for different business types: Suppliers, Vendors, Lenders, and the platform itself (10MG). The system integrates with Fincra payment gateway for processing payments, mandates, and payouts.

## Table of Contents

1. [Wallet Types](#wallet-types)
2. [Wallet Creation](#wallet-creation)
3. [Virtual Accounts & Bank Accounts](#virtual-accounts--bank-accounts)
4. [Transaction Management](#transaction-management)
5. [Payment Processing](#payment-processing)
6. [Withdrawal System](#withdrawal-system)
7. [Database Schema](#database-schema)
8. [API Endpoints](#api-endpoints)
9. [Integration Details](#integration-details)

---

## Wallet Types

The system supports four main wallet categories:

### 1. EcommerceWallet (Suppliers)

**Purpose:** Manages funds for suppliers in the ecommerce marketplace.

**Model:** `App\Models\EcommerceWallet`

**Features:**
- Tracks `previous_balance` and `current_balance`
- Linked to a `business_id` (supplier business)
- Associated with bank accounts for withdrawals
- Records transactions via polymorphic relationship

**Table:** `ecommerce_wallets`

**Fields:**
- `id` - Primary key
- `business_id` - Foreign key to businesses table
- `previous_balance` - Decimal(18,2)
- `current_balance` - Decimal(18,2)
- `created_at`, `updated_at` - Timestamps

### 2. CreditVendorWallets (Vendors)

**Purpose:** Manages two types of wallets for vendors offering credit services.

**Model:** `App\Models\CreditVendorWallets`

**Wallet Types:**
- **`payout`** - Wallet where vendors receive installment payments for loans their customers took
- **`credit_voucher`** - Wallet representing total amount given out as credit to vendor customers

**Table:** `credit_vendor_wallets`

**Fields:**
- `id` - Primary key
- `vendor_id` - Foreign key to businesses table
- `type` - Enum: 'payout' | 'credit_voucher'
- `current_balance` - Decimal(18,2), default 0
- `prev_balance` - Decimal(18,2), default 0
- `last_transaction_ref` - String, nullable
- `created_at`, `updated_at` - Timestamps

**Business Logic:**
- When a loan is disbursed, the loan amount is credited to the vendor's `credit_voucher` wallet
- When loan repayments are made, the principal is debited from `credit_voucher` and credited to `payout` wallet
- Vendors can withdraw funds from their `payout` wallet

### 3. CreditLendersWallet (Lenders)

**Purpose:** Manages three types of wallets for lenders providing capital.

**Model:** `App\Models\CreditLendersWallet`

**Wallet Types:**
- **`investment`** - Wallet for lender's investment funds (earns interest from loans)
- **`deposit`** - Wallet for lender's deposited capital (used to fund loans)
- **`ledger`** - Wallet tracking total outstanding loan amounts

**Table:** `credit_lenders_wallets`

**Fields:**
- `id` - Primary key
- `lender_id` - Foreign key to businesses table
- `type` - Enum: 'investment' | 'deposit' | 'ledger'
- `current_balance` - Decimal(10,2), default 0
- `prev_balance` - Decimal(10,2), default 0
- `last_transaction_ref` - String, nullable
- `created_at`, `updated_at` - Timestamps

**Business Logic:**
- Lenders deposit funds into their `deposit` wallet
- When a loan is approved, the capital amount is debited from `deposit` wallet
- The same amount is credited to `ledger` wallet (tracking outstanding loans)
- When repayments are made, principal + lender interest goes to `investment` wallet
- Principal amount is debited from `ledger` wallet

### 4. TenMgWallet (Platform Wallet)

**Purpose:** Manages the platform's commission and interest earnings.

**Model:** `App\Models\TenMgWallet`

**Features:**
- Single wallet for the entire platform
- Tracks platform commissions from ecommerce orders
- Tracks platform interest from loan repayments
- Uses polymorphic relationship for transactions

**Table:** `ten_mg_wallets`

**Fields:**
- `id` - Primary key
- `previous_balance` - Decimal(18,2)
- `current_balance` - Decimal(18,2)
- `created_at`, `updated_at` - Timestamps

---

## Wallet Creation

### Automatic Wallet Creation

Wallets are automatically created when a business is registered based on their business type. This is handled in `App\Services\AuthService::handleAccountSetup()`.

#### For Suppliers (BusinessType::SUPPLIER)

```php
public function createEcommerceWallet($business)
{
    $business->wallet()->create([
        'business_id' => $business->id,
        'previous_balance' => 0,
        'current_balance' => 0,
    ]);
}
```

**Location:** `app/Services/AuthService.php:375-383`

#### For Lenders (BusinessType::LENDER)

```php
public function createLendersWallet($business)
{
    $walletTypes = ['investment', 'deposit', 'ledger'];
    foreach ($walletTypes as $type) {
        CreditLendersWallet::firstOrCreate([
            'lender_id' => $business->id,
            'type' => $type,
        ], [
            'lender_id' => $business->id,
            'type' => $type,
            'prev_balance' => 0,
            'current_balance' => 0,
        ]);
    }
}
```

**Location:** `app/Services/AuthService.php:385-400`

#### For Vendors (BusinessType::VENDOR)

```php
public function createVendorWallet(Business $business): ?bool
{
    $data = [
        'current_balance' => 0,
        'prev_balance' => 0,
        'last_transaction_ref' => null,
    ];

    $payoutWallet = CreditVendorWallets::updateOrCreate(
        ['vendor_id' => $business->id, 'type' => 'payout'],
        ['type' => 'payout', ...$data]
    );

    $creditVoucherWallet = CreditVendorWallets::updateOrCreate(
        ['vendor_id' => $business->id, 'type' => 'credit_voucher'],
        ['type' => 'credit_voucher', ...$data]
    );

    return ($payoutWallet && $creditVoucherWallet) ? true : false;
}
```

**Location:** `app/Repositories/CreditVendorWalletRepository.php:10-29`

### Manual Wallet Creation

Wallets can also be created on-demand:

- **EcommerceWallet:** Created automatically when a supplier adds a bank account if it doesn't exist
- **TenMgWallet:** Created on first transaction if it doesn't exist

---

## Virtual Accounts & Bank Accounts

### Virtual Accounts

**Current Status:** The system references virtual accounts in Fincra integration mock responses, but explicit virtual account creation functionality is not currently implemented in the codebase. Virtual accounts are mentioned in the Fincra payment response structure but are not actively created or managed.

**Reference Location:** 
- `app/Repositories/FincraMandateRepository.php:1050-1064` (mock response)
- `app/Repositories/FincraPaymentRepository.php:687` (response field)

**Note:** Virtual account creation would typically be handled through Fincra's API if needed in the future.

### Bank Accounts

The system stores bank account information separately from wallets and links them for withdrawal purposes.

#### EcommerceBankAccount (For Suppliers)

**Model:** `App\Models\EcommerceBankAccount`

**Table:** `ecommerce_bank_accounts`

**Purpose:** Stores bank account details for suppliers to receive payouts from their ecommerce wallet.

**Key Features:**
- Linked to supplier via `supplier_id` (business_id)
- One-to-one relationship with EcommerceWallet
- Used for withdrawal requests
- Requires OTP verification before adding

**API Endpoint:** `POST /api/supplier/bank-account` (AddBankAccountController)

#### CreditLenderBankAccounts (For Lenders)

**Model:** `App\Models\CreditLenderBankAccounts`

**Table:** `credit_lender_bank_accounts`

**Purpose:** Stores bank account details for lenders to receive investment returns and process withdrawals.

**Key Features:**
- Linked to lender via `lender_id` (business_id)
- Used for withdrawal requests from investment wallet
- Bank account verification via Fincra API

#### Bank Account Verification

The system uses Fincra's account resolution API to verify bank accounts:

**Endpoint:** `POST /api/bank/verify`

**Service:** `App\Services\Bank\BankService::verifyBankAccount()`

**Fincra API:** `POST /core/accounts/resolve`

**Request:**
```json
{
    "accountNumber": "1234567890",
    "type": "nuban",
    "bankCode": "044"
}
```

---

## Transaction Management

### Ecommerce Transactions

**Model:** `App\Models\EcommerceTransaction`

**Table:** `ecommerce_transactions`

**Purpose:** Records all transactions for supplier wallets and platform wallet.

**Transaction Types:**
- `CREDIT` - Funds added to wallet
- `DEBIT` - Funds removed from wallet

**Transaction Groups:**
- `order_payment` - Payment from completed order (supplier)
- `order_cancellation` - Reversal from cancelled order (supplier)
- `tenmg_order_payment` - Platform commission from order (10MG)
- `tenmg_order_cancellation` - Commission reversal from cancelled order (10MG)

**Key Fields:**
- `walletable_id` - Polymorphic: wallet ID
- `walletable_type` - Polymorphic: wallet model class
- `ecommerce_order_id` - Related order
- `ecommerce_order_detail_id` - Related order detail
- `txn_type` - CREDIT or DEBIT
- `txn_group` - Transaction category
- `amount` - Transaction amount
- `balance_before` - Wallet balance before transaction
- `balance_after` - Wallet balance after transaction
- `status` - Transaction status

**Service:** `App\Services\SupplierOrderWalletService`

**Key Methods:**
- `credit(EcommerceOrder $order)` - Credit supplier wallet when order completes
- `debit(EcommerceOrder $order)` - Debit supplier wallet when order cancels

### Credit Transaction History

**Model:** `App\Models\CreditTransactionHistory`

**Table:** `credit_transaction_histories`

**Purpose:** Records all transactions for vendor and lender wallets.

**Transaction Types:**
- `CREDIT` - Funds added
- `DEBIT` - Funds removed

**Transaction Groups:**
- `deposit` - Lender deposit to wallet
- `withdrawal` - Withdrawal from wallet
- `loan_disbursement` - Loan amount disbursed
- `repayment` - Loan repayment received
- `repayment_interest` - Interest from repayment
- `payout` - Vendor payout from credit voucher

**Key Fields:**
- `identifier` - Auto-generated unique reference (THG prefix)
- `business_id` - Business (vendor or lender)
- `amount` - Transaction amount
- `type` - CREDIT or DEBIT
- `status` - Transaction status (pending, success, failed, etc.)
- `transaction_group` - Transaction category
- `description` - Transaction description
- `payment_method` - Payment method (fincra, etc.)
- `reference` - External payment reference
- `meta` - JSON metadata
- `loan_application_id` - Related loan application (if applicable)
- `wallet_id` - Related wallet ID (if applicable)

**Auto-generation:** The `identifier` field is automatically generated using `UtilityHelper::generateSlug('THG')` on model creation.

---

## Payment Processing

### Payment Gateway: Fincra

The system integrates with **Fincra** payment gateway for:
- Payment collection
- Bank account verification
- Direct debit mandates
- Payouts/withdrawals

**Configuration:** `config/services.php` (fincra.url, fincra.secret)

### Payment Flow

#### 1. Ecommerce Order Payment

**Flow:**
1. Customer initiates payment via `FincraPaymentRepository::initializePayment()`
2. Payment record created in `ecommerce_payments` table
3. Payment reference generated (PAY prefix)
4. Payment verified via webhook or manual verification
5. On success, order status updated to PENDING
6. When order completes, supplier wallets are credited via `SupplierOrderWalletService`

**Reference Prefix:** `PAY`

**Webhook Events:**
- `charge.successful` - Payment successful
- `charge.failed` - Payment failed

#### 2. Lender Deposit

**Flow:**
1. Lender initiates deposit via dashboard
2. Transaction record created with `THG` prefix
3. Payment processed through Fincra
4. On success, lender's `deposit` wallet is credited

**Reference Prefix:** `THG`

**Service:** `App\Repositories\LenderDashboardRepository::completeWalletDeposit()`

#### 3. Loan Repayment

**Flow:**
1. Repayment scheduled via direct debit mandate
2. Fincra processes debit from customer account
3. On success:
   - Lender's `investment` wallet credited (principal + lender interest)
   - Lender's `ledger` wallet debited (principal)
   - Vendor's `credit_voucher` wallet debited (principal)
   - Vendor's `payout` wallet credited (principal)
   - 10MG wallet credited (platform interest)

**Reference Prefix:** `LNR`

**Service:** `App\Repositories\FincraMandateRepository::completeDirectDebitRequest()`

### Webhook Processing

**Endpoint:** Webhook handler processes Fincra events

**Location:** `app/Repositories/FincraPaymentRepository::verifyFincraPaymentWebhook()`

**Events Handled:**
- `charge.successful` - Complete payment/transaction
- `charge.failed` - Mark payment as failed
- `mandate.approved` - Complete mandate setup
- `direct_debit.success` - Complete direct debit
- `payout.successful` - Complete payout
- `payout.failed` - Handle payout failure

**Security:** Webhook signature verification using HMAC SHA512

---

## Withdrawal System

### Supplier Withdrawals

**Endpoint:** `POST /api/supplier/withdraw`

**Controller:** `App\Http\Controllers\API\WithdrawFundController`

**Process:**
1. User requests withdrawal with amount and bank account
2. System validates:
   - User has supplier role
   - Bank account exists and is linked to user's business
   - Wallet has sufficient balance
3. OTP sent to user's email
4. User confirms with OTP
5. Withdrawal processed via Fincra
6. Wallet debited on success

**Request Validation:** `App\Http\Requests\WithdrawFundRequest`

### Vendor Withdrawals

**Endpoint:** `POST /api/vendor/wallet/withdraw`

**Controller:** `App\Http\Controllers\API\Vendor\VendorWalletController::initWithdrawals()`

**Process:**
1. Vendor requests withdrawal from `payout` wallet
2. System validates sufficient balance
3. Wallet locked and debited immediately
4. Transaction created with status `pending_verification`
5. OTP sent to vendor's email
6. Vendor confirms with OTP
7. Withdrawal processed via Fincra

**Repository:** `App\Repositories\VendorWalletRepository::initWithdrawals()`

**Features:**
- Uses database locking (`lockForUpdate()`) to prevent race conditions
- Prevents double withdrawals
- OTP verification required

### Lender Withdrawals

**Endpoint:** `POST /api/lender/wallet/withdraw`

**Controller:** `App\Http\Controllers\API\Lender\LenderDashboardController`

**Process:**
1. Lender requests withdrawal from `investment` wallet
2. System validates sufficient balance
3. Withdrawal processed (mock in non-production)
4. Wallet debited
5. Transaction recorded

**Repository:** `App\Repositories\LenderDashboardRepository::withdrawFunds()`

---

## Database Schema

### Ecommerce Wallets

```sql
CREATE TABLE ecommerce_wallets (
    id BIGINT PRIMARY KEY,
    business_id BIGINT FOREIGN KEY REFERENCES businesses(id),
    previous_balance DECIMAL(18,2),
    current_balance DECIMAL(18,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Credit Vendor Wallets

```sql
CREATE TABLE credit_vendor_wallets (
    id BIGINT PRIMARY KEY,
    vendor_id BIGINT FOREIGN KEY REFERENCES businesses(id),
    type ENUM('payout', 'credit_voucher'),
    current_balance DECIMAL(18,2) DEFAULT 0,
    prev_balance DECIMAL(18,2) DEFAULT 0,
    last_transaction_ref VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Credit Lenders Wallets

```sql
CREATE TABLE credit_lenders_wallets (
    id BIGINT PRIMARY KEY,
    lender_id BIGINT FOREIGN KEY REFERENCES businesses(id),
    type ENUM('investment', 'deposit', 'ledger'),
    current_balance DECIMAL(10,2) DEFAULT 0,
    prev_balance DECIMAL(10,2) DEFAULT 0,
    last_transaction_ref VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Ten MG Wallets

```sql
CREATE TABLE ten_mg_wallets (
    id BIGINT PRIMARY KEY,
    previous_balance DECIMAL(18,2),
    current_balance DECIMAL(18,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Ecommerce Transactions

```sql
CREATE TABLE ecommerce_transactions (
    id BIGINT PRIMARY KEY,
    walletable_id BIGINT,
    walletable_type VARCHAR(255),
    ecommerce_order_detail_id BIGINT,
    supplier_id BIGINT,
    ecommerce_order_id BIGINT,
    txn_type VARCHAR(255),
    txn_group VARCHAR(255),
    amount DECIMAL(18,2),
    balance_before DECIMAL(18,2),
    balance_after DECIMAL(18,2),
    status VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Credit Transaction Histories

```sql
CREATE TABLE credit_transaction_histories (
    id BIGINT PRIMARY KEY,
    identifier VARCHAR(255) UNIQUE,
    business_id BIGINT FOREIGN KEY REFERENCES businesses(id),
    amount DECIMAL(18,2),
    type ENUM('CREDIT', 'DEBIT'),
    status VARCHAR(255),
    transaction_group VARCHAR(255),
    description TEXT,
    payment_method VARCHAR(255),
    reference VARCHAR(255),
    meta JSON,
    loan_application_id BIGINT,
    wallet_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## API Endpoints

### Supplier Endpoints

- `GET /api/supplier/wallet` - Get supplier wallet balance and details
- `POST /api/supplier/bank-account` - Add bank account for withdrawals
- `POST /api/supplier/withdraw` - Initiate withdrawal request
- `GET /api/supplier/transactions` - Get transaction history

### Vendor Endpoints

- `GET /api/vendor/wallet/stats` - Get vendor wallet statistics
- `GET /api/vendor/wallet/transactions` - Get transaction history
- `POST /api/vendor/wallet/withdraw` - Initiate withdrawal (with OTP)
- `POST /api/vendor/wallet/withdraw/confirm` - Confirm withdrawal with OTP

### Lender Endpoints

- `GET /api/lender/dashboard` - Get lender dashboard with wallet balances
- `POST /api/lender/wallet/deposit` - Initiate deposit to deposit wallet
- `POST /api/lender/wallet/withdraw` - Withdraw from investment wallet
- `GET /api/lender/statement` - Get transaction statement

### Admin Endpoints

- `GET /api/admin/wallet` - Get admin wallet details
- `POST /api/admin/withdraw` - Admin withdrawal (if applicable)

---

## Integration Details

### Fincra Integration

**Base URL Configuration:**
- Production: `https://api.fincra.com`
- Sandbox: `https://sandboxapi.fincra.com`

**Key Endpoints Used:**
1. **Account Resolution:** `POST /core/accounts/resolve`
   - Verify bank account details
   
2. **Payment Collection:** `GET /collections/merchant-reference/{reference}`
   - Verify payment status
   
3. **Direct Debit Mandate:** `POST /v2/mandate-mgt/mandates`
   - Create debit mandate for loan repayments
   
4. **Mandate Payment:** `POST /mandate-mgt/mandates/payment`
   - Process direct debit payment
   
5. **Payout:** (Endpoint varies based on Fincra version)
   - Process withdrawals to bank accounts

**Authentication:**
- API Key in header: `api-key: {fincra_secret}`
- Webhook signature verification: HMAC SHA512

**Webhook Configuration:**
- Signature header: `signature`
- Payload verification required for all webhook events

### Transaction Reference Prefixes

The system uses specific prefixes for different transaction types:

- **`PAY`** - Ecommerce order payments
- **`THG`** - Credit transaction history (deposits, withdrawals)
- **`LNR`** - Loan repayment transactions
- **`LND`** - Loan disbursement records

### Security Features

1. **Database Locking:** Wallets are locked during transactions to prevent race conditions
2. **OTP Verification:** Required for sensitive operations (withdrawals, bank account addition)
3. **Webhook Signature Verification:** All Fincra webhooks are verified
4. **Transaction Idempotency:** Prevents double-crediting/debiting through transaction checks
5. **Balance Validation:** Ensures sufficient funds before debiting

### Error Handling

- All wallet operations are wrapped in database transactions
- Rollback on errors
- Comprehensive logging via Laravel Log facade
- API call logging for external integrations

---

## Business Logic Flow Examples

### Example 1: Ecommerce Order Completion

1. Customer pays for order → Payment processed via Fincra
2. Order status → PENDING → PROCESSING → COMPLETED
3. On COMPLETED:
   - Supplier wallet credited with payout amount
   - 10MG wallet credited with commission
   - Transactions recorded for both

### Example 2: Loan Disbursement

1. Loan application approved
2. Lender's `deposit` wallet debited (capital amount)
3. Lender's `ledger` wallet credited (tracking outstanding)
4. Vendor's `credit_voucher` wallet credited (loan amount)
5. Transactions recorded for all parties

### Example 3: Loan Repayment

1. Direct debit processed from customer account
2. Lender's `investment` wallet credited (principal + lender interest)
3. Lender's `ledger` wallet debited (principal)
4. Vendor's `credit_voucher` debited (principal)
5. Vendor's `payout` wallet credited (principal)
6. 10MG wallet credited (platform interest)
7. All transactions recorded

---

## Future Enhancements

### Potential Improvements

1. **Virtual Account Creation:**
   - Implement explicit virtual account creation via Fincra API
   - Store virtual account details in database
   - Use virtual accounts for automated collections

2. **Multi-Currency Support:**
   - Extend wallet system to support multiple currencies
   - Add currency conversion logic

3. **Wallet Transfers:**
   - Enable transfers between wallets of same business type
   - Add transfer limits and approvals

4. **Advanced Reporting:**
   - Wallet balance history tracking
   - Revenue analytics per wallet type
   - Transaction trend analysis

5. **Automated Reconciliation:**
   - Daily balance reconciliation
   - Discrepancy detection and alerts
   - Automated correction workflows

---

## Notes

- All monetary values are stored as `DECIMAL` to ensure precision
- The system uses database transactions to ensure data consistency
- Wallet balances are updated atomically with transaction records
- The platform wallet (TenMgWallet) is a singleton (only one instance)
- All external API calls are logged in `api_call_logs` table for audit purposes

---

## Support & Maintenance

For issues or questions regarding the wallet system:
1. Check transaction logs in `credit_transaction_histories` or `ecommerce_transactions`
2. Review API call logs in `api_call_logs` for Fincra integration issues
3. Verify wallet balances match transaction history
4. Check webhook delivery logs for payment confirmations

---

**Last Updated:** Based on codebase analysis as of current date
**Version:** 1.0
