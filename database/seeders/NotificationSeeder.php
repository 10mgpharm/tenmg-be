<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_notifications = [
            [
                'name' => 'Goods Expiration',
                'description' => 'Get notified about expiration of goods.',
                'is_admin' => true,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
            [
                'name' => 'New Product Added',
                'description' => 'Get notified when admin and/or supplier adds new products',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'Shopping List Update',
                'description' => 'Get notified when products are added to the shopping list',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'New User Added',
                'description' => 'Get notified when a new user is added',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'Invitation Response',
                'description' => 'Get notified when an invited member accepts or rejects an invitation',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Application Submitted',
                'description' => 'Get notified when a loan application is submitted for review and approval',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Ignored',
                'description' => 'Get notified when customer\'s loan is ignored',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Repayment Made',
                'description' => 'Get notified when repayments are made by customers (auto or manual)',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Default',
                'description' => 'Get notified when my customer defaulted on loan repayment',
                'is_admin' => true,
                'active' => true,
            ],
            [
                'name' => 'New Message',
                'description' => 'Get notified when you have a new message from a supplier.',
                'is_admin' => true,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
        ];
        $vendor_notifications = [
            [
                'name' => 'Invitation Response',
                'description' => 'Get notified when an invited member accepts or rejects an invitation.',
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Application Submitted',
                'description' => 'Get notified when a loan application is submitted by your customers.',
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Application Approved',
                'description' => 'Get notified when a lender approves your customer\'s application.',
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Ignored',
                'description' => 'Receive a notification when a customer\'s loan is ignored.',
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Repayment Made',
                'description' => 'Get notified when repayments are made by customers (auto or manual).',
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Default',
                'description' => 'Get notified when your customer defaults on loan repayment.',
                'is_vendor' => true,
                'active' => true,
            ],
        ];

        $supplier_notifications = [
            [
                'name' => 'Order Payment',
                'description' => 'Get notified when an order is paid for an admin\'s commission.',
                'is_admin' => false,
                'is_supplier' => true,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
            [
                'name' => 'New Message',
                'description' => 'Get notified when you have a new message from the admin.',
                'is_admin' => false,
                'is_supplier' => true,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
            [
                'name' => 'New Product or Medication From Admin',
                'description' => 'Get notified when admin include new medication types, branches etc.',
                'is_admin' => false,
                'is_supplier' => true,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
        ];

        $notifications = [...$admin_notifications, ...$vendor_notifications, ...$supplier_notifications ];
        foreach ($notifications as $notification) {
            AppNotification::create($notification);
        }
    }
}
