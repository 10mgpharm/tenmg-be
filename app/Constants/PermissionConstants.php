<?php

namespace App\Constants;

class PermissionConstants
{
    // DASHBOARD ACCESS
    public const VENDOR_DASHBOARD = 'access vendor dashboard';

    public const SUPPLIER_DASHBOARD = 'access supplier dashboard';

    public const ADMIN_DASHBOARD = 'access admin dashboard';

    public const STOREFRONT = 'access storefront';

    // PRODUCT MANAGEMENT
    public const VIEW_PRODUCT_LIST = 'view product list';

    public const CREATE_PRODUCT = 'create product';

    public const UPDATE_PRODUCT = 'update product'; // DISABLE, ENABLE, UPDATE

    public const DELETE_PRODUCT = 'delete product';

    public const PRODUCT_INSIGHTS = 'view product insight report';

    // ORDER MANAGEMENT
    public const VIEW_ORDERS = 'view orders';

    public const CANCEL_ORDER = 'cancel orders';

    // USER MANAGEMENTS - ROLES, PERMISSIONS [only for customer roles]
    public const VIEW_ROLES = 'view roles';

    public const CREATE_ROLES = 'create roles';

    public const UPDATE_ROLES = 'update roles'; // DISABLE, ENABLE, ADD PERMISSIONS

    public const DELETE_ROLES = 'delete roles';

    public const VIEW_ADMIN_USERS = 'view admin users';

    public const CREATE_ADMIN_USER = 'create admin users';

    public const UPDATE_ADMIN_USER = 'view all admin users';  // DISABLE, ENABLE, DELETE USER

    // APP SETUPS - [for brands, medication_types, variations etc. setups]
    public const VIEW_APP_SETUP = 'view app setup';

    public const CREATE_APP_SETUP = 'create app setup';

    public const UPDATE_APP_SETUP = 'update app setup';  // DISABLE, ENABLE, UPDATE INFO

    public const DELETE_APP_SETUP = 'delete app setup';

    public const PUBLISH_APP_SETUP = 'publish app setup';

    // CREDIT CUSTOMER MANAGEMENT
    public const VIEW_CUSTOMER_LIST = 'view customer list';

    public const CREATE_CUSTOMER = 'create customer';

    public const UPDATE_CUSTOMER = 'update customer'; // DISABLE, ENABLE, UPDATE INFO

    public const DELETE_CUSTOMER = 'enable customer';

    // CREDIT APPLICATION MANAGEMENT
    public const VIEW_APPLICATIONS_LIST = 'view application list';

    public const CREATE_APPLICATIONS = 'create application';

    public const UPDATE_APPLICATIONS = 'update application'; // APPROVE, REJECT

    public const DELETE_APPLICATIONS = 'delete application';

    // CREDIT TRANSACTION HISTORY EVALUATION CHECK
    public const VIEW_TRANSACTION_HISTORY_LIST = 'view transaction list';

    public const UPLOAD_TRANSACTION_HISTORY = 'upload transaction';

    public const RUN_CREDIT_SCORE = 'run credit score on transaction';

    // CREDIT OFFER GENERATION
    public const VIEW_OFFER_LIST = 'view offer list';

    public const CREATE_OFFER = 'create new offer offer';

    public const UPDATE_OFFER = 'update offer';  // DISABLE, ENABLE, UPDATE OFFER

    public const DELETE_OFFER = 'delete offer';

    // CREDIT VOUCHERS
    public const VIEW_LOAN_LIST = 'view loan list';

    public const POST_CASH_REPAYMENT = 'post cash repaymaner';  // DISABLE, ENABLE, UPDATE INFO

    public const CLOSE_LOAN = 'manually close loan';

    public const VIEW_REPAYMENTS = 'view loan repayments';

    // AUDIT LOG
    public const VIEW_MY_AUDIT_LOG = 'view my audit log';

    public const VIEW_SYSTEM_LOG = 'view system log';

    // MESSAGING
    public const VIEW_MESSAGES = 'view messages';

    public const SEND_MESSAGES = 'send message';
}
