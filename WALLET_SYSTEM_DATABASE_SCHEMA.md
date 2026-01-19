# Wallet System - Complete Database Schema Documentation

## Overview

This document provides a comprehensive overview of all database tables involved in the wallet system, including their fields, data types, relationships, and constraints.

---

## Table of Contents

1. [Wallet Tables](#wallet-tables)
2. [Transaction Tables](#transaction-tables)
3. [Bank Account Tables](#bank-account-tables)
4. [Payment Tables](#payment-tables)
5. [Mandate Tables](#mandate-tables)
6. [Related Tables](#related-tables)
7. [Table Relationships Diagram](#table-relationships-diagram)

---

## Wallet Tables

### 1. `ecommerce_wallets`

**Purpose:** Stores wallet information for suppliers in the ecommerce marketplace.

**Model:** `App\Models\EcommerceWallet`

**Migration:** `2024_09_10_205949_create_ecommerce_wallets_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique wallet identifier |
| `business_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, NULLABLE, ON DELETE SET NULL | Reference to supplier business |
| `previous_balance` | DECIMAL(18,2) | NOT NULL | Wallet balance before last transaction |
| `current_balance` | DECIMAL(18,2) | NOT NULL | Current wallet balance |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Relationships:**
- `belongsTo` Business (via `business_id`)
- `hasOne` EcommerceBankAccount (via `supplier_id` = `business_id`)
- `morphMany` EcommerceTransaction (polymorphic)

**Indexes:**
- Primary key on `id`
- Foreign key index on `business_id`

---

### 2. `credit_vendor_wallets`

**Purpose:** Stores wallet information for vendors offering credit services. Each vendor has two wallet types.

**Model:** `App\Models\CreditVendorWallets`

**Migration:** `2025_02_08_235818_create_credit_vendor_wallets.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique wallet identifier |
| `vendor_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, ON DELETE CASCADE | Reference to vendor business |
| `type` | ENUM | NOT NULL | Wallet type: `'payout'` or `'credit_voucher'` |
| `current_balance` | DECIMAL(18,2) | DEFAULT 0 | Current wallet balance |
| `prev_balance` | DECIMAL(18,2) | DEFAULT 0 | Previous wallet balance |
| `last_transaction_ref` | VARCHAR(255) | NULLABLE | Reference of last transaction |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Wallet Types:**
- **`payout`**: Wallet where vendors receive installment payments for loans their customers took
- **`credit_voucher`**: Wallet representing total amount given out as credit to vendor customers

**Relationships:**
- `belongsTo` Business (via `vendor_id`)

**Indexes:**
- Primary key on `id`
- Foreign key index on `vendor_id`
- Unique constraint on (`vendor_id`, `type`)

---

### 3. `credit_lenders_wallets`

**Purpose:** Stores wallet information for lenders providing capital. Each lender has three wallet types.

**Model:** `App\Models\CreditLendersWallet`

**Migration:** `2025_01_31_081849_create_credit_lenders_wallets_table.php`  
**Update Migration:** `2025_04_01_132120_add_enum_ledger_to_credit_lenders_wallets_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique wallet identifier |
| `lender_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, ON DELETE CASCADE | Reference to lender business |
| `type` | ENUM | NOT NULL | Wallet type: `'investment'`, `'deposit'`, or `'ledger'` |
| `current_balance` | DECIMAL(10,2) | DEFAULT 0 | Current wallet balance |
| `prev_balance` | DECIMAL(10,2) | DEFAULT 0 | Previous wallet balance |
| `last_transaction_ref` | VARCHAR(255) | NULLABLE | Reference of last transaction |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Wallet Types:**
- **`investment`**: Wallet for lender's investment funds (earns interest from loans)
- **`deposit`**: Wallet for lender's deposited capital (used to fund loans)
- **`ledger`**: Wallet tracking total outstanding loan amounts

**Relationships:**
- `belongsTo` Business (via `lender_id`)
- `hasMany` CreditTransactionHistory (via `wallet_id`)

**Indexes:**
- Primary key on `id`
- Foreign key index on `lender_id`
- Unique constraint on (`lender_id`, `type`)

---

### 4. `ten_mg_wallets`

**Purpose:** Stores the platform's main wallet for commissions and interest earnings.

**Model:** `App\Models\TenMgWallet`

**Migration:** `2025_04_07_092714_create_ten_mg_wallets_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique wallet identifier |
| `previous_balance` | DECIMAL(18,2) | NOT NULL | Wallet balance before last transaction |
| `current_balance` | DECIMAL(18,2) | NOT NULL | Current wallet balance |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Relationships:**
- `morphMany` EcommerceTransaction (polymorphic)

**Indexes:**
- Primary key on `id`

**Note:** This is typically a singleton table (only one record expected).

---

## Transaction Tables

### 5. `ecommerce_transactions`

**Purpose:** Records all transactions for supplier wallets and platform wallet (polymorphic relationship).

**Model:** `App\Models\EcommerceTransaction`

**Migration:** `2024_09_10_210036_create_ecommerce_transactions_table.php`  
**Update Migrations:**
- `2025_04_24_202203_use_morph_in_ecommerce_transactions_table_for_wallet.php` (converted to polymorphic)
- `2025_05_01_092137_add_column_ecommerce_order_detail_id_to_ecommerce_transactions.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique transaction identifier |
| `walletable_id` | BIGINT UNSIGNED | NULLABLE | Polymorphic: wallet ID (ecommerce_wallets.id or ten_mg_wallets.id) |
| `walletable_type` | VARCHAR(255) | NULLABLE | Polymorphic: wallet model class |
| `supplier_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, NULLABLE, ON DELETE SET NULL | Reference to supplier business |
| `ecommerce_order_id` | BIGINT UNSIGNED | FOREIGN KEY → `ecommerce_orders.id`, NULLABLE, ON DELETE SET NULL | Reference to related order |
| `ecommerce_order_detail_id` | BIGINT UNSIGNED | FOREIGN KEY → `ecommerce_order_details.id`, NULLABLE, ON DELETE SET NULL | Reference to order detail |
| `txn_type` | ENUM | NOT NULL | Transaction type: `'CREDIT'` or `'DEBIT'` |
| `txn_group` | VARCHAR(255) | NOT NULL | Transaction group/category (e.g., 'ORDER_PAYMENT', 'REFUND', 'WITHDRAWAL', 'PAYOUT') |
| `amount` | DECIMAL(18,2) | NOT NULL | Transaction amount |
| `balance_before` | DECIMAL(18,2) | NOT NULL | Wallet balance before transaction |
| `balance_after` | DECIMAL(18,2) | NOT NULL | Wallet balance after transaction |
| `status` | ENUM | NOT NULL | Transaction status: `'HOLD'`, `'CREDIT'`, or `'DEBIT'` |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Transaction Groups:**
- `ORDER_PAYMENT` - Payment from completed order (supplier)
- `REFUND` - Refund transaction
- `WITHDRAWAL` - Withdrawal from wallet
- `PAYOUT` - Payout to supplier
- `TENMG_ORDER_PAYMENT` - Platform commission from order
- `TENMG_ORDER_CANCELLATION` - Commission reversal from cancelled order

**Relationships:**
- `morphTo` walletable (EcommerceWallet or TenMgWallet)
- `belongsTo` Business (via `supplier_id`)
- `belongsTo` EcommerceOrder (via `ecommerce_order_id`)
- `belongsTo` EcommerceOrderDetail (via `ecommerce_order_detail_id`)

**Indexes:**
- Primary key on `id`
- Foreign key indexes on `supplier_id`, `ecommerce_order_id`, `ecommerce_order_detail_id`
- Polymorphic index on (`walletable_id`, `walletable_type`)

---

### 6. `credit_transaction_histories`

**Purpose:** Records all transactions for vendor and lender wallets.

**Model:** `App\Models\CreditTransactionHistory`

**Migration:** `2025_03_20_055516_create_credit_transaction_histories_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique transaction identifier |
| `business_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, ON DELETE CASCADE | Reference to business (vendor or lender) |
| `identifier` | VARCHAR(255) | UNIQUE, NOT NULL | Auto-generated unique reference (THG prefix) |
| `amount` | DECIMAL(10,2) | NOT NULL | Transaction amount |
| `type` | ENUM | NULLABLE | Transaction type: `'CREDIT'` or `'DEBIT'` |
| `transaction_group` | VARCHAR(255) | NOT NULL | Transaction category/group |
| `description` | VARCHAR(255) | NOT NULL | Transaction description |
| `status` | VARCHAR(255) | NOT NULL | Transaction status (pending, success, failed, etc.) |
| `payment_method` | VARCHAR(255) | NULLABLE | Payment method (e.g., 'fincra') |
| `reference` | VARCHAR(255) | NULLABLE | External payment reference |
| `wallet_id` | BIGINT UNSIGNED | FOREIGN KEY → `credit_lenders_wallets.id`, NULLABLE, ON DELETE SET NULL | Reference to lender wallet (if applicable) |
| `loan_application_id` | BIGINT UNSIGNED | FOREIGN KEY → `credit_applications.id`, NULLABLE, ON DELETE SET NULL | Reference to loan application (if applicable) |
| `meta` | JSON | NULLABLE | Stores response from payment gateway |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Transaction Groups:**
- `deposit` - Lender deposit to wallet
- `withdrawal` - Withdrawal from wallet
- `loan_disbursement` - Loan amount disbursed
- `repayment` - Loan repayment received
- `repayment_interest` - Interest from repayment
- `payout` - Vendor payout from credit voucher

**Status Values:**
- `pending` - Transaction pending
- `pending_verification` - Pending OTP verification
- `success` - Transaction successful
- `failed` - Transaction failed
- `cancelled` - Transaction cancelled
- `initiated` - Transaction initiated

**Relationships:**
- `belongsTo` Business (via `business_id`)
- `belongsTo` CreditLendersWallet (via `wallet_id`)
- `belongsTo` LoanApplication (via `loan_application_id`)

**Indexes:**
- Primary key on `id`
- Unique index on `identifier`
- Foreign key indexes on `business_id`, `wallet_id`, `loan_application_id`

**Auto-generation:** The `identifier` field is automatically generated using `UtilityHelper::generateSlug('THG')` on model creation.

---

### 7. `tenmg_transaction_histories`

**Purpose:** Records transaction history for the platform wallet (10MG).

**Model:** `App\Models\TenmgTransactionHistory`

**Migration:** `2025_05_09_093742_create_tenmg_transaction_histories_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique transaction identifier |
| `identifier` | VARCHAR(255) | NOT NULL | Unique transaction reference |
| `amount` | DECIMAL(15,2) | NOT NULL | Transaction amount |
| `type` | ENUM | NOT NULL | Transaction type: `'CREDIT'` or `'DEBIT'` |
| `transaction_group` | VARCHAR(255) | NOT NULL | Transaction category/group |
| `description` | VARCHAR(255) | NOT NULL | Transaction description |
| `status` | VARCHAR(255) | NOT NULL | Transaction status |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Transaction Groups:**
- `loan_interest` - Platform interest from loan repayments
- `order_commission` - Commission from ecommerce orders

**Relationships:**
- None (standalone table)

**Indexes:**
- Primary key on `id`

---

## Bank Account Tables

### 8. `ecommerce_bank_accounts`

**Purpose:** Stores bank account details for suppliers to receive payouts from their ecommerce wallet.

**Model:** `App\Models\EcommerceBankAccount`

**Migration:** `2024_09_10_210005_create_ecommerce_bank_accounts_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique bank account identifier |
| `supplier_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, ON DELETE CASCADE | Reference to supplier business |
| `bank_name` | VARCHAR(255) | NOT NULL | Name of the bank |
| `bank_code` | VARCHAR(255) | NOT NULL | Bank code (e.g., '044' for Access Bank) |
| `account_name` | VARCHAR(255) | NOT NULL | Account holder name |
| `account_number` | VARCHAR(255) | NOT NULL, INDEXED | Bank account number |
| `active` | BOOLEAN | DEFAULT true | Whether account is active |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Relationships:**
- `belongsTo` Business (via `supplier_id`)
- `hasOne` EcommerceWallet (via `supplier_id` = `business_id`)

**Indexes:**
- Primary key on `id`
- Foreign key index on `supplier_id`
- Index on `account_number`

---

### 9. `credit_lender_bank_accounts`

**Purpose:** Stores bank account details for lenders to receive investment returns and process withdrawals.

**Model:** `App\Models\CreditLenderBankAccounts`

**Migration:** `2025_02_12_135510_create_credit_lender_bank_accounts_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique bank account identifier |
| `lender_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, ON DELETE CASCADE | Reference to lender business |
| `bank_name` | VARCHAR(255) | NOT NULL | Name of the bank |
| `bank_code` | VARCHAR(255) | NOT NULL | Bank code |
| `account_name` | VARCHAR(255) | NOT NULL | Account holder name |
| `account_number` | VARCHAR(255) | NOT NULL, INDEXED | Bank account number |
| `active` | BOOLEAN | DEFAULT true | Whether account is active |
| `bvn` | VARCHAR(255) | NOT NULL | Bank Verification Number |
| `is_bvn_verified` | BOOLEAN | DEFAULT false | Whether BVN is verified |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Relationships:**
- `belongsTo` Business (via `lender_id`)

**Indexes:**
- Primary key on `id`
- Foreign key index on `lender_id`
- Index on `account_number`

---

## Payment Tables

### 10. `ecommerce_payments`

**Purpose:** Records payment information for ecommerce orders.

**Model:** `App\Models\EcommercePayment`

**Migrations:**
- `2024_09_10_210124_create_ecommerce_payments_table.php` (base table)
- `2025_01_15_151721_add_columns_to_table_ecommerce_payments.php` (adds main columns)
- `2025_01_27_174128_add_order_id_to_ecommerce_payments_table.php` (adds order_id)
- `2025_05_09_075406_add_missing_columns_to_ecommerce_payments_table.php` (adds wallet-related columns)

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique payment identifier |
| `status` | ENUM | NOT NULL | Payment status: `'initiated'`, `'success'`, `'pending'`, `'failed'`, `'abandoned'` |
| `reference` | VARCHAR(255) | UNIQUE, NOT NULL | Payment reference (PAY prefix) |
| `external_reference` | VARCHAR(255) | NULLABLE | External payment gateway reference |
| `customer_id` | BIGINT UNSIGNED | FOREIGN KEY → `users.id`, ON DELETE CASCADE | Reference to customer user |
| `order_id` | BIGINT UNSIGNED | FOREIGN KEY → `users.id`, ON DELETE CASCADE | Reference to order (note: foreign key points to users, likely should be ecommerce_orders) |
| `amount` | DECIMAL(18,2) | NOT NULL | Payment amount |
| `fee` | DECIMAL(18,2) | DEFAULT 0 | Transaction fee |
| `total_amount` | DECIMAL(18,2) | NOT NULL | Total amount (amount + fee) |
| `comment` | TEXT | NULLABLE | Payment comment/notes |
| `paid_at` | TIMESTAMP | NULLABLE | Payment completion timestamp |
| `currency` | VARCHAR(255) | NOT NULL | Currency code (e.g., 'NGN') |
| `channel` | VARCHAR(255) | NULLABLE | Payment channel (e.g., 'fincra', 'tenmg_credit') |
| `meta` | JSON | NULLABLE | Payment metadata (gateway response) |
| `wallet_id` | BIGINT UNSIGNED | NULLABLE | Wallet ID (if applicable) |
| `wallet_type` | VARCHAR(255) | NULLABLE | Wallet type (if applicable) |
| `business_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, NULLABLE, ON DELETE SET NULL | Reference to business |
| `ecommerce_transaction_id` | BIGINT UNSIGNED | FOREIGN KEY → `ecommerce_transactions.id`, NULLABLE, ON DELETE SET NULL | Reference to related transaction |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Relationships:**
- `belongsTo` User (via `customer_id`)
- `belongsTo` Business (via `business_id`)
- `belongsTo` EcommerceTransaction (via `ecommerce_transaction_id`)

**Indexes:**
- Primary key on `id`
- Unique index on `reference`
- Foreign key indexes on `customer_id`, `order_id`, `business_id`, `ecommerce_transaction_id`

---

### 11. `credit_repayment_payments`

**Purpose:** Records payment information for loan repayments.

**Model:** `App\Models\CreditRepaymentPayments`

**Migration:** `2025_04_22_111443_create_repayment_payments_table.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique payment identifier |
| `status` | ENUM | DEFAULT 'initiated' | Payment status: `'initiated'`, `'success'`, `'pending'`, `'failed'`, `'abandoned'` |
| `reference` | VARCHAR(255) | NULLABLE | Payment reference (LNR prefix) |
| `external_reference` | VARCHAR(255) | NULLABLE | External payment gateway reference |
| `loan_id` | INTEGER | FOREIGN KEY → `credit_loans.id`, ON DELETE CASCADE | Reference to loan |
| `amount` | DECIMAL(10,2) | NOT NULL | Payment amount |
| `fee` | DECIMAL(10,2) | NULLABLE | Transaction fee |
| `total_amount` | DECIMAL(10,2) | NULLABLE | Total amount (amount + fee) |
| `channel` | VARCHAR(255) | NULLABLE | Payment channel |
| `currency` | VARCHAR(255) | NULLABLE | Currency code |
| `paid_at` | DATE | NULLABLE | Payment completion date |
| `comment` | VARCHAR(255) | NULLABLE | Payment comment |
| `business_id` | INTEGER | FOREIGN KEY → `businesses.id`, ON DELETE SET NULL | Reference to business |
| `customer_id` | INTEGER | FOREIGN KEY → `credit_customers.id`, ON DELETE SET NULL | Reference to customer |
| `meta` | JSON | NULLABLE | Payment metadata |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Relationships:**
- `belongsTo` Loan (via `loan_id`)
- `belongsTo` Business (via `business_id`)
- `belongsTo` Customer (via `customer_id`)

**Indexes:**
- Primary key on `id`
- Foreign key indexes on `loan_id`, `business_id`, `customer_id`

---

## Mandate Tables

### 12. `credit_fincra_debit_mandates`

**Purpose:** Stores direct debit mandate information for loan repayments via Fincra.

**Model:** `App\Models\DebitMandate`

**Migration:** `2025_02_14_184312_create_credit_fincra_mandate.php`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique mandate identifier |
| `business_id` | BIGINT UNSIGNED | FOREIGN KEY → `businesses.id`, NULLABLE, ON DELETE CASCADE | Reference to vendor business |
| `customer_id` | BIGINT UNSIGNED | FOREIGN KEY → `credit_customers.id`, NULLABLE, ON DELETE CASCADE | Reference to customer |
| `application_id` | BIGINT UNSIGNED | FOREIGN KEY → `credit_applications.id`, NULLABLE, ON DELETE CASCADE | Reference to loan application |
| `amount` | DECIMAL(18,2) | NOT NULL | Amount to be deducted per period |
| `description` | VARCHAR(255) | NOT NULL | System-provided description |
| `response_description` | TEXT | NULLABLE | NIBSS/Fincra provided description |
| `start_date` | TIMESTAMP | NULLABLE | Mandate start date |
| `end_date` | TIMESTAMP | NULLABLE | Mandate end date |
| `customer_account_number` | VARCHAR(255) | NULLABLE | Customer bank account number |
| `customer_account_name` | VARCHAR(255) | NULLABLE | Customer account name |
| `customer_bank_code` | VARCHAR(255) | NULLABLE | Customer bank code |
| `customer_address` | VARCHAR(255) | NULLABLE | Customer address |
| `customer_email` | VARCHAR(255) | NULLABLE | Customer email |
| `customer_phone` | VARCHAR(255) | NULLABLE | Customer phone |
| `identifier` | VARCHAR(255) | NULLABLE | Internal mandate identifier |
| `reference` | VARCHAR(255) | NULLABLE | Fincra mandate reference |
| `status` | ENUM | DEFAULT 'initiated' | Mandate status: `'initiated'`, `'approved'`, `'completed'`, `'failed'`, `'pending'` |
| `currency` | VARCHAR(255) | DEFAULT 'NGN' | Currency code |
| `response` | JSON | NULLABLE | Fincra API response |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

**Relationships:**
- `belongsTo` Business (via `business_id`)
- `belongsTo` Customer (via `customer_id`)
- `belongsTo` LoanApplication (via `application_id`)

**Indexes:**
- Primary key on `id`
- Foreign key indexes on `business_id`, `customer_id`, `application_id`

---

## Related Tables

### 13. `businesses`

**Purpose:** Core business table that all wallets are linked to.

**Key Fields (Wallet-related):**
- `id` - Primary key (referenced by all wallet tables)
- `type` - Business type (SUPPLIER, VENDOR, LENDER, ADMIN)
- `owner_id` - Reference to user who owns the business

**Wallet Relationships:**
- `hasOne` EcommerceWallet (for SUPPLIER type)
- `hasMany` CreditVendorWallets (for VENDOR type)
- `hasMany` CreditLendersWallet (for LENDER type)

---

### 14. `ecommerce_orders`

**Purpose:** Ecommerce orders that trigger wallet transactions.

**Key Fields:**
- `id` - Primary key
- `status` - Order status (CART, PENDING, PROCESSING, COMPLETED, CANCELED)
- `payment_status` - Payment status
- `grand_total` - Total order amount
- `customer_id` - Reference to customer

**Relationships:**
- `hasMany` EcommerceTransaction (via `ecommerce_order_id`)

---

### 15. `ecommerce_order_details`

**Purpose:** Individual items in an order that determine supplier payouts.

**Key Fields:**
- `id` - Primary key
- `ecommerce_order_id` - Reference to order
- `supplier_id` - Reference to supplier business
- `actual_price` - Item price
- `discount_price` - Discounted price (if applicable)
- `tenmg_commission` - Platform commission amount
- `quantity` - Item quantity

**Relationships:**
- `hasMany` EcommerceTransaction (via `ecommerce_order_detail_id`)

---

### 16. `credit_applications` / `credit_loans`

**Purpose:** Loan applications and loans that trigger credit wallet transactions.

**Key Fields:**
- `id` - Primary key
- `business_id` - Reference to vendor business
- `customer_id` - Reference to customer
- `requested_amount` - Loan amount requested
- `status` - Application/loan status

**Relationships:**
- `hasMany` CreditTransactionHistory (via `loan_application_id`)

---

## Table Relationships Diagram

```
businesses
├── ecommerce_wallets (SUPPLIER type)
│   ├── ecommerce_bank_accounts
│   └── ecommerce_transactions (polymorphic)
│
├── credit_vendor_wallets (VENDOR type)
│   └── credit_transaction_histories
│
├── credit_lenders_wallets (LENDER type)
│   ├── credit_lender_bank_accounts
│   └── credit_transaction_histories
│
└── (ADMIN type)
    └── ten_mg_wallets
        ├── ecommerce_transactions (polymorphic)
        └── tenmg_transaction_histories

ecommerce_orders
├── ecommerce_payments
└── ecommerce_order_details
    └── ecommerce_transactions

credit_applications
├── credit_fincra_debit_mandates
├── credit_loans
│   └── credit_repayment_payments
└── credit_transaction_histories
```

---

## Data Flow Examples

### Example 1: Ecommerce Order Payment Flow

1. **ecommerce_payments** - Payment record created
2. **ecommerce_orders** - Order status updated
3. **ecommerce_transactions** - Transaction recorded (when order completes)
4. **ecommerce_wallets** - Supplier wallet balance updated
5. **ten_mg_wallets** - Platform wallet balance updated (commission)

### Example 2: Loan Disbursement Flow

1. **credit_applications** - Application approved
2. **credit_fincra_debit_mandates** - Mandate created
3. **credit_lenders_wallets** (deposit) - Debited
4. **credit_lenders_wallets** (ledger) - Credited
5. **credit_vendor_wallets** (credit_voucher) - Credited
6. **credit_transaction_histories** - Transactions recorded

### Example 3: Loan Repayment Flow

1. **credit_fincra_debit_mandates** - Debit processed
2. **credit_repayment_payments** - Payment record created
3. **credit_lenders_wallets** (investment) - Credited (principal + interest)
4. **credit_lenders_wallets** (ledger) - Debited (principal)
5. **credit_vendor_wallets** (credit_voucher) - Debited (principal)
6. **credit_vendor_wallets** (payout) - Credited (principal)
7. **ten_mg_wallets** - Credited (platform interest)
8. **credit_transaction_histories** - Multiple transactions recorded

---

## Important Notes

1. **Decimal Precision:**
   - Ecommerce wallets: DECIMAL(18,2) - High precision for large amounts
   - Credit wallets: DECIMAL(10,2) or DECIMAL(18,2) - Varies by table
   - Platform wallet: DECIMAL(18,2) - High precision

2. **Polymorphic Relationships:**
   - `ecommerce_transactions` uses polymorphic relationship (`walletable_id`, `walletable_type`) to support both `EcommerceWallet` and `TenMgWallet`

3. **Transaction References:**
   - Ecommerce payments: `PAY` prefix
   - Credit transactions: `THG` prefix
   - Loan repayments: `LNR` prefix
   - Loan disbursements: `LND` prefix

4. **Cascade Deletes:**
   - Most wallet tables use `ON DELETE CASCADE` to maintain referential integrity
   - Transaction tables use `ON DELETE SET NULL` to preserve transaction history

5. **Status Enums:**
   - Payment statuses: `initiated`, `success`, `pending`, `failed`, `abandoned`
   - Mandate statuses: `initiated`, `approved`, `completed`, `failed`, `pending`
   - Transaction statuses: Varies by table

---

## Indexes Summary

### Primary Indexes
- All tables have `id` as PRIMARY KEY

### Foreign Key Indexes
- All foreign key columns are automatically indexed

### Unique Indexes
- `ecommerce_payments.reference` - UNIQUE
- `credit_transaction_histories.identifier` - UNIQUE
- `credit_vendor_wallets` - UNIQUE on (`vendor_id`, `type`)
- `credit_lenders_wallets` - UNIQUE on (`lender_id`, `type`)

### Performance Indexes
- `ecommerce_bank_accounts.account_number` - INDEXED
- `credit_lender_bank_accounts.account_number` - INDEXED

---

**Last Updated:** Based on codebase analysis  
**Version:** 1.0
