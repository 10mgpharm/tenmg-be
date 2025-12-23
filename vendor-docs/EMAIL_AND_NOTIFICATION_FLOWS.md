# Email and Notification Flows Documentation

This comprehensive document details all email and notification (web push) flows in the 10mg Backend system, including where emails/notifications are sent and where they are NOT sent.

## Table of Contents

1. [Authentication & Account Management](#authentication--account-management)
2. [Business License Management](#business-license-management)
3. [Team Management & Invitations](#team-management--invitations)
4. [Loan Application Flow](#loan-application-flow)
5. [Loan Offer Management](#loan-offer-management)
6. [Loan Repayment](#loan-repayment)
7. [E-commerce Orders](#e-commerce-orders)
8. [Bank Account Management](#bank-account-management)
9. [Wallet & Withdrawals](#wallet--withdrawals)
10. [User Management](#user-management)
11. [Messages](#messages)
12. [Job Applications](#job-applications)
13. [Missing Notifications](#missing-notifications)

---

## 1. Authentication & Account Management

### 1.1 User Signup

**Email Sent:** ✅ YES
- **When:** Immediately after user signup
- **Type:** OTP Email Verification
- **Recipient:** New user
- **Method:** `OtpService->sendMail(OtpType::SIGNUP_EMAIL_VERIFICATION)`
- **Notification Class:** `VerifyEmailNotification`
- **Location:** `app/Services/AuthService.php:146-148`
- **Content:** OTP code for email verification

**In-App Notification:** ❌ NO
- **Status:** Not sent during signup

**Push Notification:** ❌ NO
- **Status:** Not sent during signup

---

### 1.2 Email Verification

**Email Sent:** ✅ YES (Already sent during signup)
- **When:** OTP is sent during signup
- **Type:** OTP Email Verification
- **Recipient:** User verifying email
- **Method:** `User->sendEmailVerification($code)`
- **Notification Class:** `VerifyEmailNotification`
- **Location:** `app/Models/User.php:169-172`

**In-App Notification:** ❌ NO
- **Status:** Not sent after email verification

**Push Notification:** ❌ NO
- **Status:** Not sent after email verification

---

### 1.3 Password Reset Request

**Email Sent:** ✅ YES
- **When:** User requests password reset
- **Type:** OTP for Password Reset
- **Recipient:** User requesting reset
- **Method:** `OtpService->sendMail(OtpType::RESET_PASSWORD_VERIFICATION)`
- **Notification Class:** `ResetPasswordNotification`
- **Location:** `app/Http/Controllers/API/Auth/PasswordController.php:42`
- **Content:** OTP code for password reset

**In-App Notification:** ❌ NO
- **Status:** Not sent for password reset

**Push Notification:** ❌ NO
- **Status:** Not sent for password reset

---

### 1.4 Password Reset Completion

**Email Sent:** ❌ NO
- **Status:** No email sent after successful password reset

**In-App Notification:** ❌ NO
- **Status:** Not sent after password reset

**Push Notification:** ❌ NO
- **Status:** Not sent after password reset

**Note:** Password update is logged in audit logs but no notification is sent.

---

### 1.5 Email Change Request

**Email Sent:** ✅ YES
- **When:** User changes email address
- **Type:** OTP for Email Change Verification
- **Recipient:** New email address
- **Method:** `OtpService->sendMail(OtpType::CHANGE_EMAIL_VERIFICATION, $newEmail)`
- **Notification Class:** `VerifyEmailNotification` (assumed)
- **Location:** `app/Services/AccountService.php:35-37`
- **Content:** OTP code to verify new email

**In-App Notification:** ❌ NO
- **Status:** Not sent for email change

**Push Notification:** ❌ NO
- **Status:** Not sent for email change

---

### 1.6 Profile Update

**Email Sent:** ❌ NO
- **Status:** No email sent on profile update

**In-App Notification:** ❌ NO
- **Status:** Not sent for profile updates

**Push Notification:** ❌ NO
- **Status:** Not sent for profile updates

---

## 2. Business License Management

### 2.1 License Upload

**Email Sent:** ✅ YES
- **When:** Vendor/Supplier/Lender uploads license document
- **Type:** License Verification Request
- **Recipients:**
  1. **Business Owner** - Confirmation that license was received
  2. **All Admins** - Notification that license needs review
- **Method:** `User->sendLicenseVerificationNotification($message, $user)`
- **Notification Class:** `LicenseVerificationNotification`
- **Location:** `app/Http/Controllers/BusinessSettingController.php:107, 117`
- **Content:**
  - To Owner: "We've received your license verification request..."
  - To Admins: "A {role} has submitted their license for verification..."

**In-App Notification:** ✅ YES
- **Type:** `LICENSE_UPLOAD`
- **Recipient:** Business owner
- **Location:** `app/Http/Controllers/BusinessSettingController.php:111`
- **Type:** `ADMIN_LICENSE_UPLOAD`
- **Recipients:** All admins
- **Location:** `app/Http/Controllers/BusinessSettingController.php:122`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 2.2 License Approval

**Email Sent:** ✅ YES
- **When:** Admin approves license
- **Type:** License Approval Notification
- **Recipient:** Business owner
- **Method:** `User->sendLicenseVerificationNotification($message, $user)`
- **Notification Class:** `LicenseVerificationNotification`
- **Location:** `app/Services/Admin/LicenseService.php:78`
- **Content:** "Great news! Your license has been successfully approved..."

**In-App Notification:** ✅ YES
- **Type:** `LICENSE_ACCEPTANCE`
- **Recipient:** Business owner
- **Location:** `app/Services/Admin/LicenseService.php:82`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 2.3 License Rejection

**Email Sent:** ✅ YES
- **When:** Admin rejects license
- **Type:** License Rejection Notification
- **Recipient:** Business owner
- **Method:** `User->sendLicenseVerificationNotification($message, $user)`
- **Notification Class:** `LicenseVerificationNotification`
- **Location:** `app/Services/Admin/LicenseService.php:84`
- **Content:** "Thank you for submitting your license... Unfortunately, your request has been rejected due to..."

**In-App Notification:** ✅ YES
- **Type:** `LICENSE_REJECTION`
- **Recipient:** Business owner
- **Location:** `app/Services/Admin/LicenseService.php:88`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 2.4 License Withdrawal

**Email Sent:** ❌ NO
- **Status:** No email sent when user withdraws license submission

**In-App Notification:** ❌ NO
- **Status:** Not sent for license withdrawal

**Push Notification:** ❌ NO
- **Status:** Not sent for license withdrawal

---

## 3. Team Management & Invitations

### 3.1 Send Team Invitation

**Email Sent:** ✅ YES
- **When:** Business owner/admin invites team member
- **Type:** Team Invitation
- **Recipient:** Invited user email
- **Method:** `Mail::to($invite->email)->send(new Mailer(MailType::SEND_INVITATION, $data))`
- **Mail Type:** `SEND_INVITATION`
- **Location:** `app/Services/InviteService.php:89`
- **Content:** Invitation link, business name, role, inviter details

**In-App Notification:** ❌ NO
- **Status:** Not sent when invitation is created

**Push Notification:** ❌ NO
- **Status:** Not sent when invitation is created

---

### 3.2 Invitation Acceptance

**Email Sent:** ❌ NO
- **Status:** No email sent to inviter when invite is accepted

**In-App Notification:** ✅ YES (Conditional)
- **Type:** `InvitationResponseNotification`
- **Recipients:** Users subscribed to "Invitation Response" notification
- **Location:** `app/Services/InviteService.php:176`
- **Condition:** Only sent to users who have subscribed to this notification type

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent if user is subscribed to notification

---

### 3.3 Invitation Rejection

**Email Sent:** ❌ NO
- **Status:** No email sent to inviter when invite is rejected

**In-App Notification:** ✅ YES (Conditional)
- **Type:** `InvitationResponseNotification`
- **Recipients:** Users subscribed to "Invitation Response" notification
- **Location:** `app/Services/InviteService.php:220`
- **Condition:** Only sent to users who have subscribed to this notification type

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent if user is subscribed to notification

---

### 3.4 Invitation Deletion

**Email Sent:** ❌ NO
- **Status:** No email sent when invitation is deleted

**In-App Notification:** ❌ NO
- **Status:** Not sent for invitation deletion

**Push Notification:** ❌ NO
- **Status:** Not sent for invitation deletion

---

## 4. Loan Application Flow

### 4.1 Loan Application Submission (Via Dashboard)

**Email Sent:** ✅ YES
- **When:** Vendor submits loan application for customer
- **Recipients:**
  1. **Customer** - Application submitted confirmation
  2. **Vendor** - Customer applied for loan notification
  3. **All Admins** - New loan request notification
  4. **Lenders** (if auto-accept disabled) - Loan request for manual approval
- **Method:** `Notification::route('mail', [...])->notify(new LoanSubmissionNotification($mailable))`
- **Notification Class:** `LoanSubmissionNotification`
- **Location:** `app/Repositories/FincraMandateRepository.php:275-324`
- **Content:**
  - Customer: Application ID, requested amount, submission date
  - Vendor: Customer name, application ID, requested amount
  - Admin: Customer name, vendor name, application ID, requested amount
  - Lender: Customer name, application ID, requested amount

**In-App Notification:** ✅ YES
- **Type:** `NEW_LOAN_REQUEST`
- **Recipients:**
  - All admins
  - Lenders (if manual approval required)
- **Location:** `app/Repositories/FincraMandateRepository.php:324, 541`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

**Webhook:** ✅ YES (Conditional)
- **When:** Vendor has webhook URL configured
- **Event:** `application.submitted`
- **Location:** `app/Repositories/FincraMandateRepository.php:550-579`

---

### 4.2 Loan Application Submission (Via Application Link)

**Email Sent:** ✅ YES
- **When:** Customer completes application via link
- **Recipients:**
  1. **Customer** - Application submitted confirmation
  2. **Vendor** - Customer applied for loan notification
  3. **All Admins** - New loan request notification
  4. **Lenders** (if auto-accept disabled) - Loan request for manual approval
- **Method:** `Notification::route('mail', [...])->notify(new CustomerLoanApplicationNotification($link))`
- **Notification Class:** `CustomerLoanApplicationNotification`
- **Location:** `app/Services/LoanApplicationService.php:102-104, 195-197, 232-234`

**In-App Notification:** ✅ YES
- **Type:** `NEW_LOAN_REQUEST`
- **Recipients:** Admins and lenders
- **Location:** Same as above

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 4.3 Loan Application Auto-Approval

**Email Sent:** ✅ YES
- **When:** Loan is auto-approved by lender
- **Recipients:**
  1. **Customer** - Loan approved notification
  2. **Vendor** - Loan approved notification
  3. **Lender** - Loan approved notification
  4. **All Admins** - Loan approved notification
- **Method:** `Notification::route('mail', [...])->notify(new LoanSubmissionNotification($mailable))`
- **Notification Class:** `LoanSubmissionNotification`
- **Location:** `app/Repositories/FincraMandateRepository.php:860-950`
- **Content:** Loan ID, approved amount, repayment schedule

**In-App Notification:** ✅ YES
- **Type:** `LOAN_REQUEST_APPROVED`
- **Recipients:**
  - Customer (via vendor user)
  - Lender
  - All admins
- **Location:** `app/Repositories/FincraMandateRepository.php:896, 923, 950`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

**Webhook:** ✅ YES (Conditional)
- **When:** Vendor has webhook URL configured
- **Event:** `application.approved`
- **Location:** `app/Repositories/FincraMandateRepository.php:968-1000`

---

### 4.4 Loan Application Cancellation

**Email Sent:** ✅ YES
- **When:** Customer cancels loan application
- **Recipient:** Vendor (business owner)
- **Method:** `Notification::route('mail', [...])->notify(new LoanSubmissionNotification($mailable))`
- **Notification Class:** `LoanSubmissionNotification`
- **Location:** `app/Repositories/LoanApplicationRepository.php:663-665`
- **Content:** Customer name, application ID, cancellation notice

**In-App Notification:** ❌ NO
- **Status:** Not sent for application cancellation

**Push Notification:** ❌ NO
- **Status:** Not sent for application cancellation

---

### 4.5 Loan Application Rejection

**Email Sent:** ❌ NO (Should be implemented)
- **Status:** No email sent when admin rejects application

**In-App Notification:** ❌ NO
- **Status:** Not sent for application rejection

**Push Notification:** ❌ NO
- **Status:** Not sent for application rejection

**Note:** This is a missing notification that should be implemented.

---

## 5. Loan Offer Management

### 5.1 Create Loan Offer

**Email Sent:** ✅ YES (via NotificationService)
- **When:** Vendor creates loan offer for customer
- **Recipient:** Customer
- **Method:** `NotificationService->sendLoanOfferNotification($creditOffer)`
- **Location:** `app/Services/OfferService.php:48`
- **Content:** Loan offer reference, offer details

**In-App Notification:** ❌ NO
- **Status:** Not sent for loan offer creation

**Push Notification:** ❌ NO
- **Status:** Not sent for loan offer creation

**Note:** Uses `Mail::raw()` which is not queued.

---

### 5.2 Loan Offer Acceptance

**Email Sent:** ✅ YES (via NotificationService)
- **When:** Customer accepts loan offer
- **Recipient:** Customer
- **Method:** `NotificationService->sendOfferAcceptanceNotification($creditOffer)`
- **Location:** `app/Services/OfferService.php:87`
- **Content:** Loan offer reference, acceptance confirmation

**In-App Notification:** ❌ NO
- **Status:** Not sent for offer acceptance

**Push Notification:** ❌ NO
- **Status:** Not sent for offer acceptance

**Note:** Uses `Mail::raw()` which is not queued.

---

### 5.3 Loan Offer Rejection

**Email Sent:** ✅ YES (via NotificationService)
- **When:** Customer rejects loan offer
- **Recipient:** Customer
- **Method:** `NotificationService->sendOfferRejectionNotification($creditOffer)`
- **Location:** `app/Services/OfferService.php:48`
- **Content:** Loan offer reference, rejection notice

**In-App Notification:** ❌ NO
- **Status:** Not sent for offer rejection

**Push Notification:** ❌ NO
- **Status:** Not sent for offer rejection

**Note:** Uses `Mail::raw()` which is not queued.

---

### 5.4 Loan Offer Deletion

**Email Sent:** ❌ NO
- **Status:** No email sent when offer is deleted

**In-App Notification:** ❌ NO
- **Status:** Not sent for offer deletion

**Push Notification:** ❌ NO
- **Status:** Not sent for offer deletion

---

## 6. Loan Repayment

### 6.1 Repayment Reminder

**Email Sent:** ✅ YES
- **When:** Repayment is due (scheduled reminder)
- **Recipient:** Customer
- **Method:** `Notification::route('mail', [...])->notify(new CustomerLoanRepaymentNotification($link, $message))`
- **Notification Class:** `CustomerLoanRepaymentNotification`
- **Location:** `app/Services/NotificationService.php:111-113`
- **Content:** Repayment amount, due date, payment link
- **Trigger:** `RepaymentReminderService` or `RepaymentProcessingService`

**In-App Notification:** ❌ NO
- **Status:** Not sent for repayment reminders

**Push Notification:** ❌ NO
- **Status:** Not sent for repayment reminders

---

### 6.2 Repayment Success

**Email Sent:** ❌ NO (Should be implemented)
- **Status:** No email sent when repayment is successful

**In-App Notification:** ❌ NO
- **Status:** Not sent for successful repayment

**Push Notification:** ❌ NO
- **Status:** Not sent for successful repayment

**Note:** This is a missing notification that should be implemented.

---

### 6.3 Loan Liquidation (Full Payment)

**Email Sent:** ✅ YES (via NotificationService)
- **When:** Loan is fully paid off
- **Recipient:** Customer
- **Method:** `NotificationService->sendLoanLiquidationNotification($customer, $loan)`
- **Location:** `app/Services/LoanService.php:103`
- **Content:** Loan ID, liquidation confirmation

**In-App Notification:** ❌ NO
- **Status:** Not sent for loan liquidation

**Push Notification:** ❌ NO
- **Status:** Not sent for loan liquidation

**Note:** Uses `Mail::raw()` which is not queued.

---

### 6.4 Repayment Failure

**Email Sent:** ❌ NO (Should be implemented)
- **Status:** No email sent when repayment fails

**In-App Notification:** ❌ NO
- **Status:** Not sent for repayment failure

**Push Notification:** ❌ NO
- **Status:** Not sent for repayment failure

**Note:** This is a missing notification that should be implemented.

---

## 7. E-commerce Orders

### 7.1 New Order Payment (Storefront/Customer)

**Email Sent:** ✅ YES
- **When:** Customer successfully pays for order
- **Recipient:** Customer (storefront user)
- **Method:** `Mail::to($user->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_STOREFRONT, [...]))`
- **Mail Type:** `NEW_ORDER_PAYMENT_STOREFRONT`
- **Location:** 
  - `app/Repositories/EcommercePaymentRepository.php:88`
  - `app/Repositories/FincraPaymentRepository.php:97`
- **Content:** Order details, products, total amount

**In-App Notification:** ✅ YES
- **Type:** `NEW_ORDER_PAYMENT_STOREFRONT`
- **Recipient:** Customer
- **Location:** 
  - `app/Repositories/EcommercePaymentRepository.php:77`
  - `app/Repositories/FincraPaymentRepository.php:92`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 7.2 New Order Payment (Supplier)

**Email Sent:** ✅ YES
- **When:** Order contains supplier's products
- **Recipient:** Supplier
- **Method:** `Mail::to($supplier->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_SUPPLIER, [...]))`
- **Mail Type:** `NEW_ORDER_PAYMENT_SUPPLIER`
- **Location:** `app/Repositories/FincraPaymentRepository.php:103`
- **Content:** Product names, order details, net amount

**In-App Notification:** ✅ YES
- **Type:** `NEW_ORDER_PAYMENT_SUPPLIER`
- **Recipient:** Supplier users
- **Location:** `app/Repositories/FincraPaymentRepository.php:93`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

**Note:** In `EcommercePaymentRepository`, supplier notification is commented out (lines 79-81, 93-98).

---

### 7.3 New Order Payment (Admin)

**Email Sent:** ✅ YES
- **When:** New order is placed
- **Recipient:** All admins
- **Method:** `Mail::to($admin->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_ADMIN, [...]))`
- **Mail Type:** `NEW_ORDER_PAYMENT_ADMIN`
- **Location:** 
  - `app/Repositories/EcommercePaymentRepository.php:100`
  - `app/Repositories/FincraPaymentRepository.php:112`
- **Content:** Order details, pharmacy name, product names

**In-App Notification:** ✅ YES
- **Type:** `NEW_ORDER_PAYMENT_ADMIN`
- **Recipients:** All admins
- **Location:** 
  - `app/Repositories/EcommercePaymentRepository.php:85`
  - `app/Repositories/FincraPaymentRepository.php:94`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 7.4 Order Processing (Pharmacy/Customer)

**Email Sent:** ✅ YES
- **When:** Order status changes to processing
- **Recipient:** Customer (pharmacy)
- **Method:** `Mail::to($customer->email)->queue(new Mailer(MailType::PROCESSING_ORDER_PHARMACY, [...]))`
- **Mail Type:** `PROCESSING_ORDER_PHARMACY`
- **Location:** `app/Services/Admin/Storefront/EcommerceCartService.php:160`
- **Content:** Order is being processed, shipment preparation

**In-App Notification:** ✅ YES
- **Type:** `PROCESSING_ORDER_PHARMACY`
- **Recipient:** Customer
- **Location:** `app/Services/Admin/Storefront/EcommerceCartService.php:152`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 7.5 Order Processing (Supplier)

**Email Sent:** ✅ YES
- **When:** Supplier's product in order is being processed
- **Recipient:** Supplier
- **Method:** `Mail::to($supplier->email)->queue(new Mailer(MailType::PROCESSING_PRODUCT_ORDER_SUPPLIER, [...]))`
- **Mail Type:** `PROCESSING_PRODUCT_ORDER_SUPPLIER`
- **Location:** `app/Services/Admin/Storefront/EcommerceCartService.php:160`
- **Content:** Product is being processed

**In-App Notification:** ✅ YES
- **Type:** `PROCESSING_PRODUCT_ORDER_SUPPLIER`
- **Recipient:** Supplier
- **Location:** `app/Services/Admin/Storefront/EcommerceCartService.php:157`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 7.6 Order Confirmation (Alternative Method)

**Email Sent:** ✅ YES
- **When:** Payment is successful (alternative implementation)
- **Recipients:**
  1. **Customer** - Order confirmation
  2. **Supplier/Owner** - New order notification
  3. **Admin** - New order notification
- **Method:** `User->sendOrderConfirmationNotification($message, $user)`
- **Notification Class:** `OrderConfirmationNotification`
- **Location:** 
  - `app/Repositories/TenmgPaymentRepository.php:94, 110, 115`
  - `app/Repositories/OrderRepository.php:356, 372, 377`
  - `app/Repositories/FincraPaymentRepository.php:428, 444, 449, 516, 532, 537`
- **Content:** Payment success message, order details

**In-App Notification:** ❌ NO
- **Status:** Not sent with this method

**Push Notification:** ❌ NO
- **Status:** Not sent with this method

---

## 8. Bank Account Management

### 8.1 Add Bank Account (Supplier/Vendor)

**Email Sent:** ✅ YES
- **When:** User adds bank account for withdrawals
- **Type:** OTP for Bank Account Verification
- **Recipient:** User adding bank account
- **Method:** `OtpService->sendMail(OtpType::SUPPLIER_ADD_BANK_ACCOUNT)`
- **Notification Class:** `SupplierAddBankAccountNotification`
- **Location:** `app/Services/OtpService.php:152-153`
- **Content:** OTP code to verify bank account addition

**In-App Notification:** ❌ NO
- **Status:** Not sent for bank account addition

**Push Notification:** ❌ NO
- **Status:** Not sent for bank account addition

---

### 8.2 Update Bank Account

**Email Sent:** ❌ NO
- **Status:** No email sent when bank account is updated

**In-App Notification:** ❌ NO
- **Status:** Not sent for bank account update

**Push Notification:** ❌ NO
- **Status:** Not sent for bank account update

---

### 8.3 Bank Account Verification

**Email Sent:** ❌ NO
- **Status:** No email sent after bank account verification

**In-App Notification:** ❌ NO
- **Status:** Not sent for bank account verification

**Push Notification:** ❌ NO
- **Status:** Not sent for bank account verification

---

## 9. Wallet & Withdrawals

### 9.1 Initialize Withdrawal

**Email Sent:** ✅ YES
- **When:** Vendor/Supplier initiates withdrawal
- **Type:** OTP for Withdrawal Verification
- **Recipient:** User initiating withdrawal
- **Method:** `OtpService->sendMail(OtpType::WITHDRAW_FUND_TO_BANK_ACCOUNT)`
- **Notification Class:** `WithdrawFundToBankAccountNotification`
- **Location:** `app/Repositories/VendorWalletRepository.php:138`
- **Content:** OTP code to complete withdrawal

**In-App Notification:** ❌ NO
- **Status:** Not sent for withdrawal initialization

**Push Notification:** ❌ NO
- **Status:** Not sent for withdrawal initialization

---

### 9.2 Withdrawal Success

**Email Sent:** ❌ NO (Should be implemented)
- **Status:** No email sent when withdrawal is successful

**In-App Notification:** ❌ NO
- **Status:** Not sent for successful withdrawal

**Push Notification:** ❌ NO
- **Status:** Not sent for successful withdrawal

**Note:** This is a missing notification that should be implemented.

---

### 9.3 Withdrawal Failure

**Email Sent:** ❌ NO (Should be implemented)
- **Status:** No email sent when withdrawal fails

**In-App Notification:** ❌ NO
- **Status:** Not sent for withdrawal failure

**Push Notification:** ❌ NO
- **Status:** Not sent for withdrawal failure

**Note:** This is a missing notification that should be implemented.

---

### 9.4 Wallet Transaction

**Email Sent:** ❌ NO
- **Status:** No email sent for wallet transactions (credits/debits)

**In-App Notification:** ❌ NO
- **Status:** Not sent for wallet transactions

**Push Notification:** ❌ NO
- **Status:** Not sent for wallet transactions

---

## 10. User Management

### 10.1 Admin Creates User

**Email Sent:** ✅ YES
- **When:** Admin creates a new user account
- **Type:** Account Creation Notification
- **Recipient:** Newly created user
- **Method:** `Mail::to($user->email)->send(new Mailer(MailType::ADMIN_CREATE_USER, $data))`
- **Mail Type:** `ADMIN_CREATE_USER`
- **Location:** `app/Services/Admin/UserService.php:92`
- **Content:** Account credentials, login information, temporary password

**In-App Notification:** ❌ NO
- **Status:** Not sent when admin creates user

**Push Notification:** ❌ NO
- **Status:** Not sent when admin creates user

---

### 10.2 User Account Suspension

**Email Sent:** ❌ NO (Should be implemented)
- **Status:** No email sent when account is suspended

**In-App Notification:** ✅ YES
- **Type:** `ACCOUNT_SUSPENSION`
- **Recipient:** Suspended user
- **Location:** `app/Http/Controllers/API/Admin/UsersController.php:175`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 10.3 User Account Unsuspension

**Email Sent:** ❌ NO (Should be implemented)
- **Status:** No email sent when account is unsuspended

**In-App Notification:** ✅ YES
- **Type:** `ACCOUNT_UNSUSPENDED`
- **Recipient:** Unsuspended user
- **Location:** `app/Http/Controllers/API/Admin/UsersController.php:178`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 10.4 User Status Change (Active/Inactive)

**Email Sent:** ❌ NO
- **Status:** No email sent when user status changes

**In-App Notification:** ❌ NO
- **Status:** Not sent for user status changes

**Push Notification:** ❌ NO
- **Status:** Not sent for user status changes

---

## 11. Messages

### 11.1 New Message Received

**Email Sent:** ❌ NO
- **Status:** No email sent for new messages

**In-App Notification:** ✅ YES
- **Type:** `NEW_MESSAGE`
- **Recipient:** Message recipient
- **Location:** `app/Services/MessageService.php:66`

**Push Notification:** ✅ YES (via Firebase)
- **Status:** Sent along with in-app notification

---

### 11.2 Message Read

**Email Sent:** ❌ NO
- **Status:** No email sent when message is read

**In-App Notification:** ❌ NO
- **Status:** Not sent for message read status

**Push Notification:** ❌ NO
- **Status:** Not sent for message read status

---

## 12. Job Applications

### 12.1 Job Application Submission

**Email Sent:** ✅ YES
- **When:** User applies for a job
- **Type:** Job Application Notification
- **Recipient:** Admin/HR email (configured)
- **Method:** `Mail::to(config('jobs.applications.notification_email'))->send(new JobApplicationSubmitted(...))`
- **Mailable Class:** `JobApplicationSubmitted`
- **Location:** `app/Services/Job/JobApplicationService.php:36-37`
- **Content:** Application details, resume attachment

**In-App Notification:** ❌ NO
- **Status:** Not sent for job applications

**Push Notification:** ❌ NO
- **Status:** Not sent for job applications

---

## 13. Missing Notifications

This section documents scenarios where notifications SHOULD be sent but are currently NOT implemented.

### 13.1 Loan Application Rejection
- **Should Send:** Email to customer and vendor
- **Should Send:** In-app notification to customer and vendor
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** HIGH

### 13.2 Repayment Success
- **Should Send:** Email to customer confirming payment
- **Should Send:** In-app notification to customer
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** HIGH

### 13.3 Repayment Failure
- **Should Send:** Email to customer about failed payment
- **Should Send:** In-app notification to customer and vendor
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** HIGH

### 13.4 Withdrawal Success
- **Should Send:** Email to user confirming withdrawal
- **Should Send:** In-app notification to user
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** MEDIUM

### 13.5 Withdrawal Failure
- **Should Send:** Email to user about failed withdrawal
- **Should Send:** In-app notification to user
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** MEDIUM

### 13.6 Account Suspension Email
- **Should Send:** Email to user when account is suspended
- **Current Status:** ❌ NOT IMPLEMENTED (In-app notification exists)
- **Priority:** MEDIUM

### 13.7 Account Unsuspension Email
- **Should Send:** Email to user when account is unsuspended
- **Current Status:** ❌ NOT IMPLEMENTED (In-app notification exists)
- **Priority:** MEDIUM

### 13.8 Loan Disbursement
- **Should Send:** Email to customer when loan is disbursed
- **Should Send:** In-app notification to customer
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** HIGH

### 13.9 Transaction History Upload Success
- **Should Send:** Email to vendor when transaction history is successfully uploaded
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** LOW

### 13.10 Credit Score Generated
- **Should Send:** Email to vendor when credit score is generated
- **Should Send:** In-app notification to vendor
- **Current Status:** ❌ NOT IMPLEMENTED
- **Priority:** MEDIUM

---

## Notification Channels Summary

### Email Channels
- ✅ **Mail Facade (Queued):** Used for most transactional emails via `Mailer` class
- ✅ **Mail Facade (Immediate):** Used for some notifications via `Mail::send()`
- ⚠️ **Mail::raw():** Used in `NotificationService` - NOT queued, should be migrated
- ✅ **Notification Route:** Used for loan-related emails via `Notification::route('mail', [...])`

### In-App Notification Channels
- ✅ **Database:** All in-app notifications stored in `notifications` table
- ✅ **InAppNotificationService:** Centralized service for sending in-app notifications
- ✅ **Firebase:** Push notifications sent via Firebase Cloud Messaging

### Notification Types (InAppNotificationType Enum)
1. `NEW_MESSAGE` - New message received
2. `LICENSE_UPLOAD` - License uploaded
3. `ADMIN_LICENSE_UPLOAD` - Admin notified of license upload
4. `LICENSE_REJECTION` - License rejected
5. `LICENSE_ACCEPTANCE` - License approved
6. `NEW_LOAN_REQUEST` - New loan application submitted
7. `LOAN_REQUEST_APPROVED` - Loan application approved
8. `NEW_ORDER_PAYMENT_STOREFRONT` - New order payment (customer)
9. `NEW_ORDER_PAYMENT_SUPPLIER` - New order payment (supplier)
10. `NEW_ORDER_PAYMENT_ADMIN` - New order payment (admin)
11. `PROCESSING_ORDER_PHARMACY` - Order processing (pharmacy)
12. `PROCESSING_PRODUCT_ORDER_SUPPLIER` - Order processing (supplier)
13. `ACCOUNT_SUSPENSION` - Account suspended
14. `ACCOUNT_UNSUSPENDED` - Account unsuspended

### Mail Types (MailType Enum)
1. `SEND_INVITATION` - Team invitation
2. `ADMIN_CREATE_USER` - Admin created user account
3. `NEW_ORDER_PAYMENT_STOREFRONT` - New order payment (customer)
4. `NEW_ORDER_PAYMENT_SUPPLIER` - New order payment (supplier)
5. `NEW_ORDER_PAYMENT_ADMIN` - New order payment (admin)
6. `PROCESSING_ORDER_PHARMACY` - Order processing (pharmacy)
7. `PROCESSING_PRODUCT_ORDER_SUPPLIER` - Order processing (supplier)

---



