<?php

namespace App\Console\Commands;

use App\Services\RepaymentProcessingService;
use Illuminate\Console\Command;

class ProcessRepayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repayment:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process auto-debit for repayments due today.';

    // run daily at 5am
    protected $schedule = '0 5 * * *';

    public function __construct(private RepaymentProcessingService $repaymentProcessingService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->repaymentProcessingService->processRepayments();
        $this->info('Repayments processed successfully.');
    }
}
