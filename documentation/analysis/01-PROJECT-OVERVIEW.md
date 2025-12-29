# 10MG Backend - Project Overview

## What is 10MG?

10MG (10mg Pharm) is a **Health Tech E-commerce Platform** designed to facilitate the purchase of drugs and medications online. The platform provides a comprehensive end-to-end e-commerce solution with integrated **Buy Now Pay Later (BNPL)** credit facility.

## Core Purpose

The platform enables:
- **Online Pharmacy Operations**: Complete e-commerce functionality for pharmaceutical products
- **Business Onboarding**: Multi-role system supporting different business types
- **Credit Facility**: BNPL system with credit scoring, voucher issuance, and repayment management
- **Multi-Stakeholder Management**: Support for vendors, suppliers, lenders, storefronts, and administrators

## Technology Stack

### Framework & Language
- **Framework**: Laravel 11
- **PHP Version**: 8.2+
- **Database**: SQLite (default), MySQL/PostgreSQL (configurable)
- **API Authentication**: Laravel Passport (OAuth2)

### Key Packages & Libraries
- **spatie/laravel-permission** (v6.9) - Role and permission management
- **spatie/laravel-activitylog** (v4.8) - Activity logging and audit trails
- **spatie/laravel-settings** (v3.4) - Application settings management
- **spatie/laravel-data** (v4.11) - Data transfer objects
- **laravel/passport** (v12.0) - API authentication
- **laravel/telescope** (v5.2) - Debugging and monitoring
- **maatwebsite/excel** (v3.1) - Excel import/export
- **kreait/laravel-firebase** (v6.0) - Firebase Cloud Messaging for push notifications
- **pragmarx/google2fa-laravel** (v2.2) - Two-factor authentication
- **knuckleswtf/scribe** (v4.37) - API documentation

### Testing
- **pestphp/pest** (v2.35) - Testing framework
- **pestphp/pest-plugin-laravel** (v2.4) - Laravel integration for Pest

## Architecture Overview

### Multi-Role System

The platform supports five main user roles:

1. **Admin** - Platform administrators managing the entire system
2. **Vendor** - Pharmacy businesses offering products and managing customers
3. **Supplier** - Product suppliers managing inventory and orders
4. **Lender** - Financial institutions providing credit facilities
5. **Storefront** - End customers purchasing products

### Core Modules

#### 1. E-commerce Module
- Product catalog management (brands, categories, medication types, measurements, presentations)
- Shopping cart and checkout
- Order management and processing
- Payment integration (Fincra, Paystack)
- Product reviews and ratings
- Wishlist and shopping lists
- Discount/coupon system
- Shipping address management

#### 2. Credit/BNPL Module
- Customer management
- Transaction history upload and evaluation
- Credit scoring system
- Loan application processing
- Loan offer management
- Loan disbursement
- Repayment scheduling and processing
- Direct debit mandate management (Paystack)
- Lender preferences and auto-acceptance

#### 3. Wallet System
- E-commerce wallets (for suppliers/vendors)
- Lender wallets (deposit, investment, ledger)
- Vendor wallets (credit voucher, payout)
- Transaction tracking
- Payout management
- Bank account integration

#### 4. Notification System
- Email notifications (via Laravel Mail)
- In-app notifications
- Push notifications (Firebase Cloud Messaging)
- Notification preferences and subscriptions

#### 5. Business Management
- Business onboarding and verification
- License management (CAC documents)
- Team member invitations
- API key management
- Audit logging

#### 6. Integration & Webhooks
- Vendor API integration
- Webhook support for external systems
- Webhook call logging

## Database Structure

The application uses a comprehensive database schema with **186+ migrations** covering:

- **User Management**: Users, roles, permissions, business users
- **E-commerce**: Products, categories, orders, payments, carts, wishlists
- **Credit System**: Customers, applications, loans, offers, repayments, scores
- **Wallet System**: Multiple wallet types, transactions, payouts
- **Notifications**: App notifications, notification settings, device tokens
- **Audit & Logging**: Activity logs, API call logs, webhook logs
- **Settings**: Application settings, business settings

## API Structure

### API Versioning
- Current version: **v1** (routes/api.php)
- Future versions: `/routes/api-v2.php`, etc.

### Route Organization
- **Public Routes**: Authentication, signup, password reset
- **Protected Routes**: All authenticated endpoints
- **Role-Based Routes**: Routes scoped by user role (admin, vendor, supplier, lender, storefront)
- **Client API Routes**: External vendor integration endpoints

### Authentication
- **Laravel Passport** with OAuth2
- Token scopes: `temp` (temporal), `full` (full access)
- Token expiration: 1 hour (access), 3 hours (refresh), 24 hours (personal access)

## Key Features Implemented

✅ **E-commerce**
- Complete product catalog management
- Shopping cart and checkout flow
- Order processing workflow
- Payment gateway integration
- Product reviews and ratings
- Discount/coupon system

✅ **Credit/BNPL**
- Customer onboarding
- Transaction history evaluation
- Credit scoring algorithm
- Loan application workflow
- Loan offer system
- Repayment scheduling
- Direct debit mandates

✅ **Multi-role Support**
- Role-based access control
- Business-specific data isolation
- Team member management
- API key management

✅ **Notifications**
- Email notifications
- In-app notifications
- Push notifications (Firebase)
- Notification preferences

✅ **Wallet System**
- Multiple wallet types
- Transaction tracking
- Payout processing
- Bank account management

✅ **Audit & Logging**
- Activity logging (Spatie Activity Log)
- API call logging
- Webhook call logging

## Deployment

### Docker Support
- Docker Compose configuration
- Separate configurations for dev and staging
- Laravel queue workers via Supervisord
- Multiple queue workers (default, high, low priority)

### Environment Configuration
- Environment-based configuration
- Configurable cache and queue drivers
- Support for multiple database drivers
- Mail configuration (SMTP, SES, Postmark, Resend)

## Development Standards

### Code Structure
- **Service Layer Pattern**: All business logic in services
- **Repository Pattern**: Data access abstraction
- **Interface-Based Services**: Services implement interfaces
- **Form Requests**: Request validation
- **API Resources**: Response transformation

### Email Handling
- Event-driven email sending
- Notification classes for all emails
- Database and mail channels enabled
- Queued email sending

### Testing
- Pest testing framework
- Unit and feature tests
- Test coverage for critical services

## Project Status

This is an **active development project** with comprehensive features implemented. The codebase follows Laravel best practices and includes:

- Modern Laravel 11 architecture
- Comprehensive API endpoints
- Multi-role access control
- Complete e-commerce functionality
- Integrated BNPL credit system
- Notification system
- Wallet management
- Audit logging

---

**Last Updated**: Based on codebase analysis as of current date
**Project Type**: Health Tech E-commerce Platform with BNPL
**Development Status**: Active Development













