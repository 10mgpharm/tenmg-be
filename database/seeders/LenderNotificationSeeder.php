<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use Illuminate\Database\Seeder;

class LenderNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notifications = [
            [
                'name' => 'New Loan Request',
                'description' => 'Get notified when a new loan request is submitted.',
                'is_lender' => true,
                'active' => true,
            ],
            [
                'name' => 'Debtors Have Defaulted on Loan Repayment',
                'description' => 'Get notified when debtors have defaulted on loan repayment.',
                'is_lender' => true,
                'active' => true,
            ],
            [
                'name' => 'Upcoming Customer Loan Repayment',
                'description' => 'Get notified when a customer loan repayment is approaching.',
                'is_lender' => true,
                'active' => true,
            ],
        ];

        AppNotification::upsert($notifications, ['name'], ['description', 'is_lender', 'active']);
    }
}
