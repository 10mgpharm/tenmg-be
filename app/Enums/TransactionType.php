<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case LOAN_DISBURSEMENT = 'loan_disbursement';
    case LOAN_REPAYMENT = 'loan_repayment';
    case LOAN_REPAYMENT_INTEREST = 'loan_repayment_interest';
    case LOAN_REPAYMENT_PRINCIPAL = 'loan_repayment_principal';
    case VENDOR_PAYOUT = 'vendor_payout';
    case VENDOR_CREDIT_VOUCHER = 'vendor_credit_voucher';
    case LENDER_DEPOSIT = 'lender_deposit';
    case LENDER_INVESTMENT_RETURN = 'lender_investment_return';
    case ADMIN_COMMISSION = 'admin_commission';
    case ADMIN_INTEREST = 'admin_interest';
    case ORDER_PAYMENT = 'order_payment';
    case ORDER_COMMISSION = 'order_commission';
}
