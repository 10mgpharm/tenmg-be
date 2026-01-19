<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ServiceProvider;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Fincra exists and get its ID
        $fincra = ServiceProvider::firstOrCreate(
            ['slug' => 'fincra'],
            [
                'name' => 'Fincra',
                'description' => 'Fincra payment infrastructure provider',
                'config' => null,
                'metadata' => ['website' => 'https://fincra.com'],
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
            ]
        );

        $currencies = [
            [
                'classification' => 'fiat',
                'name' => 'Nigerian Naira',
                'code' => 'NGN',
                'symbol' => '₦',
                'slug' => 'ngn',
                'decimal_places' => 2,
                'icon' => null,
                'description' => 'Primary currency for Nigerian transactions',
                'tier_1_limits' => null,
                'tier_2_limits' => null,
                'tier_3_limits' => null,
                'country_code' => 'NGA',
                'virtual_account_provider' => $fincra->id,
                'temp_virtual_account_provider' => null,
                'virtual_card_provider' => null,
                'bank_transfer_collection_provider' => $fincra->id,
                'mobile_money_collection_provider' => null,
                'bank_transfer_payout_provider' => $fincra->id,
                'mobile_money_payout_provider' => null,
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'classification' => 'fiat',
                'name' => 'United States Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'slug' => 'usd',
                'decimal_places' => 2,
                'icon' => null,
                'description' => 'USD currency for international transactions',
                'tier_1_limits' => null,
                'tier_2_limits' => null,
                'tier_3_limits' => null,
                'country_code' => 'USA',
                'virtual_account_provider' => $fincra->id,
                'temp_virtual_account_provider' => null,
                'virtual_card_provider' => null,
                'bank_transfer_collection_provider' => $fincra->id,
                'mobile_money_collection_provider' => null,
                'bank_transfer_payout_provider' => $fincra->id,
                'mobile_money_payout_provider' => null,
                'status' => 'active',
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $data) {
            Currency::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
