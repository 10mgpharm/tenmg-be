# Vendor Capabilities Documentation

This document provides a comprehensive overview of all features and capabilities available to vendors in the 10mg Backend system, from initial signup through all operational features.

## Table of Contents

1. [Authentication & Signup](#authentication--signup)
2. [Account Management](#account-management)
3. [Business Settings](#business-settings)
4. [Customer Management](#customer-management)
5. [Transaction History Management](#transaction-history-management)
6. [Loan Application Management](#loan-application-management)
7. [Loan Offer Management](#loan-offer-management)
8. [Loan Management](#loan-management)
9. [Loan Repayment Management](#loan-repayment-management)
10. [API Key Management](#api-key-management)
11. [Wallet Management](#wallet-management)
12. [Dashboard & Analytics](#dashboard--analytics)
13. [Team Management](#team-management)
14. [Audit Logs](#audit-logs)
15. [Bank Account Management](#bank-account-management)

---

## 1. Authentication & Signup

### 1.1 User Signup
**Endpoint:** `POST /v1/auth/signup`

Vendors can register for a new account by providing:
- Business name (must be unique)
- Full name
- Email address (must be unique, validated)
- Password (with confirmation)
- Business type (must be "VENDOR")
- Terms and conditions acceptance

**Process:**
1. User account is created
2. Business entity is created with status `PENDING_VERIFICATION`
3. Vendor role is assigned to the user
4. OTP is generated and sent to email for verification
5. Access token is returned for immediate use

### 1.2 Email Verification
**Endpoint:** `POST /v1/auth/verify-email`

After signup, vendors must verify their email using the OTP sent to their email address.

### 1.3 Complete Signup
**Endpoint:** `POST /v1/auth/signup/complete`

Vendors can complete their signup by providing additional business information:
- Contact person details
- Contact phone
- Contact email
- Contact person position

### 1.4 Sign In
**Endpoint:** `POST /v1/auth/signin`

Vendors can sign in using their email and password to receive an access token.

### 1.5 Google Sign In
**Endpoint:** `POST /v1/auth/google`

Vendors can sign in using Google OAuth authentication.

### 1.6 Password Reset
**Endpoints:**
- `POST /v1/auth/forgot-password` - Request password reset
- `POST /v1/auth/reset-password` - Reset password with OTP

### 1.7 Resend OTP
**Endpoint:** `POST /v1/auth/resend-otp`

Vendors can request a new OTP if the previous one expired (rate limited to 5 requests per minute).

### 1.8 Sign Out
**Endpoint:** `POST /v1/auth/signout`

Vendors can sign out, which invalidates their current access token.

---

## 2. Account Management

All authenticated vendors have access to account management features:

### 2.1 Profile Management
**Endpoints:**
- `GET /v1/account/profile` - View profile
- `POST/PATCH /v1/account/profile` - Update profile

Vendors can:
- View their profile information
- Update profile details (name, email, phone, profile picture)
- Change email (requires OTP verification)

### 2.2 Password Update
**Endpoint:** `PATCH /v1/account/password`

Vendors can update their password. This action:
- Updates the password
- Logs out all other sessions except the current one
- Logs the password change in audit logs

### 2.3 Two-Factor Authentication (2FA)
**Endpoints:**
- `GET /v1/account/2fa/setup` - Get 2FA setup information
- `POST /v1/account/2fa/toggle` - Enable/disable 2FA
- `POST /v1/account/2fa/verify` - Verify 2FA code
- `POST /v1/account/2fa/reset` - Reset 2FA

Vendors can:
- Set up two-factor authentication
- Enable or disable 2FA
- Verify 2FA codes during login
- Reset 2FA if needed

### 2.4 Notifications Management
**Endpoints:**
- `GET /v1/account/notifications` - List notifications
- `GET /v1/account/notifications/{id}` - View notification
- `PUT/PATCH /v1/account/notifications/mark-all-read` - Mark all as read
- `GET /v1/account/count-unread-notifications` - Get unread count

**App Notifications:**
- `GET /v1/account/app-notifications` - List app notifications
- `PATCH /v1/account/app-notifications/subscriptions` - Update notification subscriptions
- `PATCH /v1/account/app-notifications/{notification}/subscription` - Update specific notification subscription

Vendors can:
- View all notifications
- Mark notifications as read
- Manage notification preferences
- Subscribe/unsubscribe to specific notification types

### 2.5 Messages
**Endpoints:**
- `GET /v1/account/messages` - List messages
- `GET /v1/account/messages/{id}` - View message
- `GET /v1/account/messages/start-conversation` - Start new conversation
- `GET /v1/account/messages/unread-count` - Get unread message count
- `PUT/PATCH /v1/account/messages/mark-as-read/{message}` - Mark message as read

### 2.6 FCM Token Management
**Endpoint:** `POST /v1/account/fcm-token`

Vendors can update their Firebase Cloud Messaging token for push notifications.

### 2.7 User Permissions
**Endpoint:** `GET /v1/account/permissions`

Vendors can view their assigned permissions and roles.

---

## 3. Business Settings

### 3.1 View Business Settings
**Endpoint:** `GET /v1/vendor/settings`

Vendors can view their business information including:
- Business name and code
- Contact information
- License details
- Business status

### 3.2 Update Business Information
**Endpoint:** `POST/PATCH /v1/vendor/settings/business-information`

Vendors can update:
- Business name
- Contact person
- Contact phone
- Contact email
- Contact person position
- Business address

### 3.3 License Management
**Endpoints:**
- `GET /v1/vendor/settings/license` - View license status
- `POST/PATCH /v1/vendor/settings/license` - Upload/update license
- `PATCH /v1/vendor/settings/license/withdraw` - Withdraw license submission

Vendors can:
- Upload CAC document
- Set license number
- Set license expiry date
- View license verification status (PENDING_VERIFICATION, VERIFIED, etc.)
- Withdraw a submitted license if needed

**Note:** License upload triggers a notification to admins for review.

---

## 4. Customer Management

Vendors can manage their customers who are eligible for credit/loan services.

### 4.1 List Customers
**Endpoint:** `GET /v1/vendor/customers`

Vendors can:
- View paginated list of customers
- Filter customers by various criteria
- Search customers

**Query Parameters:**
- `perPage` - Items per page (default: 10)
- `search` - Search term
- `status` - Filter by status
- `active` - Filter by active status

### 4.2 Get All Customers
**Endpoint:** `GET /v1/vendor/customers/get-all`

Vendors can retrieve all customers without pagination (useful for dropdowns, exports, etc.).

### 4.3 Create Customer
**Endpoint:** `POST /v1/vendor/customers`

Vendors can create a new customer with:
- Customer name
- Email
- Phone number
- Address
- Other customer details
- Optional: Upload customer file/document

### 4.4 View Customer Details
**Endpoint:** `GET /v1/vendor/customers/{id}`

Vendors can view detailed information about a specific customer.

### 4.5 Update Customer
**Endpoint:** `PUT /v1/vendor/customers/{id}`

Vendors can update customer information.

### 4.6 Delete Customer
**Endpoint:** `DELETE /v1/vendor/customers/{id}`

Vendors can soft delete a customer (customer is marked as deleted but data is retained).

### 4.7 Toggle Customer Active Status
**Endpoint:** `PATCH /v1/vendor/customers/{id}`

Vendors can enable or disable a customer's active status.

### 4.8 Export Customers
**Endpoint:** `GET /v1/vendor/customers/export`

Vendors can export all customers to an Excel file (.xlsx format).

### 4.9 Import Customers
**Endpoint:** `POST /v1/vendor/customers/import`

Vendors can bulk import customers from an Excel file (.xlsx format).

**Requirements:**
- File must be in .xlsx format
- File size limit: 20MB
- Must include required headers
- Headers are validated before import

---

## 5. Transaction History Management

Vendors can upload and manage customer transaction history for credit scoring.

### 5.1 Upload Transaction History
**Endpoint:** `POST /v1/vendor/txn_history/upload`

Vendors can upload customer transaction history files to evaluate creditworthiness.

**Requirements:**
- File format: CSV, XLSX, or JSON
- File size: Max 5MB
- Minimum 6 months of transaction data required
- Must specify `customerId`

**Process:**
1. File is uploaded and stored
2. Transaction history evaluation record is created
3. File is ready for evaluation

### 5.2 View Transaction History
**Endpoint:** `POST /v1/vendor/txn_history/view`

Vendors can view the contents of an uploaded transaction history file.

**Required:**
- `transactionHistoryId` - ID of the transaction history evaluation

### 5.3 Download Transaction History
**Endpoint:** `POST /v1/vendor/txn_history/download/{txnEvaluationId}`

Vendors can download a previously uploaded transaction history file.

### 5.4 Evaluate Transaction History
**Endpoint:** `POST /v1/vendor/txn_history/evaluate`

Vendors can evaluate an uploaded transaction history to generate a credit score.

**Process:**
1. Transaction history is analyzed
2. Credit score is calculated
3. Credit score breakdown is generated
4. Results are stored and available for viewing

### 5.5 Upload and Evaluate (Combined)
**Endpoint:** `POST /v1/vendor/txn_history/upload_and_evaluate`

Vendors can upload and evaluate transaction history in a single operation.

**Process:**
1. File is uploaded
2. Transaction history is immediately evaluated
3. Credit score is generated
4. Results are returned

### 5.6 List All Transactions
**Endpoint:** `GET /v1/vendor/txn_history/get-all-txn`

Vendors can view all transaction history uploads with pagination and filtering.

### 5.7 List All Credit Scores
**Endpoint:** `GET /v1/vendor/txn_history/get-all-creditscore`

Vendors can view all credit score evaluations with pagination.

### 5.8 View Credit Score Breakdown
**Endpoint:** `GET /v1/vendor/txn_history/creditscore-breakdown/{txnEvaluationId}`

Vendors can view detailed breakdown of a credit score evaluation, including:
- Overall credit score
- Score components
- Risk factors
- Evaluation details

### 5.9 Get Customer Transaction History
**Endpoint:** `GET /v1/vendor/txn_history/{customerId}`

Vendors can view all transaction history records for a specific customer.

---

## 6. Loan Application Management

Vendors can create and manage loan applications for their customers.

### 6.1 Create Loan Application
**Endpoint:** `POST /v1/vendor/loan-applications`

Vendors can create a new loan application for a customer.

**Required Fields:**
- `customerId` - Customer ID (or `reference` for updates)
- `requestedAmount` - Loan amount requested
- `durationInMonths` - Loan duration (1-12 months)

**Optional:**
- `reference` - If provided, updates existing application instead of creating new

### 6.2 Send Application Link
**Endpoint:** `POST /v1/vendor/loan-applications/send-application-link`

Vendors can generate and send a loan application link to a customer.

**Required:**
- `customerId` - Customer ID
- `requestedAmount` - Requested loan amount

**Process:**
1. Application link is generated
2. Link can be shared with customer
3. Customer can complete application via the link

### 6.3 View All Loan Applications
**Endpoint:** `GET /v1/vendor/loan-applications`

Vendors can view all loan applications with pagination.

**Query Parameters:**
- `perPage` - Items per page
- `status` - Filter by status
- `search` - Search term
- `dateFrom` - Filter from date
- `dateTo` - Filter to date

### 6.4 Filter Loan Applications
**Endpoint:** `GET /v1/vendor/loan-applications/filter`

Vendors can filter loan applications by:
- Status (pending, approved, rejected)
- Search term
- Date range
- Business ID

### 6.5 View Loan Application Details
**Endpoint:** `GET /v1/vendor/loan-applications/view/{id}`

Vendors can view detailed information about a specific loan application.

### 6.6 Get Loan Application by Reference
**Endpoint:** `GET /v1/vendor/loan-applications/{reference}`

Vendors can retrieve a loan application using its reference identifier.

### 6.7 Get Customer Applications
**Endpoint:** `GET /v1/vendor/loan-applications/customer/{customerId}`

Vendors can view all loan applications for a specific customer.

### 6.8 Get Vendor Customizations
**Endpoint:** `GET /v1/vendor/loan-applications/customisations`

Vendors can retrieve their custom loan application settings and configurations.

### 6.9 Delete Loan Application
**Endpoint:** `DELETE /v1/vendor/loan-applications/{id}`

Vendors can delete a loan application (admin middleware required).

### 6.10 Toggle Application Active Status
**Endpoint:** `PATCH /v1/vendor/loan-applications/{id}`

Vendors can enable or disable a loan application (admin middleware required).

---

## 7. Loan Offer Management

Vendors can create and manage loan offers for approved applications.

### 7.1 Create Loan Offer
**Endpoint:** `POST /v1/vendor/offers`

Vendors can create a loan offer for an approved application.

**Required:**
- `applicationId` - Loan application ID
- `offerAmount` - Offer amount

**Process:**
1. Offer is created
2. Offer is sent to customer
3. Customer can accept or reject the offer

### 7.2 Handle Offer Action
**Endpoint:** `POST /v1/vendor/offers/{offerReference}`

Vendors can handle customer responses to loan offers.

**Required:**
- `action` - Either "accept" or "reject"

### 7.3 Get All Offers
**Endpoint:** `GET /v1/vendor/offers`

Vendors can view all loan offers with filters and pagination (admin middleware required).

### 7.4 Get Offer by ID
**Endpoint:** `GET /v1/vendor/offers/{id}`

Vendors can view details of a specific loan offer.

### 7.5 Get Offers by Customer
**Endpoint:** `GET /v1/vendor/offers/{customerId}/customer`

Vendors can view all offers for a specific customer.

### 7.6 Delete Offer
**Endpoint:** `DELETE /v1/vendor/offers/{id}`

Vendors can delete an offer (only if it's open/active, admin middleware required).

### 7.7 Toggle Offer Status
**Endpoint:** `PATCH /v1/vendor/offers/{id}`

Vendors can enable or disable a loan offer (admin middleware required).

**Required:**
- `active` - Boolean (true to activate, false to deactivate)

### 7.8 Generate Direct Debit Mandate
**Endpoint:** `POST /v1/vendor/direct-debit/mandate/generate`

Vendors can generate a direct debit mandate for a customer.

**Required:**
- `offerReference` - Loan offer reference

**Process:**
1. Mandate is generated
2. Customer must authorize the mandate
3. Mandate enables automatic loan repayments

### 7.9 Verify Direct Debit Mandate
**Endpoint:** `POST /v1/vendor/direct-debit/mandate/verify`

Vendors can verify the status of a direct debit mandate.

**Required:**
- `mandateReference` - Mandate reference identifier

---

## 8. Loan Management

Vendors can view and manage active loans.

### 8.1 Get All Loans
**Endpoint:** `GET /v1/vendor/loans`

Vendors can view all loans with pagination and filtering.

**Query Parameters:**
- `perPage` - Items per page
- `status` - Filter by loan status
- `search` - Search term

### 8.2 Get Loan by ID
**Endpoint:** `GET /v1/vendor/loans/{id}`

Vendors can view detailed information about a specific loan.

### 8.3 Mark Loan as Disbursed
**Endpoint:** `POST /v1/vendor/loans/{id}/disbursed`

Vendors can mark a loan as disbursed after funds have been released to the customer.

### 8.4 Get Loan Statistics
**Endpoint:** `GET /v1/vendor/loans/view/stats`

Vendors can view loan statistics including:
- Total loans
- Active loans
- Completed loans
- Defaulted loans
- Total loan amounts

### 8.5 Get Loan Status Count
**Endpoint:** `GET /v1/vendor/loans/stats/loan-status-count`

Vendors can view count of loans by status (e.g., Ongoing, Completed, Defaulted).

### 8.6 Repay Loan
**Endpoint:** `POST /v1/vendor/loans/repayments/{id}/repay`

Vendors can process a loan repayment for a specific repayment schedule.

**Process:**
1. Repayment is initiated
2. Payment is processed
3. Loan balance is updated

### 8.7 Liquidate Loan
**Endpoint:** `POST /v1/vendor/loans/repayments/{id}/liquidate`

Vendors can liquidate (fully pay off) a loan.

**Process:**
1. Full loan amount is calculated
2. Payment is processed
3. Loan is marked as completed/liquidated

---

## 9. Loan Repayment Management

Vendors can view and manage loan repayments.

### 9.1 Get List of Loan Repayments
**Endpoint:** `GET /v1/vendor/loan-repayment`

Vendors can view all loan repayments with pagination.

**Query Parameters:**
- `perPage` - Items per page
- `status` - Filter by repayment status
- `loanId` - Filter by loan ID
- `dateFrom` - Filter from date
- `dateTo` - Filter to date

---

## 10. API Key Management

Vendors can manage their API keys for integration purposes.

### 10.1 Get API Keys
**Endpoint:** `GET /v1/vendor/api_keys`

Vendors can view their API keys for different environments (test, live).

**Response includes:**
- API key (masked for security)
- Secret key (masked)
- Environment (test/live)
- Webhook URL
- Callback URL
- Transaction URL
- Status

### 10.2 Regenerate API Key
**Endpoint:** `POST /v1/vendor/api_keys/generate`

Vendors can regenerate their API keys.

**Required:**
- `type` - Key type
- `environment` - Environment (test or live)

**Process:**
1. Old keys are invalidated
2. New keys are generated
3. New keys are returned (displayed once)

### 10.3 Update API Key Configuration
**Endpoint:** `PATCH /v1/vendor/api_keys`

Vendors can update API key configuration URLs.

**Optional Fields:**
- `environment` - Environment (test or live)
- `webhookUrl` - Webhook URL for receiving notifications
- `callbackUrl` - Callback URL
- `transactionUrl` - Transaction URL

---

## 11. Wallet Management

Vendors have access to wallet functionality for managing funds.

### 11.1 Get Wallet Statistics
**Endpoint:** `GET /v1/vendor/wallet`

Vendors can view wallet statistics including:
- Credit voucher wallet balance
- Payout wallet balance
- Total transactions
- Transaction summary

### 11.2 Get Wallet Transactions
**Endpoint:** `GET /v1/vendor/wallet/transactions`

Vendors can view all wallet transactions with pagination.

**Transaction Types:**
- Credit voucher transactions
- Payout transactions
- Withdrawal transactions
- Loan-related transactions

### 11.3 Initialize Withdrawal
**Endpoint:** `POST /v1/vendor/withdrawals/init`

Vendors can initialize a withdrawal request.

**Required:**
- `amount` - Withdrawal amount
- `bankAccountId` - Bank account ID

**Process:**
1. Withdrawal request is created
2. OTP is sent to vendor's email
3. Vendor must verify OTP to complete withdrawal

### 11.4 Withdraw Funds
**Endpoint:** `POST /v1/vendor/withdrawals/withdraw-funds`

Vendors can complete a withdrawal after OTP verification.

**Required:**
- `reference` - Withdrawal transaction reference
- `otp` - OTP code from email

**Process:**
1. OTP is verified
2. Withdrawal is processed
3. Funds are transferred to bank account
4. Transaction is recorded

### 11.5 Bank Account Management
**Endpoints:**
- `GET /v1/vendor/wallet/bank-account` - Get bank account
- `POST /v1/vendor/wallet/add-bank-account` - Add bank account
- `PATCH /v1/vendor/wallet/add-bank-account/{bank_account}` - Update bank account

Vendors can:
- View their registered bank account
- Add a new bank account (requires OTP verification)
- Update existing bank account details

**Bank Account Fields:**
- Bank name
- Bank code
- Account name
- Account number
- BVN (Bank Verification Number)

---

## 12. Dashboard & Analytics

Vendors have access to a comprehensive dashboard with analytics.

### 12.1 Get Dashboard Statistics
**Endpoint:** `GET /v1/vendor/dashboard`

Vendors can view dashboard statistics including:
- Total customers
- Total loan applications
- Pending applications count
- Ongoing applications count
- Credit voucher wallet balance
- Payout wallet balance
- Transaction evaluation count
- API call statistics (successful/errors)
- Account linking statistics

### 12.2 Get Graph Statistics
**Endpoint:** `GET /v1/vendor/dashboard/graph-stats`

Vendors can view graph/chart statistics for visualization.

**Returns:**
- Monthly loan statistics
- Loan status breakdown by month
- Ongoing loans by month
- Completed loans by month

**Data Format:**
- Monthly data for current year
- Formatted for chart visualization
- Includes month names and counts

---

## 13. Team Management

Vendors can manage team members and invite users to their business.

### 13.1 List Team Members
**Endpoint:** `GET /v1/vendor/settings/invite/team-members`

Vendors can view all team members associated with their business.

**Response includes:**
- User details
- Role assignments
- Status (active/inactive)
- Join date

### 13.2 List Invites
**Endpoint:** `GET /v1/vendor/settings/invite`

Vendors can view all pending and accepted invites.

### 13.3 Create Invite
**Endpoint:** `POST /v1/vendor/settings/invite`

Vendors can invite new team members to their business.

**Required:**
- Email address
- Role assignment
- Optional: Custom message

**Process:**
1. Invite is created
2. Invitation email is sent
3. Invitee can accept or reject the invitation

### 13.4 View Invite (Public)
**Endpoint:** `GET /v1/auth/invite/view`

Invitees can view invitation details using invite token (public endpoint).

### 13.5 Accept Invite
**Endpoint:** `POST /v1/auth/invite/accept`

Invitees can accept an invitation and create their account.

**Required:**
- Invite ID
- Full name
- Password
- Password confirmation

**Process:**
1. User account is created
2. User is assigned to business
3. Role is assigned
4. Access token is returned

### 13.6 Delete Invite
**Endpoint:** `DELETE /v1/vendor/settings/invite/{invite}`

Vendors can delete/cancel a pending invitation.

### 13.7 Manage Users
**Endpoints:**
- `GET /v1/vendor/settings/users` - List users
- `GET /v1/vendor/settings/users/{user}` - View user
- `POST /v1/vendor/settings/users` - Create user
- `PUT/PATCH /v1/vendor/settings/users/{user}` - Update user
- `DELETE /v1/vendor/settings/users/{user}` - Delete user
- `PATCH /v1/vendor/settings/users/{user}/status` - Update user status

Vendors can:
- View all users in their business
- Create new users
- Update user details
- Delete users
- Activate/deactivate users

**User Management Features:**
- Filter by role
- Filter by email
- Filter by active status
- Search functionality
- Pagination support

---

## 14. Audit Logs

Vendors can view audit logs of activities within their business.

### 14.1 Get Audit Logs
**Endpoint:** `GET /v1/vendor/audit-logs`

Vendors can view audit logs with pagination.

**Query Parameters:**
- `perPage` - Items per page (default: 20)
- `search` - Search term
- `event` - Filter by event type
- `action` - Filter by action
- `crudType` - Filter by CRUD type (CREATE, READ, UPDATE, DELETE)
- `ip` - Filter by IP address
- `fromDate` - Filter from date
- `toDate` - Filter to date
- `sort` - Sort column
- `order` - Sort order (asc/desc)

**Log Information:**
- Event type
- Action performed
- User who performed action
- Timestamp
- IP address
- CRUD type
- Additional properties

### 14.2 Search Audit Logs
**Endpoint:** `GET /v1/vendor/audit-logs/search`

Vendors can search audit logs with advanced filtering (same parameters as above).

### 14.3 API Logs
**Endpoints:**
- `GET /v1/vendor/api-logs` - Get API call logs
- `GET /v1/vendor/api-logs/webhook` - Get webhook logs

Vendors can view:
- API call logs (successful/failed)
- Webhook logs
- API request/response details
- Error logs
- Integration logs

**Use Cases:**
- Debugging API integrations
- Monitoring API usage
- Tracking webhook deliveries
- Troubleshooting integration issues

---

## 15. Bank Account Management

Vendors can manage bank accounts for withdrawals and payouts.

### 15.1 Get Bank List
**Endpoint:** `GET /v1/bank/list`

Vendors can retrieve a list of all available banks.

**Response includes:**
- Bank name
- Bank code
- Bank identifier

### 15.2 Verify Bank Account
**Endpoint:** `POST /v1/bank/verify-account`

Vendors can verify bank account details before adding.

**Required:**
- Account number
- Bank code

**Response:**
- Account name
- Account number
- Bank name
- Verification status

### 15.3 Add Bank Account
**Endpoint:** `POST /v1/vendor/wallet/add-bank-account`

Vendors can add a bank account for withdrawals.

**Required:**
- Bank name
- Bank code
- Account name
- Account number
- BVN (Bank Verification Number)
- OTP (sent to email)

**Process:**
1. OTP is requested and sent to email
2. Vendor provides OTP
3. Bank account is verified
4. Bank account is added to vendor's profile

### 15.4 Update Bank Account
**Endpoint:** `PATCH /v1/vendor/wallet/add-bank-account/{bank_account}`

Vendors can update existing bank account details.

### 15.5 Get Bank Account
**Endpoint:** `GET /v1/vendor/wallet/bank-account`

Vendors can view their registered bank account details.

---

## Additional Features

### Integration Endpoint
**Endpoint:** `POST /v1/integration/vendor/ecommerce-transactions`

Vendors can integrate their ecommerce transactions with the credit system through a dedicated integration endpoint (requires integration middleware).

### Job Management (General Feature)
Vendors can also access general job features:
- `GET /v1/jobs` - View available jobs
- `GET /v1/jobs/{job}` - View job details
- `POST /v1/jobs/applications` - Apply for jobs

---

## Security & Authentication

All vendor endpoints (except public auth endpoints) require:
- **Authentication:** Valid access token in Authorization header
- **Role Check:** User must have "vendor" role
- **Scope:** Full access scope required

**Token Format:**
```
Authorization: Bearer {access_token}
```

---

## Error Handling

The API uses standard HTTP status codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Rate Limiting

Some endpoints have rate limiting:
- OTP resend: 5 requests per minute
- Other endpoints may have rate limits as configured

---

## Notes

1. **Business Status:** Vendors start with `PENDING_VERIFICATION` status and must complete verification to access all features.

2. **License Verification:** License uploads are reviewed by admins. Status can be: PENDING_VERIFICATION, VERIFIED, SUSPENDED, BANNED.

3. **Wallet Types:** Vendors have two wallet types:
   - **Credit Voucher Wallet:** Tracks total credit given to customers
   - **Payout Wallet:** Tracks funds available for withdrawal

4. **API Integration:** Vendors can integrate their systems using API keys for automated loan application processing.

5. **Transaction History:** Minimum 6 months of transaction data is required for credit scoring.

6. **Loan Durations:** Loan durations are limited to 1-12 months.

7. **Team Management:** Only business owners can manage team members and invites.

---

## Support

For additional support or questions about vendor capabilities, please contact the system administrators.

---

**Last Updated:** Generated from codebase analysis
**Version:** 1.0

