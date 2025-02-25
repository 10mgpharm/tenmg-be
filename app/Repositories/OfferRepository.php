<?php

namespace App\Repositories;

use App\Models\CreditOffer;
use Illuminate\Database\Eloquent\Collection;

class OfferRepository
{
    // Create a new loan offer
    public function updateOrCreate(array $data)
    {
        $where = [
            'business_id' => $data['business_id'],
            'customer_id' => $data['customer_id'],
        ];
        isset($data['application_id']) && $where['application_id'] = $data['application_id'];

        $payload = [];
        isset($data['offer_amount']) && $payload['offer_amount'] = $data['offer_amount'];
        isset($data['repayment_breakdown']) && $payload['repayment_breakdown'] = json_encode($data['repayment_breakdown']);
        isset($data['has_mandate']) && $payload['has_mandate'] = $data['has_mandate'];
        isset($data['has_active_debit_card']) && $payload['has_active_debit_card'] = $data['has_active_debit_card'];
        isset($data['is_valid']) && $payload['is_valid'] = $data['is_valid'];
        isset($data['accepted_at']) && $payload['accepted_at'] = $data['accepted_at'];
        isset($data['lender_id']) && $payload['lender_id'] = $data['lender_id'];

        return CreditOffer::updateOrCreate($where, $payload);
    }

    // Find offer by ID
    public function findById(int $id)
    {
        return CreditOffer::findOrFail($id);
    }

    // Find offer by unique identifier
    public function findByIdentifier(string $identifier)
    {
        return CreditOffer::where('identifier', $identifier)->firstOrFail();
    }

    // Get all loan offers
    public function getAll(): Collection
    {
        return CreditOffer::with(['business', 'customer', 'application'])->get();
    }

    // Filter loan offers based on criteria
    public function filter(array $criteria): Collection
    {
        $query = CreditOffer::query();

        if (isset($criteria['search'])) {
            $query->whereHas('customer', function ($q) use ($criteria) {
                $q->where('email', 'like', '%'.$criteria['search'].'%');
            });
        }

        if (isset($criteria['dateFrom']) && isset($criteria['dateTo'])) {
            $query->whereBetween('created_at', [$criteria['dateFrom'], $criteria['dateTo']]);
        }

        return $query->with(['business', 'customer', 'application'])->get();
    }

    // Delete loan offer by ID
    public function deleteById(int $id)
    {
        $offer = CreditOffer::findOrFail($id);
        if ($offer->accepted_at || $offer->rejected_at) {
            throw new \Exception('Cannot delete an offer that has been accepted or rejected.');
        }

        return $offer->delete();
    }

    // Enable/Disable loan offer
    public function toggleStatus(int $id, bool $active): CreditOffer
    {
        $offer = CreditOffer::findOrFail($id);
        $offer->active = $active;
        $offer->save();

        return $offer;
    }

    // Get all offers for a specific customer
    public function getOffersByCustomer(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return CreditOffer::where('customer_id', $customerId)
            ->with(['business', 'application'])
            ->get();
    }

    // Reject offer and provide reason
    public function rejectOffer(int $id, string $reason): CreditOffer
    {
        $offer = CreditOffer::findOrFail($id);
        $offer->rejected_at = now();
        $offer->rejection_reason = $reason;
        $offer->save();

        return $offer;
    }

    // Accept offer
    public function acceptOffer(int $id): CreditOffer
    {
        $offer = CreditOffer::findOrFail($id);
        $offer->accepted_at = now();
        $offer->save();

        return $offer;
    }
}
