<?php

namespace App\Repositories;

use App\Models\CreditScore;
use App\Models\Customer;

class CreditScoreRepository
{
    public function store(array $data)
    {
        return CreditScore::create($data);
    }

    public function updateCreditScore($customerId, $creditScoreId)
    {
        $customer = Customer::find($customerId);
        $customer->credit_score_id = $creditScoreId;
        $customer->save();
        return $customer;
    }
}
