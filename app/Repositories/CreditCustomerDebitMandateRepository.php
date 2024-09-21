<?php

namespace App\Repositories;

use App\Models\DebitMandate;

class CreditCustomerDebitMandateRepository
{
    public function createOrUpdateMandate($businessId, $customerId, array $data): DebitMandate
    {
        return DebitMandate::updateOrCreate(
            ['business_id' => $businessId, 'customer_id' => $customerId],
            $data
        );
    }

    public function findByAuthorizationCode($authorizationCode): ?DebitMandate
    {
        return DebitMandate::where('authorization_code', $authorizationCode)->first();
    }

    public function findByReference($reference): ?DebitMandate
    {
        return DebitMandate::where('reference', $reference)->first();
    }
}
