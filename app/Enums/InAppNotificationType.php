<?php

namespace App\Enums;

enum InAppNotificationType: string
{
    case NEW_MESSAGE = 'new_message';
    case LICENSE_UPLOAD = 'license_upload';
    case ADMIN_LICENSE_UPLOAD = 'admin_license_upload';
    case LICENSE_REJECTION = 'license_rejection';
    case LICENSE_ACCEPTANCE = 'license_acceptance';
    case NEW_LOAN_REQUEST = 'New_loan_request';
    case LOAN_REQUEST_APPROVED = 'loan_request_approved';
    case NEW_ORDER_PAYMENT_STOREFRONT = 'new_order_payment_storefront';
    case NEW_ORDER_PAYMENT_SUPPLIER = 'new_order_payment_supplier';
    case NEW_ORDER_PAYMENT_ADMIN = 'new_order_payment_admin';
    case PROCESSING_ORDER_PHARMACY = 'processing_order_pharmacy';
    case PROCESSING_PRODUCT_ORDER_SUPPLIER = 'processing_product_order_supplier';

    /**
     * Get the subject for the notification type.
     */
    public function subject(): string
    {
        return match ($this) {
            self::NEW_MESSAGE => 'New Message',
            self::LICENSE_UPLOAD => ' License Verification Request Received',
            self::ADMIN_LICENSE_UPLOAD => 'License Verification Submitted - Awaiting Review',
            self::LICENSE_REJECTION => 'License Verification Rejected',
            self::LICENSE_ACCEPTANCE => 'License Verification Approved',
            self::NEW_LOAN_REQUEST => 'New Loan Request',
            self::LOAN_REQUEST_APPROVED => 'Loan Request Approved',
            self::NEW_ORDER_PAYMENT_STOREFRONT => 'Order Successfully Placed',
            self::NEW_ORDER_PAYMENT_SUPPLIER => 'New Order with Your Product',
            self::NEW_ORDER_PAYMENT_ADMIN => 'New Order Received',
            self::PROCESSING_ORDER_PHARMACY => 'Your Order is Now Being Processed',
            self::PROCESSING_PRODUCT_ORDER_SUPPLIER => 'Your Product is Now Being Processed',
        };
    }

    /**
     * Get the default message for the notification type.
     *
     * @param  string|null  $role  The user's role (e.g., Supplier, Vendor, Pharmacy, Lender).
     */
    public function message(?string $role = null): string
    {
        if ($role == 'customer') {
            $role = 'Healthcare Provider';
        }
        $role = ucwords($role ?? '');

        return match ($this) {
            self::NEW_MESSAGE => 'You have received a new message.',
            self::LICENSE_UPLOAD => 'We have received your license verification request, and it is currently under review. You will receive a response from us shortly.',
            self::ADMIN_LICENSE_UPLOAD => "A $role has submitted their license for verification, and it is now awaiting review.",
            self::LICENSE_REJECTION => "Thank you for submitting your license for verification. Unfortunately, your request has been rejected.",
            self::LICENSE_ACCEPTANCE => "Great news! Your license has been successfully approved, and you now have full access to all features of " . config('app.name'),
            self::NEW_LOAN_REQUEST => 'A new loan request was submitted.',
            self::LOAN_REQUEST_APPROVED => 'Customer loan request has been approved.',
            self::NEW_ORDER_PAYMENT_STOREFRONT => 'Your order has been successfully placed! We are processing your order and will notify you accordingly.',
            self::NEW_ORDER_PAYMENT_SUPPLIER => 'You have an order containing your product(s).',
            self::NEW_ORDER_PAYMENT_ADMIN => 'A new order has been placed.',
            self::PROCESSING_ORDER_PHARMACY => 'Your order is now being processed! We are preparing your items for shipment and will notify you once they are on the way.',
            self::PROCESSING_PRODUCT_ORDER_SUPPLIER => 'Your product(s) in an order have been moved to the processing stage.',
        };
    }

    /**
     * Get the recipient of the notification.
     *
     * @return string  Either 'admin' or 'user'.
     */
    public function recipient(): string
    {
        return match ($this) {
            self::ADMIN_LICENSE_UPLOAD => 'admin', // License upload notifications go to the admin
            self::NEW_ORDER_PAYMENT_ADMIN => 'admin',
            default => 'user', // All other notifications go to the user
        };
    }
}
