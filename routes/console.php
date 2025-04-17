<?php

use App\Console\Commands\CancelUnapprovedLoans;
use App\Console\Commands\MarkExpiredDiscountStatus;
use App\Console\Commands\SendRepaymentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(MarkExpiredDiscountStatus::class)->dailyAt('00:00');
Schedule::command(CancelUnapprovedLoans::class)->dailyAt('00:00');
Schedule::command(SendRepaymentReminders::class)->dailyAt('06:00');
