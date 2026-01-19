<?php

namespace App\Enums;

enum CurrencyClassification: string
{
    case FIAT = 'fiat';
    case CRYPTO = 'crypto';
}
