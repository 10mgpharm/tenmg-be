<?php

namespace Database\Seeders;

use App\Models\ServiceProvider;
use Illuminate\Database\Seeder;

class ServiceProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Fincra',
                'slug' => 'fincra',
                'description' => 'Fincra payment infrastructure provider',
                'config' => null,
                'metadata' => [
                    'website' => 'https://fincra.com',
                ],
                'is_bvn_verification_provider' => false,
                'is_virtual_account_provider' => true,
                'is_virtual_card_provider' => false,
                'is_physical_card_provider' => false,
                'is_checkout_provider' => true,
                'is_bank_payout_provider' => true,
                'is_mobile_money_payout_provider' => false,
                'is_identity_verification_provider' => false,
                'currencies_supported' => ['NGN', 'USD'],
                'status' => 'active',
            ],
        ];

        foreach ($providers as $data) {
            ServiceProvider::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
