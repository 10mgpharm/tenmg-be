# Payout flow (Fincra)

- Provider: Fincra only (additional providers can be added via `PayoutProviderService` mapping).
- Entry points: authenticated users (`auth:api`, `scope:full`) hit `/api/v1/bank/list`, `/api/v1/bank/verify-account`, `/api/v1/payouts/withdraw`.
- Controller: `App\Http\Controllers\API\PayoutController`.
- Service orchestration: `App\Services\Payout\PayoutService` handles balance checks, transaction logging, and provider dispatch.
- Provider implementation: `App\Services\Payout\FincraPayoutProvider` (list banks, verify account, bank transfer, status).
- Transactions: stored in `transactions` table with `transaction_type=withdrawal`, `transaction_method=bank_transfer`; wallet debited via `WalletService`.

## Minimal request payload (withdraw)
```json
{
  "amount": 5000,
  "account_number": "0123456789",
  "bank_code": "999",
  "bank_name": "Example Bank",
  "narration": "Withdrawal to bank"
}
```

**Note**: `wallet_id`, `currency`, and `account_type` are now handled automatically by the backend:
- `wallet_id`: Auto-selects based on user role (vendor → VENDOR_PAYOUT_WALLET, lender → LENDER_WALLET, admin → ADMIN_WALLET)
- `currency`: Prefers NGN wallet, falls back to any available wallet of the correct type
- `account_type`: Always 'nuban' for Nigerian accounts

## Required config
- `services.fincra.url` (or `services.fincra.base_url`)
- `services.fincra.secret` (or `services.fincra.api_key`)
- `services.fincra.business_id`
- Optional: `services.fincra.timeout`, `services.fincra.retries`

## Notes
- Bank list and account verification now use the payout provider (no mock unless env handling upstream).
- Any authenticated user with a wallet may withdraw; business is resolved from `ownerBusinessType` or first business linked to the user.
- On provider failure, wallet is auto-refunded and transaction is marked failed.
