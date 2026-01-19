<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case FLAGGED = 'flagged';
    case CANCELLED = 'cancelled';
}
