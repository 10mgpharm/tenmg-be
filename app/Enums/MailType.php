<?php

namespace App\Enums;

use Illuminate\Http\Response;

enum MailType: string
{
    case SEND_INVITATION = 'send_invitation';
    case ADMIN_CREATE_USER = 'admin_create_user';
    case SUPPLIER_ADD_BANK_ACCOUNT = 'supplier_add_bank_account';
    case WITHDRAW_FUND_TO_BANK_ACCOUNT = 'withdraw_fund_to_bank_account';
    case NEW_ORDER_PAYMENT_STOREFRONT = 'new_order_payment_storefront';
    case NEW_ORDER_PAYMENT_SUPPLIER = 'new_order_payment_supplier';
    case NEW_ORDER_PAYMENT_ADMIN = 'new_order_payment_admin';
    case PROCESSING_ORDER_PHARMACY = 'processing_order_pharmacy';
    case PROCESSING_PRODUCT_ORDER_SUPPLIER = 'processing_product_order_supplier';

    /**
     * Get the HTTP status code associated with the mail type.
     */
    public function httpStatusCode(): int
    {
        return match ($this) {
            self::SEND_INVITATION => Response::HTTP_CREATED,
            self::ADMIN_CREATE_USER => Response::HTTP_CREATED,
            self::SUPPLIER_ADD_BANK_ACCOUNT => Response::HTTP_CREATED,
            self::WITHDRAW_FUND_TO_BANK_ACCOUNT => Response::HTTP_CREATED,
            self::NEW_ORDER_PAYMENT_STOREFRONT => Response::HTTP_CREATED,
            self::NEW_ORDER_PAYMENT_SUPPLIER => Response::HTTP_CREATED,
            self::NEW_ORDER_PAYMENT_ADMIN => Response::HTTP_CREATED,
            self::PROCESSING_ORDER_PHARMACY => Response::HTTP_CREATED,
            self::PROCESSING_PRODUCT_ORDER_SUPPLIER => Response::HTTP_CREATED,
        };
    }

    /**
     * Get the subject of the mail.
     */
    public function subject(): string
    {
        return match ($this) {
            self::SEND_INVITATION => 'You have been invited',
            self::ADMIN_CREATE_USER => 'An account has been created for you',
            self::SUPPLIER_ADD_BANK_ACCOUNT => 'Add bank account',
            self::WITHDRAW_FUND_TO_BANK_ACCOUNT => 'Withdraw Fund to Bank Account',
            self::NEW_ORDER_PAYMENT_STOREFRONT => 'Order Successfully Placed',
            self::NEW_ORDER_PAYMENT_SUPPLIER => 'New Order with Your Product',
            self::NEW_ORDER_PAYMENT_ADMIN => 'New Order Received',
            self::PROCESSING_ORDER_PHARMACY => 'Your Order is Now Being Processed',
            self::PROCESSING_PRODUCT_ORDER_SUPPLIER => 'Your Product is Now Being Processed',
        };
    }

    /**
     * Get the Blade view for the mail.
     */
    public function view(): string
    {
        return match ($this) {
            self::SEND_INVITATION => 'mail.view.send_invitation',
            self::ADMIN_CREATE_USER => 'mail.view.admin_create_user',
            self::SUPPLIER_ADD_BANK_ACCOUNT => 'mail.view.supplier_add_bank_account',
            self::WITHDRAW_FUND_TO_BANK_ACCOUNT => 'mail.view.withdraw_fund_to_bank_account',
            self::NEW_ORDER_PAYMENT_STOREFRONT => 'mail.view.new_order_payment_storefront',
            self::NEW_ORDER_PAYMENT_SUPPLIER => 'mail.view.new_order_payment_supplier',
            self::PROCESSING_ORDER_PHARMACY => 'mail.view.processing_product_order_supplier',
            self::NEW_ORDER_PAYMENT_ADMIN => 'mail.view.new_order_payment_admin',
            self::PROCESSING_PRODUCT_ORDER_SUPPLIER => 'mail.view.processing_product_order_supplier',
        };
    }

    /**
     * Get the plain text view for the mail.
     */
    public function text(): string
    {
        return match($this) {
            self::SEND_INVITATION => 'mail.text.send_invitation',
            self::ADMIN_CREATE_USER => 'mail.text.admin_create_user',
            self::SUPPLIER_ADD_BANK_ACCOUNT => 'mail.text.supplier_add_bank_account',
            self::NEW_ORDER_PAYMENT_STOREFRONT => 'mail.text.new_order_payment_storefront',
            self::WITHDRAW_FUND_TO_BANK_ACCOUNT => 'mail.text.withdraw_fund_to_bank_account',
            self::NEW_ORDER_PAYMENT_SUPPLIER => 'mail.text.new_order_payment_supplier',
            self::NEW_ORDER_PAYMENT_ADMIN => 'mail.text.new_order_payment_admin',
            self::PROCESSING_ORDER_PHARMACY => 'mail.text.processing_product_order_supplier',
            self::PROCESSING_PRODUCT_ORDER_SUPPLIER => 'mail.text.processing_product_order_supplier',
        };
    }
}
