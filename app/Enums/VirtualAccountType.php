<?php

namespace App\Enums;

enum VirtualAccountType: string
{
    case INDIVIDUAL = 'individual';
    case CORPORATE = 'corporate';
}
