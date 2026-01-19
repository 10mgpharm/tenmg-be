<?php

namespace App\Enums;

enum TransactionCategory: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
