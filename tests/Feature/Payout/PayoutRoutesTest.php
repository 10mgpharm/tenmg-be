<?php

it('requires auth for bank list', function () {
    $this->getJson('/api/v1/bank/list')->assertStatus(401);
});

it('requires auth for verify account', function () {
    $this->postJson('/api/v1/bank/verify-account', [
        'account_number' => '0000000000',
        'bank_code' => '999',
    ])->assertStatus(401);
});

it('verify account accepts minimal payload', function () {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user, 'api');

    // Should work with just account_number and bank_code
    $this->postJson('/api/v1/bank/verify-account', [
        'account_number' => '0000000000',
        'bank_code' => '999',
    ])->assertStatus(200);
});

it('requires auth for payouts', function () {
    $this->postJson('/api/v1/payouts/withdraw', [
        'amount' => 100,
        'account_number' => '0000000000',
        'bank_code' => '999',
    ])->assertStatus(401);
});

it('payout accepts minimal payload', function () {
    $user = \App\Models\User::factory()->create();
    $user->assignRole('vendor'); // Assign vendor role
    $business = \App\Models\Business::factory()->create(['owner_id' => $user->id]);
    $currency = \App\Models\Currency::where('code', 'NGN')->first();
    $wallet = \App\Models\Wallet::factory()->create([
        'business_id' => $business->id,
        'wallet_type' => \App\Enums\WalletType::VENDOR_PAYOUT_WALLET->value,
        'currency_id' => $currency->id,
    ]);
    $this->actingAs($user, 'api');

    // Should work with just amount, account_number, and bank_code
    $this->postJson('/api/v1/payouts/withdraw', [
        'amount' => 100,
        'account_number' => '0000000000',
        'bank_code' => '999',
    ])->assertStatus(200);
});
