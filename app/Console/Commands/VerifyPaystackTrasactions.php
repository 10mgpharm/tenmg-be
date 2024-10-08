<?php

namespace App\Console\Commands;

use App\Services\PaystackService;
use Illuminate\Console\Command;

class VerifyPaystackTrasactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paystack:verify-trasactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    // run command every 3 hours
    public $schedule = '0 */3 * * *';

    public function __construct(private PaystackService $paystackService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start transaction verification');

        $this->paystackService->verifyDebitMandateTransactions();
        $this->paystackService->verifyRepaymentScheduleTransactions();

        $this->info('End transaction verification');
    }
}
