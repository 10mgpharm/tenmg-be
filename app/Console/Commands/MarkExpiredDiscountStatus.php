<?php

namespace App\Console\Commands;

use App\Models\EcommerceDiscount;
use Illuminate\Console\Command;

class MarkExpiredDiscountStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-expired-discount-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark expired discount as expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = EcommerceDiscount::query()->where('end_date', '<', now())->whereIn('status', ['ACTIVE', 'INACTIVE']);

        $beforeCount = (clone $query)->count();

        if ($beforeCount === 0) {
            $this->info("No expired discounts found before update.");
            return;
        }

        $updatedCount = (clone $query)->update(['status' => 'EXPIRED']);

        $afterCount = EcommerceDiscount::where('status', 'EXPIRED')->where('end_date', '<', now())->count();

        $this->info("$beforeCount discounts were expired before the update.");
        $this->info("$updatedCount discounts marked as expired.");
        $this->info("$afterCount total discounts are now expired.");
    }
}
