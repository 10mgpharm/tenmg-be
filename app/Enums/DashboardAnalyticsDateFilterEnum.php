<?php

namespace App\Enums;

enum DashboardAnalyticsDateFilterEnum: string
{
    case TODAY = 'TODAY';
    case ONE_WEEK = 'ONE_WEEK';
    case TWO_WEEKS = 'TWO_WEEKS';
    case ONE_MONTH = 'ONE_MONTH';
    case THREE_MONTHS = 'THREE_MONTHS';
    case SIX_MONTHS = 'SIX_MONTHS';
    case ONE_YEAR = 'ONE_YEAR';
    case CUSTOM = 'CUSTOM';
}
