<?php

namespace App\Enums;

enum LoanProviderMode: string
{
    case SYSTEM = 'SYSTEM';
    case EXTERNAL = 'EXTERNAL';
}
