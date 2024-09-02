<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    case VERIFIED = 'VERIFIED';
    case SUSPENDED = 'SUSPENDED';
    case BANNED = 'BANNED';
}
