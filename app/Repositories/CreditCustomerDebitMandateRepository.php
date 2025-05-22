<?php

namespace App\Repositories;

use App\Models\DebitMandate;
use Illuminate\Database\Eloquent\Collection;

class CreditCustomerDebitMandateRepository
{
    public function createOrUpdateMandate($businessId, $customerId, array $data): DebitMandate
    {
        return DebitMandate::updateOrCreate(
            ['business_id' => $businessId, 'customer_id' => $customerId],
            $data
        );
    }

    public function updateById(int $mandateId, array $data): DebitMandate
    {
        $mandate = DebitMandate::findOrFail($mandateId);
        $mandate->update($data);
        return $mandate;
    }

    public function findByAuthorizationCode($authorizationCode): ?DebitMandate
    {
        return DebitMandate::where('authorization_code', $authorizationCode)->first();
    }

    public function findByReference($reference): ?DebitMandate
    {
        return DebitMandate::where('reference', $reference)->first();
    }

    public function findPendingMandate(): Collection
    {
        return DebitMandate::where('chargeable', false)->get();
    }
}
