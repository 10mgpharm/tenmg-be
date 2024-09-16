<?php

namespace App\Repositories;

use App\Models\CreditScore;

class CreditScoreRepository
{
    public function store(array $data)
    {
        return CreditScore::create($data);
    }
}
