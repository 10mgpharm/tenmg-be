<?php

namespace App\Repositories;

use App\Models\CreditBusinessRule;
use Illuminate\Database\Eloquent\Collection;

class CreditBusinessRuleRepository
{
    public function getActiveRules(): Collection
    {
        return CreditBusinessRule::where('active', true)->get();
    }
}
