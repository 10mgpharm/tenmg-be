<?php

namespace App\Console\Commands;

use App\Services\RepaymentReminderService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class SendRepaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repayment:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send repayment reminders for upcoming due dates.';


    // run daily at 6am
    protected $schedule = '0 6 * * *';

    public function __construct(private RepaymentReminderService $repaymentReminderService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->repaymentReminderService->sendReminders();
        $this->info('Repayment reminders sent successfully.');
    }
}
