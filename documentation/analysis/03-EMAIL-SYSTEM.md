# Email System Documentation

This document provides a comprehensive overview of the email notification system implemented in the 10MG Backend.

## Email Configuration

### Mail Driver
- **Default Driver**: `log` (configurable via `MAIL_MAILER` env variable)
- **Supported Drivers**: SMTP, SES, Postmark, Resend, Sendmail, Log, Array, Failover, Roundrobin
- **Configuration File**: `config/mail.php`

### Mail Settings
- **From Address**: Configurable via `MAIL_FROM_ADDRESS`
- **From Name**: Configurable via `MAIL_FROM_NAME`
- **Support Address**: Configurable via `MAIL_SUPPORT_ADDRESS`

## Email Types (MailType Enum)

The application uses an enum (`App\Enums\MailType`) to define email types:

### Implemented Email Types

1. **SEND_INVITATION**
   - Purpose: Send invitation to team members
   - Subject: "You have been invited"
   - View: `mail.view.send_invitation`
   - Text View: `mail.text.send_invitation`

2. **ADMIN_CREATE_USER**
   - Purpose: Notify user when admin creates their account
   - Subject: "An account has been created for you"
   - View: `mail.view.admin_create_user`
   - Text View: `mail.text.admin_create_user`

3. **NEW_ORDER_PAYMENT_STOREFRONT**
   - Purpose: Notify storefront user of successful order payment
   - Subject: "Order Successfully Placed"
   - View: `mail.view.new_order_payment_storefront`
   - Text View: `mail.text.new_order_payment_storefront`

4. **NEW_ORDER_PAYMENT_SUPPLIER**
   - Purpose: Notify supplier of new order with their products
   - Subject: "New Order with Your Product"
   - View: `mail.view.new_order_payment_supplier`
   - Text View: `mail.text.new_order_payment_supplier`

5. **NEW_ORDER_PAYMENT_ADMIN**
   - Purpose: Notify admin of new order
   - Subject: "New Order Received"
   - View: `mail.view.new_order_payment_admin`
   - Text View: `mail.text.new_order_payment_admin`

6. **PROCESSING_ORDER_PHARMACY**
   - Purpose: Notify pharmacy that order is being processed
   - Subject: "Your Order is Now Being Processed"
   - View: `mail.view.processing_product_order_supplier`
   - Text View: `mail.text.processing_product_order_supplier`

7. **PROCESSING_PRODUCT_ORDER_SUPPLIER**
   - Purpose: Notify supplier that product order is being processed
   - Subject: "Your Product is Now Being Processed"
   - View: `mail.view.processing_product_order_supplier`
   - Text View: `mail.text.processing_product_order_supplier`

## Email Implementation Architecture

### Mailer Class

**Location**: `app/Mail/Mailer.php`

The main Mailable class that implements `ShouldQueue`:

```php
class Mailer extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    
    public function __construct(
        private MailType $mailType, 
        public array $data
    ) {}
}
```

**Features**:
- ✅ Implements `ShouldQueue` - All emails are queued
- ✅ Uses `Queueable` and `SerializesModels` traits
- ✅ Environment-aware view selection (HTML in production, text in local)

### Email Sending Methods

#### 1. Direct Mail Facade (Not Recommended)
Some code uses direct `Mail::raw()` calls:
- **Location**: `app/Services/NotificationService.php`
- **Issue**: Not using the Mailer class or queue system

#### 2. Queued Mail (Recommended)
Most emails use the queued Mailer class:
- **Location**: `app/Repositories/EcommercePaymentRepository.php`
- **Example**: `Mail::to($user->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_STOREFRONT, [...]))`

#### 3. Job Batching
Some emails are sent via job batches:
- **Location**: `app/Repositories/EcommercePaymentRepository.php`
- Uses `Bus::batch()` to send multiple notifications and emails together

## Notification Classes

The application has multiple notification classes that extend Laravel's Notification system:

### Auth Notifications
- **VerifyEmailNotification** (`app/Notifications/Auth/VerifyEmailNotification.php`)
  - Sent when user needs to verify email
  - Uses OTP code
  
- **ResetPasswordNotification** (`app/Notifications/Auth/ResetPasswordNotification.php`)
  - Sent for password reset
  - Uses OTP code
  
- **WelcomeUserNotification** (`app/Notifications/Auth/WelcomeUserNotification.php`)
  - Sent to welcome new users

### Order Notifications
- **OrderConfirmationNotification** (`app/Notifications/Order/OrderConfirmationNotification.php`)
- **NewOrderPaymentNotification** (`app/Notifications/Order/NewOrderPaymentNotification.php`)
- **OrderProcessingPharmacyNotification** (`app/Notifications/Order/OrderProcessingPharmacyNotification.php`)
- **ProcessingOrderSupplierNotification** (`app/Notifications/Order/ProcessingOrderSupplierNotification.php`)

### Loan Notifications
- **LoanSubmissionNotification** (`app/Notifications/Loan/LoanSubmissionNotification.php`)
- **NewLoanRequestNotification** (`app/Notifications/Loan/NewLoanRequestNotification.php`)
- **LoanApprovedNotification** (`app/Notifications/Loan/LoanApprovedNotification.php`)
- **CustomerLoanApplicationNotification** (`app/Notifications/CustomerLoanApplicationNotification.php`)
- **CustomerLoanRepaymentNotification** (`app/Notifications/CustomerLoanRepaymentNotification.php`)

### License Notifications
- **LicenseUploadNotification** (`app/Notifications/LicenseUploadNotification.php`)
- **LicenseVerificationNotification** (`app/Notifications/LicenseVerificationNotification.php`)
- **LicenseRejectNotification** (`app/Notifications/LicenseRejectNotification.php`)
- **LicenseAcceptanceNotification** (`app/Notifications/LicenseAcceptanceNotification.php`)

### Other Notifications
- **NewUserAddedNotification** (`app/Notifications/NewUserAddedNotification.php`)
- **UserStatusNotification** (`app/Notifications/UserStatusNotification.php`)
- **UserSuspensionNotification** (`app/Notifications/UserSuspensionNotification.php`)
- **InvitationResponseNotification** (`app/Notifications/InvitationResponseNotification.php`)
- **NewMessageNotification** (`app/Notifications/NewMessageNotification.php`)
- **NewProductAddedNotification** (`app/Notifications/NewProductAddedNotification.php`)
- **GoodsExpirationNotification** (`app/Notifications/GoodsExpirationNotification.php`)
- **ShippingListUpdateNotification** (`app/Notifications/ShippingListUpdateNotification.php`)
- **SupplierAddBankAccountNotification** (`app/Notifications/SupplierAddBankAccountNotification.php`)
- **WithdrawFundToBankAccountNotification** (`app/Notifications/WithdrawFundToBankAccountNotification.php`)
- **StatementEmail** (`app/Mail/StatementEmail.php`)

## Email Channels

### Database Channel
- ✅ Enabled for notifications
- Stores notifications in `notifications` table
- Allows users to view notifications in-app

### Mail Channel
- ✅ Enabled for notifications
- Sends emails via configured mail driver

### Firebase Channel
- ✅ Custom channel implemented
- Sends push notifications via Firebase Cloud Messaging
- **Location**: `app/Providers/AppServiceProvider.php` (Notification::extend('firebase'))

## Email Templates

### Template Location
- **Blade Views**: `resources/views/mail/view/`
- **Text Views**: `resources/views/mail/text/`

### Template Structure
Templates are organized by email type:
- `send_invitation.blade.php`
- `admin_create_user.blade.php`
- `new_order_payment_storefront.blade.php`
- `new_order_payment_supplier.blade.php`
- `new_order_payment_admin.blade.php`
- `processing_product_order_supplier.blade.php`

## Email Events & Listeners

### Implemented Listeners
- **SignupEmailVerifiedListener** (`app/Listeners/SignupEmailVerifiedListener.php`)
  - Listens to `Verified` event
  - Handles post-verification actions

### Event-Driven Email (Partial)
- ⚠️ **Not Fully Implemented**: The codebase agreement states emails should be event-driven, but not all emails follow this pattern
- Some emails are sent directly from services/repositories
- Recommendation: Migrate all emails to event-driven architecture

## Email Queueing

### Queue Configuration
- **Default Queue**: Database queue (configurable)
- **Queue Connection**: `QUEUE_CONNECTION` env variable
- **Supported Drivers**: Database, Redis, SQS, Beanstalkd

### Queue Usage
- ✅ **Mailer Class**: All emails via Mailer class are queued (`implements ShouldQueue`)
- ✅ **Job Batching**: Some emails use job batching for better performance
- ⚠️ **Inconsistent**: Some emails may still use synchronous sending

### Queue Workers
- **Docker Configuration**: `docker/laravel-worker.conf`
- Multiple workers configured:
  - Default queue worker
  - High priority queue worker
  - Low priority queue worker

## Email Sending Examples

### Example 1: Queued Email via Mailer
```php
Mail::to($user->email)->queue(new Mailer(
    MailType::NEW_ORDER_PAYMENT_STOREFRONT,
    [
        'user' => $user,
        'order' => $order,
    ]
));
```

### Example 2: Job Batch for Multiple Emails
```php
Bus::batch([
    fn () => Mail::to($user->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_STOREFRONT, [...])),
    fn () => Mail::to($admin->email)->queue(new Mailer(MailType::NEW_ORDER_PAYMENT_ADMIN, [...])),
])
->name('New Order Payment Notifications & Mails')
->allowFailures()
->dispatch();
```

### Example 3: Direct Mail (Not Recommended)
```php
Mail::raw($message, function ($message) use ($data) {
    $message->to($data['to'])
        ->subject($data['subject']);
});
```

## Email Issues & Recommendations

### Current Issues

1. **Inconsistent Email Sending**
   - Some emails use Mailer class, others use direct Mail facade
   - Not all emails are queued
   - Not all emails follow event-driven pattern

2. **Missing Email Types**
   - Some notification types don't have corresponding email templates
   - Some email types are defined but not used

3. **No Email Template Management**
   - Templates are hardcoded in Blade files
   - No admin interface to manage templates
   - No email template versioning

4. **Limited Email Tracking**
   - No email delivery tracking
   - No email open tracking
   - No email click tracking

5. **No Email Preferences**
   - Users can't unsubscribe from specific email types
   - No email frequency preferences
   - No email format preferences (HTML/text)

### Recommendations

1. **Standardize Email Sending**
   - Migrate all emails to use Mailer class
   - Ensure all emails are queued
   - Implement event-driven email architecture

2. **Add Email Tracking**
   - Implement email delivery tracking
   - Add email open tracking
   - Add email click tracking

3. **Email Preferences**
   - Allow users to manage email preferences
   - Implement unsubscribe functionality
   - Add email frequency controls

4. **Email Template Management**
   - Create admin interface for email templates
   - Support template versioning
   - Allow dynamic template updates

5. **Email Testing**
   - Add email testing utilities
   - Create email preview functionality
   - Add email template validation

6. **Email Analytics**
   - Track email delivery rates
   - Monitor email open rates
   - Track email click rates

7. **Email Retry Logic**
   - Implement email retry for failed sends
   - Add email failure notifications
   - Log email failures

## Email Testing

### Local Development
- Uses `log` driver by default
- Emails are logged to `storage/logs/laravel.log`
- Can use `array` driver for testing

### Testing Emails
- Use `Mail::fake()` in tests
- Assert emails were sent
- Assert email content

## Email Security

### Current Security Measures
- ✅ Email addresses validated before sending
- ✅ Uses Laravel's built-in email validation
- ⚠️ No rate limiting on email sending
- ⚠️ No email spam protection

### Recommendations
- Implement rate limiting for email sending
- Add email spam detection
- Implement email verification for new addresses
- Add CAPTCHA for email-related actions

---

**Last Updated**: Based on codebase analysis
**Email System Status**: Partially Implemented - Needs Standardization













