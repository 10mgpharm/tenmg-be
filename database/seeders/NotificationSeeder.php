<?php

namespace Database\Seeders;

use App\Models\Notification;
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
                'name' => 'New Medication',
                'description' => 'Get notified when a supplier include new type of medication, brand, category etc.',
                'is_admin' => true,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
            [
                'name' => 'Shopping List',
                'description' => 'Get notified when a pharmacy adds a new product to the shopping list.',
                'is_admin' => true,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
            [
                'name' => 'Order Payment Confirmation',
                'description' => 'Get notified of your commission when an order is paid for.',
                'is_admin' => true,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => false,
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
            [
                'name' => 'New User',
                'description' => 'Get notified about new vendor, pharmacy or supplier.',
                'is_admin' => true,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => false,
                'active' => true,
            ],
        ];
        $vendor_notifications = [
            [
                'name' => 'License Expiry',
                'description' => 'Get notified when your license is about to expire or has expired.',
                'is_admin' => false,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Customer\'s Credit Offer Status',
                'description' => 'Get notified of customer\s credit offer status.',
                'is_admin' => false,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Customer Repayment [auto or manuel payment]',
                'description' => 'Get notified when a repayment is done for your customers.',
                'is_admin' => false,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Lender Approve Customer Application',
                'description' => 'Get notified when a lender approves your customer\'s credit application.',
                'is_admin' => false,
                'is_supplier' => false,
                'is_pharmacy' => false,
                'is_vendor' => true,
                'active' => true,
            ],
            [
                'name' => 'Loan Offering',
                'description' => 'Get notified when admins sends loan offer to your customer.',
                'is_admin' => false,
                'is_supplier' => false,
                'is_pharmacy' => false,
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
            Notification::create($notification);
        }
    }
}
